<?php
/**
 * Chunked Save Design API - Handles large data by saving in chunks
 */

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
    require_once "../../config/database.php";
    $database = new Database();
    $pdo = $database->getConnection();
    
    $rawInput = file_get_contents("php://input");
    if (empty($rawInput)) {
        throw new Exception("No input data received");
    }
    
    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON input: " . json_last_error_msg());
    }
    
    $requestId = $input["request_id"] ?? null;
    $designId = $input["design_id"] ?? null;
    $canvasData = $input["canvas_data"] ?? null;
    $exportImage = $input["export_image"] ?? null;
    $status = $input["status"] ?? "designing";
    $adminNotes = $input["admin_notes"] ?? "";
    $finalPrice = $input["final_price"] ?? null;
    
    if (!$requestId || !$canvasData) {
        throw new Exception("Request ID and canvas data are required");
    }
    
    // Validate request exists
    $checkStmt = $pdo->prepare("SELECT id FROM custom_requests WHERE id = ?");
    $checkStmt->execute([$requestId]);
    if (!$checkStmt->fetch()) {
        throw new Exception("Request not found: " . $requestId);
    }
    
    // Create table with proper structure
    $createTableSQL = "CREATE TABLE IF NOT EXISTS custom_request_designs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        request_id INT UNSIGNED NOT NULL,
        template_id INT UNSIGNED,
        canvas_width INT NOT NULL DEFAULT 800,
        canvas_height INT NOT NULL DEFAULT 600,
        canvas_data LONGTEXT,
        canvas_data_file VARCHAR(255),
        design_image_url VARCHAR(500),
        design_pdf_url VARCHAR(500),
        version INT DEFAULT 1,
        status ENUM('draft', 'designing', 'design_completed', 'approved', 'rejected') DEFAULT 'designing',
        admin_notes TEXT,
        customer_feedback TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_request_id (request_id),
        INDEX idx_status (status)
    )";
    
    $pdo->exec($createTableSQL);
    
    // Ensure canvas_data_file column exists (for existing tables)
    try {
        $checkColumn = $pdo->query("SHOW COLUMNS FROM custom_request_designs LIKE 'canvas_data_file'");
        if ($checkColumn->rowCount() == 0) {
            $pdo->exec("ALTER TABLE custom_request_designs ADD COLUMN canvas_data_file VARCHAR(255) NULL AFTER canvas_data");
            error_log("Added canvas_data_file column to existing table");
        }
        
        // Also ensure design_image_url column exists
        $checkImageColumn = $pdo->query("SHOW COLUMNS FROM custom_request_designs LIKE 'design_image_url'");
        if ($checkImageColumn->rowCount() == 0) {
            $pdo->exec("ALTER TABLE custom_request_designs ADD COLUMN design_image_url VARCHAR(500) NULL");
            error_log("Added design_image_url column to existing table");
        }
        
        // Ensure status column exists with proper enum values
        $checkStatusColumn = $pdo->query("SHOW COLUMNS FROM custom_request_designs LIKE 'status'");
        if ($checkStatusColumn->rowCount() == 0) {
            $pdo->exec("ALTER TABLE custom_request_designs ADD COLUMN status ENUM('draft', 'designing', 'design_completed', 'approved', 'rejected') DEFAULT 'designing'");
            error_log("Added status column to existing table");
        }
    } catch (Exception $e) {
        error_log("Column check/add failed: " . $e->getMessage());
        // Don't fail the whole operation, just log the error
    }
    
    // Save canvas data to file instead of database
    $dataDir = __DIR__ . "/../../uploads/designs/data/";
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    
    $canvasDataFile = "canvas_data_request_{$requestId}_" . time() . ".json";
    $canvasDataPath = $dataDir . $canvasDataFile;
    
    if (!file_put_contents($canvasDataPath, json_encode($canvasData, JSON_PRETTY_PRINT))) {
        throw new Exception("Failed to save canvas data to file");
    }
    
    // Save image if provided
    $imageUrl = null;
    if ($exportImage) {
        try {
            $uploadDir = __DIR__ . "/../../uploads/designs/images/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $exportImage);
            $imageData = base64_decode($imageData);
            
            if ($imageData) {
                $imageFilename = "design_request_{$requestId}_" . time() . ".jpg";
                $imagePath = $uploadDir . $imageFilename;
                
                if (file_put_contents($imagePath, $imageData)) {
                    $imageUrl = "uploads/designs/images/" . $imageFilename;
                }
            }
        } catch (Exception $e) {
            error_log("Image save error: " . $e->getMessage());
        }
    }
    
    // Save or update design record
    if ($designId) {
        $updateStmt = $pdo->prepare("
            UPDATE custom_request_designs 
            SET canvas_data_file = ?, 
                design_image_url = COALESCE(?, design_image_url),
                status = ?,
                admin_notes = ?,
                updated_at = NOW()
            WHERE id = ? AND request_id = ?
        ");
        $updateStmt->execute([
            $canvasDataFile,
            $imageUrl,
            $status,
            $adminNotes,
            $designId,
            $requestId
        ]);
        $finalDesignId = $designId;
    } else {
        $insertStmt = $pdo->prepare("
            INSERT INTO custom_request_designs (request_id, canvas_data_file, design_image_url, status, admin_notes)
            VALUES (?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([
            $requestId,
            $canvasDataFile,
            $imageUrl,
            $status,
            $adminNotes
        ]);
        $finalDesignId = $pdo->lastInsertId();
    }
    
    // Update request timestamp
    $updateRequestStmt = $pdo->prepare("UPDATE custom_requests SET updated_at = NOW() WHERE id = ?");
    $updateRequestStmt->execute([$requestId]);
    
    // If design is completed, add to customer's cart with the final price
    if ($status === 'design_completed') {
        try {
            addCompletedDesignToCart($pdo, $requestId, $finalDesignId, $imageUrl, $finalPrice);
        } catch (Exception $e) {
            error_log("Failed to add completed design to cart: " . $e->getMessage());
            // Don't fail the whole operation, just log the error
        }
    }
    
    ob_end_clean();
    echo json_encode([
        "status" => "success",
        "message" => "Design saved successfully (file-based storage)",
        "design_id" => $finalDesignId,
        "image_url" => $imageUrl,
        "design_status" => $status,
        "canvas_data_file" => $canvasDataFile
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    error_log("save-design-chunked.php Error: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

/**
 * Add completed custom design to customer's cart
 */
function addCompletedDesignToCart($pdo, $requestId, $designId, $imageUrl, $adminSetPrice = null) {
    // Get request details
    $requestStmt = $pdo->prepare("
        SELECT customer_id, user_id, title, description 
        FROM custom_requests 
        WHERE id = ?
    ");
    $requestStmt->execute([$requestId]);
    $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        throw new Exception("Request not found");
    }
    
    // Determine the final price to use
    $finalPrice = 50.00; // Default fallback price
    
    if ($adminSetPrice && is_numeric($adminSetPrice) && $adminSetPrice > 0) {
        // Use admin-set price (highest priority)
        $finalPrice = (float)$adminSetPrice;
        error_log("Using admin-set price: ₹$finalPrice for request #$requestId");
        
        // Update the custom_requests table with the final price
        try {
            // Check if price column exists, if not add it
            $checkColumn = $pdo->query("SHOW COLUMNS FROM custom_requests LIKE 'final_price'");
            if ($checkColumn->rowCount() == 0) {
                $pdo->exec("ALTER TABLE custom_requests ADD COLUMN final_price DECIMAL(10,2) NULL");
                error_log("Added final_price column to custom_requests table");
            }
            
            // Update the request with the final price
            $updatePriceStmt = $pdo->prepare("UPDATE custom_requests SET final_price = ? WHERE id = ?");
            $updatePriceStmt->execute([$finalPrice, $requestId]);
            error_log("Updated request #$requestId with final price: ₹$finalPrice");
            
        } catch (Exception $e) {
            error_log("Failed to update request with final price: " . $e->getMessage());
        }
        
    } else {
        // Try to get price from existing columns as fallback
        try {
            // Check for final_price column first
            $priceCheck = $pdo->query("SHOW COLUMNS FROM custom_requests LIKE 'final_price'");
            if ($priceCheck && $priceCheck->rowCount() > 0) {
                $priceStmt = $pdo->prepare("SELECT final_price FROM custom_requests WHERE id = ?");
                $priceStmt->execute([$requestId]);
                $priceResult = $priceStmt->fetch(PDO::FETCH_ASSOC);
                if ($priceResult && $priceResult['final_price']) {
                    $finalPrice = $priceResult['final_price'];
                    error_log("Using existing final_price: ₹$finalPrice for request #$requestId");
                }
            } else {
                // Check for budget fields as fallback
                $budgetStmt = $pdo->prepare("SELECT budget FROM custom_requests WHERE id = ?");
                $budgetStmt->execute([$requestId]);
                $budgetResult = $budgetStmt->fetch(PDO::FETCH_ASSOC);
                if ($budgetResult && $budgetResult['budget'] && is_numeric($budgetResult['budget'])) {
                    $finalPrice = (float)$budgetResult['budget'];
                    error_log("Using customer budget as fallback price: ₹$finalPrice for request #$requestId");
                }
            }
        } catch (Exception $e) {
            error_log("Price determination failed, using default: " . $e->getMessage());
        }
    }
    
    $customerId = $request['customer_id'] ?: $request['user_id'];
    if (!$customerId) {
        throw new Exception("No customer ID found for request");
    }
    
    // Create or get custom artwork entry
    $artworkTitle = "Custom Design: " . ($request['title'] ?: "Request #$requestId");
    $artworkDescription = $request['description'] ?: "Custom designed item";
    
    // Check if artwork already exists for this request
    $checkArtworkStmt = $pdo->prepare("
        SELECT id FROM artworks 
        WHERE title = ? AND description LIKE ? 
        LIMIT 1
    ");
    $checkArtworkStmt->execute([$artworkTitle, "%Request #$requestId%"]);
    $existingArtwork = $checkArtworkStmt->fetch();
    
    if ($existingArtwork) {
        $artworkId = $existingArtwork['id'];
        
        // Update existing artwork with the final price
        $updateArtworkStmt = $pdo->prepare("
            UPDATE artworks 
            SET price = ?, image_url = COALESCE(?, image_url), updated_at = NOW()
            WHERE id = ?
        ");
        $updateArtworkStmt->execute([$finalPrice, $imageUrl, $artworkId]);
        error_log("Updated existing artwork #$artworkId with final price: ₹$finalPrice");
        
    } else {
        // Create new artwork entry for the custom design
        $insertArtworkStmt = $pdo->prepare("
            INSERT INTO artworks (
                title, description, price, image_url, 
                status, availability, artist_id, category,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, 'active', 'available', 1, 'custom', NOW(), NOW())
        ");
        
        $insertArtworkStmt->execute([
            $artworkTitle,
            $artworkDescription . " (Request #$requestId)",
            $finalPrice,
            $imageUrl ?: 'uploads/designs/default-custom.jpg'
        ]);
        
        $artworkId = $pdo->lastInsertId();
        error_log("Created new artwork #$artworkId with final price: ₹$finalPrice");
    }
    
    // Check if already in cart
    $checkCartStmt = $pdo->prepare("
        SELECT id FROM cart 
        WHERE user_id = ? AND artwork_id = ?
    ");
    $checkCartStmt->execute([$customerId, $artworkId]);
    $existingCartItem = $checkCartStmt->fetch();
    
    if (!$existingCartItem) {
        // Add to cart
        $insertCartStmt = $pdo->prepare("
            INSERT INTO cart (user_id, artwork_id, quantity, added_at)
            VALUES (?, ?, 1, NOW())
        ");
        $insertCartStmt->execute([$customerId, $artworkId]);
        
        error_log("Added completed design to cart: Request #$requestId -> Artwork #$artworkId (₹$finalPrice) for Customer #$customerId");
    } else {
        error_log("Design already in cart: Request #$requestId -> Artwork #$artworkId (₹$finalPrice) for Customer #$customerId");
    }
    
    // Update request to mark as ready for purchase
    $updateRequestStmt = $pdo->prepare("
        UPDATE custom_requests 
        SET workflow_stage = 'design_completed', 
            design_completed_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ");
    $updateRequestStmt->execute([$requestId]);
}
?>