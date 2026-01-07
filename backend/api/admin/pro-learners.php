<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID, X-Admin-Token');

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
            // Get all pending practice uploads for review
            $stmt = $db->prepare("
                SELECT pu.*, u.first_name, u.last_name, u.email, t.title as tutorial_title
                FROM practice_uploads pu
                JOIN users u ON pu.user_id = u.id
                JOIN tutorials t ON pu.tutorial_id = t.id
                JOIN subscriptions s ON u.id = s.user_id
                JOIN subscription_plans sp ON s.plan_id = sp.id
                WHERE pu.status = 'pending' 
                AND sp.plan_code = 'pro'
                ORDER BY pu.upload_date ASC
            ");
            $stmt->execute();
            $pendingUploads = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'pending_uploads' => $pendingUploads
            ]);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle practice upload review
        $data = json_decode(file_get_contents('php://input'), true);
        
        $uploadId = $data['upload_id'] ?? null;
        $status = $data['status'] ?? null; // 'approved' or 'rejected'
        $feedback = $data['feedback'] ?? '';
        $adminId = $data['admin_id'] ?? 1; // You should get this from admin session

        if (!$uploadId || !in_array($status, ['approved', 'rejected'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid upload ID or status']);
            exit;
        }

        // Update practice upload
        $updateStmt = $db->prepare("
            UPDATE practice_uploads 
            SET status = ?, admin_feedback = ?, reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $updateStmt->execute([$status, $feedback, $adminId, $uploadId]);

        // Update learning progress if approved
        if ($status === 'approved') {
            $uploadStmt = $db->prepare("SELECT user_id, tutorial_id FROM practice_uploads WHERE id = ?");
            $uploadStmt->execute([$uploadId]);
            $upload = $uploadStmt->fetch(PDO::FETCH_ASSOC);

            if ($upload) {
                $progressStmt = $db->prepare("
                    UPDATE learning_progress 
                    SET practice_approved = 1 
                    WHERE user_id = ? AND tutorial_id = ?
                ");
                $progressStmt->execute([$upload['user_id'], $upload['tutorial_id']]);
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