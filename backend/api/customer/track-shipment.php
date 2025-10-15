<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';
require_once '../../models/Shiprocket.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Identify the user
    $user_id = null;
    if (isset($_SERVER['HTTP_X_USER_ID']) && $_SERVER['HTTP_X_USER_ID'] !== '') {
        $user_id = $_SERVER['HTTP_X_USER_ID'];
    }
    if (!$user_id && isset($_GET['user_id']) && $_GET['user_id'] !== '') {
        $user_id = $_GET['user_id'];
    }

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'User ID required']);
        exit;
    }

    $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
    if ($orderId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Valid order_id required']);
        exit;
    }

    // Get order details with shipment info
    $q = "SELECT o.*, 
          CONCAT(u.first_name, ' ', u.last_name) as customer_name, 
          u.email as customer_email
          FROM orders o
          JOIN users u ON o.user_id = u.id
          WHERE o.id = ? AND o.user_id = ?";
    $stmt = $db->prepare($q);
    $stmt->execute([$orderId, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit;
    }

    // Check if shipment exists
    if (!$order['awb_code'] && !$order['shiprocket_shipment_id']) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Shipment not yet created',
            'order' => [
                'order_number' => $order['order_number'],
                'status' => $order['status'],
                'created_at' => $order['created_at'],
                'total_amount' => $order['total_amount']
            ],
            'tracking_available' => false
        ]);
        exit;
    }

    $shiprocket = new Shiprocket();
    $trackingData = null;

    // Try to track by AWB first
    if ($order['awb_code']) {
        $trackingResponse = $shiprocket->trackOrder($order['awb_code']);
        
        if (isset($trackingResponse['tracking_data'])) {
            $trackingData = $trackingResponse['tracking_data'];
            
            // Store tracking history in database
            if (isset($trackingData['shipment_track']) && is_array($trackingData['shipment_track'])) {
                foreach ($trackingData['shipment_track'] as $track) {
                    // Check if this tracking entry already exists
                    $checkQuery = "SELECT id FROM shipment_tracking_history 
                                   WHERE order_id = ? AND awb_code = ? AND tracking_date = ?";
                    $checkStmt = $db->prepare($checkQuery);
                    $trackingDate = $track['date'] ?? null;
                    $checkStmt->execute([$orderId, $order['awb_code'], $trackingDate]);
                    
                    if (!$checkStmt->fetch()) {
                        // Insert new tracking entry
                        $insertQuery = "INSERT INTO shipment_tracking_history 
                                        (order_id, awb_code, status, status_code, location, remarks, tracking_date)
                                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $insertStmt = $db->prepare($insertQuery);
                        $insertStmt->execute([
                            $orderId,
                            $order['awb_code'],
                            $track['status'] ?? '',
                            $track['status_code'] ?? null,
                            $track['location'] ?? null,
                            $track['remarks'] ?? null,
                            $trackingDate
                        ]);
                    }
                }
            }
        }
    }
    // Fallback to shipment ID tracking
    elseif ($order['shiprocket_shipment_id']) {
        $trackingResponse = $shiprocket->trackShipment($order['shiprocket_shipment_id']);
        if (isset($trackingResponse['tracking_data'])) {
            $trackingData = $trackingResponse['tracking_data'];
        }
    }

    // Get tracking history from database
    $historyQuery = "SELECT * FROM shipment_tracking_history 
                     WHERE order_id = ? 
                     ORDER BY tracking_date DESC";
    $historyStmt = $db->prepare($historyQuery);
    $historyStmt->execute([$orderId]);
    $trackingHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'order' => [
            'order_number' => $order['order_number'],
            'status' => $order['status'],
            'created_at' => $order['created_at'],
            'total_amount' => $order['total_amount'],
            'awb_code' => $order['awb_code'],
            'courier_name' => $order['courier_name'],
            'pickup_scheduled_date' => $order['pickup_scheduled_date'],
            'estimated_delivery' => $order['estimated_delivery']
        ],
        'tracking_available' => true,
        'tracking_data' => $trackingData,
        'tracking_history' => $trackingHistory
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>