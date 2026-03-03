<?php
/**
 * Quick Fix: Add missing canvas_data_file column
 * Run this script to fix the database column error
 */

require_once "backend/config/database.php";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2>Database Column Fix</h2>";
    echo "<p>Checking and fixing custom_request_designs table...</p>";
    
    // Check if table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'custom_request_designs'")->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<p style='color: orange;'>Table 'custom_request_designs' does not exist. Creating it...</p>";
        
        $createTableSQL = "CREATE TABLE custom_request_designs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            request_id INT UNSIGNED NOT NULL,
            template_id INT UNSIGNED,
            canvas_width INT NOT NULL DEFAULT 800,
            canvas_height INT NOT NULL DEFAULT 600,
            canvas_data LONGTEXT,
            canvas_data_file VARCHAR(255),
            design_image_url VARCHAR(500),
            design_pdf_url VARCHAR(500),
            version INT DEFAULT 1,
            status ENUM('draft', 'designing', 'design_completed', 'approved', 'rejected') DEFAULT 'designing',
            admin_notes TEXT,
            customer_feedback TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_request_id (request_id),
            INDEX idx_status (status)
        )";
        
        $pdo->exec($createTableSQL);
        echo "<p style='color: green;'>✓ Table created successfully!</p>";
    } else {
        echo "<p style='color: blue;'>Table 'custom_request_designs' exists. Checking columns...</p>";
        
        // Check and add missing columns
        $columnsToAdd = [
            'canvas_data_file' => "ALTER TABLE custom_request_designs ADD COLUMN canvas_data_file VARCHAR(255) NULL",
            'canvas_width' => "ALTER TABLE custom_request_designs ADD COLUMN canvas_width INT NOT NULL DEFAULT 800",
            'canvas_height' => "ALTER TABLE custom_request_designs ADD COLUMN canvas_height INT NOT NULL DEFAULT 600",
            'template_id' => "ALTER TABLE custom_request_designs ADD COLUMN template_id INT UNSIGNED NULL",
            'version' => "ALTER TABLE custom_request_designs ADD COLUMN version INT DEFAULT 1",
            'design_pdf_url' => "ALTER TABLE custom_request_designs ADD COLUMN design_pdf_url VARCHAR(500) NULL",
            'customer_feedback' => "ALTER TABLE custom_request_designs ADD COLUMN customer_feedback TEXT NULL"
        ];
        
        foreach ($columnsToAdd as $columnName => $alterSQL) {
            $checkColumn = $pdo->query("SHOW COLUMNS FROM custom_request_designs LIKE '$columnName'");
            if ($checkColumn->rowCount() == 0) {
                try {
                    $pdo->exec($alterSQL);
                    echo "<p style='color: green;'>✓ Added column '$columnName'</p>";
                } catch (Exception $e) {
                    echo "<p style='color: red;'>✗ Failed to add column '$columnName': " . $e->getMessage() . "</p>";
                }
            } else {
                echo "<p style='color: gray;'>- Column '$columnName' already exists</p>";
            }
        }
    }
    
    // Show final table structure
    echo "<h3>Final Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    
    $columns = $pdo->query("SHOW COLUMNS FROM custom_request_designs");
    while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3 style='color: green;'>✅ Database fix completed successfully!</h3>";
    echo "<p>You can now try saving your design again.</p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Database fix failed</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>