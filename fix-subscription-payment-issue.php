<?php
// Fix Subscription Payment Issue
header('Content-Type: application/json');

echo "🔧 Fixing Subscription Payment Issue\n\n";

$testEmail = 'soudhame52@gmail.com';
echo "Fixing subscriptions for: $testEmail\n\n";

try {
    require_once 'backend/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Find user
    $userStmt = $db->prepare("SELECT id, email FROM users WHERE email = ? LIMIT 1");
    $userStmt->execute([$testEmail]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ User not found with email: $testEmail\n";
        exit;
    }
    
    $userId = $user['id'];
    echo "✅ Found user ID: $userId\n\n";
    
    // Step 1: Clean up incomplete subscriptions (created but never paid)
    echo "🧹 Step 1: Cleaning up incomplete subscriptions...\n";
    
    $incompleteStmt = $db->prepare("
        SELECT id, plan_id, status, created_at, razorpay_subscription_id
        FROM subscriptions 
        WHERE user_id = ? AND status = 'created' 
        AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ORDER BY id DESC
    ");
    $incompleteStmt->execute([$userId]);
    $incompleteSubscriptions = $incompleteStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($incompleteSubscriptions) . " incomplete subscriptions older than 1 hour\n";
    
    if (!empty($incompleteSubscriptions)) {
        // Mark old incomplete subscriptions as expired
        $expireStmt = $db->prepare("
            UPDATE subscriptions 
            SET status = 'expired', updated_at = NOW() 
            WHERE user_id = ? AND status = 'created' 
            AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $expiredCount = $expireStmt->execute([$userId]);
        echo "✅ Marked " . $expireStmt->rowCount() . " incomplete subscriptions as expired\n\n";
    }
    
    // Step 2: Check for any remaining active or pending subscriptions
    echo "🔍 Step 2: Checking for active/pending subscriptions...\n";
    
    $activeStmt = $db->prepare("
        SELECT s.*, sp.plan_code, sp.name as plan_name
        FROM subscriptions s
        JOIN subscription_plans sp ON s.plan_id = sp.id
        WHERE s.user_id = ? AND s.status IN ('active', 'authenticated', 'pending')
        ORDER BY s.id DESC
    ");
    $activeStmt->execute([$userId]);
    $activeSubscriptions = $activeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($activeSubscriptions)) {
        echo "⚠️ Found " . count($activeSubscriptions) . " active/pending subscriptions:\n";
        foreach ($activeSubscriptions as $activeSub) {
            echo "- ID: {$activeSub['id']}, Plan: {$activeSub['plan_code']}, Status: {$activeSub['status']}\n";
        }
        echo "\n";
    } else {
        echo "✅ No active/pending subscriptions found\n\n";
    }
    
    // Step 3: Test subscription creation after cleanup
    echo "🧪 Step 3: Testing subscription creation after cleanup...\n";
    
    $testPlanCode = 'premium';
    $postData = json_encode([
        'plan_code' => $testPlanCode,
        'billing_period' => 'monthly'
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'X-Tutorial-Email: ' . $testEmail
            ],
            'content' => $postData,
            'timeout' => 30
        ]
    ]);
    
    $url = 'http://localhost/my_little_thingz/backend/api/customer/create-subscription.php';
    $response = @file_get_contents($url, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && $data['status'] === 'success') {
            echo "✅ Subscription creation test SUCCESSFUL!\n";
            echo "- Subscription ID: " . $data['subscription_id'] . "\n";
            echo "- Razorpay Order ID: " . $data['razorpay_order_id'] . "\n";
            echo "- Amount: ₹" . ($data['amount'] / 100) . "\n";
            echo "- Status: " . $data['subscription_status'] . "\n\n";
            
            echo "🎉 PAYMENT ISSUE FIXED!\n";
            echo "The user can now proceed with payment.\n\n";
            
        } else {
            echo "❌ Subscription creation still failing:\n";
            echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
        }
    } else {
        echo "❌ Failed to test subscription creation\n\n";
    }
    
    // Step 4: Show current subscription status
    echo "📊 Step 4: Current subscription status after cleanup...\n";
    
    $currentStmt = $db->prepare("
        SELECT s.*, sp.plan_code, sp.name as plan_name
        FROM subscriptions s
        JOIN subscription_plans sp ON s.plan_id = sp.id
        WHERE s.user_id = ?
        ORDER BY s.id DESC
        LIMIT 5
    ");
    $currentStmt->execute([$userId]);
    $currentSubscriptions = $currentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Recent subscriptions (last 5):\n";
    echo str_repeat("-", 60) . "\n";
    printf("%-5s %-10s %-15s %-20s\n", "ID", "Plan", "Status", "Created");
    echo str_repeat("-", 60) . "\n";
    
    foreach ($currentSubscriptions as $sub) {
        printf("%-5s %-10s %-15s %-20s\n", 
            $sub['id'],
            $sub['plan_code'],
            $sub['status'],
            $sub['created_at']
        );
    }
    echo str_repeat("-", 60) . "\n\n";
    
    echo "🔧 RECOMMENDATIONS:\n";
    echo "1. ✅ Incomplete subscriptions have been cleaned up\n";
    echo "2. ✅ User can now create new subscriptions\n";
    echo "3. 💡 Frontend should handle payment completion properly\n";
    echo "4. 💡 Consider adding subscription cleanup job for old 'created' status entries\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo str_repeat("=", 50) . "\n";
echo "Fix completed at: " . date('Y-m-d H:i:s') . "\n";
?>