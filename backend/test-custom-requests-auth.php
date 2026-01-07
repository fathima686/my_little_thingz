<?php
// Test custom requests API authentication
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Custom Requests API Authentication</h2>";

// Test 1: Without headers (should fail)
echo "<h3>Test 1: No Authentication Headers</h3>";
$url = "http://localhost/my_little_thingz/backend/api/admin/custom-requests.php?status=pending";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10
    ]
]);

$response = @file_get_contents($url, false, $context);
if ($response !== false) {
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
} else {
    echo "<p style='color: red;'>Failed to connect to API</p>";
}

// Test 2: With admin email header (should work)
echo "<h3>Test 2: With Admin Email Header</h3>";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'X-Admin-Email: admin@example.com',
            'X-Admin-User-Id: 1',
            'Content-Type: application/json'
        ],
        'timeout' => 10
    ]
]);

$response = @file_get_contents($url, false, $context);
if ($response !== false) {
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    // Try to parse as JSON
    $data = json_decode($response, true);
    if ($data) {
        echo "<p><strong>Parsed JSON:</strong></p>";
        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
    }
} else {
    echo "<p style='color: red;'>Failed to connect to API</p>";
}

// Test 3: Check if custom_requests table exists
echo "<h3>Test 3: Database Table Check</h3>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'custom_requests'");
    if ($tableCheck->rowCount() > 0) {
        echo "<p style='color: green;'>✓ custom_requests table exists</p>";
        
        // Check table structure
        $columns = $pdo->query("DESCRIBE custom_requests")->fetchAll(PDO::FETCH_ASSOC);
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
        
        // Check if there are any records
        $count = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
        echo "<p>Records in table: <strong>$count</strong></p>";
        
    } else {
        echo "<p style='color: orange;'>⚠ custom_requests table does not exist</p>";
        echo "<p>The table will be created automatically when the API is first accessed with proper authentication.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h3>Summary</h3>";
echo "<p>If Test 2 shows a successful response with 'status': 'success', then the authentication fix is working.</p>";
echo "<p>Make sure your React app is sending both X-Admin-Email and X-Admin-User-Id headers.</p>";
?>