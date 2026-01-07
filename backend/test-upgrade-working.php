<?php
// Working test page for subscription upgrades (using correct database structure)
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Subscription Upgrade (Working)</title>";
echo "<style>
body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} 
.container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:8px;} 
.success{color:#10b981;} .error{color:#ef4444;} .info{color:#3b82f6;}
button{padding:10px 20px;margin:5px;border:none;border-radius:4px;cursor:pointer;background:#3b82f6;color:white;}
button:hover{background:#2563eb;}
.result{margin:15px 0;padding:15px;border-radius:5px;background:#f8f9fa;border-left:4px solid #e5e7eb;}
.result.success{background:#f0fdf4;border-left-color:#10b981;}
.result.error{background:#fef2f2;border-left-color:#ef4444;}
</style>";
echo "</head><body><div class='container'>";

echo "<h1>🧪 Test Subscription Upgrade (Working)</h1>";

$userEmail = 'soudhame52@gmail.com';

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Get user
    $userStmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $userStmt->execute([$userEmail]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<div class='result error'>❌ User not found: $userEmail</div>";
        exit;
    }
    
    $userId = $user['id'];
    $userName = trim($user['first_name'] . ' ' . $user['last_name']);
    
    // Show current subscription
    echo "<h2 class='info'>Current Subscription Status for $userName</h2>";
    $currentStmt = $db->prepare("
        SELECT s.*, sp.plan_code, sp.name as plan_name, sp.price 
        FROM subscriptions s 
        LEFT JOIN subscription_plans sp ON s.plan_id = sp.id 
        WHERE s.user_id = ? AND s.status = 'active'
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    $currentStmt->execute([$userId]);
    $current = $currentStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($current) {
        echo "<div class='result success'>";
        echo "<strong>Current Plan:</strong> {$current['plan_code']} ({$current['plan_name']})<br>";
        echo "<strong>Status:</strong> {$current['status']}<br>";
        echo "<strong>Price:</strong> ₹{$current['price']}/month<br>";
        echo "<strong>Created:</strong> {$current['created_at']}";
        echo "</div>";
    } else {
        echo "<div class='result error'>No active subscription found</div>";
    }
    
    // Handle upgrade request
    if ($_POST['upgrade_to']) {
        $newPlanCode = $_POST['upgrade_to'];
        
        echo "<h2 class='info'>Upgrading to $newPlanCode...</h2>";
        
        // Get plan by plan_code
        $planStmt = $db->prepare("SELECT * FROM subscription_plans WHERE plan_code = ? AND is_active = 1");
        $planStmt->execute([$newPlanCode]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan) {
            echo "<div class='result error'>❌ Plan '$newPlanCode' not found</div>";
        } else {
            // Check if already has this plan
            $existingStmt = $db->prepare("
                SELECT s.* FROM subscriptions s 
                WHERE s.user_id = ? AND s.plan_id = ? AND s.status = 'active'
            ");
            $existingStmt->execute([$userId, $plan['id']]);
            $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                echo "<div class='result error'>❌ You already have an active {$plan['name']} subscription</div>";
            } else {
                // Perform upgrade
                $db->beginTransaction();
                try {
                    // Cancel all current subscriptions
                    $cancelStmt = $db->prepare("UPDATE subscriptions SET status = 'cancelled', updated_at = NOW() WHERE user_id = ?");
                    $cancelStmt->execute([$userId]);
                    
                    // Create new subscription
                    $createStmt = $db->prepare("
                        INSERT INTO subscriptions (user_id, plan_id, status, created_at, updated_at) 
                        VALUES (?, ?, 'active', NOW(), NOW())
                    ");
                    $createStmt->execute([$userId, $plan['id']]);
                    
                    $db->commit();
                    
                    echo "<div class='result success'>✅ Successfully upgraded to {$plan['name']}!</div>";
                    
                    // Refresh page to show new status
                    echo "<script>setTimeout(() => location.reload(), 2000);</script>";
                    
                } catch (Exception $e) {
                    $db->rollback();
                    echo "<div class='result error'>❌ Upgrade failed: " . $e->getMessage() . "</div>";
                }
            }
        }
    }
    
    // Show upgrade options
    echo "<h2 class='info'>Available Upgrades</h2>";
    
    // Get all available plans
    $plansStmt = $db->query("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY price ASC");
    $plans = $plansStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<form method='POST'>";
    echo "<p>Choose a plan to upgrade to:</p>";
    foreach ($plans as $plan) {
        $isCurrentPlan = $current && $current['plan_code'] === $plan['plan_code'];
        $buttonStyle = $isCurrentPlan ? 'background:#9ca3af;cursor:not-allowed;' : '';
        $buttonText = $isCurrentPlan ? "Current Plan" : "Upgrade to {$plan['name']} (₹{$plan['price']}/month)";
        
        echo "<button type='submit' name='upgrade_to' value='{$plan['plan_code']}' style='$buttonStyle' " . ($isCurrentPlan ? 'disabled' : '') . ">$buttonText</button><br>";
    }
    echo "</form>";
    
    // Test API endpoint
    echo "<h2 class='info'>Test API Endpoint</h2>";
    echo "<p>You can also test the API directly:</p>";
    echo "<code>POST api/customer/upgrade-subscription-working.php</code>";
    echo "<br><br>";
    
    foreach ($plans as $plan) {
        if (!$current || $current['plan_code'] !== $plan['plan_code']) {
            echo "<button onclick='testAPI(\"{$plan['plan_code']}\")'>Test API Upgrade to {$plan['name']}</button>";
        }
    }
    
    echo "<div id='apiResult'></div>";
    
    // Test profile API
    echo "<h2 class='info'>Test Profile API</h2>";
    echo "<button onclick='testProfileAPI()'>Test Profile API</button>";
    echo "<div id='profileResult'></div>";
    
} catch (Exception $e) {
    echo "<div class='result error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

echo "<script>
function testAPI(planCode) {
    const resultDiv = document.getElementById('apiResult');
    resultDiv.innerHTML = '<div class=\"result\">Testing API upgrade to ' + planCode + '...</div>';
    
    fetch('api/customer/upgrade-subscription-working.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Tutorial-Email': '$userEmail'
        },
        body: JSON.stringify({
            email: '$userEmail',
            plan_code: planCode
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            resultDiv.innerHTML = '<div class=\"result success\">✅ API Upgrade successful: ' + data.message + '</div>';
            setTimeout(() => location.reload(), 2000);
        } else {
            resultDiv.innerHTML = '<div class=\"result error\">❌ API Upgrade failed: ' + data.message + '</div>';
        }
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class=\"result error\">❌ API Error: ' + error.message + '</div>';
    });
}

function testProfileAPI() {
    const resultDiv = document.getElementById('profileResult');
    resultDiv.innerHTML = '<div class=\"result\">Testing Profile API...</div>';
    
    fetch('api/customer/profile-working.php?email=' + encodeURIComponent('$userEmail'))
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const sub = data.subscription;
            resultDiv.innerHTML = '<div class=\"result success\">✅ Profile API working!<br>' +
                'Plan: ' + sub.plan_code + ' (' + sub.plan_name + ')<br>' +
                'Status: ' + sub.subscription_status + '<br>' +
                'Active: ' + (sub.is_active ? 'YES' : 'NO') + '<br>' +
                'Pro User: ' + (data.stats.is_pro_user ? 'YES' : 'NO') +
                '</div>';
        } else {
            resultDiv.innerHTML = '<div class=\"result error\">❌ Profile API failed: ' + data.message + '</div>';
        }
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class=\"result error\">❌ Profile API Error: ' + error.message + '</div>';
    });
}
</script>";

echo "</body></html>";
?>