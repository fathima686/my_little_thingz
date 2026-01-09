<?php
// Check what tables actually exist and where the data is
echo "<h1>🔍 Checking Actual Database Tables</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Step 1: List all tables in the database
    echo "<h2>📋 Step 1: All Tables in Database</h2>";
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 10px; margin: 15px 0;'>";
    echo "<h4>Found " . count($tables) . " tables:</h4>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li><strong>$table</strong></li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // Step 2: Check custom request related tables
    echo "<h2>🔍 Step 2: Custom Request Tables Analysis</h2>";
    
    $customRequestTables = array_filter($tables, function($table) {
        return strpos($table, 'custom') !== false || strpos($table, 'request') !== false;
    });
    
    if (empty($customRequestTables)) {
        echo "<p style='color: orange;'>⚠️ No custom request tables found!</p>";
    } else {
        foreach ($customRequestTables as $table) {
            echo "<h3>📊 Table: <code>$table</code></h3>";
            
            try {
                // Get table structure
                $columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
                echo "<p><strong>Columns:</strong> " . implode(', ', array_column($columns, 'Field')) . "</p>";
                
                // Get row count
                $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                echo "<p><strong>Row count:</strong> $count</p>";
                
                if ($count > 0) {
                    // Show sample data
                    $sampleData = $pdo->query("SELECT * FROM $table ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
                    echo "<tr style='background: #f0f0f0;'>";
                    foreach (array_keys($sampleData[0]) as $column) {
                        echo "<th>$column</th>";
                    }
                    echo "</tr>";
                    
                    foreach ($sampleData as $row) {
                        echo "<tr>";
                        foreach ($row as $value) {
                            $displayValue = is_string($value) ? substr($value, 0, 50) : $value;
                            echo "<td>$displayValue</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Error accessing table: " . $e->getMessage() . "</p>";
            }
            
            echo "<hr>";
        }
    }
    
    // Step 3: Check if main custom_requests table exists and has data
    echo "<h2>🎯 Step 3: Main Table Analysis</h2>";
    
    if (in_array('custom_requests', $tables)) {
        $mainCount = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
        echo "<p style='color: blue;'>📊 custom_requests table exists with $mainCount records</p>";
        
        if ($mainCount > 0) {
            echo "<p style='color: green;'>✅ Data found in main table! The issue might be elsewhere.</p>";
            
            // Show recent data
            $recentData = $pdo->query("SELECT id, customer_name, customer_email, title, created_at FROM custom_requests ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h4>Recent Records:</h4>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
            echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Customer</th><th>Email</th><th>Title</th><th>Created</th></tr>";
            
            foreach ($recentData as $row) {
                echo "<tr>";
                echo "<td>{$row['id']}</td>";
                echo "<td>{$row['customer_name']}</td>";
                echo "<td>{$row['customer_email']}</td>";
                echo "<td>" . substr($row['title'], 0, 40) . "...</td>";
                echo "<td>{$row['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ Main table exists but is empty</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ custom_requests table doesn't exist!</p>";
    }
    
    // Step 4: Test the admin API
    echo "<h2>🧪 Step 4: Test Admin API</h2>";
    
    echo "<p><strong>Testing the admin API endpoint...</strong></p>";
    
    $apiUrl = 'http://localhost/my_little_thingz/backend/api/admin/custom-requests-database-only.php';
    
    echo "<div style='background: #e8f4fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h4>🔗 Test Links:</h4>";
    echo "<ul>";
    echo "<li><a href='api/admin/custom-requests-database-only.php' target='_blank' style='color: #007cba;'>📊 Test Admin API Direct</a></li>";
    echo "<li><a href='../frontend/admin/custom-requests-dashboard.html' target='_blank' style='color: #007cba;'>👨‍💼 Open Admin Dashboard</a></li>";
    echo "<li><a href='../test-real-customer-request-flow.html' target='_blank' style='color: #007cba;'>🧪 Test Customer Submission</a></li>";
    echo "</ul>";
    echo "</div>";
    
    // Step 5: Recommendations
    echo "<h2>💡 Step 5: Recommendations</h2>";
    
    if (in_array('custom_requests', $tables)) {
        $mainCount = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
        
        if ($mainCount > 0) {
            echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #28a745;'>";
            echo "<h4 style='color: #155724;'>✅ Good News!</h4>";
            echo "<p style='color: #155724;'>The main table exists and has data. The issue might be:</p>";
            echo "<ul style='color: #155724;'>";
            echo "<li>Admin dashboard JavaScript not calling the correct API</li>";
            echo "<li>CORS or network issues preventing API calls</li>";
            echo "<li>Admin dashboard not loading properly</li>";
            echo "</ul>";
            echo "<p style='color: #155724;'><strong>Next step:</strong> Check the admin dashboard network calls</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #ffc107;'>";
            echo "<h4 style='color: #856404;'>⚠️ Empty Main Table</h4>";
            echo "<p style='color: #856404;'>The main table exists but is empty. Need to:</p>";
            echo "<ul style='color: #856404;'>";
            echo "<li>Check if customer API is working</li>";
            echo "<li>Test customer request submission</li>";
            echo "<li>Add sample data if needed</li>";
            echo "</ul>";
            echo "</div>";
        }
    } else {
        echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #dc3545;'>";
        echo "<h4 style='color: #721c24;'>❌ Missing Main Table</h4>";
        echo "<p style='color: #721c24;'>The custom_requests table doesn't exist. Need to create it first.</p>";
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

code {
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
}
</style>