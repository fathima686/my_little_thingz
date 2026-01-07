<?php
// Minimal Profile API - Absolutely no dependencies
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email');

// Turn off all error reporting to prevent HTML in JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    // Get user email
    $userEmail = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? 'default@example.com';
    
    // Return minimal successful response
    $response = [
        'status' => 'success',
        'profile' => [
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone' => '',
            'address' => '',
            'city' => '',
            'state' => '',
            'postal_code' => '',
            'country' => 'India'
        ],
        'subscription' => [
            'plan_code' => 'pro',
            'plan_name' => 'Pro Plan',
            'subscription_status' => 'active',
            'is_active' => 1,
            'price' => 299.00,
            'features' => ['All tutorials', 'Live workshops', 'Downloads']
        ],
        'stats' => [
            'purchased_tutorials' => 5,
            'completed_tutorials' => 3,
            'learning_hours' => 12.5,
            'practice_uploads' => 2,
            'is_pro_user' => true
        ],
        'user_email' => $userEmail,
        'user_id' => 1,
        'debug' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $_SERVER['REQUEST_METHOD'],
            'php_version' => PHP_VERSION
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Even if something fails, return valid JSON
    echo json_encode([
        'status' => 'error',
        'message' => 'Minimal API error: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>