<?php
/**
 * Craft Validation Submissions API
 * Provides detailed submission data for admin review
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    require_once '../../config/database.php';
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get filter parameters
    $statusFilter = $_GET['status'] ?? '';
    $categoryFilter = $_GET['category'] ?? '';
    $dateFilter = $_GET['date'] ?? '';
    $limit = min(intval($_GET['limit'] ?? 50), 100); // Max 100 submissions
    $offset = intval($_GET['offset'] ?? 0);
    
    // Build WHERE clause
    $whereConditions = [];
    $params = [];
    
    // Status filter
    if ($statusFilter) {
        switch ($statusFilter) {
            case 'pending':
                $whereConditions[] = "(pu.authenticity_status = 'flagged' OR pu.craft_validation_status = 'flagged') AND pu.status = 'pending'";
                break;
            case 'flagged':
                $whereConditions[] = "pu.craft_validation_status = 'flagged'";
                break;
            case 'rejected':
                $whereConditions[] = "pu.status = 'rejected'";
                break;
            case 'approved':
                $whereConditions[] = "pu.status = 'approved'";
                break;
        }
    }
    
    // Category filter
    if ($categoryFilter) {
        $whereConditions[] = "t.category LIKE ?";
        $params[] = "%{$categoryFilter}%";
    }
    
    // Date filter
    if ($dateFilter) {
        $whereConditions[] = "DATE(pu.upload_date) = ?";
        $params[] = $dateFilter;
    }
    
    $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get submissions with related data
    $stmt = $pdo->prepare("
        SELECT 
            pu.id as upload_id,
            pu.user_id,
            pu.tutorial_id,
            pu.description,
            pu.images,
            pu.status,
            pu.authenticity_status,
            pu.craft_validation_status,
            pu.admin_feedback,
            pu.upload_date,
            pu.reviewed_date,
            u.email as user_email,
            t.title as tutorial_title,
            t.category as tutorial_category,
            CASE 
                WHEN pu.status = 'rejected' THEN 'rejected'
                WHEN pu.status = 'approved' THEN 'approved'
                WHEN pu.authenticity_status = 'flagged' OR pu.craft_validation_status = 'flagged' THEN 'flagged'
                ELSE 'pending'
            END as overall_status
        FROM practice_uploads pu
        LEFT JOIN users u ON pu.user_id = u.id
        LEFT JOIN tutorials t ON pu.tutorial_id = t.id
        {$whereClause}
        ORDER BY pu.upload_date DESC
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get detailed validation data for each submission
    foreach ($submissions as &$submission) {
        $submission['images'] = json_decode($submission['images'], true) ?: [];
        
        // Get validation details for each image
        foreach ($submission['images'] as &$image) {
            $imageId = $submission['upload_id'] . '_' . array_search($image, $submission['images']);
            
            // Get authenticity data
            $authStmt = $pdo->prepare("
                SELECT * FROM image_authenticity_v2 
                WHERE image_id = ? AND image_type = 'practice_upload'
            ");
            $authStmt->execute([$imageId]);
            $authenticity = $authStmt->fetch(PDO::FETCH_ASSOC);
            
            // Get craft validation data
            $craftStmt = $pdo->prepare("
                SELECT * FROM craft_image_validation 
                WHERE image_id = ? AND image_type = 'practice_upload'
            ");
            $craftStmt->execute([$imageId]);
            $craftValidation = $craftStmt->fetch(PDO::FETCH_ASSOC);
            
            // Add validation data to image
            $image['image_id'] = $imageId;
            $image['authenticity'] = $authenticity ?: null;
            $image['craft_validation'] = $craftValidation ? [
                'validation_status' => $craftValidation['validation_status'],
                'predicted_category' => $craftValidation['predicted_category'],
                'prediction_confidence' => floatval($craftValidation['prediction_confidence']),
                'category_matches' => boolval($craftValidation['category_matches']),
                'ai_generated_detected' => boolval($craftValidation['ai_generated_detected']),
                'ai_generator' => $craftValidation['ai_generator'],
                'ai_confidence' => $craftValidation['ai_confidence'],
                'rejection_reason' => $craftValidation['rejection_reason'],
                'flag_reason' => $craftValidation['flag_reason'],
                'craft_classification' => json_decode($craftValidation['all_predictions'], true) ? [
                    'success' => true,
                    'predicted_category' => $craftValidation['predicted_category'],
                    'confidence' => floatval($craftValidation['prediction_confidence']),
                    'all_predictions' => json_decode($craftValidation['all_predictions'], true)
                ] : null,
                'category_match' => [
                    'matches' => boolval($craftValidation['category_matches']),
                    'selected_category' => $submission['tutorial_category'],
                    'predicted_category' => $craftValidation['predicted_category'],
                    'confidence' => floatval($craftValidation['prediction_confidence'])
                ],
                'ai_generated_detection' => [
                    'is_ai_generated' => boolval($craftValidation['ai_generated_detected']),
                    'detected_generator' => $craftValidation['ai_generator'],
                    'confidence' => $craftValidation['ai_confidence'],
                    'evidence' => json_decode($craftValidation['ai_evidence'], true) ?: [],
                    'metadata_analysis' => json_decode($craftValidation['metadata_analysis'], true) ?: []
                ]
            ] : null;
        }
    }
    
    // Get total count for pagination
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM practice_uploads pu
        LEFT JOIN users u ON pu.user_id = u.id
        LEFT JOIN tutorials t ON pu.tutorial_id = t.id
        {$whereClause}
    ");
    
    $countParams = array_slice($params, 0, -2); // Remove limit and offset
    $countStmt->execute($countParams);
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'submissions' => $submissions,
        'pagination' => [
            'total' => intval($totalCount),
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $totalCount
        ],
        'filters' => [
            'status' => $statusFilter,
            'category' => $categoryFilter,
            'date' => $dateFilter
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Craft validation submissions error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load submissions: ' . $e->getMessage()
    ]);
}
?>