<?php
/**
 * Send Message API
 * Handles sending messages in order-specific chat threads
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Start session to get user/admin info
session_start();

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
    if (!isset($input['order_id']) || !isset($input['message']) || empty(trim($input['message']))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order ID and message are required']);
        exit;
    }
    
    $order_id = (int)$input['order_id'];
    $message_content = trim($input['message']);
    
    // Determine sender type and ID from session
    $sender_type = null;
    $sender_id = null;
    
    if (isset($_SESSION['admin_id'])) {
        $sender_type = 'admin';
        $sender_id = (int)$_SESSION['admin_id'];
    } elseif (isset($_SESSION['user_id'])) {
        $sender_type = 'user';
        $sender_id = (int)$_SESSION['user_id'];
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
    
    // Validate message length
    if (strlen($message_content) > 1000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message too long (max 1000 characters)']);
        exit;
    }
    
    // For users, verify they own the order
    if ($sender_type === 'user') {
        $order_check = $db->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
        $order_check->execute([$order_id, $sender_id]);
        
        if (!$order_check->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied to this order']);
            exit;
        }
    }
    
    // Insert the message
    $stmt = $db->prepare("
        INSERT INTO order_messages (order_id, sender_type, sender_id, message_content) 
        VALUES (?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([$order_id, $sender_type, $sender_id, $message_content]);
    
    if ($result) {
        $message_id = $db->lastInsertId();
        
        // Get the inserted message with sender info
        $get_message = $db->prepare("
            SELECT 
                id,
                order_id,
                sender_type,
                sender_id,
                message_content,
                is_read,
                created_at,
                CASE 
                    WHEN sender_type = 'admin' THEN 'Admin'
                    WHEN sender_type = 'user' THEN COALESCE(u.name, u.email, 'User')
                    ELSE 'Unknown'
                END as sender_name
            FROM order_messages om
            LEFT JOIN users u ON om.sender_type = 'user' AND om.sender_id = u.id
            WHERE om.id = ?
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
    error_log('Order Chat Send Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('Order Chat Send Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>