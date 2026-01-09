<?php
// Remove Sample Data from Custom Requests - Direct Execution
echo "<h1>🗑️ Removing Sample Data from Custom Requests</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Show what will be deleted
    echo "<h2>📋 Sample Data to be Removed:</h2>";
    
    $sampleQuery = "SELECT id, customer_name, customer_email, title, created_at FROM custom_requests WHERE 
        customer_name IN ('Alice Johnson', 'Michael Chen', 'Sarah Williams', 'Robert Davis')
        OR customer_email LIKE '%@email.com'
        OR customer_email LIKE '%test%'
        OR customer_name LIKE '%Test%'
        OR title LIKE '%Test%'
        OR customer_name = 'Unknown Customer'
        OR customer_name LIKE '%Customer%'
        OR order_id LIKE 'CR-%001'
        OR order_id LIKE 'CR-%002'
        OR order_id LIKE 'CR-%003'
        OR order_id LIKE 'CR-%004'";
    
    $sampleData = $pdo->query($sampleQuery)->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($sampleData)) {
        echo "<p style='color: blue;'>ℹ️ No sample data found to remove.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Customer Name</th><th>Email</th><th>Title</th><th>Created</th></tr>";
        
        foreach ($sampleData as $row) {
            echo "<tr style='background: #ffebee;'>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['customer_name']}</td>";
            echo "<td>{$row['customer_email']}</td>";
            echo "<td>" . substr($row['title'], 0, 40) . "...</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p style='color: red;'><strong>⚠️ " . count($sampleData) . " sample records will be deleted</strong></p>";
    }
    
    // Show what will be kept
    echo "<h2>💾 Real Data to be Preserved:</h2>";
    
    $realQuery = "SELECT id, customer_name, customer_email, title, created_at FROM custom_requests WHERE NOT (
        customer_name IN ('Alice Johnson', 'Michael Chen', 'Sarah Williams', 'Robert Davis')
        OR customer_email LIKE '%@email.com'
        OR customer_email LIKE '%test%'
        OR customer_name LIKE '%Test%'
        OR title LIKE '%Test%'
        OR customer_name = 'Unknown Customer'
        OR customer_name LIKE '%Customer%'
        OR order_id LIKE 'CR-%001'
        OR order_id LIKE 'CR-%002'
        OR order_id LIKE 'CR-%003'
        OR order_id LIKE 'CR-%004'
    )";
    
    $realData = $pdo->query($realQuery)->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($realData)) {
        echo "<p style='color: orange;'>⚠️ No real customer data found. Only sample data exists.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Customer Name</th><th>Email</th><th>Title</th><th>Created</th></tr>";
        
        foreach ($realData as $row) {
            echo "<tr style='background: #e8f5e8;'>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['customer_name']}</td>";
            echo "<td>{$row['customer_email']}</td>";
            echo "<td>" . substr($row['title'], 0, 40) . "...</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p style='color: green;'><strong>✅ " . count($realData) . " real customer records will be preserved</strong></p>";
    }
    
    // Execute deletion
    echo "<h2>🗑️ Executing Deletion:</h2>";
    
    // Delete sample data
    $deleteQuery = "DELETE FROM custom_requests WHERE 
        customer_name IN ('Alice Johnson', 'Michael Chen', 'Sarah Williams', 'Robert Davis')
        OR customer_email LIKE '%@email.com'
        OR customer_email LIKE '%test%'
        OR customer_name LIKE '%Test%'
        OR title LIKE '%Test%'
        OR customer_name = 'Unknown Customer'
        OR customer_name LIKE '%Customer%'
        OR order_id LIKE 'CR-%001'
        OR order_id LIKE 'CR-%002'
        OR order_id LIKE 'CR-%003'
        OR order_id LIKE 'CR-%004'";
    
    $deleteStmt = $pdo->prepare($deleteQuery);
    $deleted = $deleteStmt->execute();
    $deletedCount = $deleteStmt->rowCount();
    
    echo "<p style='color: green; font-size: 18px;'><strong>✅ Successfully deleted $deletedCount sample records</strong></p>";
    
    // Clean orphaned images
    $imageDeleteStmt = $pdo->prepare("DELETE FROM custom_request_images WHERE request_id NOT IN (SELECT id FROM custom_requests)");
    $imageDeleted = $imageDeleteStmt->execute();
    $imageDeletedCount = $imageDeleteStmt->rowCount();
    
    echo "<p style='color: green;'>✅ Cleaned $imageDeletedCount orphaned image records</p>";
    
    // Show final state
    echo "<h2>📊 Final Database State:</h2>";
    
    $finalCount = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
    $finalData = $pdo->query("SELECT id, customer_name, customer_email, title, created_at FROM custom_requests ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p style='color: blue; font-size: 16px;'><strong>Total remaining records: $finalCount</strong></p>";
    
    if ($finalCount > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Customer Name</th><th>Email</th><th>Title</th><th>Created</th></tr>";
        
        foreach ($finalData as $row) {
            echo "<tr style='background: #e8f5e8;'>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['customer_name']}</td>";
            echo "<td>{$row['customer_email']}</td>";
            echo "<td>" . substr($row['title'], 0, 40) . "...</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: blue;'>Database is now completely clean. Ready for real customer requests.</p>";
    }
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #28a745;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>🎉 Sample Data Removal Complete!</h3>";
    echo "<p style='color: #155724;'>✅ Deleted $deletedCount sample records</p>";
    echo "<p style='color: #155724;'>✅ Cleaned $imageDeletedCount orphaned images</p>";
    echo "<p style='color: #155724;'>✅ Preserved $finalCount real customer records</p>";
    echo "<p style='color: #155724;'>✅ Database is now clean and ready for production</p>";
    echo "</div>";
    
    echo "<h3>🔗 Next Steps:</h3>";
    echo "<ol>";
    echo "<li><a href='api/admin/custom-requests-database-only.php' target='_blank' style='color: #007cba;'>Test Admin API</a> - Should show clean data</li>";
    echo "<li><a href='../frontend/admin/custom-requests-dashboard.html' target='_blank' style='color: #007cba;'>Open Admin Dashboard</a> - Should show only real requests</li>";
    echo "<li><a href='../test-direct-database-fix.html' target='_blank' style='color: #007cba;'>Submit Test Request</a> - Test customer flow</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #dc3545;'>";
    echo "<h3 style='color: #721c24; margin-top: 0;'>❌ Error Occurred</h3>";
    echo "<p style='color: #721c24;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p style='color: #721c24;'><strong>Possible causes:</strong></p>";
    echo "<ul style='color: #721c24;'>";
    echo "<li>MySQL server is not running</li>";
    echo "<li>Database 'my_little_thingz' does not exist</li>";
    echo "<li>Database connection settings are incorrect</li>";
    echo "<li>Insufficient database permissions</li>";
    echo "</ul>";
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

table {
    margin: 15px 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

th, td {
    padding: 10px;
    text-align: left;
    border: 1px solid #ddd;
}

th {
    background: #f8f9fa;
    font-weight: 600;
}

a {
    color: #007cba;
    text-decoration: none;
    font-weight: 600;
}

a:hover {
    text-decoration: underline;
}

ol {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
</style>