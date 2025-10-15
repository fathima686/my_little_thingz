<?php
/**
 * Test Automatic Courier Assignment
 * This script tests the automatic courier assignment for orders that have shipments but no AWB codes
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/services/ShiprocketAutomation.php';

echo "=== Testing Automatic Courier Assignment ===\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Find orders with shipments but no AWB codes
    $query = "SELECT id, order_number, shiprocket_order_id, shiprocket_shipment_id, 
              shipping_address, awb_code, courier_name
              FROM orders 
              WHERE payment_status = 'paid' 
              AND shiprocket_shipment_id IS NOT NULL 
              AND (awb_code IS NULL OR awb_code = '')
              ORDER BY created_at DESC
              LIMIT 10";
    
    $stmt = $db->query($query);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orders)) {
        echo "✅ No orders found that need courier assignment.\n";
        echo "All paid orders with shipments already have AWB codes!\n";
        exit(0);
    }
    
    echo "Found " . count($orders) . " order(s) that need courier assignment:\n\n";
    
    foreach ($orders as $order) {
        echo "Order #{$order['order_number']}\n";
        echo "  Order ID: {$order['id']}\n";
        echo "  Shiprocket Order ID: {$order['shiprocket_order_id']}\n";
        echo "  Shiprocket Shipment ID: {$order['shiprocket_shipment_id']}\n";
        echo "  Current AWB: " . ($order['awb_code'] ?: 'None') . "\n";
        echo "  Current Courier: " . ($order['courier_name'] ?: 'None') . "\n";
        echo "\n";
    }
    
    echo "Do you want to attempt automatic courier assignment for these orders? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($line) !== 'yes') {
        echo "Cancelled by user.\n";
        exit(0);
    }
    
    echo "\n=== Starting Courier Assignment ===\n\n";
    
    $automation = new ShiprocketAutomation();
    $successCount = 0;
    $failCount = 0;
    
    foreach ($orders as $order) {
        echo "Processing Order #{$order['order_number']}...\n";
        
        // Use reflection to call private method
        $reflection = new ReflectionClass($automation);
        $method = $reflection->getMethod('assignCourier');
        $method->setAccessible(true);
        
        try {
            $result = $method->invoke($automation, $order['id']);
            
            if ($result['status'] === 'success') {
                echo "  ✅ SUCCESS!\n";
                echo "  Courier: {$result['courier_name']}\n";
                echo "  AWB Code: {$result['awb_code']}\n";
                echo "  Rate: ₹{$result['rate']}\n";
                $successCount++;
            } else {
                echo "  ❌ FAILED: {$result['message']}\n";
                $failCount++;
            }
        } catch (Exception $e) {
            echo "  ❌ ERROR: " . $e->getMessage() . "\n";
            $failCount++;
        }
        
        echo "\n";
    }
    
    echo "=== Summary ===\n";
    echo "Total Orders: " . count($orders) . "\n";
    echo "✅ Successful: $successCount\n";
    echo "❌ Failed: $failCount\n";
    
    if ($successCount > 0) {
        echo "\n✅ Courier assignment completed successfully for $successCount order(s)!\n";
        echo "Customers will now see AWB tracking codes on their dashboard.\n";
    }
    
    if ($failCount > 0) {
        echo "\n⚠️  $failCount order(s) failed courier assignment.\n";
        echo "Check the logs at: backend/logs/shiprocket_automation.log\n";
        echo "Common reasons:\n";
        echo "  - Courier not serviceable for delivery pincode\n";
        echo "  - Shiprocket account issues (wallet balance, KYC, etc.)\n";
        echo "  - Invalid address or pincode\n";
        echo "\nYou may need to assign couriers manually in Shiprocket dashboard.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>