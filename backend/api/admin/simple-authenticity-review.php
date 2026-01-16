<?php
/**
 * Simplified Authenticity Review API for Admins
 * Clear, explainable review interface without complex scoring
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    require_once '../../config/database.php';
    require_once '../../services/SimplifiedImageAuthenticityService.php';
    
    $database = new Database();
    $pdo = $database->getConnection();
    $authenticityService = new SimplifiedImageAuthenticityService($pdo);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'System initialization failed: ' . $e->getMessage()
    ]);
    exit;
}

// Verify admin access
$adminEmail = $_SERVER['HTTP_X_ADMIN_EMAIL'] ?? '';

if (empty($adminEmail)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Admin authentication required'
    ]);
    exit;
}

// Verify admin role
$stmt = $pdo->prepare("
    SELECT u.id, ur.role_id 
    FROM users u 
    LEFT JOIN user_roles ur ON u.id = ur.user_id 
    WHERE u.email = ? AND ur.role_id = 1
");
$stmt->execute([$adminEmail]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Admin access required'
    ]);
    exit;
}

$adminId = $admin['id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($pdo, $action);
            break;
        case 'POST':
            handlePostRequest($pdo, $action, $adminId);
            break;
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

function handleGetRequest($pdo, $action) {
    switch ($action) {
        case 'pending_reviews':
            getPendingReviews($pdo);
            break;
        case 'review_statistics':
            getReviewStatistics($pdo);
            break;
        case 'image_details':
            getImageDetails($pdo);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handlePostRequest($pdo, $action, $adminId) {
    global $authenticityService;
    
    switch ($action) {
        case 'review_decision':
            processReviewDecision($pdo, $authenticityService, $adminId);
            break;
        case 'batch_approve':
            processBatchApproval($pdo, $authenticityService, $adminId);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function getPendingReviews($pdo) {
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 20;
    $category = $_GET['category'] ?? '';
    $status = $_GET['status'] ?? '';
    
    $offset = ($page - 1) * $limit;
    
    // Build query with filters
    $whereConditions = ['ars.admin_decision = "pending"'];
    $params = [];
    
    if (!empty($category)) {
        $whereConditions[] = 'ars.category = ?';
        $params[] = $category;
    }
    
    if (!empty($status)) {
        $whereConditions[] = 'ars.evaluation_status = ?';
        $params[] = $status;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Get pending reviews with context
    $stmt = $pdo->prepare("
        SELECT 
            ars.*,
            ias.phash,
            ias.metadata_notes,
            u.email as user_email,
            u.name as user_name,
            t.title as tutorial_title,
            t.category as tutorial_category,
            pu.description as practice_description,
            pu.upload_date
        FROM admin_review_simple ars
        LEFT JOIN image_authenticity_simple ias ON ars.image_id = ias.image_id AND ars.image_type = ias.image_type
        LEFT JOIN users u ON ars.user_id = u.id
        LEFT JOIN tutorials t ON ars.tutorial_id = t.id
        LEFT JOIN practice_uploads pu ON SUBSTRING_INDEX(ars.image_id, '_', 1) = pu.id
        WHERE {$whereClause}
        ORDER BY ars.flagged_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM admin_review_simple ars
        WHERE {$whereClause}
    ");
    $countStmt->execute(array_slice($params, 0, -2));
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Process review data
    foreach ($reviews as &$review) {
        // Parse similar image info
        $review['similar_image_info'] = json_decode($review['similar_image_info'] ?? 'null', true);
        
        // Add image preview URL
        $uploadId = explode('_', $review['image_id'])[0];
        $review['image_preview_url'] = "../../uploads/practice/practice_{$review['user_id']}_{$review['tutorial_id']}_*_{$review['image_id']}.jpg";
        
        // Get similar image details if available
        if ($review['similar_image_info']) {
            $review['similar_image_details'] = getSimilarImageDetails($pdo, $review['similar_image_info']);
        }
        
        // Calculate time waiting
        $review['hours_waiting'] = round((time() - strtotime($review['flagged_at'])) / 3600, 1);
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'reviews' => $reviews,
            'pagination' => [
                'current_page' => (int)$page,
                'total_pages' => ceil($totalCount / $limit),
                'total_items' => (int)$totalCount,
                'items_per_page' => (int)$limit
            ],
            'filters_applied' => [
                'category' => $category,
                'status' => $status
            ]
        ]
    ]);
}

function getReviewStatistics($pdo) {
    $timeframe = $_GET['timeframe'] ?? '30'; // days
    
    // Overall statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_reviews,
            SUM(CASE WHEN admin_decision = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN admin_decision = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN admin_decision = 'false_positive' THEN 1 ELSE 0 END) as false_positives,
            SUM(CASE WHEN admin_decision = 'pending' THEN 1 ELSE 0 END) as pending
        FROM admin_review_simple 
        WHERE flagged_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    $stmt->execute([$timeframe]);
    $overall = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Statistics by category
    $stmt = $pdo->prepare("
        SELECT 
            category,
            COUNT(*) as total_reviews,
            SUM(CASE WHEN admin_decision = 'false_positive' THEN 1 ELSE 0 END) as false_positives,
            SUM(CASE WHEN admin_decision = 'pending' THEN 1 ELSE 0 END) as pending
        FROM admin_review_simple 
        WHERE flagged_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY category
        ORDER BY total_reviews DESC
    ");
    $stmt->execute([$timeframe]);
    $byCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistics by evaluation status
    $stmt = $pdo->prepare("
        SELECT 
            evaluation_status,
            COUNT(*) as total_reviews,
            SUM(CASE WHEN admin_decision = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN admin_decision = 'false_positive' THEN 1 ELSE 0 END) as false_positives
        FROM admin_review_simple 
        WHERE flagged_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY evaluation_status
    ");
    $stmt->execute([$timeframe]);
    $byStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'overall' => $overall,
            'by_category' => $byCategory,
            'by_evaluation_status' => $byStatus,
            'timeframe_days' => (int)$timeframe
        ]
    ]);
}

function getImageDetails($pdo) {
    $imageId = $_GET['image_id'] ?? '';
    $imageType = $_GET['image_type'] ?? '';
    
    if (empty($imageId) || empty($imageType)) {
        throw new Exception('Image ID and type required');
    }
    
    // Get detailed image information
    $stmt = $pdo->prepare("
        SELECT 
            ias.*,
            ars.flagged_reason,
            ars.similar_image_info,
            ars.flagged_at,
            u.email as user_email,
            u.name as user_name,
            t.title as tutorial_title,
            t.category as tutorial_category
        FROM image_authenticity_simple ias
        LEFT JOIN admin_review_simple ars ON ias.image_id = ars.image_id AND ias.image_type = ars.image_type
        LEFT JOIN users u ON ias.user_id = u.id
        LEFT JOIN tutorials t ON ias.tutorial_id = t.id
        WHERE ias.image_id = ? AND ias.image_type = ?
    ");
    $stmt->execute([$imageId, $imageType]);
    $details = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$details) {
        throw new Exception('Image not found');
    }
    
    // Parse similar image info
    $details['similar_image_info'] = json_decode($details['similar_image_info'] ?? 'null', true);
    
    // Get similar image details if available
    if ($details['similar_image_info']) {
        $details['similar_image_details'] = getSimilarImageDetails($pdo, $details['similar_image_info']);
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $details
    ]);
}

function processReviewDecision($pdo, $authenticityService, $adminId) {
    $imageId = $_POST['image_id'] ?? '';
    $imageType = $_POST['image_type'] ?? '';
    $decision = $_POST['decision'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if (empty($imageId) || empty($imageType) || empty($decision)) {
        throw new Exception('Missing required fields');
    }
    
    $validDecisions = ['approved', 'rejected', 'false_positive'];
    if (!in_array($decision, $validDecisions)) {
        throw new Exception('Invalid decision');
    }
    
    // Update admin decision
    $success = $authenticityService->updateAdminDecision($imageId, $imageType, $decision, $adminId, $notes);
    
    if (!$success) {
        throw new Exception('Failed to update admin decision');
    }
    
    // If approved, update practice progress
    if ($decision === 'approved' && $imageType === 'practice_upload') {
        updatePracticeProgress($pdo, $imageId);
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Review decision processed successfully',
        'data' => [
            'image_id' => $imageId,
            'decision' => $decision,
            'processed_at' => date('Y-m-d H:i:s')
        ]
    ]);
}

function processBatchApproval($pdo, $authenticityService, $adminId) {
    $imageIds = $_POST['image_ids'] ?? [];
    $notes = $_POST['notes'] ?? 'Batch approval';
    
    if (empty($imageIds) || !is_array($imageIds)) {
        throw new Exception('No images selected for batch approval');
    }
    
    $results = [];
    $successCount = 0;
    
    foreach ($imageIds as $imageData) {
        $imageId = $imageData['image_id'] ?? '';
        $imageType = $imageData['image_type'] ?? '';
        
        if (empty($imageId) || empty($imageType)) {
            $results[] = ['image_id' => $imageId, 'status' => 'error', 'message' => 'Invalid image data'];
            continue;
        }
        
        try {
            $success = $authenticityService->updateAdminDecision($imageId, $imageType, 'approved', $adminId, $notes);
            
            if ($success) {
                if ($imageType === 'practice_upload') {
                    updatePracticeProgress($pdo, $imageId);
                }
                $results[] = ['image_id' => $imageId, 'status' => 'success'];
                $successCount++;
            } else {
                $results[] = ['image_id' => $imageId, 'status' => 'error', 'message' => 'Update failed'];
            }
            
        } catch (Exception $e) {
            $results[] = ['image_id' => $imageId, 'status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => "Batch approval completed: {$successCount} of " . count($imageIds) . " images processed",
        'data' => [
            'total_processed' => count($imageIds),
            'successful' => $successCount,
            'failed' => count($imageIds) - $successCount,
            'results' => $results
        ]
    ]);
}

function getSimilarImageDetails($pdo, $similarImageInfo) {
    if (!$similarImageInfo || !isset($similarImageInfo['image_id'])) {
        return null;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            ias.image_id,
            ias.image_type,
            ias.created_at,
            u.email as user_email,
            t.title as tutorial_title
        FROM image_authenticity_simple ias
        LEFT JOIN users u ON ias.user_id = u.id
        LEFT JOIN tutorials t ON ias.tutorial_id = t.id
        WHERE ias.image_id = ? AND ias.image_type = ?
    ");
    $stmt->execute([$similarImageInfo['image_id'], $similarImageInfo['image_type']]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updatePracticeProgress($pdo, $imageId) {
    // Extract upload ID from image ID
    $uploadId = explode('_', $imageId)[0];
    
    try {
        // Get practice upload details
        $stmt = $pdo->prepare("SELECT user_id, tutorial_id FROM practice_uploads WHERE id = ?");
        $stmt->execute([$uploadId]);
        $upload = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($upload) {
            // Update learning progress
            $stmt = $pdo->prepare("
                UPDATE learning_progress 
                SET practice_completed = 1, practice_admin_approved = 1, last_accessed = NOW()
                WHERE user_id = ? AND tutorial_id = ?
            ");
            $stmt->execute([$upload['user_id'], $upload['tutorial_id']]);
            
            // Update practice upload status
            $stmt = $pdo->prepare("
                UPDATE practice_uploads 
                SET authenticity_status = 'approved', progress_approved = 1
                WHERE id = ?
            ");
            $stmt->execute([$uploadId]);
        }
        
    } catch (Exception $e) {
        error_log("Error updating practice progress for image $imageId: " . $e->getMessage());
    }
}
?>