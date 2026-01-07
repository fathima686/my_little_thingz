<?php
// Ultra-simple API - no database, no includes, just pure PHP
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Email, X-Admin-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Hardcoded data - no database required
echo json_encode([
    'status' => 'success',
    'message' => 'Ultra-simple API working perfectly',
    'requests' => [
        [
            'id' => 1,
            'order_id' => 'CR-SIMPLE-001',
            'customer_name' => 'Test Customer 1',
            'customer_email' => 'test1@example.com',
            'title' => 'Test Custom Request 1',
            'status' => 'submitted',
            'priority' => 'high',
            'deadline' => '2025-01-20',
            'created_at' => '2025-01-05 10:00:00',
            'days_until_deadline' => 15
        ],
        [
            'id' => 2,
            'order_id' => 'CR-SIMPLE-002',
            'customer_name' => 'Test Customer 2',
            'customer_email' => 'test2@example.com',
            'title' => 'Test Custom Request 2',
            'status' => 'submitted',
            'priority' => 'medium',
            'deadline' => '2025-01-25',
            'created_at' => '2025-01-05 11:00:00',
            'days_until_deadline' => 20
        ]
    ],
    'total_count' => 2,
    'showing_count' => 2,
    'stats' => [
        'total_requests' => 2,
        'pending_requests' => 2,
        'completed_requests' => 0,
        'urgent_requests' => 1
    ],
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'php_version' => phpversion(),
        'method' => $_SERVER['REQUEST_METHOD'],
        'query_string' => $_SERVER['QUERY_STRING'] ?? ''
    ]
]);
?>