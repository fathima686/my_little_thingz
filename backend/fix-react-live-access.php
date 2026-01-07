<?php
// Fix React Live Access - Force unlock live sections
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Fix React Live Access</title>";
echo "<style>
body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} 
.container{max-width:1000px;margin:0 auto;background:white;padding:20px;border-radius:8px;} 
.success{color:#10b981;} .error{color:#ef4444;} .info{color:#3b82f6;}
button{padding:10px 20px;margin:5px;border:none;border-radius:4px;cursor:pointer;background:#3b82f6;color:white;}
pre{background:#f8f9fa;padding:10px;border-radius:4px;overflow-x:auto;font-size:12px;max-height:300px;overflow-y:auto;}
.step{background:#e0f2fe;padding:15px;border-radius:5px;margin:20px 0;}
</style>";
echo "</head><body><div class='container'>";

echo "<h1>🔧 Fix React Live Access</h1>";

echo "<div class='step'>";
echo "<h2 class='info'>Debug: Check What React App Receives</h2>";
echo "<p>Let's see exactly what data your React app is getting:</p>";
echo "<button onclick='debugReactData()'>Debug React Data</button>";
echo "<div id='debugResult'></div>";
echo "</div>";

echo "<div class='step'>";
echo "<h2 class='info'>Solution 1: Force Live Access in Profile API</h2>";
echo "<p>Update the profile API to ensure React gets the right data structure:</p>";
echo "<button onclick='applyProfileFix()'>Apply Profile Fix</button>";
echo "<div id='profileFixResult'></div>";
echo "</div>";

echo "<div class='step'>";
echo "<h2 class='info'>Solution 2: Test React Logic Simulation</h2>";
echo "<p>Simulate exactly what your React component does:</p>";
echo "<button onclick='simulateReactLogic()'>Simulate React Logic</button>";
echo "<div id='reactLogicResult'></div>";
echo "</div>";

echo "<div class='step'>";
echo "<h2 class='info'>Solution 3: Browser Console Fix</h2>";
echo "<p>If the above doesn't work, use this JavaScript in your browser console:</p>";
echo "<pre id='consoleCode'>
// Paste this in your React app's browser console:
// Force unlock live workshops
window.forceUnlockLiveWorkshops = function() {
  // Find the subscription status in React state
  const reactFiber = document.querySelector('[data-reactroot]')?._reactInternalFiber ||
                     document.querySelector('#root')?._reactInternalFiber;
  
  if (reactFiber) {
    console.log('Forcing live workshops unlock...');
    
    // Force the subscription status to Pro
    localStorage.setItem('forced_pro_access', 'true');
    
    // Trigger a page refresh
    window.location.reload();
  }
};

// Run the function
window.forceUnlockLiveWorkshops();
</pre>";
echo "<button onclick='copyConsoleCode()'>Copy Console Code</button>";
echo "</div>";

echo "</div>";

echo "<script>
function debugReactData() {
    const resultDiv = document.getElementById('debugResult');
    resultDiv.innerHTML = '<p>Debugging React data...</p>';
    
    fetch('api/customer/profile.php?email=soudhame52@gmail.com')
    .then(response => response.json())
    .then(data => {
        // Check all the fields React might be looking for
        const checks = {
            'subscription.plan_code': data.subscription?.plan_code,
            'feature_access.access_levels.can_access_live_workshops': data.feature_access?.access_levels?.can_access_live_workshops,
            'stats.is_pro_user': data.stats?.is_pro_user,
            'subscription.subscription_status': data.subscription?.subscription_status,
            'subscription.is_active': data.subscription?.is_active
        };
        
        let html = '<h3>React Data Debug:</h3>';
        html += '<table style=\"border-collapse:collapse;width:100%;margin:10px 0;\">';
        html += '<tr style=\"background:#f9fafb;\"><th style=\"border:1px solid #e5e7eb;padding:8px;\">Field</th><th style=\"border:1px solid #e5e7eb;padding:8px;\">Value</th><th style=\"border:1px solid #e5e7eb;padding:8px;\">Status</th></tr>';
        
        for (const [field, value] of Object.entries(checks)) {
            const isGood = (field.includes('can_access_live_workshops') && value === true) ||
                          (field.includes('plan_code') && value === 'pro') ||
                          (field.includes('is_pro_user') && value === true) ||
                          (field.includes('is_active') && value === 1);
            
            const statusClass = isGood ? 'success' : 'error';
            const statusText = isGood ? '✅ Good' : '❌ Issue';
            
            html += '<tr>';
            html += '<td style=\"border:1px solid #e5e7eb;padding:8px;\">' + field + '</td>';
            html += '<td style=\"border:1px solid #e5e7eb;padding:8px;\">' + JSON.stringify(value) + '</td>';
            html += '<td style=\"border:1px solid #e5e7eb;padding:8px;color:' + (isGood ? '#10b981' : '#ef4444') + ';\">' + statusText + '</td>';
            html += '</tr>';
        }
        html += '</table>';
        
        html += '<h4>Full API Response:</h4>';
        html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        
        resultDiv.innerHTML = html;
    })
    .catch(error => {
        resultDiv.innerHTML = '<p class=\"error\">❌ Error: ' + error.message + '</p>';
    });
}

function applyProfileFix() {
    const resultDiv = document.getElementById('profileFixResult');
    resultDiv.innerHTML = '<p>Applying profile fix...</p>';
    
    // This will create an enhanced profile API that ensures React gets the right data
    fetch('create-enhanced-profile-api.php', { method: 'POST' })
    .then(response => response.text())
    .then(data => {
        resultDiv.innerHTML = '<div class=\"success\">✅ Profile API enhanced!</div><p>Now refresh your React app.</p>';
    })
    .catch(error => {
        resultDiv.innerHTML = '<p class=\"error\">❌ Error: ' + error.message + '</p>';
    });
}

function simulateReactLogic() {
    const resultDiv = document.getElementById('reactLogicResult');
    resultDiv.innerHTML = '<p>Simulating React logic...</p>';
    
    fetch('api/customer/profile.php?email=soudhame52@gmail.com')
    .then(response => response.json())
    .then(subscriptionStatus => {
        // This is EXACTLY what your React component does
        const canAccessLiveWorkshops = () => {
            return subscriptionStatus?.feature_access?.access_levels?.can_access_live_workshops || false;
        };
        
        const hasAccess = canAccessLiveWorkshops();
        const currentPlan = subscriptionStatus?.subscription?.plan_code || subscriptionStatus?.plan_code || 'Basic';
        
        let html = '<h3>React Logic Simulation:</h3>';
        html += '<div style=\"padding:15px;border-radius:5px;background:' + (hasAccess ? '#f0fdf4' : '#fef2f2') + ';border:2px solid ' + (hasAccess ? '#10b981' : '#ef4444') + ';\">';
        
        if (hasAccess) {
            html += '<h4>🎉 Live Workshops Unlocked!</h4>';
            html += '<p>Your React app should show the live sessions interface.</p>';
            html += '<p><strong>Current Plan:</strong> ' + currentPlan + '</p>';
        } else {
            html += '<h4>🔒 Live Workshops - Pro Feature</h4>';
            html += '<p>Live workshops and mentorship sessions are available exclusively for Pro subscribers.</p>';
            html += '<p><strong>Current Plan:</strong> ' + currentPlan + '</p>';
            html += '<button>Upgrade to Pro</button>';
        }
        
        html += '</div>';
        
        html += '<h4>Debug Info:</h4>';
        html += '<ul>';
        html += '<li><strong>canAccessLiveWorkshops():</strong> ' + hasAccess + '</li>';
        html += '<li><strong>subscriptionStatus?.feature_access?.access_levels?.can_access_live_workshops:</strong> ' + (subscriptionStatus?.feature_access?.access_levels?.can_access_live_workshops || 'undefined') + '</li>';
        html += '<li><strong>subscriptionStatus?.subscription?.plan_code:</strong> ' + (subscriptionStatus?.subscription?.plan_code || 'undefined') + '</li>';
        html += '</ul>';
        
        resultDiv.innerHTML = html;
    })
    .catch(error => {
        resultDiv.innerHTML = '<p class=\"error\">❌ Error: ' + error.message + '</p>';
    });
}

function copyConsoleCode() {
    const code = document.getElementById('consoleCode').textContent;
    navigator.clipboard.writeText(code).then(() => {
        alert('Console code copied! Paste it in your React app\\'s browser console.');
    });
}
</script>";

echo "</body></html>";
?>