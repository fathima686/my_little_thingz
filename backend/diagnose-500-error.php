<?php
// Comprehensive 500 error diagnosis for custom requests
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>500 Error Diagnosis - Custom Requests API</h1>";

// Test 1: Basic PHP and server setup
echo "<h2>1. Server Environment Check</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Current Script:</strong> " . __FILE__ . "</p>";

// Test 2: Database connection
echo "<h2>2. Database Connection Test</h2>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Test database info
    $stmt = $pdo->query("SELECT DATABASE() as db_name, VERSION() as version");
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Database:</strong> {$info['db_name']}</p>";
    echo "<p><strong>MySQL Version:</strong> {$info['version']}</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Check:</strong></p>";
    echo "<ul>";
    echo "<li>Is XAMPP/MySQL running?</li>";
    echo "<li>Are database credentials correct in config/database.php?</li>";
    echo "<li>Does the database 'my_little_thingz' exist?</li>";
    echo "</ul>";
    exit;
}

// Test 3: Check if custom_requests table exists
echo "<h2>3. Database Table Check</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'custom_requests'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "<p style='color: green;'>✅ custom_requests table exists</p>";
        
        // Get table structure
        $stmt = $pdo->query("DESCRIBE custom_requests");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p><strong>Table Structure:</strong></p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count records
        $count = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
        echo "<p><strong>Records:</strong> $count</p>";
        
    } else {
        echo "<p style='color: orange;'>⚠️ custom_requests table does not exist</p>";
        echo "<p>Attempting to create table...</p>";
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS custom_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id VARCHAR(50) UNIQUE NOT NULL,
            customer_id INT UNSIGNED NOT NULL,
            customer_name VARCHAR(255) NOT NULL,
            customer_email VARCHAR(255) NOT NULL,
            title VARCHAR(255) NOT NULL,
            occasion VARCHAR(100),
            description TEXT,
            requirements TEXT,
            deadline DATE,
            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
            status ENUM('submitted', 'drafted_by_admin', 'changes_requested', 'approved_by_customer', 'locked_for_production') DEFAULT 'submitted',
            design_url VARCHAR(500),
            admin_notes TEXT,
            customer_feedback TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        echo "<p style='color: green;'>✅ Table created successfully</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Table error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 4: Test API files exist and are readable
echo "<h2>4. API Files Check</h2>";
$apiFiles = [
    'api/admin/custom-requests-fixed.php' => 'Fixed API (Recommended)',
    'api/admin/custom-requests-simple.php' => 'Simple API',
    'api/admin/custom-requests-minimal.php' => 'Minimal API'
];

foreach ($apiFiles as $file => $description) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        echo "<p style='color: green;'>✅ $description: $file exists</p>";
        echo "<p style='margin-left: 20px;'>Size: " . filesize($fullPath) . " bytes</p>";
    } else {
        echo "<p style='color: red;'>❌ $description: $file missing</p>";
    }
}

// Test 5: Test actual API calls
echo "<h2>5. API Response Test</h2>";

function testAPICall($apiPath, $description) {
    $url = "http://localhost/my_little_thingz/backend/$apiPath";
    
    echo "<h3>Testing: $description</h3>";
    echo "<p><strong>URL:</strong> $url</p>";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'Content-Type: application/json',
                'X-Admin-Email: admin@test.com',
                'X-Admin-User-ID: 1'
            ],
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "<p style='color: red;'>❌ Failed to connect to API</p>";
        return false;
    }
    
    // Check HTTP response code
    $httpCode = 200;
    if (isset($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                $httpCode = (int)$matches[1];
                break;
            }
        }
    }
    
    echo "<p><strong>HTTP Status:</strong> $httpCode</p>";
    
    if ($httpCode === 200) {
        echo "<p style='color: green;'>✅ HTTP 200 OK</p>";
        
        // Try to parse JSON
        $data = json_decode($response, true);
        if ($data) {
            echo "<p style='color: green;'>✅ Valid JSON response</p>";
            echo "<p><strong>Status:</strong> " . ($data['status'] ?? 'unknown') . "</p>";
            echo "<p><strong>Message:</strong> " . ($data['message'] ?? 'none') . "</p>";
            
            if (isset($data['requests'])) {
                echo "<p><strong>Requests Count:</strong> " . count($data['requests']) . "</p>";
            }
            
            return true;
        } else {
            echo "<p style='color: red;'>❌ Invalid JSON response</p>";
            echo "<p><strong>Raw Response:</strong></p>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
            return false;
        }
    } else {
        echo "<p style='color: red;'>❌ HTTP Error $httpCode</p>";
        echo "<p><strong>Response:</strong></p>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
        return false;
    }
}

// Test all APIs
$results = [];
$results['fixed'] = testAPICall('api/admin/custom-requests-fixed.php', 'Fixed API');
$results['simple'] = testAPICall('api/admin/custom-requests-simple.php', 'Simple API');
$results['minimal'] = testAPICall('api/admin/custom-requests-minimal.php', 'Minimal API');

// Test 6: Summary and recommendations
echo "<h2>6. Summary and Recommendations</h2>";

$workingAPIs = array_filter($results);
$workingCount = count($workingAPIs);

if ($workingCount === 3) {
    echo "<p style='color: green; font-size: 18px;'>🎉 All APIs are working perfectly!</p>";
    echo "<p><strong>Recommendation:</strong> Use the Fixed API for production.</p>";
} elseif ($workingCount > 0) {
    echo "<p style='color: orange; font-size: 18px;'>⚠️ $workingCount out of 3 APIs are working</p>";
    echo "<p><strong>Working APIs:</strong></p>";
    echo "<ul>";
    foreach ($results as $api => $working) {
        if ($working) {
            echo "<li style='color: green;'>✅ " . ucfirst($api) . " API</li>";
        }
    }
    echo "</ul>";
    
    if ($results['fixed']) {
        echo "<p><strong>Recommendation:</strong> Use the Fixed API - it's working and has the best features.</p>";
    } elseif ($results['minimal']) {
        echo "<p><strong>Recommendation:</strong> Use the Minimal API temporarily - it works but has static data.</p>";
    }
} else {
    echo "<p style='color: red; font-size: 18px;'>❌ No APIs are working</p>";
    echo "<p><strong>Possible Issues:</strong></p>";
    echo "<ul>";
    echo "<li>Server configuration problems</li>";
    echo "<li>Database connection issues</li>";
    echo "<li>File permissions</li>";
    echo "<li>PHP syntax errors in API files</li>";
    echo "</ul>";
}

echo "<h3>Next Steps:</h3>";
if ($workingCount > 0) {
    echo "<ol>";
    echo "<li>Test the admin dashboard to confirm custom requests are loading</li>";
    echo "<li>Check browser console for any remaining errors</li>";
    echo "<li>If issues persist, use the test files to debug further</li>";
    echo "</ol>";
} else {
    echo "<ol>";
    echo "<li>Check XAMPP is running and MySQL service is started</li>";
    echo "<li>Verify database connection settings in config/database.php</li>";
    echo "<li>Check file permissions on API files</li>";
    echo "<li>Review server error logs for detailed error messages</li>";
    echo "</ol>";
}

echo "<p><strong>Test Files Available:</strong></p>";
echo "<ul>";
echo "<li><a href='test-admin-dashboard-fix.html'>Comprehensive API Test</a></li>";
echo "<li><a href='test-minimal-api.html'>Individual API Tests</a></li>";
echo "<li><a href='test-custom-requests-debug.php'>Server-side Debug</a></li>";
echo "</ul>";
?>