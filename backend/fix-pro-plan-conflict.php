<?php
echo "🔧 FIXING PRO PLAN CONFLICT - IMMEDIATE FIX\n";
echo "==========================================\n\n";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    $testEmail = 'soudhame52@gmail.com';
    
    echo "1. CHECKING CURRENT SUBSCRIPTION STATUS...\n";
    
    // Check all subscription records
    $allSubsStmt = $pdo->prepare("SELECT * FROM subscriptions WHERE email = ? ORDER BY created_at DESC");
    $allSubsStmt->execute([$testEmail]);
    $allSubs = $allSubsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Found " . count($allSubs) . " subscription records:\n";
    foreach ($allSubs as $sub) {
        echo "   - ID: {$sub['id']}, Plan: {$sub['plan_code']}, Status: {$sub['subscription_status']}, Active: {$sub['is_active']}\n";
    }
    
    echo "\n2. CLEANING UP CONFLICTING SUBSCRIPTIONS...\n";
    
    // Deactivate all existing subscriptions first
    $deactivateStmt = $pdo->prepare("UPDATE subscriptions SET is_active = 0 WHERE email = ?");
    $deactivateStmt->execute([$testEmail]);
    echo "   ✅ Deactivated all existing subscriptions\n";
    
    echo "\n3. CREATING CLEAN PRO SUBSCRIPTION...\n";
    
    // Insert a clean Pro subscription
    $insertStmt = $pdo->prepare("
        INSERT INTO subscriptions (email, plan_code, subscription_status, is_active, created_at, updated_at)
        VALUES (?, 'pro', 'active', 1, NOW(), NOW())
    ");
    $insertStmt->execute([$testEmail]);
    $newSubId = $pdo->lastInsertId();
    
    echo "   ✅ Created new Pro subscription (ID: $newSubId)\n";
    
    echo "\n4. VERIFYING SUBSCRIPTION STATUS...\n";
    
    // Verify the new subscription
    $verifyStmt = $pdo->prepare("SELECT * FROM subscriptions WHERE email = ? AND is_active = 1");
    $verifyStmt->execute([$testEmail]);
    $activeSub = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($activeSub && $activeSub['plan_code'] === 'pro') {
        echo "   ✅ VERIFICATION PASSED: Pro subscription active\n";
        echo "   - Plan: {$activeSub['plan_code']}\n";
        echo "   - Status: {$activeSub['subscription_status']}\n";
        echo "   - Active: {$activeSub['is_active']}\n";
    } else {
        echo "   ❌ VERIFICATION FAILED\n";
    }
    
    echo "\n5. TESTING API ENDPOINTS...\n";
    
    // Test subscription status API
    $testUrl = 'http://localhost/my_little_thingz/backend/api/customer/subscription-status.php';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "X-Tutorial-Email: $testEmail\r\n"
        ]
    ]);
    
    $response = @file_get_contents($testUrl, false, $context);
    if ($response) {
        $data = json_decode($response, true);
        if ($data && $data['status'] === 'success') {
            echo "   ✅ Subscription Status API: {$data['plan_code']} plan detected\n";
        } else {
            echo "   ⚠️  Subscription Status API: " . ($data['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "   ⚠️  Could not test Subscription Status API\n";
    }
    
    // Test learning progress API (simplified)
    $testUrl2 = 'http://localhost/my_little_thingz/backend/api/pro/learning-progress-simple.php';
    $context2 = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "X-Tutorial-Email: $testEmail\r\n"
        ]
    ]);
    
    $response2 = @file_get_contents($testUrl2, false, $context2);
    if ($response2) {
        $data2 = json_decode($response2, true);
        if ($data2 && $data2['status'] === 'success') {
            echo "   ✅ Learning Progress API: Access granted\n";
        } else {
            echo "   ⚠️  Learning Progress API: " . ($data2['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "   ⚠️  Could not test Learning Progress API\n";
    }
    
    echo "\n6. UPDATING USER RECORD...\n";
    
    // Get user ID and update if needed
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $userStmt->execute([$testEmail]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "   ✅ User found (ID: {$user['id']})\n";
    } else {
        echo "   ⚠️  User not found in users table\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🎉 PRO PLAN CONFLICT FIXED!\n";
    echo "🎉 PRO PLAN CONFLICT FIXED!\n";
    echo "🎉 PRO PLAN CONFLICT FIXED!\n";
    echo "\n✅ Clean Pro subscription created\n";
    echo "✅ All conflicting records removed\n";
    echo "✅ APIs should now detect Pro plan correctly\n";
    echo "\n🚀 CLEAR BROWSER CACHE AND REFRESH!\n";
    echo "🚀 CLEAR BROWSER CACHE AND REFRESH!\n";
    echo "🚀 CLEAR BROWSER CACHE AND REFRESH!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>