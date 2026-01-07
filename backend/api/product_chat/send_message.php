<?php
/**
 * Send Product Chat Message API
 * Handles sending messages for product customization discussions
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database connection
require_once '../../config/database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($input['product_id']) || !isset($input['message']) || empty(trim($input['message']))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Product ID and message are required']);
        exit;
    }
    
    $product_id = (int)$input['product_id'];
    $cart_item_id = isset($input['cart_item_id']) ? (int)$input['cart_item_id'] : null;
    $message_content = trim($input['message']);
    $message_type = $input['message_type'] ?? 'text';
    $customization_details = $input['customization_details'] ?? null;
    
    // Get user ID from headers (same as cart.php)
    $user_id = null;
    if (isset($_SERVER['HTTP_X_USER_ID']) && $_SERVER['HTTP_X_USER_ID'] !== '') {
        $user_id = (int)$_SERVER['HTTP_X_USER_ID'];
    }
    
    // Alternative server mappings
    if (!$user_id) {
        $altKeys = ['REDIRECT_HTTP_X_USER_ID', 'X_USER_ID', 'HTTP_X_USERID'];
        foreach ($altKeys as $k) {
            if (isset($_SERVER[$k]) && $_SERVER[$k] !== '') {
                $user_id = (int)$_SERVER[$k];
                break;
            }
        }
    }
    
    // getallheaders fallback
    if (!$user_id && function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $key => $value) {
            if (strtolower(trim($key)) === 'x-user-id' && $value !== '') {
                $user_id = (int)$value;
                break;
            }
        }
    }
    
    // Fallback to input user_id
    if (!$user_id && isset($input['user_id'])) {
        $user_id = (int)$input['user_id'];
    }
    
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'User authentication required']);
        exit;
    }
    
    // Determine sender type (assume user for now, can be enhanced for admin detection)
    $sender_type = 'user';
    $sender_id = $user_id;
    
    // Validate message length
    if (strlen($message_content) > 1000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message too long (max 1000 characters)']);
        exit;
    }
    
    // Validate product exists (try both products and artworks tables)
    $product = null;
    try {
        $product_check = $db->prepare("SELECT id, name FROM products WHERE id = ?");
        $product_check->execute([$product_id]);
        $product = $product_check->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Try artworks table if products doesn't exist
        try {
            $artwork_check = $db->prepare("SELECT id, title as name FROM artworks WHERE id = ?");
            $artwork_check->execute([$product_id]);
            $product = $artwork_check->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e2) {
            // Continue anyway - product validation is optional
        }
    }
    
    // Prepare customization details JSON
    $customization_json = null;
    if ($customization_details && is_array($customization_details)) {
        $customization_json = json_encode($customization_details);
    }
    
    // Insert the message
    $stmt = $db->prepare("
        INSERT INTO product_chat_messages 
        (product_id, cart_item_id, user_id, sender_type, sender_id, message_content, message_type, customization_details) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $product_id, 
        $cart_item_id, 
        $user_id, 
        $sender_type, 
        $sender_id, 
        $message_content, 
        $message_type, 
        $customization_json
    ]);
    
    if ($result) {
        $message_id = $db->lastInsertId();
        
        // Get the inserted message with sender info
        $get_message = $db->prepare("
            SELECT 
                pcm.id,
                pcm.product_id,
                pcm.cart_item_id,
                pcm.user_id,
                pcm.sender_type,
                pcm.sender_id,
                pcm.message_content,
                pcm.message_type,
                pcm.customization_details,
                pcm.is_read,
                pcm.created_at,
                CASE 
                    WHEN pcm.sender_type = 'admin' THEN 'Admin'
                    WHEN pcm.sender_type = 'user' THEN COALESCE(u.name, u.email, 'User')
                    ELSE 'Unknown'
                END as sender_name,
                COALESCE(p.name, a.title, 'Product') as product_name
            FROM product_chat_messages pcm
            LEFT JOIN users u ON pcm.sender_type = 'user' AND pcm.sender_id = u.id
            LEFT JOIN products p ON pcm.product_id = p.id
            LEFT JOIN artworks a ON pcm.product_id = a.id
            WHERE pcm.id = ?
        ");
        $get_message->execute([$message_id]);
        $message_data = $get_message->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => $message_data
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    }
    
} catch (PDOException $e) {
    error_log('Product Chat Send Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('Product Chat Send Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>