<?php
/**
 * Database Migration: Add Tracking Status Columns
 * 
 * Adds columns needed for automatic status updates
 */

require_once __DIR__ . '/../config/database.php';

echo "=== Adding Tracking Status Columns ===\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check and add shipment_status column
    echo "1. Checking shipment_status column...\n";
    $check = $db->query("SHOW COLUMNS FROM orders LIKE 'shipment_status'")->fetch();
    if (!$check) {
        echo "   Adding shipment_status column...\n";
        $db->exec("ALTER TABLE orders ADD COLUMN shipment_status VARCHAR(100) NULL AFTER awb_code");
        echo "   ✅ shipment_status column added\n";
    } else {
        echo "   ✅ shipment_status column already exists\n";
    }
    
    // Check and add current_status column
    echo "\n2. Checking current_status column...\n";
    $check = $db->query("SHOW COLUMNS FROM orders LIKE 'current_status'")->fetch();
    if (!$check) {
        echo "   Adding current_status column...\n";
        $db->exec("ALTER TABLE orders ADD COLUMN current_status VARCHAR(255) NULL AFTER shipment_status");
        echo "   ✅ current_status column added\n";
    } else {
        echo "   ✅ current_status column already exists\n";
    }
    
    // Check and add tracking_updated_at column
    echo "\n3. Checking tracking_updated_at column...\n";
    $check = $db->query("SHOW COLUMNS FROM orders LIKE 'tracking_updated_at'")->fetch();
    if (!$check) {
        echo "   Adding tracking_updated_at column...\n";
        $db->exec("ALTER TABLE orders ADD COLUMN tracking_updated_at TIMESTAMP NULL AFTER current_status");
        echo "   ✅ tracking_updated_at column added\n";
    } else {
        echo "   ✅ tracking_updated_at column already exists\n";
    }
    
    // Check and add shipped_at column
    echo "\n4. Checking shipped_at column...\n";
    $check = $db->query("SHOW COLUMNS FROM orders LIKE 'shipped_at'")->fetch();
    if (!$check) {
        echo "   Adding shipped_at column...\n";
        $db->exec("ALTER TABLE orders ADD COLUMN shipped_at TIMESTAMP NULL AFTER tracking_updated_at");
        echo "   ✅ shipped_at column added\n";
    } else {
        echo "   ✅ shipped_at column already exists\n";
    }
    
    // Check and add delivered_at column
    echo "\n5. Checking delivered_at column...\n";
    $check = $db->query("SHOW COLUMNS FROM orders LIKE 'delivered_at'")->fetch();
    if (!$check) {
        echo "   Adding delivered_at column...\n";
        $db->exec("ALTER TABLE orders ADD COLUMN delivered_at TIMESTAMP NULL AFTER shipped_at");
        echo "   ✅ delivered_at column added\n";
    } else {
        echo "   ✅ delivered_at column already exists\n";
    }
    
    // Check shipment_tracking_history table
    echo "\n6. Checking shipment_tracking_history table...\n";
    $check = $db->query("SHOW TABLES LIKE 'shipment_tracking_history'")->fetch();
    if (!$check) {
        echo "   Creating shipment_tracking_history table...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS `shipment_tracking_history` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `order_id` INT(11) NOT NULL,
            `awb_code` VARCHAR(100) NOT NULL,
            `status` VARCHAR(100) NOT NULL,
            `status_code` VARCHAR(50) NULL,
            `location` VARCHAR(255) NULL,
            `remarks` TEXT NULL,
            `tracking_date` DATETIME NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_order_id` (`order_id`),
            INDEX `idx_awb_code` (`awb_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "   ✅ shipment_tracking_history table created\n";
    } else {
        echo "   ✅ shipment_tracking_history table already exists\n";
    }
    
    echo "\n✅ All database columns are ready!\n";
    echo "\nYou can now use automatic tracking updates.\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}