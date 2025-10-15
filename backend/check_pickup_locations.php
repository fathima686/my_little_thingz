<?php
/**
 * Check available pickup locations in Shiprocket
 */

require_once 'config/database.php';
require_once 'models/Shiprocket.php';

try {
    echo "=== CHECKING SHIPROCKET PICKUP LOCATIONS ===\n\n";
    
    $shiprocket = new Shiprocket();
    
    // Use reflection to access private methods
    $reflection = new ReflectionClass($shiprocket);
    $authMethod = $reflection->getMethod('authenticate');
    $authMethod->setAccessible(true);
    $authMethod->invoke($shiprocket);
    
    $makeRequestMethod = $reflection->getMethod('makeRequest');
    $makeRequestMethod->setAccessible(true);
    
    // Get base URL
    $baseUrlProperty = $reflection->getProperty('baseUrl');
    $baseUrlProperty->setAccessible(true);
    $baseUrl = $baseUrlProperty->getValue($shiprocket);
    
    // Fetch pickup locations
    $url = $baseUrl . '/settings/company/pickup';
    $response = $makeRequestMethod->invoke($shiprocket, 'GET', $url);
    
    if (isset($response['data']) && isset($response['data']['shipping_address'])) {
        $locations = $response['data']['shipping_address'];
        
        echo "Found " . count($locations) . " pickup location(s):\n\n";
        
        foreach ($locations as $location) {
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "Pickup Location Name: " . ($location['pickup_location'] ?? 'N/A') . "\n";
            echo "Address: " . ($location['address'] ?? 'N/A') . "\n";
            echo "City: " . ($location['city'] ?? 'N/A') . "\n";
            echo "State: " . ($location['state'] ?? 'N/A') . "\n";
            echo "Pincode: " . ($location['pin_code'] ?? 'N/A') . "\n";
            echo "Phone: " . ($location['phone'] ?? 'N/A') . "\n";
            echo "Email: " . ($location['email'] ?? 'N/A') . "\n";
            echo "Status: " . ($location['status'] ?? 'N/A') . "\n";
            echo "\n";
        }
        
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        echo "✅ Use one of these pickup location names in your config:\n";
        echo "   File: backend/config/shiprocket_automation.php\n";
        echo "   Key: 'pickup_location'\n\n";
        
    } else {
        echo "❌ ERROR: Could not fetch pickup locations\n";
        echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}