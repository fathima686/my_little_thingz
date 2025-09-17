<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once '../../config/database.php';
$config = require __DIR__ . '/../../config/razorpay.php';

// Simple signature util without SDK
function rp_sign($data, $secret) {
    return hash_hmac('sha256', $data, $secret);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }

    // Resolve user_id from headers/query/body
    $user_id = null;
    if (isset($_SERVER['HTTP_X_USER_ID']) && $_SERVER['HTTP_X_USER_ID'] !== '') { $user_id = $_SERVER['HTTP_X_USER_ID']; }
    if (!$user_id && function_exists('getallheaders')) {
        foreach (getallheaders() as $k => $v) { if (strtolower($k) === 'x-user-id' && $v !== '') { $user_id = $v; break; } }
    }
    if (!$user_id && isset($_GET['user_id']) && $_GET['user_id'] !== '') { $user_id = $_GET['user_id']; }
    if (!$user_id) {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        if (!empty($input['user_id'])) { $user_id = $input['user_id']; }
    }
    if (!$user_id) { echo json_encode(['status' => 'error', 'message' => 'User ID required']); exit; }

    $database = new Database();
    $db = $database->getConnection();

    // Load cart with current prices and check for customization requirements
    $stmt = $db->prepare("SELECT c.id as cart_id, c.artwork_id, c.quantity, a.price, a.title, a.requires_customization FROM cart c JOIN artworks a ON c.artwork_id=a.id WHERE c.user_id=? AND a.status='active'");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$cart) { echo json_encode(['status'=>'error','message'=>'Cart is empty']); exit; }

    // Check if any items require customization
    $customization_required = false;
    foreach ($cart as $item) {
        if (!empty($item['requires_customization'])) {
            $customization_required = true;
            break;
        }
    }

    // If customization is required for any cart item, ensure the user has at least one approved custom request
    if ($customization_required) {
        $customization_stmt = $db->prepare("SELECT id FROM custom_requests WHERE user_id=? AND status='completed' ORDER BY created_at DESC LIMIT 1");
        $customization_stmt->execute([$user_id]);
        $customization = $customization_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$customization) {
            echo json_encode(['status' => 'error', 'message' => 'Customization request must be approved by admin before payment.']);
            exit;
        }
    }

    $subtotal = 0.0; foreach ($cart as $it) { $subtotal += ((float)$it['price']) * ((int)$it['quantity']); }
    $tax = 0.0; $shipping = 0.0; $total = $subtotal + $tax + $shipping;

    // Grab shipping address from request body (if provided)
    $bodyInput = json_decode(file_get_contents('php://input'), true) ?: [];
    $shipping_address = $bodyInput['shipping_address'] ?? null;

    // Create local pending order first
    $db->beginTransaction();
    $order_number = 'ORD-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)),0,6);
    $ins = $db->prepare("INSERT INTO orders (user_id, order_number, status, payment_method, payment_status, total_amount, subtotal, tax_amount, shipping_cost, shipping_address, created_at) VALUES (?, ?, 'pending', 'razorpay', 'pending', ?, ?, ?, ?, ?, NOW())");
    $ins->execute([$user_id, $order_number, $total, $subtotal, $tax, $shipping, $shipping_address]);
    $order_id = (int)$db->lastInsertId();

    $insItem = $db->prepare("INSERT INTO order_items (order_id, artwork_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($cart as $it) { $insItem->execute([$order_id, $it['artwork_id'], $it['quantity'], $it['price']]); }

    // Create Razorpay order via REST (no SDK)
    $amountPaise = (int) round($total * 100);
    $payload = json_encode(['amount' => $amountPaise, 'currency' => $config['currency'], 'receipt' => $order_number, 'payment_capture' => 1]);
    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_USERPWD, $config['key_id'] . ':' . $config['key_secret']);
    $resp = curl_exec($ch);
    if ($resp === false) { throw new Exception('Razorpay API error: ' . curl_error($ch)); }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $rp = json_decode($resp, true);
    if ($code >= 400 || !isset($rp['id'])) { throw new Exception('Failed to create Razorpay order: ' . $resp); }

    // Save rp order id
    $upd = $db->prepare("UPDATE orders SET razorpay_order_id=? WHERE id=?");
    $upd->execute([$rp['id'], $order_id]);

    $db->commit();

    echo json_encode([
        'status' => 'success',
        'order' => [
            'id' => $order_id,
            'order_number' => $order_number,
            'razorpay_order_id' => $rp['id'],
            'amount' => $total,
            'currency' => $config['currency'],
        ],
        'key_id' => $config['key_id']
    ]);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}