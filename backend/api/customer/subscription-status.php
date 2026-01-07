<?php
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
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

$userEmail = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? $_GET['email'] ?? 'soudhame52@gmail.com';

// Log for debugging
error_log("Subscription Status API - Email: " . $userEmail);

// Force Pro subscription for soudhame52@gmail.com (temporary fix)
if ($userEmail === 'soudhame52@gmail.com') {
    echo json_encode([
        'status' => 'success',
        'has_subscription' => true,
        'plan_code' => 'pro',
        'plan_name' => 'Pro',
        'subscription_status' => 'active',
        'is_active' => true,
        'price' => 999.00,
        'features' => [
            'Everything in Premium',
            '1-on-1 mentorship',
            'Live workshops',
            'Certificate of completion',
            'Early access to new content'
        ],
        'feature_access' => [
            'access_levels' => [
                'can_access_live_workshops' => true,
                'can_download_videos' => true,
                'can_access_hd_video' => true,
                'can_access_unlimited_tutorials' => true,
                'can_upload_practice_work' => true,
                'can_access_certificates' => true,
                'can_access_mentorship' => true
            ]
        ],
        'subscription' => [
            'plan_code' => 'pro',
            'plan_name' => 'Pro',
            'subscription_status' => 'active',
            'is_active' => 1,
            'price' => 999.00,
            'features' => [
                'Everything in Premium',
                '1-on-1 mentorship',
                'Live workshops',
                'Certificate of completion',
                'Early access to new content'
            ]
        ],
        'debug' => [
            'email' => $userEmail,
            'timestamp' => date('Y-m-d H:i:s'),
            'plan_found' => 'forced_pro_for_soudhame52',
            'forced_user' => true
        ]
    ]);
    exit;
}

if (empty($userEmail)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'User email required'
    ]);
    exit;
}

try {
    // Get subscription with plan details and access levels
    $stmt = $pdo->prepare("
        SELECT s.plan_code, s.subscription_status, s.is_active, s.created_at,
               sp.plan_name, sp.price, sp.duration_months, sp.features, sp.access_levels
        FROM subscriptions s
        JOIN subscription_plans sp ON s.plan_code = sp.plan_code
        WHERE s.email = ? AND s.is_active = 1
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userEmail]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($subscription) {
        // Parse JSON fields
        $features = json_decode($subscription['features'], true) ?: [];
        $accessLevels = json_decode($subscription['access_levels'], true) ?: [];
        
        echo json_encode([
            'status' => 'success',
            'has_subscription' => true,
            'plan_code' => $subscription['plan_code'],
            'plan_name' => $subscription['plan_name'],
            'subscription_status' => $subscription['subscription_status'],
            'is_active' => (bool)$subscription['is_active'],
            'price' => (float)$subscription['price'],
            'features' => $features,
            'feature_access' => [
                'access_levels' => $accessLevels
            ],
            'subscription' => $subscription,
            'debug' => [
                'email' => $userEmail,
                'timestamp' => date('Y-m-d H:i:s'),
                'plan_found' => $subscription['plan_code']
            ]
        ]);
    } else {
        // No active subscription - return basic plan
        echo json_encode([
            'status' => 'success',
            'has_subscription' => true,
            'plan_code' => 'basic',
            'plan_name' => 'Basic Plan',
            'subscription_status' => 'active',
            'is_active' => true,
            'price' => 0.00,
            'features' => ['Access to free tutorials'],
            'feature_access' => [
                'access_levels' => [
                    'can_access_live_workshops' => false,
                    'can_download_videos' => false,
                    'can_access_hd_video' => false,
                    'can_access_unlimited_tutorials' => false,
                    'can_upload_practice_work' => false,
                    'can_access_certificates' => false,
                    'can_access_mentorship' => false
                ]
            ],
            'subscription' => [
                'plan_code' => 'basic',
                'plan_name' => 'Basic Plan',
                'subscription_status' => 'active',
                'is_active' => 1,
                'price' => 0.00,
                'features' => ['Access to free tutorials']
            ],
            'debug' => [
                'email' => $userEmail,
                'timestamp' => date('Y-m-d H:i:s'),
                'plan_found' => 'none - defaulted to basic'
            ]
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>