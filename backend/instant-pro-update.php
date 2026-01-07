<?php
// Direct database update to set Pro subscription
require_once 'config/database.php';

$userEmail = 'soudhame52@gmail.com';

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Step 1: Deactivate all existing subscriptions for this email
    $stmt = $pdo->prepare("UPDATE subscriptions SET is_active = 0 WHERE email = ?");
    $stmt->execute([$userEmail]);
    
    // Step 2: Check if Pro subscription exists
    $stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE email = ? AND plan_code = 'pro'");
    $stmt->execute([$userEmail]);
    $proExists = $stmt->fetch();
    
    if ($proExists) {
        // Update existing Pro subscription
        $stmt = $pdo->prepare("
            UPDATE subscriptions 
            SET is_active = 1, subscription_status = 'active', updated_at = NOW()
            WHERE email = ? AND plan_code = 'pro'
        ");
        $stmt->execute([$userEmail]);
    } else {
        // Create new Pro subscription
        $stmt = $pdo->prepare("
            INSERT INTO subscriptions 
            (email, plan_code, subscription_status, is_active, created_at, updated_at) 
            VALUES (?, 'pro', 'active', 1, NOW(), NOW())
        ");
        $stmt->execute([$userEmail]);
    }
    
    // Step 3: Verify the update
    $stmt = $pdo->prepare("
        SELECT s.plan_code, sp.plan_name, s.subscription_status, s.is_active
        FROM subscriptions s
        JOIN subscription_plans sp ON s.plan_code = sp.plan_code
        WHERE s.email = ? AND s.is_active = 1
    ");
    $stmt->execute([$userEmail]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['plan_code'] === 'pro') {
        echo json_encode([
            'status' => 'success',
            'message' => 'Successfully updated to Pro Plan',
            'subscription' => $result
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update subscription'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>