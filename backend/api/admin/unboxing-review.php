<?php
/**
 * UNBOXING VIDEO VERIFICATION - ADMIN REVIEW API
 * Academic Project - Admin Module
 * 
 * Handles admin review of unboxing video requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-User-Id, X-Admin-Email, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get admin ID from headers - be more flexible with authentication
    $adminId = $_SERVER['HTTP_X_ADMIN_USER_ID'] ?? null;
    $adminEmail = $_SERVER['HTTP_X_ADMIN_EMAIL'] ?? null;
    
    // Allow authentication with just user ID if email is missing
    if (!$adminId) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Admin authentication required - missing user ID']);
        exit;
    }
    
    // Convert to integer and validate
    $adminId = (int)$adminId;
    if ($adminId <= 0) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid admin user ID']);
        exit;
    }
    
    // If email is missing, fetch it from database
    if (!$adminEmail || trim($adminEmail) === '') {
        try {
            $emailStmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $emailStmt->execute([$adminId]);
            $userEmail = $emailStmt->fetchColumn();
            
            if ($userEmail) {
                $adminEmail = $userEmail;
                error_log("Info: Fetched missing admin email from database for user ID: $adminId");
            } else {
                $adminEmail = "admin_user_$adminId@system.local"; // Fallback email
                error_log("Warning: Admin user ID $adminId not found in database, using fallback email");
            }
        } catch (Exception $e) {
            error_log("Error fetching admin email: " . $e->getMessage());
            $adminEmail = "admin_user_$adminId@system.local"; // Fallback email
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get all unboxing requests for admin review
        $status = $_GET['status'] ?? 'all';
        
        $sql = "
            SELECT ur.*, 
                   o.order_number, o.total_amount, o.status as order_status,
                   u.first_name, u.last_name, u.email as customer_email,
                   TIMESTAMPDIFF(HOUR, ur.created_at, NOW()) as hours_since_request
            FROM unboxing_requests ur
            JOIN orders o ON ur.order_id = o.id
            JOIN users u ON ur.customer_id = u.id
        ";
        
        $params = [];
        if ($status !== 'all') {
            $sql .= " WHERE ur.request_status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY ur.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get statistics
        $statsStmt = $pdo->prepare("
            SELECT 
                request_status,
                COUNT(*) as count
            FROM unboxing_requests 
            GROUP BY request_status
        ");
        $statsStmt->execute();
        $stats = $statsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        echo json_encode([
            'status' => 'success',
            'requests' => $requests,
            'statistics' => $stats
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Update request status (admin decision)
        $data = json_decode(file_get_contents('php://input'), true);
        
        $requestId = $data['request_id'] ?? null;
        $newStatus = $data['status'] ?? null;
        $adminNotes = $data['admin_notes'] ?? '';
        
        if (!$requestId || !$newStatus) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
            exit;
        }
        
        // Validate status
        $validStatuses = ['under_review', 'refund_approved', 'replacement_approved', 'rejected', 'refund_processed'];
        if (!in_array($newStatus, $validStatuses)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
            exit;
        }
        
        // Get current request
        $currentStmt = $pdo->prepare("SELECT * FROM unboxing_requests WHERE id = ?");
        $currentStmt->execute([$requestId]);
        $currentRequest = $currentStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$currentRequest) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Request not found']);
            exit;
        }
        
        $oldStatus = $currentRequest['request_status'];
        
        // Update request
        $updateStmt = $pdo->prepare("
            UPDATE unboxing_requests 
            SET request_status = ?, admin_id = ?, admin_notes = ?, admin_reviewed_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([$newStatus, $adminId, $adminNotes, $requestId]);
        
        // Log status history
        $historyStmt = $pdo->prepare("
            INSERT INTO unboxing_request_history (request_id, old_status, new_status, changed_by_user_id, change_reason)
            VALUES (?, ?, ?, ?, ?)
        ");
        $historyStmt->execute([$requestId, $oldStatus, $newStatus, $adminId, $adminNotes]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Request status updated successfully'
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get request details with history
        $requestId = $_POST['request_id'] ?? null;
        
        if (!$requestId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Request ID required']);
            exit;
        }
        
        // Get request details
        $requestStmt = $pdo->prepare("
            SELECT ur.*, 
                   o.order_number, o.total_amount, o.status as order_status, o.delivered_at,
                   u.first_name, u.last_name, u.email as customer_email
            FROM unboxing_requests ur
            JOIN orders o ON ur.order_id = o.id
            JOIN users u ON ur.customer_id = u.id
            WHERE ur.id = ?
        ");
        $requestStmt->execute([$requestId]);
        $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Request not found']);
            exit;
        }
        
        // Get status history
        $historyStmt = $pdo->prepare("
            SELECT h.*, u.first_name, u.last_name, u.email
            FROM unboxing_request_history h
            JOIN users u ON h.changed_by_user_id = u.id
            WHERE h.request_id = ?
            ORDER BY h.created_at ASC
        ");
        $historyStmt->execute([$requestId]);
        $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'request' => $request,
            'history' => $history
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('Unboxing review API error: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error occurred'
    ]);
}
?>