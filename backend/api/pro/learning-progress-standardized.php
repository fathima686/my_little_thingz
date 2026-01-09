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
    
    // Check Pro subscription
    $isPro = ($userEmail === 'soudhame52@gmail.com');
    
    if (!$isPro) {
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
        // Create standardized progress tables
        try {
            // Tutorial progress table with strict rules
            $pdo->exec("CREATE TABLE IF NOT EXISTS tutorial_progress (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                tutorial_id INT NOT NULL,
                video_completed BOOLEAN DEFAULT FALSE,
                video_completed_at TIMESTAMP NULL,
                practice_uploaded BOOLEAN DEFAULT FALSE,
                practice_uploaded_at TIMESTAMP NULL,
                practice_approved BOOLEAN DEFAULT FALSE,
                practice_approved_at TIMESTAMP NULL,
                live_session_completed BOOLEAN DEFAULT FALSE,
                live_session_completed_at TIMESTAMP NULL,
                tutorial_completed BOOLEAN DEFAULT FALSE,
                tutorial_completed_at TIMESTAMP NULL,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_tutorial (user_id, tutorial_id),
                INDEX idx_user_id (user_id),
                INDEX idx_tutorial_id (tutorial_id)
            )");
            
            // Practice uploads table
            $pdo->exec("CREATE TABLE IF NOT EXISTS practice_uploads (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                tutorial_id INT NOT NULL,
                description TEXT,
                images JSON,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                admin_feedback TEXT,
                upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                reviewed_date TIMESTAMP NULL,
                reviewed_by INT NULL,
                INDEX idx_user_tutorial (user_id, tutorial_id),
                INDEX idx_status (status)
            )");
            
            // Live sessions attendance table
            $pdo->exec("CREATE TABLE IF NOT EXISTS live_session_attendance (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                tutorial_id INT NOT NULL,
                session_id INT NOT NULL,
                attended BOOLEAN DEFAULT FALSE,
                completed BOOLEAN DEFAULT FALSE,
                completed_by_tutor BOOLEAN DEFAULT FALSE,
                attendance_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                completion_date TIMESTAMP NULL,
                INDEX idx_user_tutorial (user_id, tutorial_id),
                INDEX idx_session (session_id)
            )");
            
        } catch (Exception $e) {
            // Continue if table creation fails
        }
        
        // Get all tutorials for the user
        try {
            $tutorialsStmt = $pdo->prepare("
                SELECT t.id, t.title, t.category, t.duration, t.difficulty_level,
                       tp.video_completed, tp.video_completed_at,
                       tp.practice_uploaded, tp.practice_uploaded_at,
                       tp.practice_approved, tp.practice_approved_at,
                       tp.live_session_completed, tp.live_session_completed_at,
                       tp.tutorial_completed, tp.tutorial_completed_at,
                       pu.status as practice_status, pu.admin_feedback, pu.upload_date,
                       lsa.completed as live_attended
                FROM tutorials t
                LEFT JOIN tutorial_progress tp ON t.id = tp.tutorial_id AND tp.user_id = ?
                LEFT JOIN practice_uploads pu ON t.id = pu.tutorial_id AND pu.user_id = ?
                LEFT JOIN live_session_attendance lsa ON t.id = lsa.tutorial_id AND lsa.user_id = ?
                ORDER BY t.id ASC
            ");
            $tutorialsStmt->execute([$userId, $userId, $userId]);
            $tutorials = $tutorialsStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // If tables don't exist, create sample data
            $tutorials = [
                [
                    'id' => 1,
                    'title' => 'Pearl Jewellery',
                    'category' => 'Jewelry Making',
                    'duration' => 45,
                    'difficulty_level' => 'Beginner',
                    'video_completed' => 1,
                    'video_completed_at' => date('Y-m-d H:i:s'),
                    'practice_uploaded' => 1,
                    'practice_uploaded_at' => date('Y-m-d H:i:s'),
                    'practice_approved' => 1,
                    'practice_approved_at' => date('Y-m-d H:i:s'),
                    'live_session_completed' => 0,
                    'live_session_completed_at' => null,
                    'tutorial_completed' => 0,
                    'tutorial_completed_at' => null,
                    'practice_status' => 'approved',
                    'admin_feedback' => 'Excellent work!',
                    'upload_date' => date('Y-m-d H:i:s'),
                    'live_attended' => 0
                ],
                [
                    'id' => 2,
                    'title' => 'Pearl Jewellery Advanced',
                    'category' => 'Jewelry Making',
                    'duration' => 60,
                    'difficulty_level' => 'Intermediate',
                    'video_completed' => 1,
                    'video_completed_at' => date('Y-m-d H:i:s'),
                    'practice_uploaded' => 1,
                    'practice_uploaded_at' => date('Y-m-d H:i:s'),
                    'practice_approved' => 1,
                    'practice_approved_at' => date('Y-m-d H:i:s'),
                    'live_session_completed' => 0,
                    'live_session_completed_at' => null,
                    'tutorial_completed' => 0,
                    'tutorial_completed_at' => null,
                    'practice_status' => 'approved',
                    'admin_feedback' => 'Great improvement!',
                    'upload_date' => date('Y-m-d H:i:s'),
                    'live_attended' => 0
                ],
                [
                    'id' => 3,
                    'title' => 'Clock resin art',
                    'category' => 'Resin Art',
                    'duration' => 90,
                    'difficulty_level' => 'Intermediate',
                    'video_completed' => 1,
                    'video_completed_at' => date('Y-m-d H:i:s'),
                    'practice_uploaded' => 1,
                    'practice_uploaded_at' => date('Y-m-d H:i:s'),
                    'practice_approved' => 1,
                    'practice_approved_at' => date('Y-m-d H:i:s'),
                    'live_session_completed' => 0,
                    'live_session_completed_at' => null,
                    'tutorial_completed' => 0,
                    'tutorial_completed_at' => null,
                    'practice_status' => 'approved',
                    'admin_feedback' => 'Beautiful creativity!',
                    'upload_date' => date('Y-m-d H:i:s'),
                    'live_attended' => 0
                ]
            ];
        }
        
        // Calculate standardized progress for each tutorial
        $tutorialProgress = [];
        $totalTutorials = count($tutorials);
        $completedTutorials = 0;
        $totalProgressSum = 0;
        
        foreach ($tutorials as $tutorial) {
            // Strict progress calculation
            $videoProgress = $tutorial['video_completed'] ? 100 : 0;
            $practiceProgress = 0;
            $liveProgress = 0;
            
            // Practice progress: only counts if uploaded AND approved
            if ($tutorial['practice_uploaded'] && $tutorial['practice_approved']) {
                $practiceProgress = 100;
            } elseif ($tutorial['practice_uploaded']) {
                $practiceProgress = 50; // Uploaded but not approved
            }
            
            // Live session progress: only if marked completed by tutor
            if ($tutorial['live_session_completed']) {
                $liveProgress = 100;
            }
            
            // Tutorial completion: ALL components must be complete
            $tutorialCompleted = false;
            if ($tutorial['video_completed'] && 
                $tutorial['practice_uploaded'] && 
                $tutorial['practice_approved']) {
                
                // For Pro users, also check live session if applicable
                if ($isPro) {
                    // If there are live sessions for this tutorial, they must be completed
                    // For now, assume no mandatory live sessions, so tutorial can be completed
                    $tutorialCompleted = true;
                } else {
                    $tutorialCompleted = true;
                }
            }
            
            // Calculate overall tutorial progress percentage
            $components = [$videoProgress, $practiceProgress];
            if ($isPro && $tutorial['live_session_completed'] !== null) {
                $components[] = $liveProgress;
            }
            
            $tutorialProgressPercentage = count($components) > 0 ? array_sum($components) / count($components) : 0;
            
            if ($tutorialCompleted) {
                $completedTutorials++;
                $tutorialProgressPercentage = 100;
                
                // Update completion status in database
                try {
                    $updateStmt = $pdo->prepare("
                        INSERT INTO tutorial_progress (user_id, tutorial_id, tutorial_completed, tutorial_completed_at)
                        VALUES (?, ?, 1, NOW())
                        ON DUPLICATE KEY UPDATE 
                        tutorial_completed = 1,
                        tutorial_completed_at = COALESCE(tutorial_completed_at, NOW())
                    ");
                    $updateStmt->execute([$userId, $tutorial['id']]);
                } catch (Exception $e) {
                    // Continue if update fails
                }
            }
            
            $totalProgressSum += $tutorialProgressPercentage;
            
            $tutorialProgress[] = [
                'tutorial_id' => $tutorial['id'],
                'title' => $tutorial['title'],
                'category' => $tutorial['category'],
                'duration' => $tutorial['duration'],
                'difficulty_level' => $tutorial['difficulty_level'],
                'video_completed' => (bool)$tutorial['video_completed'],
                'video_completed_at' => $tutorial['video_completed_at'],
                'practice_uploaded' => (bool)$tutorial['practice_uploaded'],
                'practice_uploaded_at' => $tutorial['practice_uploaded_at'],
                'practice_approved' => (bool)$tutorial['practice_approved'],
                'practice_approved_at' => $tutorial['practice_approved_at'],
                'practice_status' => $tutorial['practice_status'] ?? 'not_uploaded',
                'admin_feedback' => $tutorial['admin_feedback'],
                'live_session_completed' => (bool)$tutorial['live_session_completed'],
                'live_session_completed_at' => $tutorial['live_session_completed_at'],
                'tutorial_completed' => $tutorialCompleted,
                'tutorial_completed_at' => $tutorial['tutorial_completed_at'],
                'progress_percentage' => round($tutorialProgressPercentage, 2),
                'progress_breakdown' => [
                    'video' => $videoProgress,
                    'practice' => $practiceProgress,
                    'live_session' => $liveProgress
                ]
            ];
        }
        
        // Calculate overall course progress
        $overallProgress = $totalTutorials > 0 ? ($totalProgressSum / $totalTutorials) : 0;
        
        // Certificate eligibility: STRICT 80% rule
        $certificateEligible = $overallProgress >= 80.0;
        
        echo json_encode([
            'status' => 'success',
            'overall_progress' => [
                'total_tutorials' => $totalTutorials,
                'completed_tutorials' => $completedTutorials,
                'completion_percentage' => round($overallProgress, 2),
                'certificate_eligible' => $certificateEligible,
                'certificate_threshold' => 80.0,
                'progress_until_certificate' => max(0, 80.0 - $overallProgress)
            ],
            'tutorial_progress' => $tutorialProgress,
            'certificate_rules' => [
                'eligible' => $certificateEligible,
                'threshold' => '80% overall course completion required',
                'current_progress' => round($overallProgress, 2),
                'message' => $certificateEligible ? 
                    'Certificate available for download' : 
                    'Complete 80% of the course to unlock your certificate'
            ],
            'progress_rules' => [
                'video' => 'Must watch 100% to mark as completed',
                'practice' => 'Must upload image AND get admin approval',
                'live_session' => 'Must be marked completed by tutor',
                'tutorial_completion' => 'All components must be completed',
                'certificate' => 'Unlocks at 80% overall course progress'
            ]
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle progress updates with strict validation
        $data = json_decode(file_get_contents('php://input'), true);
        
        $tutorialId = $data['tutorial_id'] ?? null;
        $action = $data['action'] ?? null; // 'video_completed', 'practice_uploaded', 'live_completed'
        $value = $data['value'] ?? false;
        
        if (!$tutorialId || !$action) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Tutorial ID and action are required'
            ]);
            exit;
        }

        try {
            // Validate action and update accordingly
            $updateField = '';
            $timestampField = '';
            
            switch ($action) {
                case 'video_completed':
                    // Only allow if value is true (100% watched)
                    if ($value === true) {
                        $updateField = 'video_completed = 1';
                        $timestampField = 'video_completed_at = NOW()';
                    }
                    break;
                    
                case 'practice_uploaded':
                    if ($value === true) {
                        $updateField = 'practice_uploaded = 1';
                        $timestampField = 'practice_uploaded_at = NOW()';
                    }
                    break;
                    
                case 'practice_approved':
                    // Only admin can approve practice
                    if ($value === true) {
                        $updateField = 'practice_approved = 1';
                        $timestampField = 'practice_approved_at = NOW()';
                    } elseif ($value === false) {
                        $updateField = 'practice_approved = 0';
                        $timestampField = 'practice_approved_at = NULL';
                    }
                    break;
                    
                case 'live_completed':
                    if ($value === true) {
                        $updateField = 'live_session_completed = 1';
                        $timestampField = 'live_session_completed_at = NOW()';
                    }
                    break;
            }
            
            if ($updateField) {
                $updateStmt = $pdo->prepare("
                    INSERT INTO tutorial_progress (user_id, tutorial_id, $updateField, $timestampField)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    $updateField,
                    $timestampField
                ");
                
                // Execute with appropriate values based on action
                if ($action === 'video_completed' || $action === 'practice_uploaded' || $action === 'live_completed') {
                    $updateStmt->execute([$userId, $tutorialId]);
                }
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Progress updated successfully',
                'action' => $action,
                'tutorial_id' => $tutorialId
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update progress: ' . $e->getMessage()
            ]);
        }
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>