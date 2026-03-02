<?php
/**
 * Craft Validation Dashboard API V3 - Production Version
 * 
 * Shows ONLY AI-flagged submissions for human-in-the-loop review
 * Auto-approved submissions bypass the admin dashboard entirely
 * Auto-rejected submissions never appear here
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    require_once '../../config/database.php';
    
    $database = new Database();
    $pdo = $database->getConnection();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => 'Only GET method allowed'
    ]);
    exit;
}

try {
    // Get query parameters
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(50, max(10, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    $status = $_GET['status'] ?? 'flagged'; // flagged, all
    $category = $_GET['category'] ?? '';
    $sortBy = $_GET['sort_by'] ?? 'upload_date';
    $sortOrder = strtoupper($_GET['sort_order'] ?? 'DESC');
    
    // Validate sort parameters
    $allowedSortFields = ['upload_date', 'prediction_confidence', 'user_id', 'tutorial_id'];
    if (!in_array($sortBy, $allowedSortFields)) {
        $sortBy = 'upload_date';
    }
    
    if (!in_array($sortOrder, ['ASC', 'DESC'])) {
        $sortOrder = 'DESC';
    }
    
    // Build query for AI-flagged submissions ONLY
    $whereConditions = [];
    $params = [];
    
    // CRITICAL: Only show flagged submissions (not auto-approved or auto-rejected)
    if ($status === 'flagged') {
        $whereConditions[] = "pu.ai_validation_status = 'flagged' AND pu.requires_admin_review = 1";
    } else {
        // Show all submissions that require review (flagged + errors)
        $whereConditions[] = "pu.requires_admin_review = 1";
    }
    
    // Filter by category if specified
    if (!empty($category)) {
        $whereConditions[] = "cv.predicted_category = ?";
        $params[] = $category;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get flagged submissions with AI evidence
    $query = "
        SELECT 
            pu.id as upload_id,
            pu.user_id,
            pu.tutorial_id,
            pu.description,
            pu.images,
            pu.status,
            pu.ai_validation_status,
            pu.requires_admin_review,
            pu.upload_date,
            pu.reviewed_date,
            pu.admin_feedback,
            
            u.email as user_email,
            u.name as user_name,
            
            t.title as tutorial_title,
            t.category as tutorial_category,
            
            cv.predicted_category,
            cv.prediction_confidence,
            cv.category_matches,
            cv.ai_decision,
            cv.decision_reasons,
            cv.all_predictions,
            cv.classification_data,
            cv.admin_decision,
            cv.admin_notes,
            cv.reviewed_by,
            cv.reviewed_at,
            cv.created_at as validation_date
            
        FROM practice_uploads_v3 pu
        LEFT JOIN users u ON pu.user_id = u.id
        LEFT JOIN tutorials t ON pu.tutorial_id = t.id
        LEFT JOIN craft_image_validation_v2 cv ON CONCAT(pu.id, '_0') = cv.image_id AND cv.image_type = 'practice_upload'
        
        $whereClause
        
        ORDER BY pu.$sortBy $sortOrder
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $countQuery = "
        SELECT COUNT(DISTINCT pu.id) as total
        FROM practice_uploads_v3 pu
        LEFT JOIN craft_image_validation_v2 cv ON CONCAT(pu.id, '_0') = cv.image_id AND cv.image_type = 'practice_upload'
        $whereClause
    ";
    
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Process submissions to include AI evidence
    $processedSubmissions = [];
    
    foreach ($submissions as $submission) {
        // Parse images JSON
        $images = json_decode($submission['images'], true) ?: [];
        
        // Parse AI predictions
        $allPredictions = json_decode($submission['all_predictions'], true) ?: [];
        $decisionReasons = json_decode($submission['decision_reasons'], true) ?: [];
        $classificationData = json_decode($submission['classification_data'], true) ?: [];
        
        // Extract AI evidence for admin review
        $aiEvidence = [
            'predicted_category' => $submission['predicted_category'],
            'prediction_confidence' => floatval($submission['prediction_confidence']),
            'category_matches' => boolval($submission['category_matches']),
            'ai_decision' => $submission['ai_decision'],
            'decision_reasons' => $decisionReasons,
            'all_predictions' => $allPredictions,
            'confidence_level' => $this->getConfidenceLevel($submission['prediction_confidence']),
            'explanation' => $classificationData['validation_decision']['explanation'] ?? 'No explanation available'
        ];
        
        // Determine why this was flagged
        $flagReason = $this->determineFlagReason($aiEvidence, $submission['tutorial_category']);
        
        $processedSubmission = [
            'upload_id' => $submission['upload_id'],
            'user_info' => [
                'user_id' => $submission['user_id'],
                'email' => $submission['user_email'],
                'name' => $submission['user_name']
            ],
            'tutorial_info' => [
                'tutorial_id' => $submission['tutorial_id'],
                'title' => $submission['tutorial_title'],
                'category' => $submission['tutorial_category']
            ],
            'submission_info' => [
                'description' => $submission['description'],
                'upload_date' => $submission['upload_date'],
                'status' => $submission['status'],
                'ai_validation_status' => $submission['ai_validation_status'],
                'requires_admin_review' => boolval($submission['requires_admin_review'])
            ],
            'images' => $images,
            'ai_evidence' => $aiEvidence,
            'flag_reason' => $flagReason,
            'admin_review' => [
                'admin_decision' => $submission['admin_decision'],
                'admin_notes' => $submission['admin_notes'],
                'reviewed_by' => $submission['reviewed_by'],
                'reviewed_at' => $submission['reviewed_at']
            ],
            'timestamps' => [
                'uploaded' => $submission['upload_date'],
                'ai_validated' => $submission['validation_date'],
                'admin_reviewed' => $submission['reviewed_date']
            ]
        ];
        
        $processedSubmissions[] = $processedSubmission;
    }
    
    // Get summary statistics
    $statsQuery = "
        SELECT 
            COUNT(*) as total_flagged,
            SUM(CASE WHEN pu.status = 'pending' THEN 1 ELSE 0 END) as pending_review,
            SUM(CASE WHEN pu.status = 'approved' THEN 1 ELSE 0 END) as approved_after_review,
            SUM(CASE WHEN pu.status = 'rejected' THEN 1 ELSE 0 END) as rejected_after_review,
            AVG(cv.prediction_confidence) as avg_confidence,
            COUNT(DISTINCT cv.predicted_category) as unique_categories
        FROM practice_uploads_v3 pu
        LEFT JOIN craft_image_validation_v2 cv ON CONCAT(pu.id, '_0') = cv.image_id AND cv.image_type = 'practice_upload'
        WHERE pu.requires_admin_review = 1
    ";
    
    $statsStmt = $pdo->query($statsQuery);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get category distribution for flagged submissions
    $categoryQuery = "
        SELECT 
            cv.predicted_category,
            COUNT(*) as count,
            AVG(cv.prediction_confidence) as avg_confidence
        FROM practice_uploads_v3 pu
        LEFT JOIN craft_image_validation_v2 cv ON CONCAT(pu.id, '_0') = cv.image_id AND cv.image_type = 'practice_upload'
        WHERE pu.requires_admin_review = 1 AND cv.predicted_category IS NOT NULL
        GROUP BY cv.predicted_category
        ORDER BY count DESC
    ";
    
    $categoryStmt = $pdo->query($categoryQuery);
    $categoryDistribution = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Response
    $response = [
        'success' => true,
        'data' => $processedSubmissions,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => intval($totalCount),
            'total_pages' => ceil($totalCount / $limit),
            'has_next' => ($page * $limit) < $totalCount,
            'has_prev' => $page > 1
        ],
        'filters' => [
            'status' => $status,
            'category' => $category,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder
        ],
        'statistics' => [
            'total_flagged' => intval($stats['total_flagged']),
            'pending_review' => intval($stats['pending_review']),
            'approved_after_review' => intval($stats['approved_after_review']),
            'rejected_after_review' => intval($stats['rejected_after_review']),
            'avg_confidence' => round(floatval($stats['avg_confidence']), 3),
            'unique_categories' => intval($stats['unique_categories'])
        ],
        'category_distribution' => $categoryDistribution,
        'system_info' => [
            'version' => 'craft_validation_dashboard_v3.0',
            'mode' => 'flagged_submissions_only',
            'auto_approved_bypass' => true,
            'auto_rejected_hidden' => true,
            'human_in_the_loop' => true
        ],
        'admin_guidance' => [
            'purpose' => 'Review AI-flagged submissions that require human judgment',
            'auto_approved' => 'Auto-approved submissions do not appear here',
            'auto_rejected' => 'Auto-rejected submissions do not appear here',
            'decision_options' => ['approve', 'reject'],
            'ai_evidence' => 'Use AI predictions and confidence scores to inform your decision'
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Craft validation dashboard error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load dashboard: ' . $e->getMessage()
    ]);
}

/**
 * Get confidence level description
 */
function getConfidenceLevel($confidence) {
    $conf = floatval($confidence);
    if ($conf >= 0.7) return 'high';
    if ($conf >= 0.4) return 'medium';
    if ($conf >= 0.1) return 'low';
    return 'very_low';
}

/**
 * Determine why this submission was flagged
 */
function determineFlagReason($aiEvidence, $tutorialCategory) {
    $confidence = $aiEvidence['prediction_confidence'];
    $categoryMatches = $aiEvidence['category_matches'];
    $predictedCategory = $aiEvidence['predicted_category'];
    
    if (!$categoryMatches && $confidence >= 0.4) {
        return "Category mismatch: AI predicted '$predictedCategory' but tutorial is '$tutorialCategory' (confidence: " . round($confidence * 100, 1) . "%)";
    }
    
    if ($confidence < 0.4) {
        return "Low AI confidence: " . round($confidence * 100, 1) . "% - unclear what the image contains";
    }
    
    if (!empty($aiEvidence['decision_reasons'])) {
        return implode('; ', $aiEvidence['decision_reasons']);
    }
    
    return "Flagged for manual review";
}
?>