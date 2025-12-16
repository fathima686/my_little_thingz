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

    // Ensure tutorials table exists
    $db->exec("CREATE TABLE IF NOT EXISTS tutorials (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        thumbnail_url VARCHAR(255),
        video_url VARCHAR(255) NOT NULL,
        duration INT,
        difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
        price DECIMAL(10, 2) DEFAULT 0,
        is_free BOOLEAN DEFAULT 0,
        category VARCHAR(100),
        created_by INT UNSIGNED,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT 1
    )");

    // Fetch tutorials
    $stmt = $db->prepare("
        SELECT 
            id, 
            title, 
            description, 
            thumbnail_url, 
            duration, 
            difficulty_level, 
            price, 
            is_free, 
            category 
        FROM tutorials 
        WHERE is_active = 1 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $tutorials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert string booleans and price to proper types
    foreach ($tutorials as &$tutorial) {
        $tutorial['price'] = (float)$tutorial['price'];
        $tutorial['is_free'] = (bool)$tutorial['is_free'];
        $tutorial['duration'] = (int)$tutorial['duration'];
    }

    echo json_encode([
        'status' => 'success',
        'tutorials' => $tutorials
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error fetching tutorials: ' . $e->getMessage()
    ]);
}
