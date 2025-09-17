<?php
// Admin: create/view supplier requirements and send messages

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
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-User-Id");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli("localhost", "root", "", "my_little_thingz");
$mysqli->set_charset('utf8mb4');
if ($mysqli->connect_error) { http_response_code(500); echo json_encode(["status"=>"error","message"=>"DB connect failed: ".$mysqli->connect_error]); exit; }

function s($v){ return trim((string)$v); }
function body_json(){ $d=json_decode(file_get_contents('php://input'), true); return is_array($d)?$d:[]; }

// Basic admin check via user_roles
$adminUserId = isset($_SERVER['HTTP_X_ADMIN_USER_ID']) ? (int)$_SERVER['HTTP_X_ADMIN_USER_ID'] : 0;
if ($adminUserId <= 0) { http_response_code(401); echo json_encode(["status"=>"error","message"=>"Missing admin identity"]); exit; }

// Ensure roles tables exist
$mysqli->query("CREATE TABLE IF NOT EXISTS roles (id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL UNIQUE) ENGINE=InnoDB");
$mysqli->query("INSERT IGNORE INTO roles (id, name) VALUES (1,'admin'),(2,'customer'),(3,'supplier')");
$mysqli->query("CREATE TABLE IF NOT EXISTS user_roles (user_id INT UNSIGNED NOT NULL, role_id TINYINT UNSIGNED NOT NULL, PRIMARY KEY (user_id, role_id)) ENGINE=InnoDB");
$chk = $mysqli->prepare("SELECT 1 FROM user_roles ur JOIN roles r ON ur.role_id=r.id WHERE ur.user_id=? AND r.name='admin' LIMIT 1");
$chk->bind_param('i', $adminUserId); $chk->execute(); $chk->store_result();
if ($chk->num_rows === 0) { http_response_code(403); echo json_encode(["status"=>"error","message"=>"Not an admin user"]); exit; }

// Ensure requirement tables exist (same as supplier endpoint)
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
  INDEX(order_ref)
) ENGINE=InnoDB");
$mysqli->query("CREATE TABLE IF NOT EXISTS order_requirement_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  requirement_id INT UNSIGNED NOT NULL,
  sender ENUM('admin','supplier') NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(requirement_id)
) ENGINE=InnoDB");

$method = $_SERVER['REQUEST_METHOD'];

try {
  if ($method === 'GET') {
    $supplierId = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
    $q = isset($_GET['q']) ? s($_GET['q']) : '';
    $status = isset($_GET['status']) ? s($_GET['status']) : '';

    $sql = "SELECT r.id, r.supplier_id, CONCAT(u.first_name,' ',u.last_name) AS supplier_name, r.order_ref, r.material_name, r.required_qty, r.unit, r.due_date, r.status, r.updated_at
            FROM order_requirements r JOIN users u ON u.id=r.supplier_id WHERE 1=1";
    $types = '';
    $params = [];
    if ($supplierId > 0) { $sql .= " AND r.supplier_id=?"; $types.='i'; $params[]=$supplierId; }
    if ($status !== '' && in_array($status, ['pending','packed','fulfilled','cancelled'], true)) { $sql .= " AND r.status=?"; $types.='s'; $params[]=$status; }
    if ($q !== '') {
      $sql .= " AND (r.order_ref LIKE ? OR r.material_name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
      $like = "%{$q}%"; $types.='ssss'; array_push($params, $like, $like, $like, $like);
    }
    $sql .= " ORDER BY (r.status='pending') DESC, r.due_date IS NULL, r.due_date, r.updated_at DESC, r.id DESC";

    $st = $mysqli->prepare($sql);
    if ($types !== '') { $st->bind_param($types, ...$params); }
    $st->execute();
    $res = $st->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);

    // Attach messages
    $ids = array_column($rows, 'id');
    $messages = [];
    if (!empty($ids)) {
      $in = implode(',', array_fill(0, count($ids), '?'));
      $t = str_repeat('i', count($ids));
      $stm = $mysqli->prepare("SELECT requirement_id, sender, message, created_at FROM order_requirement_messages WHERE requirement_id IN ($in) ORDER BY created_at ASC");
      $stm->bind_param($t, ...$ids);
      $stm->execute();
      $rs = $stm->get_result();
      while ($row = $rs->fetch_assoc()) {
        $rid = (int)$row['requirement_id'];
        if (!isset($messages[$rid])) $messages[$rid] = [];
        $messages[$rid][] = [ 'sender'=>$row['sender'], 'message'=>$row['message'], 'created_at'=>$row['created_at'] ];
      }
    }
    foreach ($rows as &$r) { $r['messages'] = $messages[$r['id']] ?? []; }

    echo json_encode(["status"=>"success","items"=>$rows]);
    exit;
  }

  if ($method === 'POST') {
    $b = body_json();

    // Post message on a requirement
    if (!empty($b['message']) && !empty($b['requirement_id'])) {
      $rid = (int)$b['requirement_id'];
      $msg = s($b['message']);
      if ($rid<=0 || $msg==='') { http_response_code(422); echo json_encode(["status"=>"error","message"=>"requirement_id and message required"]); exit; }
      $st = $mysqli->prepare("INSERT INTO order_requirement_messages (requirement_id, sender, message) VALUES (?,?,?)");
      $sender = 'admin';
      $st->bind_param('iss', $rid, $sender, $msg);
      $st->execute();
      echo json_encode(["status"=>"success","message_id"=>$st->insert_id]);
      exit;
    }

    // Create a new requirement for a supplier
    $supplierId = (int)($b['supplier_id'] ?? 0);
    $order_ref = s($b['order_ref'] ?? '');
    $material  = s($b['material_name'] ?? '');
    $qty       = (int)($b['required_qty'] ?? 0);
    $unit      = s($b['unit'] ?? 'pcs');
    $due       = s($b['due_date'] ?? '');
    if ($supplierId<=0 || $order_ref==='' || $material==='') { http_response_code(422); echo json_encode(["status"=>"error","message"=>"supplier_id, order_ref and material_name required"]); exit; }
    $due = $due !== '' ? $due : null;
    $st = $mysqli->prepare("INSERT INTO order_requirements (supplier_id, order_ref, material_name, required_qty, unit, due_date) VALUES (?,?,?,?,?,?)");
    $st->bind_param('ississ', $supplierId, $order_ref, $material, $qty, $unit, $due);
    $st->execute();
    echo json_encode(["status"=>"success","id"=>$st->insert_id]);
    exit;
  }

  http_response_code(405);
  echo json_encode(["status"=>"error","message"=>"Method not allowed"]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["status"=>"error","message"=>"Admin requirements op failed","detail"=>$e->getMessage()]);
}