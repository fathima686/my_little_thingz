<?php
// Simple server status check
echo "<h1>🔍 Server Status Check</h1>";

echo "<div style='background: #e8f4fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>📊 Server Information</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";
echo "<p><strong>Current Script:</strong> " . __FILE__ . "</p>";
echo "<p><strong>Current Directory:</strong> " . __DIR__ . "</p>";
echo "</div>";

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>📁 File Structure Check</h2>";

$files_to_check = [
    'backend/config/database.php',
    'backend/api/admin/custom-requests-database-only.php',
    'frontend/admin/custom-requests-dashboard.html',
    'frontend/admin/js/custom-requests-dashboard.js'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ <strong>$file</strong> - EXISTS</p>";
    } else {
        echo "<p style='color: red;'>❌ <strong>$file</strong> - MISSING</p>";
    }
}
echo "</div>";

// Test database connection
echo "<div style='background: #e8f4fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>🗄️ Database Connection Test</h2>";

try {
    if (file_exists('backend/config/database.php')) {
        require_once 'backend/config/database.php';
        $database = new Database();
        $pdo = $database->getConnection();
        
        echo "<p style='color: green;'>✅ Database connection successful</p>";
        
        // Check tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p><strong>Available Tables:</strong></p>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
        
        // Check custom_requests table
        if (in_array('custom_requests', $tables)) {
            $count = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
            echo "<p style='color: green;'>✅ custom_requests table has $count records</p>";
        }
        
        // Check custom_request_images table
        if (in_array('custom_request_images', $tables)) {
            $count = $pdo->query("SELECT COUNT(*) FROM custom_request_images")->fetchColumn();
            echo "<p style='color: green;'>✅ custom_request_images table has $count records</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Database config file not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test API endpoint
echo "<div style='background: #e8f4fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h2>🔗 API Endpoint Test</h2>";

if (file_exists('backend/api/admin/custom-requests-database-only.php')) {
    echo "<p style='color: green;'>✅ API file exists</p>";
    echo "<p><strong>Direct API Test:</strong> <a href='test-api-direct.php' target='_blank' style='color: #007bff; font-weight: bold;'>Click here</a></p>";
    echo "<p><strong>API URL:</strong> <a href='backend/api/admin/custom-requests-database-only.php' target='_blank' style='color: #007bff; font-weight: bold;'>backend/api/admin/custom-requests-database-only.php</a></p>";
} else {
    echo "<p style='color: red;'>❌ API file not found</p>";
}
echo "</div>";

// Recommendations
echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #28a745;'>";
echo "<h2>💡 Recommendations</h2>";
echo "<ol>";
echo "<li><strong>If using XAMPP/WAMP:</strong> Make sure Apache and MySQL are running</li>";
echo "<li><strong>If files are missing:</strong> Check if you're in the correct directory</li>";
echo "<li><strong>If database fails:</strong> Update credentials in backend/config/database.php</li>";
echo "<li><strong>For CORS issues:</strong> Access via http://localhost/ instead of file://</li>";
echo "<li><strong>Test the API directly:</strong> <a href='test-api-direct.php' style='color: #155724; font-weight: bold;'>test-api-direct.php</a></li>";
echo "</ol>";
echo "</div>";
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 20px;
    background: #f8f9fa;
}

h1, h2 {
    color: #333;
}

ul, ol {
    background: white;
    padding: 15px 30px;
    border-radius: 5px;
    margin: 10px 0;
}

a {
    color: #007bff;
    text-decoration: none;
    font-weight: 600;
}

a:hover {
    text-decoration: underline;
}
</style>