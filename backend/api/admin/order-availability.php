<?php
// Admin: Order Materials Availability Check
// Usage: GET /backend/api/admin/order-availability.php?order_id=123
// Requires header: X-Admin-User-Id: <admin_user_id>

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
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-User-Id");

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

function ensure_schema(mysqli $db) {
  // Roles and user_roles to validate admin
  $db->query("CREATE TABLE IF NOT EXISTS roles (id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL UNIQUE) ENGINE=InnoDB");
  $db->query("INSERT IGNORE INTO roles (id, name) VALUES (1,'admin'),(2,'customer'),(3,'supplier')");
  $db->query("CREATE TABLE IF NOT EXISTS user_roles (user_id INT UNSIGNED NOT NULL, role_id TINYINT UNSIGNED NOT NULL, PRIMARY KEY (user_id, role_id)) ENGINE=InnoDB");

  // Mapping from artworks to required materials
  $db->query("CREATE TABLE IF NOT EXISTS artwork_materials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    artwork_id INT UNSIGNED NOT NULL,
    material_name VARCHAR(120) NOT NULL,
    qty_per_unit INT NOT NULL DEFAULT 1,
    unit VARCHAR(32) NOT NULL DEFAULT 'pcs',
    INDEX(artwork_id),
    CONSTRAINT fk_am_artwork FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE
  ) ENGINE=InnoDB");
}

try { ensure_schema($mysqli); } catch (Throwable $e) { /* ignore */ }

// Admin verify
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

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET') {
  http_response_code(405);
  echo json_encode(["status" => "error", "message" => "Method not allowed"]);
  exit;
}

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$orderNumber = isset($_GET['order_number']) ? trim((string)$_GET['order_number']) : '';

if ($orderId <= 0 && $orderNumber === '') {
  http_response_code(422);
  echo json_encode(["status" => "error", "message" => "order_id or order_number is required"]);
  exit;
}

try {
  // Resolve order_id if order_number given
  if ($orderId <= 0 && $orderNumber !== '') {
    $st = $mysqli->prepare("SELECT id FROM orders WHERE order_number=? LIMIT 1");
    $st->bind_param('s', $orderNumber);
    $st->execute();
    $st->bind_result($oid);
    if ($st->fetch()) { $orderId = (int)$oid; }
    $st->close();
    if ($orderId <= 0) {
      http_response_code(404);
      echo json_encode(["status" => "error", "message" => "Order not found"]);
      exit;
    }
  }

  // Fetch order items
  $oi = $mysqli->prepare("SELECT oi.order_id, oi.quantity, a.id AS artwork_id, a.title
                          FROM order_items oi JOIN artworks a ON a.id=oi.artwork_id
                          WHERE oi.order_id=?");
  $oi->bind_param('i', $orderId);
  $oi->execute();
  $res = $oi->get_result();
  $items = $res->fetch_all(MYSQLI_ASSOC);
  $oi->close();

  if (!$items) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "No items for this order"]);
    exit;
  }

  // Build requirements per material from artwork_materials
  $requirements = []; // key: material_name|unit => [material_name, unit, required_qty]
  $missingMappings = [];

  $am = $mysqli->prepare("SELECT material_name, qty_per_unit, unit FROM artwork_materials WHERE artwork_id=?");
  foreach ($items as $it) {
    $artworkId = (int)$it['artwork_id'];
    $qtyOrdered = (int)$it['quantity'];
    $am->bind_param('i', $artworkId);
    $am->execute();
    $amRes = $am->get_result();
    $rows = $amRes->fetch_all(MYSQLI_ASSOC);
    if (!$rows) {
      $missingMappings[] = [
        'artwork_id' => $artworkId,
        'title' => $it['title']
      ];
      continue;
    }
    foreach ($rows as $row) {
      $name = trim($row['material_name']);
      $unit = trim($row['unit']);
      $req = (int)$row['qty_per_unit'] * $qtyOrdered;
      $key = strtolower($name . '|' . $unit);
      if (!isset($requirements[$key])) {
        $requirements[$key] = ['material_name' => $name, 'unit' => $unit, 'required_qty' => 0];
      }
      $requirements[$key]['required_qty'] += $req;
    }
  }
  $am->close();

  if (empty($requirements)) {
    echo json_encode([
      'status' => 'success',
      'order_id' => $orderId,
      'requirements' => [],
      'summary' => ['sufficient' => false, 'total_required' => 0, 'total_available' => 0],
      'note' => 'No material mappings found for the artworks in this order. Please define artwork_materials.',
      'missing_mappings' => $missingMappings
    ]);
    exit;
  }

  // For each requirement, check suppliers' availability
  $result = [];
  $allSufficient = true;
  $totalRequired = 0;
  $totalAvailable = 0;

  $stmt = $mysqli->prepare("SELECT m.id AS material_id, m.supplier_id, m.quantity, m.unit, m.location,
                                   u.first_name, u.last_name
                            FROM materials m JOIN users u ON u.id = m.supplier_id
                            WHERE m.name = ? AND m.unit = ? AND m.quantity > 0
                            ORDER BY m.quantity DESC");

  foreach ($requirements as $req) {
    $suppliers = [];
    $sumQty = 0;

    $name = $req['material_name'];
    $unit = $req['unit'];
    $requiredQty = (int)$req['required_qty'];
    $totalRequired += $requiredQty;

    $stmt->bind_param('ss', $name, $unit);
    $stmt->execute();
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) {
      $sumQty += (int)$row['quantity'];
      $suppliers[] = [
        'supplier_id' => (int)$row['supplier_id'],
        'supplier_name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
        'material_id' => (int)$row['material_id'],
        'available_qty' => (int)$row['quantity'],
        'unit' => $row['unit'],
        'location' => $row['location']
      ];
    }
    $totalAvailable += $sumQty;

    $sufficient = ($requiredQty <= $sumQty);
    if (!$sufficient) { $allSufficient = false; }

    $result[] = [
      'material_name' => $name,
      'unit' => $unit,
      'required_qty' => $requiredQty,
      'total_available' => $sumQty,
      'sufficient' => $sufficient,
      'suppliers' => $suppliers
    ];
  }
  $stmt->close();

  echo json_encode([
    'status' => 'success',
    'order_id' => $orderId,
    'requirements' => $result,
    'summary' => [
      'sufficient' => $allSufficient,
      'total_required' => $totalRequired,
      'total_available' => $totalAvailable
    ],
    'missing_mappings' => $missingMappings
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "Server error", "detail" => $e->getMessage()]);
}