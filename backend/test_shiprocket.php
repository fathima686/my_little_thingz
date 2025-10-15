<?php
/**
 * Shiprocket Connection Test Script
 * 
 * This script tests the Shiprocket API connection and displays
 * basic information about your account.
 */

require_once 'models/Shiprocket.php';
require_once 'config/warehouse.php';

header('Content-Type: application/json');

try {
    echo "Testing Shiprocket Connection...\n\n";
    
    $shiprocket = new Shiprocket();
    
    // Test 1: Get Pickup Locations
    echo "Test 1: Getting Pickup Locations\n";
    echo "=================================\n";
    $pickupLocations = $shiprocket->getPickupLocations();
    
    if (isset($pickupLocations['data'])) {
        echo "✓ Successfully retrieved pickup locations\n";
        
        if (isset($pickupLocations['data']['shipping_address']) && is_array($pickupLocations['data']['shipping_address'])) {
            echo "Number of locations: " . count($pickupLocations['data']['shipping_address']) . "\n\n";
            
            foreach ($pickupLocations['data']['shipping_address'] as $location) {
                echo "Location: " . ($location['pickup_location'] ?? 'N/A') . "\n";
                echo "Address: " . ($location['address'] ?? 'N/A') . "\n";
                echo "City: " . ($location['city'] ?? 'N/A') . "\n";
                echo "Pincode: " . ($location['pin_code'] ?? 'N/A') . "\n";
                echo "---\n";
            }
        } else {
            echo "No pickup locations found or unexpected response format\n";
            echo "Response: " . json_encode($pickupLocations, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "✗ Failed to retrieve pickup locations\n";
        echo "Response: " . json_encode($pickupLocations, JSON_PRETTY_PRINT) . "\n";
    }
    
    echo "\n";
    
    // Test 2: Check Courier Serviceability
    echo "Test 2: Checking Courier Serviceability\n";
    echo "========================================\n";
    
    $warehouseConfig = require 'config/warehouse.php';
    $pickup_pincode = $warehouseConfig['address_fields']['pincode'];
    
    // Test with a sample delivery pincode (Mumbai)
    $test_delivery_pincode = '400001';
    
    echo "Pickup Pincode: $pickup_pincode\n";
    echo "Delivery Pincode: $test_delivery_pincode (Mumbai - Test)\n";
    echo "Weight: 0.5 kg\n";
    echo "Payment: Prepaid\n\n";
    
    $serviceability = $shiprocket->getCourierServiceability([
        'pickup_postcode' => $pickup_pincode,
        'delivery_postcode' => $test_delivery_pincode,
        'weight' => 0.5,
        'cod' => 0
    ]);
    
    if (isset($serviceability['data']['available_courier_companies'])) {
        $couriers = $serviceability['data']['available_courier_companies'];
        echo "✓ Found " . count($couriers) . " available couriers\n\n";
        
        echo "Top 5 Couriers:\n";
        echo "---------------\n";
        $count = 0;
        foreach ($couriers as $courier) {
            if ($count >= 5) break;
            echo ($count + 1) . ". " . ($courier['courier_name'] ?? 'N/A') . "\n";
            echo "   Rate: ₹" . ($courier['rate'] ?? 'N/A') . "\n";
            echo "   Delivery: " . ($courier['estimated_delivery_days'] ?? 'N/A') . " days\n";
            echo "   Rating: " . ($courier['rating'] ?? 'N/A') . "/5\n";
            echo "---\n";
            $count++;
        }
    } else {
        echo "✗ No couriers available or error occurred\n";
        echo "Response: " . json_encode($serviceability, JSON_PRETTY_PRINT) . "\n";
    }
    
    echo "\n";
    
    // Test 3: Get Recent Orders (if any)
    echo "Test 3: Getting Recent Orders from Shiprocket\n";
    echo "=============================================\n";
    
    $orders = $shiprocket->getOrders(['per_page' => 5]);
    
    if (isset($orders['data'])) {
        echo "✓ Successfully retrieved orders\n";
        echo "Total orders: " . ($orders['meta']['pagination']['total'] ?? 0) . "\n";
        
        if (!empty($orders['data'])) {
            echo "\nRecent Orders:\n";
            echo "--------------\n";
            foreach ($orders['data'] as $order) {
                echo "Order ID: " . ($order['id'] ?? 'N/A') . "\n";
                echo "Order Number: " . ($order['channel_order_id'] ?? 'N/A') . "\n";
                echo "Status: " . ($order['status'] ?? 'N/A') . "\n";
                echo "Created: " . ($order['created_at'] ?? 'N/A') . "\n";
                echo "---\n";
            }
        } else {
            echo "No orders found in Shiprocket account.\n";
        }
    } else {
        echo "✗ Failed to retrieve orders\n";
        echo "Response: " . json_encode($orders, JSON_PRETTY_PRINT) . "\n";
    }
    
    echo "\n";
    echo "=================================\n";
    echo "All tests completed!\n";
    echo "=================================\n\n";
    
    echo "Configuration Summary:\n";
    echo "---------------------\n";
    $config = require 'config/shiprocket.php';
    echo "Company ID: " . $config['company_id'] . "\n";
    echo "Email: " . $config['email'] . "\n";
    echo "Base URL: " . $config['base_url'] . "\n";
    echo "Token Status: " . (strlen($config['token']) > 0 ? 'Configured' : 'Not Configured') . "\n";
    
    echo "\nWarehouse Address:\n";
    echo "-----------------\n";
    echo "Name: " . $warehouseConfig['address_fields']['name'] . "\n";
    echo "Address: " . $warehouseConfig['address_fields']['address_line1'] . "\n";
    echo "City: " . $warehouseConfig['address_fields']['city'] . "\n";
    echo "State: " . $warehouseConfig['address_fields']['state'] . "\n";
    echo "Pincode: " . $warehouseConfig['address_fields']['pincode'] . "\n";
    echo "Phone: " . $warehouseConfig['address_fields']['phone'] . "\n";
    
    echo "\n✓ Shiprocket integration is working correctly!\n";
    
} catch (Exception $e) {
    echo "\n✗ Error occurred:\n";
    echo $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
?>