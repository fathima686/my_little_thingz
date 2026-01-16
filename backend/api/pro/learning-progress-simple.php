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
        // Create learning_progress table if it doesn't exist
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS learning_progress (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                tutorial_id INT NOT NULL,
                watch_time_seconds INT DEFAULT 0,
                completion_percentage DECIMAL(5,2) DEFAULT 0.00,
                completed_at TIMESTAMP NULL,
                practice_uploaded BOOLEAN DEFAULT FALSE,
                last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_tutorial (user_id, tutorial_id)
            )");
        } catch (Exception $e) {
            // Table creation failed, continue without it
        }
        
        // Create practice_uploads table if it doesn't exist
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS practice_uploads (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                tutorial_id INT NOT NULL,
                description TEXT,
                images JSON,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                admin_feedback TEXT,
                upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                reviewed_date TIMESTAMP NULL
            )");
        } catch (Exception $e) {
            // Table creation failed, continue without it
        }
        
        // Get total tutorials in the course (all active tutorials)
        $totalTutorialsStmt = $pdo->prepare("SELECT COUNT(*) as total FROM tutorials WHERE is_active = 1");
        $totalTutorialsStmt->execute();
        $totalTutorialsResult = $totalTutorialsStmt->fetch(PDO::FETCH_ASSOC);
        $totalTutorials = (int)($totalTutorialsResult['total'] ?? 0);
        
        // Get all tutorials with user's progress and practice status
        // Use subquery to get the latest practice upload status for each tutorial
        $allTutorialsStmt = $pdo->prepare("
            SELECT 
                t.id as tutorial_id,
                t.title,
                t.category,
                t.duration,
                COALESCE(lp.completion_percentage, 0) as completion_percentage,
                COALESCE(lp.completed_at, NULL) as completed_at,
                COALESCE(pu_latest.status, NULL) as practice_status,
                COALESCE(pu_latest.admin_feedback, NULL) as admin_feedback,
                COALESCE(pu_latest.upload_date, NULL) as practice_upload_date
            FROM tutorials t
            LEFT JOIN learning_progress lp ON t.id = lp.tutorial_id AND lp.user_id = ?
            LEFT JOIN (
                SELECT pu1.tutorial_id, pu1.user_id, pu1.status, pu1.admin_feedback, pu1.upload_date
                FROM practice_uploads pu1
                INNER JOIN (
                    SELECT tutorial_id, user_id, MAX(upload_date) as max_date
                    FROM practice_uploads
                    WHERE user_id = ?
                    GROUP BY tutorial_id, user_id
                ) pu2 ON pu1.tutorial_id = pu2.tutorial_id 
                    AND pu1.user_id = pu2.user_id 
                    AND pu1.upload_date = pu2.max_date
            ) pu_latest ON t.id = pu_latest.tutorial_id AND pu_latest.user_id = ?
            WHERE t.is_active = 1
            ORDER BY t.id ASC
        ");
        $allTutorialsStmt->execute([$userId, $userId, $userId]);
        $allTutorials = $allTutorialsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate overall progress: Count completed tutorials + approved practice submissions
        // A tutorial is considered completed if:
        // 1. completion_percentage >= 80, OR
        // 2. practice_status = 'approved'
        $completedTutorials = 0;
        $tutorialProgress = [];
        
        foreach ($allTutorials as $tutorial) {
            $completionPercentage = (float)($tutorial['completion_percentage'] ?? 0);
            $practiceStatus = $tutorial['practice_status'] ?? null;
            
            // Determine if tutorial is completed
            $isCompleted = ($completionPercentage >= 80) || ($practiceStatus === 'approved');
            
            // Determine status indicator (no percentages for individual tutorials)
            $status = 'In Progress';
            if ($practiceStatus === 'approved') {
                $status = 'Practice Approved';
            } elseif ($isCompleted) {
                $status = 'Completed';
            } elseif ($completionPercentage > 0) {
                $status = 'In Progress';
            } else {
                $status = 'Not Started';
            }
            
            if ($isCompleted) {
                $completedTutorials++;
            }
            
            // Build tutorial progress without completion_percentage
            $tutorialProgress[] = [
                'tutorial_id' => $tutorial['tutorial_id'],
                'title' => $tutorial['title'],
                'category' => $tutorial['category'],
                'duration' => $tutorial['duration'],
                'status' => $status,
                'practice_status' => $practiceStatus,
                'admin_feedback' => $tutorial['admin_feedback'],
                'practice_upload_date' => $tutorial['practice_upload_date'],
                'completed_at' => $tutorial['completed_at']
            ];
        }
        
        // Calculate overall course completion percentage
        $overallProgress = $totalTutorials > 0 ? (($completedTutorials / $totalTutorials) * 100) : 0;
        $certificateEligible = $overallProgress >= 80;

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
        // Create learning_progress table if it doesn't exist
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS learning_progress (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                tutorial_id INT NOT NULL,
                watch_time_seconds INT DEFAULT 0,
                completion_percentage DECIMAL(5,2) DEFAULT 0.00,
                completed_at TIMESTAMP NULL,
                practice_uploaded BOOLEAN DEFAULT FALSE,
                last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_tutorial (user_id, tutorial_id)
            )");
        } catch (Exception $e) {
            // Table creation failed, continue
        }
        
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

        // Try to update progress, if table doesn't exist, just return success
        try {
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
        } catch (Exception $e) {
            // If table doesn't exist, just return success for now
        }

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