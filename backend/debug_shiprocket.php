<?php
/**
 * Debug Shiprocket API
 * Check what's actually being returned from Shiprocket
 */

require_once 'config/database.php';
require_once 'models/Shiprocket.php';
require_once 'services/ShiprocketAutomation.php';

try {
    $db = (new Database())->getConnection();
    
    echo "=== DEBUGGING SHIPROCKET API ===\n\n";
    
    // Get a paid order
    $stmt = $db->query("SELECT o.*, u.first_name, u.last_name, u.email 
                        FROM orders o 
                        JOIN users u ON o.user_id = u.id 
                        WHERE o.payment_status = 'paid' 
                        AND (o.shiprocket_order_id IS NULL OR o.shiprocket_order_id = '')
                        ORDER BY o.created_at DESC 
                        LIMIT 1");
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo "No paid orders found.\n";
        exit(0);
    }
    
    echo "Testing with Order #{$order['order_number']}\n";
    echo "Shipping Address:\n{$order['shipping_address']}\n\n";
    
    // Parse address using ShiprocketAutomation class
    $automation = new ShiprocketAutomation($db);
    
    // Use reflection to access private parseAddress method
    $reflection = new ReflectionClass($automation);
    $method = $reflection->getMethod('parseAddress');
    $method->setAccessible(true);
    $parsed = $method->invoke($automation, $order['shipping_address']);
    
    // Show parsed lines
    $lines = array_filter(array_map('trim', explode("\n", $order['shipping_address'])));
    echo "Parsed Address Lines:\n";
    foreach ($lines as $i => $line) {
        echo "  Line $i: $line\n";
    }
    echo "\n";
    
    echo "Extracted Data (using ShiprocketAutomation::parseAddress):\n";
    echo "  Phone: " . ($parsed['phone'] ?? 'NOT FOUND') . "\n";
    echo "  Pincode: " . ($parsed['pincode'] ?? 'NOT FOUND') . "\n";
    echo "  City: " . ($parsed['city'] ?? 'NOT FOUND') . "\n";
    echo "  State: " . ($parsed['state'] ?? 'NOT FOUND') . "\n";
    echo "  Address: " . ($parsed['address'] ?? 'NOT FOUND') . "\n";
    echo "  Valid: " . ($parsed['valid'] ? 'YES' : 'NO') . "\n\n";
    
    if (!$parsed['valid']) {
        echo "❌ ERROR: Address parsing failed!\n";
        echo "The shipping address format is invalid.\n";
        echo "Required format:\n";
        echo "  Line 1: Customer Name\n";
        echo "  Line 2: Street address\n";
        echo "  Line 3: Locality/Area\n";
        echo "  Line 4: City, State, Pincode\n";
        echo "  Line 5: India\n";
        echo "  Line 6: Phone: 1234567890\n";
        exit(1);
    }
    
    // Use parsed data
    $phone = $parsed['phone'];
    $pincode = $parsed['pincode'];
    $city = $parsed['city'];
    $state = $parsed['state'];
    $address = $parsed['address'];
    
    // Get order items
    $itemsStmt = $db->prepare("SELECT oi.*, a.title 
                               FROM order_items oi 
                               JOIN artworks a ON oi.artwork_id = a.id 
                               WHERE oi.order_id = ?");
    $itemsStmt->execute([$order['id']]);
    $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Order Items:\n";
    foreach ($orderItems as $item) {
        echo "  - {$item['title']} x {$item['quantity']} @ ₹{$item['price']}\n";
    }
    echo "\n";
    
    // Prepare Shiprocket data
    $customerName = trim($order['first_name'] . ' ' . $order['last_name']);
    if (!$customerName) $customerName = 'Customer';
    
    $shiprocketData = [
        'order_id' => $order['order_number'],
        'order_date' => date('Y-m-d H:i', strtotime($order['created_at'])),
        'pickup_location' => 'Purathel',
        'billing_customer_name' => $customerName,
        'billing_last_name' => '',
        'billing_address' => $address,
        'billing_city' => $city,
        'billing_pincode' => $pincode,
        'billing_state' => $state,
        'billing_country' => 'India',
        'billing_email' => $order['email'],
        'billing_phone' => $phone,
        'shipping_is_billing' => true,
        'order_items' => [],
        'payment_method' => 'Prepaid',
        'sub_total' => $order['subtotal'] ?? $order['total_amount'],
        'length' => 10,
        'breadth' => 10,
        'height' => 10,
        'weight' => 0.5
    ];
    
    foreach ($orderItems as $item) {
        $shiprocketData['order_items'][] = [
            'name' => $item['title'] ?? 'Artwork',
            'sku' => 'ART-' . $item['artwork_id'],
            'units' => $item['quantity'],
            'selling_price' => $item['price'],
            'discount' => 0,
            'tax' => 0,
            'hsn' => 442110
        ];
    }
    
    echo "Shiprocket Request Data:\n";
    echo json_encode($shiprocketData, JSON_PRETTY_PRINT) . "\n\n";
    
    // Try to create order
    echo "Attempting to create order in Shiprocket...\n";
    $shiprocket = new Shiprocket();
    
    try {
        $response = $shiprocket->createOrder($shiprocketData);
        echo "\nShiprocket Response:\n";
        echo json_encode($response, JSON_PRETTY_PRINT) . "\n";
        
        if (isset($response['order_id']) && isset($response['shipment_id'])) {
            echo "\n✓ SUCCESS! Order created in Shiprocket.\n";
            echo "Order ID: {$response['order_id']}\n";
            echo "Shipment ID: {$response['shipment_id']}\n";
        } else {
            echo "\n❌ FAILED! Response doesn't contain order_id and shipment_id.\n";
        }
    } catch (Exception $e) {
        echo "\n❌ EXCEPTION: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}