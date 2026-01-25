<?php
/**
 * Simplified Save Design API for debugging
 */

// Prevent any output before headers
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    ob_end_clean();
    http_response_code(204);
    exit;
}

try {
    // Test database connection first
    require_once "../../config/database.php";
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get input
    $rawInput = file_get_contents("php://input");
    if (empty($rawInput)) {
        throw new Exception("No input data received");
    }
    
    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON: " . json_last_error_msg());
    }
    
    $requestId = $input["request_id"] ?? null;
    $status = $input["status"] ?? "designing";
    
    if (!$requestId) {
        throw new Exception("Request ID is required");
    }
    
    // Check if custom_requests table exists and request exists
    $checkStmt = $pdo->prepare("SELECT id FROM custom_requests WHERE id = ?");
    $checkStmt->execute([$requestId]);
    $request = $checkStmt->fetch();
    
    if (!$request) {
        throw new Exception("Request not found: " . $requestId);
    }
    
    // Try to create designs table if it doesn't exist
    $createTableSQL = "CREATE TABLE IF NOT EXISTS custom_request_designs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        request_id INT UNSIGNED NOT NULL,
        canvas_data LONGTEXT,
        status VARCHAR(50) DEFAULT 'designing',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($createTableSQL);
    
    // Insert a simple design record
    $insertStmt = $pdo->prepare("
        INSERT INTO custom_request_designs (request_id, canvas_data, status) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        canvas_data = VALUES(canvas_data), 
        status = VALUES(status),
        updated_at = NOW()
    ");
    
    $canvasDataJson = json_encode($input["canvas_data"] ?? []);
    $insertStmt->execute([$requestId, $canvasDataJson, $status]);
    
    $designId = $pdo->lastInsertId() ?: $requestId;
    
    ob_end_clean();
    echo json_encode([
        "status" => "success",
        "message" => "Design saved successfully (simple version)",
        "design_id" => $designId,
        "request_id" => $requestId,
        "design_status" => $status
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    error_log("save-design-simple.php Error: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage(),
        "file" => __FILE__,
        "line" => $e->getLine()
    ]);
}
?>