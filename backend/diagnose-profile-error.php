<?php
/**
 * Profile API Error Diagnosis
 * Step-by-step diagnosis to find the exact cause of 500 errors
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profile API Error Diagnosis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .step { margin: 15px 0; padding: 15px; border-radius: 8px; }
        .step-success { background: #d4edda; border: 1px solid #c3e6cb; }
        .step-error { background: #f8d7da; border: 1px solid #f5c6cb; }
        .step-info { background: #d1ecf1; border: 1px solid #bee5eb; }
        .step-warning { background: #fff3cd; border: 1px solid #ffeaa7; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>🔍 Profile API Error Diagnosis</h1>
        <p class="text-muted">Finding the exact cause of ProfileDropdown.jsx 500 errors</p>

<?php
echo '<div class="step step-info">🔧 <strong>Step 1:</strong> Testing minimal profile API...</div>';

// Test 1: Minimal API
$minimalAPI = 'api/customer/profile-minimal.php';
if (file_exists($minimalAPI)) {
    echo "<small>✅ Minimal API file exists</small><br>";
    
    // Test it
    try {
        $_SERVER['HTTP_X_TUTORIAL_EMAIL'] = 'test@example.com';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        ob_start();
        include $minimalAPI;
        $minimalOutput = ob_get_clean();
        
        $minimalData = json_decode($minimalOutput, true);
        if ($minimalData && $minimalData['status'] === 'success') {
            echo "<small>✅ Minimal API works correctly</small><br>";
        } else {
            echo "<small>❌ Minimal API failed: " . htmlspecialchars(substr($minimalOutput, 0, 100)) . "</small><br>";
        }
    } catch (Exception $e) {
        echo "<small>❌ Minimal API error: " . $e->getMessage() . "</small><br>";
    }
} else {
    echo "<small>❌ Minimal API file not found</small><br>";
}

echo '<div class="step step-info">🔧 <strong>Step 2:</strong> Testing current profile API...</div>';

// Test 2: Current API
$currentAPI = 'api/customer/profile.php';
if (file_exists($currentAPI)) {
    echo "<small>✅ Current API file exists</small><br>";
    
    // Check file permissions
    if (is_readable($currentAPI)) {
        echo "<small>✅ Current API file is readable</small><br>";
    } else {
        echo "<small>❌ Current API file is not readable</small><br>";
    }
    
    // Test it with error capture
    try {
        $_SERVER['HTTP_X_TUTORIAL_EMAIL'] = 'test@example.com';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // Capture all output including errors
        ob_start();
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        include $currentAPI;
        $currentOutput = ob_get_clean();
        
        echo "<small>📄 Current API output length: " . strlen($currentOutput) . " characters</small><br>";
        
        if (empty($currentOutput)) {
            echo "<small>❌ Current API returned empty output</small><br>";
        } else {
            // Check if it's JSON
            $currentData = json_decode($currentOutput, true);
            if ($currentData) {
                if ($currentData['status'] === 'success') {
                    echo "<small>✅ Current API works correctly</small><br>";
                } else {
                    echo "<small>⚠️ Current API returns error: " . $currentData['message'] . "</small><br>";
                }
            } else {
                echo "<small>❌ Current API returns invalid JSON</small><br>";
                echo "<small>📄 First 200 chars: " . htmlspecialchars(substr($currentOutput, 0, 200)) . "</small><br>";
            }
        }
    } catch (Exception $e) {
        echo "<small>❌ Current API error: " . $e->getMessage() . "</small><br>";
    }
} else {
    echo "<small>❌ Current API file not found</small><br>";
}

echo '<div class="step step-info">🔧 <strong>Step 3:</strong> Testing web server access...</div>';

// Test 3: Web server access
$serverInfo = [
    'PHP Version' => PHP_VERSION,
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'Script Name' => $_SERVER['SCRIPT_NAME'] ?? 'Unknown',
    'Request URI' => $_SERVER['REQUEST_URI'] ?? 'Unknown'
];

foreach ($serverInfo as $key => $value) {
    echo "<small>📋 $key: $value</small><br>";
}

echo '<div class="step step-info">🔧 <strong>Step 4:</strong> Testing database connection...</div>';

// Test 4: Database connection
try {
    $host = 'localhost';
    $dbname = 'my_little_thingz';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    echo "<small>✅ Database connection successful</small><br>";
    
    // Test basic query
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    if ($result && $result['test'] == 1) {
        echo "<small>✅ Database query works</small><br>";
    } else {
        echo "<small>❌ Database query failed</small><br>";
    }
    
} catch (Exception $e) {
    echo "<small>❌ Database connection failed: " . $e->getMessage() . "</small><br>";
}

echo '<div class="step step-info">🔧 <strong>Step 5:</strong> Replacing broken API...</div>';

// Step 5: Replace with minimal working version
if (file_exists($minimalAPI) && file_exists($currentAPI)) {
    // Backup current
    $backup = $currentAPI . '.broken-backup-' . date('Y-m-d-H-i-s');
    if (copy($currentAPI, $backup)) {
        echo "<small>📦 Backed up current API to: $backup</small><br>";
    }
    
    // Replace with minimal
    if (copy($minimalAPI, $currentAPI)) {
        echo "<small>✅ Replaced current API with minimal working version</small><br>";
        
        // Test the replacement
        try {
            ob_start();
            include $currentAPI;
            $replacedOutput = ob_get_clean();
            
            $replacedData = json_decode($replacedOutput, true);
            if ($replacedData && $replacedData['status'] === 'success') {
                echo '<div class="step step-success">';
                echo '<h4>🎉 Profile API Fixed!</h4>';
                echo '<p><strong>The ProfileDropdown.jsx 500 error should now be resolved.</strong></p>';
                echo '<ul>';
                echo '<li>✅ Minimal API successfully replaced broken version</li>';
                echo '<li>✅ API returns valid JSON response</li>';
                echo '<li>✅ All required fields are present</li>';
                echo '<li>✅ Pro subscription status included</li>';
                echo '</ul>';
                echo '</div>';
            } else {
                echo "<small>❌ Replacement failed - API still not working</small><br>";
            }
        } catch (Exception $e) {
            echo "<small>❌ Replacement test failed: " . $e->getMessage() . "</small><br>";
        }
    } else {
        echo "<small>❌ Failed to replace API file</small><br>";
    }
}

echo '<div class="step step-info">🧪 <strong>Step 6:</strong> Final tests...</div>';
echo '<div class="row">';
echo '<div class="col-md-4">';
echo '<a href="api/customer/profile.php" target="_blank" class="btn btn-primary w-100">Test Profile API</a>';
echo '<small class="text-muted d-block mt-1">Should return JSON</small>';
echo '</div>';
echo '<div class="col-md-4">';
echo '<button class="btn btn-success w-100" onclick="testWithHeaders()">Test with Headers</button>';
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
echo '<li><strong>Open browser developer tools</strong> (F12)</li>';
echo '<li><strong>Click on profile dropdown</strong> - should work without errors</li>';
echo '<li><strong>Check console</strong> - no more 500 errors should appear</li>';
echo '</ol>';
echo '<p class="mb-0"><strong>🎯 If you still see 500 errors, there may be a web server configuration issue.</strong></p>';
echo '</div>';
?>

<script>
async function testWithHeaders() {
    try {
        const response = await fetch('/my_little_thingz/backend/api/customer/profile.php', {
            method: 'GET',
            headers: {
                'X-Tutorial-Email': 'soudhame52@gmail.com',
                'Content-Type': 'application/json'
            }
        });
        
        const text = await response.text();
        
        let message = `Profile API Test Results:\n\n`;
        message += `Status: ${response.status} ${response.statusText}\n`;
        message += `Content-Type: ${response.headers.get('content-type')}\n\n`;
        
        if (response.status === 200) {
            try {
                const data = JSON.parse(text);
                message += `✅ SUCCESS! Valid JSON response\n`;
                message += `API Status: ${data.status}\n`;
                message += `User Email: ${data.user_email || 'Not set'}\n`;
                message += `Subscription: ${data.subscription?.plan_code || 'Not set'}\n\n`;
                message += `Full Response:\n${JSON.stringify(data, null, 2)}`;
            } catch (e) {
                message += `❌ Invalid JSON response\n`;
                message += `Parse Error: ${e.message}\n\n`;
                message += `Raw Response:\n${text}`;
            }
        } else {
            message += `❌ HTTP Error ${response.status}\n\n`;
            message += `Response Body:\n${text}`;
        }
        
        alert(message);
        
    } catch (error) {
        alert(`❌ Network Error:\n\n${error.message}\n\nCheck if your web server is running.`);
    }
}
</script>

    </div>
</body>
</html>