<?php
// Enable error reporting for debugging
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

// Check method early
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Method not allowed',
        'received_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
    ]);
    exit;
}

// Get request body early to help with debugging
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

// Check for JSON decode errors
if (json_last_error() !== JSON_ERROR_NONE && $rawInput !== '') {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid JSON in request body: ' . json_last_error_msg(),
        'raw_input' => substr($rawInput, 0, 200)
    ]);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Request body already loaded above

    // Identify user by email or user_id
    $userId = null;
    $email = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? null;
    
    if ($email) {
        // Look up user by email
        $userStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $userStmt->execute([$email]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $userId = (int)$user['id'];
        } else {
            // Create a new tutorial user if they don't exist
            // Check which password column exists
            $hasPasswordHash = false;
            $hasPassword = false;
            $hasRole = false;
            try {
                $colCheck = $db->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
                $hasPasswordHash = $colCheck && $colCheck->rowCount() > 0;
            } catch (Throwable $e) {}
            try {
                $colCheck = $db->query("SHOW COLUMNS FROM users LIKE 'password'");
                $hasPassword = $colCheck && $colCheck->rowCount() > 0;
            } catch (Throwable $e) {}
            try {
                $colCheck = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
                $hasRole = $colCheck && $colCheck->rowCount() > 0;
            } catch (Throwable $e) {}
            
            if ($hasRole) {
                if ($hasPasswordHash) {
                    $insertStmt = $db->prepare("INSERT INTO users (email, password_hash, role, created_at) VALUES (?, NULL, 'customer', NOW())");
                } else {
                    $insertStmt = $db->prepare("INSERT INTO users (email, password, role, created_at) VALUES (?, '', 'customer', NOW())");
                }
            } else {
                if ($hasPasswordHash) {
                    $insertStmt = $db->prepare("INSERT INTO users (email, password_hash, created_at) VALUES (?, NULL, NOW())");
                } else {
                    $insertStmt = $db->prepare("INSERT INTO users (email, password, created_at) VALUES (?, '', NOW())");
                }
            }
            $insertStmt->execute([$email]);
            $userId = (int)$db->lastInsertId();
            
            // Assign customer role if using user_roles table
            if (!$hasRole) {
                try {
                    $roleStmt = $db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, 2) ON DUPLICATE KEY UPDATE role_id=2");
                    $roleStmt->execute([$userId]);
                } catch (Throwable $e) {
                    // Ignore if table doesn't exist
                }
            }
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

    $tutorialId = (int)($input['tutorial_id'] ?? 0);
    $paymentMethod = $input['payment_method'] ?? 'razorpay';

    if (!$tutorialId) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing tutorial_id']);
        exit;
    }

    // Check if tutorial exists
    $tutorialStmt = $db->prepare("SELECT id, price, is_free, title FROM tutorials WHERE id = ?");
    $tutorialStmt->execute([$tutorialId]);
    $tutorial = $tutorialStmt->fetch(PDO::FETCH_ASSOC);

    if (!$tutorial) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Tutorial not found']);
        exit;
    }

    // Check if already purchased (completed)
    $checkStmt = $db->prepare("
        SELECT id FROM tutorial_purchases 
        WHERE user_id = ? AND tutorial_id = ? AND payment_status = 'completed'
    ");
    $checkStmt->execute([$userId, $tutorialId]);
    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Tutorial already purchased']);
        exit;
    }

    // Check if there's a pending or failed purchase (we can reuse/update it)
    $pendingStmt = $db->prepare("
        SELECT id, razorpay_order_id FROM tutorial_purchases 
        WHERE user_id = ? AND tutorial_id = ? AND payment_status IN ('pending', 'failed')
    ");
    $pendingStmt->execute([$userId, $tutorialId]);
    $pendingPurchase = $pendingStmt->fetch(PDO::FETCH_ASSOC);

    // If free tutorial, instantly create purchase record
    if ($tutorial['is_free'] || $tutorial['price'] == 0) {
        $insertStmt = $db->prepare("
            INSERT INTO tutorial_purchases 
            (user_id, tutorial_id, payment_method, payment_status, amount_paid) 
            VALUES (?, ?, ?, 'completed', 0)
        ");
        $insertStmt->execute([$userId, $tutorialId, 'free']);

        echo json_encode([
            'status' => 'success',
            'message' => 'Tutorial access granted',
            'tutorial_id' => $tutorialId
        ]);
        exit;
    }

    // For paid tutorials, initiate payment
    if ($paymentMethod === 'razorpay') {
        // Ensure razorpay columns exist (check first to avoid errors)
        try {
            $colCheck = $db->query("SHOW COLUMNS FROM tutorial_purchases LIKE 'razorpay_order_id'");
            if (!$colCheck || $colCheck->rowCount() == 0) {
                $db->exec("ALTER TABLE tutorial_purchases ADD COLUMN razorpay_order_id VARCHAR(100)");
            }
        } catch (Throwable $e) {}
        try {
            $colCheck = $db->query("SHOW COLUMNS FROM tutorial_purchases LIKE 'razorpay_payment_id'");
            if (!$colCheck || $colCheck->rowCount() == 0) {
                $db->exec("ALTER TABLE tutorial_purchases ADD COLUMN razorpay_payment_id VARCHAR(100)");
            }
        } catch (Throwable $e) {}
        
        // Create Razorpay order first
        try {
            require_once '../../config/razorpay-config.php';
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Payment configuration error: ' . $e->getMessage()
            ]);
            exit;
        }

        // Ensure Razorpay SDK is available
        try {
            if (!class_exists('Razorpay\\Api\\Api')) {
                $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
                if (file_exists($autoloadPath)) {
                    require_once $autoloadPath;
                }
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Razorpay SDK load error: ' . $e->getMessage()
            ]);
            exit;
        }

        if (!class_exists('Razorpay\\Api\\Api')) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Razorpay SDK not installed. Run composer require razorpay/razorpay in backend/.'
            ]);
            exit;
        }

        try {
            $razorpayClient = new Razorpay\Api\Api(RAZORPAY_KEY, RAZORPAY_SECRET);

            $orderData = [
                'amount' => (int)($tutorial['price'] * 100), // Amount in paise
                'currency' => 'INR',
                'receipt' => 'tutorial_' . $tutorialId . '_' . $userId . '_' . time(),
                'notes' => [
                    'tutorial_id' => $tutorialId,
                    'user_id' => $userId,
                    'tutorial_title' => $tutorial['title']
                ]
            ];

            $razorpayOrder = $razorpayClient->order->create($orderData);
            $razorpayOrderId = $razorpayOrder['id'];

            // Check if there's already a pending or failed purchase for this user+tutorial
            if ($pendingPurchase) {
                // Update existing pending/failed purchase with new order_id and set status to pending
                $updateStmt = $db->prepare("
                    UPDATE tutorial_purchases 
                    SET razorpay_order_id = ?, amount_paid = ?, payment_method = 'razorpay', payment_status = 'pending'
                    WHERE id = ?
                ");
                $updateStmt->execute([$razorpayOrderId, $tutorial['price'], $pendingPurchase['id']]);
            } else {
                // Create a new pending purchase record with order_id
                $insertStmt = $db->prepare("
                    INSERT INTO tutorial_purchases 
                    (user_id, tutorial_id, payment_method, payment_status, amount_paid, razorpay_order_id) 
                    VALUES (?, ?, ?, 'pending', ?, ?)
                ");
                $insertStmt->execute([$userId, $tutorialId, 'razorpay', $tutorial['price'], $razorpayOrderId]);
            }

            echo json_encode([
                'status' => 'success',
                'message' => 'Payment order created',
                'razorpay_order_id' => $razorpayOrderId,
                'amount' => $razorpayOrder['amount'],
                'currency' => $razorpayOrder['currency']
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to create payment order: ' . $e->getMessage()
            ]);
            exit;
        }
    } elseif ($paymentMethod === 'subscription') {
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
        } catch (Exception $e) {
            error_log('Table creation error in purchase-tutorial: ' . $e->getMessage());
        }

        // Handle subscription - redirect to subscription creation
        // First check if user has active subscription
        $subscriptionStmt = null;
        $activeSubscription = null;
        
        try {
            $subscriptionStmt = $db->prepare("
                SELECT s.*, sp.plan_code 
                FROM subscriptions s
                JOIN subscription_plans sp ON s.plan_id = sp.id
                WHERE s.user_id = ? 
                AND s.status IN ('active', 'authenticated')
                AND (s.current_end IS NULL OR s.current_end > NOW())
                LIMIT 1
            ");
            $subscriptionStmt->execute([$userId]);
            $activeSubscription = $subscriptionStmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Subscription check error in purchase-tutorial: ' . $e->getMessage());
            $activeSubscription = null;
        }

        if ($activeSubscription && in_array($activeSubscription['plan_code'], ['premium', 'pro'])) {
            // User has active subscription, grant access
            $checkPurchaseStmt = $db->prepare("
                SELECT id FROM tutorial_purchases 
                WHERE user_id = ? AND tutorial_id = ? AND payment_status = 'completed'
            ");
            $checkPurchaseStmt->execute([$userId, $tutorialId]);
            
            if (!$checkPurchaseStmt->fetch()) {
                $insertStmt = $db->prepare("
                    INSERT INTO tutorial_purchases 
                    (user_id, tutorial_id, payment_method, payment_status, amount_paid) 
                    VALUES (?, ?, 'subscription', 'completed', 0)
                ");
                $insertStmt->execute([$userId, $tutorialId]);
            }

            echo json_encode([
                'status' => 'success',
                'message' => 'Tutorial access granted via subscription',
                'tutorial_id' => $tutorialId,
                'subscription_active' => true
            ]);
        } else {
            // No active subscription, return info to create one
            echo json_encode([
                'status' => 'info',
                'message' => 'Please subscribe to access this tutorial',
                'tutorial_id' => $tutorialId,
                'requires_subscription' => true
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid payment method']);
    }

} catch (Throwable $e) {
    http_response_code(500);
    $errorMessage = $e->getMessage();
    $errorFile = $e->getFile();
    $errorLine = $e->getLine();
    
    error_log('Purchase tutorial error: ' . $errorMessage . ' in ' . $errorFile . ':' . $errorLine);
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Return detailed error for debugging
    echo json_encode([
        'status' => 'error',
        'message' => 'Error processing purchase: ' . $errorMessage,
        'file' => basename($errorFile),
        'line' => $errorLine,
        'type' => get_class($e),
        'trace' => explode("\n", $e->getTraceAsString())
    ], JSON_PRETTY_PRINT);
}
