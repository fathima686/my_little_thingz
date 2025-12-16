<?php
// Test script to verify Razorpay credentials are loading
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Razorpay Credentials Test</h2>";

// Test 1: Check .env file
echo "<h3>1. Checking .env file</h3>";
$envPath = __DIR__ . '/.env';
echo "Path: $envPath<br>";
echo "Exists: " . (file_exists($envPath) ? 'YES' : 'NO') . "<br>";
echo "Readable: " . (is_readable($envPath) ? 'YES' : 'NO') . "<br>";

if (file_exists($envPath)) {
    $content = file_get_contents($envPath);
    echo "<pre>" . htmlspecialchars($content) . "</pre>";
}

// Test 2: Try loading config
echo "<h3>2. Loading Razorpay Config</h3>";
try {
    require_once __DIR__ . '/config/razorpay-config.php';
    echo "✓ Config loaded successfully<br>";
    echo "RAZORPAY_KEY: " . (defined('RAZORPAY_KEY') ? RAZORPAY_KEY : 'NOT DEFINED') . "<br>";
    echo "RAZORPAY_SECRET: " . (defined('RAZORPAY_SECRET') ? substr(RAZORPAY_SECRET, 0, 10) . '...' : 'NOT DEFINED') . "<br>";
    
    // Test 3: Try creating Razorpay client
    echo "<h3>3. Testing Razorpay Client</h3>";
    if (class_exists('Razorpay\\Api\\Api')) {
        $razorpayClient = new Razorpay\Api\Api(RAZORPAY_KEY, RAZORPAY_SECRET);
        echo "✓ Razorpay client created successfully<br>";
        echo "Key ID matches: " . (RAZORPAY_KEY === 'rzp_test_RGXWGOBliVCIpU' ? 'YES' : 'NO') . "<br>";
    } else {
        echo "✗ Razorpay SDK class not found<br>";
    }
} catch (Throwable $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}







