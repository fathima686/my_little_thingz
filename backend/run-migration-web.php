<?php
/**
 * Web-based Migration Runner for Simplified Authenticity System
 */

// Only allow local access for security
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1', 'localhost'])) {
    die('Access denied. This migration can only be run locally.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Migrate to Simplified Authenticity System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .btn { padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #005a87; }
        .output { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; font-family: monospace; white-space: pre-wrap; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Migrate to Simplified Authenticity System</h1>
        <p>This will set up the corrected image authenticity system with simplified, explainable similarity detection.</p>
        
        <?php if (isset($_POST['run_migration'])): ?>
            <div class="output">
<?php
try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== Simplified Image Authenticity System Migration ===\n\n";
    
    // Step 1: Create simplified tables
    echo "Step 1: Creating simplified database schema...\n";
    
    // Create image_authenticity_simple table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `image_authenticity_simple` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `image_id` varchar(255) NOT NULL,
          `image_type` enum('practice_upload', 'custom_request') NOT NULL,
          `user_id` int(11) NOT NULL,
          `tutorial_id` int(11) DEFAULT NULL,
          `category` varchar(50) NOT NULL DEFAULT 'general',
          `phash` text DEFAULT NULL,
          `evaluation_status` enum('unique', 'reused', 'highly_similar', 'needs_admin_review') NOT NULL DEFAULT 'unique',
          `admin_decision` enum('pending', 'approved', 'rejected', 'false_positive') DEFAULT 'pending',
          `requires_review` tinyint(1) DEFAULT 0,
          `flagged_reason` text DEFAULT NULL,
          `metadata_notes` text DEFAULT NULL,
          `admin_notes` text DEFAULT NULL,
          `reviewed_by` int(11) DEFAULT NULL,
          `reviewed_at` timestamp NULL DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_image` (`image_id`, `image_type`),
          KEY `idx_category` (`category`),
          KEY `idx_evaluation_status` (`evaluation_status`),
          KEY `idx_requires_review` (`requires_review`),
          KEY `idx_admin_decision` (`admin_decision`),
          KEY `idx_user_tutorial` (`user_id`, `tutorial_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created image_authenticity_simple table\n";
    
    // Create admin_review_simple table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admin_review_simple` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `image_id` varchar(255) NOT NULL,
          `image_type` enum('practice_upload', 'custom_request') NOT NULL,
          `user_id` int(11) NOT NULL,
          `tutorial_id` int(11) DEFAULT NULL,
          `category` varchar(50) NOT NULL,
          `evaluation_status` enum('reused', 'highly_similar', 'needs_admin_review') NOT NULL,
          `flagged_reason` text NOT NULL,
          `similar_image_info` json DEFAULT NULL,
          `admin_decision` enum('pending', 'approved', 'rejected', 'false_positive') DEFAULT 'pending',
          `admin_notes` text DEFAULT NULL,
          `reviewed_by` int(11) DEFAULT NULL,
          `reviewed_at` timestamp NULL DEFAULT NULL,
          `flagged_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_review` (`image_id`, `image_type`),
          KEY `idx_admin_decision` (`admin_decision`),
          KEY `idx_category` (`category`),
          KEY `idx_evaluation_status` (`evaluation_status`),
          KEY `idx_flagged_at` (`flagged_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created admin_review_simple table\n";
    
    // Update practice_uploads table
    try {
        $pdo->exec("ALTER TABLE practice_uploads ADD COLUMN IF NOT EXISTS authenticity_status ENUM('pending', 'verified', 'flagged', 'approved') DEFAULT 'pending'");
        $pdo->exec("ALTER TABLE practice_uploads ADD COLUMN IF NOT EXISTS progress_approved TINYINT(1) DEFAULT 0");
        echo "✓ Updated practice_uploads table\n";
    } catch (Exception $e) {
        echo "Note: practice_uploads already updated\n";
    }
    
    // Update learning_progress table
    try {
        $pdo->exec("ALTER TABLE learning_progress ADD COLUMN IF NOT EXISTS practice_admin_approved TINYINT(1) DEFAULT 0");
        echo "✓ Updated learning_progress table\n";
    } catch (Exception $e) {
        echo "Note: learning_progress already updated\n";
    }
    
    // Set tutorial categories
    echo "\nStep 2: Setting tutorial categories...\n";
    
    $categoryUpdates = [
        'embroidery' => 'embroidery|stitch|needle|thread|borduur',
        'painting' => 'paint|canvas|brush|acrylic|watercolor|verf',
        'drawing' => 'draw|sketch|pencil|charcoal|tekenen',
        'crafts' => 'craft|diy|handmade|creative|knutsel',
        'jewelry' => 'jewelry|bead|wire|pendant|sieraad',
        'pottery' => 'pottery|clay|ceramic|wheel|klei',
        'woodwork' => 'wood|carving|furniture|timber|hout',
        'textile' => 'fabric|sewing|quilting|weaving|stof',
        'photography' => 'photo|camera|lens|portrait|fotografie',
        'digital_art' => 'digital|photoshop|illustrator|graphic|digitaal'
    ];
    
    $categorizedCount = 0;
    foreach ($categoryUpdates as $category => $pattern) {
        $stmt = $pdo->prepare("
            UPDATE tutorials 
            SET category = ? 
            WHERE (LOWER(title) REGEXP ? OR LOWER(description) REGEXP ?)
            AND (category IS NULL OR category = '' OR category = 'general')
        ");
        $stmt->execute([$category, $pattern, $pattern]);
        $categorizedCount += $stmt->rowCount();
    }
    
    // Set remaining to general
    $pdo->exec("UPDATE tutorials SET category = 'general' WHERE category IS NULL OR category = ''");
    echo "✓ Categorized {$categorizedCount} tutorials\n";
    
    // Create admin user if needed
    echo "\nStep 3: Ensuring admin user exists...\n";
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'admin@mylittlethingz.com'");
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, created_at) 
            VALUES ('Admin User', 'admin@mylittlethingz.com', ?, NOW())
        ");
        $stmt->execute([password_hash('admin123', PASSWORD_DEFAULT)]);
        $adminId = $pdo->lastInsertId();
        
        // Ensure user_roles table exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                role_id INT NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, 1)");
        $stmt->execute([$adminId]);
        
        echo "✓ Created admin user (admin@mylittlethingz.com / admin123)\n";
    } else {
        echo "✓ Admin user already exists\n";
    }
    
    // Summary
    echo "\nMigration Summary:\n";
    echo "==================\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM image_authenticity_simple");
    $authCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tutorials WHERE category != 'general'");
    $categorizedTutorials = $stmt->fetch()['count'];
    
    echo "• Authenticity records: {$authCount}\n";
    echo "• Categorized tutorials: {$categorizedTutorials}\n";
    echo "• System ready for simplified similarity detection\n";
    
    echo "\n=== Migration Completed Successfully ===\n";
    echo "\nNext: Update your practice upload to use the corrected API\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
            </div>
        <?php else: ?>
            <form method="post">
                <button type="submit" name="run_migration" class="btn">Run Migration</button>
            </form>
            
            <h3>What this migration does:</h3>
            <ul>
                <li>Creates simplified authenticity tables</li>
                <li>Sets up category-based comparison system</li>
                <li>Removes complex scoring in favor of clear states</li>
                <li>Creates admin user for review system</li>
                <li>Categorizes existing tutorials</li>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>