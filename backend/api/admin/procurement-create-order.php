<?php
// Admin procurement: create Razorpay order for supplier products

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_origins = ['http://localhost:5173', 'http://127.0.0.1:5173'];
if (in_array($origin, $allowed_origins, true)) {
  header("Access-Control-Allow-Origin: $origin");
} else {
  header("Access-Control-Allow-Origin: http://localhost:5173");
}
header("Vary: Origin");
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-User-Id');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli("localhost", "root", "", "my_little_thingz");
$mysqli->set_charset('utf8mb4');
if ($mysqli->connect_error) { http_response_code(500); echo json_encode(['status'=>'error','message'=>'DB connect failed']); exit; }

$rpConfig = require __DIR__ . '/../../config/razorpay.php';
$warehouse = require __DIR__ . '/../../config/warehouse.php';

function rnd_hex($bytes) {
  if (function_exists('random_bytes')) { return bin2hex(random_bytes($bytes)); }
  if (function_exists('openssl_random_pseudo_bytes')) { return bin2hex(openssl_random_pseudo_bytes($bytes)); }
  return bin2hex(pack('H*', md5(uniqid(mt_rand(), true))));
}

function ensure_schema(mysqli $db) {
  // roles + admin check
  $db->query("CREATE TABLE IF NOT EXISTS roles (id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL UNIQUE) ENGINE=InnoDB");
  $db->query("INSERT IGNORE INTO roles (id, name) VALUES (1,'admin'),(2,'customer'),(3,'supplier')");
  $db->query("CREATE TABLE IF NOT EXISTS user_roles (user_id INT UNSIGNED NOT NULL, role_id TINYINT UNSIGNED NOT NULL, PRIMARY KEY (user_id, role_id)) ENGINE=InnoDB");

  // supplier products table (for FK-less references)
  $db->query("CREATE TABLE IF NOT EXISTS supplier_products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT UNSIGNED NOT NULL,
    name VARCHAR(200) NOT NULL,
    category VARCHAR(100) NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantity INT NOT NULL DEFAULT 0,
    unit VARCHAR(32) NOT NULL DEFAULT 'pcs',
    image_url VARCHAR(500) NULL,
    is_trending TINYINT(1) NOT NULL DEFAULT 0,
    availability ENUM('available','unavailable') NOT NULL DEFAULT 'available',
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB");

  // purchase orders
  $db->query("CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    order_number VARCHAR(64) NOT NULL UNIQUE,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    currency CHAR(3) NOT NULL DEFAULT 'INR',
    payment_method VARCHAR(32) DEFAULT 'razorpay',
    payment_status ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
    status ENUM('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
    razorpay_order_id VARCHAR(64) NULL,
    razorpay_payment_id VARCHAR(64) NULL,
    razorpay_signature VARCHAR(128) NULL,
    shipping_address TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB");

  $db->query("CREATE TABLE IF NOT EXISTS purchase_order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT UNSIGNED NOT NULL,
    supplier_product_id INT UNSIGNED NULL,
    materials_id INT UNSIGNED NULL,
    name VARCHAR(200) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantity INT NOT NULL DEFAULT 1,
    supplier_id INT UNSIGNED NOT NULL,
    colors_json TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(purchase_order_id)
  ) ENGINE=InnoDB");
  // Best-effort add columns if table already existed
  try { $db->query("ALTER TABLE purchase_order_items ADD COLUMN colors_json TEXT NULL"); } catch (Throwable $e) {}
  try { $db->query("ALTER TABLE purchase_order_items ADD COLUMN materials_id INT UNSIGNED NULL AFTER supplier_product_id"); } catch (Throwable $e) {}
  try { $db->query("ALTER TABLE purchase_order_items MODIFY supplier_product_id INT UNSIGNED NULL"); } catch (Throwable $e) {}
}

try { ensure_schema($mysqli); } catch (Throwable $e) {}

// Admin verify
$adminUserId = isset($_SERVER['HTTP_X_ADMIN_USER_ID']) ? (int)$_SERVER['HTTP_X_ADMIN_USER_ID'] : 0;
if ($adminUserId <= 0) { http_response_code(401); echo json_encode(['status'=>'error','message'=>'Missing admin identity']); exit; }
$isAdmin = false;
$chk = $mysqli->prepare("SELECT 1 FROM user_roles ur JOIN roles r ON ur.role_id=r.id WHERE ur.user_id=? AND r.name='admin' LIMIT 1");
$chk->bind_param('i', $adminUserId);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) { $isAdmin = true; }
$chk->close();
if (!$isAdmin) { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Not an admin user']); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['status'=>'error','message'=>'Method not allowed']); exit; }

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$items = $input['items'] ?? []; // [{id, quantity, colors?: [{color, qty}]}]
if (!is_array($items) || empty($items)) { echo json_encode(['status'=>'error','message'=>'No items']); exit; }

// Load products/materials and compute total
$total = 0.0;
$resolved = [];
$getProd = $mysqli->prepare("SELECT id, supplier_id, name, price FROM supplier_products WHERE id=? AND availability='available' LIMIT 1");
$getMat  = $mysqli->prepare("SELECT id, supplier_id, name, price FROM materials WHERE id=? AND availability='available' LIMIT 1");

foreach ($items as $it) {
  $qty = (int)($it['quantity'] ?? 0);
  if ($qty <= 0) { continue; }

  $type = strtolower((string)($it['type'] ?? $it['source'] ?? 'product'));
  $isMaterial = ($type === 'material' || isset($it['materials_id']) || isset($it['material_id']));
  $id = $isMaterial ? (int)($it['materials_id'] ?? $it['material_id'] ?? $it['id'] ?? 0) : (int)($it['id'] ?? 0);
  if ($id <= 0) { continue; }

  $colors = $it['colors'] ?? [];
  if (!is_array($colors)) { $colors = []; }
  // sanitize color rows
  $colors = array_values(array_filter(array_map(function($c){
    $col = isset($c['color']) ? trim((string)$c['color']) : '';
    $q = isset($c['qty']) ? (int)$c['qty'] : 0;
    if ($q <= 0) return null;
    return ['color' => ($col !== '' ? $col : '-') , 'qty' => $q];
  }, $colors)));

  if ($isMaterial) {
    $getMat->bind_param('i', $id);
    $getMat->execute();
    $res = $getMat->get_result();
    if ($row = $res->fetch_assoc()) {
      $lineTotal = ((float)$row['price']) * $qty;
      $total += $lineTotal;
      $resolved[] = [
        'kind' => 'material',
        'id' => (int)$row['id'],
        'supplier_id' => (int)$row['supplier_id'],
        'name' => $row['name'],
        'price' => (float)$row['price'],
        'quantity' => $qty,
        'colors' => $colors,
      ];
    }
  } else {
    $getProd->bind_param('i', $id);
    $getProd->execute();
    $res = $getProd->get_result();
    if ($row = $res->fetch_assoc()) {
      $lineTotal = ((float)$row['price']) * $qty;
      $total += $lineTotal;
      $resolved[] = [
        'kind' => 'product',
        'id' => (int)$row['id'],
        'supplier_id' => (int)$row['supplier_id'],
        'name' => $row['name'],
        'price' => (float)$row['price'],
        'quantity' => $qty,
        'colors' => $colors,
      ];
    }
  }
}
$getProd->close();
$getMat->close();

if (empty($resolved)) { echo json_encode(['status'=>'error','message'=>'No valid items']); exit; }

// Create local purchase order
$order_number = 'PO-' . date('Ymd-His') . '-' . substr(rnd_hex(3),0,6);
// Compose normalized shipping address from warehouse config if structured fields are available
$addr = $warehouse['address'] ?? '';
if (isset($warehouse['address_fields']) && is_array($warehouse['address_fields'])) {
  $wf = $warehouse['address_fields'];
  $line1 = trim(($wf['name'] ?? ''));
  $line2 = trim(($wf['address_line1'] ?? ''));
  $line3 = trim(($wf['address_line2'] ?? ''));
  $line4 = trim(implode(', ', array_filter([
    trim((string)($wf['city'] ?? '')),
    trim((string)($wf['state'] ?? '')),
    trim((string)($wf['pincode'] ?? ''))
  ], fn($v)=>$v!=='')));
  $line5 = trim((string)($wf['country'] ?? ''));
  $line6 = trim((string)($wf['phone'] ?? ''));
  $parts = array_filter([
    $line1,
    $line2,
    $line3,
    $line4,
    $line5,
    $line6 !== '' ? ('Phone: ' . $line6) : ''
  ], fn($v)=>$v!=='' );
  $addr = implode("\n", $parts);
}
$po = $mysqli->prepare("INSERT INTO purchase_orders (admin_id, order_number, total_amount, currency, payment_status, status, shipping_address) VALUES (?, ?, ?, ?, 'pending', 'pending', ?)");
$cur = $rpConfig['currency'] ?? 'INR';
$po->bind_param('isdss', $adminUserId, $order_number, $total, $cur, $addr);
$po->execute();
$purchase_order_id = $po->insert_id;
$po->close();

$insItemProd = $mysqli->prepare("INSERT INTO purchase_order_items (purchase_order_id, supplier_product_id, materials_id, name, price, quantity, supplier_id, colors_json) VALUES (?, ?, NULL, ?, ?, ?, ?, ?)");
$insItemMat  = $mysqli->prepare("INSERT INTO purchase_order_items (purchase_order_id, supplier_product_id, materials_id, name, price, quantity, supplier_id, colors_json) VALUES (?, NULL, ?, ?, ?, ?, ?, ?)");
foreach ($resolved as $r) {
  $colorsJson = json_encode($r['colors'] ?? []);
  if (($r['kind'] ?? 'product') === 'material') {
    $insItemMat->bind_param('iisdiis', $purchase_order_id, $r['id'], $r['name'], $r['price'], $r['quantity'], $r['supplier_id'], $colorsJson);
    $insItemMat->execute();
  } else {
    $insItemProd->bind_param('iisdiis', $purchase_order_id, $r['id'], $r['name'], $r['price'], $r['quantity'], $r['supplier_id'], $colorsJson);
    $insItemProd->execute();
  }
}
$insItemProd->close();
$insItemMat->close();

// Create Razorpay order
if (!function_exists('curl_init')) { http_response_code(500); echo json_encode(['status'=>'error','message'=>'cURL PHP extension not enabled']); exit; }
$amountPaise = (int) round($total * 100);
$payload = json_encode(['amount' => $amountPaise, 'currency' => $cur, 'receipt' => $order_number, 'payment_capture' => 1]);
$ch = curl_init('https://api.razorpay.com/v1/orders');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_USERPWD, ($rpConfig['key_id'] ?? '') . ':' . ($rpConfig['key_secret'] ?? ''));
$resp = curl_exec($ch);
if ($resp === false) { http_response_code(500); echo json_encode(['status'=>'error','message'=>'Razorpay API error: '.curl_error($ch)]); exit; }
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$rp = json_decode($resp, true);
if ($code >= 400 || !isset($rp['id'])) { http_response_code(500); echo json_encode(['status'=>'error','message'=>'Failed to create Razorpay order', 'raw'=>$resp]); exit; }

$upd = $mysqli->prepare("UPDATE purchase_orders SET razorpay_order_id=? WHERE id=?");
$upd->bind_param('si', $rp['id'], $purchase_order_id);
$upd->execute();
$upd->close();

echo json_encode([
  'status' => 'success',
  'order' => [
    'id' => (int)$purchase_order_id,
    'order_number' => $order_number,
    'razorpay_order_id' => $rp['id'],
    'amount' => $total,
    'currency' => $cur,
  ],
  'key_id' => $rpConfig['key_id'] ?? null
]);