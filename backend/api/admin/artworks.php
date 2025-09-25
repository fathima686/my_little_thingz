<?php
// Admin Artworks Management API
// Methods: GET (list), POST (create), DELETE (delete)

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_origins = [
  'http://localhost',
  'http://127.0.0.1',
  'http://localhost:5173',
  'http://127.0.0.1:5173',
  'http://localhost:8080'
];
if ($origin && in_array($origin, $allowed_origins, true)) {
  header("Access-Control-Allow-Origin: $origin");
} else {
  // Fallback for same-origin Apache served frontend
  header("Access-Control-Allow-Origin: *");
}
header("Vary: Origin");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-User-Id");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli("localhost", "root", "", "my_little_thingz");
$mysqli->set_charset('utf8mb4');
if ($mysqli->connect_error) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "DB connect failed: " . $mysqli->connect_error]);
  exit;
}

function ensure_schema(mysqli $db) {
  // Minimal ensure for categories and artworks
  $db->query("CREATE TABLE IF NOT EXISTS categories (\n    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n    name VARCHAR(120) NOT NULL UNIQUE,\n    description VARCHAR(255) NULL,\n    status ENUM('active','inactive') NOT NULL DEFAULT 'active',\n    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

  $db->query("CREATE TABLE IF NOT EXISTS artworks (\n    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n    title VARCHAR(180) NOT NULL,\n    description TEXT NULL,\n    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,\n    image_url VARCHAR(255) NOT NULL,\n    category_id INT UNSIGNED NULL,\n    artist_id INT UNSIGNED NULL,\n    availability ENUM('in_stock','out_of_stock','made_to_order') NOT NULL DEFAULT 'in_stock',\n    status ENUM('active','inactive') NOT NULL DEFAULT 'active',\n    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n    KEY idx_artworks_category (category_id),\n    KEY idx_artworks_status (status)\n  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

  // Ensure optional offer/combo columns exist
  try {
    $col = $db->query("SHOW COLUMNS FROM artworks LIKE 'offer_price'");
    if (!$col || $col->num_rows === 0) {
      $db->query("ALTER TABLE artworks ADD COLUMN offer_price DECIMAL(10,2) NULL AFTER price");
    }
  } catch (Throwable $e) { /* ignore */ }
  try {
    $col = $db->query("SHOW COLUMNS FROM artworks LIKE 'offer_percent'");
    if (!$col || $col->num_rows === 0) {
      $db->query("ALTER TABLE artworks ADD COLUMN offer_percent DECIMAL(5,2) NULL AFTER offer_price");
    }
  } catch (Throwable $e) { /* ignore */ }
  try {
    $col = $db->query("SHOW COLUMNS FROM artworks LIKE 'offer_starts_at'");
    if (!$col || $col->num_rows === 0) {
      $db->query("ALTER TABLE artworks ADD COLUMN offer_starts_at DATETIME NULL AFTER offer_percent");
    }
  } catch (Throwable $e) { /* ignore */ }
  try {
    $col = $db->query("SHOW COLUMNS FROM artworks LIKE 'offer_ends_at'");
    if (!$col || $col->num_rows === 0) {
      $db->query("ALTER TABLE artworks ADD COLUMN offer_ends_at DATETIME NULL AFTER offer_starts_at");
    }
  } catch (Throwable $e) { /* ignore */ }
  // Force offer badge (show banner without discount)
  try {
    $col = $db->query("SHOW COLUMNS FROM artworks LIKE 'force_offer_badge'");
    if (!$col || $col->num_rows === 0) {
      $db->query("ALTER TABLE artworks ADD COLUMN force_offer_badge TINYINT(1) NOT NULL DEFAULT 0 AFTER offer_ends_at");
    }
  } catch (Throwable $e) { /* ignore */ }
  // ensure combo flag if used anywhere
  try {
    $col = $db->query("SHOW COLUMNS FROM artworks LIKE 'is_combo'");
    if (!$col || $col->num_rows === 0) {
      $db->query("ALTER TABLE artworks ADD COLUMN is_combo TINYINT(1) NOT NULL DEFAULT 0 AFTER artist_id");
    }
  } catch (Throwable $e) { /* ignore */ }

  // roles/user_roles for admin check
  $db->query("CREATE TABLE IF NOT EXISTS roles (id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL UNIQUE) ENGINE=InnoDB");
  $db->query("INSERT IGNORE INTO roles (id, name) VALUES (1,'admin'),(2,'customer'),(3,'supplier')");
  $db->query("CREATE TABLE IF NOT EXISTS user_roles (user_id INT UNSIGNED NOT NULL, role_id TINYINT UNSIGNED NOT NULL, PRIMARY KEY (user_id, role_id)) ENGINE=InnoDB");
}

try { ensure_schema($mysqli); } catch (Throwable $e) {}

// Admin auth
$adminUserId = isset($_SERVER['HTTP_X_ADMIN_USER_ID']) ? (int)$_SERVER['HTTP_X_ADMIN_USER_ID'] : 0;
if ($adminUserId <= 0) {
  http_response_code(401);
  echo json_encode(["status" => "error", "message" => "Missing admin identity"]);
  exit;
}

$isAdmin = false;
try {
  $chk = $mysqli->prepare("SELECT 1 FROM user_roles ur JOIN roles r ON ur.role_id=r.id WHERE ur.user_id=? AND r.name='admin' LIMIT 1");
  $chk->bind_param('i', $adminUserId);
  $chk->execute();
  $chk->store_result();
  if ($chk->num_rows > 0) { $isAdmin = true; }
  $chk->close();
} catch (Throwable $e) {
  // table may not exist yet; fallback below
}

// Fallback: if users table has a 'role' column and it's 'admin'
if (!$isAdmin) {
  try {
    $col = $mysqli->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($col && $col->num_rows > 0) {
      $q2 = $mysqli->prepare("SELECT 1 FROM users WHERE id=? AND role='admin' LIMIT 1");
      $q2->bind_param('i', $adminUserId);
      $q2->execute();
      $q2->store_result();
      if ($q2->num_rows > 0) { $isAdmin = true; }
      $q2->close();
    }
  } catch (Throwable $e) {}
}

if (!$isAdmin) {
  http_response_code(403);
  echo json_encode(["status" => "error", "message" => "Not an admin user"]);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'];

function json_success($payload) { echo json_encode(["status" => "success"] + $payload); }
function json_error($message, $code = 400) { http_response_code($code); echo json_encode(["status" => "error", "message" => $message]); }

try {
  if ($method === 'GET') {
    $status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : 'all';
    $allowed = ['active','inactive','all'];
    if (!in_array($status, $allowed, true)) { $status = 'all'; }

    // Build select with optional offer/combo columns (not all DBs may have them yet)
    $baseCols = "a.id, a.title, a.description, a.price, a.image_url, a.category_id, a.availability, a.status, a.created_at";
    $extraCols = "a.is_combo, a.offer_price, a.offer_percent, a.offer_starts_at, a.offer_ends_at, a.force_offer_badge";
    // Try to detect a sample column; if it doesn't exist, omit extras
    $hasOfferCols = true;
    try {
      $colCheck = $mysqli->query("SHOW COLUMNS FROM artworks LIKE 'offer_price'");
      if (!$colCheck || $colCheck->num_rows === 0) { $hasOfferCols = false; }
    } catch (Throwable $e) { $hasOfferCols = false; }
    $selectCols = $hasOfferCols ? ($baseCols . ", " . $extraCols) : $baseCols;

    $sql = "SELECT $selectCols,
                   CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS artist_name,
                   c.name AS category_name
            FROM artworks a
            LEFT JOIN users u ON u.id=a.artist_id
            LEFT JOIN categories c ON c.id=a.category_id";
    if ($status !== 'all') { $sql .= " WHERE a.status=?"; }
    $sql .= " ORDER BY a.created_at DESC";

    if ($status !== 'all') { $st = $mysqli->prepare($sql); $st->bind_param('s', $status); }
    else { $st = $mysqli->prepare($sql); }

    $st->execute();
    $res = $st->get_result();
    $rows = [];
    while ($row = $res->fetch_assoc()) { $rows[] = $row; }
    $st->close();

    json_success(["artworks" => $rows]);
    exit;
  }

  if ($method === 'POST') {
    // Accept multipart/form-data or JSON
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    $title = '';
    $description = null;
    $price = 0.0;
    $category_id = null;
    $availability = 'in_stock';
    $status = 'active';
    $image_url = '';

    if (stripos($contentType, 'application/json') !== false) {
      $input = json_decode(file_get_contents('php://input'), true) ?? [];
      $title = trim((string)($input['title'] ?? ''));
      $description = isset($input['description']) ? trim((string)$input['description']) : null;
      $price = (float)($input['price'] ?? 0);
      $category_id = isset($input['category_id']) && $input['category_id'] !== '' ? (int)$input['category_id'] : null;
      $availability = in_array(($input['availability'] ?? 'in_stock'), ['in_stock','out_of_stock','made_to_order'], true) ? $input['availability'] : 'in_stock';
      $status = in_array(($input['status'] ?? 'active'), ['active','inactive'], true) ? $input['status'] : 'active';
      $image_url = trim((string)($input['image_url'] ?? ''));
    } else {
      $title = isset($_POST['title']) ? trim((string)$_POST['title']) : '';
      $description = isset($_POST['description']) ? trim((string)$_POST['description']) : null;
      $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
      $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
      $availability = isset($_POST['availability']) && in_array($_POST['availability'], ['in_stock','out_of_stock','made_to_order'], true) ? $_POST['availability'] : 'in_stock';
      $status = isset($_POST['status']) && in_array($_POST['status'], ['active','inactive'], true) ? $_POST['status'] : 'active';
    }

    if ($title === '' || $price <= 0) {
      json_error('Title and positive price are required', 422);
      exit;
    }

    // Handle file upload if present
    if (!empty($_FILES['image']) && isset($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
      $uploadBase = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'artworks';
      if (!is_dir($uploadBase)) { @mkdir($uploadBase, 0777, true); }

      $orig = $_FILES['image']['name'] ?? 'image';
      $ext = pathinfo($orig, PATHINFO_EXTENSION);
      $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($orig, PATHINFO_FILENAME));
      $final = $safe . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . ($ext ? ('.' . strtolower($ext)) : '');
      $dest = $uploadBase . DIRECTORY_SEPARATOR . $final;

      if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
        json_error('Failed to store uploaded image', 500);
        exit;
      }

      // Build public URL (XAMPP docroot: /my_little_thingz)
      $image_url = 'http://localhost/my_little_thingz/backend/uploads/artworks/' . $final;
    }

    if ($image_url === '') {
      json_error('Image is required (upload as image or provide image_url)', 422);
      exit;
    }

    $artist_id = $adminUserId; // store admin user as the artist/uploader

    $cid = $category_id; // can be null
    // Optional offer fields (only if columns exist)
    $offer_price = null; $offer_percent = null; $offer_starts_at = null; $offer_ends_at = null; $force_offer_badge = 0;
    try {
      $offer_price = isset($_POST['offer_price']) ? (($_POST['offer_price'] === '' ) ? null : (float)$_POST['offer_price']) : (isset($input['offer_price']) ? (float)$input['offer_price'] : null);
      $offer_percent = isset($_POST['offer_percent']) ? (($_POST['offer_percent'] === '' ) ? null : (float)$_POST['offer_percent']) : (isset($input['offer_percent']) ? (float)$input['offer_percent'] : null);
      $offer_starts_at = isset($_POST['offer_starts_at']) ? (($_POST['offer_starts_at'] === '' ) ? null : $_POST['offer_starts_at']) : ($input['offer_starts_at'] ?? null);
      $offer_ends_at = isset($_POST['offer_ends_at']) ? (($_POST['offer_ends_at'] === '' ) ? null : $_POST['offer_ends_at']) : ($input['offer_ends_at'] ?? null);
      $force_offer_badge = isset($_POST['force_offer_badge']) ? (int)!!$_POST['force_offer_badge'] : (int)!!($input['force_offer_badge'] ?? 0);
    } catch (Throwable $e) {}

    // Detect if offer columns exist
    $hasOfferCols = false;
    try {
      $colCheck = $mysqli->query("SHOW COLUMNS FROM artworks LIKE 'offer_price'");
      if ($colCheck && $colCheck->num_rows > 0) { $hasOfferCols = true; }
    } catch (Throwable $e) { $hasOfferCols = false; }

    if ($cid === null) {
      if ($hasOfferCols) {
        $st = $mysqli->prepare("INSERT INTO artworks (title, description, price, image_url, category_id, artist_id, availability, status, offer_price, offer_percent, offer_starts_at, offer_ends_at, force_offer_badge) VALUES (?,?,?,?,NULL,?,?,?,?,?,?,?,?)");
        // title(s), description(s), price(d), image_url(s), artist_id(i), availability(s), status(s), offer_price(d), offer_percent(d), offer_starts_at(s), offer_ends_at(s), force_offer_badge(i)
        $st->bind_param('ssdsissddssi', $title, $description, $price, $image_url, $artist_id, $availability, $status, $offer_price, $offer_percent, $offer_starts_at, $offer_ends_at, $force_offer_badge);
      } else {
        $st = $mysqli->prepare("INSERT INTO artworks (title, description, price, image_url, category_id, artist_id, availability, status) VALUES (?,?,?,?,NULL,?,?,?)");
        $st->bind_param('ssdsiss', $title, $description, $price, $image_url, $artist_id, $availability, $status);
      }
    } else {
      if ($hasOfferCols) {
        $st = $mysqli->prepare("INSERT INTO artworks (title, description, price, image_url, category_id, artist_id, availability, status, offer_price, offer_percent, offer_starts_at, offer_ends_at, force_offer_badge) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $st->bind_param('ssdsiissddssi', $title, $description, $price, $image_url, $cid, $artist_id, $availability, $status, $offer_price, $offer_percent, $offer_starts_at, $offer_ends_at, $force_offer_badge);
      } else {
        $st = $mysqli->prepare("INSERT INTO artworks (title, description, price, image_url, category_id, artist_id, availability, status) VALUES (?,?,?,?,?,?,?,?)");
        $st->bind_param('ssdsiiss', $title, $description, $price, $image_url, $cid, $artist_id, $availability, $status);
      }
    }

    $st->execute();
    $newId = $st->insert_id;
    $st->close();

    json_success(["id" => $newId, "image_url" => $image_url]);
    exit;
  }

  if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) { json_error('ID required', 422); exit; }

    $st = $mysqli->prepare("DELETE FROM artworks WHERE id=?");
    $st->bind_param('i', $id);
    $st->execute();
    $affected = $st->affected_rows;
    $st->close();

    if ($affected === 0) { json_error('Artwork not found', 404); exit; }
    json_success(["deleted" => $id]);
    exit;
  }

  if ($method === 'PUT') {
    // Update existing artwork fields
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) { json_error('ID required', 422); exit; }

    $input = [];
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
      $input = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
      // Accept form-encoded fallback
      $input = $_POST;
      if (empty($input)) {
        // Try raw parse for PUT
        parse_str(file_get_contents('php://input'), $input);
      }
    }

    // Detect optional columns
    $hasOfferCols = true;
    try {
      $colCheck = $mysqli->query("SHOW COLUMNS FROM artworks LIKE 'offer_price'");
      if (!$colCheck || $colCheck->num_rows === 0) { $hasOfferCols = false; }
    } catch (Throwable $e) { $hasOfferCols = false; }

    // Build dynamic SQL
    $fields = [];
    $params = [];
    $types = '';

    $maybeSet = function($key, $type = 's') use (&$fields, &$params, &$types, $input) {
      if (array_key_exists($key, $input)) {
        $fields[] = "$key = ?";
        $types .= $type;
        $params[] = $input[$key];
      }
    };

    // Core editable fields
    $maybeSet('title', 's');
    $maybeSet('description', 's');
    if (array_key_exists('price', $input)) { $fields[] = 'price = ?'; $types .= 'd'; $params[] = (float)$input['price']; }
    if (array_key_exists('category_id', $input)) { $fields[] = 'category_id = ?'; $types .= 'i'; $params[] = ($input['category_id'] === '' ? null : (int)$input['category_id']); }
    $maybeSet('availability', 's');
    $maybeSet('status', 's');
    $maybeSet('image_url', 's');

    if ($hasOfferCols) {
      if (array_key_exists('offer_price', $input)) { $fields[] = 'offer_price = ?'; $types .= 'd'; $params[] = (($input['offer_price'] === '' || $input['offer_price'] === null) ? null : (float)$input['offer_price']); }
      if (array_key_exists('offer_percent', $input)) { $fields[] = 'offer_percent = ?'; $types .= 'd'; $params[] = (($input['offer_percent'] === '' || $input['offer_percent'] === null) ? null : (float)$input['offer_percent']); }
      if (array_key_exists('offer_starts_at', $input)) { $fields[] = 'offer_starts_at = ?'; $types .= 's'; $params[] = ($input['offer_starts_at'] === '' ? null : $input['offer_starts_at']); }
      if (array_key_exists('offer_ends_at', $input)) { $fields[] = 'offer_ends_at = ?'; $types .= 's'; $params[] = ($input['offer_ends_at'] === '' ? null : $input['offer_ends_at']); }
      if (array_key_exists('force_offer_badge', $input)) { $fields[] = 'force_offer_badge = ?'; $types .= 'i'; $params[] = (int)!!$input['force_offer_badge']; }
    }

    if (empty($fields)) { json_success(["updated" => 0]); exit; }

    $sql = "UPDATE artworks SET " . implode(', ', $fields) . " WHERE id = ?";
    $types .= 'i';
    $params[] = $id;

    $st = $mysqli->prepare($sql);
    $st->bind_param($types, ...$params);
    $st->execute();
    $affected = $st->affected_rows;
    $st->close();

    json_success(["updated" => $affected, "id" => $id]);
    exit;
  }

  http_response_code(405);
  echo json_encode(["status" => "error", "message" => "Method not allowed"]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "Admin artworks handling failed", "detail" => $e->getMessage()]);
}