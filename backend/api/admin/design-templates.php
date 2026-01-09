<?php
/**
 * Design Templates API
 * Returns available design templates with different sizes and orientations
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Email, X-Admin-User-Id");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

require_once "../../config/database.php";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Create design_templates table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS design_templates (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        width INT NOT NULL,
        height INT NOT NULL,
        orientation ENUM('portrait', 'landscape', 'square') NOT NULL,
        unit ENUM('px', 'mm', 'inch') DEFAULT 'px',
        dpi INT DEFAULT 300,
        background_color VARCHAR(7) DEFAULT '#FFFFFF',
        description TEXT,
        category VARCHAR(100),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        INDEX idx_active (is_active)
    )");
    
    // Check and insert default templates if they don't exist (prevent duplicates)
    $defaultTemplates = [
        // Photo Sizes
        ['4×6 Photo', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (portrait)', 'Photo'],
        ['4×6 Photo Landscape', 1800, 1200, 'landscape', 'px', 300, '#FFFFFF', 'Standard 4×6 inch photo print (landscape)', 'Photo'],
        ['5×7 Photo', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard 5×7 inch photo print', 'Photo'],
        ['8×10 Photo', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Standard 8×10 inch photo print', 'Photo'],
        ['11×14 Photo', 3300, 4200, 'portrait', 'px', 300, '#FFFFFF', 'Large 11×14 inch photo print', 'Photo'],
        
        // Professional Photo Frames - Classic
        ['Classic Frame 4×6', 1200, 1800, 'portrait', 'px', 300, '#F5F5DC', 'Classic beige frame - 4×6 inch', 'Frame'],
        ['Classic Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#F5F5DC', 'Classic beige frame - 5×7 inch', 'Frame'],
        ['Classic Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#F5F5DC', 'Classic beige frame - 8×10 inch', 'Frame'],
        ['Classic Frame 11×14', 3300, 4200, 'portrait', 'px', 300, '#F5F5DC', 'Classic beige frame - 11×14 inch', 'Frame'],
        
        // Professional Photo Frames - Modern
        ['Modern Black Frame 4×6', 1200, 1800, 'portrait', 'px', 300, '#1A1A1A', 'Modern black frame - 4×6 inch', 'Frame'],
        ['Modern Black Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#1A1A1A', 'Modern black frame - 5×7 inch', 'Frame'],
        ['Modern Black Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#1A1A1A', 'Modern black frame - 8×10 inch', 'Frame'],
        ['Modern White Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Modern white frame - 5×7 inch', 'Frame'],
        ['Modern White Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Modern white frame - 8×10 inch', 'Frame'],
        
        // Professional Photo Frames - Elegant
        ['Elegant Gold Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFD700', 'Elegant gold frame - 5×7 inch', 'Frame'],
        ['Elegant Gold Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFD700', 'Elegant gold frame - 8×10 inch', 'Frame'],
        ['Elegant Silver Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#C0C0C0', 'Elegant silver frame - 5×7 inch', 'Frame'],
        ['Elegant Silver Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#C0C0C0', 'Elegant silver frame - 8×10 inch', 'Frame'],
        
        // Professional Photo Frames - Wood
        ['Wooden Frame 4×6', 1200, 1800, 'portrait', 'px', 300, '#8B4513', 'Natural wood frame - 4×6 inch', 'Frame'],
        ['Wooden Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#8B4513', 'Natural wood frame - 5×7 inch', 'Frame'],
        ['Wooden Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#8B4513', 'Natural wood frame - 8×10 inch', 'Frame'],
        ['Dark Wood Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#654321', 'Dark wood frame - 5×7 inch', 'Frame'],
        ['Dark Wood Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#654321', 'Dark wood frame - 8×10 inch', 'Frame'],
        
        // Professional Photo Frames - Vintage
        ['Vintage Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#D2B48C', 'Vintage distressed frame - 5×7 inch', 'Frame'],
        ['Vintage Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#D2B48C', 'Vintage distressed frame - 8×10 inch', 'Frame'],
        ['Antique Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#CD853F', 'Antique style frame - 5×7 inch', 'Frame'],
        ['Antique Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#CD853F', 'Antique style frame - 8×10 inch', 'Frame'],
        
        // Professional Photo Frames - Contemporary
        ['Minimalist Frame 4×6', 1200, 1800, 'portrait', 'px', 300, '#E8E8E8', 'Minimalist thin frame - 4×6 inch', 'Frame'],
        ['Minimalist Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#E8E8E8', 'Minimalist thin frame - 5×7 inch', 'Frame'],
        ['Floating Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#FFFFFF', 'Floating frame style - 8×10 inch', 'Frame'],
        ['Gallery Frame 5×7', 1500, 2100, 'portrait', 'px', 300, '#F0F0F0', 'Gallery style frame - 5×7 inch', 'Frame'],
        ['Gallery Frame 8×10', 2400, 3000, 'portrait', 'px', 300, '#F0F0F0', 'Gallery style frame - 8×10 inch', 'Frame'],
        
        // Professional Photo Frames - Multi-Photo
        ['Collage Frame 2×2', 2400, 2400, 'square', 'px', 300, '#FFFFFF', '2×2 photo collage frame', 'Frame'],
        ['Collage Frame 3×3', 3600, 3600, 'square', 'px', 300, '#FFFFFF', '3×3 photo collage frame', 'Frame'],
        ['Collage Frame 4×4', 4800, 4800, 'square', 'px', 300, '#FFFFFF', '4×4 photo collage frame', 'Frame'],
        ['Family Frame Horizontal', 4800, 3600, 'landscape', 'px', 300, '#FFFFFF', 'Horizontal family photo frame', 'Frame'],
        ['Family Frame Vertical', 3600, 4800, 'portrait', 'px', 300, '#FFFFFF', 'Vertical family photo frame', 'Frame'],
        
        // Polaroids
        ['Polaroid', 3000, 3600, 'portrait', 'px', 300, '#FFFFFF', 'Polaroid style print (3.5×4.2 inch)', 'Polaroid'],
        ['Polaroid Landscape', 3600, 3000, 'landscape', 'px', 300, '#FFFFFF', 'Polaroid landscape style', 'Polaroid'],
        
        // Documents
        ['A4 Portrait', 2480, 3508, 'portrait', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) portrait', 'Document'],
        ['A4 Landscape', 3508, 2480, 'landscape', 'px', 300, '#FFFFFF', 'A4 size (8.27×11.69 inch) landscape', 'Document'],
        ['Letter Portrait', 2550, 3300, 'portrait', 'px', 300, '#FFFFFF', 'US Letter size (8.5×11 inch) portrait', 'Document'],
        
        // Cards
        ['Wedding Card 5×7', 1500, 2100, 'portrait', 'px', 300, '#FFFFFF', 'Standard wedding card size 5×7 inch', 'Card'],
        ['Wedding Card 4×6', 1200, 1800, 'portrait', 'px', 300, '#FFFFFF', 'Compact wedding card size 4×6 inch', 'Card'],
        ['Invitation Card', 1050, 1485, 'portrait', 'px', 300, '#FFFFFF', 'Standard invitation card (A5)', 'Card'],
        
        // Social Media
        ['Square 8×8', 2400, 2400, 'square', 'px', 300, '#FFFFFF', 'Square 8×8 inch design', 'Social'],
        ['Square 12×12', 3600, 3600, 'square', 'px', 300, '#FFFFFF', 'Square 12×12 inch design', 'Social'],
        ['Instagram Post', 1080, 1080, 'square', 'px', 300, '#FFFFFF', 'Instagram square post', 'Social'],
        ['Instagram Story', 1080, 1920, 'portrait', 'px', 300, '#FFFFFF', 'Instagram story format', 'Social'],
        
        // Posters
        ['Poster 11×17', 3300, 5100, 'portrait', 'px', 300, '#FFFFFF', 'Standard poster size 11×17 inch', 'Poster'],
        ['Poster 18×24', 5400, 7200, 'portrait', 'px', 300, '#FFFFFF', 'Large poster size 18×24 inch', 'Poster'],
        ['Poster 24×36', 7200, 10800, 'portrait', 'px', 300, '#FFFFFF', 'Extra large poster 24×36 inch', 'Poster']
    ];
    
    $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM design_templates WHERE name = ? AND width = ? AND height = ?");
    $insertStmt = $pdo->prepare("INSERT INTO design_templates (name, width, height, orientation, unit, dpi, background_color, description, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($defaultTemplates as $template) {
        $checkStmt->execute([$template[0], $template[1], $template[2]]);
        $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if ($exists['count'] == 0) {
            $insertStmt->execute($template);
        }
    }
    
    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        $category = $_GET["category"] ?? null;
        
        $query = "SELECT DISTINCT * FROM design_templates WHERE is_active = TRUE";
        $params = [];
        
        if ($category) {
            $query .= " AND category = ?";
            $params[] = $category;
        }
        
        $query .= " ORDER BY category, name";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Remove duplicates based on name, width, height combination
        $uniqueTemplates = [];
        $seen = [];
        foreach ($templates as $template) {
            $key = $template['name'] . '_' . $template['width'] . '_' . $template['height'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueTemplates[] = $template;
            }
        }
        
        // Group templates by category for better organization
        $groupedTemplates = [];
        foreach ($uniqueTemplates as $template) {
            $cat = $template['category'] ?: 'Other';
            if (!isset($groupedTemplates[$cat])) {
                $groupedTemplates[$cat] = [];
            }
            $groupedTemplates[$cat][] = $template;
        }
        
        echo json_encode([
            "status" => "success",
            "templates" => $uniqueTemplates,
            "grouped" => $groupedTemplates
        ]);
        
    } elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
        // Save template selection for a request
        $input = json_decode(file_get_contents("php://input"), true);
        $requestId = $input["request_id"] ?? null;
        $templateId = $input["template_id"] ?? null;
        
        if (!$requestId || !$templateId) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Request ID and Template ID required"
            ]);
            exit;
        }
        
        // Get template details
        $templateStmt = $pdo->prepare("SELECT * FROM design_templates WHERE id = ?");
        $templateStmt->execute([$templateId]);
        $template = $templateStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            http_response_code(404);
            echo json_encode([
                "status" => "error",
                "message" => "Template not found"
            ]);
            exit;
        }
        
        // Create custom_request_designs table if it doesn't exist
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
            // Table might already exist, ignore error
            error_log("design-templates.php: Table creation note: " . $e->getMessage());
        }
        
        // Check if design already exists
        $checkStmt = $pdo->prepare("SELECT id, version FROM custom_request_designs WHERE request_id = ? ORDER BY version DESC LIMIT 1");
        $checkStmt->execute([$requestId]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        $version = 1;
        if ($existing) {
            $version = intval($existing["version"]) + 1;
            // Update status of previous version to draft if it's still designing
            $updateStmt = $pdo->prepare("UPDATE custom_request_designs SET status = 'draft' WHERE request_id = ? AND status = 'designing'");
            $updateStmt->execute([$requestId]);
        }
        
        // Insert new design record
        $insertStmt = $pdo->prepare("
            INSERT INTO custom_request_designs (request_id, template_id, canvas_width, canvas_height, version, status)
            VALUES (?, ?, ?, ?, ?, 'designing')
        ");
        $insertStmt->execute([
            $requestId,
            $templateId,
            $template["width"],
            $template["height"],
            $version
        ]);
        
        // Update custom_requests status to 'designing'
        $updateRequestStmt = $pdo->prepare("UPDATE custom_requests SET status = 'in_progress' WHERE id = ?");
        $updateRequestStmt->execute([$requestId]);
        
        echo json_encode([
            "status" => "success",
            "message" => "Template selected successfully",
            "design_id" => $pdo->lastInsertId(),
            "template" => $template,
            "version" => $version
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("design-templates.php Error: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "Server error: " . $e->getMessage(),
        "debug" => $e->getTraceAsString()
    ]);
}
?>

