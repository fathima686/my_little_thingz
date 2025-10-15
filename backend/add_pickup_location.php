<?php
/**
 * Add a pickup location to Shiprocket
 * This needs to be done once before creating shipments
 */

require_once 'config/database.php';
require_once 'models/Shiprocket.php';

try {
    echo "=== ADDING PICKUP LOCATION TO SHIPROCKET ===\n\n";
    
    // Pickup location details - MODIFY THESE AS NEEDED
    $pickupData = [
        'pickup_location' => 'Purathel',  // Name to identify this location
        'name' => 'Fathima Shibu',        // Contact person name
        'email' => 'fathimashibu15@gmail.com',
        'phone' => '9495470077',
        'address' => 'House No. 123, Purathel House',
        'address_2' => 'Kottayam Road, Near St. Mary\'s Church',
        'city' => 'Kottayam',
        'state' => 'Kerala',
        'country' => 'India',
        'pin_code' => '686508'
    ];
    
    echo "Pickup Location Details:\n";
    echo json_encode($pickupData, JSON_PRETTY_PRINT) . "\n\n";
    
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
    
    // Add pickup location
    $url = $baseUrl . '/settings/company/addpickup';
    $response = $makeRequestMethod->invoke($shiprocket, 'POST', $url, $pickupData);
    
    if (isset($response['success']) && $response['success'] === true) {
        echo "✅ SUCCESS! Pickup location added successfully!\n\n";
        echo "Response:\n";
        echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
        echo "You can now create shipments using pickup_location: '{$pickupData['pickup_location']}'\n";
    } else {
        echo "❌ ERROR: Failed to add pickup location\n\n";
        echo "Response:\n";
        echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
        
        if (isset($response['message'])) {
            echo "Message: " . $response['message'] . "\n";
        }
        if (isset($response['errors'])) {
            echo "Errors:\n";
            foreach ($response['errors'] as $field => $errors) {
                echo "  - $field: " . implode(', ', (array)$errors) . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}