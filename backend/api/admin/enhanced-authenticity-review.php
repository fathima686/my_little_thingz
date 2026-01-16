<?php
/**
 * Enhanced Authenticity Review API for Admins
 * Handles review of flagged images with detailed evaluation context
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
            handleGetRequest($pdo, $action, $adminUserId);
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

function handleGetRequest($pdo, $action, $adminUserId) {
    switch ($action) {
        case 'pending_reviews':
            getPendingReviews($pdo);
            break;
        case 'review_statistics':
            getReviewStatistics($pdo);
            break;
        case 'category_analysis':
            getCategoryAnalysis($pdo);
            break;
        case 'similarity_patterns':
            getSimilarityPatterns($pdo);
            break;
        case 'false_positive_trends':
            getFalsePositiveTrends($pdo);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function handlePostRequest($pdo, $action, $adminUserId) {
    switch ($action) {
        case 'review_decision':
            processReviewDecision($pdo, $adminUserId);
            break;
        case 'batch_review':
            processBatchReview($pdo, $adminUserId);
            break;
        case 'update_thresholds':
            updateSimilarityThresholds($pdo, $adminUserId);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

function getPendingReviews($pdo) {
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 20;
    $category = $_GET['category'] ?? '';
    $riskLevel = $_GET['risk_level'] ?? '';
    $sortBy = $_GET['sort_by'] ?? 'flagged_at';
    $sortOrder = $_GET['sort_order'] ?? 'DESC';
    
    $offset = ($page - 1) * $limit;
    
    // Build query with filters
    $whereConditions = ['arq.admin_decision = "pending"'];
    $params = [];
    
    if (!empty($category)) {
        $whereConditions[] = 'arq.tutorial_category = ?';
        $params[] = $category;
    }
    
    if (!empty($riskLevel)) {
        $whereConditions[] = 'arq.risk_level = ?';
        $params[] = $riskLevel;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Get pending reviews with comprehensive context
    $stmt = $pdo->prepare("
        SELECT 
            arq.*,
            iam.file_path,
            iam.original_filename,
            iam.file_size,
            iam.perceptual_hash,
            iam.image_properties,
            iam.similarity_context,
            iam.multi_hash_results,
            iam.category_comparison_count,
            u.email as user_email,
            u.name as user_name,
            t.title as tutorial_title,
            t.description as tutorial_description,
            scr.max_similarity_score,
            scr.similarity_methods,
            COUNT(scr.id) as similarity_matches_count
        FROM admin_review_queue arq
        LEFT JOIN image_authenticity_metadata iam ON arq.image_id = iam.image_id AND arq.image_type = iam.image_type
        LEFT JOIN users u ON arq.user_id = u.id
        LEFT JOIN tutorials t ON arq.tutorial_id = t.id
        LEFT JOIN similarity_comparison_results scr ON arq.image_id = scr.source_image_id AND scr.flagged_as_suspicious = 1
        WHERE {$whereClause}
        GROUP BY arq.id
        ORDER BY {$sortBy} {$sortOrder}
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $countStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT arq.id) as total
        FROM admin_review_queue arq
        WHERE {$whereClause}
    ");
    $countStmt->execute(array_slice($params, 0, -2));
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Process and enhance review data
    foreach ($reviews as &$review) {
        // Parse JSON fields
        $review['flagged_reasons'] = json_decode($review['flagged_reasons'] ?? '[]', true);
        $review['evaluation_details'] = json_decode($review['evaluation_details'] ?? '[]', true);
        $review['similarity_matches'] = json_decode($review['similarity_matches'] ?? '[]', true);
        $review['image_properties'] = json_decode($review['image_properties'] ?? '{}', true);
        $review['similarity_context'] = json_decode($review['similarity_context'] ?? '{}', true);
        $review['multi_hash_results'] = json_decode($review['multi_hash_results'] ?? '{}', true);
        
        // Add image URL for preview
        $review['image_preview_url'] = 'http://localhost/my_little_thingz/backend/' . $review['file_path'];
        
        // Calculate review priority score
        $review['priority_score'] = calculateReviewPriority($review);
        
        // Get similar images for comparison
        $review['similar_images_details'] = getSimilarImagesForReview($pdo, $review['image_id'], $review['image_type']);
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
                'risk_level' => $riskLevel,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder
            ]
        ]
    ]);
}

function getReviewStatistics($pdo) {
    $timeframe = $_GET['timeframe'] ?? '30'; // days
    
    // Get comprehensive statistics
    $stats = [];
    
    // Overall statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_reviews,
            SUM(CASE WHEN admin_decision = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN admin_decision = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN admin_decision = 'false_positive' THEN 1 ELSE 0 END) as false_positives,
            SUM(CASE WHEN admin_decision = 'pending' THEN 1 ELSE 0 END) as pending,
            AVG(review_time_seconds) as avg_review_time,
            AVG(authenticity_score) as avg_authenticity_score
        FROM admin_review_queue 
        WHERE flagged_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    $stmt->execute([$timeframe]);
    $stats['overall'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Statistics by category
    $stmt = $pdo->prepare("
        SELECT 
            tutorial_category,
            COUNT(*) as total_reviews,
            SUM(CASE WHEN admin_decision = 'false_positive' THEN 1 ELSE 0 END) as false_positives,
            AVG(authenticity_score) as avg_score,
            SUM(CASE WHEN admin_decision = 'pending' THEN 1 ELSE 0 END) as pending
        FROM admin_review_queue 
        WHERE flagged_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY tutorial_category
        ORDER BY total_reviews DESC
    ");
    $stmt->execute([$timeframe]);
    $stats['by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistics by risk level
    $stmt = $pdo->prepare("
        SELECT 
            risk_level,
            COUNT(*) as total_reviews,
            SUM(CASE WHEN admin_decision = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN admin_decision = 'false_positive' THEN 1 ELSE 0 END) as false_positives
        FROM admin_review_queue 
        WHERE flagged_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY risk_level
    ");
    $stmt->execute([$timeframe]);
    $stats['by_risk_level'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Daily trends
    $stmt = $pdo->prepare("
        SELECT 
            DATE(flagged_at) as date,
            COUNT(*) as total_flagged,
            SUM(CASE WHEN admin_decision = 'false_positive' THEN 1 ELSE 0 END) as false_positives,
            AVG(authenticity_score) as avg_score
        FROM admin_review_queue 
        WHERE flagged_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY DATE(flagged_at)
        ORDER BY date DESC
    ");
    $stmt->execute([$timeframe]);
    $stats['daily_trends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $stats,
        'timeframe_days' => (int)$timeframe
    ]);
}

function processReviewDecision($pdo, $adminUserId) {
    $imageId = $_POST['image_id'] ?? '';
    $imageType = $_POST['image_type'] ?? '';
    $decision = $_POST['decision'] ?? '';
    $feedback = $_POST['feedback'] ?? '';
    $reasoning = $_POST['reasoning'] ?? '';
    $reviewTimeSeconds = $_POST['review_time_seconds'] ?? 0;
    $wasCorrectlyFlagged = $_POST['was_correctly_flagged'] ?? null;
    
    if (empty($imageId) || empty($imageType) || empty($decision)) {
        throw new Exception('Missing required fields');
    }
    
    $validDecisions = ['approved', 'rejected', 'request_reupload', 'false_positive'];
    if (!in_array($decision, $validDecisions)) {
        throw new Exception('Invalid decision');
    }
    
    $pdo->beginTransaction();
    
    try {
        // Get original review data
        $stmt = $pdo->prepare("
            SELECT * FROM admin_review_queue 
            WHERE image_id = ? AND image_type = ? AND admin_decision = 'pending'
        ");
        $stmt->execute([$imageId, $imageType]);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$review) {
            throw new Exception('Review not found or already processed');
        }
        
        // Update admin review queue
        $stmt = $pdo->prepare("
            UPDATE admin_review_queue 
            SET admin_decision = ?, 
                admin_feedback = ?, 
                reviewed_by = ?, 
                reviewed_at = NOW()
            WHERE image_id = ? AND image_type = ?
        ");
        $stmt->execute([$decision, $feedback, $adminUserId, $imageId, $imageType]);
        
        // Record decision in tracking table
        $stmt = $pdo->prepare("
            INSERT INTO admin_review_decisions 
            (image_id, image_type, original_risk_level, admin_decision, 
             admin_feedback, decision_reasoning, reviewed_by, review_time_seconds, 
             was_correctly_flagged, reviewed_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $imageId, $imageType, $review['risk_level'], $decision,
            $feedback, $reasoning, $adminUserId, $reviewTimeSeconds, $wasCorrectlyFlagged
        ]);
        
        // Update image authenticity metadata
        $newStatus = ($decision === 'approved') ? 'approved' : 
                    (($decision === 'rejected') ? 'rejected' : 'pending_reupload');
        
        $stmt = $pdo->prepare("
            UPDATE image_authenticity_metadata 
            SET verification_status = ?, 
                requires_admin_review = 0,
                updated_at = NOW()
            WHERE image_id = ? AND image_type = ?
        ");
        $stmt->execute([$newStatus, $imageId, $imageType]);
        
        // If false positive, update evaluation rules (learning mechanism)
        if ($decision === 'false_positive') {
            updateEvaluationRulesFromFalsePositive($pdo, $review);
        }
        
        // Update practice upload status if applicable
        if ($imageType === 'practice_upload') {
            updatePracticeUploadStatus($pdo, $imageId, $decision, $feedback);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Review decision processed successfully',
            'data' => [
                'image_id' => $imageId,
                'decision' => $decision,
                'processed_at' => date('Y-m-d H:i:s')
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function calculateReviewPriority($review) {
    $score = 0;
    
    // Risk level priority
    switch ($review['risk_level']) {
        case 'highly_suspicious': $score += 50; break;
        case 'suspicious': $score += 30; break;
        case 'low_concern': $score += 10; break;
    }
    
    // Authenticity score (lower = higher priority)
    $score += (100 - $review['authenticity_score']) * 0.3;
    
    // Similarity matches
    $score += $review['similarity_matches_count'] * 10;
    
    // Time waiting (older = higher priority)
    $hoursWaiting = (time() - strtotime($review['flagged_at'])) / 3600;
    $score += min($hoursWaiting * 2, 50);
    
    return round($score, 2);
}

function getSimilarImagesForReview($pdo, $imageId, $imageType) {
    $stmt = $pdo->prepare("
        SELECT 
            scr.*,
            iam.file_path,
            iam.original_filename,
            t.title as tutorial_title
        FROM similarity_comparison_results scr
        LEFT JOIN image_authenticity_metadata iam ON scr.target_image_id = iam.image_id
        LEFT JOIN practice_uploads pu ON iam.image_id LIKE CONCAT('%', pu.id, '%')
        LEFT JOIN tutorials t ON pu.tutorial_id = t.id
        WHERE scr.source_image_id = ? 
        AND scr.flagged_as_suspicious = 1
        ORDER BY scr.max_similarity_score DESC
        LIMIT 5
    ");
    $stmt->execute([$imageId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateEvaluationRulesFromFalsePositive($pdo, $review) {
    // Implement learning mechanism to reduce false positives
    // This could adjust thresholds or add exceptions based on patterns
    
    try {
        // Log false positive pattern for analysis
        $stmt = $pdo->prepare("
            INSERT INTO authenticity_statistics 
            (date, tutorial_category, false_positive_reports)
            VALUES (CURDATE(), ?, 1)
            ON DUPLICATE KEY UPDATE 
            false_positive_reports = false_positive_reports + 1
        ");
        $stmt->execute([$review['tutorial_category']]);
        
    } catch (Exception $e) {
        error_log("Error updating evaluation rules from false positive: " . $e->getMessage());
    }
}

function updatePracticeUploadStatus($pdo, $imageId, $decision, $feedback) {
    // Extract practice upload ID from image ID
    if (preg_match('/direct_(\d+)_/', $imageId, $matches)) {
        $uploadId = $matches[1];
        
        $status = ($decision === 'approved') ? 'approved' : 
                 (($decision === 'rejected') ? 'rejected' : 'pending');
        
        $stmt = $pdo->prepare("
            UPDATE practice_uploads 
            SET status = ?, 
                admin_feedback = ?,
                reviewed_date = NOW(),
                authenticity_verified = 1
            WHERE id = ?
        ");
        $stmt->execute([$status, $feedback, $uploadId]);
    }
}

// Additional helper functions would go here...

?>