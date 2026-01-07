<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Only POST method allowed'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userEmail = $input['email'] ?? $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? '';
$planCode = $input['plan_code'] ?? '';

if (empty($userEmail) || empty($planCode)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email and plan_code are required'
    ]);
    exit;
}

try {
    // Get user ID
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $userStmt->execute([$userEmail]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found'
        ]);
        exit;
    }
    
    $userId = $user['id'];
    
    // Get plan details
    $planStmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE plan_code = ?");
    $planStmt->execute([$planCode]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid plan code'
        ]);
        exit;
    }
    
    // For Pro plan, require payment through Razorpay
    if ($planCode === 'pro') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Pro plan requires payment. Please use the payment flow.',
            'redirect_to_payment' => true
        ]);
        exit;
    }
    
    // Check if user already has an active subscription
    $existingSubStmt = $pdo->prepare("
        SELECT * FROM subscriptions 
        WHERE email = ? AND is_active = 1 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $existingSubStmt->execute([$userEmail]);
    $existingSub = $existingSubStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingSub) {
        // Update existing subscription
        $updateStmt = $pdo->prepare("
            UPDATE subscriptions 
            SET plan_code = ?, subscription_status = 'active', updated_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([$planCode, $existingSub['id']]);
    } else {
        // Create new subscription record
        $insertStmt = $pdo->prepare("
            INSERT INTO subscriptions (email, plan_code, subscription_status, is_active, created_at, updated_at)
            VALUES (?, ?, 'active', 1, NOW(), NOW())
        ");
        $insertStmt->execute([$userEmail, $planCode]);
    }
    
    // Log the subscription change
    error_log("Subscription updated: $userEmail -> $planCode");
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Subscription updated successfully',
        'plan_code' => $planCode,
        'plan_name' => $plan['plan_name'],
        'user_email' => $userEmail,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Subscription upgrade error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update subscription: ' . $e->getMessage()
    ]);
}
?>