<?php
// Restore Real Custom Requests - Remove Sample Data, Keep Only Real Submissions
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Restore Real Custom Requests</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} .container{max-width:1200px;margin:0 auto;background:white;padding:20px;border-radius:8px;} .success{color:#10b981;} .error{color:#ef4444;} .info{color:#3b82f6;} .warning{color:#f59e0b;}</style>";
echo "</head><body><div class='container'>";

echo "<h1>🔄 Restore Real Custom Requests</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2 class='info'>Step 1: Backup Current Data</h2>";
    
    // Create backup of current state
    $pdo->exec("DROP TABLE IF EXISTS custom_requests_with_samples");
    $pdo->exec("CREATE TABLE custom_requests_with_samples AS SELECT * FROM custom_requests");
    echo "<p class='success'>✓ Backed up current data to custom_requests_with_samples</p>";
    
    echo "<h2 class='info'>Step 2: Identify and Remove Sample Data</h2>";
    
    // Remove sample data based on patterns
    $sampleEmails = [
        'alice.johnson@email.com',
        'michael.chen@email.com', 
        'sarah.williams@email.com',
        'robert.davis@email.com'
    ];
    
    $sampleOrderPatterns = [
        'CR-' . date('Ymd') . '-001',
        'CR-' . date('Ymd') . '-002',
        'CR-' . date('Ymd') . '-003',
        'CR-' . date('Ymd') . '-004'
    ];
    
    // Count sample records
    $sampleCount = 0;
    foreach ($sampleEmails as $email) {
        $count = $pdo->prepare("SELECT COUNT(*) FROM custom_requests WHERE customer_email = ?");
        $count->execute([$email]);
        $sampleCount += $count->fetchColumn();
    }
    
    echo "<p>Found $sampleCount sample records to remove</p>";
    
    // Remove sample data
    foreach ($sampleEmails as $email) {
        $stmt = $pdo->prepare("DELETE FROM custom_requests WHERE customer_email = ?");
        $stmt->execute([$email]);
        echo "<p class='success'>✓ Removed sample records for: $email</p>";
    }
    
    // Also remove by order ID pattern
    foreach ($sampleOrderPatterns as $orderId) {
        $stmt = $pdo->prepare("DELETE FROM custom_requests WHERE order_id = ?");
        $stmt->execute([$orderId]);
    }
    
    echo "<h2 class='info'>Step 3: Check for Real Data in Backup Tables</h2>";
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $backupTables = array_filter($tables, function($table) {
        return strpos(strtolower($table), 'backup') !== false && 
               strpos(strtolower($table), 'custom') !== false;
    });
    
    foreach ($backupTables as $backupTable) {
        echo "<h3>Checking $backupTable:</h3>";
        
        try {
            $backupRecords = $pdo->query("SELECT * FROM `$backupTable`")->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($backupRecords)) {
                echo "<p>Found " . count($backupRecords) . " records in $backupTable</p>";
                
                // Check if these look like real records (not sample data)
                foreach ($backupRecords as $record) {
                    $isReal = !in_array($record['customer_email'] ?? '', $sampleEmails);
                    
                    if ($isReal) {
                        echo "<p class='success'>✓ Real record found: {$record['customer_name']} - {$record['title']}</p>";
                        
                        // Try to restore this record
                        try {
                            $columns = array_keys($record);
                            $placeholders = str_repeat('?,', count($columns) - 1) . '?';
                            $columnsList = implode(',', $columns);
                            
                            $insertStmt = $pdo->prepare("INSERT IGNORE INTO custom_requests ($columnsList) VALUES ($placeholders)");
                            $insertStmt->execute(array_values($record));
                            
                            echo "<p class='success'>✓ Restored: {$record['customer_name']}</p>";
                        } catch (Exception $e) {
                            echo "<p class='warning'>Could not restore record: " . $e->getMessage() . "</p>";
                        }
                    }
                }
            }
        } catch (Exception $e) {
            echo "<p class='warning'>Could not read $backupTable: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2 class='info'>Step 4: Check Other Tables for Real Requests</h2>";
    
    // Check orders table for custom requests
    if (in_array('orders', $tables)) {
        try {
            $orders = $pdo->query("SELECT * FROM orders WHERE order_type = 'custom' OR product_name LIKE '%custom%' LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($orders)) {
                echo "<p>Found " . count($orders) . " potential custom orders</p>";
                
                foreach ($orders as $order) {
                    echo "<p class='info'>Order: {$order['id']} - Customer: " . ($order['customer_name'] ?? $order['user_name'] ?? 'Unknown') . "</p>";
                    
                    // Try to create custom request from order
                    $customRequest = [
                        'order_id' => 'ORD-' . $order['id'],
                        'customer_id' => $order['user_id'] ?? 0,
                        'customer_name' => $order['customer_name'] ?? $order['user_name'] ?? 'Unknown Customer',
                        'customer_email' => $order['customer_email'] ?? $order['email'] ?? '',
                        'customer_phone' => $order['phone'] ?? '',
                        'title' => $order['product_name'] ?? 'Custom Order',
                        'description' => $order['notes'] ?? $order['description'] ?? '',
                        'status' => 'pending',
                        'source' => 'order'
                    ];
                    
                    try {
                        $stmt = $pdo->prepare("INSERT IGNORE INTO custom_requests (order_id, customer_id, customer_name, customer_email, customer_phone, title, description, status, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute(array_values($customRequest));
                        echo "<p class='success'>✓ Created custom request from order {$order['id']}</p>";
                    } catch (Exception $e) {
                        echo "<p class='warning'>Could not create request from order: " . $e->getMessage() . "</p>";
                    }
                }
            }
        } catch (Exception $e) {
            echo "<p class='warning'>Could not check orders table: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2 class='info'>Step 5: Final Status</h2>";
    
    $finalCount = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
    echo "<p><strong>Total real custom requests now:</strong> $finalCount</p>";
    
    if ($finalCount > 0) {
        $realRequests = $pdo->query("SELECT * FROM custom_requests ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Real Custom Requests:</h3>";
        echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
        echo "<tr><th>ID</th><th>Order ID</th><th>Customer</th><th>Email</th><th>Title</th><th>Status</th><th>Source</th></tr>";
        
        foreach ($realRequests as $req) {
            echo "<tr>";
            echo "<td>{$req['id']}</td>";
            echo "<td>{$req['order_id']}</td>";
            echo "<td>{$req['customer_name']}</td>";
            echo "<td>{$req['customer_email']}</td>";
            echo "<td>{$req['title']}</td>";
            echo "<td>{$req['status']}</td>";
            echo "<td>" . ($req['source'] ?? 'form') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='background:#fef3c7;padding:15px;border-radius:5px;margin:15px 0;'>";
        echo "<h3>⚠️ No Real Custom Requests Found</h3>";
        echo "<p>It appears there are no real customer submissions yet. This could mean:</p>";
        echo "<ul>";
        echo "<li>Customers haven't submitted any custom requests yet</li>";
        echo "<li>Real data might be in a different table or database</li>";
        echo "<li>The custom request form might not be working properly</li>";
        echo "</ul>";
        echo "</div>";
    }
    
    echo "<h2 class='success'>✅ Restoration Complete!</h2>";
    echo "<div style='background:#d1fae5;padding:20px;border-radius:5px;margin:20px 0;'>";
    echo "<h3>🎉 Summary:</h3>";
    echo "<ul>";
    echo "<li>✅ Removed sample/test data</li>";
    echo "<li>✅ Checked backup tables for real data</li>";
    echo "<li>✅ Restored any real customer requests found</li>";
    echo "<li>✅ Admin dashboard will now show only real requests</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🧪 Test Your System:</h3>";
    echo "<p><a href='api/admin/custom-requests-database-only.php?status=all' target='_blank'>View Real Custom Requests</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
?>