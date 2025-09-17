<?php
// Admin Inventory Search (read-only, across all suppliers)
// Purpose: Quickly check if any supplier has stock for specified craft supplies

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_origins = ['http://localhost:5173', 'http://127.0.0.1:5173'];
if (in_array($origin, $allowed_origins, true)) {
  header("Access-Control-Allow-Origin: $origin");
} else {
  header("Access-Control-Allow-Origin: http://localhost:5173");
}
header("Vary: Origin");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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

// Filters: q, category, type, size, color, min_qty
function s($v){ return trim((string)$v); }
$q = isset($_GET['q']) ? s($_GET['q']) : '';
$category = isset($_GET['category']) ? s($_GET['category']) : '';
$type = isset($_GET['type']) ? s($_GET['type']) : '';
$size = isset($_GET['size']) ? s($_GET['size']) : '';
$color = isset($_GET['color']) ? s($_GET['color']) : '';
$min_qty = isset($_GET['min_qty']) ? (int)$_GET['min_qty'] : null;

try {
  $sql = "SELECT m.id, m.name, m.sku, m.category, m.type, m.size, m.color, m.brand, m.tags, m.location,
                 m.quantity, m.unit, m.updated_at, u.id AS supplier_id, CONCAT(u.first_name, ' ', u.last_name) AS supplier_name
          FROM materials m
          JOIN users u ON u.id = m.supplier_id
          WHERE 1=1";
  $types = '';
  $params = [];

  if ($q !== '') {
    $sql .= " AND (m.name LIKE ? OR m.sku LIKE ? OR m.category LIKE ? OR m.type LIKE ? OR m.size LIKE ? OR m.color LIKE ? OR m.brand LIKE ? OR m.tags LIKE ?)";
    $like = "%{$q}%";
    $types .= 'ssssssss';
    array_push($params, $like, $like, $like, $like, $like, $like, $like, $like);
  }
  if ($category !== '') { $sql .= " AND m.category = ?"; $types .= 's'; $params[] = $category; }
  if ($type !== '')     { $sql .= " AND m.type = ?";     $types .= 's'; $params[] = $type; }
  if ($size !== '')     { $sql .= " AND m.size = ?";     $types .= 's'; $params[] = $size; }
  if ($color !== '')    { $sql .= " AND m.color = ?";    $types .= 's'; $params[] = $color; }
  if ($min_qty !== null){ $sql .= " AND m.quantity >= ?"; $types .= 'i'; $params[] = $min_qty; }

  $sql .= " ORDER BY m.category, m.type, m.name";
  $stmt = $mysqli->prepare($sql);
  if ($types !== '') { $stmt->bind_param($types, ...$params); }
  $stmt->execute();
  $res = $stmt->get_result();
  $rows = $res->fetch_all(MYSQLI_ASSOC);

  echo json_encode(["status"=>"success","items"=>$rows]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["status"=>"error","message"=>"Server error"]);
}