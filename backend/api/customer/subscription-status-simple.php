<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

$userEmail = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? $_GET['email'] ?? '';

if (empty($userEmail)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'User email required'
    ]);
    exit;
}

try {
    // Create subscription tables if they don't exist
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (plan_code) REFERENCES subscription_plans(plan_code)
    )");
    
    // Insert default plans if they don't exist
    $planCheck = $pdo->query("SELECT COUNT(*) as count FROM subscription_plans")->fetch();
    if ($planCheck['count'] == 0) {
        $plans = [
            ['basic', 'Basic Plan', 0.00, 1, '["Access to free tutorials"]'],
            ['pro', 'Pro Plan', 299.00, 1, '["Access to all tutorials", "Download videos", "Priority support"]'],
            ['premium', 'Premium Plan', 499.00, 1, '["All Pro features", "Live workshops", "1-on-1 sessions"]']
        ];
        
        $planStmt = $pdo->prepare("INSERT INTO subscription_plans (plan_code, plan_name, price, duration_months, features) VALUES (?, ?, ?, ?, ?)");
        foreach ($plans as $plan) {
            $planStmt->execute($plan);
        }
    }
    
    // Check for active subscription
    $stmt = $pdo->prepare("
        SELECT s.plan_code, s.subscription_status, s.is_active, s.created_at,
               sp.plan_name, sp.price, sp.duration_months, sp.features
        FROM subscriptions s
        JOIN subscription_plans sp ON s.plan_code = sp.plan_code
        WHERE s.email = ? AND s.is_active = 1
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userEmail]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($subscription) {
        // Parse features if it's JSON string
        if (is_string($subscription['features'])) {
            $subscription['features'] = json_decode($subscription['features'], true);
        }
        
        echo json_encode([
            'status' => 'success',
            'has_subscription' => true,
            'subscription' => $subscription
        ]);
    } else {
        // No active subscription - create a basic one
        try {
            $pdo->prepare("INSERT INTO subscriptions (email, plan_code, subscription_status, is_active) VALUES (?, 'basic', 'active', 1)")
                ->execute([$userEmail]);
        } catch (Exception $e) {
            // Ignore if already exists
        }
        
        // Return basic plan info
        $basicPlan = $pdo->query("SELECT * FROM subscription_plans WHERE plan_code = 'basic'")->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'has_subscription' => true,
            'subscription' => [
                'plan_code' => 'basic',
                'plan_name' => 'Basic Plan',
                'subscription_status' => 'active',
                'is_active' => 1,
                'price' => 0.00,
                'features' => ['Access to free tutorials']
            ]
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>