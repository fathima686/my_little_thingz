<?php
/**
 * Database Migration: Add canvas_data_file column
 * This fixes the "Unknown column 'canvas_data_file'" error
 */

require_once "../config/database.php";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Adding canvas_data_file column to custom_request_designs table...\n";
    
    // Check if column already exists
    $checkColumn = $pdo->query("SHOW COLUMNS FROM custom_request_designs LIKE 'canvas_data_file'");
    if ($checkColumn->rowCount() > 0) {
        echo "✓ Column 'canvas_data_file' already exists.\n";
    } else {
        // Add the column
        $pdo->exec("ALTER TABLE custom_request_designs ADD COLUMN canvas_data_file VARCHAR(255) NULL AFTER canvas_data");
        echo "✓ Added 'canvas_data_file' column successfully.\n";
    }
    
    // Also ensure other required columns exist
    $columns = [
        'canvas_width' => "ALTER TABLE custom_request_designs ADD COLUMN canvas_width INT NOT NULL DEFAULT 800 AFTER template_id",
        'canvas_height' => "ALTER TABLE custom_request_designs ADD COLUMN canvas_height INT NOT NULL DEFAULT 600 AFTER canvas_width"
    ];
    
    foreach ($columns as $columnName => $alterSQL) {
        $checkColumn = $pdo->query("SHOW COLUMNS FROM custom_request_designs LIKE '$columnName'");
        if ($checkColumn->rowCount() > 0) {
            echo "✓ Column '$columnName' already exists.\n";
        } else {
            try {
                $pdo->exec($alterSQL);
                echo "✓ Added '$columnName' column successfully.\n";
            } catch (Exception $e) {
                echo "Note: Could not add '$columnName' column: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Show current table structure
    echo "\nCurrent table structure:\n";
    $columns = $pdo->query("SHOW COLUMNS FROM custom_request_designs");
    while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$column['Field']} ({$column['Type']}) {$column['Null']} {$column['Default']}\n";
    }
    
    echo "\n✅ Database migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>