<?php
/**
 * Get Request Images API
 * Returns all images uploaded by customer for a custom request
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Email, X-Admin-User-Id");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

require_once "../../config/database.php";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $requestId = $_GET["request_id"] ?? null;
    
    if (!$requestId) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Request ID required"
        ]);
        exit;
    }
    
    // Check which columns exist
    $checkCol = $pdo->query("SHOW COLUMNS FROM custom_request_images LIKE 'image_url'");
    $hasImageUrl = $checkCol->rowCount() > 0;
    
    if (!$hasImageUrl) {
        $checkCol = $pdo->query("SHOW COLUMNS FROM custom_request_images LIKE 'image_path'");
        $hasImagePath = $checkCol->rowCount() > 0;
    } else {
        $hasImagePath = false;
    }
    
    $checkCol = $pdo->query("SHOW COLUMNS FROM custom_request_images LIKE 'filename'");
    $hasFilename = $checkCol->rowCount() > 0;
    
    $checkCol = $pdo->query("SHOW COLUMNS FROM custom_request_images LIKE 'original_filename'");
    $hasOriginalFilename = $checkCol->rowCount() > 0;
    
    $checkCol = $pdo->query("SHOW COLUMNS FROM custom_request_images LIKE 'uploaded_at'");
    $hasUploadedAt = $checkCol->rowCount() > 0;
    
    if (!$hasUploadedAt) {
        $checkCol = $pdo->query("SHOW COLUMNS FROM custom_request_images LIKE 'upload_time'");
        $hasUploadTime = $checkCol->rowCount() > 0;
    } else {
        $hasUploadTime = false;
    }
    
    $imageColumn = $hasImageUrl ? 'image_url' : ($hasImagePath ? 'image_path' : 'image_url');
    $timeColumn = $hasUploadedAt ? 'uploaded_at' : ($hasUploadTime ? 'upload_time' : 'uploaded_at');
    
    // Build SELECT columns dynamically
    $selectCols = [$imageColumn . ' as image_url'];
    if ($hasFilename) {
        $selectCols[] = 'filename';
    }
    if ($hasOriginalFilename) {
        $selectCols[] = 'original_filename';
    }
    if ($hasUploadedAt || $hasUploadTime) {
        $selectCols[] = $timeColumn . ' as uploaded_at';
    }
    
    $selectColsStr = implode(', ', $selectCols);
    
    // Get images for this request
    $orderBy = ($hasUploadedAt || $hasUploadTime) ? "ORDER BY $timeColumn ASC" : "ORDER BY id ASC";
    
    $stmt = $pdo->prepare("
        SELECT $selectColsStr
        FROM custom_request_images 
        WHERE request_id = ? 
        $orderBy
    ");
    $stmt->execute([$requestId]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Base URL should point to backend folder
    // Script is at: /my_little_thingz/backend/api/admin/get-request-images.php
    // dirname(dirname(dirname())) gives: /my_little_thingz/backend
    // So base URL is: http://host/my_little_thingz/backend/
    $scriptPath = dirname(dirname(dirname($_SERVER["SCRIPT_NAME"]))); // /my_little_thingz/backend
    $baseUrl = "http://" . $_SERVER["HTTP_HOST"] . $scriptPath . "/";
    
    // Format image URLs
    $formattedImages = [];
    foreach ($images as $img) {
        $imageUrl = $img["image_url"] ?? null;
        
        if (empty($imageUrl)) {
            continue; // Skip images without URL
        }
        
        // Convert relative paths to full URLs
        if (!preg_match('/^https?:\/\//', $imageUrl)) {
            $imageUrl = $baseUrl . ltrim($imageUrl, '/');
        }
        
        $filename = ($hasFilename && isset($img["filename"])) ? $img["filename"] : basename($imageUrl);
        $originalFilename = ($hasOriginalFilename && isset($img["original_filename"]) && !empty($img["original_filename"])) 
            ? $img["original_filename"] 
            : $filename;
        
        $formattedImages[] = [
            "url" => $imageUrl,
            "filename" => $filename,
            "original_filename" => $originalFilename
        ];
    }
    
    echo json_encode([
        "status" => "success",
        "images" => $formattedImages,
        "count" => count($formattedImages)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("get-request-images.php Error: " . $e->getMessage());
    error_log("get-request-images.php Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        "status" => "error",
        "message" => "Server error: " . $e->getMessage(),
        "debug" => [
            "file" => $e->getFile(),
            "line" => $e->getLine(),
            "trace" => explode("\n", $e->getTraceAsString())
        ]
    ]);
}
?>

