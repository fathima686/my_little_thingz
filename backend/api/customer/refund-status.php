<?php
/**
 * CUSTOMER REFUND STATUS API
 * Allows customers to check the status of their refund requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config/database.php';

try {
    // Customer authentication
    $userId = $_SERVER['HTTP_X_USER_ID'] ?? null;
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'User authentication required']);
        exit;
    }
    
    $userId = (int)$userId;
    if ($userId <= 0) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }
    
    $orderId = $_GET['order_id'] ?? null;
    
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
        exit;
    }
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get refund request for this order and customer
    $stmt = $pdo->prepare("
        SELECT ur.*, o.order_number, o.total_amount as order_amount
        FROM unboxing_requests ur
        JOIN orders o ON ur.order_id = o.id
        WHERE ur.order_id = ? AND ur.customer_id = ?
    ");
    $stmt->execute([$orderId, $userId]);
    $refundRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($refundRequest) {
        // Get refund history for this request
        $historyStmt = $pdo->prepare("
            SELECT h.*, u.first_name, u.last_name
            FROM unboxing_request_history h
            LEFT JOIN users u ON h.changed_by_user_id = u.id
            WHERE h.request_id = ?
            ORDER BY h.created_at ASC
        ");
        $historyStmt->execute([$refundRequest['id']]);
        $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'refund_request' => $refundRequest,
            'history' => $history
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'refund_request' => null,
            'message' => 'No refund request found for this order'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('Refund status API error: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error occurred'
    ]);
}
?>