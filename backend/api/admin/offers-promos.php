<?php
// Admin: Promotional Offers (banners/cards) management
// Methods:
// - GET: list offers (status filter optional)
// - POST: create offer (multipart/form-data preferred: fields title, image)
// - DELETE: delete offer by id

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
  header("Access-Control-Allow-Origin: *");
}
header('Vary: Origin');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-User-Id');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204); exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli("localhost", "root", "", "my_little_thingz");
$mysqli->set_charset('utf8mb4');
if ($mysqli->connect_error) { http_response_code(500); echo json_encode(["status"=>"error","message"=>"DB connect failed: ".$mysqli->connect_error]); exit; }

function ensure_schema(mysqli $db) {
  $db->query("CREATE TABLE IF NOT EXISTS offers_promos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(status), INDEX(sort_order)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
  // roles and user_roles for admin check
  $db->query("CREATE TABLE IF NOT EXISTS roles (id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL UNIQUE) ENGINE=InnoDB");
  $db->query("INSERT IGNORE INTO roles (id, name) VALUES (1,'admin'),(2,'customer'),(3,'supplier')");
  $db->query("CREATE TABLE IF NOT EXISTS user_roles (user_id INT UNSIGNED NOT NULL, role_id TINYINT UNSIGNED NOT NULL, PRIMARY KEY (user_id, role_id)) ENGINE=InnoDB");
}
try { ensure_schema($mysqli); } catch (Throwable $e) {}

// Admin auth
$adminUserId = isset($_SERVER['HTTP_X_ADMIN_USER_ID']) ? (int)$_SERVER['HTTP_X_ADMIN_USER_ID'] : 0;
if ($adminUserId <= 0) { http_response_code(401); echo json_encode(["status"=>"error","message"=>"Missing admin identity"]); exit; }
$isAdmin = false;
try {
  $chk = $mysqli->prepare("SELECT 1 FROM user_roles ur JOIN roles r ON ur.role_id=r.id WHERE ur.user_id=? AND r.name='admin' LIMIT 1");
  $chk->bind_param('i', $adminUserId);
  $chk->execute();
  $chk->store_result();
  if ($chk->num_rows > 0) { $isAdmin = true; }
  $chk->close();
} catch (Throwable $e) {}
if (!$isAdmin) { http_response_code(403); echo json_encode(["status"=>"error","message"=>"Not an admin user"]); exit; }

$method = $_SERVER['REQUEST_METHOD'];
function s($v){ return trim((string)$v); }
function json_success($p){ echo json_encode(["status"=>"success"] + $p); }
function json_error($m,$c=400){ http_response_code($c); echo json_encode(["status"=>"error","message"=>$m]); }

try {
  if ($method === 'GET') {
    $status = isset($_GET['status']) ? strtolower(s($_GET['status'])) : 'all';
    if (!in_array($status, ['all','active','inactive'], true)) { $status = 'all'; }
    $sql = "SELECT id, title, image_url, status, sort_order, created_at FROM offers_promos";
    if ($status !== 'all') { $sql .= " WHERE status=?"; }
    $sql .= " ORDER BY sort_order DESC, created_at DESC, id DESC";
    if ($status !== 'all') { $st = $mysqli->prepare($sql); $st->bind_param('s', $status); }
    else { $st = $mysqli->prepare($sql); }
    $st->execute();
    $res = $st->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $st->close();
    json_success(["offers"=>$rows]);
    exit;
  }

  if ($method === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $title = '';
    $image_url = '';
    $status = 'active';
    $sort_order = 0;

    if (stripos($contentType, 'application/json') !== false) {
      $input = json_decode(file_get_contents('php://input'), true) ?? [];
      $title = s($input['title'] ?? '');
      $image_url = s($input['image_url'] ?? '');
      $status = in_array(($input['status'] ?? 'active'), ['active','inactive'], true) ? $input['status'] : 'active';
      $sort_order = (int)($input['sort_order'] ?? 0);
    } else {
      $title = isset($_POST['title']) ? s($_POST['title']) : '';
      $status = isset($_POST['status']) && in_array($_POST['status'], ['active','inactive'], true) ? $_POST['status'] : 'active';
      $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
    }

    if ($title === '') { json_error('Title required', 422); exit; }

    // Handle file upload if present
    if (!empty($_FILES['image']) && isset($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
      $uploadBase = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'offers';
      if (!is_dir($uploadBase)) { @mkdir($uploadBase, 0777, true); }

      $orig = $_FILES['image']['name'] ?? 'offer';
      $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
      if (!in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) { $ext = 'jpg'; }
      $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($orig, PATHINFO_FILENAME));
      $final = 'offer_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
      $dest = $uploadBase . DIRECTORY_SEPARATOR . $final;

      if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
        json_error('Failed to store uploaded image', 500); exit;
      }

      // Public URL through Apache
      $image_url = 'http://localhost/my_little_thingz/backend/uploads/offers/' . $final;
    }

    if ($image_url === '') { json_error('Image required (upload file as image or provide image_url)', 422); exit; }

    $st = $mysqli->prepare("INSERT INTO offers_promos (title, image_url, status, sort_order) VALUES (?,?,?,?)");
    $st->bind_param('sssi', $title, $image_url, $status, $sort_order);
    $st->execute();
    $newId = $st->insert_id; $st->close();

    json_success(["id"=>$newId, "image_url"=>$image_url]);
    exit;
  }

  if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) { json_error('ID required', 422); exit; }
    $st = $mysqli->prepare("DELETE FROM offers_promos WHERE id=?");
    $st->bind_param('i', $id);
    $st->execute();
    $affected = $st->affected_rows; $st->close();
    if ($affected === 0) { json_error('Offer not found', 404); exit; }
    json_success(["deleted"=>$id]);
    exit;
  }

  http_response_code(405);
  echo json_encode(["status"=>"error","message"=>"Method not allowed"]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["status"=>"error","message"=>"Admin offers handling failed","detail"=>$e->getMessage()]);
}