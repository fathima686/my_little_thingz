<?php
/**
 * Fix Custom Requests Table Structure
 * This script ensures the custom_requests table has all required columns
 */

require_once "backend/config/database.php";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2>Fixing Custom Requests Table Structure</h2>";
    
    // Check if table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'custom_requests'");
    if ($tableCheck->rowCount() == 0) {
        echo "<p style='color: orange;'>Table 'custom_requests' does not exist. Creating it...</p>";
        
        $createTableSQL = "CREATE TABLE custom_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id INT UNSIGNED,
            user_id INT UNSIGNED,
            title VARCHAR(255),
            description TEXT,
            price DECIMAL(10,2) DEFAULT 50.00,
            status ENUM('pending', 'in_progress', 'designing', 'design_completed', 'completed', 'cancelled') DEFAULT 'pending',
            workflow_stage ENUM('submitted', 'in_design', 'in_crafting', 'design_completed', 'packed', 'courier_assigned', 'delivered') DEFAULT 'submitted',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_customer_id (customer_id),
            INDEX idx_user_id (user_id),
            INDEX idx_status (status)
        )";
        
        $pdo->exec($createTableSQL);
        echo "<p style='color: green;'>✅ Table created successfully!</p>";
    } else {
        echo "<p style='color: blue;'>Table 'custom_requests' exists. Checking columns...</p>";
    }
    
    // Get current columns
    $existingColumns = [];
    $columnsResult = $pdo->query("SHOW COLUMNS FROM custom_requests");
    while ($column = $columnsResult->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[$column['Field']] = $column;
    }
    
    // Define required columns
    $requiredColumns = [
        'id' => "INT UNSIGNED AUTO_INCREMENT PRIMARY KEY",
        'customer_id' => "INT UNSIGNED",
        'user_id' => "INT UNSIGNED", 
        'title' => "VARCHAR(255)",
        'description' => "TEXT",
        'price' => "DECIMAL(10,2) DEFAULT 50.00",
        'status' => "ENUM('pending', 'in_progress', 'designing', 'design_completed', 'completed', 'cancelled') DEFAULT 'pending'",
        'workflow_stage' => "ENUM('submitted', 'in_design', 'in_crafting', 'design_completed', 'packed', 'courier_assigned', 'delivered') DEFAULT 'submitted'",
        'created_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        'updated_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];
    
    // Add missing columns
    foreach ($requiredColumns as $columnName => $columnDef) {
        if (!isset($existingColumns[$columnName])) {
            try {
                if ($columnName === 'id') {
                    // Skip ID if it doesn't exist - table structure issue
                    continue;
                }
                
                $alterSQL = "ALTER TABLE custom_requests ADD COLUMN $columnName $columnDef";
                $pdo->exec($alterSQL);
                echo "<p style='color: green;'>✅ Added column '$columnName'</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>✗ Failed to add column '$columnName': " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: gray;'>- Column '$columnName' already exists</p>";
        }
    }
    
    // Show final table structure
    echo "<h3>Final Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    
    $columns = $pdo->query("SHOW COLUMNS FROM custom_requests");
    while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check for existing requests
    $countStmt = $pdo->query("SELECT COUNT(*) as count FROM custom_requests");
    $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<h3>Current Data:</h3>";
    echo "<p>Total custom requests: <strong>$count</strong></p>";
    
    if ($count == 0) {
        echo "<p style='color: orange;'>⚠️ No custom requests found. You may want to create a test request.</p>";
        echo "<p><a href='create-test-custom-request.php' target='_blank'>Create Test Request</a></p>";
    } else {
        echo "<p>Recent requests:</p>";
        $recentStmt = $pdo->query("SELECT id, title, status, created_at FROM custom_requests ORDER BY created_at DESC LIMIT 5");
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Created</th></tr>";
        while ($request = $recentStmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$request['id']}</td>";
            echo "<td>{$request['title']}</td>";
            echo "<td>{$request['status']}</td>";
            echo "<td>{$request['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3 style='color: green;'>✅ Database structure fix completed!</h3>";
    echo "<p>You can now test the custom design → cart integration.</p>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>If you don't have any requests, <a href='create-test-custom-request.php'>create a test request</a></li>";
    echo "<li>Use the <a href='debug-custom-design-cart-flow.html'>debug tool</a> to test the flow</li>";
    echo "<li>Try completing a design and check if it appears in the cart</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Database fix failed</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>