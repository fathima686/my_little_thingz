<?php
/**
 * Example of how to integrate the Order Chat into your existing order details page
 */

// Start session (assuming you already have this)
session_start();

// Include your database connection (assuming you already have this)
// require_once 'config/database.php';

// Get order ID from URL or form (assuming you already have this logic)
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Verify user has access to this order (assuming you already have this logic)
// This is just an example - adapt to your existing authentication system
$user_can_access_order = true; // Your existing logic here

if (!$user_can_access_order) {
    header('Location: /orders');
    exit;
}

// Your existing order details logic here...
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Custom Gifts</title>
    
    <!-- Your existing CSS files -->
    <link rel="stylesheet" href="css/main.css">
    
    <!-- Add the chat CSS -->
    <link rel="stylesheet" href="frontend/css/order-chat.css">
</head>
<body>
    <!-- Your existing page structure -->
    <div class="container">
        <div class="order-details">
            <h1>Order #<?php echo htmlspecialchars($order_id); ?></h1>
            
            <!-- Your existing order details content -->
            <div class="order-items">
                <!-- Order items display -->
            </div>
            
            <!-- Chat Integration - Add this div where you want the chat to appear -->
            <div id="order-chat"></div>
            
            <!-- Rest of your order details -->
        </div>
    </div>
    
    <!-- Your existing JavaScript files -->
    <script src="js/main.js"></script>
    
    <!-- Add the chat JavaScript -->
    <script src="frontend/js/order-chat.js"></script>
    
    <!-- Initialize the chat with the current order ID -->
    <script>
        // Initialize chat for this order
        initOrderChat(<?php echo $order_id; ?>, 'order-chat');
    </script>
</body>
</html>