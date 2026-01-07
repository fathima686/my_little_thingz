<?php
// Admin endpoint to enforce a fixed set of categories and deactivate others

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

// Admin auth
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

// Ensure categories table exists
$mysqli->query("CREATE TABLE IF NOT EXISTS categories (\n  id INT UNSIGNED NOT NULL AUTO_INCREMENT,\n  name VARCHAR(120) NOT NULL,\n  description VARCHAR(255) DEFAULT NULL,\n  status ENUM('active','inactive') NOT NULL DEFAULT 'active',\n  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n  PRIMARY KEY (id),\n  UNIQUE KEY uq_category_name (name)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$desired = [
  'Gift box',
  'boquetes',
  'frames',
  'poloroid',
  'custom chocolate',
  'Wedding card',
  'drawings',
  'album',
];

// Insert or activate desired categories
$ins = $mysqli->prepare("INSERT INTO categories (name, description, status) VALUES (?, ?, 'active') ON DUPLICATE KEY UPDATE description=VALUES(description), status='active'");
foreach ($desired as $name) {
  $desc = $name;
  $ins->bind_param('ss', $name, $desc);
  $ins->execute();
}
$ins->close();

// Deactivate any categories not in the desired list
$placeholders = implode(',', array_fill(0, count($desired), '?'));
$types = str_repeat('s', count($desired));
$sql = "UPDATE categories SET status='inactive' WHERE name NOT IN ($placeholders)";
$st = $mysqli->prepare($sql);
$st->bind_param($types, ...$desired);
$st->execute();
$st->close();

// Deduplicate exact name duplicates (keep the smallest id)
try {
  $mysqli->query("DELETE c1 FROM categories c1 INNER JOIN categories c2 ON c1.name = c2.name AND c1.id > c2.id");
} catch (Throwable $e) {}

// Ensure unique index on name exists
try {
  $chk = $mysqli->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='categories' AND INDEX_NAME='uq_category_name'");
  $c = $chk->fetch_assoc()['c'] ?? 0;
  $chk->close();
  if ((int)$c === 0) {
    $mysqli->query("ALTER TABLE categories ADD UNIQUE KEY uq_category_name (name)");
  }
} catch (Throwable $e) {}

// Return active categories (unique by name)
$res = $mysqli->query("SELECT MIN(id) AS id, name, MIN(description) AS description FROM categories WHERE status='active' GROUP BY name ORDER BY name");
$rows = [];
while ($row = $res->fetch_assoc()) { $rows[] = $row; }
$res->close();

echo json_encode(["status" => "success", "categories" => $rows]);