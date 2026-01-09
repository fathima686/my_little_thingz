<?php
/**
 * Save Design API
 * Saves canvas JSON and exports as image/PDF for custom requests
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Email, X-Admin-User-Id");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

require_once "../../config/database.php";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $input = json_decode(file_get_contents("php://input"), true);
    $requestId = $input["request_id"] ?? null;
    $designId = $input["design_id"] ?? null;
    $canvasData = $input["canvas_data"] ?? null;
    $previewImage = $input["preview_image"] ?? null;
    $exportImage = $input["export_image"] ?? null; // Base64 image
    $exportPDF = $input["export_pdf"] ?? null; // Base64 PDF
    $status = $input["status"] ?? "designing";
    $adminNotes = $input["admin_notes"] ?? "";
    
    if (!$requestId || !$canvasData) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Request ID and canvas data required"
        ]);
        exit;
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
        error_log("save-design.php: Table creation note: " . $e->getMessage());
    }
    
    // Create upload directories
    $baseDir = __DIR__ . "/../../uploads/designs/";
    $imageDir = $baseDir . "images/";
    $pdfDir = $baseDir . "pdfs/";
    
    if (!is_dir($imageDir)) {
        mkdir($imageDir, 0755, true);
    }
    if (!is_dir($pdfDir)) {
        mkdir($pdfDir, 0755, true);
    }
    
    $imageUrl = null;
    $pdfUrl = null;
    
    // Save image if provided
    if ($exportImage) {
        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $exportImage);
        $imageData = base64_decode($imageData);
        
        if ($imageData) {
            $imageFilename = "design_request_{$requestId}_" . time() . ".png";
            $imagePath = $imageDir . $imageFilename;
            
            if (file_put_contents($imagePath, $imageData)) {
                $imageUrl = "uploads/designs/images/" . $imageFilename;
            }
        }
    } else if ($previewImage) {
        // Fallback to preview image
        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $previewImage);
        $imageData = base64_decode($imageData);
        
        if ($imageData) {
            $imageFilename = "design_request_{$requestId}_preview_" . time() . ".png";
            $imagePath = $imageDir . $imageFilename;
            
            if (file_put_contents($imagePath, $imageData)) {
                $imageUrl = "uploads/designs/images/" . $imageFilename;
            }
        }
    }
    
    // Save PDF if provided (base64 encoded)
    if ($exportPDF) {
        $pdfData = preg_replace('/^data:application\/pdf;base64,/', '', $exportPDF);
        $pdfData = base64_decode($pdfData);
        
        if ($pdfData) {
            $pdfFilename = "design_request_{$requestId}_" . time() . ".pdf";
            $pdfPath = $pdfDir . $pdfFilename;
            
            if (file_put_contents($pdfPath, $pdfData)) {
                $pdfUrl = "uploads/designs/pdfs/" . $pdfFilename;
            }
        }
    }
    
    // Get or create design record
    if ($designId) {
        $checkStmt = $pdo->prepare("SELECT * FROM custom_request_designs WHERE id = ? AND request_id = ?");
        $checkStmt->execute([$designId, $requestId]);
        $design = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$design) {
            http_response_code(404);
            echo json_encode([
                "status" => "error",
                "message" => "Design not found"
            ]);
            exit;
        }
        
        // Update existing design
        $updateStmt = $pdo->prepare("
            UPDATE custom_request_designs 
            SET canvas_data = ?, 
                design_image_url = COALESCE(?, design_image_url),
                design_pdf_url = COALESCE(?, design_pdf_url),
                status = ?,
                admin_notes = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([
            json_encode($canvasData),
            $imageUrl,
            $pdfUrl,
            $status,
            $adminNotes,
            $designId
        ]);
        
        $finalDesignId = $designId;
    } else {
        // Get latest version
        $versionStmt = $pdo->prepare("SELECT MAX(version) as max_version FROM custom_request_designs WHERE request_id = ?");
        $versionStmt->execute([$requestId]);
        $versionData = $versionStmt->fetch(PDO::FETCH_ASSOC);
        $version = ($versionData["max_version"] ?? 0) + 1;
        
        // Get canvas dimensions from canvas data
        $canvasJson = json_decode($canvasData, true);
        $canvasWidth = $canvasJson["width"] ?? 800;
        $canvasHeight = $canvasJson["height"] ?? 600;
        
        // Create new design
        $insertStmt = $pdo->prepare("
            INSERT INTO custom_request_designs (request_id, canvas_width, canvas_height, canvas_data, design_image_url, design_pdf_url, version, status, admin_notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([
            $requestId,
            $canvasWidth,
            $canvasHeight,
            json_encode($canvasData),
            $imageUrl,
            $pdfUrl,
            $version,
            $status,
            $adminNotes
        ]);
        
        $finalDesignId = $pdo->lastInsertId();
    }
    
    // Update custom_requests status based on design status
    // Keep request status as 'in_progress' if design is completed, so customer can see both statuses
    // The design_status will show 'design_completed' separately
    if ($status === "design_completed") {
        // Don't change the main status to 'completed' yet - let customer approve design first
        // Keep as 'in_progress' so they can see design is ready for review
        // The design_status field will show 'design_completed' in the customer API
        $updateRequestStmt = $pdo->prepare("UPDATE custom_requests SET updated_at = NOW() WHERE id = ?");
        $updateRequestStmt->execute([$requestId]);
    }
    
    echo json_encode([
        "status" => "success",
        "message" => "Design saved successfully",
        "design_id" => $finalDesignId,
        "image_url" => $imageUrl,
        "pdf_url" => $pdfUrl,
        "design_status" => $status
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("save-design.php Error: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "Server error: " . $e->getMessage(),
        "debug" => $e->getTraceAsString()
    ]);
}
?>

