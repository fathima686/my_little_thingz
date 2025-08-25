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
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// Use mysqli (consistent with other endpoints)
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
  // Ensure categories table exists (used in joins)
  $db->query("CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB");

  // Ensure custom_requests table exists (minimal columns used by this endpoint)
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

  // Try to add occasion column if missing (older DBs)
  try {
    if (!hasColumn($db, 'custom_requests', 'occasion')) {
      $db->query("ALTER TABLE custom_requests ADD COLUMN occasion VARCHAR(100) NULL AFTER category_id");
    }
  } catch (Throwable $e) {
    // ignore; we'll handle missing column dynamically below
  }
}

try { ensure_schema($mysqli); } catch (Throwable $e) {}

$userId = isset($_SERVER['HTTP_X_USER_ID']) ? (int)$_SERVER['HTTP_X_USER_ID'] : 0;
if ($userId <= 0) {
  echo json_encode(['status' => 'error', 'message' => 'User ID required']);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
  if ($method === 'GET') {
    // List this user's requests (works even if 'occasion' column doesn't exist)
    $hasOccasion = hasColumn($mysqli, 'custom_requests', 'occasion');
    $occasionSelect = $hasOccasion ? 'cr.occasion,' : "'' AS occasion,";
    $sql = "SELECT cr.id, cr.title, cr.description, $occasionSelect cr.budget_min, cr.budget_max, cr.deadline, cr.status, cr.created_at, cr.category_id, c.name AS category_name
            FROM custom_requests cr
            LEFT JOIN categories c ON c.id = cr.category_id
            WHERE cr.user_id = ?
            ORDER BY cr.created_at DESC";
    $st = $mysqli->prepare($sql);
    $st->bind_param('i', $userId);
    $st->execute();
    $rs = $st->get_result();
    $rows = [];
    while ($r = $rs->fetch_assoc()) {
      // format
      if ($r['budget_min'] !== null) { $r['budget_min'] = number_format((float)$r['budget_min'], 2); }
      if ($r['budget_max'] !== null) { $r['budget_max'] = number_format((float)$r['budget_max'], 2); }
      $rows[] = $r;
    }
    $st->close();

    echo json_encode(['status' => 'success', 'requests' => $rows]);
    exit;
  }

  if ($method === 'POST') {
    // Accept only: title, occasion, description, budget, date, images
    $title = $_POST['title'] ?? '';
    $occasion = $_POST['occasion'] ?? null;
    $description = $_POST['description'] ?? '';
    $budget = $_POST['budget'] ?? null; // map to budget_min
    $deadline = $_POST['date'] ?? ($_POST['deadline'] ?? null); // allow either 'date' or 'deadline'

    if (trim($title) === '' || trim($description) === '') {
      echo json_encode(['status' => 'error', 'message' => 'Title and description are required']);
      exit;
    }

    // Insert minimal record, handle absence of 'occasion' column gracefully
    $hasOccasionIns = hasColumn($mysqli, 'custom_requests', 'occasion');
    if ($hasOccasionIns) {
      $sql = "INSERT INTO custom_requests (user_id, title, description, category_id, occasion, budget_min, budget_max, deadline, special_instructions, status, created_at)
              VALUES (?, ?, ?, NULL, ?, ?, NULL, ?, '', 'pending', NOW())";
      $st = $mysqli->prepare($sql);
    } else {
      $sql = "INSERT INTO custom_requests (user_id, title, description, category_id, budget_min, budget_max, deadline, special_instructions, status, created_at)
              VALUES (?, ?, ?, NULL, ?, NULL, ?, '', 'pending', NOW())";
      $st = $mysqli->prepare($sql);
      // If no occasion column, ignore it
      $occasion = null;
    }

    // normalize values
    $budgetVal = null;
    if ($budget !== null && $budget !== '') {
      $budgetVal = (float)$budget;
    }
    $deadlineVal = ($deadline && $deadline !== '') ? $deadline : null;

    // Bind using strings for nullable values to avoid type errors when NULL is passed
    $budgetStr = $budgetVal !== null ? (string)$budgetVal : null;
    if ($hasOccasionIns) {
      $st->bind_param('isssss', $userId, $title, $description, $occasion, $budgetStr, $deadlineVal);
      // types: i (user_id), s, s, s, s (budget nullable), s (deadline nullable)
    } else {
      $st->bind_param('issss', $userId, $title, $description, $budgetStr, $deadlineVal);
      // types: i (user_id), s, s, s (budget nullable), s (deadline nullable)
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
    if (!empty($_FILES['reference_images'])) {
      $uploadDir = __DIR__ . '/../../uploads/custom-requests/';
      if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0755, true); }

      $files = $_FILES['reference_images'];
      // Normalize single vs multiple
      $tmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
      $names    = is_array($files['name']) ? $files['name'] : [$files['name']];
      $errors   = is_array($files['error']) ? $files['error'] : [$files['error']];

      for ($i = 0; $i < count($tmpNames); $i++) {
        if ($errors[$i] === UPLOAD_ERR_OK) {
          $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $names[$i]);
          $fileName = uniqid('', true) . '_' . $safeName;
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
    }

    echo json_encode(['status' => 'success', 'message' => 'Custom request submitted successfully', 'request_id' => $requestId]);
    exit;
  }

  http_response_code(405);
  echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Request failed', 'detail' => $e->getMessage()]);
}