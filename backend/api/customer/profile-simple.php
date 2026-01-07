<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    require_once '../../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

$userEmail = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? '';
if (empty($userEmail)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'User email required'
    ]);
    exit;
}

try {
    // Create users table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        name VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create user_profiles table if it doesn't exist
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
    
    // Get or create user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$userEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Create new user
        $stmt = $pdo->prepare("INSERT INTO users (email, name) VALUES (?, ?)");
        $name = explode('@', $userEmail)[0]; // Default name from email
        $stmt->execute([$userEmail, $name]);
        $userId = $pdo->lastInsertId();
    } else {
        $userId = $user['id'];
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get profile
        $stmt = $pdo->prepare("
            SELECT first_name, last_name, phone, address, city, state, postal_code, country, created_at 
            FROM user_profiles 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get subscription info
        $subStmt = $pdo->prepare("
            SELECT s.plan_code, s.subscription_status, s.is_active,
                   sp.plan_name, sp.status
            FROM subscriptions s
            LEFT JOIN subscription_plans sp ON s.plan_code = sp.plan_code
            WHERE s.email = ? AND s.is_active = 1
            ORDER BY s.created_at DESC
            LIMIT 1
        ");
        $subStmt->execute([$userEmail]);
        $subscription = $subStmt->fetch(PDO::FETCH_ASSOC);
        
        // Default to basic if no subscription
        if (!$subscription) {
            $subscription = [
                'plan_code' => 'basic',
                'plan_name' => 'Basic Plan',
                'subscription_status' => 'active',
                'is_active' => 1,
                'status' => 'active'
            ];
        }
        
        // Simple stats
        $stats = [
            'purchased_tutorials' => 0,
            'completed_tutorials' => 0,
            'learning_hours' => 0.0,
            'practice_uploads' => 0,
            'is_pro_user' => ($subscription['plan_code'] !== 'basic')
        ];
        
        echo json_encode([
            'status' => 'success',
            'profile' => $profile,
            'subscription' => $subscription,
            'stats' => $stats
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Update profile
        $input = json_decode(file_get_contents('php://input'), true);
        
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
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
        
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
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>