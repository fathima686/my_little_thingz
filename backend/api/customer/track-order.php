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

    // Identify the user (to ensure they own the order)
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

    // Ensure the order belongs to the user and get AWB
    $q = "SELECT tracking_number FROM orders WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($q);
    $stmt->execute([$orderId, $user_id]);
    $awb = $stmt->fetchColumn();

    if (!$awb) {
        echo json_encode(['status' => 'error', 'message' => 'Tracking not available for this order']);
        exit;
    }

    $shiprocket = new Shiprocket();
    $tracking = $shiprocket->trackOrder($awb);

    if (isset($tracking['tracking_data'])) {
        echo json_encode(['status' => 'success', 'awb' => $awb, 'data' => $tracking['tracking_data']]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Unable to fetch tracking', 'response' => $tracking]);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>




