<?php
/**
 * Craft Validation Decision API
 * Handles admin approval/rejection decisions
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
        // Get submission details
        $stmt = $pdo->prepare("
            SELECT pu.*, u.email as user_email, t.title as tutorial_title
            FROM practice_uploads pu
            LEFT JOIN users u ON pu.user_id = u.id
            LEFT JOIN tutorials t ON pu.tutorial_id = t.id
            WHERE pu.id = ?
        ");
        $stmt->execute([$uploadId]);
        $submission = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$submission) {
            throw new Exception('Submission not found');
        }
        
        // Update practice upload status
        $stmt = $pdo->prepare("
            UPDATE practice_uploads 
            SET status = ?, 
                admin_feedback = ?, 
                reviewed_date = NOW(),
                progress_approved = ?
            WHERE id = ?
        ");
        $progressApproved = ($decision === 'approved') ? 1 : 0;
        $stmt->execute([$decision, $adminNotes, $progressApproved, $uploadId]);
        
        // Update all related image validation records
        $images = json_decode($submission['images'], true) ?: [];
        
        foreach ($images as $index => $image) {
            $imageId = $uploadId . '_' . $index;
            
            // Update authenticity record
            $stmt = $pdo->prepare("
                UPDATE image_authenticity_v2 
                SET admin_decision = ?, 
                    admin_notes = ?, 
                    reviewed_by = ?, 
                    reviewed_at = NOW()
                WHERE image_id = ? AND image_type = 'practice_upload'
            ");
            $stmt->execute([$decision, $adminNotes, $adminId, $imageId]);
            
            // Update craft validation record
            $stmt = $pdo->prepare("
                UPDATE craft_image_validation 
                SET validation_status = ?, 
                    flag_reason = ?, 
                    updated_at = NOW()
                WHERE image_id = ? AND image_type = 'practice_upload'
            ");
            $finalStatus = ($decision === 'approved') ? 'approved' : 'rejected';
            $stmt->execute([$finalStatus, $adminNotes, $imageId]);
            
            // Update admin review queue
            $stmt = $pdo->prepare("
                UPDATE admin_review_v2 
                SET admin_decision = ?, 
                    admin_notes = ?, 
                    reviewed_by = ?, 
                    reviewed_at = NOW()
                WHERE image_id = ? AND image_type = 'practice_upload'
            ");
            $stmt->execute([$decision, $adminNotes, $adminId, $imageId]);
        }
        
        // Update learning progress if approved
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
            INSERT INTO admin_actions_log 
            (admin_id, action_type, target_type, target_id, details, created_at)
            VALUES (?, 'craft_validation_decision', 'practice_upload', ?, ?, NOW())
        ");
        
        // Create admin actions log table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_actions_log (
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
        
        $logDetails = json_encode([
            'decision' => $decision,
            'admin_notes' => $adminNotes,
            'user_email' => $submission['user_email'],
            'tutorial_title' => $submission['tutorial_title'],
            'images_count' => count($images)
        ]);
        
        $stmt->execute([$adminId, $uploadId, $logDetails]);
        
        // Send notification to user (optional)
        if ($decision === 'approved') {
            $notificationTitle = 'Practice Work Approved! 🎉';
            $notificationMessage = "Your practice submission for \"{$submission['tutorial_title']}\" has been approved! Great work!";
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
        
        echo json_encode([
            'success' => true,
            'message' => "Submission {$decision} successfully",
            'decision' => $decision,
            'upload_id' => $uploadId,
            'user_email' => $submission['user_email'],
            'tutorial_title' => $submission['tutorial_title'],
            'admin_notes' => $adminNotes,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Craft validation decision error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process decision: ' . $e->getMessage()
    ]);
}
?>