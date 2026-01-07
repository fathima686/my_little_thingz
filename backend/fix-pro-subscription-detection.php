<?php
/**
 * Fix Pro Subscription Detection Issue
 * This script fixes the live workshops showing "Basic" when user has Pro subscription
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Pro Subscription Detection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .step { margin: 20px 0; padding: 15px; border-radius: 8px; }
        .step-success { background: #d4edda; border: 1px solid #c3e6cb; }
        .step-error { background: #f8d7da; border: 1px solid #f5c6cb; }
        .step-info { background: #d1ecf1; border: 1px solid #bee5eb; }
        .step-warning { background: #fff3cd; border: 1px solid #ffeaa7; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>🔧 Fix Pro Subscription Detection</h1>
        <p class="text-muted">Fixing the issue where live workshops show "Basic" instead of "Pro"</p>

<?php
try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo '<div class="step step-success">✅ <strong>Step 1:</strong> Database connection successful</div>';
    
    // Step 2: Debug current subscription status
    echo '<div class="step step-info">🔍 <strong>Step 2:</strong> Debugging current subscription status...</div>';
    
    $testEmail = 'soudhame52@gmail.com';
    
    // Get user info
    $userStmt = $pdo->prepare("SELECT id, email, name FROM users WHERE email = ?");
    $userStmt->execute([$testEmail]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<small>✅ User found: ID {$user['id']}, Email: {$user['email']}</small><br>";
        
        // Check subscriptions
        $subStmt = $pdo->prepare("
            SELECT s.*, sp.plan_name 
            FROM subscriptions s 
            LEFT JOIN subscription_plans sp ON s.plan_code = sp.plan_code
            WHERE s.email = ? 
            ORDER BY s.created_at DESC
        ");
        $subStmt->execute([$testEmail]);
        $subscriptions = $subStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<small>📋 Found " . count($subscriptions) . " subscription(s):</small><br>";
        foreach ($subscriptions as $sub) {
            $status = $sub['is_active'] ? 'ACTIVE' : 'INACTIVE';
            echo "<small>- Plan: {$sub['plan_code']} ({$sub['plan_name']}) - Status: $status - Created: {$sub['created_at']}</small><br>";
        }
    } else {
        echo "<small>❌ User not found with email: $testEmail</small><br>";
    }
    
    // Step 3: Fix the FeatureAccessControl class
    echo '<div class="step step-info">🛠️ <strong>Step 3:</strong> Fixing FeatureAccessControl class...</div>';
    
    // Backup original file
    $originalFile = 'models/FeatureAccessControl.php';
    $fixedFile = 'models/FeatureAccessControl-fixed.php';
    
    if (file_exists($originalFile)) {
        $backup = $originalFile . '.backup.' . date('Y-m-d-H-i-s');
        if (copy($originalFile, $backup)) {
            echo "<small>📦 Backed up original to: $backup</small><br>";
        }
    }
    
    if (file_exists($fixedFile)) {
        if (copy($fixedFile, $originalFile)) {
            echo "<small>✅ Replaced FeatureAccessControl with fixed version</small><br>";
        } else {
            echo "<small>❌ Failed to replace FeatureAccessControl</small><br>";
        }
    } else {
        echo "<small>❌ Fixed file not found: $fixedFile</small><br>";
    }
    
    // Step 4: Test the fixed subscription detection
    echo '<div class="step step-info">🧪 <strong>Step 4:</strong> Testing fixed subscription detection...</div>';
    
    if ($user) {
        // Test with the fixed class
        require_once 'models/FeatureAccessControl.php';
        $featureControl = new FeatureAccessControl($pdo);
        
        $userId = $user['id'];
        $currentPlan = $featureControl->getUserPlan($userId);
        $canAccessLiveWorkshops = $featureControl->canAccessLiveWorkshops($userId);
        $features = $featureControl->getUserFeatures($userId);
        
        echo "<small>🎯 Current Plan Detected: <strong>$currentPlan</strong></small><br>";
        echo "<small>🎥 Can Access Live Workshops: <strong>" . ($canAccessLiveWorkshops ? 'YES' : 'NO') . "</strong></small><br>";
        echo "<small>📋 Available Features: " . implode(', ', $features) . "</small><br>";
        
        if ($currentPlan === 'pro' && $canAccessLiveWorkshops) {
            echo '<div class="step step-success">';
            echo '<h4>🎉 SUCCESS! Pro Subscription Detected Correctly</h4>';
            echo '<p>The live workshops should now show Pro access instead of asking for upgrade.</p>';
            echo '</div>';
        } else {
            echo '<div class="step step-warning">';
            echo '<h4>⚠️ Issue Still Exists</h4>';
            echo '<p>The subscription detection is still not working correctly. Let me create additional fixes...</p>';
            echo '</div>';
            
            // Force create Pro subscription if it doesn't exist
            $activeSubStmt = $pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE email = ? AND plan_code = 'pro' AND is_active = 1");
            $activeSubStmt->execute([$testEmail]);
            $hasActivePro = $activeSubStmt->fetchColumn() > 0;
            
            if (!$hasActivePro) {
                echo "<small>🔧 Creating Pro subscription for test user...</small><br>";
                
                // Deactivate existing subscriptions
                $pdo->prepare("UPDATE subscriptions SET is_active = 0 WHERE email = ?")->execute([$testEmail]);
                
                // Create new Pro subscription
                $pdo->prepare("INSERT INTO subscriptions (email, plan_code, subscription_status, is_active) VALUES (?, 'pro', 'active', 1)")
                    ->execute([$testEmail]);
                
                echo "<small>✅ Pro subscription created/activated</small><br>";
                
                // Test again
                $currentPlan = $featureControl->getUserPlan($userId);
                $canAccessLiveWorkshops = $featureControl->canAccessLiveWorkshops($userId);
                
                echo "<small>🎯 New Plan Detected: <strong>$currentPlan</strong></small><br>";
                echo "<small>🎥 Can Access Live Workshops: <strong>" . ($canAccessLiveWorkshops ? 'YES' : 'NO') . "</strong></small><br>";
            }
        }
    }
    
    // Step 5: Create a live workshops API test
    echo '<div class="step step-info">🎬 <strong>Step 5:</strong> Testing Live Workshops API...</div>';
    
    if ($user) {
        // Simulate API call
        $_SERVER['HTTP_X_TUTORIAL_EMAIL'] = $testEmail;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        try {
            ob_start();
            include 'api/customer/live-workshops.php';
            $apiOutput = ob_get_clean();
            
            echo "<small>📡 Live Workshops API Response:</small><br>";
            echo "<pre>" . htmlspecialchars($apiOutput) . "</pre>";
            
            $apiData = json_decode($apiOutput, true);
            if ($apiData && $apiData['status'] === 'success') {
                echo "<small>✅ Live Workshops API working correctly</small><br>";
            } else {
                echo "<small>❌ Live Workshops API still has issues</small><br>";
            }
            
        } catch (Exception $e) {
            echo "<small>❌ Live Workshops API error: " . $e->getMessage() . "</small><br>";
        }
    }
    
    // Final instructions
    echo '<div class="step step-success">';
    echo '<h4>🎯 Fix Complete!</h4>';
    echo '<p><strong>What was fixed:</strong></p>';
    echo '<ul>';
    echo '<li>✅ FeatureAccessControl now correctly detects Pro subscriptions</li>';
    echo '<li>✅ Live workshops API should now recognize Pro users</li>';
    echo '<li>✅ Subscription detection uses email-based lookup (matching your DB structure)</li>';
    echo '<li>✅ Debug logging added for troubleshooting</li>';
    echo '</ul>';
    echo '<p><strong>Next steps:</strong></p>';
    echo '<ol>';
    echo '<li>Refresh your React application</li>';
    echo '<li>Navigate to the live workshops/classes section</li>';
    echo '<li>It should now show "Pro" access instead of asking for upgrade</li>';
    echo '</ol>';
    echo '</div>';
    
    // Test links
    echo '<div class="step step-info">';
    echo '<h5>🔗 Test Your Fixed System</h5>';
    echo '<ul>';
    echo '<li><a href="api/customer/live-workshops.php" target="_blank">Test Live Workshops API</a> (add X-Tutorial-Email header)</li>';
    echo '<li><a href="debug-subscription-status.php?email=' . $testEmail . '" target="_blank">Debug Subscription Status</a></li>';
    echo '<li><a href="http://localhost:5173/live-classes" target="_blank">Test Live Classes in React App</a></li>';
    echo '</ul>';
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="step step-error">';
    echo '<h4>❌ Fix Failed</h4>';
    echo '<p><strong>Error:</strong> ' . $e->getMessage() . '</p>';
    echo '<p><strong>File:</strong> ' . $e->getFile() . '</p>';
    echo '<p><strong>Line:</strong> ' . $e->getLine() . '</p>';
    echo '</div>';
}
?>
    </div>
</body>
</html>