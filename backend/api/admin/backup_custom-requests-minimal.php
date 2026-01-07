<?php
// Minimal custom requests API for debugging
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Email, X-Admin-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Simple response for testing
try {
    echo json_encode([
        'status' => 'success',
        'message' => 'Minimal API is working',
        'requests' => [
            [
                'id' => 1,
                'order_id' => 'CR-20250105-001',
                'customer_name' => 'John Doe',
                'customer_email' => 'john@example.com',
                'title' => 'Custom Wedding Invitation',
                'status' => 'submitted',
                'priority' => 'high',
                'deadline' => '2025-01-19',
                'created_at' => '2025-01-05 10:00:00',
                'days_until_deadline' => 14
            ],
            [
                'id' => 2,
                'order_id' => 'CR-20250105-002',
                'customer_name' => 'Sarah Smith',
                'customer_email' => 'sarah@example.com',
                'title' => 'Birthday Party Decorations',
                'status' => 'submitted',
                'priority' => 'medium',
                'deadline' => '2025-01-12',
                'created_at' => '2025-01-05 11:00:00',
                'days_until_deadline' => 7
            ]
        ],
        'total_count' => 2,
        'showing_count' => 2,
        'stats' => [
            'total_requests' => 2,
            'pending_requests' => 2,
            'completed_requests' => 0,
            'urgent_requests' => 0
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>