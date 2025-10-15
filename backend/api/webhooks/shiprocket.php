<?php
/**
 * Shiprocket Webhook Handler
 * 
 * Receives real-time status updates from Shiprocket
 * 
 * Setup Instructions:
 * 1. Log in to Shiprocket dashboard
 * 2. Go to Settings → API → Webhooks
 * 3. Add webhook URL: https://yourdomain.com/backend/api/webhooks/shiprocket.php
 * 4. Select events: All shipment events
 * 5. Save
 * 
 * Shiprocket will send POST requests to this endpoint when shipment status changes
 */

header('Content-Type: application/json');

// Log all webhook requests for debugging
$logFile = __DIR__ . '/../../logs/shiprocket_webhook.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Get raw POST data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

// Log the webhook
$logMessage = date('Y-m-d H:i:s') . " - Webhook received:\n" . $rawData . "\n\n";
file_put_contents($logFile, $logMessage, FILE_APPEND);

// Validate webhook data
if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    exit;
}

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../services/ShiprocketAutomation.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Extract webhook data
    $awbCode = $data['awb'] ?? $data['awb_code'] ?? null;
    $shipmentStatus = $data['current_status'] ?? $data['shipment_status'] ?? null;
    $currentStatusDescription = $data['current_status_description'] ?? $data['status'] ?? null;
    $shiprocketOrderId = $data['order_id'] ?? null;
    $deliveredDate = $data['delivered_date'] ?? null;
    
    if (!$awbCode && !$shiprocketOrderId) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing AWB code or order ID']);
        exit;
    }
    
    // Find the order in our database
    $query = "SELECT id, order_number, status FROM orders WHERE ";
    $params = [];
    
    if ($awbCode) {
        $query .= "awb_code = ?";
        $params[] = $awbCode;
    } elseif ($shiprocketOrderId) {
        $query .= "shiprocket_order_id = ?";
        $params[] = $shiprocketOrderId;
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        // Order not found - this is OK, might be a test webhook
        echo json_encode(['status' => 'success', 'message' => 'Order not found in database']);
        exit;
    }
    
    // Map Shiprocket status to local status
    $automation = new ShiprocketAutomation();
    $reflection = new ReflectionClass($automation);
    $method = $reflection->getMethod('mapShiprocketStatus');
    $method->setAccessible(true);
    $localStatus = $method->invoke($automation, $shipmentStatus);
    
    // Update order status
    $updateQuery = "UPDATE orders 
        SET shipment_status = ?,
            current_status = ?,
            status = ?,
            tracking_updated_at = NOW()";
    
    $updateParams = [$shipmentStatus, $currentStatusDescription, $localStatus];
    
    // Add delivered_at if status is delivered
    if ($localStatus === 'delivered' && $deliveredDate) {
        $updateQuery .= ", delivered_at = ?";
        $updateParams[] = $deliveredDate;
    } elseif ($localStatus === 'delivered' && !$deliveredDate) {
        $updateQuery .= ", delivered_at = NOW()";
    }
    
    $updateQuery .= " WHERE id = ?";
    $updateParams[] = $order['id'];
    
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute($updateParams);
    
    // Store tracking history
    if ($awbCode) {
        $historyStmt = $db->prepare("INSERT INTO shipment_tracking_history 
            (order_id, awb_code, status, status_code, remarks, tracking_date) 
            VALUES (?, ?, ?, ?, ?, NOW())");
        $historyStmt->execute([
            $order['id'],
            $awbCode,
            $currentStatusDescription ?? $shipmentStatus,
            $shipmentStatus,
            json_encode($data)
        ]);
    }
    
    // Log success
    $logMessage = date('Y-m-d H:i:s') . " - Order #{$order['order_number']} updated: $shipmentStatus -> $localStatus\n\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Send success response to Shiprocket
    echo json_encode([
        'status' => 'success',
        'message' => 'Order status updated',
        'order_number' => $order['order_number'],
        'new_status' => $localStatus
    ]);
    
} catch (Exception $e) {
    // Log error
    $logMessage = date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}