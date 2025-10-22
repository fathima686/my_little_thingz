<?php
header('Content-Type: application/json');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['http://localhost:5173', 'http://127.0.0.1:5173'];
if (in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Vary: Origin');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    exit; 
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Only POST method allowed'
    ]);
    exit;
}

require_once '../../config/database.php';
require_once '../../services/UserBehaviorTracker.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $behaviorTracker = new UserBehaviorTracker($db);

    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid JSON data'
        ]);
        exit;
    }

    // Validate required fields
    $requiredFields = ['user_id', 'artwork_id', 'behavior_type'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field])) {
            echo json_encode([
                'status' => 'error',
                'message' => "Missing required field: $field"
            ]);
            exit;
        }
    }

    $userId = (int)$input['user_id'];
    $artworkId = (int)$input['artwork_id'];
    $behaviorType = $input['behavior_type'];
    $additionalData = $input['additional_data'] ?? [];

    // Validate behavior type
    $validTypes = ['view', 'add_to_cart', 'add_to_wishlist', 'purchase', 'rating', 'remove_from_wishlist'];
    if (!in_array($behaviorType, $validTypes)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid behavior type. Must be one of: ' . implode(', ', $validTypes)
        ]);
        exit;
    }

    // Add client information to additional data
    $additionalData['ip_address'] = $additionalData['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $additionalData['user_agent'] = $additionalData['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? null;
    $additionalData['session_id'] = $additionalData['session_id'] ?? session_id() ?: uniqid('sess_', true);

    // Track the behavior
    $success = $behaviorTracker->trackBehavior($userId, $artworkId, $behaviorType, $additionalData);

    if ($success) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Behavior tracked successfully',
            'data' => [
                'user_id' => $userId,
                'artwork_id' => $artworkId,
                'behavior_type' => $behaviorType,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to track behavior'
        ]);
    }

} catch (Exception $e) {
    error_log("Behavior Tracking Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        'debug' => $e->getMessage()
    ]);
}








