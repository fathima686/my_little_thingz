<?php
// Fix Table Mismatch Issue - Custom Requests
echo "<h1>🔧 Fixing Table Mismatch Issue</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Check which tables exist
    echo "<h2>📋 Checking Database Tables:</h2>";
    
    $tables = $pdo->query("SHOW TABLES LIKE 'custom_request%'")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<ul>";
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "<li><strong>$table</strong> - $count records</li>";
    }
    echo "</ul>";
    
    // Check custom_requests table
    echo "<h3>🔍 Data in custom_requests table (what admin sees):</h3>";
    $mainTableData = $pdo->query("SELECT id, customer_name, customer_email, title, created_at FROM custom_requests ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($mainTableData)) {
        echo "<p style='color: red;'>❌ No data in custom_requests table - This is why admin dashboard is empty!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Customer</th><th>Email</th><th>Title</th><th>Created</th></tr>";
        foreach ($mainTableData as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['customer_name']}</td>";
            echo "<td>{$row['customer_email']}</td>";
            echo "<td>" . substr($row['title'], 0, 40) . "...</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check custom_request_backup table
    echo "<h3>🔍 Data in custom_request_backup table (where new requests go):</h3>";
    
    try {
        $backupTableData = $pdo->query("SELECT id, customer_name, customer_email, title, created_at FROM custom_request_backup ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($backupTableData)) {
            echo "<p style='color: orange;'>⚠️ No data in custom_request_backup table</p>";
        } else {
            echo "<p style='color: blue;'>📊 Found " . count($backupTableData) . " records in backup table</p>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
            echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Customer</th><th>Email</th><th>Title</th><th>Created</th></tr>";
            foreach ($backupTableData as $row) {
                echo "<tr style='background: #fff3cd;'>";
                echo "<td>{$row['id']}</td>";
                echo "<td>{$row['customer_name']}</td>";
                echo "<td>{$row['customer_email']}</td>";
                echo "<td>" . substr($row['title'], 0, 40) . "...</td>";
                echo "<td>{$row['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ custom_request_backup table doesn't exist</p>";
        $backupTableData = [];
    }
    
    // Solution options
    echo "<h2>🛠️ Solution Options:</h2>";
    
    echo "<div style='background: #e8f4fd; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #007cba;'>";
    echo "<h3>Option 1: Move Data from Backup to Main Table (Recommended)</h3>";
    echo "<p>Move all data from custom_request_backup to custom_requests so admin can see it.</p>";
    
    if (!empty($backupTableData)) {
        echo "<form method='post' style='margin: 15px 0;'>";
        echo "<input type='hidden' name='action' value='move_backup_to_main'>";
        echo "<button type='submit' style='background: #28a745; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>✅ Move Backup Data to Main Table</button>";
        echo "</form>";
    } else {
        echo "<p style='color: orange;'>⚠️ No backup data to move</p>";
    }
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #ffc107;'>";
    echo "<h3>Option 2: Update Customer API to Use Main Table</h3>";
    echo "<p>Change the customer upload API to save directly to custom_requests instead of custom_request_backup.</p>";
    echo "<form method='post' style='margin: 15px 0;'>";
    echo "<input type='hidden' name='action' value='fix_customer_api'>";
    echo "<button type='submit' style='background: #ffc107; color: #333; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>🔧 Fix Customer API</button>";
    echo "</form>";
    echo "</div>";
    
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #dc3545;'>";
    echo "<h3>Option 3: Update Admin API to Read from Backup Table</h3>";
    echo "<p>Change the admin API to read from custom_request_backup instead of custom_requests.</p>";
    echo "<form method='post' style='margin: 15px 0;'>";
    echo "<input type='hidden' name='action' value='fix_admin_api'>";
    echo "<button type='submit' style='background: #dc3545; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>⚠️ Update Admin API</button>";
    echo "</form>";
    echo "</div>";
    
    // Handle form submissions
    if ($_POST['action'] ?? '') {
        echo "<h2>🔄 Executing Solution:</h2>";
        
        switch ($_POST['action']) {
            case 'move_backup_to_main':
                moveBackupToMain($pdo);
                break;
            case 'fix_customer_api':
                fixCustomerAPI();
                break;
            case 'fix_admin_api':
                fixAdminAPI();
                break;
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

function moveBackupToMain($pdo) {
    try {
        // Get all data from backup table
        $backupData = $pdo->query("SELECT * FROM custom_request_backup")->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($backupData)) {
            echo "<p style='color: orange;'>⚠️ No data to move from backup table</p>";
            return;
        }
        
        echo "<p style='color: blue;'>📊 Moving " . count($backupData) . " records from backup to main table...</p>";
        
        // Get column names from main table
        $columns = $pdo->query("DESCRIBE custom_requests")->fetchAll(PDO::FETCH_COLUMN);
        
        $moved = 0;
        foreach ($backupData as $row) {
            // Prepare insert statement for main table
            $insertColumns = [];
            $insertValues = [];
            $insertParams = [];
            
            foreach ($row as $column => $value) {
                if (in_array($column, $columns) && $column !== 'id') { // Skip ID to avoid conflicts
                    $insertColumns[] = $column;
                    $insertValues[] = '?';
                    $insertParams[] = $value;
                }
            }
            
            if (!empty($insertColumns)) {
                $insertSQL = "INSERT INTO custom_requests (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertValues) . ")";
                $stmt = $pdo->prepare($insertSQL);
                
                if ($stmt->execute($insertParams)) {
                    $moved++;
                }
            }
        }
        
        echo "<p style='color: green;'>✅ Successfully moved $moved records to main table</p>";
        
        // Also move images if they exist
        try {
            $imagesMoved = $pdo->exec("
                UPDATE custom_request_images 
                SET request_id = (
                    SELECT cr.id FROM custom_requests cr 
                    WHERE cr.customer_email = (
                        SELECT crb.customer_email FROM custom_request_backup crb 
                        WHERE crb.id = custom_request_images.request_id
                    ) 
                    ORDER BY cr.created_at DESC LIMIT 1
                )
                WHERE request_id IN (SELECT id FROM custom_request_backup)
            ");
            
            echo "<p style='color: green;'>✅ Updated $imagesMoved image references</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ Could not update image references: " . $e->getMessage() . "</p>";
        }
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<h4 style='color: #155724;'>🎉 Data Move Complete!</h4>";
        echo "<p style='color: #155724;'>✅ $moved records moved to main table</p>";
        echo "<p style='color: #155724;'>✅ Admin dashboard should now show all requests</p>";
        echo "<p style='color: #155724;'>✅ <a href='api/admin/custom-requests-database-only.php' target='_blank' style='color: #155724;'>Test Admin API</a></p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error moving data: " . $e->getMessage() . "</p>";
    }
}

function fixCustomerAPI() {
    echo "<p style='color: blue;'>🔧 Updating customer API to use main table...</p>";
    
    $apiFile = __DIR__ . '/api/customer/custom-request-upload.php';
    
    if (file_exists($apiFile)) {
        $content = file_get_contents($apiFile);
        
        // Replace custom_request_backup with custom_requests
        $newContent = str_replace('custom_request_backup', 'custom_requests', $content);
        
        if (file_put_contents($apiFile, $newContent)) {
            echo "<p style='color: green;'>✅ Customer API updated successfully</p>";
            echo "<p style='color: green;'>✅ New requests will now go to main table</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to update customer API file</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Customer API file not found</p>";
    }
}

function fixAdminAPI() {
    echo "<p style='color: blue;'>🔧 Updating admin API to read from backup table...</p>";
    
    $apiFile = __DIR__ . '/api/admin/custom-requests-database-only.php';
    
    if (file_exists($apiFile)) {
        $content = file_get_contents($apiFile);
        
        // Replace custom_requests with custom_request_backup
        $newContent = str_replace('FROM custom_requests', 'FROM custom_request_backup', $content);
        $newContent = str_replace('custom_requests SET', 'custom_request_backup SET', $newContent);
        
        if (file_put_contents($apiFile, $newContent)) {
            echo "<p style='color: green;'>✅ Admin API updated successfully</p>";
            echo "<p style='color: green;'>✅ Admin will now read from backup table</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to update admin API file</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Admin API file not found</p>";
    }
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