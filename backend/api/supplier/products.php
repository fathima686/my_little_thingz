<?php
// Supplier Products API
// Suppliers can create/list/update/delete their sellable products.
// Table auto-creation: supplier_products

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-SUPPLIER-ID");

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

// Ensure table exists
$mysqli->query("CREATE TABLE IF NOT EXISTS supplier_products (
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
  INDEX(is_trending),
  CONSTRAINT fk_sp_supplier FOREIGN KEY (supplier_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB");

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
} catch (Throwable $e) {
  // Ignore migration errors to avoid blocking requests; surfacing will happen on insert anyway
}

$method = $_SERVER['REQUEST_METHOD'];

function body_json(){ $d=json_decode(file_get_contents('php://input'), true); return is_array($d)?$d:[]; }
function s($v){ return trim((string)$v); }

// Basic supplier auth (header or query)
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : (int)($_SERVER['HTTP_X_SUPPLIER_ID'] ?? 0);
if ($supplier_id <= 0) {
  http_response_code(401);
  echo json_encode(["status" => "error", "message" => "Missing supplier_id"]);
  exit;
}

// Enforce supplier approval for write operations
$isApproved = true;
try {
  $chk = $mysqli->prepare("SELECT sp.status FROM supplier_profiles sp WHERE sp.user_id=? LIMIT 1");
  $chk->bind_param('i', $supplier_id);
  $chk->execute();
  $res = $chk->get_result();
  if ($row = $res->fetch_assoc()) { $isApproved = ($row['status'] === 'approved'); }
  $chk->close();
} catch (Throwable $e) { /* ignore */ }

try {
  if ($method === 'GET') {
    // List own products with filters
    $q = isset($_GET['q']) ? s($_GET['q']) : '';
    $status = isset($_GET['status']) ? strtolower(s($_GET['status'])) : '';
    $category = isset($_GET['category']) ? s($_GET['category']) : '';
    $trending = isset($_GET['trending']) ? (int)$_GET['trending'] : null; // 1 or 0
    $availability = isset($_GET['availability']) ? strtolower(s($_GET['availability'])) : '';

    $sql = "SELECT id, name, description, category, price, quantity, unit, availability, image_url, is_trending, status, updated_at, created_at
            FROM supplier_products WHERE supplier_id=?";
    $types = 'i';
    $params = [$supplier_id];

    if ($q !== '') {
      $sql .= " AND (name LIKE ? OR category LIKE ?)";
      $like = "%{$q}%";
      $types .= 'ss';
      array_push($params, $like, $like);
    }
    if ($status !== '' && in_array($status, ['pending','approved','rejected'], true)) {
      $sql .= " AND status = ?";
      $types .= 's';
      $params[] = $status;
    }
    if ($category !== '') {
      $sql .= " AND category = ?";
      $types .= 's';
      $params[] = $category;
    }
    if ($availability !== '' && in_array($availability, ['available','unavailable'], true)) {
      $sql .= " AND availability = ?";
      $types .= 's';
      $params[] = $availability;
    }
    if ($trending !== null) {
      $sql .= " AND is_trending = ?";
      $types .= 'i';
      $params[] = ($trending ? 1 : 0);
    }

    $sql .= " ORDER BY is_trending DESC, updated_at DESC, id DESC";

    $st = $mysqli->prepare($sql);
    $st->bind_param($types, ...$params);
    $st->execute();
    $res = $st->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    echo json_encode(["status"=>"success","items"=>$rows]);

  } elseif ($method === 'POST') {
    // Allow adding products without approval - they start as pending

    // Create new product (status defaults to pending)
    $b = body_json();
    $name = s($b['name'] ?? '');
    $description = s($b['description'] ?? '');
    $category = s($b['category'] ?? '');
    $price = (float)($b['price'] ?? 0);
    $quantity = (int)($b['quantity'] ?? 0);
    $unit = s($b['unit'] ?? 'pcs');
    $image_url = s($b['image_url'] ?? '');
    $availability = strtolower(s($b['availability'] ?? 'available'));
    if (!in_array($availability, ['available','unavailable'], true)) { $availability = 'available'; }
    $is_trending = !empty($b['is_trending']) ? 1 : 0;

    if ($name === '') { http_response_code(422); echo json_encode(["status"=>"error","message"=>"name is required"]); exit; }
    if ($price < 0) { $price = 0.00; }
    if ($quantity < 0) { $quantity = 0; }

    $st = $mysqli->prepare("INSERT INTO supplier_products (supplier_id, name, description, category, price, quantity, unit, availability, image_url, is_trending) VALUES (?,?,?,?,?,?,?,?,?,?)");
    // Types: i (supplier_id), s (name), s (description), s (category), d (price), i (quantity), s (unit), s (availability), s (image_url), i (is_trending)
    $st->bind_param('isssdisssi', $supplier_id, $name, $description, $category, $price, $quantity, $unit, $availability, $image_url, $is_trending);
    $st->execute();
    echo json_encode(["status"=>"success","id"=>$st->insert_id]);

  } elseif ($method === 'PUT') {
    // Block write if not approved
    if (!$isApproved) { http_response_code(403); echo json_encode(["status"=>"error","message"=>"Supplier not approved"]); exit; }

    // Update own product (cannot change status here)
    $b = body_json();
    $id = (int)($b['id'] ?? 0);
    if ($id <= 0) { http_response_code(422); echo json_encode(["status"=>"error","message"=>"id is required"]); exit; }

    $fields = [
      'name' => 's', 'description' => 's', 'category' => 's',
      'price' => 'd', 'quantity' => 'i', 'unit' => 's', 'image_url' => 's',
      'availability' => 's', 'is_trending' => 'i'
    ];

    $set = [];
    $types = '';
    $params = [];

    foreach ($fields as $key => $t) {
      if (array_key_exists($key, $b)) {
        $set[] = "$key = ?";
        $types .= $t;
        if ($key === 'price') { $params[] = (float)$b[$key]; }
        elseif ($key === 'quantity') { $params[] = (int)$b[$key]; }
        else { $params[] = s((string)$b[$key]); }
      }
    }

    if (empty($set)) { echo json_encode(["status"=>"success","message"=>"No changes"]); exit; }

    $sql = "UPDATE supplier_products SET ".implode(', ', $set)." WHERE id=? AND supplier_id=?";
    $types .= 'ii';
    $params[] = $id;
    $params[] = $supplier_id;

    $st = $mysqli->prepare($sql);
    $st->bind_param($types, ...$params);
    $st->execute();
    echo json_encode(["status"=>"success"]);

  } elseif ($method === 'DELETE') {
    // Block write if not approved
    if (!$isApproved) { http_response_code(403); echo json_encode(["status"=>"error","message"=>"Supplier not approved"]); exit; }

    // Delete own product
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) { http_response_code(422); echo json_encode(["status"=>"error","message"=>"invalid id"]); exit; }
    $st = $mysqli->prepare("DELETE FROM supplier_products WHERE id=? AND supplier_id=?");
    $st->bind_param('ii', $id, $supplier_id);
    $st->execute();
    echo json_encode(["status"=>"success"]);

  } else {
    http_response_code(405);
    echo json_encode(["status"=>"error","message"=>"Method not allowed"]);
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["status"=>"error","message"=>"Server error: ".$e->getMessage()]);
}