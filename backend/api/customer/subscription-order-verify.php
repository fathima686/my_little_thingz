<?php
// Verify order-based subscription payments
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID, X-Tutorial-Email, X-Tutorials-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config/database.php';
require_once '../../config/razorpay-config.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }

    // Identify user
    $userId = null;
    $email = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? $_SERVER['HTTP_X_TUTORIALS_EMAIL'] ?? null;
    
    if ($email) {
        $userStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $userStmt->execute([$email]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $userId = (int)$user['id'];
        }
    }
    
    if (!$userId && !empty($_SERVER['HTTP_X_USER_ID'])) {
        $userId = (int)$_SERVER['HTTP_X_USER_ID'];
    }

    if (!$userId) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Missing user identity']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    $razorpayPaymentId = $input['razorpay_payment_id'] ?? '';
    $razorpayOrderId = $input['razorpay_order_id'] ?? '';
    $razorpaySignature = $input['razorpay_signature'] ?? '';
    $subscriptionId = (int)($input['subscription_id'] ?? 0);
    $planCode = $input['plan_code'] ?? '';

    if (!$razorpayPaymentId || !$razorpayOrderId || !$razorpaySignature) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing payment details']);
        exit;
    }

    // Verify signature
    $body = $razorpayOrderId . '|' . $razorpayPaymentId;
    $expectedSignature = hash_hmac('sha256', $body, RAZORPAY_SECRET);

    if (!hash_equals($expectedSignature, $razorpaySignature)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
        exit;
    }

    // Get subscription from database
    $stmt = $db->prepare("
        SELECT s.*, sp.plan_code, sp.name as plan_name
        FROM subscriptions s
        JOIN subscription_plans sp ON s.plan_id = sp.id
        WHERE s.id = ? AND s.user_id = ?
    ");
    $stmt->execute([$subscriptionId, $userId]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$subscription) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Subscription not found']);
        exit;
    }

    // Update subscription status to active
    $updateStmt = $db->prepare("
        UPDATE subscriptions 
        SET status = 'active',
            current_start = NOW(),
            current_end = DATE_ADD(NOW(), INTERVAL 1 MONTH),
            paid_count = 1,
            remaining_count = 0,
            updated_at = NOW(),
            notes = JSON_SET(COALESCE(notes, '{}'), '$.razorpay_payment_id', ?, '$.payment_verified_at', NOW())
        WHERE id = ?
    ");
    
    $updateStmt->execute([$razorpayPaymentId, $subscriptionId]);

    // Log the successful payment
    error_log("Subscription payment verified: subscription_id=$subscriptionId, payment_id=$razorpayPaymentId, user_id=$userId");

    echo json_encode([
        'status' => 'success',
        'message' => 'Payment verified and subscription activated',
        'subscription_id' => $subscriptionId,
        'plan_code' => $subscription['plan_code'],
        'plan_name' => $subscription['plan_name'],
        'subscription_status' => 'active',
        'current_start' => date('Y-m-d H:i:s'),
        'current_end' => date('Y-m-d H:i:s', strtotime('+1 month'))
    ]);

} catch (Exception $e) {
    error_log('Subscription order verify error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error verifying payment: ' . $e->getMessage()
    ]);
}
?>