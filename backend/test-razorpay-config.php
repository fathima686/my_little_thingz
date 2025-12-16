<?php
// Quick test script to verify Razorpay configuration
// Access via: http://localhost/my_little_thingz/backend/test-razorpay-config.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Razorpay Configuration Test</h2>";

// Test 1: Check .env file
echo "<h3>1. Checking .env file</h3>";
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    echo "✓ .env file exists<br>";
    echo "<pre>" . htmlspecialchars(file_get_contents($envPath)) . "</pre>";
} else {
    echo "✗ .env file NOT found at: $envPath<br>";
    echo "Create it with:<br>";
    echo "<pre>RAZORPAY_KEY_ID=rzp_test_RGXWGOBliVCIpU<br>RAZORPAY_KEY_SECRET=9Q49llzcN0kLD3021OoSstOp</pre>";
}

// Test 2: Load config
echo "<h3>2. Loading Razorpay Config</h3>";
try {
    require_once __DIR__ . '/config/razorpay-config.php';
    echo "✓ Config loaded successfully<br>";
    echo "RAZORPAY_KEY: " . (defined('RAZORPAY_KEY') ? RAZORPAY_KEY : 'NOT DEFINED') . "<br>";
    echo "RAZORPAY_SECRET: " . (defined('RAZORPAY_SECRET') ? substr(RAZORPAY_SECRET, 0, 10) . '...' : 'NOT DEFINED') . "<br>";
} catch (Throwable $e) {
    echo "✗ Config load failed: " . $e->getMessage() . "<br>";
}

// Test 3: Check Razorpay SDK
echo "<h3>3. Checking Razorpay SDK</h3>";
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    echo "✓ vendor/autoload.php exists<br>";
    try {
        require_once $autoloadPath;
        if (class_exists('Razorpay\\Api\\Api')) {
            echo "✓ Razorpay SDK loaded successfully<br>";
        } else {
            echo "✗ Razorpay SDK class not found after autoload<br>";
        }
    } catch (Throwable $e) {
        echo "✗ Error loading SDK: " . $e->getMessage() . "<br>";
    }
} else {
    echo "✗ vendor/autoload.php NOT found<br>";
    echo "Run: <code>cd backend && composer require razorpay/razorpay</code><br>";
}

echo "<hr>";
echo "<p><strong>If all tests pass, your Razorpay setup is ready!</strong></p>";








