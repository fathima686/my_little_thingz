<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID, X-Tutorial-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Ensure subscription tables exist
    try {
        // Check if JSON type is supported, otherwise use TEXT
        $jsonType = 'TEXT'; // Default to TEXT for compatibility
        try {
            $versionCheck = $db->query("SELECT VERSION() as version");
            $version = $versionCheck->fetch(PDO::FETCH_ASSOC)['version'];
            // MySQL 5.7.8+ and MariaDB 10.2.7+ support JSON
            if (version_compare($version, '5.7.8', '>=') || (strpos($version, 'MariaDB') !== false && version_compare($version, '10.2.7', '>='))) {
                $jsonType = 'JSON';
            }
        } catch (Exception $e) {
            // Use TEXT if version check fails
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
        
        // Note: Unique constraint on razorpay_subscription_id is optional
        // We'll handle uniqueness in application logic if needed

        // Insert default plans if they don't exist
        $checkPlans = $db->query("SELECT COUNT(*) as count FROM subscription_plans");
        $planCount = $checkPlans->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($planCount == 0) {
            $plans = [
                ['free', 'Free', 'Limited access to free tutorials', 0.00, 'monthly', json_encode(['Limited free tutorials', 'Basic video quality', 'Community support'])],
                ['premium', 'Premium', 'Unlimited access to all tutorials', 499.00, 'monthly', json_encode(['Unlimited tutorial access', 'HD video quality', 'New content weekly', 'Priority support', 'Download videos'])],
                ['pro', 'Pro', 'Everything in Premium plus mentorship', 999.00, 'monthly', json_encode(['Everything in Premium', '1-on-1 mentorship', 'Live workshops', 'Certificate of completion', 'Early access to new content'])]
            ];
            
            $insertPlan = $db->prepare("INSERT INTO subscription_plans (plan_code, name, description, price, billing_period, features, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
            foreach ($plans as $plan) {
                $insertPlan->execute($plan);
            }
        }
    } catch (Exception $e) {
        error_log('Table creation error: ' . $e->getMessage());
    }

    // Identify user by email or user_id
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

    // Get active subscription
    try {
        $stmt = $db->prepare("
            SELECT 
                s.*,
                sp.plan_code,
                sp.name as plan_name,
                sp.price,
                sp.description,
                sp.features
            FROM subscriptions s
            JOIN subscription_plans sp ON s.plan_id = sp.id
            WHERE s.user_id = ? 
            AND s.status IN ('active', 'authenticated', 'pending')
            ORDER BY s.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // If query fails (table might not exist yet), treat as no subscription
        $subscription = null;
        error_log('Subscription query error: ' . $e->getMessage());
    }

    if (!$subscription) {
        // Return free plan as default
        try {
            $freePlanStmt = $db->prepare("SELECT * FROM subscription_plans WHERE plan_code = 'free' LIMIT 1");
            $freePlanStmt->execute();
            $freePlan = $freePlanStmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $freePlan = null;
        }

        echo json_encode([
            'status' => 'success',
            'has_subscription' => false,
            'plan_code' => 'free',
            'plan_name' => $freePlan['name'] ?? 'Free',
            'subscription_status' => 'free'
        ]);
        exit;
    }

    // Check if subscription is expired
    $isExpired = false;
    if ($subscription['current_end']) {
        $currentEnd = new DateTime($subscription['current_end']);
        $now = new DateTime();
        $isExpired = $currentEnd < $now;
    }

    if ($isExpired && $subscription['status'] !== 'cancelled') {
        // Update status to expired
        $updateStmt = $db->prepare("UPDATE subscriptions SET status = 'expired' WHERE id = ?");
        $updateStmt->execute([$subscription['id']]);
        $subscription['status'] = 'expired';
    }

    $features = json_decode($subscription['features'] ?? '[]', true);

    echo json_encode([
        'status' => 'success',
        'has_subscription' => true,
        'subscription_id' => $subscription['id'],
        'plan_code' => $subscription['plan_code'],
        'plan_name' => $subscription['plan_name'],
        'subscription_status' => $subscription['status'],
        'current_start' => $subscription['current_start'],
        'current_end' => $subscription['current_end'],
        'price' => $subscription['price'],
        'features' => $features,
        'is_active' => in_array($subscription['status'], ['active', 'authenticated'])
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    $errorMessage = $e->getMessage();
    $errorFile = $e->getFile();
    $errorLine = $e->getLine();
    
    error_log('Subscription status error: ' . $errorMessage . ' in ' . $errorFile . ':' . $errorLine);
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Error fetching subscription status: ' . $errorMessage,
        'file' => basename($errorFile),
        'line' => $errorLine,
        'type' => get_class($e)
    ]);
}

