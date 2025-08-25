<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_origins = ['http://localhost:5173', 'http://127.0.0.1:5173'];
if (in_array($origin, $allowed_origins, true)) {
  header("Access-Control-Allow-Origin: $origin");
} else {
  header("Access-Control-Allow-Origin: http://localhost:5173");
}
header("Vary: Origin");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

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

// Ensure tables exist
$mysqli->query("CREATE TABLE IF NOT EXISTS materials (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  supplier_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  sku VARCHAR(80) NULL,
  quantity INT NOT NULL DEFAULT 0,
  unit VARCHAR(32) NOT NULL DEFAULT 'pcs',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(supplier_id),
  CONSTRAINT fk_materials_supplier FOREIGN KEY (supplier_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB");

$method = $_SERVER['REQUEST_METHOD'];

function json_body() {
  $d = json_decode(file_get_contents('php://input'), true);
  return is_array($d) ? $d : [];
}

function sanitize($s) { return trim((string)$s); }

// Basic supplier auth via query/header (placeholder)
// Expect supplier_id passed temporarily; replace with real session/JWT later
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : (int)($_SERVER['HTTP_X_SUPPLIER_ID'] ?? 0);
if ($supplier_id <= 0) {
  http_response_code(401);
  echo json_encode(["status" => "error", "message" => "Missing supplier_id"]);
  exit;
}

try {
  if ($method === 'GET') {
    // List inventory for supplier
    $stmt = $mysqli->prepare("SELECT id, name, sku, quantity, unit, updated_at FROM materials WHERE supplier_id=? ORDER BY name");
    $stmt->bind_param('i', $supplier_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    echo json_encode(["status" => "success", "items" => $rows]);
  } elseif ($method === 'POST') {
    // Add a material
    $b = json_body();
    $name = sanitize($b['name'] ?? '');
    $sku  = sanitize($b['sku'] ?? '');
    $qty  = (int)($b['quantity'] ?? 0);
    $unit = sanitize($b['unit'] ?? 'pcs');
    if ($name === '') { http_response_code(422); echo json_encode(["status"=>"error","message"=>"Name required"]); exit; }
    if ($qty < 0) { $qty = 0; }
    $stmt = $mysqli->prepare("INSERT INTO materials (supplier_id, name, sku, quantity, unit) VALUES (?,?,?,?,?)");
    $stmt->bind_param('issis', $supplier_id, $name, $sku, $qty, $unit);
    $stmt->execute();
    echo json_encode(["status" => "success", "id" => $stmt->insert_id]);
  } elseif ($method === 'PUT') {
    // Update a material
    $b = json_body();
    $id  = (int)($b['id'] ?? 0);
    $name = sanitize($b['name'] ?? '');
    $sku  = sanitize($b['sku'] ?? '');
    $qty  = (int)($b['quantity'] ?? 0);
    $unit = sanitize($b['unit'] ?? 'pcs');
    if ($id <= 0) { http_response_code(422); echo json_encode(["status"=>"error","message"=>"Invalid id"]); exit; }
    $stmt = $mysqli->prepare("UPDATE materials SET name=?, sku=?, quantity=?, unit=? WHERE id=? AND supplier_id=?");
    $stmt->bind_param('ssissi', $name, $sku, $qty, $unit, $id, $supplier_id);
    $stmt->execute();
    echo json_encode(["status" => "success"]);
  } elseif ($method === 'DELETE') {
    // Delete a material
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) { http_response_code(422); echo json_encode(["status"=>"error","message"=>"Invalid id"]); exit; }
    $stmt = $mysqli->prepare("DELETE FROM materials WHERE id=? AND supplier_id=?");
    $stmt->bind_param('ii', $id, $supplier_id);
    $stmt->execute();
    echo json_encode(["status" => "success"]);
  } else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "Server error"]);
}