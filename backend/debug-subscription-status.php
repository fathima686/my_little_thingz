<?php
/**
 * Debug Subscription Status
 * Shows detailed subscription information for troubleshooting
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get email from query parameter or header
    $email = $_GET['email'] ?? $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? '';
    
    if (empty($email)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Email parameter required',
            'usage' => 'Add ?email=your@email.com to URL or use X-Tutorial-Email header'
        ]);
        exit;
    }
    
    // Get user info
    $userStmt = $pdo->prepare("SELECT id, email, name, role, created_at FROM users WHERE email = ?");
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
    
    // Get all subscriptions for this user
    $subStmt = $pdo->prepare("
        SELECT s.*, sp.plan_name, sp.price, sp.features
        FROM subscriptions s 
        LEFT JOIN subscription_plans sp ON s.plan_code = sp.plan_code
        WHERE s.email = ? 
        ORDER BY s.created_at DESC
    ");
    $subStmt->execute([$email]);
    $subscriptions = $subStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get active subscription
    $activeSubStmt = $pdo->prepare("
        SELECT s.*, sp.plan_name, sp.price, sp.features
        FROM subscriptions s 
        LEFT JOIN subscription_plans sp ON s.plan_code = sp.plan_code
        WHERE s.email = ? AND s.is_active = 1 AND s.subscription_status = 'active'
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    $activeSubStmt->execute([$email]);
    $activeSubscription = $activeSubStmt->fetch(PDO::FETCH_ASSOC);
    
    // Test feature access using the FeatureAccessControl
    $featureAccess = [];
    try {
        require_once 'models/FeatureAccessControl.php';
        $featureControl = new FeatureAccessControl($pdo);
        
        $userId = $user['id'];
        $currentPlan = $featureControl->getUserPlan($userId);
        $features = $featureControl->getUserFeatures($userId);
        
        $featureAccess = [
            'detected_plan' => $currentPlan,
            'available_features' => $features,
            'can_access_live_workshops' => $featureControl->canAccessLiveWorkshops($userId),
            'can_access_hd_video' => $featureControl->canAccessHDVideo($userId),
            'can_download_videos' => $featureControl->canDownloadVideos($userId),
            'can_access_unlimited_tutorials' => $featureControl->canAccessUnlimitedTutorials($userId),
            'can_access_mentorship' => $featureControl->canAccessMentorship($userId),
            'can_upload_practice_work' => $featureControl->canUploadPracticeWork($userId),
            'can_access_certificates' => $featureControl->canAccessCertificates($userId)
        ];
        
        // Debug method if available
        if (method_exists($featureControl, 'debugSubscriptionStatus')) {
            $featureAccess['debug_info'] = $featureControl->debugSubscriptionStatus($userId);
        }
        
    } catch (Exception $e) {
        $featureAccess = [
            'error' => 'FeatureAccessControl error: ' . $e->getMessage()
        ];
    }
    
    // Get subscription plans for reference
    $plansStmt = $pdo->query("SELECT * FROM subscription_plans ORDER BY price ASC");
    $availablePlans = $plansStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare response
    $response = [
        'status' => 'success',
        'timestamp' => date('Y-m-d H:i:s'),
        'user' => $user,
        'active_subscription' => $activeSubscription,
        'all_subscriptions' => $subscriptions,
        'subscription_count' => count($subscriptions),
        'feature_access' => $featureAccess,
        'available_plans' => $availablePlans,
        'analysis' => [
            'has_active_subscription' => !empty($activeSubscription),
            'current_plan_code' => $activeSubscription['plan_code'] ?? 'basic',
            'is_pro_user' => ($activeSubscription['plan_code'] ?? 'basic') === 'pro',
            'subscription_issues' => []
        ]
    ];
    
    // Analyze potential issues
    if (empty($activeSubscription)) {
        $response['analysis']['subscription_issues'][] = 'No active subscription found';
    }
    
    if ($activeSubscription && $activeSubscription['plan_code'] === 'pro') {
        if (!$featureAccess['can_access_live_workshops']) {
            $response['analysis']['subscription_issues'][] = 'Pro user cannot access live workshops - FeatureAccessControl issue';
        }
    }
    
    if (count($subscriptions) > 1) {
        $response['analysis']['subscription_issues'][] = 'Multiple subscriptions found - may cause conflicts';
    }
    
    // Add recommendations
    $response['recommendations'] = [];
    
    if ($response['analysis']['is_pro_user'] && !empty($response['analysis']['subscription_issues'])) {
        $response['recommendations'][] = 'Run fix-pro-subscription-detection.php to resolve Pro access issues';
    }
    
    if (empty($activeSubscription)) {
        $response['recommendations'][] = 'Create an active Pro subscription for this user';
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Debug failed: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
?>