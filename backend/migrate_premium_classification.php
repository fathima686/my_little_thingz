<?php
/**
 * Premium Gifts Classification Migration Script
 * Run this script to safely add category_tier column to artworks table
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Starting Premium Gifts Classification Migration...\n";
    
    // Check if column already exists
    $checkColumn = $db->query("SHOW COLUMNS FROM artworks LIKE 'category_tier'");
    if ($checkColumn && $checkColumn->rowCount() > 0) {
        echo "✅ category_tier column already exists!\n";
    } else {
        // Add the column
        $db->exec("ALTER TABLE artworks ADD COLUMN category_tier ENUM('Budget', 'Premium') DEFAULT 'Budget' AFTER status");
        echo "✅ Added category_tier column to artworks table\n";
        
        // Create index for better performance
        $db->exec("CREATE INDEX idx_artworks_category_tier ON artworks(category_tier)");
        echo "✅ Created index for category_tier\n";
    }
    
    // Update existing items based on price thresholds
    $stmt = $db->prepare("UPDATE artworks SET category_tier = 'Premium' WHERE price >= 1000.00 AND category_tier = 'Budget'");
    $stmt->execute();
    $premiumCount = $stmt->rowCount();
    echo "✅ Updated {$premiumCount} items to Premium tier (price >= ₹1000)\n";
    
    // Update items with luxury keywords
    $stmt = $db->prepare("UPDATE artworks SET category_tier = 'Premium' WHERE (
        LOWER(title) LIKE '%luxury%' OR 
        LOWER(title) LIKE '%premium%' OR 
        LOWER(title) LIKE '%designer%' OR 
        LOWER(title) LIKE '%exclusive%' OR
        LOWER(title) LIKE '%hamper%' OR
        LOWER(title) LIKE '%portrait%' OR
        LOWER(description) LIKE '%luxury%' OR 
        LOWER(description) LIKE '%premium%' OR 
        LOWER(description) LIKE '%designer%' OR 
        LOWER(description) LIKE '%exclusive%'
    ) AND category_tier = 'Budget'");
    $stmt->execute();
    $luxuryCount = $stmt->rowCount();
    echo "✅ Updated {$luxuryCount} items to Premium tier (luxury keywords)\n";
    
    // Show statistics
    $stmt = $db->query("SELECT category_tier, COUNT(*) as count, MIN(price) as min_price, MAX(price) as max_price, AVG(price) as avg_price FROM artworks WHERE status = 'active' GROUP BY category_tier");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📊 Classification Statistics:\n";
    foreach ($stats as $stat) {
        echo "  {$stat['category_tier']}: {$stat['count']} items (₹{$stat['min_price']} - ₹{$stat['max_price']}, avg: ₹" . number_format($stat['avg_price'], 2) . ")\n";
    }
    
    echo "\n🎉 Premium Gifts Classification migration completed successfully!\n";
    echo "💡 Premium items will now show 💎 Premium badges and get higher recommendation scores.\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}




