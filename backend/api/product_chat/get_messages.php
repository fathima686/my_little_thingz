<?php
/**
 * Get Product Chat Messages API
 * Retrieves messages for a specific product customization discussion
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database connection
require_once '../../config/database.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Get parameters
    if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        exit;
    }
    
    $product_id = (int)$_GET['product_id'];
    $cart_item_id = isset($_GET['cart_item_id']) ? (int)$_GET['cart_item_id'] : null;
    $target_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    
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
    
    // Query param fallback
    if (!$user_id && $target_user_id) {
        $user_id = $target_user_id;
    }
    
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'User authentication required']);
        exit;
    }
    
    // Determine current user type (assume user for now, can be enhanced for admin detection)
    $current_user_type = 'user';
    $current_user_id = $user_id;
    
    // Build query based on parameters
    $where_conditions = ["pcm.product_id = ?", "pcm.user_id = ?"];
    $params = [$product_id, $user_id];
    
    if ($cart_item_id) {
        $where_conditions[] = "pcm.cart_item_id = ?";
        $params[] = $cart_item_id;
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    // Get messages for the product
    $stmt = $db->prepare("
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
            p.name as product_name,
            p.image as product_image
        FROM product_chat_messages pcm
        LEFT JOIN users u ON pcm.sender_type = 'user' AND pcm.sender_id = u.id
        LEFT JOIN products p ON pcm.product_id = p.id
        WHERE $where_clause
        ORDER BY pcm.created_at ASC
    ");
    
    $stmt->execute($params);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark messages as read for the current user
    $mark_read_stmt = $db->prepare("
        UPDATE product_chat_messages 
        SET is_read = TRUE 
        WHERE product_id = ? 
        AND user_id = ?
        AND sender_type != ? 
        AND is_read = FALSE
        " . ($cart_item_id ? "AND cart_item_id = ?" : "")
    );
    
    $mark_read_params = [$product_id, $user_id, $current_user_type];
    if ($cart_item_id) {
        $mark_read_params[] = $cart_item_id;
    }
    $mark_read_stmt->execute($mark_read_params);
    
    // Get unread count for the current user
    $unread_stmt = $db->prepare("
        SELECT COUNT(*) as unread_count
        FROM product_chat_messages 
        WHERE product_id = ? 
        AND user_id = ?
        AND sender_type != ? 
        AND is_read = FALSE
        " . ($cart_item_id ? "AND cart_item_id = ?" : "")
    );
    $unread_stmt->execute($mark_read_params);
    $unread_data = $unread_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Format timestamps and parse customization details
    foreach ($messages as &$message) {
        $message['formatted_time'] = date('M j, Y g:i A', strtotime($message['created_at']));
        $message['is_own_message'] = ($message['sender_type'] === $current_user_type && $message['sender_id'] == $current_user_id);
        
        // Parse customization details if present
        if ($message['customization_details']) {
            $message['customization_data'] = json_decode($message['customization_details'], true);
        } else {
            $message['customization_data'] = null;
        }
    }
    
    // Get product info (try both products and artworks tables)
    $product_info = null;
    try {
        $product_stmt = $db->prepare("SELECT id, name, image, price FROM products WHERE id = ?");
        $product_stmt->execute([$product_id]);
        $product_info = $product_stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Try artworks table if products doesn't exist
        try {
            $artwork_stmt = $db->prepare("SELECT id, title as name, image_url as image, price FROM artworks WHERE id = ?");
            $artwork_stmt->execute([$product_id]);
            $product_info = $artwork_stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e2) {
            // Default product info
            $product_info = ['id' => $product_id, 'name' => 'Product', 'image' => null, 'price' => 0];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'messages' => $messages,
            'unread_count' => (int)$unread_data['unread_count'],
            'current_user_type' => $current_user_type,
            'product_id' => $product_id,
            'cart_item_id' => $cart_item_id,
            'user_id' => $user_id,
            'product_info' => $product_info
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('Product Chat Get Messages Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('Product Chat Get Messages Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>