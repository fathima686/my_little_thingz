<?php
/**
 * Create a fresh delivered order for testing unboxing video verification
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Creating fresh delivered order for testing...\n\n";
    
    // Find a customer
    $customerStmt = $pdo->query("SELECT id, email FROM users LIMIT 1");
    $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        echo "No users found. Creating a test customer...\n";
        
        // Create a test customer
        $insertCustomer = $pdo->prepare("
            INSERT INTO users (first_name, last_name, email, password_hash) 
            VALUES (?, ?, ?, ?)
        ");
        $insertCustomer->execute([
            'Test',
            'Customer',
            'testcustomer@example.com',
            password_hash('password123', PASSWORD_DEFAULT)
        ]);
        
        $customerId = $pdo->lastInsertId();
        $customerEmail = 'testcustomer@example.com';
        echo "✓ Created test customer: $customerEmail (ID: $customerId)\n";
    } else {
        $customerId = $customer['id'];
        $customerEmail = $customer['email'];
        echo "✓ Using existing customer: $customerEmail (ID: $customerId)\n";
    }
    
    // Create a fresh delivered order (delivered just now)
    $orderNumber = 'ORD' . date('Ymd') . rand(1000, 9999);
    $deliveredAt = date('Y-m-d H:i:s'); // Delivered right now
    
    $insertOrder = $pdo->prepare("
        INSERT INTO orders (
            user_id, order_number, total_amount, status, 
            delivered_at, allows_unboxing_request, created_at
        ) VALUES (?, ?, ?, 'delivered', ?, 1, NOW())
    ");
    
    $insertOrder->execute([
        $customerId,
        $orderNumber,
        399.99,
        $deliveredAt
    ]);
    
    $orderId = $pdo->lastInsertId();
    
    echo "✓ Created fresh delivered order:\n";
    echo "   Order ID: $orderId\n";
    echo "   Order Number: $orderNumber\n";
    echo "   Customer: $customerEmail\n";
    echo "   Status: delivered\n";
    echo "   Delivered At: $deliveredAt (just now)\n";
    echo "   Allows Unboxing Request: Yes\n";
    echo "   Total Amount: ₹399.99\n";
    echo "   Time Remaining: 48 hours\n\n";
    
    // Also create some sample order items for better display
    try {
        $insertItem = $pdo->prepare("
            INSERT INTO order_items (order_id, product_name, quantity, price) 
            VALUES (?, ?, ?, ?)
        ");
        
        $insertItem->execute([$orderId, 'Premium Gift Box', 1, 199.99]);
        $insertItem->execute([$orderId, 'Handcrafted Frame', 1, 199.99]);
        
        echo "✓ Added sample order items\n\n";
    } catch (Exception $e) {
        echo "⚠ Note: Could not add order items (table may not exist)\n\n";
    }
    
    echo "=== TESTING INSTRUCTIONS ===\n";
    echo "1. Login as customer: $customerEmail / password123\n";
    echo "2. Navigate to Order Tracking\n";
    echo "3. Find order #$orderNumber (should show as DELIVERED)\n";
    echo "4. Look for 'Unboxing Video Verification' section\n";
    echo "5. Click 'Show' to expand the section\n";
    echo "6. Click 'Report Issue with This Order'\n";
    echo "7. Fill out the form and upload a video\n";
    echo "8. Submit the request\n";
    echo "9. Login as admin to review in Admin Dashboard > Unboxing Review\n\n";
    
    echo "Order Details for Testing:\n";
    echo "- Customer Email: $customerEmail\n";
    echo "- Customer Password: password123\n";
    echo "- Order Number: $orderNumber\n";
    echo "- Status: DELIVERED (just now)\n";
    echo "- Upload Window: 48 hours remaining\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>