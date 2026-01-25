<?php
// Test version of create order that bypasses Razorpay for local testing
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

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
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
    if (!$user_id) {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        if (!empty($input['user_id'])) { 
            $user_id = $input['user_id']; 
        }
    }
    if (!$user_id) { 
        echo json_encode(['status' => 'error', 'message' => 'User ID required']); 
        exit; 
    }

    $database = new Database();
    $db = $database->getConnection();

    // Load cart
    $stmt = $db->prepare("SELECT c.id as cart_id, c.artwork_id, c.quantity, a.price, a.title, a.weight, a.requires_customization FROM cart c JOIN artworks a ON c.artwork_id=a.id WHERE c.user_id=? AND a.status='active'");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!$cart) { 
        echo json_encode(['status'=>'error','message'=>'Cart is empty']); 
        exit; 
    }

    // Check customization (skip for test mode)
    $customization_required = false;
    foreach ($cart as $item) {
        if (!empty($item['requires_customization'])) {
            $customization_required = true;
            break;
        }
    }

    if ($customization_required) {
        $customization_stmt = $db->prepare("SELECT id FROM custom_requests WHERE user_id=? AND status='completed' ORDER BY created_at DESC LIMIT 1");
        $customization_stmt->execute([$user_id]);
        $customization = $customization_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$customization) {
            echo json_encode(['status' => 'error', 'message' => 'Customization request must be approved by admin before payment.']);
            exit;
        }
    }

    // Calculate totals
    $subtotal = 0.0;
    $totalWeight = 0.0;
    foreach ($cart as $item) {
        $price = (float)$item['price'];
        $subtotal += $price * $item['quantity'];
        $weight = isset($item['weight']) && $item['weight'] > 0 ? (float)$item['weight'] : 0.5;
        $totalWeight += $weight * $item['quantity'];
    }

    $tax = 0.0;
    $shipping = max(60.0, ceil($totalWeight) * 60.0);
    
    // Get request body
    $bodyInput = json_decode(file_get_contents('php://input'), true) ?: [];
    
    // Extract addon costs
    $addon_total = 0.0;
    $selected_addons = [];
    if (!empty($bodyInput['selected_addons']) && is_array($bodyInput['selected_addons'])) {
        foreach ($bodyInput['selected_addons'] as $addon) {
            if (isset($addon['id'], $addon['name'], $addon['price'])) {
                $addon_price = (float)$addon['price'];
                $addon_total += $addon_price;
                $selected_addons[] = $addon;
            }
        }
    }
    
    $total = $subtotal + $tax + $shipping + $addon_total;

    // Get shipping address
    $shipping_address = $bodyInput['shipping_address'] ?? null;

    // Create local order
    $db->beginTransaction();
    $order_number = 'ORD-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)),0,6);
    
    $ins = $db->prepare("INSERT INTO orders (user_id, order_number, status, payment_method, payment_status, total_amount, subtotal, tax_amount, shipping_cost, shipping_charges, weight, shipping_address, created_at) VALUES (?, ?, 'pending', 'razorpay', 'pending', ?, ?, ?, ?, ?, ?, ?, NOW())");
    $ins->execute([$user_id, $order_number, $total, $subtotal, $tax, $shipping, $shipping, $totalWeight, $shipping_address]);
    $order_id = (int)$db->lastInsertId();

    // Insert order items
    $insItem = $db->prepare("INSERT INTO order_items (order_id, artwork_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($cart as $item) { 
        $insItem->execute([$order_id, $item['artwork_id'], $item['quantity'], $item['price']]); 
    }

    // Store selected addons if table exists
    $hasAddonsTable = false;
    try { 
        $chk = $db->query("SHOW TABLES LIKE 'order_addons'"); 
        $hasAddonsTable = $chk && $chk->rowCount() > 0; 
    } catch (Throwable $e) {}
    
    if ($hasAddonsTable && !empty($selected_addons)) {
        $insAddon = $db->prepare("INSERT INTO order_addons (order_id, addon_id, addon_name, addon_price, created_at) VALUES (?, ?, ?, ?, NOW())");
        foreach ($selected_addons as $addon) {
            $insAddon->execute([$order_id, $addon['id'], $addon['name'], (float)$addon['price']]);
        }
    }

    // Create mock Razorpay order for testing
    $mock_razorpay_order_id = 'order_test_' . time() . '_' . $order_id;
    
    // Save mock order id
    $upd = $db->prepare("UPDATE orders SET razorpay_order_id=? WHERE id=?");
    $upd->execute([$mock_razorpay_order_id, $order_id]);

    $db->commit();

    echo json_encode([
        'status' => 'success',
        'order' => [
            'id' => $order_id,
            'order_number' => $order_number,
            'razorpay_order_id' => $mock_razorpay_order_id,
            'amount' => $total,
            'currency' => 'INR',
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'addon_total' => $addon_total,
            'weight' => $totalWeight
        ],
        'key_id' => 'rzp_test_mock',
        'test_mode' => true
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) { 
        $db->rollBack(); 
    }
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>