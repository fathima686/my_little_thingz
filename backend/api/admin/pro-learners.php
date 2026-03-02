<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID, X-Admin-Token, X-Admin-User-Id, X-Admin-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config/database.php';
require_once '../../models/FeatureAccessControl.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $featureControl = new FeatureAccessControl($db);

    // Simple admin authentication (you can enhance this)
    $adminToken = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? null;
    if (!$adminToken || $adminToken !== 'admin_secret_token') {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Admin authentication required']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? 'list_learners';

        if ($action === 'list_learners') {
            // Get all Pro users with their progress
            $stmt = $db->prepare("
                SELECT DISTINCT u.id, u.first_name, u.last_name, u.email,
                       s.status as subscription_status, sp.plan_code,
                       COUNT(DISTINCT lp.tutorial_id) as tutorials_started,
                       COUNT(DISTINCT CASE WHEN lp.completion_percentage >= 80 THEN lp.tutorial_id END) as tutorials_completed,
                       COUNT(DISTINCT pu.id) as practice_uploads,
                       COUNT(DISTINCT CASE WHEN pu.status = 'approved' THEN pu.id END) as approved_uploads,
                       MAX(lp.last_accessed) as last_activity
                FROM users u
                JOIN subscriptions s ON u.id = s.user_id
                JOIN subscription_plans sp ON s.plan_id = sp.id
                LEFT JOIN learning_progress lp ON u.id = lp.user_id
                LEFT JOIN practice_uploads pu ON u.id = pu.user_id
                WHERE sp.plan_code = 'pro' 
                AND s.status IN ('active', 'authenticated', 'pending')
                GROUP BY u.id, u.first_name, u.last_name, u.email, s.status, sp.plan_code
                ORDER BY last_activity DESC
            ");
            $stmt->execute();
            $learners = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate progress for each learner
            foreach ($learners as &$learner) {
                $progress = $featureControl->calculateLearningProgress($learner['id']);
                $learner['overall_progress'] = $progress;
                $learner['certificate_eligible'] = $progress['certificate_eligible'];
            }

            echo json_encode([
                'status' => 'success',
                'learners' => $learners
            ]);

        } elseif ($action === 'learner_detail') {
            $learnerId = $_GET['learner_id'] ?? null;
            
            if (!$learnerId) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Learner ID required']);
                exit;
            }

            // Get learner info
            $learnerStmt = $db->prepare("
                SELECT u.*, sp.plan_code, s.status as subscription_status
                FROM users u
                JOIN subscriptions s ON u.id = s.user_id
                JOIN subscription_plans sp ON s.plan_id = sp.id
                WHERE u.id = ? AND sp.plan_code = 'pro'
            ");
            $learnerStmt->execute([$learnerId]);
            $learner = $learnerStmt->fetch(PDO::FETCH_ASSOC);

            if (!$learner) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Pro learner not found']);
                exit;
            }

            // Get detailed progress
            $progress = $featureControl->calculateLearningProgress($learnerId);

            // Get practice uploads
            $uploadsStmt = $db->prepare("
                SELECT pu.*, t.title as tutorial_title
                FROM practice_uploads pu
                JOIN tutorials t ON pu.tutorial_id = t.id
                WHERE pu.user_id = ?
                ORDER BY pu.upload_date DESC
            ");
            $uploadsStmt->execute([$learnerId]);
            $uploads = $uploadsStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get tutorial progress
            $tutorialProgressStmt = $db->prepare("
                SELECT lp.*, t.title, t.category
                FROM learning_progress lp
                JOIN tutorials t ON lp.tutorial_id = t.id
                WHERE lp.user_id = ?
                ORDER BY lp.last_accessed DESC
            ");
            $tutorialProgressStmt->execute([$learnerId]);
            $tutorialProgress = $tutorialProgressStmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'learner' => $learner,
                'progress' => $progress,
                'uploads' => $uploads,
                'tutorial_progress' => $tutorialProgress
            ]);

        } elseif ($action === 'pending_uploads') {
            // Get practice uploads for review (support status filtering)
            // Admin should be able to review all uploads regardless of subscription status
            $status = $_GET['status'] ?? 'pending';
            
            $whereClause = "";
            if ($status !== 'all') {
                $whereClause = "WHERE pu.status = :status";
            }
            
            $stmt = $db->prepare("
                SELECT pu.*, u.first_name, u.last_name, u.email, t.title as tutorial_title
                FROM practice_uploads pu
                JOIN users u ON pu.user_id = u.id
                LEFT JOIN tutorials t ON pu.tutorial_id = t.id
                $whereClause
                ORDER BY pu.upload_date DESC
            ");
            
            if ($status !== 'all') {
                $stmt->bindParam(':status', $status);
            }
            $stmt->execute();
            $uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'pending_uploads' => $uploads,
                'total_count' => count($uploads),
                'filter_status' => $status
            ]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle different POST actions
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? 'review'; // Default to review action for backward compatibility
        
        if ($action === 'remove_practice_upload') {
            // Handle practice upload removal
            $uploadId = $data['upload_id'] ?? null;
            
            if (!$uploadId) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Upload ID required']);
                exit;
            }
            
            // Get upload details before deletion for file cleanup
            $uploadStmt = $db->prepare("SELECT * FROM practice_uploads WHERE id = ?");
            $uploadStmt->execute([$uploadId]);
            $upload = $uploadStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$upload) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Practice upload not found']);
                exit;
            }
            
            // Delete the database record first
            $deleteStmt = $db->prepare("DELETE FROM practice_uploads WHERE id = ?");
            $deleteResult = $deleteStmt->execute([$uploadId]);
            
            if (!$deleteResult) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete practice upload from database']);
                exit;
            }
            
            // Clean up associated files if they exist
            $filesDeleted = 0;
            $filesSkipped = 0;
            
            if (!empty($upload['images'])) {
                try {
                    $images = json_decode($upload['images'], true);
                    if (is_array($images)) {
                        foreach ($images as $image) {
                            if (isset($image['stored_name'])) {
                                // Try multiple possible paths for the uploaded files
                                $possiblePaths = [
                                    '../../uploads/practice/' . $image['stored_name'],
                                    '../../uploads/practice_uploads/' . $image['stored_name'],
                                    '../../uploads/' . $image['stored_name'],
                                    '../../' . $image['stored_name']
                                ];
                                
                                $fileDeleted = false;
                                foreach ($possiblePaths as $imagePath) {
                                    if (file_exists($imagePath)) {
                                        if (unlink($imagePath)) {
                                            $filesDeleted++;
                                            $fileDeleted = true;
                                            break;
                                        }
                                    }
                                }
                                
                                if (!$fileDeleted) {
                                    $filesSkipped++;
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    // Log the error but don't fail the deletion
                    error_log('Error cleaning up files for practice upload ' . $uploadId . ': ' . $e->getMessage());
                }
            }
            
            $message = 'Practice upload removed successfully';
            if ($filesDeleted > 0) {
                $message .= " ($filesDeleted file(s) deleted)";
            }
            if ($filesSkipped > 0) {
                $message .= " ($filesSkipped file(s) not found)";
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => $message,
                'files_deleted' => $filesDeleted,
                'files_skipped' => $filesSkipped
            ]);
            exit;
        }
        
        // Handle practice upload review (existing functionality)
        $uploadId = $data['upload_id'] ?? null;
        $status = $data['status'] ?? null; // 'approved' or 'rejected'
        $feedback = $data['admin_feedback'] ?? ''; // Match frontend parameter name
        $adminId = $data['admin_id'] ?? 1; // You should get this from admin session

        if (!$uploadId || !in_array($status, ['approved', 'rejected'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid upload ID or status']);
            exit;
        }

        // Update practice upload
        $updateStmt = $db->prepare("
            UPDATE practice_uploads 
            SET status = ?, admin_feedback = ?, reviewed_date = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $updateStmt->execute([$status, $feedback, $uploadId]);

        // Update learning progress if approved
        if ($status === 'approved') {
            $uploadStmt = $db->prepare("SELECT user_id, tutorial_id FROM practice_uploads WHERE id = ?");
            $uploadStmt->execute([$uploadId]);
            $upload = $uploadStmt->fetch(PDO::FETCH_ASSOC);

            if ($upload) {
                // Check if learning progress record exists, if not create it
                $checkProgressStmt = $db->prepare("
                    SELECT id FROM learning_progress 
                    WHERE user_id = ? AND tutorial_id = ?
                ");
                $checkProgressStmt->execute([$upload['user_id'], $upload['tutorial_id']]);
                
                if ($checkProgressStmt->fetch()) {
                    // Update existing record
                    $progressStmt = $db->prepare("
                        UPDATE learning_progress 
                        SET practice_uploaded = 1 
                        WHERE user_id = ? AND tutorial_id = ?
                    ");
                    $progressStmt->execute([$upload['user_id'], $upload['tutorial_id']]);
                } else {
                    // Create new record
                    $progressStmt = $db->prepare("
                        INSERT INTO learning_progress (user_id, tutorial_id, practice_uploaded, last_accessed, created_at)
                        VALUES (?, ?, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                    ");
                    $progressStmt->execute([$upload['user_id'], $upload['tutorial_id']]);
                }
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Practice upload reviewed successfully'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log('Admin Pro learners API error: ' . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error occurred',
        'error_code' => 'INTERNAL_ERROR'
    ]);
}
?>