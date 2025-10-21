<?php
/**
 * Migration: Add order_addons table to store selected add-ons for orders
 * Run this via CLI: php order_addons_migration.php
 */

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Check if table exists
    $check = $db->query("SHOW TABLES LIKE 'order_addons'");
    if ($check && $check->rowCount() > 0) {
        echo "✓ Table 'order_addons' already exists.\n";
        exit(0);
    }

    // Create order_addons table
    $sql = "CREATE TABLE IF NOT EXISTS order_addons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        addon_id VARCHAR(50) NOT NULL,
        addon_name VARCHAR(255) NOT NULL,
        addon_price DECIMAL(10, 2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        INDEX idx_order_id (order_id)
    )";

    $db->exec($sql);
    echo "✓ Table 'order_addons' created successfully.\n";
    exit(0);

} catch (Exception $e) {
    echo "✗ Error creating table: " . $e->getMessage() . "\n";
    exit(1);
}