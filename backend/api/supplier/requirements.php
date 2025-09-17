<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
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

// Messages for requirements (thread between admin and supplier)
$mysqli->query("CREATE TABLE IF NOT EXISTS order_requirement_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  requirement_id INT UNSIGNED NOT NULL,
  sender ENUM('admin','supplier') NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(requirement_id),
  CONSTRAINT fk_req_msg FOREIGN KEY (requirement_id) REFERENCES order_requirements(id) ON DELETE CASCADE
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
    // List requirements + include message thread
    $stmt = $mysqli->prepare("SELECT id, order_ref, material_name, required_qty, unit, due_date, status FROM order_requirements WHERE supplier_id=? ORDER BY due_date IS NULL, due_date, id DESC");
    $stmt->bind_param('i', $supplier_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = $res->fetch_all(MYSQLI_ASSOC);

    // Fetch messages for these requirements
    $ids = array_column($items, 'id');
    $messagesByReq = [];
    if (!empty($ids)) {
      $placeholders = implode(',', array_fill(0, count($ids), '?'));
      $types = str_repeat('i', count($ids));
      $sql = "SELECT requirement_id, sender, message, created_at FROM order_requirement_messages WHERE requirement_id IN ($placeholders) ORDER BY created_at ASC";
      $st2 = $mysqli->prepare($sql);
      $st2->bind_param($types, ...$ids);
      $st2->execute();
      $rs2 = $st2->get_result();
      while ($row = $rs2->fetch_assoc()) {
        $rid = (int)$row['requirement_id'];
        if (!isset($messagesByReq[$rid])) $messagesByReq[$rid] = [];
        $messagesByReq[$rid][] = [
          'sender' => $row['sender'],
          'message' => $row['message'],
          'created_at' => $row['created_at'],
        ];
      }
    }
    foreach ($items as &$it) { $it['messages'] = $messagesByReq[$it['id']] ?? []; }

    echo json_encode(["status"=>"success","items"=>$items]);
  } elseif ($method === 'POST') {
    // Block write if not approved
    if (!$isApproved) { http_response_code(403); echo json_encode(["status"=>"error","message"=>"Supplier not approved"]); exit; }

    $b = body_json();

    // Supplier reply to a requirement thread
    if (!empty($b['message']) && !empty($b['requirement_id'])) {
      $rid = (int)$b['requirement_id'];
      $msg = s($b['message']);
      if ($rid <= 0 || $msg === '') { http_response_code(422); echo json_encode(["status"=>"error","message"=>"requirement_id and message required"]); exit; }
      // Ensure requirement belongs to supplier
      $chk = $mysqli->prepare("SELECT 1 FROM order_requirements WHERE id=? AND supplier_id=?");
      $chk->bind_param('ii', $rid, $supplier_id);
      $chk->execute();
      $chk->store_result();
      if ($chk->num_rows === 0) { http_response_code(403); echo json_encode(["status"=>"error","message"=>"Not allowed"]); exit; }
      $ins = $mysqli->prepare("INSERT INTO order_requirement_messages (requirement_id, sender, message) VALUES (?,?,?)");
      $sender = 'supplier';
      $ins->bind_param('iss', $rid, $sender, $msg);
      $ins->execute();
      echo json_encode(["status"=>"success","message_id"=>$ins->insert_id]);
      exit;
    }

    // Create requirement (used by supplier self-only; admin uses admin endpoint)
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
  echo json_encode(["status"=>"error","message"=>"Server error: " . $e->getMessage()]);
}