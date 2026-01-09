<?php
/**
 * Check if a custom request requires design editor
 * Returns product category info and requires_editor flag
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
    
    // Create product_categories table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_categories (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        type ENUM('design-based', 'handmade', 'mixed') NOT NULL,
        description TEXT,
        requires_editor BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_name (name)
    )");
    
    // Insert default categories if they don't exist
    $pdo->exec("INSERT IGNORE INTO product_categories (name, type, requires_editor, description) VALUES
        ('Photo Frames', 'design-based', TRUE, 'Custom photo frames with personalized designs'),
        ('Polaroids', 'design-based', TRUE, 'Custom polaroid prints with editing'),
        ('Wedding Cards', 'design-based', TRUE, 'Wedding invitation cards with custom designs'),
        ('Posters', 'design-based', TRUE, 'Custom posters and prints'),
        ('Name Boards', 'design-based', TRUE, 'Personalized name boards and signs'),
        ('Bouquets', 'handmade', FALSE, 'Handcrafted flower bouquets'),
        ('Handcrafted Gifts', 'handmade', FALSE, 'Custom handmade gift items'),
        ('Jewelry', 'handmade', FALSE, 'Custom jewelry pieces'),
        ('Cakes', 'handmade', FALSE, 'Custom decorated cakes')");
    
    $requestId = $_GET["request_id"] ?? null;
    
    if (!$requestId) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Request ID required"
        ]);
        exit;
    }
    
    // Get request details first
    $stmt = $pdo->prepare("SELECT * FROM custom_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Request not found"
        ]);
        exit;
    }
    
    // Now try to match with product categories using all possible fields
    $title = strtolower($request["title"] ?? "");
    $category = strtolower($request["category"] ?? "");
    $occasion = strtolower($request["occasion"] ?? "");
    $description = strtolower($request["description"] ?? "");
    $allText = "$title $category $occasion $description";
    
    // Try to find matching product category - check if any design-based category matches
    $categoryStmt = $pdo->prepare("
        SELECT * FROM product_categories 
        WHERE requires_editor = TRUE
        AND (
            LOWER(name) LIKE ? 
            OR LOWER(name) LIKE ?
            OR LOWER(name) LIKE ?
            OR LOWER(name) LIKE ?
            OR ? LIKE CONCAT('%', LOWER(name), '%')
            OR ? LIKE CONCAT('%', LOWER(name), '%')
            OR ? LIKE CONCAT('%', LOWER(name), '%')
        )
        LIMIT 1
    ");
    
    // Search for category names in request fields
    $categoryStmt->execute([
        "%$title%", "%$category%", "%$occasion%", "%$description%",
        $title, $category, $occasion
    ]);
    $matchedCategory = $categoryStmt->fetch(PDO::FETCH_ASSOC);
    
    // Determine if design editor is required
    $requiresEditor = false;
    $productType = 'handmade';
    $matchedCategoryName = null;
    
    // First check if we found a matching product category with requires_editor
    if ($matchedCategory && $matchedCategory["requires_editor"]) {
        $requiresEditor = true;
        $productType = $matchedCategory["type"] ?? 'design-based';
        $matchedCategoryName = $matchedCategory["name"];
    } else {
        // Check by keywords in title, category, description, or occasion
        // $allText already defined above
        
        // Expanded design keywords - including plurals and variations
        $designKeywords = [
            'frame', 'frames', 'photo frame', 'photo frames', 'photoframe', 'photoframes',
            'polaroid', 'polaroids', 'polaroid print', 'polaroid prints',
            'wedding card', 'wedding cards', 'invitation', 'invitations',
            'card', 'cards', 'greeting card', 'greeting cards',
            'poster', 'posters', 'print', 'prints',
            'name board', 'name boards', 'nameboard', 'nameboards',
            'photo', 'photos', 'picture', 'pictures',
            'album', 'albums', 'photo album', 'photo albums'
        ];
        
        // Check all text fields for design keywords
        foreach ($designKeywords as $keyword) {
            if (stripos($allText, $keyword) !== false) {
                $requiresEditor = true;
                $productType = 'design-based';
                
                // Map keyword to category name
                $keywordToCategory = [
                    'frame' => 'Photo Frames',
                    'frames' => 'Photo Frames',
                    'photo frame' => 'Photo Frames',
                    'polaroid' => 'Polaroids',
                    'polaroids' => 'Polaroids',
                    'wedding card' => 'Wedding Cards',
                    'card' => 'Wedding Cards',
                    'poster' => 'Posters',
                    'name board' => 'Name Boards'
                ];
                
                // Find matching category
                foreach ($keywordToCategory as $kw => $catName) {
                    if (stripos($allText, $kw) !== false) {
                        $matchedCategoryName = $catName;
                        break;
                    }
                }
                
                // If no direct match, try database lookup
                if (!$matchedCategoryName) {
                    $catStmt = $pdo->prepare("SELECT name FROM product_categories WHERE LOWER(name) LIKE ? AND requires_editor = TRUE LIMIT 1");
                    $catStmt->execute(["%$keyword%"]);
                    $catMatch = $catStmt->fetch(PDO::FETCH_ASSOC);
                    if ($catMatch) {
                        $matchedCategoryName = $catMatch["name"];
                    }
                }
                break;
            }
        }
        
        // If still no match, check handmade keywords (but don't override if design found)
        if (!$requiresEditor) {
            $handmadeKeywords = ['bouquet', 'bouquets', 'flower', 'flowers', 'cake', 'cakes', 'jewelry', 'jewellery', 'handmade', 'crafted'];
            foreach ($handmadeKeywords as $keyword) {
                if (stripos($allText, $keyword) !== false) {
                    $productType = 'handmade';
                    break;
                }
            }
        }
    }
    
    echo json_encode([
        "status" => "success",
        "request_id" => intval($requestId),
        "requires_editor" => $requiresEditor,
        "product_type" => $productType,
        "category_name" => $matchedCategoryName ?? $request["category"] ?? $request["occasion"] ?? "Unknown",
        "title" => $request["title"] ?? "",
        "occasion" => $request["occasion"] ?? "",
        "debug" => [
            "title" => $title,
            "category" => $category,
            "occasion" => $occasion,
            "matched_category" => $matchedCategoryName,
            "all_text" => $allText ?? ""
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("check-design-required.php Error: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "Server error: " . $e->getMessage(),
        "debug" => $e->getTraceAsString()
    ]);
}
?>

