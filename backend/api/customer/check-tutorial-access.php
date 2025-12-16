<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID, X-Tutorials-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }

    // Identify user by email or user_id
    $userId = null;
    $email = $_GET['email'] ?? $_SERVER['HTTP_X_TUTORIALS_EMAIL'] ?? null;
    
    if ($email) {
        // Look up user by email
        $userStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $userStmt->execute([$email]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $userId = (int)$user['id'];
        } else {
            // Create a new tutorial user if they don't exist
            $insertStmt = $db->prepare("INSERT INTO users (email, password, role, created_at) VALUES (?, '', 'customer', NOW())");
            $insertStmt->execute([$email]);
            $userId = (int)$db->lastInsertId();
        }
    }
    
    if (!$userId) {
        if (!empty($_SERVER['HTTP_X_USER_ID'])) {
            $userId = (int)$_SERVER['HTTP_X_USER_ID'];
        } elseif (!empty($_GET['user_id'])) {
            $userId = (int)$_GET['user_id'];
        }
    }

    if (!$userId) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Missing user identity']);
        exit;
    }

    $tutorialId = (int)($_GET['tutorial_id'] ?? 0);

    if (!$tutorialId) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing tutorial_id']);
        exit;
    }

    // Check if tutorial is free
    $tutorialStmt = $db->prepare("
        SELECT is_free, price FROM tutorials WHERE id = ?
    ");
    $tutorialStmt->execute([$tutorialId]);
    $tutorial = $tutorialStmt->fetch(PDO::FETCH_ASSOC);

    if (!$tutorial) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Tutorial not found']);
        exit;
    }

    // If free or free price, always grant access
    if ($tutorial['is_free'] || $tutorial['price'] == 0) {
        echo json_encode([
            'status' => 'success',
            'has_access' => true,
            'reason' => 'free'
        ]);
        exit;
    }

    // Check if user has active subscription (premium or pro)
    // First ensure tables exist
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
        error_log('Table creation error in check-tutorial-access: ' . $e->getMessage());
    }

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
            AND sp.plan_code IN ('premium', 'pro')
            LIMIT 1
        ");
        $subscriptionStmt->execute([$userId]);
        $activeSubscription = $subscriptionStmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // If subscription tables don't exist or query fails, treat as no subscription
        error_log('Subscription check error: ' . $e->getMessage());
        $activeSubscription = null;
    }

    if ($activeSubscription) {
        echo json_encode([
            'status' => 'success',
            'has_access' => true,
            'reason' => 'subscription',
            'plan_code' => $activeSubscription['plan_code']
        ]);
        exit;
    }

    // Check if user has purchased
    $checkStmt = $db->prepare("
        SELECT id FROM tutorial_purchases 
        WHERE user_id = ? AND tutorial_id = ? AND payment_status = 'completed'
    ");
    $checkStmt->execute([$userId, $tutorialId]);
    $purchase = $checkStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'has_access' => (bool)$purchase,
        'reason' => $purchase ? 'purchased' : 'not_purchased'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error checking access: ' . $e->getMessage()
    ]);
}
