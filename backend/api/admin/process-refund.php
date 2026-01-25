<?php
/**
 * PROCESS REFUND API
 * Handles refund processing for approved unboxing requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-User-Id, X-Admin-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config/database.php';
require_once '../../services/RefundService.php';

try {
    // Admin authentication
    $adminId = $_SERVER['HTTP_X_ADMIN_USER_ID'] ?? null;
    
    if (!$adminId) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Admin authentication required']);
        exit;
    }
    
    $adminId = (int)$adminId;
    if ($adminId <= 0) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid admin user ID']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $requestId = $data['request_id'] ?? null;
    $adminNotes = $data['admin_notes'] ?? '';
    
    if (!$requestId) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Request ID is required']);
        exit;
    }
    
    // Initialize refund service
    $database = new Database();
    $refundService = new RefundService($database);
    
    // Process the refund
    $result = $refundService->processRefund($requestId, $adminId, $adminNotes);
    
    if ($result['status'] === 'success') {
        echo json_encode([
            'status' => 'success',
            'message' => 'Refund processed successfully and customer has been notified',
            'refund_details' => $result
        ]);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('Process refund API error: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error occurred while processing refund'
    ]);
}
?>