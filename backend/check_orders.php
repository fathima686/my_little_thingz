<?php
require_once 'config/database.php';

try {
    $db = (new Database())->getConnection();
    
    echo "=== ORDERS TABLE STRUCTURE ===\n";
    $cols = $db->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\n=== RECENT ORDERS ===\n";
    $orders = $db->query("SELECT id, order_number, status, payment_status, shiprocket_order_id, shiprocket_shipment_id, awb_code, courier_name, created_at FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orders)) {
        echo "No orders found in database.\n";
    } else {
        foreach ($orders as $order) {
            echo "\nOrder #{$order['order_number']}\n";
            echo "  Status: {$order['status']}\n";
            echo "  Payment: {$order['payment_status']}\n";
            echo "  Shiprocket Order ID: {$order['shiprocket_order_id']}\n";
            echo "  Shiprocket Shipment ID: {$order['shiprocket_shipment_id']}\n";
            echo "  AWB: {$order['awb_code']}\n";
            echo "  Courier: {$order['courier_name']}\n";
            echo "  Created: {$order['created_at']}\n";
        }
    }
    
    echo "\n=== SHIPMENT TRACKING HISTORY TABLE ===\n";
    $trackingExists = $db->query("SHOW TABLES LIKE 'shipment_tracking_history'")->fetch();
    if ($trackingExists) {
        echo "Table exists\n";
        $trackingCols = $db->query("SHOW COLUMNS FROM shipment_tracking_history")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($trackingCols as $col) {
            echo "  " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    } else {
        echo "Table does NOT exist - needs to be created!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}