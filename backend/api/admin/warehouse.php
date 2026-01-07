<?php
// Admin: return fixed warehouse address from config

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
$mysqli = new mysqli("localhost", "root", "", "my_little_thingz");
$mysqli->set_charset('utf8mb4');
if ($mysqli->connect_error) { http_response_code(500); echo json_encode(['status'=>'error','message'=>'DB connect failed']); exit; }

require_once __DIR__ . '/../../config/warehouse.php';
$warehouse = require __DIR__ . '/../../config/warehouse.php';

// Admin verify
$adminUserId = isset($_SERVER['HTTP_X_ADMIN_USER_ID']) ? (int)$_SERVER['HTTP_X_ADMIN_USER_ID'] : 0;
if ($adminUserId <= 0) { http_response_code(401); echo json_encode(['status'=>'error','message'=>'Missing admin identity']); exit; }
$isAdmin = false;
$mysqli->query("CREATE TABLE IF NOT EXISTS roles (id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL UNIQUE) ENGINE=InnoDB");
$mysqli->query("INSERT IGNORE INTO roles (id, name) VALUES (1,'admin'),(2,'customer'),(3,'supplier')");
$mysqli->query("CREATE TABLE IF NOT EXISTS user_roles (user_id INT UNSIGNED NOT NULL, role_id TINYINT UNSIGNED NOT NULL, PRIMARY KEY (user_id, role_id)) ENGINE=InnoDB");
$chk = $mysqli->prepare("SELECT 1 FROM user_roles ur JOIN roles r ON ur.role_id=r.id WHERE ur.user_id=? AND r.name='admin' LIMIT 1");
$chk->bind_param('i', $adminUserId);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) { $isAdmin = true; }
$chk->close();
if (!$isAdmin) { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Not an admin user']); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); echo json_encode(['status'=>'error','message'=>'Method not allowed']); exit; }

$address = (string)($warehouse['address'] ?? '');
$fields = isset($warehouse['address_fields']) && is_array($warehouse['address_fields']) ? $warehouse['address_fields'] : null;
echo json_encode(['status'=>'success','address'=>$address,'address_fields'=>$fields]);