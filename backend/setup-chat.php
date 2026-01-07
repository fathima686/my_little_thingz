<?php
// Setup Product Chat Database Tables - Backend version
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
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
    
    // Insert a test message to verify everything works
    $test_message_sql = "
    INSERT IGNORE INTO product_chat_messages 
    (product_id, user_id, sender_type, sender_id, message_content, message_type) 
    VALUES (1, 1, 'user', 1, 'Welcome! Chat system is working perfectly! 🎉', 'text')
    ";
    
    $db->exec($test_message_sql);
    
    // Check if table was created successfully
    $check_sql = "SELECT COUNT(*) as count FROM product_chat_messages";
    $stmt = $db->prepare($check_sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Database setup complete!',
        'data' => [
            'table_created' => true,
            'message_count' => (int)$result['count'],
            'status' => 'Chat system is ready to use!'
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Setup failed: ' . $e->getMessage(),
        'error_details' => $e->getTraceAsString()
    ]);
}
?>