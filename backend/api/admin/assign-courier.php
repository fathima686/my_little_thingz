<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';
require_once '../../models/Shiprocket.php';

try {
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

    // GET: Get available couriers for an order
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $order_id = $_GET['order_id'] ?? null;

        if (!$order_id) {
            echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
            exit;
        }

        // Get order details
        $orderQuery = "SELECT * FROM orders WHERE id = ?";
        $stmt = $db->prepare($orderQuery);
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order || !$order['shiprocket_shipment_id']) {
            echo json_encode(['status' => 'error', 'message' => 'Order not found or shipment not created']);
            exit;
        }

        // Parse shipping address to get pincode
        $shippingAddress = $order['shipping_address'] ?? '';
        $delivery_pincode = '';
        if (preg_match('/\b(\d{6})\b/', $shippingAddress, $matches)) {
            $delivery_pincode = $matches[1];
        }

        // Load warehouse config
        $warehouseConfig = require __DIR__ . '/../../config/warehouse.php';
        $pickup_pincode = $warehouseConfig['address_fields']['pincode'];

        // Get courier serviceability
        $shiprocket = new Shiprocket();
        $params = [
            'pickup_postcode' => $pickup_pincode,
            'delivery_postcode' => $delivery_pincode ?: '000000',
            'weight' => $order['weight'] ?? 0.5,
            'cod' => $order['payment_method'] === 'COD' ? 1 : 0
        ];

        $response = $shiprocket->getCourierServiceability($params);

        if (isset($response['data']['available_courier_companies'])) {
            echo json_encode([
                'status' => 'success',
                'couriers' => $response['data']['available_courier_companies'],
                'order_id' => $order_id,
                'shipment_id' => $order['shiprocket_shipment_id']
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No couriers available',
                'response' => $response
            ]);
        }
        exit;
    }

    // POST: Assign courier and generate AWB
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $order_id = $input['order_id'] ?? null;
        $courier_id = $input['courier_id'] ?? null;

        if (!$order_id || !$courier_id) {
            echo json_encode(['status' => 'error', 'message' => 'Order ID and Courier ID are required']);
            exit;
        }

        // Get order details
        $orderQuery = "SELECT * FROM orders WHERE id = ?";
        $stmt = $db->prepare($orderQuery);
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order || !$order['shiprocket_shipment_id']) {
            echo json_encode(['status' => 'error', 'message' => 'Order not found or shipment not created']);
            exit;
        }

        if ($order['awb_code']) {
            echo json_encode([
                'status' => 'error',
                'message' => 'AWB already assigned to this order',
                'awb_code' => $order['awb_code']
            ]);
            exit;
        }

        // Generate AWB
        $shiprocket = new Shiprocket();
        $response = $shiprocket->generateAWB($order['shiprocket_shipment_id'], $courier_id);

        if (isset($response['awb_assign_status']) && $response['awb_assign_status'] == 1) {
            $awb_code = $response['response']['data']['awb_code'] ?? null;
            $courier_name = $response['response']['data']['courier_name'] ?? '';

            if ($awb_code) {
                // Update order with AWB details
                $updateQuery = "UPDATE orders SET 
                                awb_code = ?,
                                courier_id = ?,
                                courier_name = ?,
                                tracking_number = ?
                                WHERE id = ?";
                $stmt = $db->prepare($updateQuery);
                $stmt->execute([$awb_code, $courier_id, $courier_name, $awb_code, $order_id]);

                echo json_encode([
                    'status' => 'success',
                    'message' => 'AWB assigned successfully',
                    'data' => [
                        'awb_code' => $awb_code,
                        'courier_name' => $courier_name,
                        'courier_id' => $courier_id
                    ]
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'AWB code not received',
                    'response' => $response
                ]);
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to assign AWB',
                'response' => $response
            ]);
        }
        exit;
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>