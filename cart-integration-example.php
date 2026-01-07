<?php
/**
 * Example of how to integrate Product Chat into your cart/product pages
 */

// Start session (assuming you already have this)
session_start();

// Include your database connection (assuming you already have this)
// require_once 'config/database.php';

// Get user ID from session (assuming you already have this logic)
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Get cart items (assuming you already have this logic)
$cart_items = [
    [
        'id' => 101,
        'product_id' => 1,
        'name' => 'Custom Photo Frame',
        'description' => 'Wooden frame with personalized engraving',
        'price' => 29.99,
        'image' => 'frame.jpg'
    ],
    [
        'id' => 102,
        'product_id' => 2,
        'name' => 'Personalized Mug',
        'description' => 'Ceramic mug with custom text and design',
        'price' => 15.99,
        'image' => 'mug.jpg'
    ]
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Custom Gifts</title>
    
    <!-- Your existing CSS files -->
    <link rel="stylesheet" href="css/main.css">
    
    <!-- Add the product chat CSS -->
    <link rel="stylesheet" href="frontend/css/order-chat.css">
</head>
<body>
    <!-- Your existing page structure -->
    <div class="container">
        <div class="cart-header">
            <h1>Shopping Cart</h1>
            <p>Review your items and customize as needed</p>
        </div>
        
        <div class="cart-content">
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                <div class="cart-item">
                    <div class="item-image">
                        <img src="images/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    </div>
                    <div class="item-details">
                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p><?php echo htmlspecialchars($item['description']); ?></p>
                        <button class="customize-btn" onclick="showCustomizationChat(<?php echo $item['product_id']; ?>, <?php echo $item['id']; ?>)">
                            💬 Customize This Item
                        </button>
                    </div>
                    <div class="item-price">$<?php echo number_format($item['price'], 2); ?></div>
                </div>
                <?php endforeach; ?>
                
                <!-- Chat Section - This will be populated by JavaScript -->
                <div class="chat-section">
                    <div id="product-chat"></div>
                </div>
            </div>
            
            <!-- Your existing cart sidebar -->
            <div class="cart-sidebar">
                <!-- Order summary, checkout button, etc. -->
            </div>
        </div>
    </div>
    
    <!-- Your existing JavaScript files -->
    <script src="js/main.js"></script>
    
    <!-- Add the product chat JavaScript -->
    <script src="frontend/js/order-chat.js"></script>
    
    <!-- Initialize product chat functionality -->
    <script>
        let currentChat = null;
        const currentUserId = <?php echo $user_id; ?>;
        
        function showCustomizationChat(productId, cartItemId) {
            // Destroy existing chat if any
            if (currentChat) {
                currentChat.destroy();
            }
            
            // Initialize new chat for the selected product
            // Pass user ID for proper authentication
            currentChat = new ProductChat(productId, currentUserId, cartItemId, 'product-chat');
            
            // Scroll to chat section
            document.getElementById('product-chat').scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        }
        
        // Optional: Show a welcome message
        function showWelcomeMessage() {
            const chatContainer = document.getElementById('product-chat');
            if (chatContainer && !currentChat) {
                chatContainer.innerHTML = `
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; text-align: center; color: #6c757d;">
                        <h4 style="margin: 0 0 8px 0; color: #2c3e50;">Need Customization Help?</h4>
                        <p style="margin: 0;">Click "Customize This Item" on any product to start a conversation with our team!</p>
                    </div>
                `;
            }
        }
        
        // Show welcome message when page loads
        document.addEventListener('DOMContentLoaded', showWelcomeMessage);
    </script>
</body>
</html>