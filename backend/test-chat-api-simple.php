<?php
// Simple test for chat API - Backend version
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'product_chat_messages'");
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        echo json_encode([
            'success' => false, 
            'message' => 'Database table not found. Please run setup first.',
            'setup_url' => '../setup-chat-tables.php'
        ]);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Test GET messages
        $product_id = $_GET['product_id'] ?? 1;
        $user_id = $_GET['user_id'] ?? 1;
        
        $stmt = $db->prepare("
            SELECT 
                id, product_id, user_id, sender_type, message_content, 
                created_at, 'Test User' as sender_name
            FROM product_chat_messages 
            WHERE product_id = ? AND user_id = ?
            ORDER BY created_at ASC
        ");
        $stmt->execute([$product_id, $user_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format messages
        foreach ($messages as &$message) {
            $message['formatted_time'] = date('M j, Y g:i A', strtotime($message['created_at']));
            $message['is_own_message'] = ($message['sender_type'] === 'user');
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'messages' => $messages,
                'unread_count' => 0,
                'current_user_type' => 'user',
                'product_id' => (int)$product_id,
                'user_id' => (int)$user_id,
                'product_info' => ['id' => $product_id, 'name' => 'Test Product', 'image' => null]
            ]
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Test POST message
        $input = json_decode(file_get_contents('php://input'), true);
        
        $product_id = $input['product_id'] ?? 1;
        $user_id = $input['user_id'] ?? 1;
        $message = $input['message'] ?? 'Test message';
        
        $stmt = $db->prepare("
            INSERT INTO product_chat_messages 
            (product_id, user_id, sender_type, sender_id, message_content, message_type) 
            VALUES (?, ?, 'user', ?, ?, 'text')
        ");
        
        $result = $stmt->execute([$product_id, $user_id, $user_id, $message]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => [
                    'id' => $db->lastInsertId(),
                    'product_id' => $product_id,
                    'user_id' => $user_id,
                    'message_content' => $message,
                    'sender_name' => 'Test User',
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send message']);
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage(),
        'error_details' => $e->getTraceAsString()
    ]);
}
?>