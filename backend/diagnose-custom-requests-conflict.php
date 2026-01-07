<?php
// Diagnose Custom Requests Conflict
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Diagnose Custom Requests Conflict</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} .container{max-width:1200px;margin:0 auto;background:white;padding:20px;border-radius:8px;} .success{color:#10b981;} .error{color:#ef4444;} .info{color:#3b82f6;} .warning{color:#f59e0b;}</style>";
echo "</head><body><div class='container'>";

echo "<h1>🔍 Diagnose Custom Requests Conflict</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2 class='info'>Step 1: Check All Custom Request APIs</h2>";
    
    $adminApiDir = __DIR__ . '/api/admin/';
    $customRequestApis = [];
    
    if (is_dir($adminApiDir)) {
        $files = scandir($adminApiDir);
        foreach ($files as $file) {
            if (strpos($file, 'custom-request') !== false && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $customRequestApis[] = $file;
            }
        }
    }
    
    echo "<p class='warning'>Found " . count($customRequestApis) . " custom request APIs:</p>";
    echo "<ul>";
    foreach ($customRequestApis as $api) {
        echo "<li><strong>$api</strong></li>";
    }
    echo "</ul>";
    
    echo "<h2 class='info'>Step 2: Check Database Tables</h2>";
    
    // Check for custom_requests table
    $tables = $pdo->query("SHOW TABLES LIKE '%custom%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Custom-related tables found:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li><strong>$table</strong></li>";
    }
    echo "</ul>";
    
    // Check custom_requests table structure
    if (in_array('custom_requests', $tables)) {
        echo "<h3>Custom Requests Table Structure:</h3>";
        $columns = $pdo->query("DESCRIBE custom_requests")->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
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
        
        // Check data in table
        $count = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
        echo "<p><strong>Total records:</strong> $count</p>";
        
        if ($count > 0) {
            echo "<h3>Sample Records:</h3>";
            $samples = $pdo->query("SELECT id, order_id, customer_name, customer_email, title, status, created_at FROM custom_requests ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
            echo "<tr><th>ID</th><th>Order ID</th><th>Customer</th><th>Email</th><th>Title</th><th>Status</th><th>Created</th></tr>";
            foreach ($samples as $sample) {
                echo "<tr>";
                echo "<td>{$sample['id']}</td>";
                echo "<td>{$sample['order_id']}</td>";
                echo "<td>{$sample['customer_name']}</td>";
                echo "<td>{$sample['customer_email']}</td>";
                echo "<td>{$sample['title']}</td>";
                echo "<td>{$sample['status']}</td>";
                echo "<td>{$sample['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    echo "<h2 class='info'>Step 3: Check Which API Admin Dashboard Uses</h2>";
    
    // Check the admin dashboard code
    $adminDashboardPath = __DIR__ . '/../frontend/src/pages/AdminDashboard.jsx';
    if (file_exists($adminDashboardPath)) {
        $content = file_get_contents($adminDashboardPath);
        if (preg_match('/custom-requests-([^.]+)\.php/', $content, $matches)) {
            echo "<p class='success'>✓ Admin Dashboard uses: <strong>custom-requests-{$matches[1]}.php</strong></p>";
        } else {
            echo "<p class='error'>❌ Could not determine which API admin dashboard uses</p>";
        }
    }
    
    echo "<h2 class='info'>Step 4: Check Customer Request Submission</h2>";
    
    $customerApiDir = __DIR__ . '/api/customer/';
    $customerApis = [];
    
    if (is_dir($customerApiDir)) {
        $files = scandir($customerApiDir);
        foreach ($files as $file) {
            if ((strpos($file, 'custom') !== false || strpos($file, 'cart') !== false) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $customerApis[] = $file;
            }
        }
    }
    
    echo "<p>Customer-side APIs that might create requests:</p>";
    echo "<ul>";
    foreach ($customerApis as $api) {
        echo "<li><strong>$api</strong></li>";
    }
    echo "</ul>";
    
    echo "<h2 class='warning'>Step 5: Identify the Conflict</h2>";
    
    echo "<div style='background:#fff3cd;padding:15px;border-radius:5px;margin:15px 0;'>";
    echo "<h3>🚨 Potential Issues Found:</h3>";
    echo "<ul>";
    echo "<li><strong>Multiple Admin APIs:</strong> " . count($customRequestApis) . " different APIs might be conflicting</li>";
    echo "<li><strong>Data Inconsistency:</strong> Different APIs might be using different table structures</li>";
    echo "<li><strong>Request Routing:</strong> Customer requests might not be going to the correct admin API</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h2 class='success'>Step 6: Recommended Solution</h2>";
    
    echo "<div style='background:#d1fae5;padding:15px;border-radius:5px;margin:15px 0;'>";
    echo "<h3>🔧 Fix Strategy:</h3>";
    echo "<ol>";
    echo "<li><strong>Consolidate APIs:</strong> Use only one admin API (custom-requests-database-only.php)</li>";
    echo "<li><strong>Standardize Table:</strong> Ensure all systems use the same table structure</li>";
    echo "<li><strong>Update Customer APIs:</strong> Make sure customer requests go to the right place</li>";
    echo "<li><strong>Clean Up:</strong> Remove conflicting/duplicate APIs</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h3>🧪 Test Current API:</h3>";
    echo "<p><a href='api/admin/custom-requests-database-only.php?status=all' target='_blank'>Test Current Admin API</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
?>