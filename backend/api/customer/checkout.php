<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    // Get user ID robustly from headers or query (align with wishlist/cart)
    $user_id = null;
    if (isset($_SERVER['HTTP_X_USER_ID']) && $_SERVER['HTTP_X_USER_ID'] !== '') {
        $user_id = $_SERVER['HTTP_X_USER_ID'];
    }
    if (!$user_id) {
        $altKeys = ['REDIRECT_HTTP_X_USER_ID', 'X_USER_ID', 'HTTP_X_USERID'];
        foreach ($altKeys as $k) {
            if (isset($_SERVER[$k]) && $_SERVER[$k] !== '') {
                $user_id = $_SERVER[$k];
                break;
            }
        }
    }
    if (!$user_id && function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $key => $value) {
            if (strtolower(trim($key)) === 'x-user-id' && $value !== '') {
                $user_id = $value;
                break;
            }
        }
    }
    if (!$user_id && isset($_GET['user_id']) && $_GET['user_id'] !== '') {
        $user_id = $_GET['user_id'];
    }
    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'User ID required']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $shipping_address = $input['shipping_address'] ?? null;

    // Begin transaction
    $db->beginTransaction();

    // Fetch cart items with latest artwork data
    $cartQuery = "SELECT c.id as cart_id, c.artwork_id, c.quantity, a.price, a.title, a.image_url
                  FROM cart c
                  JOIN artworks a ON c.artwork_id = a.id
                  WHERE c.user_id = ? AND a.status = 'active'";
    $cartStmt = $db->prepare($cartQuery);
    $cartStmt->execute([$user_id]);
    $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$cartItems || count($cartItems) === 0) {
        $db->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Cart is empty']);
        exit;
    }

    // Compute totals
    $subtotal = 0.0;
    foreach ($cartItems as $item) {
        $subtotal += ((float)$item['price']) * ((int)$item['quantity']);
    }
    $tax = 0.0; // extend later if needed
    $shipping = 0.0; // extend later if needed
    $total = $subtotal + $tax + $shipping;

    // Generate order number
    $order_number = 'ORD-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);

    // Insert order
    $orderInsert = "INSERT INTO orders (user_id, order_number, status, total_amount, subtotal, tax_amount, shipping_cost, shipping_address, created_at)
                    VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, NOW())";
    $orderStmt = $db->prepare($orderInsert);
    $orderStmt->execute([$user_id, $order_number, $total, $subtotal, $tax, $shipping, $shipping_address]);
    $order_id = (int)$db->lastInsertId();

    // Insert order items
    $itemInsert = $db->prepare("INSERT INTO order_items (order_id, artwork_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($cartItems as $item) {
        $itemInsert->execute([$order_id, $item['artwork_id'], $item['quantity'], $item['price']]);
    }

    // Clear cart
    $clearStmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
    $clearStmt->execute([$user_id]);

    $db->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Order placed successfully',
        'order' => [
            'id' => $order_id,
            'order_number' => $order_number,
            'total_amount' => number_format($total, 2),
            'subtotal' => number_format($subtotal, 2),
            'tax_amount' => number_format($tax, 2),
            'shipping_cost' => number_format($shipping, 2)
        ]
    ]);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => 'Checkout error: ' . $e->getMessage()
    ]);
}