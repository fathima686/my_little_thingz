<?php
/**
 * Immediate Profile API Fix
 * Replaces the broken profile API with a working version RIGHT NOW
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Profile API - Immediate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .step { margin: 15px 0; padding: 15px; border-radius: 8px; }
        .step-success { background: #d4edda; border: 1px solid #c3e6cb; }
        .step-error { background: #f8d7da; border: 1px solid #f5c6cb; }
        .step-info { background: #d1ecf1; border: 1px solid #bee5eb; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>🚨 Immediate Profile API Fix</h1>
        <p class="text-muted">Fixing the 500 error in ProfileDropdown.jsx RIGHT NOW</p>

<?php
try {
    echo '<div class="step step-info">🔧 <strong>Step 1:</strong> Backing up broken profile API...</div>';
    
    $brokenAPI = 'api/customer/profile.php';
    $workingAPI = 'api/customer/profile-bulletproof.php';
    
    // Backup the broken file
    if (file_exists($brokenAPI)) {
        $backup = $brokenAPI . '.broken-500-error.' . date('Y-m-d-H-i-s');
        if (copy($brokenAPI, $backup)) {
            echo "<small>📦 Backed up broken API to: $backup</small><br>";
        } else {
            echo "<small>❌ Failed to backup broken API</small><br>";
        }
    } else {
        echo "<small>ℹ️ Original API file doesn't exist</small><br>";
    }
    
    echo '<div class="step step-info">✅ <strong>Step 2:</strong> Installing bulletproof profile API...</div>';
    
    // Replace with working version
    if (file_exists($workingAPI)) {
        if (copy($workingAPI, $brokenAPI)) {
            echo "<small>✅ Replaced profile API with bulletproof version</small><br>";
        } else {
            echo "<small>❌ Failed to replace profile API</small><br>";
            throw new Exception('Could not replace profile API file');
        }
    } else {
        echo "<small>❌ Bulletproof API file not found</small><br>";
        throw new Exception('Bulletproof API file missing');
    }
    
    echo '<div class="step step-info">🧪 <strong>Step 3:</strong> Testing fixed profile API...</div>';
    
    // Test the API directly
    $testEmail = 'soudhame52@gmail.com';
    
    // Simulate the exact call that ProfileDropdown.jsx makes
    $_SERVER['HTTP_X_TUTORIAL_EMAIL'] = $testEmail;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    ob_start();
    include 'api/customer/profile.php';
    $apiOutput = ob_get_clean();
    
    // Check if it's valid JSON
    $apiData = json_decode($apiOutput, true);
    
    if ($apiData && $apiData['status'] === 'success') {
        echo "<small>✅ Profile API working correctly</small><br>";
        echo "<small>📊 User: {$apiData['user_email']}</small><br>";
        echo "<small>🎯 Subscription: {$apiData['subscription']['plan_code']} ({$apiData['subscription']['plan_name']})</small><br>";
        
        echo '<div class="step step-success">';
        echo '<h4>🎉 Profile API Fixed Successfully!</h4>';
        echo '<p><strong>The ProfileDropdown.jsx should now work without 500 errors.</strong></p>';
        echo '<ul>';
        echo '<li>✅ API returns valid JSON response</li>';
        echo '<li>✅ User profile data loaded</li>';
        echo '<li>✅ Subscription status detected</li>';
        echo '<li>✅ No more 500 Internal Server Errors</li>';
        echo '</ul>';
        echo '</div>';
        
    } else {
        echo "<small>❌ Profile API still has issues</small><br>";
        echo "<small>📄 Raw output: " . htmlspecialchars(substr($apiOutput, 0, 200)) . "...</small><br>";
        
        echo '<div class="step step-error">';
        echo '<h4>❌ API Still Not Working</h4>';
        echo '<p>The API replacement didn\'t fix the issue. This might be a database connection problem.</p>';
        echo '</div>';
    }
    
    echo '<div class="step step-info">🔗 <strong>Step 4:</strong> Test links...</div>';
    echo '<div class="row">';
    echo '<div class="col-md-4">';
    echo '<a href="api/customer/profile.php" target="_blank" class="btn btn-primary w-100">Test Profile API</a>';
    echo '<small class="text-muted d-block mt-1">Add X-Tutorial-Email header</small>';
    echo '</div>';
    echo '<div class="col-md-4">';
    echo '<button class="btn btn-success w-100" onclick="testProfileAPI()">Test with JavaScript</button>';
    echo '<small class="text-muted d-block mt-1">Simulates React call</small>';
    echo '</div>';
    echo '<div class="col-md-4">';
    echo '<a href="http://localhost:5173" target="_blank" class="btn btn-info w-100">Test React App</a>';
    echo '<small class="text-muted d-block mt-1">Check ProfileDropdown</small>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="alert alert-success mt-4">';
    echo '<h5>📋 Next Steps:</h5>';
    echo '<ol>';
    echo '<li><strong>Hard refresh your React app</strong> (Ctrl+F5)</li>';
    echo '<li><strong>Click on your profile dropdown</strong> - should load without errors</li>';
    echo '<li><strong>Check browser console</strong> - no more 500 errors</li>';
    echo '</ol>';
    echo '<p class="mb-0"><strong>🎯 Your ProfileDropdown.jsx should now work perfectly!</strong></p>';
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="step step-error">';
    echo '<h4>❌ Fix Failed</h4>';
    echo '<p><strong>Error:</strong> ' . $e->getMessage() . '</p>';
    echo '<p><strong>Manual Fix:</strong></p>';
    echo '<ol>';
    echo '<li>Copy the content from <code>profile-bulletproof.php</code></li>';
    echo '<li>Paste it into <code>api/customer/profile.php</code></li>';
    echo '<li>Save the file and test again</li>';
    echo '</ol>';
    echo '</div>';
}
?>

<script>
async function testProfileAPI() {
    try {
        const response = await fetch('/my_little_thingz/backend/api/customer/profile.php', {
            method: 'GET',
            headers: {
                'X-Tutorial-Email': 'soudhame52@gmail.com',
                'Content-Type': 'application/json'
            }
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', [...response.headers.entries()]);
        
        const text = await response.text();
        console.log('Raw response:', text);
        
        let result = `Profile API Test Results:\n\n`;
        result += `Status: ${response.status}\n`;
        result += `Status Text: ${response.statusText}\n\n`;
        
        if (response.status === 200) {
            try {
                const data = JSON.parse(text);
                result += `✅ SUCCESS! Valid JSON response\n`;
                result += `API Status: ${data.status}\n`;
                if (data.subscription) {
                    result += `Subscription: ${data.subscription.plan_code}\n`;
                }
                result += `\nFull Response:\n${JSON.stringify(data, null, 2)}`;
            } catch (e) {
                result += `❌ Invalid JSON response\n`;
                result += `Raw response: ${text.substring(0, 500)}`;
            }
        } else {
            result += `❌ HTTP Error ${response.status}\n`;
            result += `Response: ${text.substring(0, 500)}`;
        }
        
        alert(result);
        
    } catch (error) {
        alert(`❌ Network Error:\n\n${error.message}\n\nThis usually means the server is not running.`);
    }
}
</script>

    </div>
</body>
</html>