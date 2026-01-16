<?php
/**
 * Image Authenticity Verification API
 * Handles image verification requests and integrates with Python ML service
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Email, X-Admin-User-Id');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

ini_set('display_errors', 0);
error_reporting(0);

try {
    require_once '../../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

// Verify admin access
$adminEmail = $_SERVER['HTTP_X_ADMIN_EMAIL'] ?? '';
$adminUserId = $_SERVER['HTTP_X_ADMIN_USER_ID'] ?? '';

if (empty($adminEmail) && empty($adminUserId)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Admin authentication required'
    ]);
    exit;
}

// Verify admin role
$isAdmin = false;
if (!empty($adminEmail)) {
    $stmt = $pdo->prepare("
        SELECT u.id, ur.role_id 
        FROM users u 
        LEFT JOIN user_roles ur ON u.id = ur.user_id 
        WHERE u.email = ? AND ur.role_id = 1
    ");
    $stmt->execute([$adminEmail]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = ($admin !== false);
    $adminUserId = $admin['id'] ?? $adminUserId;
}

if (!$isAdmin) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Admin access required'
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($pdo, $action);
            break;
        case 'POST':
            handlePostRequest($pdo, $action, $adminUserId);
            break;
        case 'PUT':
            handlePutRequest($pdo, $action, $adminUserId);
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
        case 'flagged_images':
            getFlaggedImages($pdo);
            break;
        case 'authenticity_stats':
            getAuthenticityStats($pdo);
            break;
        case 'verification_history':
            getVerificationHistory($pdo);
            break;
        case 'settings':
            getAuthenticitySettings($pdo);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handlePostRequest($pdo, $action, $adminUserId) {
    switch ($action) {
        case 'verify_image':
            verifyImage($pdo, $adminUserId);
            break;
        case 'batch_verify':
            batchVerifyImages($pdo, $adminUserId);
            break;
        case 'update_settings':
            updateAuthenticitySettings($pdo, $adminUserId);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handlePutRequest($pdo, $action, $adminUserId) {
    switch ($action) {
        case 'review_decision':
            updateReviewDecision($pdo, $adminUserId);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function getFlaggedImages($pdo) {
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $status = $_GET['status'] ?? 'pending';
    $riskLevel = $_GET['risk_level'] ?? '';
    
    $offset = ($page - 1) * $limit;
    
    $whereConditions = ['arq.admin_decision = ?'];
    $params = [$status];
    
    if (!empty($riskLevel)) {
        $whereConditions[] = 'arq.risk_level = ?';
        $params[] = $riskLevel;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Get total count
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM admin_review_queue arq 
        WHERE $whereClause
    ");
    $countStmt->execute($params);
    $totalCount = $countStmt->fetchColumn();
    
    // Get flagged images with details
    $stmt = $pdo->prepare("
        SELECT 
            arq.*,
            u.first_name,
            u.last_name,
            u.email,
            t.title as tutorial_title,
            iam.file_path,
            iam.original_filename,
            iam.metadata_extracted,
            iam.camera_info,
            iam.editing_software,
            iam.similarity_matches
        FROM admin_review_queue arq
        LEFT JOIN users u ON arq.user_id = u.id
        LEFT JOIN tutorials t ON arq.tutorial_id = t.id
        LEFT JOIN image_authenticity_metadata iam ON arq.image_id = iam.image_id AND arq.image_type = iam.image_type
        WHERE $whereClause
        ORDER BY arq.flagged_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $flaggedImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process the results to include image URLs
    foreach ($flaggedImages as &$image) {
        if (!empty($image['file_path'])) {
            $image['image_url'] = 'http://localhost/my_little_thingz/backend/' . $image['file_path'];
        }
        
        // Parse JSON fields
        $image['flagged_reasons'] = json_decode($image['flagged_reasons'] ?? '[]', true);
        $image['metadata_extracted'] = json_decode($image['metadata_extracted'] ?? '{}', true);
        $image['camera_info'] = json_decode($image['camera_info'] ?? '{}', true);
        $image['editing_software'] = json_decode($image['editing_software'] ?? '{}', true);
        $image['similarity_matches'] = json_decode($image['similarity_matches'] ?? '[]', true);
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'images' => $flaggedImages,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalCount / $limit),
                'total_count' => $totalCount,
                'per_page' => $limit
            ]
        ]
    ]);
}

function getAuthenticityStats($pdo) {
    // Get overall statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_images,
            SUM(CASE WHEN verification_status = 'verified' THEN 1 ELSE 0 END) as verified_images,
            SUM(CASE WHEN verification_status = 'flagged' THEN 1 ELSE 0 END) as flagged_images,
            SUM(CASE WHEN verification_status = 'pending' THEN 1 ELSE 0 END) as pending_images,
            SUM(CASE WHEN risk_level = 'clean' THEN 1 ELSE 0 END) as clean_images,
            SUM(CASE WHEN risk_level = 'suspicious' THEN 1 ELSE 0 END) as suspicious_images,
            SUM(CASE WHEN risk_level = 'highly_suspicious' THEN 1 ELSE 0 END) as highly_suspicious_images,
            AVG(authenticity_score) as avg_authenticity_score,
            MAX(authenticity_score) as max_authenticity_score,
            MIN(authenticity_score) as min_authenticity_score
        FROM image_authenticity_metadata
    ");
    $overallStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get admin review queue statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_pending,
            SUM(CASE WHEN admin_decision = 'approved' THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN admin_decision = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
            SUM(CASE WHEN admin_decision = 'request_reupload' THEN 1 ELSE 0 END) as reupload_count
        FROM admin_review_queue
    ");
    $reviewStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent activity (last 7 days)
    $stmt = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as images_processed,
            AVG(authenticity_score) as avg_score
        FROM image_authenticity_metadata 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'overall_stats' => $overallStats,
            'review_stats' => $reviewStats,
            'recent_activity' => $recentActivity
        ]
    ]);
}

function getVerificationHistory($pdo) {
    $imageId = $_GET['image_id'] ?? '';
    $imageType = $_GET['image_type'] ?? '';
    
    if (empty($imageId) || empty($imageType)) {
        throw new Exception('Image ID and type are required');
    }
    
    $stmt = $pdo->prepare("
        SELECT * FROM authenticity_audit_log 
        WHERE image_id = ? AND image_type = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$imageId, $imageType]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse JSON details
    foreach ($history as &$entry) {
        $entry['details'] = json_decode($entry['details'] ?? '{}', true);
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $history
    ]);
}

function getAuthenticitySettings($pdo) {
    $stmt = $pdo->query("
        SELECT setting_key, setting_value, setting_type, description 
        FROM authenticity_settings 
        ORDER BY setting_key
    ");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $settings
    ]);
}

function verifyImage($pdo, $adminUserId) {
    $imageId = $_POST['image_id'] ?? '';
    $imageType = $_POST['image_type'] ?? '';
    $filePath = $_POST['file_path'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);
    $tutorialId = !empty($_POST['tutorial_id']) ? (int)$_POST['tutorial_id'] : null;
    
    if (empty($imageId) || empty($imageType) || empty($filePath) || empty($userId)) {
        throw new Exception('Missing required parameters');
    }
    
    // Add to verification queue
    $stmt = $pdo->prepare("
        INSERT INTO image_verification_queue 
        (image_id, image_type, file_path, user_id, tutorial_id, priority, status)
        VALUES (?, ?, ?, ?, ?, 'high', 'queued')
        ON DUPLICATE KEY UPDATE
        priority = 'high',
        status = 'queued',
        attempts = 0,
        queued_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$imageId, $imageType, $filePath, $userId, $tutorialId]);
    
    // Call Python service to process immediately
    $result = callPythonVerificationService($imageId, $imageType, $filePath, $userId, $tutorialId);
    
    // Log audit action
    logAuditAction($pdo, $imageId, $imageType, 'manual_verification_requested', 
                   null, 'queued', $adminUserId, 'admin');
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Image verification initiated',
        'verification_result' => $result
    ]);
}

function batchVerifyImages($pdo, $adminUserId) {
    $images = $_POST['images'] ?? [];
    
    if (empty($images) || !is_array($images)) {
        throw new Exception('Images array is required');
    }
    
    $results = [];
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($images as $image) {
        try {
            $imageId = $image['image_id'] ?? '';
            $imageType = $image['image_type'] ?? '';
            $filePath = $image['file_path'] ?? '';
            $userId = (int)($image['user_id'] ?? 0);
            $tutorialId = !empty($image['tutorial_id']) ? (int)$image['tutorial_id'] : null;
            
            if (empty($imageId) || empty($imageType) || empty($filePath) || empty($userId)) {
                throw new Exception('Missing required parameters for image: ' . $imageId);
            }
            
            // Add to verification queue
            $stmt = $pdo->prepare("
                INSERT INTO image_verification_queue 
                (image_id, image_type, file_path, user_id, tutorial_id, priority, status)
                VALUES (?, ?, ?, ?, ?, 'medium', 'queued')
                ON DUPLICATE KEY UPDATE
                priority = 'medium',
                status = 'queued',
                attempts = 0,
                queued_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$imageId, $imageType, $filePath, $userId, $tutorialId]);
            
            $results[] = [
                'image_id' => $imageId,
                'status' => 'queued',
                'message' => 'Added to verification queue'
            ];
            $successCount++;
            
        } catch (Exception $e) {
            $results[] = [
                'image_id' => $image['image_id'] ?? 'unknown',
                'status' => 'error',
                'message' => $e->getMessage()
            ];
            $errorCount++;
        }
    }
    
    // Log audit action
    logAuditAction($pdo, 'batch', 'multiple', 'batch_verification_requested', 
                   null, 'queued', $adminUserId, 'admin');
    
    echo json_encode([
        'status' => 'success',
        'message' => "Batch verification initiated: $successCount successful, $errorCount errors",
        'results' => $results,
        'summary' => [
            'total' => count($images),
            'successful' => $successCount,
            'errors' => $errorCount
        ]
    ]);
}

function updateReviewDecision($pdo, $adminUserId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $reviewId = $input['review_id'] ?? '';
    $decision = $input['decision'] ?? '';
    $feedback = $input['feedback'] ?? '';
    
    if (empty($reviewId) || empty($decision)) {
        throw new Exception('Review ID and decision are required');
    }
    
    if (!in_array($decision, ['approved', 'rejected', 'request_reupload'])) {
        throw new Exception('Invalid decision value');
    }
    
    // Get current review details
    $stmt = $pdo->prepare("
        SELECT image_id, image_type, admin_decision 
        FROM admin_review_queue 
        WHERE id = ?
    ");
    $stmt->execute([$reviewId]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$review) {
        throw new Exception('Review not found');
    }
    
    $oldDecision = $review['admin_decision'];
    
    // Update review decision
    $stmt = $pdo->prepare("
        UPDATE admin_review_queue 
        SET admin_decision = ?, admin_feedback = ?, reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$decision, $feedback, $adminUserId, $reviewId]);
    
    // Update the original upload record based on decision
    if ($review['image_type'] === 'practice_upload') {
        $approvedStatus = ($decision === 'approved') ? 1 : 0;
        $verificationStatus = ($decision === 'approved') ? 'approved' : 
                             (($decision === 'rejected') ? 'rejected' : 'pending');
        
        $stmt = $pdo->prepare("
            UPDATE practice_uploads 
            SET admin_approved = ?, verification_status = ?, verification_notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$approvedStatus, $verificationStatus, $feedback, $review['image_id']]);
        
        // Update learning progress if approved
        if ($decision === 'approved') {
            updateLearningProgress($pdo, $review['image_id']);
        }
    }
    
    // Update authenticity metadata
    $stmt = $pdo->prepare("
        UPDATE image_authenticity_metadata 
        SET verification_status = ?
        WHERE image_id = ? AND image_type = ?
    ");
    $stmt->execute([$verificationStatus ?? $decision, $review['image_id'], $review['image_type']]);
    
    // Log audit action
    logAuditAction($pdo, $review['image_id'], $review['image_type'], 'admin_review_decision', 
                   $oldDecision, $decision, $adminUserId, 'admin');
    
    // Send notification to user if needed
    if ($decision !== 'approved') {
        sendUserNotification($pdo, $review['image_id'], $review['image_type'], $decision, $feedback);
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Review decision updated successfully',
        'decision' => $decision
    ]);
}

function updateLearningProgress($pdo, $practiceUploadId) {
    // Get practice upload details
    $stmt = $pdo->prepare("
        SELECT user_id, tutorial_id 
        FROM practice_uploads 
        WHERE id = ?
    ");
    $stmt->execute([$practiceUploadId]);
    $upload = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($upload) {
        // Update learning progress to mark practice as uploaded and approved
        $stmt = $pdo->prepare("
            UPDATE learning_progress 
            SET practice_uploaded = 1
            WHERE user_id = ? AND tutorial_id = ?
        ");
        $stmt->execute([$upload['user_id'], $upload['tutorial_id']]);
    }
}

function updateAuthenticitySettings($pdo, $adminUserId) {
    $settings = $_POST['settings'] ?? [];
    
    if (empty($settings) || !is_array($settings)) {
        throw new Exception('Settings array is required');
    }
    
    $updatedCount = 0;
    
    foreach ($settings as $setting) {
        $key = $setting['key'] ?? '';
        $value = $setting['value'] ?? '';
        
        if (empty($key)) {
            continue;
        }
        
        $stmt = $pdo->prepare("
            UPDATE authenticity_settings 
            SET setting_value = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP
            WHERE setting_key = ?
        ");
        $stmt->execute([$value, $adminUserId, $key]);
        
        if ($stmt->rowCount() > 0) {
            $updatedCount++;
        }
    }
    
    // Log audit action
    logAuditAction($pdo, 'settings', 'system', 'settings_updated', 
                   null, 'updated', $adminUserId, 'admin');
    
    echo json_encode([
        'status' => 'success',
        'message' => "$updatedCount settings updated successfully"
    ]);
}

function callPythonVerificationService($imageId, $imageType, $filePath, $userId, $tutorialId) {
    $pythonScript = __DIR__ . '/../../python_ml_service/image_authenticity_service.py';
    $command = "python3 \"$pythonScript\" verify \"$imageId\" \"$imageType\" \"$filePath\" \"$userId\"";
    
    if ($tutorialId) {
        $command .= " \"$tutorialId\"";
    }
    
    $output = shell_exec($command . ' 2>&1');
    
    if ($output) {
        $result = json_decode($output, true);
        return $result ?: ['error' => 'Failed to parse Python service response'];
    }
    
    return ['error' => 'Python service did not respond'];
}

function sendUserNotification($pdo, $imageId, $imageType, $decision, $feedback) {
    // Implementation for user notification
    // This could send email, in-app notification, etc.
    // For now, we'll just log it
    error_log("User notification: Image $imageId ($imageType) decision: $decision - $feedback");
}

function logAuditAction($pdo, $imageId, $imageType, $action, $oldStatus, $newStatus, $performedBy, $performedByType) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO authenticity_audit_log 
            (image_id, image_type, action, old_status, new_status, performed_by, performed_by_type, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $details = json_encode([
            'timestamp' => date('c'),
            'action_details' => "$action performed by $performedByType"
        ]);
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt->execute([
            $imageId, $imageType, $action, $oldStatus, $newStatus,
            $performedBy, $performedByType, $details, $ipAddress, $userAgent
        ]);
        
    } catch (Exception $e) {
        error_log("Failed to log audit action: " . $e->getMessage());
    }
}
?>