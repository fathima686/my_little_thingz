<?php
// Admin procurement: verify Razorpay payment

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

require_once __DIR__ . '/../../config/razorpay.php';
$rpConfig = require __DIR__ . '/../../config/razorpay.php';

function ensure_schema(mysqli $db) {
  $db->query("CREATE TABLE IF NOT EXISTS roles (id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL UNIQUE) ENGINE=InnoDB");
  $db->query("INSERT IGNORE INTO roles (id, name) VALUES (1,'admin'),(2,'customer'),(3,'supplier')");
  $db->query("CREATE TABLE IF NOT EXISTS user_roles (user_id INT UNSIGNED NOT NULL, role_id TINYINT UNSIGNED NOT NULL, PRIMARY KEY (user_id, role_id)) ENGINE=InnoDB");
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
    supplier_product_id INT UNSIGNED NOT NULL,
    name VARCHAR(200) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantity INT NOT NULL DEFAULT 1,
    supplier_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(purchase_order_id)
  ) ENGINE=InnoDB");
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
$local_id = (int)($input['purchase_order_id'] ?? 0);
$razorpay_order_id = trim($input['razorpay_order_id'] ?? '');
$razorpay_payment_id = trim($input['razorpay_payment_id'] ?? '');
$razorpay_signature = trim($input['razorpay_signature'] ?? '');

if ($local_id <= 0 || $razorpay_order_id === '' || $razorpay_payment_id === '' || $razorpay_signature === '') {
  echo json_encode(['status'=>'error','message'=>'Missing parameters']);
  exit;
}

// Verify signature
$expected = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, $rpConfig['key_secret'] ?? '');
if (!hash_equals($expected, $razorpay_signature)) {
  $upd = $mysqli->prepare("UPDATE purchase_orders SET payment_status='failed' WHERE id=?");
  $upd->bind_param('i', $local_id);
  $upd->execute();
  echo json_encode(['status'=>'error','message'=>'Signature verification failed']);
  exit;
}

$upd = $mysqli->prepare("UPDATE purchase_orders SET payment_status='paid', status='processing', razorpay_order_id=?, razorpay_payment_id=?, razorpay_signature=? WHERE id=?");
$upd->bind_param('sssi', $razorpay_order_id, $razorpay_payment_id, $razorpay_signature, $local_id);
$upd->execute();

// Send email notifications
require_once __DIR__ . '/../../includes/EmailSender.php';
$emailSender = new EmailSender();

// Get order details for email
$orderQuery = $mysqli->prepare("
 SELECT po.order_number, po.total_amount, po.currency, u.email as admin_email, u.first_name as admin_first, u.last_name as admin_last
 FROM purchase_orders po
 JOIN users u ON po.admin_id = u.id
 WHERE po.id = ?
");
$orderQuery->bind_param('i', $local_id);
$orderQuery->execute();
$orderResult = $orderQuery->get_result();
$orderData = $orderResult->fetch_assoc();
$orderQuery->close();

if ($orderData) {
 $order_details = [
  'order_number' => $orderData['order_number'],
  'total_amount' => (float)$orderData['total_amount'],
  'currency' => $orderData['currency'],
  'payment_method' => 'Razorpay'
 ];

 $admin_email = $orderData['admin_email'];
 $admin_name = $orderData['admin_first'] . ' ' . $orderData['admin_last'];

 // Send email to admin
 $emailSender->sendProcurementSuccessEmail($admin_email, $admin_name, $order_details, 'admin');

 // Get unique suppliers from order items
 $supplierQuery = $mysqli->prepare("
  SELECT DISTINCT u.email, u.first_name, u.last_name
  FROM purchase_order_items poi
  JOIN users u ON poi.supplier_id = u.id
  WHERE poi.purchase_order_id = ?
 ");
 $supplierQuery->bind_param('i', $local_id);
 $supplierQuery->execute();
 $supplierResult = $supplierQuery->get_result();

 while ($supplier = $supplierResult->fetch_assoc()) {
  $supplier_email = $supplier['email'];
  $supplier_name = $supplier['first_name'] . ' ' . $supplier['last_name'];

  // Send email to supplier
  $emailSender->sendProcurementSuccessEmail($supplier_email, $supplier_name, $order_details, 'supplier');
 }
 $supplierQuery->close();
}

echo json_encode(['status'=>'success','message'=>'Payment verified']);