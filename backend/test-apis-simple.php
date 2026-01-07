<?php
// Simple API test page
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Test APIs Simple</title>";
echo "<style>
body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} 
.container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:8px;} 
.success{color:#10b981;} .error{color:#ef4444;} .info{color:#3b82f6;}
button{padding:10px 20px;margin:5px;border:none;border-radius:4px;cursor:pointer;background:#3b82f6;color:white;}
button:hover{background:#2563eb;}
.result{margin:15px 0;padding:15px;border-radius:5px;background:#f8f9fa;border-left:4px solid #e5e7eb;}
.result.success{background:#f0fdf4;border-left-color:#10b981;}
.result.error{background:#fef2f2;border-left-color:#ef4444;}
pre{background:#f8f9fa;padding:10px;border-radius:4px;overflow-x:auto;font-size:12px;}
</style>";
echo "</head><body><div class='container'>";

echo "<h1>🧪 Test APIs Simple</h1>";

$userEmail = 'soudhame52@gmail.com';

echo "<h2 class='info'>Test Profile API</h2>";
echo "<button onclick='testProfile()'>Test Profile API</button>";
echo "<div id='profileResult'></div>";

echo "<h2 class='info'>Test Upgrade APIs</h2>";
echo "<button onclick='testUpgrade(\"free\")'>Test Upgrade to Free</button>";
echo "<button onclick='testUpgrade(\"premium\")'>Test Upgrade to Premium</button>";
echo "<button onclick='testUpgrade(\"pro\")'>Test Upgrade to Pro</button>";
echo "<div id='upgradeResult'></div>";

echo "<h2 class='info'>Current Status Check</h2>";
echo "<button onclick='checkCurrentStatus()'>Check Current Status</button>";
echo "<div id='statusResult'></div>";

echo "</div>";

echo "<script>
const userEmail = '$userEmail';

function testProfile() {
    const resultDiv = document.getElementById('profileResult');
    resultDiv.innerHTML = '<div class=\"result\">Testing Profile API...</div>';
    
    fetch('api/customer/profile-working.php?email=' + encodeURIComponent(userEmail))
    .then(response => {
        console.log('Profile API Response Status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Profile API Raw Response:', text);
        try {
            const data = JSON.parse(text);
            if (data.status === 'success') {
                const sub = data.subscription;
                resultDiv.innerHTML = '<div class=\"result success\">✅ Profile API working!<br>' +
                    'Plan: ' + sub.plan_code + ' (' + sub.plan_name + ')<br>' +
                    'Status: ' + sub.subscription_status + '<br>' +
                    'Active: ' + (sub.is_active ? 'YES' : 'NO') + '<br>' +
                    'Pro User: ' + (data.stats.is_pro_user ? 'YES' : 'NO') +
                    '</div><pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } else {
                resultDiv.innerHTML = '<div class=\"result error\">❌ Profile API failed: ' + data.message + '</div>';
            }
        } catch (e) {
            resultDiv.innerHTML = '<div class=\"result error\">❌ Profile API returned invalid JSON:<br><pre>' + text + '</pre></div>';
        }
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class=\"result error\">❌ Profile API Error: ' + error.message + '</div>';
    });
}

function testUpgrade(planCode) {
    const resultDiv = document.getElementById('upgradeResult');
    resultDiv.innerHTML = '<div class=\"result\">Testing upgrade to ' + planCode + '...</div>';
    
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
    .then(response => {
        console.log('Upgrade API Response Status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Upgrade API Raw Response:', text);
        try {
            const data = JSON.parse(text);
            if (data.status === 'success') {
                resultDiv.innerHTML = '<div class=\"result success\">✅ Upgrade successful: ' + data.message + '</div><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                setTimeout(() => checkCurrentStatus(), 1000);
            } else {
                resultDiv.innerHTML = '<div class=\"result error\">❌ Upgrade failed: ' + data.message + '</div><pre>' + JSON.stringify(data, null, 2) + '</pre>';
            }
        } catch (e) {
            resultDiv.innerHTML = '<div class=\"result error\">❌ Upgrade API returned invalid JSON:<br><pre>' + text + '</pre></div>';
        }
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class=\"result error\">❌ Upgrade API Error: ' + error.message + '</div>';
    });
}

function checkCurrentStatus() {
    const resultDiv = document.getElementById('statusResult');
    resultDiv.innerHTML = '<div class=\"result\">Checking current status...</div>';
    
    testProfile();
}
</script>";

echo "</body></html>";
?>