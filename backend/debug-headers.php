<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Email, X-Admin-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Debug: Show all headers received
$headers = [];
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        $headers[$key] = $value;
    }
}

echo json_encode([
    'status' => 'success',
    'message' => 'Headers debug',
    'all_headers' => $headers,
    'specific_checks' => [
        'HTTP_X_ADMIN_EMAIL' => $_SERVER['HTTP_X_ADMIN_EMAIL'] ?? 'NOT_FOUND',
        'HTTP_X_ADMIN_USER_ID' => $_SERVER['HTTP_X_ADMIN_USER_ID'] ?? 'NOT_FOUND',
        'HTTP_X_ADMIN_USER_ID_ALT' => $_SERVER['HTTP_X_ADMIN_USER_ID'] ?? 'NOT_FOUND'
    ]
]);
?>