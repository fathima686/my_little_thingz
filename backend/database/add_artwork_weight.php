<?php
/**
 * Add weight column to artworks table
 * This allows each artwork to have its own weight for shipping calculation
 */

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Adding weight column to artworks table...\n";
    
    // Check if weight column already exists
    $checkStmt = $db->query("SHOW COLUMNS FROM artworks LIKE 'weight'");
    $exists = $checkStmt->fetch();
    
    if ($exists) {
        echo "✓ Weight column already exists in artworks table\n";
    } else {
        // Add weight column (default 0.5 kg for most artworks)
        $db->exec("ALTER TABLE artworks ADD COLUMN weight DECIMAL(10,2) NOT NULL DEFAULT 0.50 COMMENT 'Product weight in kg' AFTER price");
        echo "✓ Weight column added to artworks table\n";
    }
    
    // Update some default weights for existing artworks (you can customize these)
    echo "\nUpdating default weights for existing artworks...\n";
    $db->exec("UPDATE artworks SET weight = 0.50 WHERE weight = 0 OR weight IS NULL");
    echo "✓ Default weights set to 0.5 kg\n";
    
    echo "\n✅ Migration completed successfully!\n";
    echo "\nNote: You can update individual artwork weights in the admin panel.\n";
    echo "Shipping charges will be calculated as: ₹60 per kg (minimum ₹60)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}