<?php
require_once 'config/database.php';

$userEmail = $_GET['email'] ?? 'soudhame52@gmail.com'; // Get email from URL parameter

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "=== DEBUGGING PROFILE SUBSCRIPTION ISSUE ===\n";
    echo "Email: $userEmail\n\n";
    
    // 1. Check if user exists
    echo "1. Checking user existence:\n";
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email = ?");
    $stmt->execute([$userEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✓ User found: ID = {$user['id']}, Email = {$user['email']}\n\n";
        $userId = $user['id'];
    } else {
        echo "✗ User not found!\n\n";
        exit;
    }
    
    // 2. Check subscriptions table
    echo "2. Checking subscriptions table:\n";
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE email = ?");
    $stmt->execute([$userEmail]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($subscriptions) {
        echo "✓ Found " . count($subscriptions) . " subscription(s):\n";
        foreach ($subscriptions as $sub) {
            echo "   - Plan: {$sub['plan_code']}, Status: {$sub['subscription_status']}, Active: " . ($sub['is_active'] ? 'Yes' : 'No') . "\n";
        }
    } else {
        echo "✗ No subscriptions found for this email!\n";
    }
    echo "\n";
    
    // 3. Check subscription_plans table
    echo "3. Checking subscription_plans table:\n";
    $stmt = $pdo->prepare("SELECT * FROM subscription_plans");
    $stmt->execute();
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Available plans:\n";
    foreach ($plans as $plan) {
        echo "   - Code: {$plan['plan_code']}, Name: {$plan['plan_name']}, Status: {$plan['status']}\n";
    }
    echo "\n";
    
    // 4. Test current profile query
    echo "4. Testing current profile query:\n";
    $stmt = $pdo->prepare("
        SELECT sp.plan_code, sp.plan_name, sp.status, sp.created_at,
               s.subscription_status, s.is_active
        FROM subscription_plans sp
        LEFT JOIN subscriptions s ON s.plan_code = sp.plan_code AND s.email = ?
        WHERE s.email = ? OR sp.plan_code = 'basic'
        ORDER BY CASE WHEN s.email IS NOT NULL THEN 0 ELSE 1 END
        LIMIT 1
    ");
    $stmt->execute([$userEmail, $userEmail]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Current query result:\n";
    print_r($result);
    echo "\n";
    
    // 5. Test improved query
    echo "5. Testing improved query:\n";
    $stmt = $pdo->prepare("
        SELECT s.plan_code, sp.plan_name, sp.status as plan_status, s.created_at,
               s.subscription_status, s.is_active
        FROM subscriptions s
        JOIN subscription_plans sp ON s.plan_code = sp.plan_code
        WHERE s.email = ? AND s.is_active = 1
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userEmail]);
    $activeSubscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($activeSubscription) {
        echo "✓ Active subscription found:\n";
        print_r($activeSubscription);
    } else {
        echo "✗ No active subscription found, checking for any subscription:\n";
        
        $stmt = $pdo->prepare("
            SELECT s.plan_code, sp.plan_name, sp.status as plan_status, s.created_at,
                   s.subscription_status, s.is_active
            FROM subscriptions s
            JOIN subscription_plans sp ON s.plan_code = sp.plan_code
            WHERE s.email = ?
            ORDER BY s.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userEmail]);
        $latestSubscription = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($latestSubscription) {
            echo "Latest subscription (may be inactive):\n";
            print_r($latestSubscription);
        } else {
            echo "No subscriptions found at all. Defaulting to basic plan.\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>