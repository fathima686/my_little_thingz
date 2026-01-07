<?php
// Debug live sessions access
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Debug Live Sessions Access</title>";
echo "<style>
body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} 
.container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:8px;} 
.success{color:#10b981;} .error{color:#ef4444;} .info{color:#3b82f6;}
pre{background:#f8f9fa;padding:10px;border-radius:4px;overflow-x:auto;font-size:12px;}
.step{background:#e0f2fe;padding:15px;border-radius:5px;margin:20px 0;}
</style>";
echo "</head><body><div class='container'>";

echo "<h1>🔍 Debug Live Sessions Access</h1>";

$userEmail = 'soudhame52@gmail.com';

try {
    require_once 'config/database.php';
    require_once 'models/FeatureAccessControl.php';
    
    $database = new Database();
    $db = $database->getConnection();
    $featureControl = new FeatureAccessControl($db);
    
    echo "<div class='step'>";
    echo "<h2 class='info'>Step 1: Get User ID</h2>";
    
    $userStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $userStmt->execute([$userEmail]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<p class='error'>❌ User not found: $userEmail</p>";
        exit;
    }
    
    $userId = $user['id'];
    echo "<p class='success'>✓ User ID: $userId</p>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h2 class='info'>Step 2: Check Current Subscription</h2>";
    
    $subStmt = $db->prepare("
        SELECT s.*, sp.plan_code, sp.name as plan_name 
        FROM subscriptions s 
        LEFT JOIN subscription_plans sp ON s.plan_id = sp.id 
        WHERE s.user_id = ? 
        ORDER BY s.created_at DESC
    ");
    $subStmt->execute([$userId]);
    $subscriptions = $subStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>All Subscriptions:</h3>";
    foreach ($subscriptions as $sub) {
        $statusColor = ($sub['status'] === 'active') ? 'success' : 'error';
        echo "<p class='$statusColor'>Plan: {$sub['plan_code']}, Status: {$sub['status']}, Created: {$sub['created_at']}</p>";
    }
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h2 class='info'>Step 3: Test FeatureAccessControl</h2>";
    
    $userPlan = $featureControl->getUserPlan($userId);
    echo "<p><strong>Detected Plan:</strong> $userPlan</p>";
    
    $canAccessLive = $featureControl->canAccessLiveWorkshops($userId);
    echo "<p><strong>Can Access Live Workshops:</strong> " . ($canAccessLive ? 'YES ✅' : 'NO ❌') . "</p>";
    
    $userFeatures = $featureControl->getUserFeatures($userId);
    echo "<p><strong>Available Features:</strong></p>";
    echo "<pre>" . print_r($userFeatures, true) . "</pre>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h2 class='info'>Step 4: Test Profile API Response</h2>";
    echo "<button onclick='testProfileAPI()'>Test Profile API</button>";
    echo "<div id='profileResult'></div>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h2 class='info'>Step 5: Test Live Sessions API</h2>";
    echo "<button onclick='testLiveAPI()'>Test Live Sessions API</button>";
    echo "<div id='liveResult'></div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

echo "<script>
const userEmail = '$userEmail';

function testProfileAPI() {
    const resultDiv = document.getElementById('profileResult');
    resultDiv.innerHTML = '<p>Testing profile API...</p>';
    
    fetch('api/customer/profile.php?email=' + encodeURIComponent(userEmail))
    .then(response => response.json())
    .then(data => {
        const canAccess = data.feature_access?.access_levels?.can_access_live_workshops;
        const statusClass = canAccess ? 'success' : 'error';
        const statusText = canAccess ? 'YES ✅' : 'NO ❌';
        
        resultDiv.innerHTML = '<p class=\"' + statusClass + '\">Can Access Live Workshops: ' + statusText + '</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
    })
    .catch(error => {
        resultDiv.innerHTML = '<p class=\"error\">❌ Error: ' + error.message + '</p>';
    });
}

function testLiveAPI() {
    const resultDiv = document.getElementById('liveResult');
    resultDiv.innerHTML = '<p>Testing live sessions API...</p>';
    
    fetch('api/customer/live-sessions.php', {
        headers: {
            'X-Tutorial-Email': userEmail
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            resultDiv.innerHTML = '<p class=\"success\">✅ Live sessions accessible! Found ' + data.sessions.length + ' sessions</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
        } else {
            resultDiv.innerHTML = '<p class=\"error\">❌ Live sessions blocked: ' + data.message + '</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }
    })
    .catch(error => {
        resultDiv.innerHTML = '<p class=\"error\">❌ Error: ' + error.message + '</p>';
    });
}
</script>";

echo "</body></html>";
?>