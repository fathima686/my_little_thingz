<?php
// Direct API test without browser fetch
echo "<h1>🧪 Direct API Test</h1>";

try {
    // Test database connection first
    require_once 'backend/config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Test if tables exist
    $tables = $pdo->query("SHOW TABLES LIKE 'custom_requests'")->fetchAll();
    if (count($tables) > 0) {
        echo "<p style='color: green;'>✅ custom_requests table exists</p>";
    } else {
        echo "<p style='color: red;'>❌ custom_requests table missing</p>";
    }
    
    $tables = $pdo->query("SHOW TABLES LIKE 'custom_request_images'")->fetchAll();
    if (count($tables) > 0) {
        echo "<p style='color: green;'>✅ custom_request_images table exists</p>";
    } else {
        echo "<p style='color: red;'>❌ custom_request_images table missing</p>";
    }
    
    // Test API directly
    echo "<h2>📊 Testing API Response</h2>";
    
    // Simulate the API call
    $_SERVER["REQUEST_METHOD"] = "GET";
    $_GET["status"] = "all";
    
    // Capture output
    ob_start();
    include 'backend/api/admin/custom-requests-database-only.php';
    $apiOutput = ob_get_clean();
    
    echo "<h3>🔍 API Output:</h3>";
    echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
    echo htmlspecialchars($apiOutput);
    echo "</pre>";
    
    // Try to decode JSON
    $data = json_decode($apiOutput, true);
    if ($data) {
        echo "<h3>✅ JSON Decoded Successfully</h3>";
        echo "<p><strong>Status:</strong> " . ($data['status'] ?? 'unknown') . "</p>";
        echo "<p><strong>Total Requests:</strong> " . (count($data['requests'] ?? [])) . "</p>";
        
        if (!empty($data['requests'])) {
            $firstRequest = $data['requests'][0];
            echo "<h4>🔍 First Request Images:</h4>";
            echo "<pre style='background: #e8f4fd; padding: 10px; border-radius: 5px;'>";
            echo htmlspecialchars(json_encode($firstRequest['images'] ?? [], JSON_PRETTY_PRINT));
            echo "</pre>";
        }
    } else {
        echo "<h3>❌ JSON Decode Failed</h3>";
        echo "<p>JSON Error: " . json_last_error_msg() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #dc3545;'>";
    echo "<h3 style='color: #721c24; margin-top: 0;'>❌ Error Occurred</h3>";
    echo "<p style='color: #721c24;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p style='color: #721c24;'><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p style='color: #721c24;'><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 20px;
    background: #f8f9fa;
}

h1, h2, h3 {
    color: #333;
}

pre {
    max-height: 400px;
    overflow-y: auto;
}
</style>