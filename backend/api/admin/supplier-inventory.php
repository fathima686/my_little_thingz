<?php
// Admin: View Supplier Inventory (materials)
// View-only listing of materials across suppliers with filters

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_origins = ['http://localhost:5173', 'http://127.0.0.1:5173'];
if (in_array($origin, $allowed_origins, true)) {
  header("Access-Control-Allow-Origin: $origin");
} else {
  header("Access-Control-Allow-Origin: http://localhost:5173");
}
header('Vary: Origin');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-User-Id, X-Admin-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli('localhost', 'root', '', 'my_little_thingz');
$mysqli->set_charset('utf8mb4');
if ($mysqli->connect_error) {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>'DB connect failed: '.$mysqli->connect_error]);
  exit;
}

function ensure_schema(mysqli $db) {
  // roles + user_roles for admin check
  $db->query("CREATE TABLE IF NOT EXISTS roles (id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL UNIQUE) ENGINE=InnoDB");
  $db->query("INSERT IGNORE INTO roles (id, name) VALUES (1,'admin'),(2,'customer'),(3,'supplier')");
  $db->query("CREATE TABLE IF NOT EXISTS user_roles (user_id INT UNSIGNED NOT NULL, role_id TINYINT UNSIGNED NOT NULL, PRIMARY KEY (user_id, role_id)) ENGINE=InnoDB");

  // Minimal users table to satisfy joins if not present
  $db->query("CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB");

  // Materials table (as used by supplier/inventory.php); ensure basic columns exist
  $db->query("CREATE TABLE IF NOT EXISTS materials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    sku VARCHAR(80) NULL,
    quantity INT NOT NULL DEFAULT 0,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    unit VARCHAR(32) NOT NULL DEFAULT 'pcs',
    category VARCHAR(60) NOT NULL DEFAULT '',
    type VARCHAR(60) NOT NULL DEFAULT '',
    size VARCHAR(60) NOT NULL DEFAULT '',
    color VARCHAR(60) NULL,
    brand VARCHAR(60) NULL,
    tags VARCHAR(255) NULL,
    location VARCHAR(120) NULL,
    availability ENUM('available','out_of_stock') NOT NULL DEFAULT 'available',
    image_url VARCHAR(500) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(supplier_id)
  ) ENGINE=InnoDB");
  // Best-effort add price column if table already exists
  try { $db->query("ALTER TABLE materials ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0.00"); } catch (Throwable $e) {}
}

try { ensure_schema($mysqli); } catch (Throwable $e) {}

// Admin verify
$adminUserId = isset($_SERVER['HTTP_X_ADMIN_USER_ID']) ? (int)$_SERVER['HTTP_X_ADMIN_USER_ID'] : 0;
if ($adminUserId <= 0) { http_response_code(401); echo json_encode(['status'=>'error','message'=>'Missing admin identity']); exit; }

$isAdmin = false;
$chk = $mysqli->prepare("SELECT 1 FROM user_roles ur JOIN roles r ON ur.role_id=r.id WHERE ur.user_id=? AND r.name='admin' LIMIT 1");
$chk->bind_param('i', $adminUserId);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) { $isAdmin = true; }
$chk->close();
if (!$isAdmin) { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Not an admin user']); exit; }

$method = $_SERVER['REQUEST_METHOD'];
function s($v){ return trim((string)$v); }

try {
  if ($method === 'GET') {
    // Filters: q (name, sku, category, type, size, color, brand, tags), supplier_id, category, availability, min_qty
    $q = isset($_GET['q']) ? s($_GET['q']) : '';
    $supplierId = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
    $category = isset($_GET['category']) ? s($_GET['category']) : '';
    $availability = isset($_GET['availability']) ? strtolower(s($_GET['availability'])) : '';
    $min_qty = isset($_GET['min_qty']) ? (int)$_GET['min_qty'] : null;

    $sql = "SELECT m.id, m.supplier_id, CONCAT(u.first_name,' ',u.last_name) AS supplier_name,
                   m.name, m.sku, m.category, m.type, m.size, m.color, m.brand, m.tags, m.location,
                   m.quantity, m.price, m.unit, m.availability, m.image_url, m.updated_at
            FROM materials m JOIN users u ON u.id=m.supplier_id WHERE 1=1";
    $types = '';
    $params = [];

    if ($supplierId > 0) { $sql .= " AND m.supplier_id=?"; $types .= 'i'; $params[] = $supplierId; }
    if ($category !== '') { $sql .= " AND m.category=?"; $types .= 's'; $params[] = $category; }
    if ($availability !== '' && in_array($availability, ['available','out_of_stock'], true)) { $sql .= " AND m.availability=?"; $types .= 's'; $params[] = $availability; }
    if ($min_qty !== null) { $sql .= " AND m.quantity >= ?"; $types .= 'i'; $params[] = $min_qty; }
    if ($q !== '') {
      $sql .= " AND (m.name LIKE ? OR m.sku LIKE ? OR m.category LIKE ? OR m.type LIKE ? OR m.size LIKE ? OR m.color LIKE ? OR m.brand LIKE ? OR m.tags LIKE ?)";
      $like = "%{$q}%";
      $types .= 'ssssssss';
      array_push($params, $like, $like, $like, $like, $like, $like, $like, $like);
    }

    $sql .= " ORDER BY m.updated_at DESC, m.id DESC LIMIT 500"; // safety limit

    $st = $mysqli->prepare($sql);
    if ($types !== '') { $st->bind_param($types, ...$params); }
    $st->execute();
    $res = $st->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['status'=>'success','items'=>$rows]);
    exit;
  }

  http_response_code(405);
  echo json_encode(['status'=>'error','message'=>'Method not allowed']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>'Admin operation failed','detail'=>$e->getMessage()]);
}