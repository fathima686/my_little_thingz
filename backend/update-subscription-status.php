<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config/database.php';

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $email = $input['email'] ?? '';
    $planCode = $input['plan_code'] ?? 'basic';
    $isActive = $input['is_active'] ?? 1;
    $subscriptionStatus = $input['subscription_status'] ?? 'active';
    
    if (empty($email)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Email is required']);
        exit;
    }
    
    // Check if subscription exists
    $stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE email = ? AND plan_code = ?");
    $stmt->execute([$email, $planCode]);
    $existingSubscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingSubscription) {
        // Update existing subscription
        $stmt = $pdo->prepare("
            UPDATE subscriptions 
            SET is_active = ?, subscription_status = ?, updated_at = NOW()
            WHERE email = ? AND plan_code = ?
        ");
        $stmt->execute([$isActive, $subscriptionStatus, $email, $planCode]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Subscription updated successfully',
            'action' => 'updated'
        ]);
    } else {
        // Create new subscription
        $stmt = $pdo->prepare("
            INSERT INTO subscriptions 
            (email, plan_code, subscription_status, is_active, created_at, updated_at) 
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$email, $planCode, $subscriptionStatus, $isActive]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Subscription created successfully',
            'action' => 'created'
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>