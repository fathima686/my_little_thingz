<?php
// Simple test page for subscription upgrades
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Subscription Upgrade</title>";
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

echo "<h1>🧪 Test Subscription Upgrade</h1>";

$userEmail = 'soudhame52@gmail.com';

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Show current subscription
    echo "<h2 class='info'>Current Subscription Status</h2>";
    $currentStmt = $db->prepare("
        SELECT s.*, sp.plan_name, sp.price 
        FROM subscriptions s 
        LEFT JOIN subscription_plans sp ON s.plan_code = sp.plan_code 
        WHERE s.email = ? AND s.is_active = 1
    ");
    $currentStmt->execute([$userEmail]);
    $current = $currentStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($current) {
        echo "<div class='result success'>";
        echo "<strong>Current Plan:</strong> {$current['plan_code']} ({$current['plan_name']})<br>";
        echo "<strong>Status:</strong> {$current['subscription_status']}<br>";
        echo "<strong>Active:</strong> " . ($current['is_active'] ? 'YES' : 'NO') . "<br>";
        echo "<strong>Price:</strong> ₹{$current['price']}/month";
        echo "</div>";
    } else {
        echo "<div class='result error'>No active subscription found</div>";
    }
    
    // Handle upgrade request
    if ($_POST['upgrade_to']) {
        $newPlan = $_POST['upgrade_to'];
        
        echo "<h2 class='info'>Upgrading to $newPlan...</h2>";
        
        // Check if plan exists
        $planStmt = $db->prepare("SELECT * FROM subscription_plans WHERE plan_code = ?");
        $planStmt->execute([$newPlan]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan) {
            echo "<div class='result error'>❌ Plan '$newPlan' not found</div>";
        } else {
            // Check if already has this plan
            $existingStmt = $db->prepare("SELECT * FROM subscriptions WHERE email = ? AND plan_code = ? AND is_active = 1");
            $existingStmt->execute([$userEmail, $newPlan]);
            $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                echo "<div class='result error'>❌ You already have an active {$plan['plan_name']} subscription</div>";
            } else {
                // Perform upgrade
                $db->beginTransaction();
                try {
                    // Deactivate all current subscriptions
                    $deactivateStmt = $db->prepare("UPDATE subscriptions SET is_active = 0 WHERE email = ?");
                    $deactivateStmt->execute([$userEmail]);
                    
                    // Create new subscription
                    $createStmt = $db->prepare("
                        INSERT INTO subscriptions (email, plan_code, subscription_status, is_active, created_at) 
                        VALUES (?, ?, 'active', 1, NOW())
                    ");
                    $createStmt->execute([$userEmail, $newPlan]);
                    
                    $db->commit();
                    
                    echo "<div class='result success'>✅ Successfully upgraded to {$plan['plan_name']}!</div>";
                    
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
    echo "<form method='POST'>";
    echo "<p>Choose a plan to upgrade to:</p>";
    echo "<button type='submit' name='upgrade_to' value='free'>Upgrade to Free</button>";
    echo "<button type='submit' name='upgrade_to' value='premium'>Upgrade to Premium</button>";
    echo "<button type='submit' name='upgrade_to' value='pro'>Upgrade to Pro</button>";
    echo "</form>";
    
    // Test API endpoint
    echo "<h2 class='info'>Test API Endpoint</h2>";
    echo "<p>You can also test the API directly:</p>";
    echo "<code>POST api/customer/upgrade-subscription-fixed.php</code>";
    echo "<br><br>";
    echo "<button onclick='testAPI(\"pro\")'>Test API Upgrade to Pro</button>";
    echo "<button onclick='testAPI(\"premium\")'>Test API Upgrade to Premium</button>";
    
    echo "<div id='apiResult'></div>";
    
} catch (Exception $e) {
    echo "<div class='result error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

echo "<script>
function testAPI(planCode) {
    const resultDiv = document.getElementById('apiResult');
    resultDiv.innerHTML = '<div class=\"result\">Testing API upgrade to ' + planCode + '...</div>';
    
    fetch('api/customer/upgrade-subscription-fixed.php', {
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
</script>";

echo "</body></html>";
?>