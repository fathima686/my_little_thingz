<?php
// Check User Subscription Status
header('Content-Type: application/json');

echo "🔍 Checking User Subscription Status\n\n";

$testEmail = 'soudhame52@gmail.com';
echo "Checking subscriptions for: $testEmail\n\n";

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
    
    // Check all subscriptions for this user
    $subsStmt = $db->prepare("
        SELECT s.*, sp.plan_code, sp.name as plan_name, sp.price
        FROM subscriptions s
        JOIN subscription_plans sp ON s.plan_id = sp.id
        WHERE s.user_id = ?
        ORDER BY s.id DESC
    ");
    $subsStmt->execute([$userId]);
    $subscriptions = $subsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 Total subscriptions found: " . count($subscriptions) . "\n\n";
    
    if (empty($subscriptions)) {
        echo "ℹ️ No subscriptions found for this user.\n";
        echo "✅ User can create new subscription.\n";
    } else {
        echo "📋 Subscription History:\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-5s %-10s %-15s %-12s %-20s %-15s\n", "ID", "Plan", "Status", "Price", "Created", "Current End");
        echo str_repeat("-", 80) . "\n";
        
        $hasActive = false;
        foreach ($subscriptions as $sub) {
            printf("%-5s %-10s %-15s ₹%-10s %-20s %-15s\n", 
                $sub['id'],
                $sub['plan_code'],
                $sub['status'],
                $sub['price'],
                $sub['created_at'],
                $sub['current_end'] ?? 'N/A'
            );
            
            if ($sub['status'] === 'active') {
                $hasActive = true;
                echo "  ⚠️ ACTIVE SUBSCRIPTION FOUND!\n";
            }
        }
        
        echo str_repeat("-", 80) . "\n\n";
        
        if ($hasActive) {
            echo "❌ User already has active subscription(s).\n";
            echo "💡 This might be causing the 400 error when trying to create new subscription.\n\n";
            
            echo "🔧 Solutions:\n";
            echo "1. Cancel existing active subscriptions first\n";
            echo "2. Allow upgrade/downgrade by modifying existing subscription\n";
            echo "3. Check frontend to handle 'already subscribed' response properly\n\n";
            
            // Show active subscriptions details
            $activeStmt = $db->prepare("
                SELECT s.*, sp.plan_code, sp.name as plan_name, sp.price
                FROM subscriptions s
                JOIN subscription_plans sp ON s.plan_id = sp.id
                WHERE s.user_id = ? AND s.status = 'active'
                ORDER BY s.id DESC
            ");
            $activeStmt->execute([$userId]);
            $activeSubscriptions = $activeStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "🔴 Active Subscriptions:\n";
            foreach ($activeSubscriptions as $activeSub) {
                echo "- ID: {$activeSub['id']}\n";
                echo "  Plan: {$activeSub['plan_code']} ({$activeSub['plan_name']})\n";
                echo "  Price: ₹{$activeSub['price']}\n";
                echo "  Start: {$activeSub['current_start']}\n";
                echo "  End: {$activeSub['current_end']}\n";
                echo "  Razorpay ID: {$activeSub['razorpay_subscription_id']}\n\n";
            }
        } else {
            echo "✅ No active subscriptions found.\n";
            echo "✅ User can create new subscription.\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Check completed at: " . date('Y-m-d H:i:s') . "\n";
?>