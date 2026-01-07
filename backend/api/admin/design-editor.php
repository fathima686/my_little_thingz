<?php
/**
 * Admin Design Editor API
 * Handles saving/loading canvas designs and managing design versions
 */

require_once '../../config/database.php';
require_once '../../middleware/auth.php';

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Check admin authentication
$auth = new AuthMiddleware();
$user = $auth->authenticate();

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = Database::getConnection();
    
    switch ($method) {
        case 'GET':
            handleGetDesign($pdo, $_GET);
            break;
        case 'POST':
            handleSaveDesign($pdo, $input, $user['id']);
            break;
        case 'PUT':
            handleUpdateStatus($pdo, $input, $user['id']);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function handleGetDesign($pdo, $params) {
    $orderId = $params['order_id'] ?? null;
    $version = $params['version'] ?? null;
    
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID required']);
        return;
    }
    
    // Get order design status
    $statusStmt = $pdo->prepare("
        SELECT ods.*, cr.customer_text, cr.customer_image_path, cr.preferred_color, cr.special_notes
        FROM order_design_status ods
        LEFT JOIN customer_design_requests cr ON ods.order_id = cr.order_id
        WHERE ods.order_id = ?
    ");
    $statusStmt->execute([$orderId]);
    $status = $statusStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$status) {
        http_response_code(404);
        echo json_encode(['error' => 'Design not found']);
        return;
    }
    
    // Check if design is locked
    if ($status['current_status'] === 'locked_for_production') {
        http_response_code(403);
        echo json_encode(['error' => 'Design is locked for production']);
        return;
    }
    
    // Get specific version or latest
    $versionToGet = $version ?? $status['current_version'];
    
    $designStmt = $pdo->prepare("
        SELECT dv.*, u.name as admin_name
        FROM design_versions dv
        LEFT JOIN users u ON dv.created_by_admin_id = u.id
        WHERE dv.order_id = ? AND dv.version_number = ?
    ");
    $designStmt->execute([$orderId, $versionToGet]);
    $design = $designStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get all versions for this order
    $versionsStmt = $pdo->prepare("
        SELECT version_number, created_at, preview_image_path, notes
        FROM design_versions
        WHERE order_id = ?
        ORDER BY version_number DESC
    ");
    $versionsStmt->execute([$orderId]);
    $versions = $versionsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => $status,
        'design' => $design,
        'versions' => $versions
    ]);
}

function handleSaveDesign($pdo, $input, $adminId) {
    $orderId = $input['order_id'] ?? null;
    $canvasData = $input['canvas_data'] ?? null;
    $previewImage = $input['preview_image'] ?? null;
    $notes = $input['notes'] ?? '';
    
    if (!$orderId || !$canvasData) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID and canvas data required']);
        return;
    }
    
    $pdo->beginTransaction();
    
    try {
        // Check current status
        $statusStmt = $pdo->prepare("SELECT current_status, current_version FROM order_design_status WHERE order_id = ?");
        $statusStmt->execute([$orderId]);
        $currentStatus = $statusStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$currentStatus) {
            throw new Exception('Order design status not found');
        }
        
        if ($currentStatus['current_status'] === 'locked_for_production') {
            throw new Exception('Cannot edit locked design');
        }
        
        // Generate new version number
        $newVersion = $currentStatus['current_version'] + 1;
        
        // Save preview image if provided
        $previewPath = null;
        if ($previewImage) {
            $previewPath = savePreviewImage($previewImage, $orderId, $newVersion);
        }
        
        // Insert new design version
        $insertStmt = $pdo->prepare("
            INSERT INTO design_versions (order_id, version_number, canvas_data, preview_image_path, created_by_admin_id, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([
            $orderId,
            $newVersion,
            json_encode($canvasData),
            $previewPath,
            $adminId,
            $notes
        ]);
        
        // Update order status
        $updateStatusStmt = $pdo->prepare("
            UPDATE order_design_status 
            SET current_version = ?, current_status = 'drafted_by_admin', last_updated_by = ?
            WHERE order_id = ?
        ");
        $updateStatusStmt->execute([$newVersion, $adminId, $orderId]);
        
        // Log approval history
        $historyStmt = $pdo->prepare("
            INSERT INTO design_approval_history (order_id, version_number, action, performed_by)
            VALUES (?, ?, 'admin_draft_saved', ?)
        ");
        $historyStmt->execute([$orderId, $newVersion, $adminId]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'version' => $newVersion,
            'preview_path' => $previewPath
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function handleUpdateStatus($pdo, $input, $adminId) {
    $orderId = $input['order_id'] ?? null;
    $newStatus = $input['status'] ?? null;
    $adminNotes = $input['admin_notes'] ?? '';
    
    if (!$orderId || !$newStatus) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID and status required']);
        return;
    }
    
    $validStatuses = ['submitted', 'drafted_by_admin', 'changes_requested', 'approved_by_customer', 'locked_for_production'];
    if (!in_array($newStatus, $validStatuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status']);
        return;
    }
    
    $updateStmt = $pdo->prepare("
        UPDATE order_design_status 
        SET current_status = ?, admin_notes = ?, last_updated_by = ?
        WHERE order_id = ?
    ");
    $updateStmt->execute([$newStatus, $adminNotes, $adminId, $orderId]);
    
    echo json_encode(['success' => true]);
}

function savePreviewImage($base64Image, $orderId, $version) {
    // Remove data:image/png;base64, prefix
    $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);
    $imageData = base64_decode($imageData);
    
    if (!$imageData) {
        throw new Exception('Invalid image data');
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = '../../uploads/design_previews/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filename = "order_{$orderId}_v{$version}_" . time() . '.png';
    $filepath = $uploadDir . $filename;
    
    if (file_put_contents($filepath, $imageData) === false) {
        throw new Exception('Failed to save preview image');
    }
    
    return 'uploads/design_previews/' . $filename;
}
?>