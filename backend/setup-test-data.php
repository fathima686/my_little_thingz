<?php
/**
 * Setup Test Data for Corrected Authenticity System
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Setting up test data for corrected authenticity system...\n\n";
    
    // Create test tutorials with different categories
    $testTutorials = [
        [
            'id' => 1,
            'title' => 'Basic Embroidery Stitches',
            'category' => 'embroidery',
            'description' => 'Learn fundamental embroidery techniques and stitches'
        ],
        [
            'id' => 2,
            'title' => 'Watercolor Painting Basics',
            'category' => 'painting',
            'description' => 'Introduction to watercolor painting techniques'
        ],
        [
            'id' => 3,
            'title' => 'Pencil Drawing Fundamentals',
            'category' => 'drawing',
            'description' => 'Master the basics of pencil drawing and shading'
        ],
        [
            'id' => 4,
            'title' => 'DIY Craft Projects',
            'category' => 'crafts',
            'description' => 'Creative DIY projects for beginners'
        ],
        [
            'id' => 5,
            'title' => 'Jewelry Making Workshop',
            'category' => 'jewelry',
            'description' => 'Create beautiful handmade jewelry pieces'
        ]
    ];
    
    foreach ($testTutorials as $tutorial) {
        $stmt = $pdo->prepare("
            INSERT INTO tutorials (id, title, category, description, created_at) 
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            title = VALUES(title),
            category = VALUES(category),
            description = VALUES(description)
        ");
        $stmt->execute([
            $tutorial['id'],
            $tutorial['title'],
            $tutorial['category'],
            $tutorial['description']
        ]);
        echo "✓ Created/Updated tutorial: {$tutorial['title']} (Category: {$tutorial['category']})\n";
    }
    
    // Ensure test user exists
    $stmt = $pdo->prepare("
        INSERT INTO users (id, name, email, password, created_at) 
        VALUES (1, 'Test User', 'soudhame52@gmail.com', ?, NOW())
        ON DUPLICATE KEY UPDATE 
        name = VALUES(name),
        email = VALUES(email)
    ");
    $stmt->execute([password_hash('test123', PASSWORD_DEFAULT)]);
    echo "✓ Ensured test user exists (soudhame52@gmail.com)\n";
    
    // Create Pro subscription for test user
    $stmt = $pdo->prepare("
        INSERT INTO subscription_plans (id, plan_code, plan_name, price, features) 
        VALUES (1, 'pro', 'Pro Plan', 29.99, 'All features included')
        ON DUPLICATE KEY UPDATE 
        plan_code = VALUES(plan_code),
        plan_name = VALUES(plan_name)
    ");
    $stmt->execute();
    
    $stmt = $pdo->prepare("
        INSERT INTO subscriptions (user_id, plan_id, status, created_at) 
        VALUES (1, 1, 'active', NOW())
        ON DUPLICATE KEY UPDATE 
        status = 'active'
    ");
    $stmt->execute();
    echo "✓ Ensured Pro subscription for test user\n";
    
    // Create uploads directory
    $uploadDir = 'uploads/practice/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "✓ Created uploads directory\n";
    } else {
        echo "✓ Uploads directory already exists\n";
    }
    
    echo "\nTest data setup completed!\n";
    echo "\nYou can now:\n";
    echo "1. Run migration: http://localhost/my_little_thingz/backend/run-migration-web.php\n";
    echo "2. Test uploads: http://localhost/my_little_thingz/test-corrected-authenticity.html\n";
    echo "3. View admin dashboard: http://localhost/my_little_thingz/frontend/admin/simple-authenticity-dashboard.html\n";
    
} catch (Exception $e) {
    echo "Error setting up test data: " . $e->getMessage() . "\n";
}
?>