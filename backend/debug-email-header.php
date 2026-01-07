<?php
header('Content-Type: application/json');

// Debug what email headers are being received
$debug = [
    'timestamp' => date('Y-m-d H:i:s'),
    'headers' => [
        'X-Tutorial-Email' => $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? null,
        'X-User-Email' => $_SERVER['HTTP_X_USER_EMAIL'] ?? null,
        'Authorization' => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
    ],
    'get_params' => $_GET,
    'post_params' => $_POST,
    'all_headers' => getallheaders(),
    'server_vars' => array_filter($_SERVER, function($key) {
        return strpos($key, 'HTTP_') === 0 || strpos($key, 'X_') === 0;
    }, ARRAY_FILTER_USE_KEY)
];

echo json_encode($debug, JSON_PRETTY_PRINT);
?>