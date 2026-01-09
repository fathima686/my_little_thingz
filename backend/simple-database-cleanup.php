<?php
// Simple Database Cleanup for Custom Requests
echo "<h2>🧹 Simple Database Cleanup</h2>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Show current data
    echo "<h3>Current Data in Database:</h3>";
    $current = $pdo->query("SELECT id, customer_name, customer_email, title, created_at FROM custom_requests ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($current)) {
        echo "<p>No requests found in database.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Customer</th><th>Email</th><th>Title</th><th>Created</th><th>Type</th></tr>";
        
        foreach ($current as $req) {
            $isSample = (
                in_array($req['customer_name'], ['Alice Johnson', 'Michael Chen', 'Sarah Williams', 'Robert Davis']) ||
                strpos($req['customer_email'], '@email.com') !== false ||
                strpos($req['customer_email'], 'test') !== false ||
                strpos($req['customer_name'], 'Test') !== false
            );
            
            $rowColor = $isSample ? '#ffebee' : '#e8f5e8';
            $type = $isSample ? 'SAMPLE' : 'REAL';
            
            echo "<tr style='background: $rowColor;'>";
            echo "<td>{$req['id']}</td>";
            echo "<td>{$req['customer_name']}</td>";
            echo "<td>{$req['customer_email']}</td>";
            echo "<td>" . substr($req['title'], 0, 30) . "...</td>";
            echo "<td>{$req['created_at']}</td>";
            echo "<td><strong>$type</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><span style='background: #ffebee; padding: 2px 8px;'>Red = Sample Data (will be deleted)</span> | <span style='background: #e8f5e8; padding: 2px 8px;'>Green = Real Data (will be kept)</span></p>";
    }
    
    // Clean sample data
    echo "<h3>Cleaning Sample Data:</h3>";
    
    $deleteQuery = "DELETE FROM custom_requests WHERE 
        customer_name IN ('Alice Johnson', 'Michael Chen', 'Sarah Williams', 'Robert Davis')
        OR customer_email LIKE '%@email.com'
        OR customer_email LIKE '%test%'
        OR customer_name LIKE '%Test%'
        OR title LIKE '%Test%'
        OR customer_name = 'Unknown Customer'";
    
    $deleteStmt = $pdo->prepare($deleteQuery);
    $deleted = $deleteStmt->execute();
    $deletedCount = $deleteStmt->rowCount();
    
    echo "<p style='color: green;'>✅ Deleted $deletedCount sample records</p>";
    
    // Clean orphaned images
    $pdo->exec("DELETE FROM custom_request_images WHERE request_id NOT IN (SELECT id FROM custom_requests)");
    echo "<p style='color: green;'>✅ Cleaned orphaned image records</p>";
    
    // Show final data
    echo "<h3>Final Data After Cleanup:</h3>";
    $final = $pdo->query("SELECT id, customer_name, customer_email, title, created_at FROM custom_requests ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($final)) {
        echo "<p>Database is now clean - no requests remaining.</p>";
        echo "<p style='color: blue;'>ℹ️ You can now submit real customer requests and they will appear in the admin dashboard.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Customer</th><th>Email</th><th>Title</th><th>Created</th></tr>";
        
        foreach ($final as $req) {
            echo "<tr style='background: #e8f5e8;'>";
            echo "<td>{$req['id']}</td>";
            echo "<td>{$req['customer_name']}</td>";
            echo "<td>{$req['customer_email']}</td>";
            echo "<td>" . substr($req['title'], 0, 40) . "...</td>";
            echo "<td>{$req['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p style='color: green;'>✅ " . count($final) . " real customer requests preserved</p>";
    }
    
    echo "<h3>✅ Cleanup Complete!</h3>";
    echo "<p>Next steps:</p>";
    echo "<ol>";
    echo "<li><a href='api/admin/custom-requests-database-only.php' target='_blank'>Test Admin API</a></li>";
    echo "<li><a href='../frontend/admin/custom-requests-dashboard.html' target='_blank'>Open Admin Dashboard</a></li>";
    echo "<li>Submit a test customer request to verify the flow</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Make sure:</p>";
    echo "<ul>";
    echo "<li>MySQL server is running</li>";
    echo "<li>Database 'my_little_thingz' exists</li>";
    echo "<li>Database connection settings are correct in config/database.php</li>";
    echo "</ul>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 15px 0; }
th, td { padding: 8px; text-align: left; }
th { background: #f0f0f0; }
</style>