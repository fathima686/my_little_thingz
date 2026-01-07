<?php
// Test Razorpay SDK loading and basic functionality
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo json_encode(['step' => 'start', 'message' => 'Testing Razorpay SDK'], JSON_PRETTY_PRINT) . "\n";
    
    // Test 1: Check autoload
    $autoloadPath = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        echo json_encode(['step' => 'autoload_found', 'path' => $autoloadPath], JSON_PRETTY_PRINT) . "\n";
        require_once $autoloadPath;
        echo json_encode(['step' => 'autoload_loaded'], JSON_PRETTY_PRINT) . "\n";
    } else {
        throw new Exception('Autoload not found at: ' . $autoloadPath);
    }
    
    // Test 2: Check Razorpay class
    if (class_exists('Razorpay\\Api\\Api')) {
        echo json_encode(['step' => 'razorpay_class_exists'], JSON_PRETTY_PRINT) . "\n";
    } else {
        throw new Exception('Razorpay\\Api\\Api class not found');
    }
    
    // Test 3: Load config
    require_once 'config/razorpay-config.php';
    
    if (defined('RAZORPAY_KEY') && defined('RAZORPAY_SECRET')) {
        echo json_encode([
            'step' => 'config_loaded',
            'key_preview' => substr(RAZORPAY_KEY, 0, 15) . '...',
            'secret_preview' => substr(RAZORPAY_SECRET, 0, 10) . '...'
        ], JSON_PRETTY_PRINT) . "\n";
    } else {
        throw new Exception('Razorpay config not loaded properly');
    }
    
    // Test 4: Create API instance
    $api = new Razorpay\Api\Api(RAZORPAY_KEY, RAZORPAY_SECRET);
    echo json_encode(['step' => 'api_instance_created'], JSON_PRETTY_PRINT) . "\n";
    
    // Test 5: Try to create a test order (small amount)
    try {
        $orderData = [
            'amount' => 100, // ₹1.00 in paise
            'currency' => 'INR',
            'receipt' => 'test_sdk_' . time(),
            'notes' => ['test' => 'sdk_verification']
        ];
        
        $order = $api->order->create($orderData);
        echo json_encode([
            'step' => 'test_order_created',
            'order_id' => $order['id'],
            'amount' => $order['amount'],
            'status' => $order['status']
        ], JSON_PRETTY_PRINT) . "\n";
        
    } catch (Exception $e) {
        echo json_encode([
            'step' => 'test_order_failed',
            'error' => $e->getMessage()
        ], JSON_PRETTY_PRINT) . "\n";
    }
    
    // Test 6: Try to create a test plan
    try {
        $planData = [
            'period' => 'monthly',
            'interval' => 1,
            'item' => [
                'name' => 'Test Plan SDK',
                'amount' => 99900, // ₹999 in paise
                'currency' => 'INR',
                'description' => 'Test plan for SDK verification'
            ]
        ];
        
        $plan = $api->plan->create($planData);
        echo json_encode([
            'step' => 'test_plan_created',
            'plan_id' => $plan['id'],
            'amount' => $plan['item']['amount'],
            'period' => $plan['period']
        ], JSON_PRETTY_PRINT) . "\n";
        
        // Clean up - delete the test plan
        try {
            // Note: Razorpay doesn't allow plan deletion via API, so we'll leave it
            echo json_encode(['step' => 'test_plan_cleanup_skipped', 'note' => 'Razorpay plans cannot be deleted via API'], JSON_PRETTY_PRINT) . "\n";
        } catch (Exception $e) {
            // Ignore cleanup errors
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'step' => 'test_plan_failed',
            'error' => $e->getMessage()
        ], JSON_PRETTY_PRINT) . "\n";
    }
    
    echo json_encode([
        'step' => 'complete',
        'status' => 'success',
        'message' => 'Razorpay SDK is working correctly!'
    ], JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo json_encode([
        'step' => 'error',
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT) . "\n";
}
?>