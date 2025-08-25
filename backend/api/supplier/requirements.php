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
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
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

// Ensure table exists
$mysqli->query("CREATE TABLE IF NOT EXISTS order_requirements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  supplier_id INT UNSIGNED NOT NULL,
  order_ref VARCHAR(100) NOT NULL,
  material_name VARCHAR(120) NOT NULL,
  required_qty INT NOT NULL DEFAULT 0,
  unit VARCHAR(32) NOT NULL DEFAULT 'pcs',
  due_date DATE NULL,
  status ENUM('pending','packed','fulfilled','cancelled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX(supplier_id),
  INDEX(order_ref),
  CONSTRAINT fk_req_supplier FOREIGN KEY (supplier_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB");

$method = $_SERVER['REQUEST_METHOD'];

function body_json() { $d=json_decode(file_get_contents('php://input'), true); return is_array($d)?$d:[]; }
function s($v){ return trim((string)$v); }

$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : (int)($_SERVER['HTTP_X_SUPPLIER_ID'] ?? 0);
if ($supplier_id <= 0) {
  http_response_code(401);
  echo json_encode(["status" => "error", "message" => "Missing supplier_id"]);
  exit;
}

try {
  if ($method === 'GET') {
    $stmt = $mysqli->prepare("SELECT id, order_ref, material_name, required_qty, unit, due_date, status FROM order_requirements WHERE supplier_id=? ORDER BY due_date IS NULL, due_date, id DESC");
    $stmt->bind_param('i', $supplier_id);
    $stmt->execute();
    $res = $stmt->get_result();
    echo json_encode(["status"=>"success","items"=>$res->fetch_all(MYSQLI_ASSOC)]);
  } elseif ($method === 'POST') {
    $b = body_json();
    $order_ref = s($b['order_ref'] ?? '');
    $material  = s($b['material_name'] ?? '');
    $qty       = (int)($b['required_qty'] ?? 0);
    $unit      = s($b['unit'] ?? 'pcs');
    $due       = s($b['due_date'] ?? '');
    if ($order_ref==='' || $material==='') { http_response_code(422); echo json_encode(["status"=>"error","message"=>"order_ref and material_name required"]); exit; }
    $due = $due !== '' ? $due : null;
    $stmt = $mysqli->prepare("INSERT INTO order_requirements (supplier_id, order_ref, material_name, required_qty, unit, due_date) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param('ississ', $supplier_id, $order_ref, $material, $qty, $unit, $due);
    $stmt->execute();
    echo json_encode(["status"=>"success","id"=>$stmt->insert_id]);
  } elseif ($method === 'PUT') {
    $b = body_json();
    $id = (int)($b['id'] ?? 0);
    $status = s($b['status'] ?? '');
    if ($id<=0 || $status==='') { http_response_code(422); echo json_encode(["status"=>"error","message"=>"id and status required"]); exit; }
    $allowed = ['pending','packed','fulfilled','cancelled'];
    if (!in_array($status, $allowed, true)) { http_response_code(422); echo json_encode(["status"=>"error","message"=>"Invalid status"]); exit; }
    $stmt = $mysqli->prepare("UPDATE order_requirements SET status=? WHERE id=? AND supplier_id=?");
    $stmt->bind_param('sii', $status, $id, $supplier_id);
    $stmt->execute();
    echo json_encode(["status"=>"success"]);
  } else {
    http_response_code(405);
    echo json_encode(["status"=>"error","message"=>"Method not allowed"]);
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["status"=>"error","message"=>"Server error"]);
}