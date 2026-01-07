<?php
// Test the API using cURL to simulate actual HTTP request
echo "Testing notifications API via HTTP...\n\n";

$url = 'http://localhost/my_little_thingz/backend/api/customer/notifications.php?limit=10';
$headers = [
    'X-Tutorial-Email: fathima470077@gmail.com',
    'Content-Type: application/json'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}

echo "Response:\n";
echo $response . "\n\n";

// Test if it's valid JSON
$decoded = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "✓ Valid JSON response\n";
    echo "Status: " . ($decoded['status'] ?? 'unknown') . "\n";
    if (isset($decoded['notifications'])) {
        echo "Notifications count: " . count($decoded['notifications']) . "\n";
    }
} else {
    echo "✗ Invalid JSON response\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
    echo "First 200 chars of response: " . substr($response, 0, 200) . "\n";
}
?>