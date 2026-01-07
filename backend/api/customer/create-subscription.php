<?php
// Subscription creation endpoint with Razorpay integration for tutorials
// Creates Razorpay subscription and returns checkout details

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

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

// Read JSON body
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) { $input = []; }

$planCode = $input['plan_code'] ?? 'premium';

// Log the request for debugging
error_log('Create subscription request: ' . json_encode([
    'plan_code' => $planCode,
    'input' => $input,
    'headers' => [
        'X-Tutorial-Email' => $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? 'not set',
        'X-Tutorials-Email' => $_SERVER['HTTP_X_TUTORIALS_EMAIL'] ?? 'not set'
    ]
]));

try {
    $database = new Database();
    $db = $database->getConnection();

    // Ensure subscription tables exist (reuse logic from subscription-status)
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

        // Seed default plans if missing
        $planCount = (int)$db->query("SELECT COUNT(*) AS c FROM subscription_plans")->fetch(PDO::FETCH_ASSOC)['c'];
        if ($planCount === 0) {
            $plans = [
                ['basic', 'Basic', 'Perfect for getting started with craft tutorials', 199.00, 'monthly', json_encode(['Access to basic tutorials', 'Standard video quality', 'Community support', 'Mobile access'])],
                ['premium', 'Premium', 'Unlimited access to all tutorials', 499.00, 'monthly', json_encode(['Unlimited tutorial access', 'HD video quality', 'New content weekly', 'Priority support', 'Download videos'])],
                ['pro', 'Pro', 'Everything in Premium plus mentorship', 999.00, 'monthly', json_encode(['Everything in Premium', '1-on-1 mentorship', 'Live workshops', 'Certificate of completion', 'Early access to new content'])]
            ];
            $insertPlan = $db->prepare("INSERT INTO subscription_plans (plan_code, name, description, price, billing_period, features, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
            foreach ($plans as $p) { $insertPlan->execute($p); }
        }
    } catch (Exception $e) {
        error_log('Table creation error (create-subscription): ' . $e->getMessage());
    }

    // Identify user
    $userId = null;
    $email = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? $_SERVER['HTTP_X_TUTORIALS_EMAIL'] ?? null;
    
    error_log('User identification: email=' . ($email ?? 'null')); // Debug log
    
    if ($email) {
        $userStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $userStmt->execute([$email]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $userId = (int)$user['id'];
            error_log('Found existing user: ' . $userId); // Debug log
        } else {
            // Create user entry if missing - check which columns exist
            try {
                // First, check what columns exist in the users table
                $columnsStmt = $db->query("SHOW COLUMNS FROM users");
                $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
                
                $hasPassword = in_array('password', $columns);
                $hasPasswordHash = in_array('password_hash', $columns);
                $hasRole = in_array('role', $columns);
                $hasCreatedAt = in_array('created_at', $columns);
                
                error_log('Users table columns: ' . implode(', ', $columns)); // Debug log
                
                // Build insert query based on available columns
                $insertColumns = ['email'];
                $insertValues = ['?'];
                $insertParams = [$email];
                
                if ($hasPassword) {
                    $insertColumns[] = 'password';
                    $insertValues[] = '?';
                    $insertParams[] = '';
                } elseif ($hasPasswordHash) {
                    $insertColumns[] = 'password_hash';
                    $insertValues[] = '?';
                    $insertParams[] = null;
                }
                
                if ($hasRole) {
                    $insertColumns[] = 'role';
                    $insertValues[] = '?';
                    $insertParams[] = 'customer';
                }
                
                if ($hasCreatedAt) {
                    $insertColumns[] = 'created_at';
                    $insertValues[] = 'NOW()';
                } else {
                    // Check for created_date or similar
                    if (in_array('created_date', $columns)) {
                        $insertColumns[] = 'created_date';
                        $insertValues[] = 'NOW()';
                    }
                }
                
                $sql = "INSERT INTO users (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertValues) . ")";
                error_log('User insert SQL: ' . $sql); // Debug log
                
                $insertUser = $db->prepare($sql);
                $insertUser->execute($insertParams);
                $userId = (int)$db->lastInsertId();
                error_log('Created new user: ' . $userId); // Debug log
                
            } catch (Exception $e) {
                error_log('User creation error: ' . $e->getMessage());
                // Try a minimal insert
                try {
                    $insertUser = $db->prepare("INSERT INTO users (email) VALUES (?)");
                    $insertUser->execute([$email]);
                    $userId = (int)$db->lastInsertId();
                    error_log('Created user with minimal insert: ' . $userId); // Debug log
                } catch (Exception $e2) {
                    error_log('Minimal user creation also failed: ' . $e2->getMessage());
                    throw new Exception('Failed to create user account: ' . $e2->getMessage());
                }
            }
        }
    }
    if (!$userId && !empty($_SERVER['HTTP_X_USER_ID'])) {
        $userId = (int)$_SERVER['HTTP_X_USER_ID'];
        error_log('Using X-User-ID: ' . $userId); // Debug log
    }
    if (!$userId) {
        error_log('No user identity found'); // Debug log
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Missing user identity']);
        exit;
    }

    // Find requested plan
    $planStmt = $db->prepare("SELECT * FROM subscription_plans WHERE plan_code = ? AND is_active = 1 LIMIT 1");
    $planStmt->execute([$planCode]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);

    error_log('Plan lookup: ' . json_encode($plan)); // Debug log

    if (!$plan) {
        error_log('Plan not found: ' . $planCode); // Debug log
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid plan_code: ' . $planCode]);
        exit;
    }

    // Check existing active subscription
    $activeStmt = $db->prepare("
        SELECT s.*, sp.plan_code 
        FROM subscriptions s
        JOIN subscription_plans sp ON s.plan_id = sp.id
        WHERE s.user_id = ? 
          AND s.status IN ('active', 'authenticated')
          AND (s.current_end IS NULL OR s.current_end > NOW())
        ORDER BY s.id DESC LIMIT 1
    ");
    $activeStmt->execute([$userId]);
    $existing = $activeStmt->fetch(PDO::FETCH_ASSOC);

    error_log('Existing subscription check: ' . json_encode($existing)); // Debug log

    if ($existing && $existing['status'] === 'active') {
        // Check if user is trying to upgrade/downgrade or same plan
        if ($existing['plan_code'] === $planCode) {
            // Same plan - user already has this subscription
            error_log('User already has the same plan: ' . $planCode); // Debug log
            echo json_encode([
                'status' => 'success',
                'message' => 'You already have an active ' . $planCode . ' subscription',
                'subscription_id' => (int)$existing['id'],
                'plan_code' => $existing['plan_code'],
                'subscription_status' => 'active',
                'amount' => (float)$plan['price'] * 100,
                'currency' => $plan['currency'] ?? 'INR',
                'already_subscribed' => true
            ]);
            exit;
        } else {
            // Different plan - allow upgrade/downgrade by canceling existing and creating new
            error_log('User upgrading from ' . $existing['plan_code'] . ' to ' . $planCode); // Debug log
            
            // Mark existing subscription as cancelled
            $cancelStmt = $db->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE id = ?");
            $cancelStmt->execute([$existing['id']]);
            
            // Continue with creating new subscription
            error_log('Cancelled existing subscription, proceeding with new one'); // Debug log
        }
    }

    // For free plan, just activate without payment
    if ($planCode === 'free' && $plan['price'] == 0) {
        $currentStart = date('Y-m-d H:i:s');
        $currentEnd = date('Y-m-d H:i:s', strtotime('+365 days'));
        
        $insertSub = $db->prepare("
            INSERT INTO subscriptions (user_id, plan_id, razorpay_subscription_id, razorpay_plan_id, status, current_start, current_end, total_count, remaining_count, paid_count)
            VALUES (?, ?, NULL, NULL, 'active', ?, ?, 1, 0, 0)
        ");
        $insertSub->execute([$userId, $plan['id'], $currentStart, $currentEnd]);
        $subscriptionId = (int)$db->lastInsertId();

        echo json_encode([
            'status' => 'success',
            'message' => 'Free subscription activated',
            'subscription_id' => $subscriptionId,
            'plan_code' => $plan['plan_code'],
            'subscription_status' => 'active',
            'amount' => 0,
            'currency' => 'INR'
        ]);
        exit;
    }

    // For paid plans, create Razorpay subscription
    try {
        // Load Razorpay SDK
        $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        } else {
            throw new Exception('Composer autoload not found. Run: composer install in backend directory');
        }
        
        // Check if Razorpay SDK is available
        if (!class_exists('Razorpay\\Api\\Api')) {
            throw new Exception('Razorpay SDK not loaded after autoload. Run: composer require razorpay/razorpay');
        }

        error_log('Razorpay SDK loaded successfully'); // Debug log

        $razorpayClient = new Razorpay\Api\Api(RAZORPAY_KEY, RAZORPAY_SECRET);
        
        // For subscriptions, we'll use a simpler approach - create an order instead of subscription
        // This is more reliable for testing and works better with test credentials
        $billingPeriod = $input['billing_period'] ?? 'monthly';
        $planAmount = (float)$plan['price'] * 100; // Convert to paise
        
        error_log('Creating Razorpay order instead of subscription for better compatibility'); // Debug log
        
        // Create a simple order for the subscription amount
        $orderData = [
            'amount' => $planAmount,
            'currency' => 'INR',
            'receipt' => 'subscription_' . $planCode . '_' . $userId . '_' . time(),
            'notes' => [
                'user_id' => $userId,
                'plan_code' => $planCode,
                'email' => $email,
                'billing_period' => $billingPeriod,
                'subscription_type' => 'manual'
            ]
        ];
        
        error_log('Creating Razorpay order: ' . json_encode($orderData)); // Debug log
        
        $razorpayOrder = $razorpayClient->order->create($orderData);
        $razorpayOrderId = $razorpayOrder['id'];
        
        error_log('Created Razorpay order: ' . $razorpayOrderId); // Debug log
        
        // Save subscription to database with 'created' status (will be activated after payment)
        $insertSub = $db->prepare("
            INSERT INTO subscriptions (user_id, plan_id, razorpay_subscription_id, razorpay_plan_id, status, total_count, remaining_count, paid_count, notes)
            VALUES (?, ?, ?, NULL, 'created', 1, 1, 0, ?)
        ");
        
        $notes = json_encode([
            'razorpay_order_id' => $razorpayOrderId,
            'billing_period' => $billingPeriod,
            'amount' => $planAmount,
            'payment_type' => 'order'
        ]);
        
        $insertSub->execute([$userId, $plan['id'], $razorpayOrderId, $notes]);
        $subscriptionId = (int)$db->lastInsertId();

        error_log('Saved subscription to database: ' . $subscriptionId); // Debug log

        // Return order-based response instead of subscription-based
        echo json_encode([
            'status' => 'success',
            'message' => 'Payment order created successfully',
            'subscription_id' => $subscriptionId,
            'plan_code' => $plan['plan_code'],
            'subscription_status' => 'created',
            'amount' => $planAmount,
            'currency' => 'INR',
            'razorpay_order_id' => $razorpayOrderId,
            'payment_type' => 'order', // Indicate this is order-based, not subscription-based
            'razorpay_key' => RAZORPAY_KEY // Include key for frontend
        ]);
        exit;

    } catch (Exception $e) {
        error_log('Razorpay order creation error: ' . $e->getMessage());
        error_log('Error trace: ' . $e->getTraceAsString());
        
        // Try to get more specific error information
        $errorDetails = [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ];
        
        // Check if it's a Razorpay-specific error
        if (method_exists($e, 'getField')) {
            $errorDetails['razorpay_field'] = $e->getField();
        }
        if (method_exists($e, 'getDescription')) {
            $errorDetails['razorpay_description'] = $e->getDescription();
        }
        
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to create payment order: ' . $e->getMessage(),
            'error_details' => $errorDetails,
            'debug_info' => [
                'autoload_path' => $autoloadPath ?? 'not set',
                'autoload_exists' => file_exists($autoloadPath ?? ''),
                'razorpay_class_exists' => class_exists('Razorpay\\Api\\Api'),
                'razorpay_key_set' => defined('RAZORPAY_KEY') && !empty(RAZORPAY_KEY),
                'razorpay_secret_set' => defined('RAZORPAY_SECRET') && !empty(RAZORPAY_SECRET),
                'razorpay_key_preview' => defined('RAZORPAY_KEY') ? substr(RAZORPAY_KEY, 0, 15) . '...' : 'not set'
            ]
        ]);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal error creating subscription',
        'error' => $e->getMessage()
    ]);
    exit;
}


