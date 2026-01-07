<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $userEmail = $input['email'] ?? 'soudhame52@gmail.com';
    $planCode = $input['plan_code'] ?? 'pro';
    
    // Create tables if they don't exist
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
    
    // Insert plans if they don't exist
    $planCheck = $pdo->query("SELECT COUNT(*) as count FROM subscription_plans")->fetch();
    if ($planCheck['count'] == 0) {
        $plans = [
            ['basic', 'Basic Plan', 0.00, 1, '["Access to free tutorials", "Individual purchases"]'],
            ['premium', 'Premium Plan', 199.00, 1, '["Access to all tutorials", "HD video", "Download videos", "Priority support"]'],
            ['pro', 'Pro Plan', 299.00, 1, '["All Premium features", "Live workshops", "Practice uploads", "Certificates", "Mentorship"]']
        ];
        
        $planStmt = $pdo->prepare("INSERT INTO subscription_plans (plan_code, plan_name, price, duration_months, features) VALUES (?, ?, ?, ?, ?)");
        foreach ($plans as $plan) {
            $planStmt->execute($plan);
        }
    }
    
    // Delete existing subscription for this user
    $pdo->prepare("DELETE FROM subscriptions WHERE email = ?")->execute([$userEmail]);
    
    // Insert new subscription
    $pdo->prepare("INSERT INTO subscriptions (email, plan_code, subscription_status, is_active) VALUES (?, ?, 'active', 1)")
        ->execute([$userEmail, $planCode]);
    
    // Verify the subscription
    $stmt = $pdo->prepare("
        SELECT s.plan_code, s.subscription_status, s.is_active, s.created_at,
               sp.plan_name, sp.price, sp.features
        FROM subscriptions s
        JOIN subscription_plans sp ON s.plan_code = sp.plan_code
        WHERE s.email = ? AND s.is_active = 1
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userEmail]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'message' => "User set to $planCode subscription successfully",
        'user_email' => $userEmail,
        'plan_code' => $planCode,
        'subscription' => $subscription
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>