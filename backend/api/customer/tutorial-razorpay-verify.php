<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID, X-Tutorial-Email, X-Tutorials-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }

    // Identify user by email or user_id
    $userId = null;
    $email = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? $_SERVER['HTTP_X_TUTORIALS_EMAIL'] ?? null;
    
    if ($email) {
        // Look up user by email
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

    if (!$razorpayPaymentId || !$razorpayOrderId || !$razorpaySignature) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing payment details']);
        exit;
    }

    // Verify signature
    require_once '../../config/razorpay-config.php';

    $body = $razorpayOrderId . '|' . $razorpayPaymentId;
    $expectedSignature = hash_hmac('sha256', $body, RAZORPAY_SECRET);

    if ($expectedSignature !== $razorpaySignature) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
        exit;
    }

    // Ensure tutorial_purchases table has razorpay columns
    try {
        $db->exec("ALTER TABLE tutorial_purchases ADD COLUMN IF NOT EXISTS razorpay_order_id VARCHAR(100)");
    } catch (Throwable $e) {}
    try {
        $db->exec("ALTER TABLE tutorial_purchases ADD COLUMN IF NOT EXISTS razorpay_payment_id VARCHAR(100)");
    } catch (Throwable $e) {}

    // Get tutorial from pending purchase (try with order_id first, then fallback to tutorial_id)
    $tutorialId = (int)($input['tutorial_id'] ?? 0);
    
    $stmt = $db->prepare("
        SELECT tutorial_id, amount_paid 
        FROM tutorial_purchases 
        WHERE user_id = ? AND tutorial_id = ? AND payment_status = 'pending'
        LIMIT 1
    ");
    $stmt->execute([$userId, $tutorialId]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    // If not found by tutorial_id, try by order_id
    if (!$purchase) {
        $stmt = $db->prepare("
            SELECT tutorial_id, amount_paid 
            FROM tutorial_purchases 
            WHERE user_id = ? AND razorpay_order_id = ? AND payment_status = 'pending'
            LIMIT 1
        ");
        $stmt->execute([$userId, $razorpayOrderId]);
        $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$purchase) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Purchase record not found']);
        exit;
    }

    $finalTutorialId = $purchase['tutorial_id'];

    // Update purchase status to completed with payment details
    $updateStmt = $db->prepare("
        UPDATE tutorial_purchases 
        SET payment_status = 'completed',
            razorpay_payment_id = ?,
            razorpay_order_id = ?
        WHERE user_id = ? AND tutorial_id = ?
    ");
    $updateStmt->execute([$razorpayPaymentId, $razorpayOrderId, $userId, $finalTutorialId]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Payment verified',
        'tutorial_id' => $purchase['tutorial_id']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error verifying payment: ' . $e->getMessage()
    ]);
}
