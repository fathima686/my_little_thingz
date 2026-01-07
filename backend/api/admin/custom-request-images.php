<?php
// Custom request image upload API with database integration
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Email, X-Admin-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Only POST method allowed']);
    exit;
}

try {
    require_once '../../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

try {
    $requestId = $_POST['request_id'] ?? '';
    
    if (empty($requestId)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Request ID is required']);
        exit;
    }
    
    // Verify request exists
    $checkStmt = $pdo->prepare("SELECT id, title FROM custom_requests WHERE id = ?");
    $checkStmt->execute([$requestId]);
    $request = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Request not found']);
        exit;
    }
    
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'No valid image file uploaded']);
        exit;
    }
    
    $file = $_FILES['image'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed']);
        exit;
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = __DIR__ . '/../../uploads/custom-requests/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'cr_' . $requestId . '_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $imageUrl = 'uploads/custom-requests/' . $filename;
        
        // Save image reference to database
        $insertStmt = $pdo->prepare("
            INSERT INTO custom_request_images 
            (request_id, image_url, filename, original_filename, file_size, mime_type, uploaded_by) 
            VALUES (?, ?, ?, ?, ?, ?, 'admin')
        ");
        
        $insertStmt->execute([
            $requestId,
            $imageUrl,
            $filename,
            $file['name'],
            $file['size'],
            $file['type']
        ]);
        
        $imageId = $pdo->lastInsertId();
        
        // Update request's updated_at timestamp
        $updateStmt = $pdo->prepare("UPDATE custom_requests SET updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$requestId]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Image uploaded successfully',
            'request_id' => $requestId,
            'image_id' => $imageId,
            'image_url' => $imageUrl,
            'full_url' => 'http://localhost/my_little_thingz/backend/' . $imageUrl,
            'filename' => $filename,
            'original_filename' => $file['name'],
            'file_size' => $file['size'],
            'upload_time' => date('Y-m-d H:i:s')
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to save uploaded file']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Upload error: ' . $e->getMessage()]);
}
?>