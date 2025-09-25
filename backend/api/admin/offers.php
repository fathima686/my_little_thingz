<?php
// Admin Offers management: set/clear per-product offers
// Methods: POST set, DELETE clear

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_origins = ['http://localhost:5173', 'http://127.0.0.1:5173'];
if (in_array($origin, $allowed_origins, true)) {
  header("Access-Control-Allow-Origin: $origin");
} else {
  header("Access-Control-Allow-Origin: http://localhost:5173");
}
header("Vary: Origin");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-User-Id");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli("localhost", "root", "", "my_little_thingz");
$mysqli->set_charset('utf8mb4');

$adminUserId = isset($_SERVER['HTTP_X_ADMIN_USER_ID']) ? (int)$_SERVER['HTTP_X_ADMIN_USER_ID'] : 0;
if ($adminUserId <= 0) { http_response_code(401); echo json_encode(["status"=>"error","message"=>"Missing admin identity"]); exit; }

// Basic admin check using roles table
$isAdmin = false;
try {
  $mysqli->query("CREATE TABLE IF NOT EXISTS roles (id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL UNIQUE) ENGINE=InnoDB");
  $mysqli->query("INSERT IGNORE INTO roles (id, name) VALUES (1,'admin'),(2,'customer'),(3,'supplier')");
  $mysqli->query("CREATE TABLE IF NOT EXISTS user_roles (user_id INT UNSIGNED NOT NULL, role_id TINYINT UNSIGNED NOT NULL, PRIMARY KEY (user_id, role_id)) ENGINE=InnoDB");
} catch (Throwable $e) {}
$chk = $mysqli->prepare("SELECT 1 FROM user_roles ur JOIN roles r ON ur.role_id=r.id WHERE ur.user_id=? AND r.name='admin' LIMIT 1");
$chk->bind_param('i', $adminUserId);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) { $isAdmin = true; }
$chk->close();
if (!$isAdmin) { http_response_code(403); echo json_encode(["status"=>"error","message"=>"Not an admin user"]); exit; }

$method = $_SERVER['REQUEST_METHOD'];

function json_success($payload){ echo json_encode(["status"=>"success"] + $payload); }
function json_error($msg, $code=400){ http_response_code($code); echo json_encode(["status"=>"error","message"=>$msg]); }

try {
  if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $artwork_id = isset($input['artwork_id']) ? (int)$input['artwork_id'] : 0;
    $offer_price = isset($input['offer_price']) && $input['offer_price'] !== '' ? (float)$input['offer_price'] : null;
    $offer_percent = isset($input['offer_percent']) && $input['offer_percent'] !== '' ? (float)$input['offer_percent'] : null;
    $starts_at = isset($input['starts_at']) && $input['starts_at'] !== '' ? $input['starts_at'] : null;
    $ends_at = isset($input['ends_at']) && $input['ends_at'] !== '' ? $input['ends_at'] : null;
    
    if ($artwork_id <= 0) { json_error('artwork_id required', 422); exit; }
    if ($offer_price === null && $offer_percent === null) { json_error('offer_price or offer_percent required', 422); exit; }

    $stmt = $mysqli->prepare("UPDATE artworks SET offer_price=?, offer_percent=?, offer_starts_at=?, offer_ends_at=? WHERE id=?");
    $stmt->bind_param('ddssi', $offer_price, $offer_percent, $starts_at, $ends_at, $artwork_id);
    $stmt->execute();
    $affected = $stmt->affected_rows; $stmt->close();

    if ($affected === 0) { json_error('Artwork not found or no change', 404); exit; }
    json_success(["updated"=>true]);
    exit;
  }

  if ($method === 'DELETE') {
    $artwork_id = isset($_GET['artwork_id']) ? (int)$_GET['artwork_id'] : 0;
    if ($artwork_id <= 0) { json_error('artwork_id required', 422); exit; }
    $stmt = $mysqli->prepare("UPDATE artworks SET offer_price=NULL, offer_percent=NULL, offer_starts_at=NULL, offer_ends_at=NULL WHERE id=?");
    $stmt->bind_param('i', $artwork_id);
    $stmt->execute();
    $affected = $stmt->affected_rows; $stmt->close();
    if ($affected === 0) { json_error('Artwork not found or no change', 404); exit; }
    json_success(["cleared"=>true]);
    exit;
  }

  http_response_code(405);
  echo json_encode(["status"=>"error","message"=>"Method not allowed"]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["status"=>"error","message"=>"Offers endpoint failed","detail"=>$e->getMessage()]);
}