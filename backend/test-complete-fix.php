<?php
// Complete test to verify subscription and live sessions are working
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Complete Fix Test</title>";
echo "<style>
body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} 
.container{max-width:1000px;margin:0 auto;background:white;padding:20px;border-radius:8px;} 
.success{color:#10b981;} .error{color:#ef4444;} .info{color:#3b82f6;} .warning{color:#f59e0b;}
button{padding:10px 20px;margin:5px;border:none;border-radius:4px;cursor:pointer;background:#3b82f6;color:white;}
button:hover{background:#2563eb;}
.result{margin:15px 0;padding:15px;border-radius:5px;background:#f8f9fa;border-left:4px solid #e5e7eb;}
.result.success{background:#f0fdf4;border-left-color:#10b981;}
.result.error{background:#fef2f2;border-left-color:#ef4444;}
.result.warning{background:#fef3c7;border-left-color:#f59e0b;}
pre{background:#f8f9fa;padding:10px;border-radius:4px;overflow-x:auto;font-size:12px;max-height:300px;overflow-y:auto;}
.step{background:#e0f2fe;padding:15px;border-radius:5px;margin:20px 0;}
.comparison{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin:20px 0;}
.comparison-item{padding:15px;border-radius:8px;border:2px solid #e5e7eb;}
.before{border-color:#ef4444;background:#fef2f2;}
.after{border-color:#10b981;background:#f0fdf4;}
</style>";
echo "</head><body><div class='container'>";

echo "<h1>🔧 Complete Subscription & Live Sessions Fix Test</h1>";

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
    
    echo "<div class='step'>";
    echo "<h2 class='info'>Step 1: Current Database Status</h2>";
    echo "<p><strong>User:</strong> $userName (ID: $userId, Email: $userEmail)</p>";
    
    // Check current subscription in database
    $currentStmt = $db->prepare("
        SELECT s.*, sp.plan_code, sp.name as plan_name, sp.price 
        FROM subscriptions s 
        LEFT JOIN subscription_plans sp ON s.plan_id = sp.id 
        WHERE s.user_id = ? 
        ORDER BY s.created_at DESC
    ");
    $currentStmt->execute([$userId]);
    $allSubs = $currentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>All Subscriptions in Database:</h3>";
    if (empty($allSubs)) {
        echo "<p class='warning'>⚠️ No subscriptions found in database</p>";
    } else {
        echo "<table style='border-collapse:collapse;width:100%;margin:10px 0;'>";
        echo "<tr style='background:#f9fafb;'><th style='border:1px solid #e5e7eb;padding:8px;'>Plan</th><th style='border:1px solid #e5e7eb;padding:8px;'>Status</th><th style='border:1px solid #e5e7eb;padding:8px;'>Price</th><th style='border:1px solid #e5e7eb;padding:8px;'>Created</th></tr>";
        foreach ($allSubs as $sub) {
            $statusColor = ($sub['status'] === 'active') ? 'success' : 'error';
            echo "<tr>";
            echo "<td style='border:1px solid #e5e7eb;padding:8px;'>{$sub['plan_code']}</td>";
            echo "<td style='border:1px solid #e5e7eb;padding:8px;' class='$statusColor'>{$sub['status']}</td>";
            echo "<td style='border:1px solid #e5e7eb;padding:8px;'>₹{$sub['price']}</td>";
            echo "<td style='border:1px solid #e5e7eb;padding:8px;'>{$sub['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h2 class='info'>Step 2: API Response Comparison</h2>";
    echo "<div class='comparison'>";
    echo "<div class='comparison-item before'>";
    echo "<h3>❌ Before Fix (Old Profile API)</h3>";
    echo "<p>Shows: basic plan, inactive, no live access</p>";
    echo "<button onclick='testOldAPI()'>Test Old Profile API</button>";
    echo "<div id='oldApiResult'></div>";
    echo "</div>";
    echo "<div class='comparison-item after'>";
    echo "<h3>✅ After Fix (New Profile API)</h3>";
    echo "<p>Shows: actual active subscription, live access</p>";
    echo "<button onclick='testNewAPI()'>Test New Profile API</button>";
    echo "<div id='newApiResult'></div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h2 class='info'>Step 3: Live Sessions Access Test</h2>";
    echo "<button onclick='testLiveWorkshops()'>Test Live Workshops Access</button>";
    echo "<div id='liveResult'></div>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h2 class='info'>Step 4: Subscription Upgrade Test</h2>";
    echo "<p>Test upgrading between plans:</p>";
    echo "<button onclick='upgradeToFree()'>Upgrade to Free</button>";
    echo "<button onclick='upgradeToPremium()'>Upgrade to Premium</button>";
    echo "<button onclick='upgradeToPro()'>Upgrade to Pro</button>";
    echo "<div id='upgradeResult'></div>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h2 class='info'>Step 5: Complete Verification</h2>";
    echo "<button onclick='runCompleteTest()'>Run Complete Verification</button>";
    echo "<div id='completeResult'></div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='result error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

echo "<script>
const userEmail = '$userEmail';

function testOldAPI() {
    const resultDiv = document.getElementById('oldApiResult');
    resultDiv.innerHTML = '<div class=\"result\">Testing old profile API...</div>';
    
    fetch('api/customer/profile.php?email=' + encodeURIComponent(userEmail))
    .then(response => response.json())
    .then(data => {
        const sub = data.subscription;
        const isActive = sub.is_active;
        const statusClass = isActive ? 'success' : 'error';
        const statusText = isActive ? 'ACTIVE' : 'INACTIVE';
        
        resultDiv.innerHTML = '<div class=\"result ' + statusClass + '\">Plan: ' + sub.plan_code + ' (' + statusText + ')<br>Pro User: ' + (data.stats.is_pro_user ? 'YES' : 'NO') + '</div><details><summary>Full Response</summary><pre>' + JSON.stringify(data, null, 2) + '</pre></details>';
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class=\"result error\">❌ Error: ' + error.message + '</div>';
    });
}

function testNewAPI() {
    const resultDiv = document.getElementById('newApiResult');
    resultDiv.innerHTML = '<div class=\"result\">Testing new profile API...</div>';
    
    fetch('api/customer/profile-working.php?email=' + encodeURIComponent(userEmail))
    .then(response => response.json())
    .then(data => {
        const sub = data.subscription;
        const isActive = sub.is_active;
        const statusClass = isActive ? 'success' : 'error';
        const statusText = isActive ? 'ACTIVE' : 'INACTIVE';
        
        resultDiv.innerHTML = '<div class=\"result ' + statusClass + '\">Plan: ' + sub.plan_code + ' (' + statusText + ')<br>Pro User: ' + (data.stats.is_pro_user ? 'YES' : 'NO') + '</div><details><summary>Full Response</summary><pre>' + JSON.stringify(data, null, 2) + '</pre></details>';
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class=\"result error\">❌ Error: ' + error.message + '</div>';
    });
}

function testLiveWorkshops() {
    const resultDiv = document.getElementById('liveResult');
    resultDiv.innerHTML = '<div class=\"result\">Testing live workshops access...</div>';
    
    fetch('api/customer/live-workshops-corrected.php?email=' + encodeURIComponent(userEmail))
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            resultDiv.innerHTML = '<div class=\"result success\">✅ Live workshops accessible!<br>Found ' + data.total_workshops + ' workshops<br>Access Level: ' + data.access_level + '</div><details><summary>Workshops</summary><pre>' + JSON.stringify(data.workshops, null, 2) + '</pre></details>';
        } else {
            resultDiv.innerHTML = '<div class=\"result error\">❌ Live workshops blocked: ' + data.message + '<br>Current Plan: ' + data.current_plan + '</div><details><summary>Full Response</summary><pre>' + JSON.stringify(data, null, 2) + '</pre></details>';
        }
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class=\"result error\">❌ Error: ' + error.message + '</div>';
    });
}

function upgradeToFree() { testUpgrade('free'); }
function upgradeToPremium() { testUpgrade('premium'); }
function upgradeToPro() { testUpgrade('pro'); }

function testUpgrade(planCode) {
    const resultDiv = document.getElementById('upgradeResult');
    resultDiv.innerHTML = '<div class=\"result\">Upgrading to ' + planCode + '...</div>';
    
    fetch('api/customer/upgrade-subscription-working.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Tutorial-Email': userEmail
        },
        body: JSON.stringify({
            email: userEmail,
            plan_code: planCode
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            resultDiv.innerHTML = '<div class=\"result success\">✅ Upgraded to ' + planCode + '!</div>';
            setTimeout(() => {
                testNewAPI();
                testLiveWorkshops();
            }, 1000);
        } else {
            resultDiv.innerHTML = '<div class=\"result error\">❌ Upgrade failed: ' + data.message + '</div>';
        }
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class=\"result error\">❌ Error: ' + error.message + '</div>';
    });
}

function runCompleteTest() {
    const resultDiv = document.getElementById('completeResult');
    resultDiv.innerHTML = '<div class=\"result\">Running complete verification...</div>';
    
    Promise.all([
        fetch('api/customer/profile.php?email=' + encodeURIComponent(userEmail)).then(r => r.json()),
        fetch('api/customer/profile-working.php?email=' + encodeURIComponent(userEmail)).then(r => r.json()),
        fetch('api/customer/live-workshops-corrected.php?email=' + encodeURIComponent(userEmail)).then(r => r.json())
    ])
    .then(([oldProfile, newProfile, liveWorkshops]) => {
        let html = '<div class=\"result success\">✅ Complete verification finished!</div>';
        
        html += '<h4>API Comparison:</h4>';
        html += '<table style=\"border-collapse:collapse;width:100%;margin:10px 0;\">';
        html += '<tr style=\"background:#f9fafb;\"><th style=\"border:1px solid #e5e7eb;padding:8px;\">API</th><th style=\"border:1px solid #e5e7eb;padding:8px;\">Plan</th><th style=\"border:1px solid #e5e7eb;padding:8px;\">Active</th><th style=\"border:1px solid #e5e7eb;padding:8px;\">Pro User</th><th style=\"border:1px solid #e5e7eb;padding:8px;\">Live Access</th></tr>';
        
        const oldPlan = oldProfile.subscription?.plan_code || 'N/A';
        const oldActive = oldProfile.subscription?.is_active ? 'YES' : 'NO';
        const oldPro = oldProfile.stats?.is_pro_user ? 'YES' : 'NO';
        
        const newPlan = newProfile.subscription?.plan_code || 'N/A';
        const newActive = newProfile.subscription?.is_active ? 'YES' : 'NO';
        const newPro = newProfile.stats?.is_pro_user ? 'YES' : 'NO';
        
        const liveAccess = liveWorkshops.status === 'success' ? 'YES' : 'NO';
        
        html += '<tr><td style=\"border:1px solid #e5e7eb;padding:8px;\">Old Profile</td><td style=\"border:1px solid #e5e7eb;padding:8px;\">' + oldPlan + '</td><td style=\"border:1px solid #e5e7eb;padding:8px;\">' + oldActive + '</td><td style=\"border:1px solid #e5e7eb;padding:8px;\">' + oldPro + '</td><td style=\"border:1px solid #e5e7eb;padding:8px;\">-</td></tr>';
        html += '<tr><td style=\"border:1px solid #e5e7eb;padding:8px;\">New Profile</td><td style=\"border:1px solid #e5e7eb;padding:8px;\">' + newPlan + '</td><td style=\"border:1px solid #e5e7eb;padding:8px;\">' + newActive + '</td><td style=\"border:1px solid #e5e7eb;padding:8px;\">' + newPro + '</td><td style=\"border:1px solid #e5e7eb;padding:8px;\">-</td></tr>';
        html += '<tr><td style=\"border:1px solid #e5e7eb;padding:8px;\">Live Workshops</td><td style=\"border:1px solid #e5e7eb;padding:8px;\">-</td><td style=\"border:1px solid #e5e7eb;padding:8px;\">-</td><td style=\"border:1px solid #e5e7eb;padding:8px;\">-</td><td style=\"border:1px solid #e5e7eb;padding:8px;\">' + liveAccess + '</td></tr>';
        html += '</table>';
        
        // Check if everything is working
        const isFixed = newActive === 'YES' && newPro === 'YES' && liveAccess === 'YES';
        
        if (isFixed) {
            html += '<div class=\"result success\">🎉 Everything is working perfectly! Your subscription system is fixed.</div>';
        } else {
            html += '<div class=\"result warning\">⚠️ Some issues remain. Check the comparison above.</div>';
        }
        
        resultDiv.innerHTML = html;
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class=\"result error\">❌ Verification error: ' + error.message + '</div>';
    });
}
</script>";

echo "</body></html>";
?>