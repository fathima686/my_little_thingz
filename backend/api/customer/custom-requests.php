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

  // Check if custom_requests table exists
  $tableExists = false;
  try {
    $result = $db->query("SHOW TABLES LIKE 'custom_requests'");
    $tableExists = $result && $result->num_rows > 0;
  } catch (Exception $e) {
    $tableExists = false;
  }

  if (!$tableExists) {
    // Table doesn't exist, create it with customer_id (preferred column name)
    $db->query("CREATE TABLE custom_requests (
      id INT AUTO_INCREMENT PRIMARY KEY,
      customer_id INT NOT NULL DEFAULT 0,
      user_id INT NULL,
      title VARCHAR(255) NOT NULL,
      description TEXT NOT NULL,
      category_id INT NULL,
      occasion VARCHAR(100) NULL,
      budget_min DECIMAL(10,2) NULL,
      budget_max DECIMAL(10,2) NULL,
      deadline DATE NULL,
      special_instructions TEXT NULL,
      gift_tier ENUM('budget','premium') NULL DEFAULT 'budget',
      source ENUM('form','cart') NOT NULL DEFAULT 'form',
      status ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_customer_id (customer_id),
      INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB");
  } else {
    // Table exists - add missing columns if needed
    // Check and add customer_id if missing
    if (!hasColumn($db, 'custom_requests', 'customer_id')) {
      try {
        $db->query("ALTER TABLE custom_requests ADD COLUMN customer_id INT NULL DEFAULT 0 AFTER id");
      } catch (Exception $e) {
        // Column might already exist, ignore
      }
    }
    // Check and add user_id if missing (for backward compatibility)
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

  // Try to add occasion column if missing (older DBs)
  try {
    if (!hasColumn($db, 'custom_requests', 'occasion')) {
      $db->query("ALTER TABLE custom_requests ADD COLUMN occasion VARCHAR(100) NULL AFTER category_id");
    }
  } catch (Throwable $e) {
    // ignore; we'll handle missing column dynamically below
  }

  // Ensure source column exists
  try {
    if (!hasColumn($db, 'custom_requests', 'source')) {
      $db->query("ALTER TABLE custom_requests ADD COLUMN source ENUM('form','cart') NOT NULL DEFAULT 'form' AFTER special_instructions");
    }
  } catch (Throwable $e) {
    // ignore; inserts will fallback if column missing
  }

  // Ensure gift_tier column exists
  try {
    if (!hasColumn($db, 'custom_requests', 'gift_tier')) {
      $db->query("ALTER TABLE custom_requests ADD COLUMN gift_tier ENUM('budget','premium') NULL DEFAULT 'budget' AFTER special_instructions");
    }
  } catch (Throwable $e) {
    // ignore; inserts will fallback if column missing
  }
}

try { ensure_schema($mysqli); } catch (Throwable $e) {}

// Strict User ID validation
$userId = isset($_SERVER['HTTP_X_USER_ID']) ? trim($_SERVER['HTTP_X_USER_ID']) : '';
if (empty($userId) || !ctype_digit($userId)) {
  http_response_code(401);
  echo json_encode(['status' => 'error', 'message' => 'Valid user ID required']);
  exit;
}
$userId = (int)$userId;
if ($userId <= 0) {
  http_response_code(401);
  echo json_encode(['status' => 'error', 'message' => 'User ID must be a positive integer']);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
  if ($method === 'GET') {
    // List this user's requests (works even if 'occasion' or 'gift_tier' column doesn't exist)
    $hasOccasion = hasColumn($mysqli, 'custom_requests', 'occasion');
    $hasGiftTier = hasColumn($mysqli, 'custom_requests', 'gift_tier');
    
    // Check which user ID column exists (user_id or customer_id)
    $hasUserId = hasColumn($mysqli, 'custom_requests', 'user_id');
    $hasCustomerId = hasColumn($mysqli, 'custom_requests', 'customer_id');
    
    // Prioritize customer_id if it exists, otherwise use user_id
    if ($hasCustomerId) {
      $userIdColumn = 'cr.customer_id';
    } elseif ($hasUserId) {
      $userIdColumn = 'cr.user_id';
    } else {
      // Neither exists - default to customer_id (shouldn't happen if ensure_schema ran)
      $userIdColumn = 'cr.customer_id';
    }
    
    $occasionSelect = $hasOccasion ? 'cr.occasion,' : "'' AS occasion,";
    $giftTierSelect = $hasGiftTier ? 'cr.gift_tier,' : "'' AS gift_tier,";
    $sql = "SELECT cr.id, cr.title, cr.description, $occasionSelect cr.budget_min, cr.budget_max, $giftTierSelect cr.deadline, cr.status, cr.created_at, cr.category_id, c.name AS category_name
            FROM custom_requests cr
            LEFT JOIN categories c ON c.id = cr.category_id
            WHERE $userIdColumn = ?
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
      
      // Get design status and information for this request
      // Check if custom_request_designs table exists
      $hasDesignTable = false;
      try {
        $checkTable = $mysqli->query("SHOW TABLES LIKE 'custom_request_designs'");
        $hasDesignTable = $checkTable && $checkTable->num_rows > 0;
      } catch (Exception $e) {
        $hasDesignTable = false;
      }
      
      if ($hasDesignTable) {
        $designStmt = $mysqli->prepare("
          SELECT id, status, design_image_url, design_pdf_url, created_at, updated_at, version
          FROM custom_request_designs
          WHERE request_id = ?
          ORDER BY version DESC
          LIMIT 1
        ");
        if ($designStmt) {
          $designStmt->bind_param('i', $r['id']);
          $designStmt->execute();
          $designResult = $designStmt->get_result();
          $design = $designResult->fetch_assoc();
          
          if ($design) {
            // Add design information to request
            $r['design_status'] = $design['status'];
            
            // Construct full URLs for design images
            if ($design['design_image_url']) {
              $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
              $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
              if (!preg_match('/^https?:\/\//', $design['design_image_url'])) {
                $r['design_image_url'] = $scheme . '://' . $host . '/my_little_thingz/backend/' . ltrim($design['design_image_url'], '/');
              } else {
                $r['design_image_url'] = $design['design_image_url'];
              }
            } else {
              $r['design_image_url'] = null;
            }
            
            if ($design['design_pdf_url']) {
              $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
              $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
              if (!preg_match('/^https?:\/\//', $design['design_pdf_url'])) {
                $r['design_pdf_url'] = $scheme . '://' . $host . '/my_little_thingz/backend/' . ltrim($design['design_pdf_url'], '/');
              } else {
                $r['design_pdf_url'] = $design['design_pdf_url'];
              }
            } else {
              $r['design_pdf_url'] = null;
            }
            
            $r['design_updated_at'] = $design['updated_at'];
            $r['design_version'] = $design['version'];
            
            // Update request status based on design status for better customer visibility
            if ($design['status'] === 'design_completed' && $r['status'] === 'in_progress') {
              // Don't change main status, but design_status will show it's completed
            }
          } else {
            $r['design_status'] = null;
            $r['design_image_url'] = null;
            $r['design_pdf_url'] = null;
          }
          $designStmt->close();
        }
      } else {
        $r['design_status'] = null;
        $r['design_image_url'] = null;
        $r['design_pdf_url'] = null;
      }
      
      $rows[] = $r;
    }
    $st->close();

    // Attach images for each request so the frontend can render them
    $ids = array_column($rows, 'id');
    if (!empty($ids)) {
      // Check which image column exists (image_path or image_url)
      $hasImagePath = hasColumn($mysqli, 'custom_request_images', 'image_path');
      $hasImageUrl = hasColumn($mysqli, 'custom_request_images', 'image_url');
      $imageColumn = $hasImageUrl ? 'image_url' : ($hasImagePath ? 'image_path' : 'image_url'); // Default to image_url
      
      // Build a dynamic IN clause safely with prepared statement
      $in = implode(',', array_fill(0, count($ids), '?'));
      $types = str_repeat('i', count($ids));
      $sqlImgs = "SELECT request_id, $imageColumn FROM custom_request_images WHERE request_id IN ($in) ORDER BY uploaded_at ASC";
      $stImgs = $mysqli->prepare($sqlImgs);
      // Spread IDs as params
      $stImgs->bind_param($types, ...$ids);
      $stImgs->execute();
      $rsImgs = $stImgs->get_result();
      $byReq = [];
      while ($im = $rsImgs->fetch_assoc()) {
        $rid = (int)$im['request_id'];
        if (!isset($byReq[$rid])) { $byReq[$rid] = []; }

        // Construct full URL for image (similar to profile images)
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $imagePath = $im[$imageColumn];
        $fullImageUrl = $scheme . '://' . $host . '/my_little_thingz/backend/' . $imagePath;

        // Add cache busting parameter
        $fullPath = __DIR__ . '/../../' . $imagePath;
        if (file_exists($fullPath)) {
          $fullImageUrl .= '?v=' . filemtime($fullPath);
        }

        $byReq[$rid][] = $fullImageUrl;
      }
      $stImgs->close();
      foreach ($rows as &$r) { $r['images'] = $byReq[$r['id']] ?? []; }
      unset($r);
    }

    echo json_encode(['status' => 'success', 'requests' => $rows]);
    exit;
  }

  if ($method === 'POST') {
    // Accept only: title, occasion, description, budget, date, gift_tier, images
    $title = $_POST['title'] ?? '';
    $occasion = $_POST['occasion'] ?? null;
    $description = $_POST['description'] ?? '';
    $budget = $_POST['budget'] ?? null; // map to budget_min
    $deadline = $_POST['date'] ?? ($_POST['deadline'] ?? null); // allow either 'date' or 'deadline'
    $gift_tier = $_POST['gift_tier'] ?? 'budget'; // default to budget
    $source = isset($_POST['source']) && strtolower((string)$_POST['source']) === 'cart' ? 'cart' : 'form';
    
    if ($source === 'cart') {
      // Cart customization: require description, occasion, date, and at least one image
      if (trim($description) === '') {
        echo json_encode(['status' => 'error', 'message' => 'Description is required for cart customization']);
        exit;
      }
      if ($occasion === null || trim((string)$occasion) === '') {
        echo json_encode(['status' => 'error', 'message' => 'Occasion is required for cart customization']);
        exit;
      }
      if ($deadline === null || trim((string)$deadline) === '') {
        echo json_encode(['status' => 'error', 'message' => 'Date is required for cart customization']);
        exit;
      }
      $hasImage = !empty($_FILES['reference_images']) && (
        (is_array($_FILES['reference_images']['error']) && count(array_filter($_FILES['reference_images']['error'], function ($e) { return (int)$e === UPLOAD_ERR_OK; })) > 0)
        || (!is_array($_FILES['reference_images']['error']) && (int)$_FILES['reference_images']['error'] === UPLOAD_ERR_OK)
      );
      if (!$hasImage) {
        echo json_encode(['status' => 'error', 'message' => 'At least one reference image is required for cart customization']);
        exit;
      }
      if (trim($title) === '') {
        $when = $deadline && $deadline !== '' ? $deadline : date('Y-m-d');
        $occ = $occasion && $occasion !== '' ? $occasion : 'General';
        $title = 'Cart customization - ' . $occ . ' - ' . $when;
      }
    } else {
      // Normal flow: require title and description
      if (trim($title) === '' || trim($description) === '') {
        echo json_encode(['status' => 'error', 'message' => 'Title and description are required']);
        exit;
      }
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
      'gift_tier' => hasColumn($mysqli, 'custom_requests', 'gift_tier'),
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
      $placeholders[] = '?';
      $bindTypes .= 's';
      $budgetStr = null;
      if ($budget !== null && $budget !== '') {
        $budgetStr = (string)(float)$budget;
      }
      $bindValues[] = &$budgetStr;
    }
    
    if ($colChecks['budget_max']) {
      $columns[] = 'budget_max';
      $placeholders[] = 'NULL';
    }
    
    if ($colChecks['deadline']) {
      $columns[] = 'deadline';
      $placeholders[] = '?';
      $bindTypes .= 's';
      $deadlineVal = ($deadline && $deadline !== '') ? $deadline : null;
      $bindValues[] = &$deadlineVal;
    }
    
    if ($colChecks['special_instructions']) {
      $columns[] = 'special_instructions';
      $placeholders[] = "''";
    }
    
    if ($colChecks['gift_tier']) {
      $columns[] = 'gift_tier';
      $placeholders[] = '?';
      $bindTypes .= 's';
      $bindValues[] = &$gift_tier;
    }
    
    if ($colChecks['source']) {
      $columns[] = 'source';
      $placeholders[] = '?';
      $bindTypes .= 's';
      $bindValues[] = &$source;
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
            // Check which image columns exist
            $hasImagePath = hasColumn($mysqli, 'custom_request_images', 'image_path');
            $hasImageUrl = hasColumn($mysqli, 'custom_request_images', 'image_url');
            $hasFilename = hasColumn($mysqli, 'custom_request_images', 'filename');
            
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