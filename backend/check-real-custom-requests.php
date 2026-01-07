<?php
// Check Real Custom Requests - Find Actual Customer Submissions
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Check Real Custom Requests</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} .container{max-width:1200px;margin:0 auto;background:white;padding:20px;border-radius:8px;} .success{color:#10b981;} .error{color:#ef4444;} .info{color:#3b82f6;} .warning{color:#f59e0b;} table{width:100%;border-collapse:collapse;margin:15px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f8f9fa;}</style>";
echo "</head><body><div class='container'>";

echo "<h1>🔍 Check Real Custom Requests</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2 class='info'>Step 1: Check All Tables for Custom Requests</h2>";
    
    // Check all tables that might contain custom requests
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $customTables = array_filter($tables, function($table) {
        return strpos(strtolower($table), 'custom') !== false || 
               strpos(strtolower($table), 'request') !== false ||
               strpos(strtolower($table), 'order') !== false;
    });
    
    echo "<p>Found potential tables:</p>";
    echo "<ul>";
    foreach ($customTables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "<li><strong>$table</strong> - $count records</li>";
    }
    echo "</ul>";
    
    echo "<h2 class='info'>Step 2: Check Current custom_requests Table</h2>";
    
    if (in_array('custom_requests', $tables)) {
        $allRequests = $pdo->query("SELECT * FROM custom_requests ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Total records in custom_requests:</strong> " . count($allRequests) . "</p>";
        
        if (count($allRequests) > 0) {
            echo "<h3>All Records:</h3>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Order ID</th><th>Customer</th><th>Email</th><th>Title</th><th>Status</th><th>Created</th><th>Source</th></tr>";
            
            foreach ($allRequests as $req) {
                $isReal = !preg_match('/CR-\d{8}-\d{3}/', $req['order_id']) || 
                         !in_array($req['customer_email'], [
                             'alice.johnson@email.com',
                             'michael.chen@email.com', 
                             'sarah.williams@email.com',
                             'robert.davis@email.com'
                         ]);
                
                $rowClass = $isReal ? 'style="background:#d1fae5;"' : 'style="background:#fef3c7;"';
                
                echo "<tr $rowClass>";
                echo "<td>{$req['id']}</td>";
                echo "<td>{$req['order_id']}</td>";
                echo "<td>{$req['customer_name']}</td>";
                echo "<td>{$req['customer_email']}</td>";
                echo "<td>{$req['title']}</td>";
                echo "<td>{$req['status']}</td>";
                echo "<td>{$req['created_at']}</td>";
                echo "<td>" . ($req['source'] ?? 'unknown') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<p class='info'><strong>Legend:</strong> Green = Likely Real Customer, Yellow = Sample Data</p>";
        }
    }
    
    echo "<h2 class='info'>Step 3: Check for Backup Tables</h2>";
    
    $backupTables = array_filter($tables, function($table) {
        return strpos(strtolower($table), 'backup') !== false;
    });
    
    if (!empty($backupTables)) {
        foreach ($backupTables as $table) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            echo "<p><strong>$table:</strong> $count records</p>";
            
            if ($count > 0 && $count < 20) {
                $records = $pdo->query("SELECT * FROM `$table` LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
                echo "<table>";
                if (!empty($records)) {
                    echo "<tr>";
                    foreach (array_keys($records[0]) as $col) {
                        echo "<th>$col</th>";
                    }
                    echo "</tr>";
                    
                    foreach ($records as $record) {
                        echo "<tr>";
                        foreach ($record as $value) {
                            echo "<td>" . htmlspecialchars(substr($value, 0, 50)) . "</td>";
                        }
                        echo "</tr>";
                    }
                }
                echo "</table>";
            }
        }
    }
    
    echo "<h2 class='info'>Step 4: Check Other Potential Tables</h2>";
    
    $otherTables = ['orders', 'cart_items', 'submissions', 'user_requests'];
    foreach ($otherTables as $table) {
        if (in_array($table, $tables)) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            echo "<p><strong>$table:</strong> $count records</p>";
            
            if ($count > 0 && $count < 50) {
                try {
                    $sample = $pdo->query("SELECT * FROM `$table` LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
                    if (!empty($sample)) {
                        echo "<p>Sample columns: " . implode(', ', array_keys($sample[0])) . "</p>";
                    }
                } catch (Exception $e) {
                    echo "<p class='warning'>Could not read $table structure</p>";
                }
            }
        }
    }
    
    echo "<h2 class='success'>Step 5: Recommendations</h2>";
    
    echo "<div style='background:#d1fae5;padding:15px;border-radius:5px;margin:15px 0;'>";
    echo "<h3>🎯 Next Steps:</h3>";
    echo "<ol>";
    echo "<li><strong>Identify Real Requests:</strong> Look for records that don't match sample data patterns</li>";
    echo "<li><strong>Check Backup Tables:</strong> Real data might be in backup tables</li>";
    echo "<li><strong>Restore Real Data:</strong> If found in backups, restore to main table</li>";
    echo "<li><strong>Remove Sample Data:</strong> Clean out test/sample records</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
?>