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
  
  // Send payment success email
  if ($user && $order) {
    $emailSender = new SimpleEmailSender();
    $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    // Send to customer
    $emailSender->sendPaymentSuccessEmail(
      $user['email'], 
      $fullName !== '' ? $fullName : 'Customer', 
      $order
    );

    // Also notify admin
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