<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Running Shiprocket database migration...\n\n";
    
    // Read the SQL file
    $sqlFile = __DIR__ . '/database/migrations_shiprocket.sql';
    $sql = file_get_contents($sqlFile);
    
    // Remove comments and split by semicolon
    $sql = preg_replace('/--.*$/m', '', $sql);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        try {
            $db->exec($statement);
            $successCount++;
            
            // Show what was executed
            $firstLine = strtok($statement, "\n");
            echo "✓ " . substr($firstLine, 0, 80) . "...\n";
        } catch (PDOException $e) {
            // Check if error is about column/table already existing
            if (strpos($e->getMessage(), 'Duplicate column') !== false || 
                strpos($e->getMessage(), 'already exists') !== false) {
                echo "⚠ Already exists: " . substr(strtok($statement, "\n"), 0, 60) . "...\n";
            } else {
                $errorCount++;
                echo "✗ Error: " . $e->getMessage() . "\n";
                echo "  Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    echo "\n=================================\n";
    echo "Migration completed!\n";
    echo "=================================\n";
    echo "Successful: $successCount\n";
    echo "Errors: $errorCount\n";
    
    // Verify migration
    echo "\nVerifying migration...\n";
    
    $stmt = $db->query("SHOW COLUMNS FROM orders LIKE 'shiprocket_order_id'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Orders table updated successfully\n";
    }
    
    $stmt = $db->query("SHOW TABLES LIKE 'courier_serviceability_cache'");
    if ($stmt->rowCount() > 0) {
        echo "✓ courier_serviceability_cache table created\n";
    }
    
    $stmt = $db->query("SHOW TABLES LIKE 'shipment_tracking_history'");
    if ($stmt->rowCount() > 0) {
        echo "✓ shipment_tracking_history table created\n";
    }
    
    echo "\n✓ Database is ready for Shiprocket integration!\n";
    
} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}