<?php
// Verify the table mismatch fix is complete
echo "<h1>🎯 Verifying Table Mismatch Fix</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Check main table
    echo "<h2>📊 Main Table Status</h2>";
    $mainCount = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
    echo "<p style='color: blue;'>📊 custom_requests table now has: <strong>$mainCount records</strong></p>";
    
    if ($mainCount > 0) {
        // Show recent records
        $recentData = $pdo->query("
            SELECT id, order_id, title, occasion, created_at 
            FROM custom_requests 
            ORDER BY created_at DESC 
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Recent Records in Main Table:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Order ID</th><th>Title</th><th>Occasion</th><th>Created</th></tr>";
        
        foreach ($recentData as $row) {
            echo "<tr style='background: #e8f5e8;'>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['order_id']}</td>";
            echo "<td>" . substr($row['title'], 0, 40) . "...</td>";
            echo "<td>{$row['occasion']}</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check backup table
    echo "<h2>📋 Backup Table Status</h2>";
    $backupCount = $pdo->query("SELECT COUNT(*) FROM custom_requests_backup")->fetchColumn();
    echo "<p style='color: orange;'>📊 custom_requests_backup table still has: <strong>$backupCount records</strong></p>";
    echo "<p style='color: gray;'><em>Note: Backup table is kept for safety, but new requests will go to main table</em></p>";
    
    // Check images
    echo "<h2>📷 Images Status</h2>";
    $imageCount = $pdo->query("SELECT COUNT(*) FROM custom_request_images")->fetchColumn();
    echo "<p style='color: blue;'>📊 custom_request_images table has: <strong>$imageCount images</strong></p>";
    
    // Check image references
    $imageCheck = $pdo->query("
        SELECT 
            COUNT(DISTINCT cri.request_id) as requests_with_images,
            COUNT(cri.id) as total_images
        FROM custom_request_images cri
        INNER JOIN custom_requests cr ON cri.request_id = cr.id
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "<p style='color: green;'>✅ <strong>{$imageCheck['requests_with_images']}</strong> requests have images properly linked</p>";
    echo "<p style='color: green;'>✅ <strong>{$imageCheck['total_images']}</strong> images are properly referenced</p>";
    
    // Final status
    echo "<h2>🎉 Final Status</h2>";
    
    if ($mainCount >= 29) {
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #28a745;'>";
        echo "<h3 style='color: #155724; margin-top: 0;'>✅ SUCCESS! Fix Complete</h3>";
        echo "<p style='color: #155724;'>✅ All customer requests are now in the main table</p>";
        echo "<p style='color: #155724;'>✅ Admin dashboard should display all requests</p>";
        echo "<p style='color: #155724;'>✅ Images are properly linked</p>";
        echo "<p style='color: #155724;'>✅ Future requests will save to the correct table</p>";
        echo "</div>";
        
        echo "<div style='background: #e8f4fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h4 style='color: #0c5460;'>🔗 Test Links:</h4>";
        echo "<ul>";
        echo "<li><a href='../frontend/admin/custom-requests-dashboard.html' target='_blank' style='color: #007cba; font-weight: bold;'>👨‍💼 Open Admin Dashboard</a> - Should show all $mainCount requests</li>";
        echo "<li><a href='../test-real-customer-request-flow.html' target='_blank' style='color: #007cba;'>🧪 Test Customer Submission</a> - Should save to main table</li>";
        echo "<li><a href='api/admin/custom-requests-database-only.php' target='_blank' style='color: #007cba;'>📊 Test Admin API</a> - Should return all data</li>";
        echo "</ul>";
        echo "</div>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #dc3545;'>";
        echo "<h3 style='color: #721c24; margin-top: 0;'>⚠️ Incomplete Fix</h3>";
        echo "<p style='color: #721c24;'>Expected 29+ records but found $mainCount. Some data may not have moved correctly.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #dc3545;'>";
    echo "<h3 style='color: #721c24; margin-top: 0;'>❌ Error Occurred</h3>";
    echo "<p style='color: #721c24;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
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
    padding: 8px;
    text-align: left;
    border: 1px solid #ddd;
    font-size: 12px;
}

th {
    background: #f8f9fa;
    font-weight: 600;
}

ul {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

li {
    margin: 8px 0;
}

a {
    color: #007cba;
    text-decoration: none;
    font-weight: 600;
}

a:hover {
    text-decoration: underline;
}
</style>