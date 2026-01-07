<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID, X-Tutorial-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config/database.php';
require_once '../../middleware/FeatureGuard.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $featureGuard = new FeatureGuard();

    // Get user ID
    $userId = null;
    $email = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? null;
    
    if ($email) {
        $userStmt = $db->prepare("SELECT id, first_name, last_name FROM users WHERE email = ? LIMIT 1");
        $userStmt->execute([$email]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $userId = (int)$user['id'];
        }
    }
    
    if (!$userId && !empty($_SERVER['HTTP_X_USER_ID'])) {
        $userId = (int)$_SERVER['HTTP_X_USER_ID'];
    }

    if (!$userId) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
        exit;
    }

    // ENFORCE PRO ACCESS - Progress tracking is Pro only
    $accessCheck = $featureGuard->guardFeature($userId, 'certificates');
    
    if (!$accessCheck['allowed']) {
        $featureGuard->sendAccessDeniedResponse($accessCheck);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get user's learning progress
        $accessSummary = $featureGuard->getUserAccessSummary($userId);
        
        // Get detailed progress from FeatureAccessControl
        $featureControl = new FeatureAccessControl($db);
        $progressData = $featureControl->calculateLearningProgress($userId);
        
        // Get individual tutorial progress
        $tutorialProgressStmt = $db->prepare("
            SELECT lp.*, t.title, t.category, t.duration,
                   pu.status as practice_status, pu.admin_feedback,
                   pu.upload_date as practice_upload_date
            FROM learning_progress lp
            JOIN tutorials t ON lp.tutorial_id = t.id
            LEFT JOIN practice_uploads pu ON lp.user_id = pu.user_id AND lp.tutorial_id = pu.tutorial_id
            WHERE lp.user_id = ?
            ORDER BY lp.last_accessed DESC
        ");
        $tutorialProgressStmt->execute([$userId]);
        $tutorialProgress = $tutorialProgressStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get certificate eligibility
        $certificateEligible = $featureControl->isCertificateEligible($userId);

        echo json_encode([
            'status' => 'success',
            'overall_progress' => $progressData,
            'tutorial_progress' => $tutorialProgress,
            'certificate_eligible' => $certificateEligible,
            'access_summary' => $accessSummary
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update tutorial progress (when user watches a tutorial)
        $data = json_decode(file_get_contents('php://input'), true);
        
        $tutorialId = $data['tutorial_id'] ?? null;
        $watchTimeSeconds = $data['watch_time_seconds'] ?? 0;
        $completionPercentage = $data['completion_percentage'] ?? 0;
        
        if (!$tutorialId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Tutorial ID is required']);
            exit;
        }

        // Update or insert progress
        $progressStmt = $db->prepare("
            INSERT INTO learning_progress (user_id, tutorial_id, watch_time_seconds, completion_percentage)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            watch_time_seconds = GREATEST(watch_time_seconds, VALUES(watch_time_seconds)),
            completion_percentage = GREATEST(completion_percentage, VALUES(completion_percentage)),
            completed_at = CASE 
                WHEN VALUES(completion_percentage) >= 80 THEN CURRENT_TIMESTAMP 
                ELSE completed_at 
            END,
            last_accessed = CURRENT_TIMESTAMP
        ");
        
        $progressStmt->execute([$userId, $tutorialId, $watchTimeSeconds, $completionPercentage]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Progress updated successfully'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log('Learning progress API error: ' . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error occurred',
        'error_code' => 'INTERNAL_ERROR'
    ]);
}
?>