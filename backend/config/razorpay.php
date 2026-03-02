<?php
// Razorpay configuration
// Load environment variables from .env file

// Load .env file if it exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

$keyId = getenv('RAZORPAY_KEY_ID');
$keySecret = getenv('RAZORPAY_KEY_SECRET');

// If environment variables are not set, load from config file
if (!$keyId || !$keySecret) {
    $keysConfig = require __DIR__ . '/razorpay-keys.php';
    $mode = $keysConfig['mode'];
    
    $keyId = $keysConfig[$mode]['key_id'];
    $keySecret = $keysConfig[$mode]['key_secret'];
    
    // Check if keys are still placeholder values
    if (strpos($keyId, 'YOUR_KEY_ID_HERE') !== false || strpos($keySecret, 'YOUR_SECRET_KEY_HERE') !== false) {
        error_log('ERROR: Please update Razorpay API keys in backend/config/razorpay-keys.php');
        error_log('Get your keys from: https://dashboard.razorpay.com/app/keys');
        
        // For immediate testing, you can uncomment and use these demo keys:
        // Note: These may not work for actual payments, you need your own keys
        $keyId = 'rzp_test_1DP5mmOlF5G5ag';
        $keySecret = 'thisissecretkey';
        error_log('WARNING: Using demo Razorpay keys. Please get your own keys from https://dashboard.razorpay.com/app/keys');
    } else {
        error_log("INFO: Using Razorpay $mode mode keys");
    }
} else {
    error_log("INFO: Using Razorpay keys from environment variables");
}

$razorpay = [
    'key_id' => $keyId,
    'key_secret' => $keySecret,
    'currency' => 'INR',
];

return $razorpay;