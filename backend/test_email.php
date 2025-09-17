<?php
// Test script for email functionality
require_once 'includes/SimpleEmailSender.php';

// Test data (recipient can be provided via CLI arg or ?to= query param)
$to = null;
if (PHP_SAPI === 'cli' && !empty($argv[1])) { $to = $argv[1]; }
if (!$to && isset($_GET['to']) && $_GET['to']) { $to = $_GET['to']; }
if (!$to) { $to = 'test@example.com'; }

$test_user = [
    'email' => $to,
    'first_name' => 'John',
    'last_name' => 'Doe'
];

$test_order = [
    'order_number' => 'TEST-ORDER-001',
    'total_amount' => 150.00,
    'subtotal' => 120.00,
    'tax_amount' => 18.00,
    'shipping_cost' => 12.00,
    'shipping_address' => '123 Test Street, Test City, Test State 12345',
    'created_at' => date('Y-m-d H:i:s'),
    'items' => [
        [
            'artwork_name' => 'Test Artwork 1',
            'quantity' => 2,
            'price' => 50.00
        ],
        [
            'artwork_name' => 'Test Artwork 2',
            'quantity' => 1,
            'price' => 20.00
        ]
    ]
];

echo "Testing email functionality...\n";

try {
    $emailSender = new SimpleEmailSender();
    $fullName = trim($test_user['first_name'] . ' ' . $test_user['last_name']);
    
    // Test payment success email
    echo "Sending payment success email...\n";
    $result = $emailSender->sendPaymentSuccessEmail(
        $test_user['email'],
        $fullName,
        $test_order
    );
    
    if ($result) {
        echo "✅ Payment success email sent successfully!\n";
    } else {
        echo "❌ Payment success email failed to send.\n";
    }
    
    // Test payment failure email
    echo "Sending payment failure email...\n";
    $result = $emailSender->sendPaymentFailureEmail(
        $test_user['email'],
        $fullName,
        $test_order,
        'Test payment failure'
    );
    
    if ($result) {
        echo "✅ Payment failure email sent successfully!\n";
    } else {
        echo "❌ Payment failure email failed to send.\n";
    }
    
    echo "\nCheck the email_log.txt file for detailed email logs.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>

