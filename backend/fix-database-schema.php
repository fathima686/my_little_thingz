<?php
// Database schema fix for tutorial and subscription functionality
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Starting database schema fix...\n";
    
    // 1. Check and fix users table
    echo "\n1. Checking users table...\n";
    
    try {
        $stmt = $db->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Current users table columns: " . implode(', ', $columns) . "\n";
        
        // Ensure basic columns exist
        if (!in_array('email', $columns)) {
            echo "Adding email column...\n";
            $db->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) UNIQUE");
        }
        
        if (!in_array('created_at', $columns) && !in_array('created_date', $columns)) {
            echo "Adding created_at column...\n";
            $db->exec("ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        }
        
        // Add password_hash if neither password nor password_hash exists
        if (!in_array('password', $columns) && !in_array('password_hash', $columns)) {
            echo "Adding password_hash column...\n";
            $db->exec("ALTER TABLE users ADD COLUMN password_hash VARCHAR(255)");
        }
        
        // Add role column if it doesn't exist
        if (!in_array('role', $columns)) {
            echo "Adding role column...\n";
            $db->exec("ALTER TABLE users ADD COLUMN role ENUM('customer', 'admin', 'supplier') DEFAULT 'customer'");
        }
        
    } catch (Exception $e) {
        echo "Users table doesn't exist, creating it...\n";
        $db->exec("CREATE TABLE users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255),
            role ENUM('customer', 'admin', 'supplier') DEFAULT 'customer',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_role (role)
        )");
        echo "✓ Users table created\n";
    }
    
    // 2. Ensure tutorials table exists
    echo "\n2. Checking tutorials table...\n";
    
    $db->exec("CREATE TABLE IF NOT EXISTS tutorials (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        thumbnail_url VARCHAR(255),
        video_url VARCHAR(255) NOT NULL,
        duration INT,
        difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
        price DECIMAL(10, 2) DEFAULT 0,
        is_free BOOLEAN DEFAULT 0,
        category VARCHAR(100),
        created_by INT UNSIGNED,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT 1,
        INDEX idx_active (is_active),
        INDEX idx_category (category),
        INDEX idx_price (price),
        INDEX idx_is_free (is_free)
    )");
    echo "✓ Tutorials table ready\n";
    
    // 3. Ensure tutorial_purchases table exists
    echo "\n3. Checking tutorial_purchases table...\n";
    
    $db->exec("CREATE TABLE IF NOT EXISTS tutorial_purchases (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        tutorial_id INT UNSIGNED NOT NULL,
        purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expiry_date DATETIME,
        payment_method VARCHAR(50),
        razorpay_order_id VARCHAR(100),
        razorpay_payment_id VARCHAR(100),
        payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
        amount_paid DECIMAL(10, 2),
        UNIQUE KEY unique_purchase (user_id, tutorial_id),
        INDEX idx_user (user_id),
        INDEX idx_tutorial (tutorial_id),
        INDEX idx_status (payment_status),
        INDEX idx_razorpay_order (razorpay_order_id)
    )");
    echo "✓ Tutorial purchases table ready\n";
    
    // 4. Ensure subscription tables exist
    echo "\n4. Checking subscription tables...\n";
    
    // Determine JSON column type
    $jsonType = 'TEXT';
    try {
        $versionCheck = $db->query("SELECT VERSION() as version");
        $version = $versionCheck->fetch(PDO::FETCH_ASSOC)['version'];
        if (version_compare($version, '5.7.8', '>=') || (strpos($version, 'MariaDB') !== false && version_compare($version, '10.2.7', '>='))) {
            $jsonType = 'JSON';
        }
    } catch (Exception $e) {
        $jsonType = 'TEXT';
    }
    
    $db->exec("CREATE TABLE IF NOT EXISTS subscription_plans (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        plan_code VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10, 2) NOT NULL,
        currency VARCHAR(10) DEFAULT 'INR',
        billing_period ENUM('monthly', 'yearly') DEFAULT 'monthly',
        razorpay_plan_id VARCHAR(100),
        features $jsonType,
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_plan_code (plan_code),
        INDEX idx_active (is_active)
    )");
    echo "✓ Subscription plans table ready\n";
    
    $db->exec("CREATE TABLE IF NOT EXISTS subscriptions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        plan_id INT UNSIGNED NOT NULL,
        razorpay_subscription_id VARCHAR(100),
        razorpay_plan_id VARCHAR(100),
        status ENUM('created', 'authenticated', 'active', 'pending', 'halted', 'cancelled', 'completed', 'expired') DEFAULT 'created',
        current_start TIMESTAMP NULL,
        current_end TIMESTAMP NULL,
        quantity INT DEFAULT 1,
        total_count INT DEFAULT NULL,
        paid_count INT DEFAULT 0,
        remaining_count INT DEFAULT NULL,
        notes $jsonType,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_status (status),
        INDEX idx_razorpay_subscription_id (razorpay_subscription_id)
    )");
    echo "✓ Subscriptions table ready\n";
    
    // 5. Seed default subscription plans
    echo "\n5. Checking subscription plans data...\n";
    
    $planCount = (int)$db->query("SELECT COUNT(*) AS c FROM subscription_plans")->fetch(PDO::FETCH_ASSOC)['c'];
    if ($planCount === 0) {
        echo "Seeding default subscription plans...\n";
        $plans = [
            ['free', 'Free', 'Limited access to free tutorials', 0.00, 'monthly', json_encode(['Limited free tutorials', 'Basic video quality', 'Community support'])],
            ['premium', 'Premium', 'Unlimited access to all tutorials', 499.00, 'monthly', json_encode(['Unlimited tutorial access', 'HD video quality', 'New content weekly', 'Priority support', 'Download videos'])],
            ['pro', 'Pro', 'Everything in Premium plus mentorship', 999.00, 'monthly', json_encode(['Everything in Premium', '1-on-1 mentorship', 'Live workshops', 'Certificate of completion', 'Early access to new content'])]
        ];
        $insertPlan = $db->prepare("INSERT INTO subscription_plans (plan_code, name, description, price, billing_period, features, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        foreach ($plans as $p) { 
            $insertPlan->execute($p);
            echo "✓ Created plan: " . $p[1] . "\n";
        }
    } else {
        echo "Subscription plans already exist ($planCount plans)\n";
    }
    
    // 6. Check sample tutorials
    echo "\n6. Checking tutorials data...\n";
    
    $tutorialCount = (int)$db->query("SELECT COUNT(*) AS c FROM tutorials")->fetch(PDO::FETCH_ASSOC)['c'];
    if ($tutorialCount === 0) {
        echo "No tutorials found. You may want to run migrate_tutorials.php to add sample data.\n";
    } else {
        echo "Found $tutorialCount tutorials\n";
    }
    
    echo "\n✅ Database schema fix completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Test the subscription API again\n";
    echo "2. Try the complete subscription flow\n";
    echo "3. Check that Razorpay integration works\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
?>