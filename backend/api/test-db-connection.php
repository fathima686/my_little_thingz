<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID, X-Tutorial-Email, X-Tutorials-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $result = [
        'status' => 'success',
        'message' => 'Database connection successful',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Test users table
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $userCount = $stmt->fetch(PDO::FETCH_ASSOC);
        $result['users_count'] = $userCount['count'];
    } catch (Exception $e) {
        $result['users_table_error'] = $e->getMessage();
    }
    
    // Test subscription_plans table
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM subscription_plans");
        $planCount = $stmt->fetch(PDO::FETCH_ASSOC);
        $result['plans_count'] = $planCount['count'];
        
        // Get all plans
        $stmt = $db->query("SELECT plan_code, name, price FROM subscription_plans");
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result['plans'] = $plans;
    } catch (Exception $e) {
        $result['plans_table_error'] = $e->getMessage();
    }
    
    // Test subscriptions table
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM subscriptions");
        $subCount = $stmt->fetch(PDO::FETCH_ASSOC);
        $result['subscriptions_count'] = $subCount['count'];
    } catch (Exception $e) {
        $result['subscriptions_table_error'] = $e->getMessage();
    }
    
    // Test Razorpay config
    try {
        require_once '../config/razorpay-config.php';
        $result['razorpay_key_configured'] = defined('RAZORPAY_KEY') && !empty(RAZORPAY_KEY);
        $result['razorpay_secret_configured'] = defined('RAZORPAY_SECRET') && !empty(RAZORPAY_SECRET);
        if (defined('RAZORPAY_KEY')) {
            $result['razorpay_key_preview'] = substr(RAZORPAY_KEY, 0, 15) . '...';
        }
    } catch (Exception $e) {
        $result['razorpay_config_error'] = $e->getMessage();
    }
    
    // Test Razorpay SDK
    try {
        if (class_exists('Razorpay\\Api\\Api')) {
            $result['razorpay_sdk_loaded'] = true;
        } else {
            $result['razorpay_sdk_loaded'] = false;
        }
    } catch (Exception $e) {
        $result['razorpay_sdk_error'] = $e->getMessage();
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
?>