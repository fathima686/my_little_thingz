<?php
// Debug endpoint to test tutorial purchase setup
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$debug = [];

// Test 1: Check .env file
$envPath = __DIR__ . '/../../.env';
$debug['env_file'] = [
    'path' => $envPath,
    'exists' => file_exists($envPath),
    'readable' => file_exists($envPath) ? is_readable($envPath) : false
];

if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    $debug['env_file']['content'] = $envContent;
    $debug['env_file']['lines'] = explode("\n", $envContent);
}

// Test 2: Check environment variables
$debug['env_vars'] = [
    'RAZORPAY_KEY_ID' => getenv('RAZORPAY_KEY_ID') ?: 'NOT SET',
    'RAZORPAY_KEY_SECRET' => getenv('RAZORPAY_KEY_SECRET') ? substr(getenv('RAZORPAY_KEY_SECRET'), 0, 10) . '...' : 'NOT SET'
];

// Test 3: Try loading config
try {
    require_once __DIR__ . '/../../config/razorpay-config.php';
    $debug['config'] = [
        'loaded' => true,
        'RAZORPAY_KEY' => defined('RAZORPAY_KEY') ? RAZORPAY_KEY : 'NOT DEFINED',
        'RAZORPAY_SECRET' => defined('RAZORPAY_SECRET') ? substr(RAZORPAY_SECRET, 0, 10) . '...' : 'NOT DEFINED'
    ];
} catch (Throwable $e) {
    $debug['config'] = [
        'loaded' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
}

// Test 4: Check Razorpay SDK
$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
$debug['sdk'] = [
    'autoload_exists' => file_exists($autoloadPath),
    'class_exists' => false
];

if (file_exists($autoloadPath)) {
    try {
        require_once $autoloadPath;
        $debug['sdk']['class_exists'] = class_exists('Razorpay\\Api\\Api');
    } catch (Throwable $e) {
        $debug['sdk']['error'] = $e->getMessage();
    }
}

// Test 5: Check database
try {
    require_once __DIR__ . '/../../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    $debug['database'] = ['connected' => true];
} catch (Throwable $e) {
    $debug['database'] = [
        'connected' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($debug, JSON_PRETTY_PRINT);







