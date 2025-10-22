 <?php
// c:\xampp\dbsql\htdocs\my_little_thingz\backend\api\customer\razorpay-verify.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once '../../config/database.php';
require_once '../../includes/SimpleEmailSender.php';
$config = require __DIR__ . '/../../config/razorpay.php';

function rp_sign($data, $secret) {
  return hash_hmac('sha256', $data, $secret);
}

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status'=>'error','message'=>'Method not allowed']);
    exit;
  }

  // Resolve user_id
  $user_id = null;
  if (isset($_SERVER['HTTP_X_USER_ID']) && $_SERVER['HTTP_X_USER_ID'] !== '') { $user_id = $_SERVER['HTTP_X_USER_ID']; }
  if (!$user_id && function_exists('getallheaders')) {
    foreach (getallheaders() as $k => $v) { if (strtolower($k) === 'x-user-id' && $v !== '') { $user_id = $v; break; } }
  }
  if (!$user_id && isset($_GET['user_id']) && $_GET['user_id'] !== '') { $user_id = $_GET['user_id']; }

  $input = json_decode(file_get_contents('php://input'), true) ?: [];
  if (!$user_id && !empty($input['user_id'])) { $user_id = $input['user_id']; }
  if (!$user_id) { echo json_encode(['status'=>'error','message'=>'User ID required']); exit; }

  $local_order_id      = $input['order_id'] ?? null;                // local order id (int)
  $razorpay_order_id   = $input['razorpay_order_id'] ?? '';
  $razorpay_payment_id = $input['razorpay_payment_id'] ?? '';
  $razorpay_signature  = $input['razorpay_signature'] ?? '';

  if (!$local_order_id || !$razorpay_order_id || !$razorpay_payment_id || !$razorpay_signature) {
    echo json_encode(['status'=>'error','message'=>'Missing verification parameters']);
    exit;
  }

  $expected = rp_sign($razorpay_order_id . '|' . $razorpay_payment_id, $config['key_secret']);
  if (!hash_equals($expected, $razorpay_signature)) {
    // Mark failed
    $database = new Database();
    $db = $database->getConnection();
    $upd = $db->prepare("UPDATE orders SET payment_status='failed' WHERE id=? AND user_id=?");
    $upd->execute([$local_order_id, $user_id]);

    // Get user and order details for email
    $userStmt = $db->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
    $userStmt->execute([$user_id]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    $orderStmt = $db->prepare("SELECT order_number, total_amount, subtotal, tax_amount, shipping_cost, shipping_address, created_at FROM orders WHERE id = ?");
    $orderStmt->execute([$local_order_id]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get order items with artwork details
    $itemsStmt = $db->prepare("SELECT 
      oi.quantity, 
      oi.price, 
      a.title as artwork_name, 
      a.image_url as artwork_image
      FROM order_items oi 
      JOIN artworks a ON oi.artwork_id = a.id 
      WHERE oi.order_id = ?");
    $itemsStmt->execute([$local_order_id]);
    $order_items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add items to order details
    if ($order) {
      $order['items'] = $order_items;
    }
    
    // Send payment failure email
    if ($user && $order) {
      $emailSender = new SimpleEmailSender();
      $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
      $emailSender->sendPaymentFailureEmail(
        $user['email'], 
        $fullName !== '' ? $fullName : 'Customer', 
        $order, 
        'Payment signature verification failed'
      );
    }

    echo json_encode(['status'=>'error','message'=>'Signature verification failed']);
    exit;
  }

  $database = new Database();
  $db = $database->getConnection();

  // Update order as paid/processing
  $upd = $db->prepare("UPDATE orders 
    SET payment_method='razorpay', payment_status='paid', status='processing',
        razorpay_order_id=?, razorpay_payment_id=?, razorpay_signature=?
    WHERE id=? AND user_id=?");
  $upd->execute([$razorpay_order_id, $razorpay_payment_id, $razorpay_signature, $local_order_id, $user_id]);

  // ========================================
  // AUTOMATIC SHIPROCKET PROCESSING
  // ========================================
  try {
    require_once __DIR__ . '/../../services/ShiprocketAutomation.php';
    
    $automation = new ShiprocketAutomation();
    $automationResult = $automation->processOrder($local_order_id, $user_id);
    
    // Log the automation result
    if ($automationResult['status'] === 'success') {
      $logMessage = "Shiprocket automation for order #$local_order_id: ";
      if ($automationResult['shipment_created']) {
        $logMessage .= "Shipment created. ";
      }
      if ($automationResult['courier_assigned']) {
        $logMessage .= "Courier assigned (" . $automationResult['courier_name'] . "). ";
      }
      if ($automationResult['pickup_scheduled']) {
        $logMessage .= "Pickup scheduled. ";
      }
      error_log($logMessage);
    } else {
      error_log("Shiprocket automation failed for order #$local_order_id: " . implode(', ', $automationResult['errors']));
    }
  } catch (Exception $shipmentError) {
    // Don't fail the payment if shipment automation fails
    // Just log the error for admin to handle manually
    error_log("Shiprocket automation error for order #$local_order_id: " . $shipmentError->getMessage());
  }
  // ========================================
  // END AUTOMATIC SHIPROCKET PROCESSING
  // ========================================

  // Get user and order details for email
  $userStmt = $db->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
  $userStmt->execute([$user_id]);
  $user = $userStmt->fetch(PDO::FETCH_ASSOC);
  
  $orderStmt = $db->prepare("SELECT order_number, total_amount, subtotal, tax_amount, shipping_cost, shipping_address, created_at FROM orders WHERE id = ?");
  $orderStmt->execute([$local_order_id]);
  $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
  
  $itemsStmt = $db->prepare("SELECT 
    oi.quantity, 
    oi.price, 
    a.title as artwork_name, 
    a.image_url as artwork_image
    FROM order_items oi 
    JOIN artworks a ON oi.artwork_id = a.id 
    WHERE oi.order_id = ?");
  $itemsStmt->execute([$local_order_id]);
  $order_items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

  $hasAddonsTable = false;
  try {
    $addonsCheck = $db->query("SHOW TABLES LIKE 'order_addons'");
    $hasAddonsTable = $addonsCheck && $addonsCheck->rowCount() > 0;
  } catch (Throwable $e) {
    $hasAddonsTable = false;
  }

  $order_addons = [];
  $addon_total = 0.0;
  if ($hasAddonsTable) {
    $addonsStmt = $db->prepare("SELECT addon_name, addon_price FROM order_addons WHERE order_id = ?");
    $addonsStmt->execute([$local_order_id]);
    $order_addons = $addonsStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($order_addons as $addonRow) {
      $addon_total += (float)($addonRow['addon_price'] ?? 0);
    }
  }

  $hasInvoicesTable = false;
  try {
    $invoiceCheck = $db->query("SHOW TABLES LIKE 'invoices'");
    $hasInvoicesTable = $invoiceCheck && $invoiceCheck->rowCount() > 0;
  } catch (Throwable $e) {
    $hasInvoicesTable = false;
  }

  if (!$hasInvoicesTable) {
    $createInvoiceSql = "CREATE TABLE IF NOT EXISTS invoices (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      order_id INT NOT NULL,
      invoice_number VARCHAR(100) NOT NULL,
      invoice_date DATETIME NOT NULL,
      billing_name VARCHAR(191) DEFAULT NULL,
      billing_email VARCHAR(191) DEFAULT NULL,
      billing_address TEXT,
      subtotal DECIMAL(10,2) DEFAULT 0.00,
      tax_amount DECIMAL(10,2) DEFAULT 0.00,
      shipping_cost DECIMAL(10,2) DEFAULT 0.00,
      addon_total DECIMAL(10,2) DEFAULT 0.00,
      total_amount DECIMAL(10,2) NOT NULL,
      items_json LONGTEXT,
      addons_json LONGTEXT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY uniq_invoice_number (invoice_number),
      UNIQUE KEY uniq_invoice_order (order_id),
      CONSTRAINT invoices_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    try {
      $db->exec($createInvoiceSql);
      $hasInvoicesTable = true;
    } catch (Throwable $e) {
      $hasInvoicesTable = false;
    }
  }

  if ($hasInvoicesTable && $order) {
    $invoiceItems = [];
    foreach ($order_items as $itemRow) {
      $qty = (int)($itemRow['quantity'] ?? 0);
      $price = (float)($itemRow['price'] ?? 0);
      $invoiceItems[] = [
        'name' => $itemRow['artwork_name'] ?? '',
        'quantity' => $qty,
        'price' => $price,
        'line_total' => $qty * $price
      ];
    }
    $invoiceAddons = [];
    foreach ($order_addons as $addonRow) {
      $invoiceAddons[] = [
        'name' => $addonRow['addon_name'] ?? '',
        'price' => (float)($addonRow['addon_price'] ?? 0)
      ];
    }
    $invoiceNumberBase = (string)($order['order_number'] ?? '');
    $invoiceNumber = 'INV-' . preg_replace('/^ORD-/', '', $invoiceNumberBase);
    if ($invoiceNumber === 'INV-' || strlen($invoiceNumber) < 5) {
      try {
        $invoiceNumber .= strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
      } catch (Throwable $e) {
        $invoiceNumber .= strtoupper(substr(md5((string)$local_order_id), 0, 6));
      }
    }
    $billingName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    $billingEmail = $user['email'] ?? null;
    $invoiceDate = date('Y-m-d H:i:s');
    $invoiceFetch = $db->prepare("SELECT id, invoice_number FROM invoices WHERE order_id = ? LIMIT 1");
    $invoiceFetch->execute([$local_order_id]);
    $invoiceRecord = $invoiceFetch->fetch(PDO::FETCH_ASSOC);
    if ($invoiceRecord) {
      $invoiceNumber = $invoiceRecord['invoice_number'] ?? $invoiceNumber;
      $updateInvoice = $db->prepare("UPDATE invoices SET invoice_number = ?, invoice_date = ?, billing_name = ?, billing_email = ?, billing_address = ?, subtotal = ?, tax_amount = ?, shipping_cost = ?, addon_total = ?, total_amount = ?, items_json = ?, addons_json = ? WHERE order_id = ?");
      $updateInvoice->execute([
        $invoiceNumber,
        $invoiceDate,
        $billingName !== '' ? $billingName : null,
        $billingEmail,
        $order['shipping_address'] ?? null,
        (float)($order['subtotal'] ?? 0),
        (float)($order['tax_amount'] ?? 0),
        (float)($order['shipping_cost'] ?? 0),
        $addon_total,
        (float)($order['total_amount'] ?? 0),
        json_encode($invoiceItems),
        json_encode($invoiceAddons),
        $local_order_id
      ]);
    } else {
      $insertInvoice = $db->prepare("INSERT INTO invoices (order_id, invoice_number, invoice_date, billing_name, billing_email, billing_address, subtotal, tax_amount, shipping_cost, addon_total, total_amount, items_json, addons_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $insertInvoice->execute([
        $local_order_id,
        $invoiceNumber,
        $invoiceDate,
        $billingName !== '' ? $billingName : null,
        $billingEmail,
        $order['shipping_address'] ?? null,
        (float)($order['subtotal'] ?? 0),
        (float)($order['tax_amount'] ?? 0),
        (float)($order['shipping_cost'] ?? 0),
        $addon_total,
        (float)($order['total_amount'] ?? 0),
        json_encode($invoiceItems),
        json_encode($invoiceAddons)
      ]);
    }
    $invoiceFinalize = $db->prepare("SELECT invoice_number FROM invoices WHERE order_id = ? LIMIT 1");
    $invoiceFinalize->execute([$local_order_id]);
    $invoiceData = $invoiceFinalize->fetch(PDO::FETCH_ASSOC);
    if ($invoiceData && isset($invoiceData['invoice_number'])) {
      $order['invoice_number'] = $invoiceData['invoice_number'];
    } else {
      $order['invoice_number'] = $invoiceNumber;
    }
    $order['invoices_enabled'] = true;
  }
  
  if ($order) {
    $order['items'] = $order_items;
    if (!empty($order_addons)) {
      $order['addons'] = $order_addons;
    }
    $order['addon_total'] = $addon_total;
  }
  
  if ($user && $order) {
    $emailSender = new SimpleEmailSender();
    $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    $emailSender->sendPaymentSuccessEmail(
      $user['email'], 
      $fullName !== '' ? $fullName : 'Customer', 
      $order
    );

    $email_config = require __DIR__ . '/../../config/email.php';
    $adminEmail = $email_config['admin_email'] ?? ($email_config['from_email'] ?? null);
    if ($adminEmail) {
      $adminName = $email_config['admin_name'] ?? 'Store Admin';
      $emailSender->sendPaymentSuccessEmail(
        $adminEmail,
        $adminName,
        $order
      );
    }
  }

  // Optional: clear cart
  $clr = $db->prepare("DELETE FROM cart WHERE user_id=?");
  $clr->execute([$user_id]);

  echo json_encode(['status'=>'success','message'=>'Payment verified']);
} catch (Exception $e) {
  echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}