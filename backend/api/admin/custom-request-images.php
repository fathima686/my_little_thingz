<?php
header("Content-Type: application/json");
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['http://localhost:5173','http://127.0.0.1:5173'];
header("Access-Control-Allow-Origin: " . (in_array($origin, $allowed, true) ? $origin : 'http://localhost:5173'));
header("Vary: Origin");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-User-Id");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli("localhost","root","","my_little_thingz");
$mysqli->set_charset('utf8mb4');
if ($mysqli->connect_error) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "DB connect failed: " . $mysqli->connect_error]);
  exit;
}

// Ensure roles and user_roles tables minimal presence (mirror from other admin endpoints)
try {
  $mysqli->query("CREATE TABLE IF NOT EXISTS roles (id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL UNIQUE) ENGINE=InnoDB");
  $mysqli->query("INSERT IGNORE INTO roles (id, name) VALUES (1,'admin'),(2,'customer'),(3,'supplier')");
  $mysqli->query("CREATE TABLE IF NOT EXISTS user_roles (user_id INT UNSIGNED NOT NULL, role_id TINYINT UNSIGNED NOT NULL, PRIMARY KEY (user_id, role_id)) ENGINE=InnoDB");
} catch (Throwable $e) {}

$adminId = isset($_SERVER['HTTP_X_ADMIN_USER_ID']) ? (int)$_SERVER['HTTP_X_ADMIN_USER_ID'] : 0;
if ($adminId <= 0) { http_response_code(401); echo json_encode(["status"=>"error","message"=>"Missing admin identity"]); exit; }

$isAdmin = false;
$chk = $mysqli->prepare("SELECT 1 FROM user_roles ur JOIN roles r ON ur.role_id=r.id WHERE ur.user_id=? AND r.name='admin' LIMIT 1");
$chk->bind_param('i', $adminId); $chk->execute(); $chk->store_result();
$isAdmin = $chk->num_rows > 0; $chk->close();
if (!$isAdmin) { http_response_code(403); echo json_encode(["status"=>"error","message"=>"Not an admin user"]); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(["status"=>"error","message"=>"Method not allowed"]); exit; }

$requestId = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
if ($requestId <= 0) { http_response_code(400); echo json_encode(["status"=>"error","message"=>"request_id required"]); exit; }

if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400); echo json_encode(["status"=>"error","message"=>"image file required"]); exit; }

$uploadDir = __DIR__ . '/../../uploads/custom-requests/';
if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0755, true); }

$name = $_FILES['image']['name'];
$tmp  = $_FILES['image']['tmp_name'];
$safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
$file = uniqid('admin_', true) . '_' . $safe;
$dest = $uploadDir . $file;

if (!move_uploaded_file($tmp, $dest)) {
  http_response_code(500); echo json_encode(["status"=>"error","message"=>"Failed to store file"]); exit; }

$relPath = 'uploads/custom-requests/' . $file;
$ins = $mysqli->prepare("INSERT INTO custom_request_images (request_id, image_path, uploaded_at) VALUES (?, ?, NOW())");
$ins->bind_param('is', $requestId, $relPath); $ins->execute(); $ins->close();

echo json_encode(["status"=>"success","image_path"=>$relPath]);