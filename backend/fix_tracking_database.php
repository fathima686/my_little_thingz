<?php
/**
 * Fix Order Tracking Database Schema
 * Adds missing columns needed for live tracking
 */

require_once 'config/database.php';

try {
    $db = (new Database())->getConnection();
    
    echo "=== FIXING ORDER TRACKING DATABASE ===\n\n";
    
    // Check and add shipment_status column
    echo "1. Checking shipment_status column...\n";
    $checkShipmentStatus = $db->query("SHOW COLUMNS FROM orders LIKE 'shipment_status'")->fetch();
    if (!$checkShipmentStatus) {
        echo "   Adding shipment_status column...\n";
        $db->exec("ALTER TABLE orders ADD COLUMN shipment_status VARCHAR(100) NULL AFTER pickup_token_number");
        echo "   ✓ shipment_status column added\n";
    } else {
        echo "   ✓ shipment_status column already exists\n";
    }
    
    // Check and add current_status column
    echo "\n2. Checking current_status column...\n";
    $checkCurrentStatus = $db->query("SHOW COLUMNS FROM orders LIKE 'current_status'")->fetch();
    if (!$checkCurrentStatus) {
        echo "   Adding current_status column...\n";
        $db->exec("ALTER TABLE orders ADD COLUMN current_status VARCHAR(100) NULL AFTER shipment_status");
        echo "   ✓ current_status column added\n";
    } else {
        echo "   ✓ current_status column already exists\n";
    }
    
    // Check and add tracking_updated_at column
    echo "\n3. Checking tracking_updated_at column...\n";
    $checkTrackingUpdated = $db->query("SHOW COLUMNS FROM orders LIKE 'tracking_updated_at'")->fetch();
    if (!$checkTrackingUpdated) {
        echo "   Adding tracking_updated_at column...\n";
        $db->exec("ALTER TABLE orders ADD COLUMN tracking_updated_at TIMESTAMP NULL AFTER current_status");
        echo "   ✓ tracking_updated_at column added\n";
    } else {
        echo "   ✓ tracking_updated_at column already exists\n";
    }
    
    // Verify shipment_tracking_history table
    echo "\n4. Verifying shipment_tracking_history table...\n";
    $checkTrackingTable = $db->query("SHOW TABLES LIKE 'shipment_tracking_history'")->fetch();
    if (!$checkTrackingTable) {
        echo "   Creating shipment_tracking_history table...\n";
        $db->exec("CREATE TABLE shipment_tracking_history (
            id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT(11) NOT NULL,
            awb_code VARCHAR(100) NULL,
            status VARCHAR(100) NULL,
            status_code VARCHAR(50) NULL,
            location VARCHAR(255) NULL,
            remarks TEXT NULL,
            tracking_date DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_order_id (order_id),
            INDEX idx_awb_code (awb_code),
            INDEX idx_tracking_date (tracking_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "   ✓ shipment_tracking_history table created\n";
    } else {
        echo "   ✓ shipment_tracking_history table already exists\n";
    }
    
    echo "\n=== DATABASE FIX COMPLETED SUCCESSFULLY ===\n";
    echo "\nAll required columns and tables are now in place.\n";
    echo "You can now use the live tracking feature!\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}