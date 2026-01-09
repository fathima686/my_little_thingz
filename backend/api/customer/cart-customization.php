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
  // Check if custom_requests table exists
  $tableExists = false;
  try {
    $result = $db->query("SHOW TABLES LIKE 'custom_requests'");
    $tableExists = $result && $result->num_rows > 0;
  } catch (Exception $e) {
    $tableExists = false;
  }

  if (!$tableExists) {
    // Table doesn't exist - create with customer_id (preferred column name)
    $db->query("CREATE TABLE custom_requests (
      id INT AUTO_INCREMENT PRIMARY KEY,
      customer_id INT NULL DEFAULT 0,
      user_id INT NULL,
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
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_customer_id (customer_id),
      INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB");
  } else {
    // Table exists - ensure customer_id column exists (add if missing)
    if (!hasColumn($db, 'custom_requests', 'customer_id')) {
      try {
        $db->query("ALTER TABLE custom_requests ADD COLUMN customer_id INT NULL DEFAULT 0 AFTER id");
      } catch (Exception $e) {
        // Column might already exist, ignore
      }
    }
    // Also ensure user_id exists for backward compatibility
    if (!hasColumn($db, 'custom_requests', 'user_id')) {
      try {
        $db->query("ALTER TABLE custom_requests ADD COLUMN user_id INT NULL AFTER id");
      } catch (Exception $e) {
        // Column might already exist, ignore
      }
    }
  }

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

  // Dynamically build INSERT statement based on existing columns
  // Check which user ID column exists (user_id or customer_id) - prefer customer_id
  $hasUserId = hasColumn($mysqli, 'custom_requests', 'user_id');
  $hasCustomerId = hasColumn($mysqli, 'custom_requests', 'customer_id');
  
  // Prioritize customer_id if it exists, otherwise use user_id
  if ($hasCustomerId) {
    $userIdColumn = 'customer_id';
  } elseif ($hasUserId) {
    $userIdColumn = 'user_id';
  } else {
    // Neither exists - default to customer_id and try to add it
    $userIdColumn = 'customer_id';
    try {
      $mysqli->query("ALTER TABLE custom_requests ADD COLUMN customer_id INT NULL DEFAULT 0 AFTER id");
    } catch (Exception $e) {
      // Ignore if it fails
    }
  }
  
  // Check all optional columns
  $colChecks = [
    'category_id' => hasColumn($mysqli, 'custom_requests', 'category_id'),
    'occasion' => hasColumn($mysqli, 'custom_requests', 'occasion'),
    'budget_min' => hasColumn($mysqli, 'custom_requests', 'budget_min'),
    'budget_max' => hasColumn($mysqli, 'custom_requests', 'budget_max'),
    'deadline' => hasColumn($mysqli, 'custom_requests', 'deadline'),
    'special_instructions' => hasColumn($mysqli, 'custom_requests', 'special_instructions'),
    'source' => hasColumn($mysqli, 'custom_requests', 'source'),
    'status' => hasColumn($mysqli, 'custom_requests', 'status'),
    'created_at' => hasColumn($mysqli, 'custom_requests', 'created_at')
  ];
  
  // Build columns and values arrays dynamically
  $columns = [$userIdColumn, 'title', 'description']; // Required columns
  $placeholders = ['?', '?', '?']; // For userId, title, description
  $bindTypes = 'iss'; // int, string, string
  $bindValues = [&$userId, &$title, &$description];
  
  // Add optional columns if they exist
  if ($colChecks['category_id']) {
    $columns[] = 'category_id';
    $placeholders[] = 'NULL';
  }
  
  if ($colChecks['occasion']) {
    $columns[] = 'occasion';
    $placeholders[] = '?';
    $bindTypes .= 's';
    $bindValues[] = &$occasion;
  }
  
  if ($colChecks['budget_min']) {
    $columns[] = 'budget_min';
    $placeholders[] = 'NULL';
  }
  
  if ($colChecks['budget_max']) {
    $columns[] = 'budget_max';
    $placeholders[] = 'NULL';
  }
  
  if ($colChecks['deadline']) {
    $columns[] = 'deadline';
    $placeholders[] = '?';
    $bindTypes .= 's';
    $bindValues[] = &$deadline;
  }
  
  if ($colChecks['special_instructions']) {
    $columns[] = 'special_instructions';
    $placeholders[] = "''";
  }
  
  if ($colChecks['source']) {
    $columns[] = 'source';
    $placeholders[] = "'cart'";
  }
  
  if ($colChecks['status']) {
    $columns[] = 'status';
    $placeholders[] = "'pending'";
  }
  
  if ($colChecks['created_at']) {
    $columns[] = 'created_at';
    $placeholders[] = 'NOW()';
  }
  
  // Build and execute SQL
  $columnList = implode(', ', $columns);
  $placeholderList = implode(', ', $placeholders);
  $sql = "INSERT INTO custom_requests ($columnList) VALUES ($placeholderList)";
  
  $st = $mysqli->prepare($sql);
  if (!$st) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement: ' . $mysqli->error]);
    exit;
  }
  
  // Bind parameters dynamically
  $st->bind_param($bindTypes, ...$bindValues);

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
      
      // Check which image columns exist
      $hasImagePath = hasColumn($mysqli, 'custom_request_images', 'image_path');
      $hasImageUrl = hasColumn($mysqli, 'custom_request_images', 'image_url');
      $hasFilename = hasColumn($mysqli, 'custom_request_images', 'filename');
      
      for ($i = 0; $i < count($tmpNames); $i++) {
        if ($errors[$i] === UPLOAD_ERR_OK) {
          $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $names[$i]);
          $fileName = uniqid('cart_', true) . '_' . $safeName;
          $filePath = $uploadDir . $fileName;
          if (move_uploaded_file($tmpNames[$i], $filePath)) {
            $relPath = 'uploads/custom-requests/' . $fileName;
            
            // Build INSERT dynamically based on available columns
            if ($hasImageUrl) {
              // Use image_url column
              if ($hasFilename) {
                $sti = $mysqli->prepare("INSERT INTO custom_request_images (request_id, image_url, filename, uploaded_at) VALUES (?, ?, ?, NOW())");
                $sti->bind_param('iss', $requestId, $relPath, $names[$i]);
              } else {
                $sti = $mysqli->prepare("INSERT INTO custom_request_images (request_id, image_url, uploaded_at) VALUES (?, ?, NOW())");
                $sti->bind_param('is', $requestId, $relPath);
              }
            } elseif ($hasImagePath) {
              // Use image_path column
              if ($hasFilename) {
                $sti = $mysqli->prepare("INSERT INTO custom_request_images (request_id, image_path, filename, uploaded_at) VALUES (?, ?, ?, NOW())");
                $sti->bind_param('iss', $requestId, $relPath, $names[$i]);
              } else {
                $sti = $mysqli->prepare("INSERT INTO custom_request_images (request_id, image_path, uploaded_at) VALUES (?, ?, NOW())");
                $sti->bind_param('is', $requestId, $relPath);
              }
            } else {
              // Fallback: try image_url
              if ($hasFilename) {
                $sti = $mysqli->prepare("INSERT INTO custom_request_images (request_id, image_url, filename, uploaded_at) VALUES (?, ?, ?, NOW())");
                $sti->bind_param('iss', $requestId, $relPath, $names[$i]);
              } else {
                $sti = $mysqli->prepare("INSERT INTO custom_request_images (request_id, image_url, uploaded_at) VALUES (?, ?, NOW())");
                $sti->bind_param('is', $requestId, $relPath);
              }
            }
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


