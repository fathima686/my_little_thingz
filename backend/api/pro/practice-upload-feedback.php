<?php
/**
 * Practice Upload Feedback API
 * 
 * Provides detailed feedback for learner's practice uploads
 * Includes AI validation decisions, rejection reasons, and admin feedback
 * Designed for transparency in AI decision-making
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email');

try {
    require_once '../../config/database.php';
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get user email from header or query parameter
    $userEmail = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? $_GET['email'] ?? '';
    
    if (empty($userEmail)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Email is required'
        ]);
        exit;
    }
    
    // Get user ID
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $userStmt->execute([$userEmail]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found'
        ]);
        exit;
    }
    
    $userId = $user['id'];
    
    // Optional filters
    $tutorialId = $_GET['tutorial_id'] ?? null;
    $uploadId = $_GET['upload_id'] ?? null;
    $limit = min(intval($_GET['limit'] ?? 20), 50);
    
    // Build query
    $whereConditions = ['pu.user_id = ?'];
    $params = [$userId];
    
    if ($tutorialId) {
        $whereConditions[] = 'pu.tutorial_id = ?';
        $params[] = $tutorialId;
    }
    
    if ($uploadId) {
        $whereConditions[] = 'pu.id = ?';
        $params[] = $uploadId;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Fetch practice uploads with detailed feedback
    $stmt = $pdo->prepare("
        SELECT 
            pu.id as upload_id,
            pu.tutorial_id,
            pu.description,
            pu.images,
            pu.status,
            pu.authenticity_status,
            pu.craft_validation_status,
            pu.admin_feedback,
            pu.upload_date,
            pu.reviewed_date,
            t.title as tutorial_title,
            t.category as tutorial_category,
            
            -- Get AI validation data from first image
            cv.predicted_category,
            cv.prediction_confidence,
            cv.category_matches,
            cv.ai_decision,
            cv.requires_review,
            cv.decision_reasons,
            cv.ai_risk_score,
            cv.ai_risk_level,
            cv.ai_detection_decision,
            cv.ai_detection_evidence,
            cv.metadata_ai_keywords,
            cv.exif_camera_present,
            cv.texture_laplacian_variance,
            cv.watermark_detected,
            cv.created_at as validation_date
            
        FROM practice_uploads pu
        LEFT JOIN tutorials t ON pu.tutorial_id = t.id
        LEFT JOIN craft_image_validation_v2 cv ON CONCAT(pu.id, '_0') = cv.image_id AND cv.image_type = 'practice_upload'
        WHERE {$whereClause}
        ORDER BY pu.upload_date DESC
        LIMIT ?
    ");
    
    $params[] = $limit;
    $stmt->execute($params);
    $uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process each upload to create structured feedback
    $feedbackData = [];
    
    foreach ($uploads as $upload) {
        $images = json_decode($upload['images'], true) ?: [];
        $decisionReasons = json_decode($upload['decision_reasons'], true) ?: [];
        $aiDetectionEvidence = json_decode($upload['ai_detection_evidence'], true) ?: null;
        $metadataKeywords = json_decode($upload['metadata_ai_keywords'], true) ?: [];
        
        // Determine overall status
        $overallStatus = determineOverallStatus($upload);
        
        // Build structured feedback
        $feedback = [
            'upload_id' => $upload['upload_id'],
            'tutorial_id' => $upload['tutorial_id'],
            'tutorial_title' => $upload['tutorial_title'],
            'tutorial_category' => $upload['tutorial_category'],
            'upload_date' => $upload['upload_date'],
            'reviewed_date' => $upload['reviewed_date'],
            'images_count' => count($images),
            'images' => $images,
            
            // Overall status
            'overall_status' => $overallStatus['status'],
            'status_label' => $overallStatus['label'],
            'status_color' => $overallStatus['color'],
            'status_icon' => $overallStatus['icon'],
            
            // Detailed status breakdown
            'status_details' => [
                'upload_status' => $upload['status'],
                'craft_validation_status' => $upload['craft_validation_status'],
                'authenticity_status' => $upload['authenticity_status']
            ],
            
            // AI Validation Results
            'ai_validation' => [
                'decision' => $upload['ai_decision'],
                'predicted_category' => $upload['predicted_category'],
                'confidence' => $upload['prediction_confidence'] ? round(floatval($upload['prediction_confidence']) * 100, 1) : null,
                'category_matches' => (bool)$upload['category_matches'],
                'requires_review' => (bool)$upload['requires_review'],
                'reasons' => $decisionReasons,
                'validation_date' => $upload['validation_date']
            ],
            
            // AI Detection Results (if available)
            'ai_detection' => $upload['ai_risk_score'] ? [
                'risk_score' => intval($upload['ai_risk_score']),
                'risk_level' => $upload['ai_risk_level'],
                'decision' => $upload['ai_detection_decision'],
                'metadata_keywords_found' => !empty($metadataKeywords),
                'exif_camera_present' => $upload['exif_camera_present'] !== null ? (bool)$upload['exif_camera_present'] : null,
                'texture_variance' => $upload['texture_laplacian_variance'] ? floatval($upload['texture_laplacian_variance']) : null,
                'watermark_detected' => (bool)$upload['watermark_detected'],
                'evidence' => $aiDetectionEvidence
            ] : null,
            
            // Feedback messages
            'feedback' => buildFeedbackMessages($upload, $overallStatus, $decisionReasons),
            
            // Admin feedback (if any)
            'admin_feedback' => $upload['admin_feedback'],
            
            // Action items for learner
            'action_items' => buildActionItems($upload, $overallStatus)
        ];
        
        $feedbackData[] = $feedback;
    }
    
    echo json_encode([
        'status' => 'success',
        'user_email' => $userEmail,
        'total_uploads' => count($feedbackData),
        'uploads' => $feedbackData,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Practice feedback error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch feedback: ' . $e->getMessage()
    ]);
}

/**
 * Determine overall status with visual indicators
 */
function determineOverallStatus($upload) {
    $status = $upload['status'];
    $craftStatus = $upload['craft_validation_status'];
    
    if ($status === 'approved') {
        return [
            'status' => 'approved',
            'label' => 'Approved',
            'color' => 'green',
            'icon' => 'check-circle',
            'message' => 'Your practice work has been approved!'
        ];
    } elseif ($status === 'rejected') {
        return [
            'status' => 'rejected',
            'label' => 'Rejected',
            'color' => 'red',
            'icon' => 'x-circle',
            'message' => 'Your practice work was not approved'
        ];
    } elseif ($craftStatus === 'flagged' || $status === 'pending') {
        return [
            'status' => 'under_review',
            'label' => 'Under Review',
            'color' => 'yellow',
            'icon' => 'clock',
            'message' => 'Your practice work is being reviewed by our team'
        ];
    } else {
        return [
            'status' => 'pending',
            'label' => 'Pending',
            'color' => 'gray',
            'icon' => 'clock',
            'message' => 'Your practice work is pending validation'
        ];
    }
}

/**
 * Build detailed feedback messages
 */
function buildFeedbackMessages($upload, $overallStatus, $decisionReasons) {
    $messages = [
        'primary' => $overallStatus['message'],
        'details' => [],
        'ai_explanation' => null,
        'next_steps' => null
    ];
    
    // Approved
    if ($overallStatus['status'] === 'approved') {
        $messages['details'][] = '✅ Your submission passed all validation checks';
        $messages['details'][] = '✅ Your learning progress has been updated';
        
        if ($upload['predicted_category']) {
            $confidence = round(floatval($upload['prediction_confidence']) * 100, 1);
            $messages['ai_explanation'] = "AI classified your work as '{$upload['predicted_category']}' with {$confidence}% confidence, matching the tutorial category.";
        }
        
        $messages['next_steps'] = 'Continue to the next tutorial or practice more!';
    }
    
    // Rejected
    elseif ($overallStatus['status'] === 'rejected') {
        $messages['details'][] = '❌ Your submission did not meet validation criteria';
        
        // Add specific rejection reasons
        if (!empty($decisionReasons)) {
            foreach ($decisionReasons as $reason) {
                $messages['details'][] = '• ' . $reason;
            }
        }
        
        // AI explanation
        if ($upload['predicted_category']) {
            $confidence = round(floatval($upload['prediction_confidence']) * 100, 1);
            
            if (!$upload['category_matches']) {
                $messages['ai_explanation'] = "AI detected your work as '{$upload['predicted_category']}' ({$confidence}% confidence), which doesn't match the tutorial category '{$upload['tutorial_category']}'.";
            } else {
                $messages['ai_explanation'] = "AI validation confidence was too low ({$confidence}%) to approve automatically.";
            }
        }
        
        // AI detection explanation
        if ($upload['ai_risk_score'] && intval($upload['ai_risk_score']) >= 70) {
            $messages['details'][] = '⚠️ AI-generated image detected (risk score: ' . $upload['ai_risk_score'] . '/100)';
            $messages['ai_explanation'] = ($messages['ai_explanation'] ?? '') . ' Additionally, the image showed characteristics of AI-generated content.';
        }
        
        $messages['next_steps'] = 'Please upload a new image that matches the tutorial category and is your own work.';
    }
    
    // Under Review
    elseif ($overallStatus['status'] === 'under_review') {
        $messages['details'][] = '⏳ Your submission is being reviewed by our team';
        $messages['details'][] = '📧 You will receive feedback within 24-48 hours';
        
        if (!empty($decisionReasons)) {
            $messages['details'][] = 'Flagged for review because:';
            foreach ($decisionReasons as $reason) {
                $messages['details'][] = '• ' . $reason;
            }
        }
        
        if ($upload['predicted_category']) {
            $confidence = round(floatval($upload['prediction_confidence']) * 100, 1);
            $messages['ai_explanation'] = "AI classified your work as '{$upload['predicted_category']}' with {$confidence}% confidence. Manual review is needed to confirm.";
        }
        
        $messages['next_steps'] = 'No action needed. Wait for admin review.';
    }
    
    return $messages;
}

/**
 * Build action items for learner
 */
function buildActionItems($upload, $overallStatus) {
    $actions = [];
    
    if ($overallStatus['status'] === 'approved') {
        $actions[] = [
            'type' => 'success',
            'action' => 'continue',
            'label' => 'Continue Learning',
            'description' => 'Move on to the next tutorial'
        ];
    } elseif ($overallStatus['status'] === 'rejected') {
        $actions[] = [
            'type' => 'retry',
            'action' => 'reupload',
            'label' => 'Upload New Image',
            'description' => 'Submit a new practice image that addresses the feedback'
        ];
        $actions[] = [
            'type' => 'help',
            'action' => 'view_guidelines',
            'label' => 'View Guidelines',
            'description' => 'Review submission guidelines and requirements'
        ];
    } elseif ($overallStatus['status'] === 'under_review') {
        $actions[] = [
            'type' => 'wait',
            'action' => 'check_later',
            'label' => 'Check Back Later',
            'description' => 'Review will be completed within 24-48 hours'
        ];
    }
    
    return $actions;
}
?>
