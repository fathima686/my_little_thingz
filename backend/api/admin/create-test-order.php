<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-User-Id');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }

    // Simple admin auth guard
    $adminId = null;
    if (isset($_SERVER['HTTP_X_ADMIN_USER_ID']) && $_SERVER['HTTP_X_ADMIN_USER_ID'] !== '') {
        $adminId = $_SERVER['HTTP_X_ADMIN_USER_ID'];
    }
    if (!$adminId && isset($_GET['admin_id']) && $_GET['admin_id'] !== '') {
        $adminId = $_GET['admin_id'];
    }
    if (!$adminId) {
        echo json_encode(['status' => 'error', 'message' => 'Admin authentication required']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
    $artworkId = isset($input['artwork_id']) ? (int)$input['artwork_id'] : 0;
    $quantity = isset($input['quantity']) && (int)$input['quantity'] > 0 ? (int)$input['quantity'] : 1;
    $shippingAddress = isset($input['shipping_address']) ? $input['shipping_address'] : null;

    if ($userId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Valid user_id required']);
        exit;
    }

    // If artwork not provided, pick the first active one
    if ($artworkId <= 0) {
        $row = $db->query("SELECT id FROM artworks WHERE status='active' ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo json_encode(['status' => 'error', 'message' => 'No active artworks to order']);
            exit;
        }
        $artworkId = (int)$row['id'];
    }

    // Fetch artwork with offer columns similar to checkout
    $art = $db->prepare("SELECT id, price, title, offer_price, offer_percent, offer_starts_at, offer_ends_at FROM artworks WHERE id = ? AND status='active'");
    $art->execute([$artworkId]);
    $artwork = $art->fetch(PDO::FETCH_ASSOC);
    if (!$artwork) {
        echo json_encode(['status' => 'error', 'message' => 'Artwork not found or inactive']);
        exit;
    }

    $now = new DateTime('now');
    $base = (float)$artwork['price'];
    $effective = $base;
    $offerPrice   = isset($artwork['offer_price']) && $artwork['offer_price'] !== null ? (float)$artwork['offer_price'] : null;
    $offerPercent = isset($artwork['offer_percent']) && $artwork['offer_percent'] !== null ? (float)$artwork['offer_percent'] : null;
    $startsAt = isset($artwork['offer_starts_at']) && $artwork['offer_starts_at'] ? new DateTime($artwork['offer_starts_at']) : null;
    $endsAt   = isset($artwork['offer_ends_at']) && $artwork['offer_ends_at'] ? new DateTime($artwork['offer_ends_at']) : null;
    $isWindowOk = true;
    if ($startsAt && $now < $startsAt) $isWindowOk = false;
    if ($endsAt && $now > $endsAt) $isWindowOk = false;
    if ($isWindowOk) {
        if ($offerPrice !== null && $offerPrice > 0 && $offerPrice < $effective) {
            $effective = $offerPrice;
        } elseif ($offerPercent !== null && $offerPercent > 0 && $offerPercent <= 100) {
            $disc = round($base * ($offerPercent / 100), 2);
            $candidate = max(0, $base - $disc);
            if ($candidate < $effective) $effective = $candidate;
        }
    }

    $subtotal = $effective * $quantity;
    $tax = 0.0;
    $shipping = 0.0;
    $total = $subtotal + $tax + $shipping;

    $order_number = 'ORD-TEST-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);

    $db->beginTransaction();

    // Normalize shipping address to a JSON string if provided as array
    if (is_array($shippingAddress)) {
        $shippingAddress = json_encode($shippingAddress, JSON_UNESCAPED_UNICODE);
    }
    $orderStmt = $db->prepare("INSERT INTO orders (user_id, order_number, status, total_amount, subtotal, tax_amount, shipping_cost, shipping_address, created_at) VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, NOW())");
    $orderStmt->execute([$userId, $order_number, $total, $subtotal, $tax, $shipping, $shippingAddress]);
    $orderId = (int)$db->lastInsertId();

    $itemStmt = $db->prepare("INSERT INTO order_items (order_id, artwork_id, quantity, price) VALUES (?, ?, ?, ?)");
    $itemStmt->execute([$orderId, $artworkId, $quantity, $effective]);

    $db->commit();

    echo json_encode(['status' => 'success', 'order_id' => $orderId, 'order_number' => $order_number, 'total' => number_format($total, 2)]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Create test order error: ' . $e->getMessage()]);
}
?>




