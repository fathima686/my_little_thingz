<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }

    $tutorialId = (int)($_GET['id'] ?? 0);

    if (!$tutorialId) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing tutorial id']);
        exit;
    }

    // Fetch tutorial details
    $stmt = $db->prepare("
        SELECT 
            id, 
            title, 
            description, 
            thumbnail_url, 
            video_url, 
            duration, 
            difficulty_level, 
            price, 
            is_free, 
            category 
        FROM tutorials 
        WHERE id = ? AND is_active = 1
    ");
    $stmt->execute([$tutorialId]);
    $tutorial = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tutorial) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Tutorial not found']);
        exit;
    }

    // Convert types
    $tutorial['price'] = (float)$tutorial['price'];
    $tutorial['is_free'] = (bool)$tutorial['is_free'];
    $tutorial['duration'] = (int)$tutorial['duration'];

    echo json_encode([
        'status' => 'success',
        'tutorial' => $tutorial
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error fetching tutorial: ' . $e->getMessage()
    ]);
}
