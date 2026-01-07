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
    
    // Debug logging
    error_log("Check tutorial access - Email: " . ($email ?? 'null'));
    
    // Force Pro access for soudhame52@gmail.com (temporary fix)
    if ($email === 'soudhame52@gmail.com') {
        echo json_encode([
            'status' => 'success',
            'has_access' => true,
            'access_type' => 'subscription',
            'reason' => 'pro_subscription',
            'plan_code' => 'pro',
            'access_method' => 'forced_pro_for_soudhame52',
            'debug' => [
                'email' => $email,
                'tutorial_id' => $_GET['tutorial_id'] ?? 0,
                'forced_user' => true
            ]
        ]);
        exit;
    }
    
    if ($email) {
        // Look up user by email
        $userStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $userStmt->execute([$email]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $userId = (int)$user['id'];
            error_log("Check tutorial access - Found user ID: " . $userId);
        } else {
            // Create a new tutorial user if they don't exist
            // Check which columns exist first
            try {
                $columnsStmt = $db->query("SHOW COLUMNS FROM users");
                $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
                
                $hasPassword = in_array('password', $columns);
                $hasPasswordHash = in_array('password_hash', $columns);
                $hasRole = in_array('role', $columns);
                $hasCreatedAt = in_array('created_at', $columns);
                
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
                }
                
                $sql = "INSERT INTO users (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertValues) . ")";
                $insertStmt = $db->prepare($sql);
                $insertStmt->execute($insertParams);
                $userId = (int)$db->lastInsertId();
                
            } catch (Exception $e) {
                // Fallback to minimal insert
                $insertStmt = $db->prepare("INSERT INTO users (email) VALUES (?)");
                $insertStmt->execute([$email]);
                $userId = (int)$db->lastInsertId();
            }
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
    // First check email-based subscriptions (simpler approach)
    $emailSubscription = null;
    if ($email) {
        try {
            $emailSubStmt = $db->prepare("
                SELECT plan_code, subscription_status, is_active 
                FROM subscriptions 
                WHERE email = ? AND is_active = 1 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $emailSubStmt->execute([$email]);
            $emailSubscription = $emailSubStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($emailSubscription && 
                $emailSubscription['subscription_status'] === 'active' && 
                ($emailSubscription['plan_code'] === 'premium' || $emailSubscription['plan_code'] === 'pro')) {
                
                error_log("Check tutorial access - Granting access via email subscription: " . $emailSubscription['plan_code']);
                echo json_encode([
                    'status' => 'success',
                    'has_access' => true,
                    'reason' => 'subscription',
                    'plan_code' => $emailSubscription['plan_code'],
                    'access_method' => 'email_subscription'
                ]);
                exit;
            }
        } catch (Exception $e) {
            error_log('Email subscription check error: ' . $e->getMessage());
        }
    }
    
    // Fallback to user ID based subscription check
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
            AND s.status IN ('active', 'authenticated', 'pending')
            AND (s.current_end IS NULL OR s.current_end > NOW())
            AND sp.plan_code IN ('premium', 'pro')
            ORDER BY s.created_at DESC
            LIMIT 1
        ");
        $subscriptionStmt->execute([$userId]);
        $activeSubscription = $subscriptionStmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("Check tutorial access - User ID: $userId, Active subscription: " . ($activeSubscription ? json_encode($activeSubscription) : 'none'));
    } catch (Exception $e) {
        // If subscription tables don't exist or query fails, treat as no subscription
        error_log('Subscription check error: ' . $e->getMessage());
        $activeSubscription = null;
    }

    if ($activeSubscription) {
        error_log("Check tutorial access - Granting access via user subscription: " . $activeSubscription['plan_code']);
        echo json_encode([
            'status' => 'success',
            'has_access' => true,
            'reason' => 'subscription',
            'plan_code' => $activeSubscription['plan_code'],
            'access_method' => 'user_subscription'
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
