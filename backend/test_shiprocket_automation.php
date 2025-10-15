<?php
/**
 * Test Shiprocket Automation
 * Manually trigger automation for existing paid orders
 */

require_once 'config/database.php';
require_once 'services/ShiprocketAutomation.php';

try {
    $db = (new Database())->getConnection();
    
    echo "=== TESTING SHIPROCKET AUTOMATION ===\n\n";
    
    // Find paid orders without shipment
    $stmt = $db->query("SELECT id, order_number, user_id, status, payment_status, shiprocket_order_id 
                        FROM orders 
                        WHERE payment_status = 'paid' 
                        AND (shiprocket_order_id IS NULL OR shiprocket_order_id = '')
                        ORDER BY created_at DESC 
                        LIMIT 5");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orders)) {
        echo "No paid orders found without shipment.\n";
        echo "All orders are already processed!\n";
        exit(0);
    }
    
    echo "Found " . count($orders) . " paid order(s) without shipment:\n\n";
    
    foreach ($orders as $order) {
        echo "Processing Order #{$order['order_number']} (ID: {$order['id']})...\n";
        echo str_repeat("-", 80) . "\n";
        
        try {
            $automation = new ShiprocketAutomation();
            $result = $automation->processOrder($order['id'], $order['user_id']);
            
            echo "Status: " . $result['status'] . "\n";
            echo "Shipment Created: " . ($result['shipment_created'] ? 'YES' : 'NO') . "\n";
            echo "Courier Assigned: " . ($result['courier_assigned'] ? 'YES' : 'NO') . "\n";
            echo "Pickup Scheduled: " . ($result['pickup_scheduled'] ? 'YES' : 'NO') . "\n";
            
            if ($result['shipment_created']) {
                echo "Shiprocket Order ID: " . ($result['shiprocket_order_id'] ?? 'N/A') . "\n";
                echo "Shiprocket Shipment ID: " . ($result['shiprocket_shipment_id'] ?? 'N/A') . "\n";
            }
            
            if ($result['courier_assigned']) {
                echo "Courier: " . ($result['courier_name'] ?? 'N/A') . "\n";
                echo "AWB Code: " . ($result['awb_code'] ?? 'N/A') . "\n";
            }
            
            if (!empty($result['errors'])) {
                echo "\nErrors:\n";
                foreach ($result['errors'] as $error) {
                    echo "  - $error\n";
                }
            }
            
            if ($result['status'] === 'success' && $result['shipment_created']) {
                echo "\n✓ Order processed successfully!\n";
            } else {
                echo "\n⚠ Order processing incomplete or failed.\n";
            }
            
        } catch (Exception $e) {
            echo "❌ ERROR: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    echo "=== AUTOMATION TEST COMPLETED ===\n";
    
} catch (Exception $e) {
    echo "\n❌ FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}