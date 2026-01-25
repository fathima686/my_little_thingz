<?php
// Test version of payment verification that bypasses Razorpay signature check
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('html_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200);
    exit(0); 
}

set_error_handler(function($severity, $message, $file, $line) {
    error_log("PHP Error: $message in $file on line $line");
    return true;
});

set_exception_handler(function($exception) {
    error_log("Uncaught Exception: " . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
    exit;
});

require_once '../../config/database.php';
require_once '../../includes/SimpleEmailSender.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status'=>'error','message'=>'Method not allowed']);
        exit;
    }

    // Resolve user_id
    $user_id = null;
    if (isset($_SERVER['HTTP_X_USER_ID']) && $_SERVER['HTTP_X_USER_ID'] !== '') { 
        $user_id = $_SERVER['HTTP_X_USER_ID']; 
    }
    if (!$user_id && function_exists('getallheaders')) {
        foreach (getallheaders() as $k => $v) { 
            if (strtolower($k) === 'x-user-id' && $v !== '') { 
                $user_id = $v; 
                break; 
            } 
        }
    }
    if (!$user_id && isset($_GET['user_id']) && $_GET['user_id'] !== '') { 
        $user_id = $_GET['user_id']; 
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    if (!$user_id && !empty($input['user_id'])) { 
        $user_id = $input['user_id']; 
    }
    if (!$user_id) { 
        echo json_encode(['status'=>'error','message'=>'User ID required']); 
        exit; 
    }

    $local_order_id      = $input['order_id'] ?? null;
    $razorpay_order_id   = $input['razorpay_order_id'] ?? '';
    $razorpay_payment_id = $input['razorpay_payment_id'] ?? '';
    $razorpay_signature  = $input['razorpay_signature'] ?? '';

    if (!$local_order_id || !$razorpay_order_id) {
        echo json_encode(['status'=>'error','message'=>'Missing verification parameters']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    // For test mode, skip signature verification and create mock payment data
    $mock_payment_id = $razorpay_payment_id ?: ('pay_test_' . time() . '_' . $local_order_id);
    $mock_signature = $razorpay_signature ?: ('sig_test_' . time());

    // Update order as paid/processing
    $upd = $db->prepare("UPDATE orders 
        SET payment_method='razorpay', payment_status='paid', status='processing',
            razorpay_order_id=?, razorpay_payment_id=?, razorpay_signature=?
        WHERE id=? AND user_id=?");
    $upd->execute([$razorpay_order_id, $mock_payment_id, $mock_signature, $local_order_id, $user_id]);

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

    // Get addons if table exists
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

    if ($order) {
        $order['items'] = $order_items;
        if (!empty($order_addons)) {
            $order['addons'] = $order_addons;
        }
        $order['addon_total'] = $addon_total;
        $order['test_mode'] = true;
    }
    
    // Send success email (optional for test mode)
    if ($user && $order) {
        try {
            $emailSender = new SimpleEmailSender();
            $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            $emailSender->sendPaymentSuccessEmail(
                $user['email'], 
                $fullName !== '' ? $fullName : 'Customer', 
                $order
            );
        } catch (Exception $emailError) {
            // Don't fail payment if email fails
            error_log("Email sending failed: " . $emailError->getMessage());
        }
    }

    // Clear cart
    $clr = $db->prepare("DELETE FROM cart WHERE user_id=?");
    $clr->execute([$user_id]);

    echo json_encode([
        'status'=>'success',
        'message'=>'Payment verified (test mode)',
        'test_mode' => true
    ]);
    
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>