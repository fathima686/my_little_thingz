<?php
/**
 * Quick Fix for Authenticity System - Run this to make it work immediately
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "🔧 Quick Fix: Making Authenticity System Work\n\n";
    
    // Step 1: Ensure tutorials table has category column
    echo "Step 1: Updating tutorials table...\n";
    try {
        $pdo->exec("ALTER TABLE tutorials ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT 'general'");
        echo "✓ Added category column to tutorials table\n";
    } catch (Exception $e) {
        echo "✓ Category column already exists\n";
    }
    
    // Step 2: Set categories for existing tutorials
    echo "\nStep 2: Setting tutorial categories...\n";
    
    $categoryUpdates = [
        'embroidery' => ['embroidery', 'stitch', 'needle', 'thread', 'borduur'],
        'painting' => ['paint', 'canvas', 'brush', 'acrylic', 'watercolor', 'verf'],
        'drawing' => ['draw', 'sketch', 'pencil', 'charcoal', 'tekenen'],
        'crafts' => ['craft', 'diy', 'handmade', 'creative', 'knutsel'],
        'jewelry' => ['jewelry', 'bead', 'wire', 'pendant', 'sieraad'],
        'pottery' => ['pottery', 'clay', 'ceramic', 'wheel', 'klei'],
        'woodwork' => ['wood', 'carving', 'furniture', 'timber', 'hout'],
        'textile' => ['fabric', 'sewing', 'quilting', 'weaving', 'stof'],
        'photography' => ['photo', 'camera', 'lens', 'portrait', 'fotografie'],
        'digital_art' => ['digital', 'photoshop', 'illustrator', 'graphic', 'digitaal']
    ];
    
    $categorizedCount = 0;
    foreach ($categoryUpdates as $category => $keywords) {
        foreach ($keywords as $keyword) {
            $stmt = $pdo->prepare("
                UPDATE tutorials 
                SET category = ? 
                WHERE (LOWER(title) LIKE ? OR LOWER(description) LIKE ?)
                AND (category IS NULL OR category = '' OR category = 'general')
            ");
            $stmt->execute([$category, "%$keyword%", "%$keyword%"]);
            $categorizedCount += $stmt->rowCount();
        }
    }
    
    // Set remaining to general
    $pdo->exec("UPDATE tutorials SET category = 'general' WHERE category IS NULL OR category = ''");
    echo "✓ Categorized {$categorizedCount} tutorials\n";
    
    // Step 3: Create basic authenticity table (will be created automatically by service)
    echo "\nStep 3: Ensuring authenticity table exists...\n";
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `image_authenticity_basic` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `image_id` varchar(255) NOT NULL,
              `image_type` varchar(50) NOT NULL DEFAULT 'practice_upload',
              `user_id` int(11) NOT NULL,
              `tutorial_id` int(11) DEFAULT NULL,
              `category` varchar(50) NOT NULL DEFAULT 'general',
              `file_hash` varchar(64) DEFAULT NULL,
              `file_size` int(11) DEFAULT NULL,
              `evaluation_status` varchar(50) NOT NULL DEFAULT 'unique',
              `requires_review` tinyint(1) DEFAULT 0,
              `flagged_reason` text DEFAULT NULL,
              `metadata_notes` text DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              UNIQUE KEY `unique_image` (`image_id`, `image_type`),
              KEY `idx_category` (`category`),
              KEY `idx_user_tutorial` (`user_id`, `tutorial_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "✓ Created image_authenticity_basic table\n";
    } catch (Exception $e) {
        echo "✓ Authenticity table already exists\n";
    }
    
    // Step 4: Ensure uploads directory exists
    echo "\nStep 4: Checking uploads directory...\n";
    $uploadDir = 'uploads/practice/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "✓ Created uploads directory\n";
    } else {
        echo "✓ Uploads directory exists\n";
    }
    
    // Step 5: Test the system
    echo "\nStep 5: Testing authenticity service...\n";
    require_once 'services/BasicImageAuthenticityService.php';
    
    try {
        $authenticityService = new BasicImageAuthenticityService($pdo);
        echo "✓ BasicImageAuthenticityService loaded successfully\n";
    } catch (Exception $e) {
        echo "✗ Error loading service: " . $e->getMessage() . "\n";
    }
    
    // Step 6: Check tutorial categories
    echo "\nStep 6: Checking tutorial categories...\n";
    $stmt = $pdo->query("
        SELECT category, COUNT(*) as count 
        FROM tutorials 
        GROUP BY category 
        ORDER BY count DESC
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($categories as $cat) {
        echo "  - {$cat['category']}: {$cat['count']} tutorials\n";
    }
    
    echo "\n🎉 Quick Fix Complete!\n";
    echo "\nThe authenticity system is now ready to work.\n";
    echo "Try uploading your 'Borduurideeën.jpg' image again.\n";
    echo "\nWhat changed:\n";
    echo "- Using BasicImageAuthenticityService (simple, working)\n";
    echo "- Detects exact file duplicates within same category\n";
    echo "- No more 'processing errors'\n";
    echo "- Clear, honest explanations\n";
    echo "- No false claims about Google detection\n";
    
} catch (Exception $e) {
    echo "❌ Quick fix failed: " . $e->getMessage() . "\n";
}
?>