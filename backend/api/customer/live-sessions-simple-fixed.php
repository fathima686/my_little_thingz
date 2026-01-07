<?php
// Simple Live Sessions API - Fixed for your database structure
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID, X-Tutorial-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    require_once '../../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Get user email
    $email = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? $_GET['email'] ?? 'soudhame52@gmail.com';
    
    // Get user ID
    $userStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $userStmt->execute([$email]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found',
            'email' => $email
        ]);
        exit;
    }
    
    $userId = $user['id'];
    
    // Check user's subscription - SIMPLE CHECK
    $subStmt = $db->prepare("
        SELECT s.status, sp.plan_code, sp.name as plan_name
        FROM subscriptions s
        LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
        WHERE s.user_id = ? AND s.status = 'active'
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    $subStmt->execute([$userId]);
    $subscription = $subStmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user has Pro or Premium access
    $hasAccess = false;
    $currentPlan = 'free';
    
    if ($subscription) {
        $currentPlan = $subscription['plan_code'];
        $hasAccess = in_array($currentPlan, ['pro', 'premium']);
    }
    
    if (!$hasAccess) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Live sessions require Pro or Premium subscription',
            'error_code' => 'FEATURE_ACCESS_DENIED',
            'current_plan' => $currentPlan,
            'required_plans' => ['pro', 'premium'],
            'upgrade_required' => true,
            'debug' => [
                'user_id' => $userId,
                'email' => $email,
                'subscription_found' => $subscription ? true : false,
                'subscription_plan' => $subscription ? $subscription['plan_code'] : null
            ]
        ]);
        exit;
    }
    
    // User has access - return mock live sessions
    $sessions = [
        [
            'id' => 1,
            'title' => 'Advanced Chocolate Making Techniques',
            'description' => 'Learn professional chocolate making techniques with live guidance from our expert chef.',
            'teacher_email' => 'chef@mylittlethings.com',
            'subject_name' => 'Chocolate Making',
            'subject_description' => 'Professional chocolate crafting techniques',
            'subject_color' => '#8B4513',
            'scheduled_date' => '2026-01-10',
            'scheduled_time' => '15:00:00',
            'scheduled_datetime' => '2026-01-10 15:00:00',
            'duration_minutes' => 120,
            'max_participants' => 20,
            'registered_count' => 8,
            'is_registered' => false,
            'status' => 'scheduled',
            'meeting_link' => 'https://meet.google.com/abc-def-ghi',
            'materials_needed' => 'Dark chocolate (70% cocoa), Tempering thermometer, Silicone molds',
            'level' => 'intermediate'
        ],
        [
            'id' => 2,
            'title' => 'Custom Gift Wrapping Workshop',
            'description' => 'Create beautiful custom gift wrapping for your chocolate creations.',
            'teacher_email' => 'designer@mylittlethings.com',
            'subject_name' => 'Gift Design',
            'subject_description' => 'Creative gift presentation techniques',
            'subject_color' => '#FF69B4',
            'scheduled_date' => '2026-01-12',
            'scheduled_time' => '14:00:00',
            'scheduled_datetime' => '2026-01-12 14:00:00',
            'duration_minutes' => 90,
            'max_participants' => 15,
            'registered_count' => 5,
            'is_registered' => false,
            'status' => 'scheduled',
            'meeting_link' => 'https://meet.google.com/xyz-abc-def',
            'materials_needed' => 'Wrapping paper, Ribbons, Decorative elements',
            'level' => 'beginner'
        ],
        [
            'id' => 3,
            'title' => 'Business Scaling for Chocolate Makers',
            'description' => 'Learn how to scale your chocolate business and reach more customers.',
            'teacher_email' => 'business@mylittlethings.com',
            'subject_name' => 'Business Development',
            'subject_description' => 'Entrepreneurship and business growth',
            'subject_color' => '#4169E1',
            'scheduled_date' => '2026-01-15',
            'scheduled_time' => '16:00:00',
            'scheduled_datetime' => '2026-01-15 16:00:00',
            'duration_minutes' => 150,
            'max_participants' => 25,
            'registered_count' => 12,
            'is_registered' => false,
            'status' => 'scheduled',
            'meeting_link' => 'https://meet.google.com/business-scale-123',
            'materials_needed' => 'Notebook, Calculator, Business plan template (provided)',
            'level' => 'advanced'
        ]
    ];
    
    echo json_encode([
        'status' => 'success',
        'sessions' => $sessions,
        'message' => 'Live sessions available for ' . $currentPlan . ' users',
        'debug' => [
            'user_id' => $userId,
            'email' => $email,
            'current_plan' => $currentPlan,
            'has_access' => $hasAccess,
            'total_sessions' => count($sessions),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>