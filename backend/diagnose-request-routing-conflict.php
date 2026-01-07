<?php
// Diagnose Request Routing Conflict - Find Where Customer Requests Are Going
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Diagnose Request Routing Conflict</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} .container{max-width:1200px;margin:0 auto;background:white;padding:20px;border-radius:8px;} .success{color:#10b981;} .error{color:#ef4444;} .info{color:#3b82f6;} .warning{color:#f59e0b;} table{width:100%;border-collapse:collapse;margin:15px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f8f9fa;} .highlight{background:#fff3cd;}</style>";
echo "</head><body><div class='container'>";

echo "<h1>🔍 Diagnose Request Routing Conflict</h1>";
echo "<p>Finding where customer requests are being stored vs where admin dashboard is looking...</p>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2 class='info'>Step 1: Check All Tables for Request Data</h2>";
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $requestTables = [];
    
    foreach ($tables as $table) {
        if (strpos(strtolower($table), 'custom') !== false || 
            strpos(strtolower($table), 'request') !== false ||
            strpos(strtolower($table), 'order') !== false ||
            strpos(strtolower($table), 'cart') !== false ||
            strpos(strtolower($table), 'submission') !== false) {
            
            try {
                $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                if ($count > 0) {
                    $requestTables[$table] = $count;
                }
            } catch (Exception $e) {
                // Skip tables we can't read
            }
        }
    }
    
    echo "<p>Found tables with potential request data:</p>";
    echo "<table>";
    echo "<tr><th>Table Name</th><th>Record Count</th><th>Recent Activity</th></tr>";
    
    foreach ($requestTables as $table => $count) {
        try {
            // Check for recent activity
            $recentQuery = "SELECT MAX(created_at) as latest FROM `$table` WHERE created_at IS NOT NULL";
            $latest = $pdo->query($recentQuery)->fetchColumn();
            
            if (!$latest) {
                // Try other date columns
                $columns = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($columns as $col) {
                    if (strpos(strtolower($col['Field']), 'date') !== false || 
                        strpos(strtolower($col['Field']), 'time') !== false) {
                        try {
                            $latest = $pdo->query("SELECT MAX(`{$col['Field']}`) FROM `$table`")->fetchColumn();
                            break;
                        } catch (Exception $e) {
                            // Continue trying
                        }
                    }
                }
            }
            
            $recentActivity = $latest ? date('Y-m-d H:i:s', strtotime($latest)) : 'Unknown';
            
            echo "<tr>";
            echo "<td><strong>$table</strong></td>";
            echo "<td>$count</td>";
            echo "<td>$recentActivity</td>";
            echo "</tr>";
        } catch (Exception $e) {
            echo "<tr>";
            echo "<td><strong>$table</strong></td>";
            echo "<td>$count</td>";
            echo "<td>Error reading dates</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    echo "<h2 class='info'>Step 2: Examine Each Table Structure</h2>";
    
    foreach ($requestTables as $table => $count) {
        echo "<h3>Table: $table ($count records)</h3>";
        
        try {
            $structure = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table>";
            echo "<tr><th>Column</th><th>Type</th><th>Sample Data</th></tr>";
            
            // Get sample data
            $sampleData = $pdo->query("SELECT * FROM `$table` ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            
            foreach ($structure as $col) {
                $columnName = $col['Field'];
                $sampleValue = $sampleData[$columnName] ?? 'NULL';
                
                // Highlight important columns
                $rowClass = '';
                if (strpos(strtolower($columnName), 'custom') !== false ||
                    strpos(strtolower($columnName), 'request') !== false ||
                    strpos(strtolower($columnName), 'name') !== false ||
                    strpos(strtolower($columnName), 'email') !== false) {
                    $rowClass = 'class="highlight"';
                }
                
                echo "<tr $rowClass>";
                echo "<td><strong>$columnName</strong></td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>" . htmlspecialchars(substr($sampleValue, 0, 50)) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Show recent records if they look like custom requests
            if ($count < 20) {
                echo "<h4>Recent Records:</h4>";
                $recent = $pdo->query("SELECT * FROM `$table` ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($recent)) {
                    echo "<table>";
                    echo "<tr>";
                    foreach (array_keys($recent[0]) as $header) {
                        echo "<th>$header</th>";
                    }
                    echo "</tr>";
                    
                    foreach ($recent as $record) {
                        echo "<tr>";
                        foreach ($record as $value) {
                            echo "<td>" . htmlspecialchars(substr($value, 0, 30)) . "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>Could not examine $table: " . $e->getMessage() . "</p>";
        }
        
        echo "<hr>";
    }
    
    echo "<h2 class='info'>Step 3: Check Customer-Side APIs</h2>";
    
    $customerApiDir = __DIR__ . '/api/customer/';
    $customerApis = [];
    
    if (is_dir($customerApiDir)) {
        $files = scandir($customerApiDir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php' && 
                (strpos(strtolower($file), 'custom') !== false ||
                 strpos(strtolower($file), 'request') !== false ||
                 strpos(strtolower($file), 'cart') !== false ||
                 strpos(strtolower($file), 'order') !== false)) {
                $customerApis[] = $file;
            }
        }
    }
    
    echo "<p>Found customer-side APIs that might handle requests:</p>";
    echo "<ul>";
    foreach ($customerApis as $api) {
        echo "<li><strong>$api</strong></li>";
        
        // Try to read the API to see what table it uses
        $apiPath = $customerApiDir . $api;
        if (file_exists($apiPath)) {
            $content = file_get_contents($apiPath);
            
            // Look for table names in the code
            if (preg_match_all('/INSERT\s+INTO\s+`?(\w+)`?/i', $content, $matches)) {
                echo "<ul>";
                foreach ($matches[1] as $tableName) {
                    echo "<li class='info'>→ Inserts into table: <strong>$tableName</strong></li>";
                }
                echo "</ul>";
            }
            
            if (preg_match_all('/FROM\s+`?(\w+)`?/i', $content, $matches)) {
                echo "<ul>";
                foreach ($matches[1] as $tableName) {
                    echo "<li class='warning'>→ Reads from table: <strong>$tableName</strong></li>";
                }
                echo "</ul>";
            }
        }
    }
    echo "</ul>";
    
    echo "<h2 class='info'>Step 4: Check Admin Dashboard API</h2>";
    
    $adminApiPath = __DIR__ . '/api/admin/custom-requests-database-only.php';
    if (file_exists($adminApiPath)) {
        $adminContent = file_get_contents($adminApiPath);
        
        echo "<p><strong>Admin API reads from:</strong></p>";
        if (preg_match_all('/FROM\s+`?(\w+)`?/i', $adminContent, $matches)) {
            echo "<ul>";
            foreach ($matches[1] as $tableName) {
                echo "<li class='success'>→ <strong>$tableName</strong></li>";
            }
            echo "</ul>";
        }
    }
    
    echo "<h2 class='warning'>Step 5: Identify the Conflict</h2>";
    
    echo "<div style='background:#fff3cd;padding:15px;border-radius:5px;margin:15px 0;'>";
    echo "<h3>🚨 Potential Issues:</h3>";
    echo "<ul>";
    echo "<li><strong>Table Mismatch:</strong> Customer requests might be going to a different table than admin reads from</li>";
    echo "<li><strong>API Routing:</strong> Customer form might be calling a different API endpoint</li>";
    echo "<li><strong>Database Selection:</strong> Different APIs might be using different databases</li>";
    echo "<li><strong>Status Filtering:</strong> Admin might be filtering out the requests by status</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h2 class='success'>Step 6: Recommended Fix</h2>";
    
    echo "<div style='background:#d1fae5;padding:15px;border-radius:5px;margin:15px 0;'>";
    echo "<h3>🔧 Next Steps:</h3>";
    echo "<ol>";
    echo "<li><strong>Identify Source Table:</strong> Find which table actually contains the customer requests</li>";
    echo "<li><strong>Update Admin API:</strong> Make admin dashboard read from the correct table</li>";
    echo "<li><strong>Standardize APIs:</strong> Ensure all APIs use the same table structure</li>";
    echo "<li><strong>Test End-to-End:</strong> Verify customer submission → admin display flow</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
?>