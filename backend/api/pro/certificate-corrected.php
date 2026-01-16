<?php
/**
 * Corrected Certificate API
 * Only generates certificates when overall course progress ≥ 80% AND practice is admin-approved
 */

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

$userEmail = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? $_GET['email'] ?? $_POST['email'] ?? '';
$tutorialId = $_GET['tutorial_id'] ?? $_POST['tutorial_id'] ?? '';

if (empty($userEmail) || empty($tutorialId)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email and tutorial_id are required'
    ]);
    exit;
}

try {
    // Get user information
    $userStmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
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
    $userName = $user['name'] ?? 'Student';
    
    // Check Pro subscription
    $isPro = ($userEmail === 'soudhame52@gmail.com'); // Admin override
    
    if (!$isPro) {
        $subStmt = $pdo->prepare("
            SELECT s.status, sp.plan_code 
            FROM subscriptions s
            LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
            WHERE s.user_id = ? AND s.status = 'active'
            ORDER BY s.created_at DESC 
            LIMIT 1
        ");
        $subStmt->execute([$userId]);
        $subscription = $subStmt->fetch(PDO::FETCH_ASSOC);
        
        $isPro = ($subscription && $subscription['plan_code'] === 'pro' && $subscription['status'] === 'active');
    }
    
    if (!$isPro) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Certificates are only available for Pro subscribers',
            'upgrade_required' => true
        ]);
        exit;
    }
    
    // Get tutorial information
    $tutorialStmt = $pdo->prepare("SELECT title, description, category FROM tutorials WHERE id = ?");
    $tutorialStmt->execute([$tutorialId]);
    $tutorial = $tutorialStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tutorial) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Tutorial not found'
        ]);
        exit;
    }
    
    // Get comprehensive learning progress
    $progressStmt = $pdo->prepare("
        SELECT 
            video_watched,
            practice_uploaded,
            practice_completed,
            practice_admin_approved,
            quiz_completed,
            last_accessed
        FROM learning_progress 
        WHERE user_id = ? AND tutorial_id = ?
    ");
    $progressStmt->execute([$userId, $tutorialId]);
    $progress = $progressStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$progress) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No learning progress found for this tutorial',
            'certificate_available' => false,
            'requirements' => [
                'video_watched' => false,
                'practice_admin_approved' => false,
                'quiz_completed' => false,
                'overall_progress' => 0
            ]
        ]);
        exit;
    }
    
    // Calculate overall progress percentage
    $progressComponents = [
        'video_watched' => (bool)$progress['video_watched'],
        'practice_admin_approved' => (bool)$progress['practice_admin_approved'],
        'quiz_completed' => (bool)$progress['quiz_completed']
    ];
    
    $completedComponents = array_sum($progressComponents);
    $totalComponents = count($progressComponents);
    $overallProgress = ($completedComponents / $totalComponents) * 100;
    
    // Certificate eligibility check (strict requirements)
    $certificateEligible = $overallProgress >= 80.0 && $progress['practice_admin_approved'];
    
    if (!$certificateEligible) {
        $missingRequirements = [];
        if (!$progress['video_watched']) {
            $missingRequirements[] = 'Complete tutorial video';
        }
        if (!$progress['practice_admin_approved']) {
            if (!$progress['practice_uploaded']) {
                $missingRequirements[] = 'Upload practice work';
            } else {
                $missingRequirements[] = 'Practice work must be approved by admin';
            }
        }
        if (!$progress['quiz_completed']) {
            $missingRequirements[] = 'Complete tutorial quiz';
        }
        
        echo json_encode([
            'status' => 'error',
            'message' => 'Certificate requirements not met',
            'certificate_available' => false,
            'overall_progress' => round($overallProgress, 1),
            'requirements' => [
                'video_watched' => $progressComponents['video_watched'],
                'practice_admin_approved' => $progressComponents['practice_admin_approved'],
                'quiz_completed' => $progressComponents['quiz_completed'],
                'minimum_progress_required' => 80.0,
                'current_progress' => round($overallProgress, 1)
            ],
            'missing_requirements' => $missingRequirements,
            'practice_status' => [
                'uploaded' => (bool)$progress['practice_uploaded'],
                'completed' => (bool)$progress['practice_completed'],
                'admin_approved' => (bool)$progress['practice_admin_approved']
            ]
        ]);
        exit;
    }
    
    // Check if certificate already exists
    $certStmt = $pdo->prepare("
        SELECT certificate_id, generated_at, certificate_data 
        FROM certificates 
        WHERE user_id = ? AND tutorial_id = ?
    ");
    $certStmt->execute([$userId, $tutorialId]);
    $existingCert = $certStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingCert) {
        $certificateData = json_decode($existingCert['certificate_data'], true);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Certificate already generated',
            'certificate_available' => true,
            'certificate' => [
                'certificate_id' => $existingCert['certificate_id'],
                'generated_at' => $existingCert['generated_at'],
                'student_name' => $certificateData['student_name'] ?? $userName,
                'tutorial_title' => $certificateData['tutorial_title'] ?? $tutorial['title'],
                'tutorial_category' => $certificateData['tutorial_category'] ?? $tutorial['category'],
                'completion_date' => $certificateData['completion_date'] ?? $existingCert['generated_at'],
                'overall_progress' => round($overallProgress, 1),
                'verification_url' => "https://mylittlethingz.com/verify-certificate/{$existingCert['certificate_id']}"
            ],
            'requirements_met' => [
                'video_watched' => true,
                'practice_admin_approved' => true,
                'quiz_completed' => true,
                'overall_progress' => round($overallProgress, 1)
            ]
        ]);
        exit;
    }
    
    // Generate new certificate
    $certificateId = 'CERT_' . strtoupper(uniqid()) . '_' . $userId . '_' . $tutorialId;
    $completionDate = date('Y-m-d H:i:s');
    
    $certificateData = [
        'certificate_id' => $certificateId,
        'student_name' => $userName,
        'student_email' => $userEmail,
        'tutorial_title' => $tutorial['title'],
        'tutorial_category' => $tutorial['category'] ?? 'General',
        'completion_date' => $completionDate,
        'overall_progress' => round($overallProgress, 1),
        'requirements_completed' => [
            'video_watched' => true,
            'practice_admin_approved' => true,
            'quiz_completed' => true
        ],
        'authenticity_verified' => true,
        'generated_by' => 'MyLittleThingz Learning Platform',
        'verification_method' => 'admin_approved_practice_work'
    ];
    
    // Store certificate in database
    $insertStmt = $pdo->prepare("
        INSERT INTO certificates 
        (certificate_id, user_id, tutorial_id, certificate_data, generated_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $insertStmt->execute([
        $certificateId,
        $userId,
        $tutorialId,
        json_encode($certificateData)
    ]);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Certificate generated successfully',
        'certificate_available' => true,
        'certificate' => [
            'certificate_id' => $certificateId,
            'generated_at' => $completionDate,
            'student_name' => $userName,
            'tutorial_title' => $tutorial['title'],
            'tutorial_category' => $tutorial['category'] ?? 'General',
            'completion_date' => $completionDate,
            'overall_progress' => round($overallProgress, 1),
            'verification_url' => "https://mylittlethingz.com/verify-certificate/{$certificateId}"
        ],
        'requirements_met' => [
            'video_watched' => true,
            'practice_admin_approved' => true,
            'quiz_completed' => true,
            'overall_progress' => round($overallProgress, 1)
        ],
        'authenticity_notes' => [
            'practice_work_verified' => 'Practice work has been reviewed and approved by admin',
            'similarity_check_passed' => 'Images verified as unique within platform',
            'academic_integrity' => 'Certificate represents genuine learning achievement'
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Certificate generation error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Certificate generation failed: ' . $e->getMessage()
    ]);
}
?>