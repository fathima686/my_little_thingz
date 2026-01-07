<?php
// Bulletproof Profile API - No dependencies, handles all errors gracefully
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email');

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(0);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Simple database connection without external dependencies
function getSimpleConnection() {
    try {
        $host = 'localhost';
        $dbname = 'my_little_thingz';
        $username = 'root';
        $password = '';
        
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        return $pdo;
    } catch (Exception $e) {
        return null;
    }
}

try {
    $pdo = getSimpleConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get user email
    $userEmail = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? '';
    if (empty($userEmail)) {
        throw new Exception('User email required in X-Tutorial-Email header');
    }
    
    // Create tables if they don't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        name VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
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
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
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
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        plan_code VARCHAR(50) NOT NULL,
        subscription_status ENUM('active', 'inactive', 'cancelled') DEFAULT 'active',
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Ensure subscription plans exist
    $planCount = $pdo->query("SELECT COUNT(*) FROM subscription_plans")->fetchColumn();
    if ($planCount == 0) {
        $plans = [
            ['basic', 'Basic Plan', 0.00, 1, '["Access to free tutorials"]'],
            ['pro', 'Pro Plan', 299.00, 1, '["All tutorials", "Live workshops", "Downloads"]'],
            ['premium', 'Premium Plan', 499.00, 1, '["All Pro features", "1-on-1 sessions"]']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO subscription_plans (plan_code, plan_name, price, duration_months, features) VALUES (?, ?, ?, ?, ?)");
        foreach ($plans as $plan) {
            $stmt->execute($plan);
        }
    }
    
    // Get or create user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$userEmail]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $stmt = $pdo->prepare("INSERT INTO users (email, name) VALUES (?, ?)");
        $name = explode('@', $userEmail)[0];
        $stmt->execute([$userEmail, $name]);
        $userId = $pdo->lastInsertId();
    } else {
        $userId = $user['id'];
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get profile
        $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();
        
        // Get subscription
        $stmt = $pdo->prepare("
            SELECT s.plan_code, s.subscription_status, s.is_active, s.created_at,
                   sp.plan_name, sp.price, sp.features
            FROM subscriptions s
            LEFT JOIN subscription_plans sp ON s.plan_code = sp.plan_code
            WHERE s.email = ? AND s.is_active = 1
            ORDER BY s.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userEmail]);
        $subscription = $stmt->fetch();
        
        // Default to basic if no subscription
        if (!$subscription) {
            // Create basic subscription
            try {
                $pdo->prepare("INSERT INTO subscriptions (email, plan_code, subscription_status, is_active) VALUES (?, 'basic', 'active', 1)")
                    ->execute([$userEmail]);
            } catch (Exception $e) {
                // Ignore if already exists
            }
            
            $subscription = [
                'plan_code' => 'basic',
                'plan_name' => 'Basic Plan',
                'subscription_status' => 'active',
                'is_active' => 1,
                'price' => 0.00,
                'features' => ['Access to free tutorials']
            ];
        }
        
        // Parse features if JSON string
        if (isset($subscription['features']) && is_string($subscription['features'])) {
            $subscription['features'] = json_decode($subscription['features'], true) ?: [];
        }
        
        // Simple stats
        $stats = [
            'purchased_tutorials' => 0,
            'completed_tutorials' => 0,
            'learning_hours' => 0.0,
            'practice_uploads' => 0,
            'is_pro_user' => ($subscription['plan_code'] === 'pro' || $subscription['plan_code'] === 'premium')
        ];
        
        echo json_encode([
            'status' => 'success',
            'profile' => $profile,
            'subscription' => $subscription,
            'stats' => $stats,
            'user_email' => $userEmail,
            'user_id' => $userId
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Update profile
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }
        
        $firstName = $input['first_name'] ?? '';
        $lastName = $input['last_name'] ?? '';
        $phone = $input['phone'] ?? '';
        $address = $input['address'] ?? '';
        $city = $input['city'] ?? '';
        $state = $input['state'] ?? '';
        $postalCode = $input['postal_code'] ?? '';
        $country = $input['country'] ?? 'India';
        
        // Check if profile exists
        $stmt = $pdo->prepare("SELECT user_id FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update
            $stmt = $pdo->prepare("
                UPDATE user_profiles 
                SET first_name = ?, last_name = ?, phone = ?, address = ?, 
                    city = ?, state = ?, postal_code = ?, country = ?, updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([$firstName, $lastName, $phone, $address, $city, $state, $postalCode, $country, $userId]);
        } else {
            // Insert
            $stmt = $pdo->prepare("
                INSERT INTO user_profiles 
                (user_id, first_name, last_name, phone, address, city, state, postal_code, country) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $firstName, $lastName, $phone, $address, $city, $state, $postalCode, $country]);
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Profile updated successfully'
        ]);
    } else {
        throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug_info' => [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'email_header' => $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? 'missing',
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
}
?>