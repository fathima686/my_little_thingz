<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Server status information
$status = [
    'status' => 'success',
    'message' => 'Server is running',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'localhost',
    'server_port' => $_SERVER['SERVER_PORT'] ?? '80',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
    'extensions' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'json' => extension_loaded('json'),
        'curl' => extension_loaded('curl')
    ]
];

// Test database connection if config exists
try {
    if (file_exists(__DIR__ . '/config/database.php')) {
        require_once __DIR__ . '/config/database.php';
        $database = new Database();
        $pdo = $database->getConnection();
        $status['database'] = 'Connected';
        $status['database_info'] = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    } else {
        $status['database'] = 'Config not found';
    }
} catch (Exception $e) {
    $status['database'] = 'Connection failed: ' . $e->getMessage();
}

echo json_encode($status, JSON_PRETTY_PRINT);
?>