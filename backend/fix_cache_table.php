<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Creating courier_serviceability_cache table...\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS `courier_serviceability_cache` (
      `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `pickup_pincode` VARCHAR(10) NOT NULL,
      `delivery_pincode` VARCHAR(10) NOT NULL,
      `weight` DECIMAL(10,2) NOT NULL,
      `cod` TINYINT(1) NOT NULL DEFAULT 0,
      `courier_data` TEXT NOT NULL COMMENT 'JSON data of available couriers',
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `expires_at` TIMESTAMP NULL,
      PRIMARY KEY (`id`),
      INDEX `idx_pincodes` (`pickup_pincode`, `delivery_pincode`),
      INDEX `idx_expires` (`expires_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    
    echo "✓ Table created successfully!\n";
    
    // Verify
    $stmt = $db->query("SHOW TABLES LIKE 'courier_serviceability_cache'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Verification passed\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}