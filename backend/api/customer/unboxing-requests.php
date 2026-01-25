<?php
/**
 * UNBOXING VIDEO VERIFICATION API
 * Academic Project - Customer Module
 * 
 * Handles customer unboxing video uploads and refund/replacement requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-User-ID, X-Admin-User-Id, X-Admin-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get customer ID from headers
    $customerId = $_SERVER['HTTP_X_USER_ID'] ?? null;
    
    if (!$customerId) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get customer's unboxing requests
        $stmt = $pdo->prepare("
            SELECT ur.*, o.order_number, o.status as order_status, o.delivered_at,
                   TIMESTAMPDIFF(HOUR, o.delivered_at, NOW()) as hours_since_delivery
            FROM unboxing_requests ur
            JOIN orders o ON ur.order_id = o.id
            WHERE ur.customer_id = ?
            ORDER BY ur.created_at DESC
        ");
        $stmt->execute([$customerId]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'requests' => $requests
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle new unboxing request submission
        
        // Validate required fields
        $orderId = $_POST['order_id'] ?? null;
        $issueType = $_POST['issue_type'] ?? null;
        $requestType = $_POST['request_type'] ?? null;
        $description = $_POST['description'] ?? '';
        
        if (!$orderId || !$issueType || !$requestType) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
            exit;
        }
        
        // Validate order belongs to customer and is delivered
        $orderStmt = $pdo->prepare("
            SELECT id, order_number, status, delivered_at, allows_unboxing_request,
                   TIMESTAMPDIFF(HOUR, delivered_at, NOW()) as hours_since_delivery
            FROM orders 
            WHERE id = ? AND user_id = ?
        ");
        $orderStmt->execute([$orderId, $customerId]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Order not found']);
            exit;
        }
        
        // Business Rule Validations
        if ($order['status'] !== 'delivered') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Unboxing requests are only allowed for delivered orders']);
            exit;
        }
        
        if (!$order['allows_unboxing_request']) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'This order does not allow unboxing requests']);
            exit;
        }
        
        // Check 48-hour time window
        if ($order['hours_since_delivery'] > 48) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Unboxing requests must be submitted within 48 hours of delivery']);
            exit;
        }
        
        // Check if request already exists for this order
        $existingStmt = $pdo->prepare("SELECT id FROM unboxing_requests WHERE order_id = ?");
        $existingStmt->execute([$orderId]);
        if ($existingStmt->fetch()) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'A request has already been submitted for this order']);
            exit;
        }
        
        // Handle video upload
        if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Video upload is required']);
            exit;
        }
        
        $video = $_FILES['video'];
        
        // Validate video file
        $allowedTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo'];
        if (!in_array($video['type'], $allowedTypes)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Only MP4, MOV, and AVI video formats are allowed']);
            exit;
        }
        
        // Check file size (max 100MB)
        $maxSize = 100 * 1024 * 1024; // 100MB
        if ($video['size'] > $maxSize) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Video file must be less than 100MB']);
            exit;
        }
        
        // Create upload directory
        $uploadDir = '../../uploads/unboxing_videos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($video['name'], PATHINFO_EXTENSION);
        $filename = 'unboxing_' . $orderId . '_' . time() . '.' . $fileExtension;
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($video['tmp_name'], $filepath)) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to save video file']);
            exit;
        }
        
        // Insert request into database
        $insertStmt = $pdo->prepare("
            INSERT INTO unboxing_requests 
            (order_id, customer_id, issue_type, request_type, video_filename, video_path, video_size_bytes, customer_description)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $insertStmt->execute([
            $orderId,
            $customerId,
            $issueType,
            $requestType,
            $filename,
            'uploads/unboxing_videos/' . $filename,
            $video['size'],
            $description
        ]);
        
        $requestId = $pdo->lastInsertId();
        
        // Log status history
        $historyStmt = $pdo->prepare("
            INSERT INTO unboxing_request_history (request_id, old_status, new_status, changed_by_user_id, change_reason)
            VALUES (?, NULL, 'pending', ?, 'Initial request submission')
        ");
        $historyStmt->execute([$requestId, $customerId]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Unboxing request submitted successfully',
            'request_id' => $requestId
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('Unboxing requests API error: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error occurred'
    ]);
}
?>