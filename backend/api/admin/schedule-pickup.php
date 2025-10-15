<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';
require_once '../../models/Shiprocket.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    // Get admin user ID
    $user_id = null;
    if (isset($_SERVER['HTTP_X_USER_ID']) && $_SERVER['HTTP_X_USER_ID'] !== '') {
        $user_id = $_SERVER['HTTP_X_USER_ID'];
    }

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'Admin authentication required']);
        exit;
    }

    // Verify admin role
    $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'admin') {
        echo json_encode(['status' => 'error', 'message' => 'Admin access required']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $order_id = $input['order_id'] ?? null;

    if (!$order_id) {
        echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
        exit;
    }

    // Get order details
    $orderQuery = "SELECT * FROM orders WHERE id = ?";
    $stmt = $db->prepare($orderQuery);
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit;
    }

    if (!$order['shiprocket_shipment_id']) {
        echo json_encode(['status' => 'error', 'message' => 'Shipment not created for this order']);
        exit;
    }

    if (!$order['awb_code']) {
        echo json_encode(['status' => 'error', 'message' => 'AWB not assigned. Please assign courier first']);
        exit;
    }

    $shiprocket = new Shiprocket();

    // Request pickup
    $pickupResponse = $shiprocket->requestPickup($order['shiprocket_shipment_id']);

    if (isset($pickupResponse['pickup_status']) && $pickupResponse['pickup_status'] == 1) {
        $pickup_scheduled_date = $pickupResponse['response']['pickup_scheduled_date'] ?? null;
        $pickup_token_number = $pickupResponse['response']['pickup_token_number'] ?? null;

        // Generate shipping label
        $labelResponse = $shiprocket->generateLabel([$order['shiprocket_shipment_id']]);
        $label_url = $labelResponse['label_url'] ?? null;

        // Update order with pickup details
        $updateQuery = "UPDATE orders SET 
                        pickup_scheduled_date = ?,
                        pickup_token_number = ?,
                        label_url = ?,
                        status = 'processing'
                        WHERE id = ?";
        $stmt = $db->prepare($updateQuery);
        $stmt->execute([
            $pickup_scheduled_date,
            $pickup_token_number,
            $label_url,
            $order_id
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Pickup scheduled successfully',
            'data' => [
                'pickup_scheduled_date' => $pickup_scheduled_date,
                'pickup_token_number' => $pickup_token_number,
                'label_url' => $label_url,
                'awb_code' => $order['awb_code']
            ]
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to schedule pickup',
            'response' => $pickupResponse
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>