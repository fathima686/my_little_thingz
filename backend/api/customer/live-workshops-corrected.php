<?php
// Live Workshops API - Corrected for actual database structure
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    require_once '../../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get user email
    $email = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? $_GET['email'] ?? 'soudhame52@gmail.com';
    
    // Get user ID first
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
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
    
    // Check user's subscription status using correct database structure
    $subStmt = $pdo->prepare("
        SELECT s.status, s.created_at,
               sp.plan_code, sp.name as plan_name, sp.price, sp.features
        FROM subscriptions s
        LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
        WHERE s.user_id = ? AND s.status = 'active'
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    $subStmt->execute([$userId]);
    $subscription = $subStmt->fetch(PDO::FETCH_ASSOC);
    
    // Determine access level
    $currentPlan = 'free';
    $hasProAccess = false;
    
    if ($subscription) {
        $currentPlan = $subscription['plan_code'];
        $hasProAccess = in_array($currentPlan, ['pro', 'premium']);
    }
    
    if (!$hasProAccess) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Live workshops require Pro or Premium subscription',
            'error_code' => 'FEATURE_ACCESS_DENIED',
            'current_plan' => $currentPlan,
            'required_plans' => ['pro', 'premium'],
            'upgrade_required' => true,
            'subscription_info' => $subscription,
            'debug' => [
                'user_id' => $userId,
                'email' => $email,
                'subscription_found' => $subscription ? true : false,
                'has_pro_access' => $hasProAccess
            ]
        ]);
        exit;
    }
    
    // User has Pro access - return live workshops
    $workshops = [
        [
            'id' => 1,
            'title' => 'Advanced Chocolate Making Techniques',
            'description' => 'Learn professional chocolate making techniques with live guidance',
            'instructor' => 'Master Chef Sarah',
            'date' => '2026-01-10',
            'time' => '15:00',
            'duration' => 120,
            'max_participants' => 20,
            'current_participants' => 8,
            'status' => 'upcoming',
            'meeting_link' => 'https://meet.google.com/abc-def-ghi',
            'materials_needed' => [
                'Dark chocolate (70% cocoa)',
                'Tempering thermometer',
                'Silicone molds',
                'Double boiler'
            ],
            'level' => 'intermediate'
        ],
        [
            'id' => 2,
            'title' => 'Custom Gift Wrapping Workshop',
            'description' => 'Create beautiful custom gift wrapping for your chocolate creations',
            'instructor' => 'Design Expert Maria',
            'date' => '2026-01-12',
            'time' => '14:00',
            'duration' => 90,
            'max_participants' => 15,
            'current_participants' => 5,
            'status' => 'upcoming',
            'meeting_link' => 'https://meet.google.com/xyz-abc-def',
            'materials_needed' => [
                'Wrapping paper',
                'Ribbons',
                'Decorative elements',
                'Scissors and tape'
            ],
            'level' => 'beginner'
        ],
        [
            'id' => 3,
            'title' => 'Business Scaling for Chocolate Makers',
            'description' => 'Learn how to scale your chocolate business and reach more customers',
            'instructor' => 'Business Coach John',
            'date' => '2026-01-15',
            'time' => '16:00',
            'duration' => 150,
            'max_participants' => 25,
            'current_participants' => 12,
            'status' => 'upcoming',
            'meeting_link' => 'https://meet.google.com/business-scale-123',
            'materials_needed' => [
                'Notebook',
                'Calculator',
                'Business plan template (provided)'
            ],
            'level' => 'advanced'
        ]
    ];
    
    echo json_encode([
        'status' => 'success',
        'workshops' => $workshops,
        'access_level' => $currentPlan,
        'subscription_info' => [
            'plan_code' => $subscription['plan_code'],
            'plan_name' => $subscription['plan_name'],
            'status' => $subscription['status'],
            'price' => (float)$subscription['price']
        ],
        'message' => 'Pro-level live workshops available',
        'total_workshops' => count($workshops),
        'debug' => [
            'user_id' => $userId,
            'email' => $email,
            'current_plan' => $currentPlan,
            'has_pro_access' => $hasProAccess,
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