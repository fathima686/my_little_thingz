<?php
// Setup Product Chat Database Tables
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>🚀 Setting up Product Chat Database...</h2>";
    
    // Create product_chat_messages table
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS product_chat_messages (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        product_id INT UNSIGNED NOT NULL,
        cart_item_id INT UNSIGNED NULL,
        user_id INT UNSIGNED NOT NULL,
        sender_type ENUM('admin', 'user') NOT NULL,
        sender_id INT UNSIGNED NOT NULL,
        message_content TEXT NOT NULL,
        message_type ENUM('text', 'image', 'customization_request') DEFAULT 'text',
        customization_details JSON NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_product_id (product_id),
        INDEX idx_cart_item_id (cart_item_id),
        INDEX idx_user_id (user_id),
        INDEX idx_sender (sender_type, sender_id),
        INDEX idx_created_at (created_at),
        INDEX idx_read_status (is_read),
        INDEX idx_product_messages (product_id, user_id, created_at),
        INDEX idx_cart_messages (cart_item_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $db->exec($create_table_sql);
    echo "✅ Created product_chat_messages table<br>";
    
    // Insert a test message to verify everything works
    $test_message_sql = "
    INSERT IGNORE INTO product_chat_messages 
    (product_id, user_id, sender_type, sender_id, message_content, message_type) 
    VALUES (1, 1, 'user', 1, 'Test message - chat system is working!', 'text')
    ";
    
    $db->exec($test_message_sql);
    echo "✅ Added test message<br>";
    
    // Check if table was created successfully
    $check_sql = "SELECT COUNT(*) as count FROM product_chat_messages";
    $stmt = $db->prepare($check_sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<br><strong>🎉 Database setup complete!</strong><br>";
    echo "📊 Messages in database: {$result['count']}<br>";
    echo "<p>✅ Product chat system is ready to use!</p>";
    
    echo "<br><h3>🧪 Test the System:</h3>";
    echo "<p><a href='debug-chat-api.php'>Debug Chat API</a></p>";
    echo "<p><a href='test-product-chat.html'>Test Chat Interface</a></p>";
    
} catch (Exception $e) {
    echo "<strong>❌ Error:</strong> " . $e->getMessage() . "<br>";
    echo "<p>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h2, h3 { color: #333; }
a { color: #007cba; text-decoration: none; }
a:hover { text-decoration: underline; }
pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>