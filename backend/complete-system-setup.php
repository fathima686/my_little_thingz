<?php
/**
 * Complete System Setup - Creates all necessary tables and sample data
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    $results = [];
    $results[] = "🚀 Starting complete system setup...";
    
    // 1. Create Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        name VARCHAR(255),
        password VARCHAR(255),
        role ENUM('customer', 'admin', 'teacher') DEFAULT 'customer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $results[] = "✅ Users table created/verified";
    
    // 2. Create Tutorials Table with sample data
    $pdo->exec("CREATE TABLE IF NOT EXISTS tutorials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        video_url VARCHAR(500),
        thumbnail_url VARCHAR(500),
        duration_minutes INT DEFAULT 0,
        difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
        category VARCHAR(100) DEFAULT 'general',
        is_free BOOLEAN DEFAULT 0,
        price DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Check if tutorials exist, if not create sample ones
    $tutorialCount = $pdo->query("SELECT COUNT(*) FROM tutorials")->fetchColumn();
    if ($tutorialCount == 0) {
        $sampleTutorials = [
            ['Ring Making Masterclass', 'Learn to create beautiful handmade rings with professional techniques', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://img.youtube.com/vi/dQw4w9WgXcQ/maxresdefault.jpg', 45, 'intermediate', 'jewelry', 0, 299.00],
            ['Beginner Earring Tutorial', 'Perfect for beginners - create stunning earrings step by step', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://img.youtube.com/vi/dQw4w9WgXcQ/maxresdefault.jpg', 30, 'beginner', 'jewelry', 1, 0.00],
            ['Kitkat Chocolate Bouquets', 'Create amazing chocolate bouquets for special occasions', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://img.youtube.com/vi/dQw4w9WgXcQ/maxresdefault.jpg', 60, 'advanced', 'food-craft', 0, 399.00],
            ['Clock Resin Art', 'Master the art of resin clock making with this comprehensive guide', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://img.youtube.com/vi/dQw4w9WgXcQ/maxresdefault.jpg', 90, 'intermediate', 'resin-art', 0, 499.00],
            ['Mirror Clay Decoration', 'Transform ordinary mirrors with beautiful clay decorations', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://img.youtube.com/vi/dQw4w9WgXcQ/maxresdefault.jpg', 40, 'beginner', 'home-decor', 0, 199.00],
            ['Advanced Ring Techniques', 'Take your ring making to the next level with advanced methods', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://img.youtube.com/vi/dQw4w9WgXcQ/maxresdefault.jpg', 75, 'advanced', 'jewelry', 0, 599.00]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO tutorials (title, description, video_url, thumbnail_url, duration_minutes, difficulty_level, category, is_free, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($sampleTutorials as $tutorial) {
            $stmt->execute($tutorial);
        }
        $results[] = "✅ Created " . count($sampleTutorials) . " sample tutorials";
    } else {
        $results[] = "✅ Tutorials table verified ($tutorialCount existing tutorials)";
    }
    
    // 3. Create Subscription Plans
    $pdo->exec("CREATE TABLE IF NOT EXISTS subscription_plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plan_code VARCHAR(50) UNIQUE NOT NULL,
        plan_name VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        duration_months INT NOT NULL,
        features JSON,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert default plans
    $planCount = $pdo->query("SELECT COUNT(*) FROM subscription_plans")->fetchColumn();
    if ($planCount == 0) {
        $plans = [
            ['basic', 'Basic Plan', 0.00, 1, '["Access to free tutorials", "Basic support"]'],
            ['pro', 'Pro Plan', 299.00, 1, '["Access to all tutorials", "Download videos", "Priority support", "Practice uploads"]'],
            ['premium', 'Premium Plan', 499.00, 1, '["All Pro features", "Live workshops", "1-on-1 sessions", "Certificate generation"]']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO subscription_plans (plan_code, plan_name, price, duration_months, features) VALUES (?, ?, ?, ?, ?)");
        foreach ($plans as $plan) {
            $stmt->execute($plan);
        }
        $results[] = "✅ Created " . count($plans) . " subscription plans";
    } else {
        $results[] = "✅ Subscription plans verified ($planCount existing plans)";
    }
    
    // 4. Create Subscriptions Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        plan_code VARCHAR(50) NOT NULL,
        subscription_status ENUM('active', 'inactive', 'cancelled') DEFAULT 'active',
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (plan_code) REFERENCES subscription_plans(plan_code)
    )");
    $results[] = "✅ Subscriptions table created/verified";
    
    // 5. Create User Profiles Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        first_name VARCHAR(100),
        last_name VARCHAR(100),
        phone VARCHAR(20),
        address TEXT,
        city VARCHAR(100),
        state VARCHAR(100),
        postal_code VARCHAR(20),
        country VARCHAR(100) DEFAULT 'India',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    $results[] = "✅ User profiles table created/verified";
    
    // 6. Create Tutorial Purchases Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS tutorial_purchases (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        tutorial_id INT NOT NULL,
        payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
        amount_paid DECIMAL(10,2),
        purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (tutorial_id) REFERENCES tutorials(id) ON DELETE CASCADE
    )");
    $results[] = "✅ Tutorial purchases table created/verified";
    
    // 7. Create Notifications Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
        action_url VARCHAR(500),
        is_read BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    $results[] = "✅ Notifications table created/verified";
    
    // 8. Create sample admin user
    $adminEmail = 'admin@mylittlethingz.com';
    $adminExists = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $adminExists->execute([$adminEmail]);
    
    if ($adminExists->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (email, name, role) VALUES (?, ?, ?)");
        $stmt->execute([$adminEmail, 'Admin User', 'admin']);
        $results[] = "✅ Created admin user: $adminEmail";
    } else {
        $results[] = "✅ Admin user already exists: $adminEmail";
    }
    
    // 9. Create sample customer with pro subscription
    $testEmail = 'soudhame52@gmail.com';
    $userExists = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $userExists->execute([$testEmail]);
    $user = $userExists->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $stmt = $pdo->prepare("INSERT INTO users (email, name, role) VALUES (?, ?, ?)");
        $stmt->execute([$testEmail, 'Test User', 'customer']);
        $userId = $pdo->lastInsertId();
        $results[] = "✅ Created test user: $testEmail";
    } else {
        $userId = $user['id'];
        $results[] = "✅ Test user already exists: $testEmail";
    }
    
    // Give test user a pro subscription
    $subExists = $pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE email = ? AND is_active = 1");
    $subExists->execute([$testEmail]);
    
    if ($subExists->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO subscriptions (email, plan_code, subscription_status, is_active) VALUES (?, ?, ?, ?)");
        $stmt->execute([$testEmail, 'pro', 'active', 1]);
        $results[] = "✅ Assigned Pro subscription to test user";
    } else {
        $results[] = "✅ Test user already has active subscription";
    }
    
    // 10. Test API endpoints
    $results[] = "\n🧪 Testing API endpoints:";
    
    $apiTests = [
        'Tutorials API' => 'api/customer/tutorials-simple.php',
        'Subscription Status API' => 'api/customer/subscription-status-simple.php?email=' . $testEmail,
        'Profile API' => 'api/customer/profile-simple.php'
    ];
    
    foreach ($apiTests as $name => $endpoint) {
        if (file_exists($endpoint)) {
            $results[] = "✅ $name - File exists";
        } else {
            $results[] = "❌ $name - File missing: $endpoint";
        }
    }
    
    $results[] = "\n🎉 System setup complete!";
    $results[] = "📋 Summary:";
    $results[] = "- Database tables created and populated";
    $results[] = "- Sample tutorials with videos available";
    $results[] = "- Test user with Pro subscription created";
    $results[] = "- All APIs should now work correctly";
    $results[] = "\n🔗 Test URLs:";
    $results[] = "- Tutorials: /my_little_thingz/backend/api/customer/tutorials-simple.php";
    $results[] = "- Subscription: /my_little_thingz/backend/api/customer/subscription-status-simple.php?email=$testEmail";
    $results[] = "- Profile: /my_little_thingz/backend/api/customer/profile-simple.php";
    
    echo json_encode([
        'status' => 'success',
        'message' => 'System setup completed successfully',
        'results' => $results
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Setup failed: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
?>