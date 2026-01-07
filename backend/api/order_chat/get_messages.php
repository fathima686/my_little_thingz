<?php
/**
 * Get Messages API
 * Retrieves messages for a specific order chat thread
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Start session to get user/admin info
session_start();

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
    
    // Get order ID from query parameters
    if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order ID is required']);
        exit;
    }
    
    $order_id = (int)$_GET['order_id'];
    
    // Determine current user type and ID from session
    $current_user_type = null;
    $current_user_id = null;
    
    if (isset($_SESSION['admin_id'])) {
        $current_user_type = 'admin';
        $current_user_id = (int)$_SESSION['admin_id'];
    } elseif (isset($_SESSION['user_id'])) {
        $current_user_type = 'user';
        $current_user_id = (int)$_SESSION['user_id'];
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
    
    // For users, verify they own the order
    if ($current_user_type === 'user') {
        $order_check = $db->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
        $order_check->execute([$order_id, $current_user_id]);
        
        if (!$order_check->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied to this order']);
            exit;
        }
    }
    
    // Get messages for the order
    $stmt = $db->prepare("
        SELECT 
            om.id,
            om.order_id,
            om.sender_type,
            om.sender_id,
            om.message_content,
            om.is_read,
            om.created_at,
            CASE 
                WHEN om.sender_type = 'admin' THEN 'Admin'
                WHEN om.sender_type = 'user' THEN COALESCE(u.name, u.email, 'User')
                ELSE 'Unknown'
            END as sender_name
        FROM order_messages om
        LEFT JOIN users u ON om.sender_type = 'user' AND om.sender_id = u.id
        WHERE om.order_id = ?
        ORDER BY om.created_at ASC
    ");
    
    $stmt->execute([$order_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark messages as read for the current user
    $mark_read_stmt = $db->prepare("
        UPDATE order_messages 
        SET is_read = TRUE 
        WHERE order_id = ? 
        AND sender_type != ? 
        AND is_read = FALSE
    ");
    $mark_read_stmt->execute([$order_id, $current_user_type]);
    
    // Get unread count for the current user
    $unread_stmt = $db->prepare("
        SELECT COUNT(*) as unread_count
        FROM order_messages 
        WHERE order_id = ? 
        AND sender_type != ? 
        AND is_read = FALSE
    ");
    $unread_stmt->execute([$order_id, $current_user_type]);
    $unread_data = $unread_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Format timestamps for display
    foreach ($messages as &$message) {
        $message['formatted_time'] = date('M j, Y g:i A', strtotime($message['created_at']));
        $message['is_own_message'] = ($message['sender_type'] === $current_user_type && $message['sender_id'] == $current_user_id);
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'messages' => $messages,
            'unread_count' => (int)$unread_data['unread_count'],
            'current_user_type' => $current_user_type,
            'order_id' => $order_id
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('Order Chat Get Messages Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('Order Chat Get Messages Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>