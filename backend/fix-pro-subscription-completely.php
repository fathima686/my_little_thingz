<?php
header('Content-Type: text/html');

$userEmail = 'soudhame52@gmail.com';

echo "<h1>Complete Pro Subscription Fix</h1>";
echo "<p>Fixing all issues with Pro subscription for: <strong>$userEmail</strong></p>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2>Step 1: Database Setup</h2>";
    
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
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_plan_code (plan_code)
    )");
    
    echo "<p>✅ Tables created/verified</p>";
    
    // Insert/update plans
    $plans = [
        ['basic', 'Basic Plan', 0.00, 1, '["Access to free tutorials", "Individual purchases"]'],
        ['premium', 'Premium Plan', 199.00, 1, '["Access to all tutorials", "HD video", "Download videos", "Priority support"]'],
        ['pro', 'Pro Plan', 299.00, 1, '["All Premium features", "Live workshops", "Practice uploads", "Certificates", "Mentorship"]']
    ];
    
    foreach ($plans as $plan) {
        $stmt = $pdo->prepare("INSERT INTO subscription_plans (plan_code, plan_name, price, duration_months, features) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE plan_name = VALUES(plan_name), price = VALUES(price), features = VALUES(features)");
        $stmt->execute($plan);
    }
    echo "<p>✅ Subscription plans updated</p>";
    
    echo "<h2>Step 2: Fix User Subscription</h2>";
    
    // Delete any existing subscriptions for this user
    $deleteStmt = $pdo->prepare("DELETE FROM subscriptions WHERE email = ?");
    $deleteStmt->execute([$userEmail]);
    echo "<p>🗑️ Removed old subscriptions for $userEmail</p>";
    
    // Insert new ACTIVE Pro subscription
    $insertStmt = $pdo->prepare("
        INSERT INTO subscriptions (email, plan_code, subscription_status, is_active, created_at) 
        VALUES (?, 'pro', 'active', 1, NOW())
    ");
    $insertStmt->execute([$userEmail]);
    echo "<p>✅ Created ACTIVE Pro subscription for $userEmail</p>";
    
    echo "<h2>Step 3: Verification</h2>";
    
    // Verify subscription
    $verifyStmt = $pdo->prepare("
        SELECT s.*, sp.plan_name, sp.price, sp.features
        FROM subscriptions s
        JOIN subscription_plans sp ON s.plan_code = sp.plan_code
        WHERE s.email = ? AND s.is_active = 1
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    $verifyStmt->execute([$userEmail]);
    $subscription = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($subscription && $subscription['subscription_status'] === 'active') {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>🎉 SUCCESS! Pro Subscription Fixed</h3>";
        echo "<p><strong>Email:</strong> {$subscription['email'] ?? $userEmail}</p>";
        echo "<p><strong>Plan:</strong> {$subscription['plan_code']} ({$subscription['plan_name']})</p>";
        echo "<p><strong>Status:</strong> {$subscription['subscription_status']}</p>";
        echo "<p><strong>Is Active:</strong> " . ($subscription['is_active'] ? 'YES' : 'NO') . "</p>";
        echo "<p><strong>Price:</strong> ₹{$subscription['price']}</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<h3>❌ ERROR: Subscription not created properly</h3>";
        echo "</div>";
        exit;
    }
    
    echo "<h2>Step 4: Test APIs</h2>";
    
    // Test Profile API
    echo "<h3>Testing Profile API:</h3>";
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
            $sub = $profileData['subscription'];
            $stats = $profileData['stats'];
            
            echo "<div style='background: " . ($sub['plan_code'] === 'pro' && $sub['subscription_status'] === 'active' ? '#d4edda' : '#f8d7da') . "; padding: 10px; border-radius: 5px; margin: 5px 0;'>";
            echo "<p><strong>Profile API Result:</strong></p>";
            echo "<p>Email: {$profileData['user_email']}</p>";
            echo "<p>Plan: {$sub['plan_code']} ({$sub['plan_name']})</p>";
            echo "<p>Status: {$sub['subscription_status']}</p>";
            echo "<p>Is Active: " . ($sub['is_active'] ? 'YES' : 'NO') . "</p>";
            echo "<p>Is Pro User: " . ($stats['is_pro_user'] ? 'YES' : 'NO') . "</p>";
            echo "</div>";
            
            if ($profileData['user_email'] !== $userEmail) {
                echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px; margin: 5px 0;'>";
                echo "<p>⚠️ <strong>WARNING:</strong> Profile API is using wrong email: {$profileData['user_email']} instead of $userEmail</p>";
                echo "<p>This means the frontend is not sending the correct email header!</p>";
                echo "</div>";
            }
        } else {
            echo "<p>❌ Profile API Error: " . ($profileData['message'] ?? 'Unknown error') . "</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Profile API Network Error: " . $e->getMessage() . "</p>";
    }
    
    // Test Subscription Status API
    echo "<h3>Testing Subscription Status API:</h3>";
    $statusUrl = "http://localhost/my_little_thingz/backend/api/customer/subscription-status.php";
    
    try {
        $statusResponse = file_get_contents($statusUrl, false, $context);
        $statusData = json_decode($statusResponse, true);
        
        if ($statusData && $statusData['status'] === 'success') {
            $canAccessLive = $statusData['feature_access']['access_levels']['can_access_live_workshops'] ?? false;
            
            echo "<div style='background: " . ($statusData['plan_code'] === 'pro' && $canAccessLive ? '#d4edda' : '#f8d7da') . "; padding: 10px; border-radius: 5px; margin: 5px 0;'>";
            echo "<p><strong>Subscription Status API Result:</strong></p>";
            echo "<p>Plan: {$statusData['plan_code']}</p>";
            echo "<p>Status: {$statusData['subscription_status']}</p>";
            echo "<p>Can Access Live Workshops: " . ($canAccessLive ? 'YES' : 'NO') . "</p>";
            echo "</div>";
        } else {
            echo "<p>❌ Subscription Status API Error: " . ($statusData['message'] ?? 'Unknown error') . "</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Subscription Status API Network Error: " . $e->getMessage() . "</p>";
    }
    
    // Test Live Workshops API
    echo "<h3>Testing Live Workshops API:</h3>";
    $workshopsUrl = "http://localhost/my_little_thingz/backend/api/customer/live-workshops.php";
    
    try {
        $workshopsResponse = file_get_contents($workshopsUrl, false, $context);
        $workshopsData = json_decode($workshopsResponse, true);
        
        if ($workshopsData && $workshopsData['status'] === 'success') {
            echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0;'>";
            echo "<p>✅ <strong>Live Workshops API:</strong> Access granted</p>";
            echo "<p>Access Level: {$workshopsData['access_level']}</p>";
            echo "<p>Message: {$workshopsData['message']}</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 5px 0;'>";
            echo "<p>❌ <strong>Live Workshops API:</strong> Access denied</p>";
            echo "<p>Error: " . ($workshopsData['message'] ?? 'Unknown error') . "</p>";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Live Workshops API Network Error: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>🎯 Next Steps</h2>";
    echo "<ol>";
    echo "<li><strong>Clear browser cache</strong> (Ctrl+Shift+Delete)</li>";
    echo "<li><strong>Refresh the frontend</strong> (Ctrl+F5)</li>";
    echo "<li><strong>Check that the frontend is sending the correct email header</strong></li>";
    echo "<li><strong>Verify Pro features are now working</strong></li>";
    echo "</ol>";
    
    echo "<h2>🔧 Frontend Email Header Check</h2>";
    echo "<p>Make sure your frontend (React app) is sending the correct email in the <code>X-Tutorial-Email</code> header.</p>";
    echo "<p>Current email being sent: <strong>" . ($_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? 'NONE') . "</strong></p>";
    echo "<p>Expected email: <strong>$userEmail</strong></p>";
    
    if (($_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? '') !== $userEmail) {
        echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>⚠️ EMAIL HEADER ISSUE DETECTED</h3>";
        echo "<p>The frontend is not sending your correct email address. This is why you're seeing default data instead of your Pro subscription.</p>";
        echo "<p><strong>Fix needed:</strong> Check your authentication context in the React app to ensure it's sending the right email.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h3>❌ ERROR</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>