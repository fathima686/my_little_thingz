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
    $order_ids = $input['order_ids'] ?? [];

    if (empty($order_ids) || !is_array($order_ids)) {
        echo json_encode(['status' => 'error', 'message' => 'Order IDs array is required']);
        exit;
    }

    // Get shipment IDs for the orders
    $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
    $orderQuery = "SELECT id, order_number, shiprocket_shipment_id, awb_code 
                   FROM orders 
                   WHERE id IN ($placeholders) 
                   AND shiprocket_shipment_id IS NOT NULL 
                   AND awb_code IS NOT NULL";
    $stmt = $db->prepare($orderQuery);
    $stmt->execute($order_ids);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($orders)) {
        echo json_encode(['status' => 'error', 'message' => 'No valid orders found with shipments']);
        exit;
    }

    $shipment_ids = array_column($orders, 'shiprocket_shipment_id');

    // Generate manifest
    $shiprocket = new Shiprocket();
    $response = $shiprocket->generateManifest($shipment_ids);

    if (isset($response['status']) && $response['status'] == 1) {
        $manifest_url = $response['manifest_url'] ?? null;

        // Update orders with manifest URL
        if ($manifest_url) {
            $updateQuery = "UPDATE orders SET manifest_url = ? WHERE id = ?";
            $stmt = $db->prepare($updateQuery);
            
            foreach ($order_ids as $order_id) {
                $stmt->execute([$manifest_url, $order_id]);
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Manifest generated successfully',
            'data' => [
                'manifest_url' => $manifest_url,
                'order_count' => count($orders),
                'orders' => array_map(function($order) {
                    return [
                        'order_id' => $order['id'],
                        'order_number' => $order['order_number'],
                        'awb_code' => $order['awb_code']
                    ];
                }, $orders)
            ]
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to generate manifest',
            'response' => $response
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>