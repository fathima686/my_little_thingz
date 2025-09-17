<?php
header('Content-Type: application/json');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_origins = ['http://localhost:5173', 'http://127.0.0.1:5173'];
if (in_array($origin, $allowed_origins, true)) {
  header("Access-Control-Allow-Origin: $origin");
} else {
  header("Access-Control-Allow-Origin: *");
}
header('Vary: Origin');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli('localhost', 'root', '', 'my_little_thingz');
$mysqli->set_charset('utf8mb4');
if ($mysqli->connect_error) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'DB connect failed: ' . $mysqli->connect_error]);
  exit;
}

function hasColumn(mysqli $db, string $table, string $column): bool {
  try {
    $res = $db->query("SHOW COLUMNS FROM `" . $db->real_escape_string($table) . "` LIKE '" . $db->real_escape_string($column) . "'");
    return $res && $res->num_rows > 0;
  } catch (Throwable $e) {
    return false;
  }
}

function ensure_schema(mysqli $db) {
  // Ensure custom_requests table exists with required columns
  $db->query("CREATE TABLE IF NOT EXISTS custom_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category_id INT NULL,
    occasion VARCHAR(100) NULL,
    budget_min DECIMAL(10,2) NULL,
    budget_max DECIMAL(10,2) NULL,
    deadline DATE NULL,
    special_instructions TEXT NULL,
    source ENUM('form','cart') NOT NULL DEFAULT 'form',
    status ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB");

  // Ensure images table exists
  $db->query("CREATE TABLE IF NOT EXISTS custom_request_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB");

  // Columns that might be missing on older DBs
  try { if (!hasColumn($db, 'custom_requests', 'occasion')) { $db->query("ALTER TABLE custom_requests ADD COLUMN occasion VARCHAR(100) NULL AFTER category_id"); } } catch (Throwable $e) {}
  try { if (!hasColumn($db, 'custom_requests', 'source')) { $db->query("ALTER TABLE custom_requests ADD COLUMN source ENUM('form','cart') NOT NULL DEFAULT 'form' AFTER special_instructions"); } } catch (Throwable $e) {}
}

try { ensure_schema($mysqli); } catch (Throwable $e) {}

$userId = isset($_SERVER['HTTP_X_USER_ID']) ? (int)$_SERVER['HTTP_X_USER_ID'] : 0;
if ($userId <= 0) { echo json_encode(['status' => 'error', 'message' => 'User ID required']); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
  exit;
}

try {
  // Expected multipart/form-data with fields: description, occasion, date (deadline), reference_images[]
  $description = $_POST['description'] ?? '';
  $occasion = $_POST['occasion'] ?? '';
  $deadline = $_POST['date'] ?? ($_POST['deadline'] ?? '');
  $title = $_POST['title'] ?? '';

  if (trim($description) === '') { echo json_encode(['status'=>'error','message'=>'Description is required']); exit; }
  if (trim($occasion) === '') { echo json_encode(['status'=>'error','message'=>'Occasion is required']); exit; }
  if (trim($deadline) === '') { echo json_encode(['status'=>'error','message'=>'Date is required']); exit; }

  // Require at least one image
  $hasImage = !empty($_FILES['reference_images']) && (
    (is_array($_FILES['reference_images']['error']) && count(array_filter($_FILES['reference_images']['error'], function ($e) { return (int)$e === UPLOAD_ERR_OK; })) > 0)
    || (!is_array($_FILES['reference_images']['error']) && (int)$_FILES['reference_images']['error'] === UPLOAD_ERR_OK)
  );
  if (!$hasImage) { echo json_encode(['status'=>'error','message'=>'At least one reference image is required']); exit; }

  if (trim($title) === '') {
    $title = 'Cart customization - ' . $occasion . ' - ' . $deadline;
  }

  $hasOccasionIns = hasColumn($mysqli, 'custom_requests', 'occasion');
  $hasSourceIns = hasColumn($mysqli, 'custom_requests', 'source');

  if ($hasOccasionIns && $hasSourceIns) {
    $sql = "INSERT INTO custom_requests (user_id, title, description, category_id, occasion, budget_min, budget_max, deadline, special_instructions, source, status, created_at)
            VALUES (?, ?, ?, NULL, ?, NULL, NULL, ?, '', 'cart', 'pending', NOW())";
    $st = $mysqli->prepare($sql);
    $st->bind_param('issss', $userId, $title, $description, $occasion, $deadline);
  } elseif ($hasOccasionIns && !$hasSourceIns) {
    $sql = "INSERT INTO custom_requests (user_id, title, description, category_id, occasion, budget_min, budget_max, deadline, special_instructions, status, created_at)
            VALUES (?, ?, ?, NULL, ?, NULL, NULL, ?, '', 'pending', NOW())";
    $st = $mysqli->prepare($sql);
    $st->bind_param('issss', $userId, $title, $description, $occasion, $deadline);
  } elseif (!$hasOccasionIns && $hasSourceIns) {
    $sql = "INSERT INTO custom_requests (user_id, title, description, category_id, budget_min, budget_max, deadline, special_instructions, source, status, created_at)
            VALUES (?, ?, ?, NULL, NULL, NULL, ?, '', 'cart', 'pending', NOW())";
    $st = $mysqli->prepare($sql);
    $st->bind_param('isss', $userId, $title, $description, $deadline);
  } else {
    $sql = "INSERT INTO custom_requests (user_id, title, description, category_id, budget_min, budget_max, deadline, special_instructions, status, created_at)
            VALUES (?, ?, ?, NULL, NULL, NULL, ?, '', 'pending', NOW())";
    $st = $mysqli->prepare($sql);
    $st->bind_param('isss', $userId, $title, $description, $deadline);
  }

  try {
    $st->execute();
  } catch (Throwable $ex) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to save request', 'detail' => $ex->getMessage()]);
    $st->close();
    exit;
  }
  $requestId = $st->insert_id;
  $st->close();

  // Upload images
  $uploadDir = __DIR__ . '/../../uploads/custom-requests/';
  if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0755, true); }
  $files = $_FILES['reference_images'];
  $tmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
  $names    = is_array($files['name']) ? $files['name'] : [$files['name']];
  $errors   = is_array($files['error']) ? $files['error'] : [$files['error']];
  for ($i = 0; $i < count($tmpNames); $i++) {
    if ($errors[$i] === UPLOAD_ERR_OK) {
      $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $names[$i]);
      $fileName = uniqid('cart_', true) . '_' . $safeName;
      $filePath = $uploadDir . $fileName;
      if (move_uploaded_file($tmpNames[$i], $filePath)) {
        $relPath = 'uploads/custom-requests/' . $fileName;
        $sti = $mysqli->prepare("INSERT INTO custom_request_images (request_id, image_path, uploaded_at) VALUES (?, ?, NOW())");
        $sti->bind_param('is', $requestId, $relPath);
        $sti->execute();
        $sti->close();
      }
    }
  }

  echo json_encode(['status' => 'success', 'message' => 'Cart customization request submitted', 'request_id' => $requestId]);
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Request failed', 'detail' => $e->getMessage()]);
}


