<?php
// Simple test endpoint to debug purchase tutorial
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS, GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID, X-Tutorial-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$response = [
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'headers' => [],
    'input' => file_get_contents('php://input'),
    'post_data' => $_POST,
    'get_data' => $_GET,
    'server_vars' => [
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
        'HTTP_X_TUTORIAL_EMAIL' => $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? 'not set',
    ]
];

// Get all headers
if (function_exists('getallheaders')) {
    $response['headers'] = getallheaders();
} else {
    foreach ($_SERVER as $key => $value) {
        if (strpos($key, 'HTTP_') === 0) {
            $headerName = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $response['headers'][$headerName] = $value;
        }
    }
}

echo json_encode($response, JSON_PRETTY_PRINT);







