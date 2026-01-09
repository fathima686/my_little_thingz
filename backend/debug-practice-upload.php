<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

echo json_encode([
    'debug_info' => [
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'headers' => getallheaders(),
        'post_data' => $_POST,
        'files_data' => $_FILES,
        'php_input' => file_get_contents('php://input'),
        'server_info' => [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads'),
            'memory_limit' => ini_get('memory_limit')
        ]
    ]
]);
?>