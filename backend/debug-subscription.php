<?php
// Debug subscription status
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config/database.php';

$email = $_GET['email'] ?? '';

if (!$email) {
    echo json_encode(['error' => 'Email required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Find user
    $userStmt = $db->prepare("SELECT id, email FROM users WHERE email = ?");
    $userStmt->execute([$email]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['error' => 'User not found', 'email' => $email]);
        exit;
    }
    
    // Get subscription
    $subStmt = $db->prepare("
        SELECT s.*, sp.plan_code, sp.name as plan_name
        FROM subscriptions s
        JOIN subscription_plans sp ON s.plan_id = sp.id
        WHERE s.user_id = ?
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    $subStmt->execute([$user['id']]);
    $subscription = $subStmt->fetch(PDO::FETCH_ASSOC);
    
    // Check active subscription
    $activeStmt = $db->prepare("
        SELECT s.*, sp.plan_code, sp.name as plan_name
        FROM subscriptions s
        JOIN subscription_plans sp ON s.plan_id = sp.id
        WHERE s.user_id = ? 
        AND s.status IN ('active', 'authenticated')
        AND (s.current_end IS NULL OR s.current_end > NOW())
        AND sp.plan_code IN ('premium', 'pro')
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    $activeStmt->execute([$user['id']]);
    $activeSubscription = $activeStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'user' => $user,
        'latest_subscription' => $subscription,
        'active_premium_subscription' => $activeSubscription,
        'has_premium_access' => (bool)$activeSubscription,
        'current_time' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>