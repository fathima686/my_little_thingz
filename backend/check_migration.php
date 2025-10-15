<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Checking database migration status...\n\n";
    
    // Check if shiprocket columns exist in orders table
    $stmt = $db->query("SHOW COLUMNS FROM orders LIKE 'shiprocket_order_id'");
    $hasShiprocketColumns = $stmt->rowCount() > 0;
    
    if ($hasShiprocketColumns) {
        echo "✓ Shiprocket columns exist in orders table\n";
    } else {
        echo "✗ Shiprocket columns NOT found in orders table\n";
        echo "  → Run: mysql -u root my_little_thingz < backend/database/migrations_shiprocket.sql\n\n";
    }
    
    // Check if courier_serviceability_cache table exists
    $stmt = $db->query("SHOW TABLES LIKE 'courier_serviceability_cache'");
    $hasCacheTable = $stmt->rowCount() > 0;
    
    if ($hasCacheTable) {
        echo "✓ courier_serviceability_cache table exists\n";
    } else {
        echo "✗ courier_serviceability_cache table NOT found\n";
    }
    
    // Check if shipment_tracking_history table exists
    $stmt = $db->query("SHOW TABLES LIKE 'shipment_tracking_history'");
    $hasTrackingTable = $stmt->rowCount() > 0;
    
    if ($hasTrackingTable) {
        echo "✓ shipment_tracking_history table exists\n";
    } else {
        echo "✗ shipment_tracking_history table NOT found\n";
    }
    
    echo "\n";
    
    if ($hasShiprocketColumns && $hasCacheTable && $hasTrackingTable) {
        echo "=================================\n";
        echo "✓ Database migration is complete!\n";
        echo "=================================\n";
    } else {
        echo "=================================\n";
        echo "⚠ Database migration needed!\n";
        echo "=================================\n";
        echo "\nTo run the migration:\n";
        echo "1. Open phpMyAdmin or MySQL command line\n";
        echo "2. Select 'my_little_thingz' database\n";
        echo "3. Import: backend/database/migrations_shiprocket.sql\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}