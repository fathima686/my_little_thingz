<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email');

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
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

$userEmail = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? $_GET['email'] ?? '';

if (empty($userEmail)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email required'
    ]);
    exit;
}

try {
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
    
    // Simple Pro check - allow soudhame52@gmail.com or check subscription
    $isPro = ($userEmail === 'soudhame52@gmail.com');
    
    if (!$isPro) {
        // Check subscription
        $subStmt = $pdo->prepare("SELECT plan_code FROM subscriptions WHERE email = ? AND is_active = 1 ORDER BY created_at DESC LIMIT 1");
        $subStmt->execute([$userEmail]);
        $sub = $subStmt->fetch(PDO::FETCH_ASSOC);
        $isPro = ($sub && $sub['plan_code'] === 'pro');
    }
    
    if (!$isPro) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Progress tracking requires Pro subscription',
            'current_plan' => 'basic',
            'upgrade_required' => true
        ]);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get tutorial progress
        $progressStmt = $pdo->prepare("
            SELECT lp.*, t.title, t.category, t.duration,
                   pu.status as practice_status, pu.admin_feedback,
                   pu.upload_date as practice_upload_date
            FROM learning_progress lp
            JOIN tutorials t ON lp.tutorial_id = t.id
            LEFT JOIN practice_uploads pu ON lp.user_id = pu.user_id AND lp.tutorial_id = pu.tutorial_id
            WHERE lp.user_id = ?
            ORDER BY lp.last_accessed DESC
        ");
        $progressStmt->execute([$userId]);
        $tutorialProgress = $progressStmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate overall progress
        $totalTutorials = count($tutorialProgress);
        $completedTutorials = 0;
        $totalProgress = 0;
        
        foreach ($tutorialProgress as $tutorial) {
            $completion = $tutorial['completion_percentage'] ?? 0;
            $totalProgress += $completion;
            if ($completion >= 80) {
                $completedTutorials++;
            }
        }
        
        $overallProgress = $totalTutorials > 0 ? ($totalProgress / $totalTutorials) : 0;
        $certificateEligible = $overallProgress >= 100;

        echo json_encode([
            'status' => 'success',
            'overall_progress' => [
                'total_tutorials' => $totalTutorials,
                'completed_tutorials' => $completedTutorials,
                'completion_percentage' => round($overallProgress, 2),
                'certificate_eligible' => $certificateEligible
            ],
            'tutorial_progress' => $tutorialProgress,
            'certificate_eligible' => $certificateEligible,
            'access_summary' => [
                'current_plan' => 'pro',
                'features' => ['certificates', 'practice_uploads', 'live_workshops']
            ]
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update progress
        $data = json_decode(file_get_contents('php://input'), true);
        
        $tutorialId = $data['tutorial_id'] ?? null;
        $watchTimeSeconds = $data['watch_time_seconds'] ?? 0;
        $completionPercentage = $data['completion_percentage'] ?? 0;
        
        if (!$tutorialId) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Tutorial ID required'
            ]);
            exit;
        }

        // Update progress
        $progressStmt = $pdo->prepare("
            INSERT INTO learning_progress (user_id, tutorial_id, watch_time_seconds, completion_percentage, last_accessed)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            watch_time_seconds = GREATEST(watch_time_seconds, VALUES(watch_time_seconds)),
            completion_percentage = GREATEST(completion_percentage, VALUES(completion_percentage)),
            completed_at = CASE 
                WHEN VALUES(completion_percentage) >= 80 THEN NOW() 
                ELSE completed_at 
            END,
            last_accessed = NOW()
        ");
        
        $progressStmt->execute([$userId, $tutorialId, $watchTimeSeconds, $completionPercentage]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Progress updated successfully'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>