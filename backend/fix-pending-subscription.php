<?php
// Fix pending subscription status
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
    
    // Find pending premium/pro subscription
    $subStmt = $db->prepare("
        SELECT s.*, sp.plan_code, sp.name as plan_name
        FROM subscriptions s
        JOIN subscription_plans sp ON s.plan_id = sp.id
        WHERE s.user_id = ?
        AND s.status = 'pending'
        AND sp.plan_code IN ('premium', 'pro')
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    $subStmt->execute([$user['id']]);
    $subscription = $subStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$subscription) {
        echo json_encode(['error' => 'No pending premium/pro subscription found']);
        exit;
    }
    
    // Update subscription to active
    $updateStmt = $db->prepare("
        UPDATE subscriptions 
        SET status = 'active',
            current_start = COALESCE(current_start, NOW()),
            current_end = COALESCE(current_end, DATE_ADD(NOW(), INTERVAL 1 MONTH)),
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $result = $updateStmt->execute([$subscription['id']]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Subscription activated successfully',
            'subscription_id' => $subscription['id'],
            'plan_code' => $subscription['plan_code'],
            'user_email' => $email
        ]);
    } else {
        echo json_encode(['error' => 'Failed to update subscription']);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>