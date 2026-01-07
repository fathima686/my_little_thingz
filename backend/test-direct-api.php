<?php
// Direct test of notifications API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing notifications API directly...\n\n";

// Simulate the API call
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_X_TUTORIAL_EMAIL'] = 'fathima470077@gmail.com';
$_GET['limit'] = 10;

// Capture output
ob_start();
include 'api/customer/notifications.php';
$output = ob_get_clean();

echo "API Response:\n";
echo $output . "\n";

// Test if it's valid JSON
$decoded = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "\n✓ Valid JSON response\n";
    echo "Status: " . ($decoded['status'] ?? 'unknown') . "\n";
    if (isset($decoded['notifications'])) {
        echo "Notifications count: " . count($decoded['notifications']) . "\n";
    }
} else {
    echo "\n✗ Invalid JSON response\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
}
?>