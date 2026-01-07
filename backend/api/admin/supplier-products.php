<?php
// Admin: Supplier Products moderation/view
// Admin can list all supplier products and approve/reject

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_origins = ['http://localhost:5173', 'http://127.0.0.1:5173'];
if (in_array($origin, $allowed_origins, true)) {
  header("Access-Control-Allow-Origin: $origin");
} else {
  header("Access-Control-Allow-Origin: http://localhost:5173");
}
header("Vary: Origin");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-User-Id, X-Admin-Email");

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
  // roles + user_roles for admin check
  $db->query("CREATE TABLE IF NOT EXISTS roles (id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL UNIQUE) ENGINE=InnoDB");
  $db->query("INSERT IGNORE INTO roles (id, name) VALUES (1,'admin'),(2,'customer'),(3,'supplier')");
  $db->query("CREATE TABLE IF NOT EXISTS user_roles (user_id INT UNSIGNED NOT NULL, role_id TINYINT UNSIGNED NOT NULL, PRIMARY KEY (user_id, role_id)) ENGINE=InnoDB");

  // Minimal users table to satisfy joins if not present yet
  $db->query("CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB");
  
  // Supplier products table used here
  $db->query("CREATE TABLE IF NOT EXISTS supplier_products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT UNSIGNED NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT NULL,
    category VARCHAR(100) NULL,
    sku VARCHAR(80) NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantity INT NOT NULL DEFAULT 0,
    unit VARCHAR(32) NOT NULL DEFAULT 'pcs',
    availability ENUM('available','unavailable') NOT NULL DEFAULT 'available',
    image_url VARCHAR(500) NULL,
    is_trending TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(supplier_id),
    INDEX(status),
    INDEX(category),
    INDEX(is_trending)
  ) ENGINE=InnoDB");
}

try { ensure_schema($mysqli); } catch (Throwable $e) {}

// Auto-migrate: ensure required columns exist (handles older tables)
try {
  $colsRes = $mysqli->query("SHOW COLUMNS FROM supplier_products");
  $have = [];
  while ($row = $colsRes->fetch_assoc()) { $have[strtolower($row['Field'])] = true; }
  $alters = [];
  if (empty($have['availability'])) {
    $alters[] = "ADD COLUMN availability ENUM('available','unavailable') NOT NULL DEFAULT 'available'";
  }
  if (empty($have['image_url'])) {
    $alters[] = "ADD COLUMN image_url VARCHAR(500) NULL";
  }
  if (empty($have['is_trending'])) {
    $alters[] = "ADD COLUMN is_trending TINYINT(1) NOT NULL DEFAULT 0";
  }
  if (empty($have['status'])) {
    $alters[] = "ADD COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'";
  }
  if (empty($have['updated_at'])) {
    $alters[] = "ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
  }
  if (!empty($alters)) {
    $mysqli->query("ALTER TABLE supplier_products " . implode(', ', $alters));
  }
} catch (Throwable $e) { /* ignore */ }

// Admin verify
$adminUserId = isset($_SERVER['HTTP_X_ADMIN_USER_ID']) ? (int)$_SERVER['HTTP_X_ADMIN_USER_ID'] : 0;
if ($adminUserId <= 0) {
  http_response_code(401);
  echo json_encode(["status" => "error", "message" => "Missing admin identity"]);
  exit;
}

$isAdmin = false;
$chk = $mysqli->prepare("SELECT 1 FROM user_roles ur JOIN roles r ON ur.role_id=r.id WHERE ur.user_id=? AND r.name='admin' LIMIT 1");
$chk->bind_param('i', $adminUserId);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) { $isAdmin = true; }
$chk->close();

if (!$isAdmin) {
  http_response_code(403);
  echo json_encode(["status" => "error", "message" => "Not an admin user"]);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'];

function s($v){ return trim((string)$v); }

try {
  if ($method === 'GET') {
    // Filters: q, supplier_id, category, trending, availability (status hidden for UI)
    $q = isset($_GET['q']) ? s($_GET['q']) : '';
    $supplierId = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
    $category = isset($_GET['category']) ? s($_GET['category']) : '';
    $trending = isset($_GET['trending']) ? (int)$_GET['trending'] : null; // 1 or 0
    $availability = isset($_GET['availability']) ? strtolower(s($_GET['availability'])) : '';

    $sql = "SELECT sp.id, sp.supplier_id, CONCAT(u.first_name,' ',u.last_name) AS supplier_name, sp.name, sp.description, sp.category,
                   sp.price, sp.quantity, sp.unit, sp.image_url, sp.is_trending,
                   CASE WHEN sp.availability='available' AND sp.quantity>0 THEN 'in_stock' ELSE 'out_of_stock' END AS status,
                   sp.updated_at
            FROM supplier_products sp JOIN users u ON u.id=sp.supplier_id WHERE 1=1";
    $types = '';
    $params = [];

    if ($supplierId > 0) {
      $sql .= " AND sp.supplier_id=?";
      $types .= 'i';
      $params[] = $supplierId;
    }
    if ($category !== '') {
      $sql .= " AND sp.category=?";
      $types .= 's';
      $params[] = $category;
    }
    if ($availability !== '' && in_array($availability, ['available','unavailable'], true)) {
      $sql .= " AND sp.availability=?";
      $types .= 's';
      $params[] = $availability;
    }
    if ($trending !== null) {
      $sql .= " AND sp.is_trending=?";
      $types .= 'i';
      $params[] = ($trending ? 1 : 0);
    }
    if ($q !== '') {
      $sql .= " AND (sp.name LIKE ? OR sp.category LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
      $like = "%{$q}%";
      $types .= 'ssss';
      array_push($params, $like, $like, $like, $like);
    }

    $sql .= " ORDER BY sp.is_trending DESC, sp.updated_at DESC, sp.id DESC";

    $st = $mysqli->prepare($sql);
    if ($types !== '') { $st->bind_param($types, ...$params); }
    $st->execute();
    $res = $st->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);

    // Map derived status to stock/out_of_stock, keep image_url for frontend display
    foreach ($rows as &$row) {
      $row['stock'] = ($row['status'] === 'in_stock') ? 'in_stock' : 'out_of_stock';
      unset($row['status']); // remove internal status field name
    }

    echo json_encode(["status"=>"success","items"=>$rows]);
    exit;
  }

  if ($method === 'POST') {
    // Approve or reject
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($input['id'] ?? 0);
    $action = strtolower((string)($input['action'] ?? ''));
    if ($id <= 0 || !in_array($action, ['approve','reject'], true)) {
      http_response_code(400);
      echo json_encode(["status" => "error", "message" => "Bad request"]);
      exit;
    }

    $newStatus = $action === 'approve' ? 'approved' : 'rejected';
    $st = $mysqli->prepare("UPDATE supplier_products SET status=? WHERE id=?");
    $st->bind_param('si', $newStatus, $id);
    $st->execute();
    if ($st->affected_rows === 0) {
      http_response_code(404);
      echo json_encode(["status"=>"error","message"=>"Product not found"]);
      exit;
    }

    echo json_encode(["status"=>"success","id"=>$id,"new_status"=>$newStatus]);
    exit;
  }

  http_response_code(405);
  echo json_encode(["status"=>"error","message"=>"Method not allowed"]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["status"=>"error","message"=>"Admin operation failed","detail"=>$e->getMessage()]);
}