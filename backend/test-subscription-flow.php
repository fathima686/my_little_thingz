<?php
// Test subscription flow with real data
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID, X-Tutorial-Email, X-Tutorials-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // First run the database fix
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    echo json_encode(['step' => 'database_connection', 'status' => 'success'], JSON_PRETTY_PRINT) . "\n";
    
    // Check if subscription tables exist and create them if needed
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

    // Ensure users table has proper structure
    try {
        $stmt = $db->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('email', $columns)) {
            $db->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) UNIQUE");
        }
        if (!in_array('created_at', $columns) && !in_array('created_date', $columns)) {
            $db->exec("ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        }
        if (!in_array('password_hash', $columns) && !in_array('password', $columns)) {
            $db->exec("ALTER TABLE users ADD COLUMN password_hash VARCHAR(255)");
        }
        
        echo json_encode(['step' => 'users_table_check', 'status' => 'success', 'columns' => $columns], JSON_PRETTY_PRINT) . "\n";
    } catch (Exception $e) {
        // Create users table if it doesn't exist
        $db->exec("CREATE TABLE users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255),
            role ENUM('customer', 'admin', 'supplier') DEFAULT 'customer',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email)
        )");
        echo json_encode(['step' => 'users_table_created', 'status' => 'success'], JSON_PRETTY_PRINT) . "\n";
    }
    
    // Create subscription tables
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
    
    echo json_encode(['step' => 'subscription_tables_created', 'status' => 'success'], JSON_PRETTY_PRINT) . "\n";
    
    // Seed default plans
    $planCount = (int)$db->query("SELECT COUNT(*) AS c FROM subscription_plans")->fetch(PDO::FETCH_ASSOC)['c'];
    if ($planCount === 0) {
        $plans = [
            ['free', 'Free', 'Limited access to free tutorials', 0.00, 'monthly', json_encode(['Limited free tutorials', 'Basic video quality', 'Community support'])],
            ['premium', 'Premium', 'Unlimited access to all tutorials', 499.00, 'monthly', json_encode(['Unlimited tutorial access', 'HD video quality', 'New content weekly', 'Priority support', 'Download videos'])],
            ['pro', 'Pro', 'Everything in Premium plus mentorship', 999.00, 'monthly', json_encode(['Everything in Premium', '1-on-1 mentorship', 'Live workshops', 'Certificate of completion', 'Early access to new content'])]
        ];
        $insertPlan = $db->prepare("INSERT INTO subscription_plans (plan_code, name, description, price, billing_period, features, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        foreach ($plans as $p) { 
            $insertPlan->execute($p);
        }
        echo json_encode(['step' => 'plans_seeded', 'status' => 'success', 'count' => count($plans)], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo json_encode(['step' => 'plans_exist', 'status' => 'success', 'count' => $planCount], JSON_PRETTY_PRINT) . "\n";
    }
    
    // Test Razorpay config
    try {
        require_once 'config/razorpay-config.php';
        $razorpayConfigured = defined('RAZORPAY_KEY') && defined('RAZORPAY_SECRET') && !empty(RAZORPAY_KEY) && !empty(RAZORPAY_SECRET);
        echo json_encode(['step' => 'razorpay_config', 'status' => $razorpayConfigured ? 'success' : 'error', 'configured' => $razorpayConfigured], JSON_PRETTY_PRINT) . "\n";
        
        if ($razorpayConfigured) {
            // Test Razorpay SDK
            if (class_exists('Razorpay\\Api\\Api')) {
                echo json_encode(['step' => 'razorpay_sdk', 'status' => 'success'], JSON_PRETTY_PRINT) . "\n";
            } else {
                echo json_encode(['step' => 'razorpay_sdk', 'status' => 'error', 'message' => 'SDK not loaded'], JSON_PRETTY_PRINT) . "\n";
            }
        }
    } catch (Exception $e) {
        echo json_encode(['step' => 'razorpay_config', 'status' => 'error', 'message' => $e->getMessage()], JSON_PRETTY_PRINT) . "\n";
    }
    
    // Test subscription creation with sample data
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $testEmail = $input['email'] ?? 'test@example.com';
        $planCode = $input['plan_code'] ?? 'pro';
        
        echo json_encode(['step' => 'testing_subscription', 'email' => $testEmail, 'plan' => $planCode], JSON_PRETTY_PRINT) . "\n";
        
        // Simulate the subscription creation process
        // This is the same logic as in create-subscription.php but with more debugging
        
        // Find or create user
        $userStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $userStmt->execute([$testEmail]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $userId = (int)$user['id'];
            echo json_encode(['step' => 'user_found', 'user_id' => $userId], JSON_PRETTY_PRINT) . "\n";
        } else {
            // Create user
            $insertUser = $db->prepare("INSERT INTO users (email) VALUES (?)");
            $insertUser->execute([$testEmail]);
            $userId = (int)$db->lastInsertId();
            echo json_encode(['step' => 'user_created', 'user_id' => $userId], JSON_PRETTY_PRINT) . "\n";
        }
        
        // Find plan
        $planStmt = $db->prepare("SELECT * FROM subscription_plans WHERE plan_code = ? AND is_active = 1 LIMIT 1");
        $planStmt->execute([$planCode]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($plan) {
            echo json_encode(['step' => 'plan_found', 'plan' => $plan], JSON_PRETTY_PRINT) . "\n";
            
            // For testing, we'll simulate a successful response
            echo json_encode([
                'step' => 'final_result',
                'status' => 'success',
                'message' => 'Test subscription flow completed',
                'subscription_id' => 999,
                'plan_code' => $plan['plan_code'],
                'subscription_status' => 'created',
                'amount' => (float)$plan['price'] * 100,
                'currency' => 'INR',
                'razorpay_subscription_id' => 'sub_test_' . time(),
                'razorpay_plan_id' => 'plan_test_' . time(),
                'test_mode' => true
            ], JSON_PRETTY_PRINT) . "\n";
        } else {
            echo json_encode(['step' => 'plan_not_found', 'status' => 'error', 'plan_code' => $planCode], JSON_PRETTY_PRINT) . "\n";
        }
    }
    
    echo json_encode(['step' => 'complete', 'status' => 'success', 'message' => 'Database setup completed successfully'], JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo json_encode([
        'step' => 'error',
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT) . "\n";
}
?>