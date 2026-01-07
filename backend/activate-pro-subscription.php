<?php
header('Content-Type: application/json');

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    $userEmail = 'soudhame52@gmail.com';
    
    echo "<h1>Activating Pro Subscription for $userEmail</h1>";
    
    // Step 1: Check current subscription status
    echo "<h2>Step 1: Current Subscription Status</h2>";
    try {
        $checkStmt = $pdo->prepare("
            SELECT s.*, sp.plan_name, sp.price 
            FROM subscriptions s 
            LEFT JOIN subscription_plans sp ON s.plan_code = sp.plan_code 
            WHERE s.email = ? 
            ORDER BY s.created_at DESC 
            LIMIT 1
        ");
        $checkStmt->execute([$userEmail]);
        $currentSub = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($currentSub) {
            echo "<p><strong>Current Plan:</strong> {$currentSub['plan_code']}</p>";
            echo "<p><strong>Status:</strong> {$currentSub['subscription_status']}</p>";
            echo "<p><strong>Is Active:</strong> " . ($currentSub['is_active'] ? 'YES' : 'NO') . "</p>";
            echo "<p><strong>Created:</strong> {$currentSub['created_at']}</p>";
        } else {
            echo "<p>No subscription found.</p>";
        }
    } catch (Exception $e) {
        echo "<p>Error checking subscription: " . $e->getMessage() . "</p>";
    }
    
    // Step 2: Create/Update to Active Pro Subscription
    echo "<h2>Step 2: Activating Pro Subscription</h2>";
    
    // Create tables if they don't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS subscription_plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plan_code VARCHAR(50) UNIQUE NOT NULL,
        plan_name VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        duration_months INT NOT NULL,
        features JSON,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        plan_code VARCHAR(50) NOT NULL,
        subscription_status ENUM('active', 'inactive', 'cancelled', 'pending') DEFAULT 'active',
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Insert plans if they don't exist
    $planCheck = $pdo->query("SELECT COUNT(*) as count FROM subscription_plans")->fetch();
    if ($planCheck['count'] == 0) {
        $plans = [
            ['basic', 'Basic Plan', 0.00, 1, '["Access to free tutorials", "Individual purchases"]'],
            ['premium', 'Premium Plan', 199.00, 1, '["Access to all tutorials", "HD video", "Download videos", "Priority support"]'],
            ['pro', 'Pro Plan', 299.00, 1, '["All Premium features", "Live workshops", "Practice uploads", "Certificates", "Mentorship"]']
        ];
        
        $planStmt = $pdo->prepare("INSERT INTO subscription_plans (plan_code, plan_name, price, duration_months, features) VALUES (?, ?, ?, ?, ?)");
        foreach ($plans as $plan) {
            $planStmt->execute($plan);
        }
        echo "<p>✅ Created subscription plans</p>";
    }
    
    // Delete any existing subscriptions for this user
    $deleteStmt = $pdo->prepare("DELETE FROM subscriptions WHERE email = ?");
    $deleteStmt->execute([$userEmail]);
    echo "<p>🗑️ Removed old subscriptions</p>";
    
    // Insert new ACTIVE Pro subscription
    $insertStmt = $pdo->prepare("
        INSERT INTO subscriptions (email, plan_code, subscription_status, is_active, created_at) 
        VALUES (?, 'pro', 'active', 1, NOW())
    ");
    $insertStmt->execute([$userEmail]);
    echo "<p>✅ Created new ACTIVE Pro subscription</p>";
    
    // Step 3: Verify the new subscription
    echo "<h2>Step 3: Verification</h2>";
    $verifyStmt = $pdo->prepare("
        SELECT s.*, sp.plan_name, sp.price, sp.features
        FROM subscriptions s
        JOIN subscription_plans sp ON s.plan_code = sp.plan_code
        WHERE s.email = ? AND s.is_active = 1
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    $verifyStmt->execute([$userEmail]);
    $newSub = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($newSub && $newSub['subscription_status'] === 'active') {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "<h3>🎉 SUCCESS! Pro Subscription is now ACTIVE</h3>";
        echo "<p><strong>Plan:</strong> {$newSub['plan_code']} ({$newSub['plan_name']})</p>";
        echo "<p><strong>Status:</strong> {$newSub['subscription_status']}</p>";
        echo "<p><strong>Is Active:</strong> " . ($newSub['is_active'] ? 'YES' : 'NO') . "</p>";
        echo "<p><strong>Price:</strong> ₹{$newSub['price']}</p>";
        echo "<p><strong>Created:</strong> {$newSub['created_at']}</p>";
        echo "</div>";
        
        // Step 4: Test API endpoints
        echo "<h2>Step 4: Testing API Endpoints</h2>";
        
        // Test profile API
        echo "<h3>Profile API Test:</h3>";
        $profileUrl = "http://localhost/my_little_thingz/backend/api/customer/profile.php";
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "X-Tutorial-Email: $userEmail\r\n"
            ]
        ]);
        
        try {
            $profileResponse = file_get_contents($profileUrl, false, $context);
            $profileData = json_decode($profileResponse, true);
            
            if ($profileData && $profileData['status'] === 'success') {
                echo "<p>✅ Profile API: Working</p>";
                echo "<p>Plan: {$profileData['subscription']['plan_code']}</p>";
                echo "<p>Status: {$profileData['subscription']['subscription_status']}</p>";
            } else {
                echo "<p>❌ Profile API: Error</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Profile API: " . $e->getMessage() . "</p>";
        }
        
        // Test subscription status API
        echo "<h3>Subscription Status API Test:</h3>";
        $statusUrl = "http://localhost/my_little_thingz/backend/api/customer/subscription-status.php";
        
        try {
            $statusResponse = file_get_contents($statusUrl, false, $context);
            $statusData = json_decode($statusResponse, true);
            
            if ($statusData && $statusData['status'] === 'success') {
                echo "<p>✅ Subscription Status API: Working</p>";
                echo "<p>Plan: {$statusData['plan_code']}</p>";
                echo "<p>Can Access Live Workshops: " . ($statusData['feature_access']['access_levels']['can_access_live_workshops'] ? 'YES' : 'NO') . "</p>";
            } else {
                echo "<p>❌ Subscription Status API: Error</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Subscription Status API: " . $e->getMessage() . "</p>";
        }
        
        echo "<h2>🎯 Next Steps:</h2>";
        echo "<ol>";
        echo "<li>Refresh your profile page - it should now show 'Active' Pro subscription</li>";
        echo "<li>Go to Live Classes section - it should now show 'Pro' instead of 'Basic'</li>";
        echo "<li>All Pro features should now be available</li>";
        echo "</ol>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<h3>❌ ERROR: Failed to activate subscription</h3>";
        echo "<p>Please check the database connection and try again.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h3>❌ ERROR</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>