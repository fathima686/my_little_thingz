<?php
require_once 'config/database.php';

$userEmail = 'soudhame52@gmail.com'; // Your email
$planCode = 'pro';

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "Setting Pro subscription for: $userEmail\n\n";
    
    // First, deactivate any existing subscriptions for this email
    $stmt = $pdo->prepare("UPDATE subscriptions SET is_active = 0 WHERE email = ?");
    $stmt->execute([$userEmail]);
    echo "✓ Deactivated existing subscriptions\n";
    
    // Check if Pro subscription already exists
    $stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE email = ? AND plan_code = ?");
    $stmt->execute([$userEmail, $planCode]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update existing Pro subscription to active
        $stmt = $pdo->prepare("
            UPDATE subscriptions 
            SET is_active = 1, subscription_status = 'active', updated_at = NOW()
            WHERE email = ? AND plan_code = ?
        ");
        $stmt->execute([$userEmail, $planCode]);
        echo "✓ Updated existing Pro subscription to active\n";
    } else {
        // Create new Pro subscription
        $stmt = $pdo->prepare("
            INSERT INTO subscriptions 
            (email, plan_code, subscription_status, is_active, created_at, updated_at) 
            VALUES (?, ?, 'active', 1, NOW(), NOW())
        ");
        $stmt->execute([$userEmail, $planCode]);
        echo "✓ Created new Pro subscription\n";
    }
    
    // Verify the change
    $stmt = $pdo->prepare("
        SELECT s.plan_code, sp.plan_name, s.subscription_status, s.is_active
        FROM subscriptions s
        JOIN subscription_plans sp ON s.plan_code = sp.plan_code
        WHERE s.email = ? AND s.is_active = 1
    ");
    $stmt->execute([$userEmail]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "\n✓ SUCCESS! Current subscription:\n";
        echo "   Plan: {$result['plan_name']} ({$result['plan_code']})\n";
        echo "   Status: {$result['subscription_status']}\n";
        echo "   Active: " . ($result['is_active'] ? 'Yes' : 'No') . "\n";
        echo "\nYour profile should now show Pro Plan. Please refresh your browser.\n";
    } else {
        echo "\n✗ Error: Could not verify subscription update\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>