<?php
/**
 * Get Design API
 * Retrieves design data for a custom request
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Email, X-Admin-User-Id, X-User-ID");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

require_once "../../config/database.php";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $requestId = $_GET["request_id"] ?? null;
    $designId = $_GET["design_id"] ?? null;
    
    // Check if this is a customer request (has X-User-ID header) or admin
    $isAdmin = isset($_SERVER['HTTP_X_ADMIN_USER_ID']) || isset($_SERVER['HTTP_X_ADMIN_EMAIL']);
    $customerId = isset($_SERVER['HTTP_X_USER_ID']) ? (int)$_SERVER['HTTP_X_USER_ID'] : null;
    
    if (!$requestId) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Request ID required"
        ]);
        exit;
    }
    
    // If customer, verify they own this request
    if (!$isAdmin && $customerId) {
        $checkStmt = $pdo->prepare("
            SELECT id FROM custom_requests 
            WHERE id = ? AND (customer_id = ? OR user_id = ?)
        ");
        $checkStmt->execute([$requestId, $customerId, $customerId]);
        $owned = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$owned) {
            http_response_code(403);
            echo json_encode([
                "status" => "error",
                "message" => "You don't have access to this request"
            ]);
            exit;
        }
    }
    
    // Ensure custom_request_designs table exists
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS custom_request_designs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            request_id INT UNSIGNED NOT NULL,
            template_id INT UNSIGNED,
            canvas_width INT NOT NULL,
            canvas_height INT NOT NULL,
            canvas_data LONGTEXT,
            design_image_url VARCHAR(500),
            design_pdf_url VARCHAR(500),
            version INT DEFAULT 1,
            status ENUM('draft', 'designing', 'design_completed', 'approved', 'rejected') DEFAULT 'draft',
            admin_notes TEXT,
            customer_feedback TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_request_id (request_id),
            INDEX idx_status (status)
        )");
    } catch (Exception $e) {
        error_log("get-design.php: Table creation note: " . $e->getMessage());
    }
    
    $baseUrl = "http://" . $_SERVER["HTTP_HOST"] . dirname(dirname(dirname($_SERVER["SCRIPT_NAME"]))) . "/";
    
    if ($designId) {
        // Get specific design
        $stmt = $pdo->prepare("
            SELECT d.*, t.name as template_name, t.width as template_width, t.height as template_height
            FROM custom_request_designs d
            LEFT JOIN design_templates t ON d.template_id = t.id
            WHERE d.id = ? AND d.request_id = ?
        ");
        $stmt->execute([$designId, $requestId]);
        $design = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$design) {
            http_response_code(404);
            echo json_encode([
                "status" => "error",
                "message" => "Design not found"
            ]);
            exit;
        }
        
        // Convert image URLs to full URLs
        if ($design["design_image_url"] && !preg_match('/^https?:\/\//', $design["design_image_url"])) {
            $design["design_image_url"] = $baseUrl . ltrim($design["design_image_url"], '/');
        }
        if ($design["design_pdf_url"] && !preg_match('/^https?:\/\//', $design["design_pdf_url"])) {
            $design["design_pdf_url"] = $baseUrl . ltrim($design["design_pdf_url"], '/');
        }
        
        echo json_encode([
            "status" => "success",
            "design" => $design
        ]);
    } else {
        // Get all designs for request (latest first)
        $stmt = $pdo->prepare("
            SELECT d.*, t.name as template_name, t.width as template_width, t.height as template_height
            FROM custom_request_designs d
            LEFT JOIN design_templates t ON d.template_id = t.id
            WHERE d.request_id = ?
            ORDER BY d.version DESC
        ");
        $stmt->execute([$requestId]);
        $designs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert image URLs to full URLs
        foreach ($designs as &$design) {
            if ($design["design_image_url"] && !preg_match('/^https?:\/\//', $design["design_image_url"])) {
                $design["design_image_url"] = $baseUrl . ltrim($design["design_image_url"], '/');
            }
            if ($design["design_pdf_url"] && !preg_match('/^https?:\/\//', $design["design_pdf_url"])) {
                $design["design_pdf_url"] = $baseUrl . ltrim($design["design_pdf_url"], '/');
            }
        }
        
        // Get request details for customer view
        $requestStmt = $pdo->prepare("SELECT id, title, status FROM custom_requests WHERE id = ?");
        $requestStmt->execute([$requestId]);
        $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "status" => "success",
            "designs" => $designs,
            "latest" => $designs[0] ?? null,
            "request" => $request
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("get-design.php Error: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "Server error: " . $e->getMessage(),
        "debug" => $e->getTraceAsString()
    ]);
}
?>

