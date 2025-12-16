<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID, X-Tutorial-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

require_once '../../config/database.php';
require_once '../../config/razorpay-config.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Ensure subscription tables exist
    try {
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

        $db->exec("CREATE TABLE IF NOT EXISTS subscription_invoices (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            subscription_id INT UNSIGNED NOT NULL,
            razorpay_invoice_id VARCHAR(100),
            razorpay_payment_id VARCHAR(100),
            invoice_number VARCHAR(50),
            amount DECIMAL(10, 2) NOT NULL,
            currency VARCHAR(10) DEFAULT 'INR',
            status ENUM('issued', 'paid', 'partially_paid', 'cancelled', 'expired') DEFAULT 'issued',
            invoice_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            due_date TIMESTAMP NULL,
            paid_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_subscription (subscription_id),
            INDEX idx_status (status),
            INDEX idx_razorpay_invoice_id (razorpay_invoice_id)
        )");
    } catch (Exception $e) {
        error_log('Table creation error in subscription-verify: ' . $e->getMessage());
    }

    // Identify user
    $userId = null;
    $email = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? null;
    
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
    $razorpaySubscriptionId = $input['razorpay_subscription_id'] ?? '';
    $razorpayPaymentId = $input['razorpay_payment_id'] ?? '';
    $razorpaySignature = $input['razorpay_signature'] ?? '';

    if (!$razorpaySubscriptionId || !$razorpayPaymentId || !$razorpaySignature) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing verification parameters']);
        exit;
    }

    // Verify signature
    $body = $razorpaySubscriptionId . '|' . $razorpayPaymentId;
    $expectedSignature = hash_hmac('sha256', $body, RAZORPAY_SECRET);

    if (!hash_equals($expectedSignature, $razorpaySignature)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
        exit;
    }

    // Get subscription from database
    $stmt = $db->prepare("
        SELECT * FROM subscriptions 
        WHERE razorpay_subscription_id = ? AND user_id = ?
    ");
    $stmt->execute([$razorpaySubscriptionId, $userId]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$subscription) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Subscription not found']);
        exit;
    }

    // Fetch subscription details from Razorpay
    if (!class_exists('Razorpay\\Api\\Api')) {
        $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }
    }

    if (!class_exists('Razorpay\\Api\\Api')) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Razorpay SDK not installed']);
        exit;
    }

    try {
        $razorpayClient = new Razorpay\Api\Api(RAZORPAY_KEY, RAZORPAY_SECRET);
        $razorpaySubscription = $razorpayClient->subscription->fetch($razorpaySubscriptionId);

        // Update subscription status
        $updateStmt = $db->prepare("
            UPDATE subscriptions 
            SET status = ?, 
                current_start = FROM_UNIXTIME(?),
                current_end = FROM_UNIXTIME(?),
                paid_count = ?,
                remaining_count = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $currentStart = $razorpaySubscription['current_start'] ?? null;
        $currentEnd = $razorpaySubscription['current_end'] ?? null;
        $paidCount = $razorpaySubscription['paid_count'] ?? 0;
        $remainingCount = $razorpaySubscription['remaining_count'] ?? null;

        $updateStmt->execute([
            $razorpaySubscription['status'],
            $currentStart,
            $currentEnd,
            $paidCount,
            $remainingCount,
            $subscription['id']
        ]);

        // Create invoice record if payment exists
        if ($razorpayPaymentId) {
            try {
                $invoiceStmt = $db->prepare("
                    INSERT INTO subscription_invoices 
                    (subscription_id, razorpay_payment_id, amount, currency, status, paid_at)
                    VALUES (?, ?, ?, ?, 'paid', NOW())
                    ON DUPLICATE KEY UPDATE 
                        status = 'paid',
                        paid_at = NOW(),
                        updated_at = NOW()
                ");
                
                $planStmt = $db->prepare("SELECT price, currency FROM subscription_plans WHERE id = ?");
                $planStmt->execute([$subscription['plan_id']]);
                $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
                
                $invoiceStmt->execute([
                    $subscription['id'],
                    $razorpayPaymentId,
                    $plan['price'] ?? 0,
                    $plan['currency'] ?? 'INR'
                ]);
            } catch (Throwable $e) {
                error_log('Invoice creation error: ' . $e->getMessage());
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Subscription verified and activated',
            'subscription_status' => $razorpaySubscription['status'],
            'current_start' => $currentStart ? date('Y-m-d H:i:s', $currentStart) : null,
            'current_end' => $currentEnd ? date('Y-m-d H:i:s', $currentEnd) : null
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to verify subscription: ' . $e->getMessage()
        ]);
        exit;
    }

} catch (Throwable $e) {
    http_response_code(500);
    error_log('Subscription verify error: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Error verifying subscription: ' . $e->getMessage()
    ]);
}

