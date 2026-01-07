<?php
/**
 * Customer Design Approval API
 * Handles customer approval/rejection of admin designs
 */

require_once '../../config/database.php';
require_once '../../middleware/auth.php';

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Check customer authentication
$auth = new AuthMiddleware();
$user = $auth->authenticate();

if (!$user || $user['role'] !== 'customer') {
    http_response_code(403);
    echo json_encode(['error' => 'Customer access required']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = Database::getConnection();
    
    switch ($method) {
        case 'GET':
            handleGetDesignForApproval($pdo, $_GET, $user['id']);
            break;
        case 'POST':
            handleCustomerResponse($pdo, $input, $user['id']);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function handleGetDesignForApproval($pdo, $params, $customerId) {
    $orderId = $params['order_id'] ?? null;
    
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID required']);
        return;
    }
    
    // Verify customer owns this order
    $orderStmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND customer_id = ?");
    $orderStmt->execute([$orderId, $customerId]);
    if (!$orderStmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    // Get current design status and latest version
    $statusStmt = $pdo->prepare("
        SELECT ods.*, dv.preview_image_path, dv.notes as admin_notes, dv.created_at as design_created_at
        FROM order_design_status ods
        LEFT JOIN design_versions dv ON ods.order_id = dv.order_id AND ods.current_version = dv.version_number
        WHERE ods.order_id = ?
    ");
    $statusStmt->execute([$orderId]);
    $design = $statusStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$design) {
        http_response_code(404);
        echo json_encode(['error' => 'Design not found']);
        return;
    }
    
    // Get approval history
    $historyStmt = $pdo->prepare("
        SELECT dah.*, u.name as performed_by_name
        FROM design_approval_history dah
        LEFT JOIN users u ON dah.performed_by = u.id
        WHERE dah.order_id = ?
        ORDER BY dah.performed_at DESC
        LIMIT 10
    ");
    $historyStmt->execute([$orderId]);
    $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get original customer request
    $requestStmt = $pdo->prepare("
        SELECT customer_text, customer_image_path, preferred_color, special_notes
        FROM customer_design_requests
        WHERE order_id = ?
    ");
    $requestStmt->execute([$orderId]);
    $originalRequest = $requestStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'design' => $design,
        'history' => $history,
        'original_request' => $originalRequest,
        'can_respond' => in_array($design['current_status'], ['drafted_by_admin', 'changes_requested'])
    ]);
}

function handleCustomerResponse($pdo, $input, $customerId) {
    $orderId = $input['order_id'] ?? null;
    $action = $input['action'] ?? null; // 'approve' or 'request_changes'
    $feedback = $input['feedback'] ?? '';
    
    if (!$orderId || !$action) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID and action required']);
        return;
    }
    
    if (!in_array($action, ['approve', 'request_changes'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        return;
    }
    
    // Verify customer owns this order
    $orderStmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND customer_id = ?");
    $orderStmt->execute([$orderId, $customerId]);
    if (!$orderStmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    $pdo->beginTransaction();
    
    try {
        // Get current status
        $statusStmt = $pdo->prepare("SELECT current_status, current_version FROM order_design_status WHERE order_id = ?");
        $statusStmt->execute([$orderId]);
        $currentStatus = $statusStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$currentStatus) {
            throw new Exception('Design status not found');
        }
        
        // Check if customer can respond
        if (!in_array($currentStatus['current_status'], ['drafted_by_admin', 'changes_requested'])) {
            throw new Exception('Cannot respond to design in current status');
        }
        
        // Update status based on action
        $newStatus = ($action === 'approve') ? 'approved_by_customer' : 'changes_requested';
        
        $updateStmt = $pdo->prepare("
            UPDATE order_design_status 
            SET current_status = ?, customer_feedback = ?, last_updated_by = ?
            WHERE order_id = ?
        ");
        $updateStmt->execute([$newStatus, $feedback, $customerId, $orderId]);
        
        // Log approval history
        $historyAction = ($action === 'approve') ? 'approved' : 'changes_requested';
        $historyStmt = $pdo->prepare("
            INSERT INTO design_approval_history (order_id, version_number, action, performed_by, feedback)
            VALUES (?, ?, ?, ?, ?)
        ");
        $historyStmt->execute([
            $orderId,
            $currentStatus['current_version'],
            $historyAction,
            $customerId,
            $feedback
        ]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'new_status' => $newStatus,
            'message' => ($action === 'approve') ? 'Design approved successfully' : 'Changes requested successfully'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?>