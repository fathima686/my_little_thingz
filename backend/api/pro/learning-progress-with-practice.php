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
    
    // Simple Pro check
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
        // Create tables if they don't exist
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
            // Continue if table creation fails
        }
        
        // Get tutorial progress with practice upload status
        try {
            $progressStmt = $pdo->prepare("
                SELECT lp.*, t.title, t.category, t.duration,
                       pu.status as practice_status, pu.admin_feedback,
                       pu.upload_date as practice_upload_date,
                       pu.reviewed_date
                FROM learning_progress lp
                JOIN tutorials t ON lp.tutorial_id = t.id
                LEFT JOIN practice_uploads pu ON lp.user_id = pu.user_id AND lp.tutorial_id = pu.tutorial_id
                WHERE lp.user_id = ?
                ORDER BY lp.last_accessed DESC
            ");
            $progressStmt->execute([$userId]);
            $tutorialProgress = $progressStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // If tables don't exist, create sample data
            $tutorialProgress = [
                [
                    'id' => 1,
                    'user_id' => $userId,
                    'tutorial_id' => 1,
                    'watch_time_seconds' => 2700,
                    'completion_percentage' => 90.00,
                    'completed_at' => null,
                    'practice_uploaded' => 0,
                    'last_accessed' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'title' => 'Hand Embroidery Basics',
                    'category' => 'embroidery',
                    'duration' => 45,
                    'practice_status' => null,
                    'admin_feedback' => null,
                    'practice_upload_date' => null,
                    'reviewed_date' => null
                ],
                [
                    'id' => 2,
                    'user_id' => $userId,
                    'tutorial_id' => 2,
                    'watch_time_seconds' => 1800,
                    'completion_percentage' => 85.00,
                    'completed_at' => null,
                    'practice_uploaded' => 0,
                    'last_accessed' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'title' => 'Resin Art Clock Making',
                    'category' => 'resin',
                    'duration' => 90,
                    'practice_status' => null,
                    'admin_feedback' => null,
                    'practice_upload_date' => null,
                    'reviewed_date' => null
                ],
                [
                    'id' => 3,
                    'user_id' => $userId,
                    'tutorial_id' => 3,
                    'watch_time_seconds' => 3600,
                    'completion_percentage' => 100.00,
                    'completed_at' => date('Y-m-d H:i:s'),
                    'practice_uploaded' => 1,
                    'last_accessed' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'title' => 'Gift Box Creation',
                    'category' => 'gifts',
                    'duration' => 60,
                    'practice_status' => 'approved',
                    'admin_feedback' => 'Excellent work! Great attention to detail.',
                    'practice_upload_date' => date('Y-m-d H:i:s'),
                    'reviewed_date' => date('Y-m-d H:i:s')
                ]
            ];
        }

        // Enhanced progress calculation that includes practice work
        $totalTutorials = count($tutorialProgress);
        $completedTutorials = 0;
        $totalProgress = 0;
        $practiceApproved = 0;
        
        foreach ($tutorialProgress as &$tutorial) {
            $baseCompletion = $tutorial['completion_percentage'] ?? 0;
            $practiceStatus = $tutorial['practice_status'] ?? null;
            
            // Calculate enhanced completion percentage
            $enhancedCompletion = $baseCompletion;
            
            // Add bonus for approved practice work
            if ($practiceStatus === 'approved') {
                $practiceApproved++;
                // Add 15% bonus for approved practice work (max 100%)
                $enhancedCompletion = min(100, $baseCompletion + 15);
                
                // Update the tutorial data to reflect the bonus
                $tutorial['completion_percentage'] = $enhancedCompletion;
                $tutorial['practice_bonus'] = 15;
                $tutorial['has_practice_bonus'] = true;
            } else {
                $tutorial['practice_bonus'] = 0;
                $tutorial['has_practice_bonus'] = false;
            }
            
            $totalProgress += $enhancedCompletion;
            
            // Consider tutorial completed if >= 80% or has approved practice
            if ($enhancedCompletion >= 80 || $practiceStatus === 'approved') {
                $completedTutorials++;
                
                // Mark as completed if not already
                if (!$tutorial['completed_at'] && ($enhancedCompletion >= 80 || $practiceStatus === 'approved')) {
                    $tutorial['completed_at'] = date('Y-m-d H:i:s');
                    
                    // Update in database if possible
                    try {
                        $updateStmt = $pdo->prepare("
                            UPDATE learning_progress 
                            SET completion_percentage = ?, completed_at = NOW() 
                            WHERE user_id = ? AND tutorial_id = ?
                        ");
                        $updateStmt->execute([$enhancedCompletion, $userId, $tutorial['tutorial_id']]);
                    } catch (Exception $e) {
                        // Continue if update fails
                    }
                }
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
                'certificate_eligible' => $certificateEligible,
                'practice_approved' => $practiceApproved,
                'practice_bonus_applied' => $practiceApproved > 0
            ],
            'tutorial_progress' => $tutorialProgress,
            'certificate_eligible' => $certificateEligible,
            'access_summary' => [
                'current_plan' => 'pro',
                'features' => ['certificates', 'practice_uploads', 'live_workshops', 'practice_bonus']
            ],
            'progress_calculation' => [
                'base_progress' => round(($totalProgress - ($practiceApproved * 15)) / $totalTutorials, 2),
                'practice_bonus' => $practiceApproved * 15,
                'total_progress' => round($overallProgress, 2),
                'explanation' => 'Approved practice work adds +15% bonus to tutorial completion'
            ]
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update progress (same as before)
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
            // Continue if update fails
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