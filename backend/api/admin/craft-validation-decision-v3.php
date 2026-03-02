<?php
/**
 * Craft Validation Decision API V3 - Production Version
 * Handles admin approval/rejection decisions for AI-flagged submissions only
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Only POST method allowed'
    ]);
    exit;
}

try {
    require_once '../../config/database.php';
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON input'
        ]);
        exit;
    }
    
    $uploadId = $input['upload_id'] ?? null;
    $decision = $input['decision'] ?? null;
    $adminNotes = $input['admin_notes'] ?? '';
    $adminId = $input['admin_id'] ?? 1; // TODO: Get from session/auth
    
    // Validate input
    if (!$uploadId || !$decision) {
        echo json_encode([
            'success' => false,
            'message' => 'upload_id and decision are required'
        ]);
        exit;
    }
    
    if (!in_array($decision, ['approved', 'rejected'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Decision must be "approved" or "rejected"'
        ]);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Get submission details - ONLY flagged submissions can be decided
        $stmt = $pdo->prepare("
            SELECT pu.*, u.email as user_email, t.title as tutorial_title, t.category as tutorial_category
            FROM practice_uploads_v3 pu
            LEFT JOIN users u ON pu.user_id = u.id
            LEFT JOIN tutorials t ON pu.tutorial_id = t.id
            WHERE pu.id = ? AND pu.requires_admin_review = 1 AND pu.ai_validation_status = 'flagged'
        ");
        $stmt->execute([$uploadId]);
        $submission = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$submission) {
            throw new Exception('Flagged submission not found or not eligible for admin decision');
        }
        
        // Verify this submission was actually flagged by AI (security check)
        if ($submission['ai_validation_status'] !== 'flagged') {
            throw new Exception('Only AI-flagged submissions can be manually decided');
        }
        
        // Update practice upload status
        $stmt = $pdo->prepare("
            UPDATE practice_uploads_v3 
            SET status = ?, 
                admin_feedback = ?, 
                reviewed_date = NOW(),
                progress_approved = ?,
                requires_admin_review = 0
            WHERE id = ?
        ");
        $progressApproved = ($decision === 'approved') ? 1 : 0;
        $stmt->execute([$decision, $adminNotes, $progressApproved, $uploadId]);
        
        // Update craft validation record with admin decision
        $images = json_decode($submission['images'], true) ?: [];
        
        foreach ($images as $index => $image) {
            $imageId = $uploadId . '_' . $index;
            
            // Update craft validation record
            $stmt = $pdo->prepare("
                UPDATE craft_image_validation_v2 
                SET admin_decision = ?, 
                    admin_notes = ?, 
                    reviewed_by = ?, 
                    reviewed_at = NOW()
                WHERE image_id = ? AND image_type = 'practice_upload'
            ");
            $stmt->execute([$decision, $adminNotes, $adminId, $imageId]);
        }
        
        // Update learning progress based on admin decision
        if ($decision === 'approved') {
            $stmt = $pdo->prepare("
                INSERT INTO learning_progress 
                (user_id, tutorial_id, practice_uploaded, practice_completed, practice_admin_approved, last_accessed)
                VALUES (?, ?, 1, 1, 1, NOW())
                ON DUPLICATE KEY UPDATE 
                practice_completed = 1,
                practice_admin_approved = 1,
                last_accessed = NOW()
            ");
            $stmt->execute([$submission['user_id'], $submission['tutorial_id']]);
        } else {
            // If rejected, mark as not completed
            $stmt = $pdo->prepare("
                UPDATE learning_progress 
                SET practice_completed = 0, 
                    practice_admin_approved = 0,
                    last_accessed = NOW()
                WHERE user_id = ? AND tutorial_id = ?
            ");
            $stmt->execute([$submission['user_id'], $submission['tutorial_id']]);
        }
        
        // Log admin action
        $stmt = $pdo->prepare("
            INSERT INTO admin_actions_log_v3 
            (admin_id, action_type, target_type, target_id, details, created_at)
            VALUES (?, 'craft_validation_decision_v3', 'practice_upload', ?, ?, NOW())
        ");
        
        // Create admin actions log table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_actions_log_v3 (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                action_type VARCHAR(50) NOT NULL,
                target_type VARCHAR(50) NOT NULL,
                target_id VARCHAR(100) NOT NULL,
                details JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_admin_action (admin_id, action_type),
                INDEX idx_target (target_type, target_id)
            )
        ");
        
        // Get AI evidence for logging
        $aiEvidenceStmt = $pdo->prepare("
            SELECT predicted_category, prediction_confidence, category_matches, ai_decision, decision_reasons
            FROM craft_image_validation_v2 
            WHERE image_id = ? AND image_type = 'practice_upload'
            LIMIT 1
        ");
        $aiEvidenceStmt->execute([$uploadId . '_0']);
        $aiEvidence = $aiEvidenceStmt->fetch(PDO::FETCH_ASSOC);
        
        $logDetails = json_encode([
            'admin_decision' => $decision,
            'admin_notes' => $adminNotes,
            'user_email' => $submission['user_email'],
            'tutorial_title' => $submission['tutorial_title'],
            'tutorial_category' => $submission['tutorial_category'],
            'images_count' => count($images),
            'ai_evidence' => $aiEvidence,
            'original_ai_decision' => 'flag-for-review',
            'human_override' => true
        ]);
        
        $stmt->execute([$adminId, $uploadId, $logDetails]);
        
        // Send notification to user
        if ($decision === 'approved') {
            $notificationTitle = 'Practice Work Approved! 🎉';
            $notificationMessage = "Your practice submission for \"{$submission['tutorial_title']}\" has been approved after admin review! Great work!";
        } else {
            $notificationTitle = 'Practice Work Needs Revision';
            $notificationMessage = "Your practice submission for \"{$submission['tutorial_title']}\" needs revision. Please check the feedback and resubmit.";
        }
        
        // Insert notification
        $stmt = $pdo->prepare("
            INSERT INTO notifications 
            (user_id, title, message, type, action_url, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $notificationType = ($decision === 'approved') ? 'success' : 'warning';
        $actionUrl = '/pro-dashboard';
        
        $stmt->execute([
            $submission['user_id'], 
            $notificationTitle, 
            $notificationMessage, 
            $notificationType, 
            $actionUrl
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        // Get updated statistics for response
        $statsStmt = $pdo->query("
            SELECT 
                COUNT(*) as total_flagged,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_review
            FROM practice_uploads_v3 
            WHERE requires_admin_review = 1 AND ai_validation_status = 'flagged'
        ");
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => "AI-flagged submission {$decision} successfully",
            'decision' => $decision,
            'upload_id' => $uploadId,
            'user_email' => $submission['user_email'],
            'tutorial_title' => $submission['tutorial_title'],
            'tutorial_category' => $submission['tutorial_category'],
            'admin_notes' => $adminNotes,
            'ai_evidence' => $aiEvidence,
            'human_override' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'remaining_flagged' => [
                'total_flagged' => intval($stats['total_flagged']),
                'pending_review' => intval($stats['pending_review'])
            ],
            'system_info' => [
                'version' => 'craft_validation_decision_v3.0',
                'mode' => 'human_in_the_loop_review',
                'ai_decision_overridden' => true
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Craft validation decision V3 error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process decision: ' . $e->getMessage()
    ]);
}
?>