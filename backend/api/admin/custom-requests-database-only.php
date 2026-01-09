<?php
// Master Custom Requests API - Single Source of Truth
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Email, X-Admin-User-Id");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

ini_set("display_errors", 0);
error_reporting(0);

try {
    require_once "../../config/database.php";
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Create table if it doesn't exist (enhanced version with all fields)
    $pdo->exec("CREATE TABLE IF NOT EXISTS custom_requests (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id VARCHAR(100) NOT NULL DEFAULT '',
        customer_id INT UNSIGNED DEFAULT 0,
        customer_name VARCHAR(255) NOT NULL DEFAULT '',
        customer_email VARCHAR(255) NOT NULL DEFAULT '',
        customer_phone VARCHAR(50) DEFAULT '',
        title VARCHAR(255) NOT NULL DEFAULT '',
        occasion VARCHAR(100) DEFAULT '',
        description TEXT,
        requirements TEXT,
        budget_min DECIMAL(10,2) DEFAULT 500.00,
        budget_max DECIMAL(10,2) DEFAULT 1000.00,
        deadline DATE,
        priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
        status ENUM('submitted', 'pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
        admin_notes TEXT,
        design_url VARCHAR(500) DEFAULT '',
        source ENUM('form', 'cart', 'admin') DEFAULT 'form',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_customer_email (customer_email),
        INDEX idx_created_at (created_at)
    )");
    
    // Create custom request images table
    $pdo->exec("CREATE TABLE IF NOT EXISTS custom_request_images (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        request_id INT UNSIGNED NOT NULL,
        image_url VARCHAR(500) NOT NULL,
        filename VARCHAR(255) NOT NULL,
        original_filename VARCHAR(255) DEFAULT '',
        file_size INT UNSIGNED DEFAULT 0,
        mime_type VARCHAR(100) DEFAULT '',
        uploaded_by ENUM('customer', 'admin') DEFAULT 'customer',
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_request_id (request_id),
        INDEX idx_uploaded_at (uploaded_at),
        FOREIGN KEY (request_id) REFERENCES custom_requests(id) ON DELETE CASCADE
    )");
    
    // Check if table has any data, if not add comprehensive sample data
    $count = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
    if ($count == 0) {
        $sampleData = [
            [
                'CR-' . date('Ymd') . '-001',
                1,
                'Alice Johnson',
                'alice.johnson@email.com',
                '+1-555-0123',
                'Custom Wedding Anniversary Gift',
                'Anniversary',
                'I need a beautiful custom gift for my parents\' 25th wedding anniversary. They love gardening and vintage items. Something that shows how much their love has grown over the years.',
                'Silver theme to match 25th anniversary, incorporate garden elements like flowers or trees, vintage/classic style, size should be suitable for display on mantle, include their names and wedding date (June 15, 1999)',
                800.00,
                1200.00,
                '2026-06-10',
                'high',
                'pending',
                'form'
            ],
            [
                'CR-' . date('Ymd') . '-002',
                2,
                'Michael Chen',
                'michael.chen@email.com',
                '+1-555-0456',
                'Personalized Baby Gift Set',
                'Baby Shower',
                'My sister is having her first baby and I want to create a special personalized gift set. The baby is a girl and they\'ve chosen the name Emma. Looking for something unique and memorable.',
                'Baby girl theme, name "Emma" to be included, soft pastel colors (pink, lavender, cream), include practical items like blanket or clothing, also decorative keepsake item, safe materials only',
                300.00,
                600.00,
                '2026-02-28',
                'medium',
                'submitted',
                'form'
            ],
            [
                'CR-' . date('Ymd') . '-003',
                3,
                'Sarah Williams',
                'sarah.williams@email.com',
                '+1-555-0789',
                'Corporate Achievement Award',
                'Corporate',
                'We need a custom achievement award for our top performing employee of the year. This should be professional, elegant, and represent excellence in customer service.',
                'Professional corporate design, include company logo, recipient name "David Rodriguez", achievement "Customer Service Excellence 2026", premium materials (crystal, metal, or high-quality wood), suitable for office display',
                500.00,
                800.00,
                '2026-01-25',
                'high',
                'in_progress',
                'form'
            ],
            [
                'CR-' . date('Ymd') . '-004',
                4,
                'Robert Davis',
                'robert.davis@email.com',
                '+1-555-0321',
                'Custom Pet Memorial',
                'Memorial',
                'Our beloved dog Max passed away last month and we want to create a beautiful memorial to honor his memory. He was a Golden Retriever who brought so much joy to our family for 12 years.',
                'Pet memorial for dog named "Max", Golden Retriever breed, lived 2014-2026, include paw print design, warm and comforting design, suitable for garden or indoor display, weather-resistant if for outdoor use',
                400.00,
                700.00,
                '2026-03-01',
                'medium',
                'pending',
                'form'
            ]
        ];
        
        $insertStmt = $pdo->prepare("
            INSERT INTO custom_requests (
                order_id, customer_id, customer_name, customer_email, customer_phone,
                title, occasion, description, requirements, budget_min, budget_max,
                deadline, priority, status, source
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($sampleData as $data) {
            $insertStmt->execute($data);
        }
    }
    
    $method = $_SERVER["REQUEST_METHOD"];
    
    if ($method === "GET") {
        $status = $_GET["status"] ?? "all";
        $limit = min((int)($_GET["limit"] ?? 50), 100);
        $offset = max((int)($_GET["offset"] ?? 0), 0);
        
        // Build query
        $whereClause = "";
        $params = [];
        
        if ($status !== "all") {
            if ($status === "pending") {
                $whereClause = "WHERE status IN ('submitted', 'pending')";
            } else {
                $whereClause = "WHERE status = ?";
                $params[] = $status;
            }
        }
        
        $query = "SELECT * FROM custom_requests $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process each request
        foreach ($requests as &$request) {
            // Customer info
            $nameParts = explode(" ", $request["customer_name"], 2);
            $request["first_name"] = $nameParts[0] ?? "";
            $request["last_name"] = $nameParts[1] ?? "";
            $request["email"] = $request["customer_email"];
            $request["phone"] = $request["customer_phone"] ?? "";
            
            // Request details
            $request["category_name"] = $request["occasion"] ?: "General";
            $request["description"] = $request["description"] ?: "";
            $request["requirements"] = $request["requirements"] ?: "";
            
            // Ensure budget values are properly formatted as numbers
            $request["budget_min"] = (isset($request["budget_min"]) && $request["budget_min"] !== null && $request["budget_min"] !== '' && $request["budget_min"] !== '0') 
                ? (float)floatval($request["budget_min"]) 
                : null;
            $request["budget_max"] = (isset($request["budget_max"]) && $request["budget_max"] !== null && $request["budget_max"] !== '' && $request["budget_max"] !== '0') 
                ? (float)floatval($request["budget_max"]) 
                : null;
            
            // Images - Get from custom_request_images table
            // Base URL should point to backend folder
            // Script is at: /my_little_thingz/backend/api/admin/custom-requests-database-only.php
            // dirname(dirname(dirname())) gives: /my_little_thingz/backend
            // So base URL is: http://host/my_little_thingz/backend/
            $scriptPath = dirname(dirname(dirname($_SERVER["SCRIPT_NAME"]))); // /my_little_thingz/backend
            $baseUrl = "http://" . $_SERVER["HTTP_HOST"] . $scriptPath . "/";
            $request["images"] = [];
            
            // Get images from database
            try {
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
                
                // Build query based on available columns
                $selectCols = [$imageColumn];
                if ($hasFilename) {
                    $selectCols[] = 'filename';
                }
                if ($hasOriginalFilename) {
                    $selectCols[] = 'original_filename';
                }
                if ($hasUploadedAt || $hasUploadTime) {
                    $selectCols[] = $timeColumn;
                }
                
                $selectColsStr = implode(', ', $selectCols);
                
                $imageStmt = $pdo->prepare("
                    SELECT $selectColsStr
                    FROM custom_request_images 
                    WHERE request_id = ? 
                    ORDER BY $timeColumn ASC
                ");
                $imageStmt->execute([$request["id"]]);
                $dbImages = $imageStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Debug: Log image retrieval
                error_log("Request #{$request['id']}: Found " . count($dbImages) . " images in database. Image column: $imageColumn");
                
                foreach ($dbImages as $img) {
                    $imagePathOrUrl = $img[$imageColumn] ?? null;
                    if (empty($imagePathOrUrl)) {
                        error_log("Request #{$request['id']}: Skipping image with empty path/url. Row data: " . json_encode($img));
                        continue;
                    }
                    
                    error_log("Request #{$request['id']}: Processing image - Path/URL: $imagePathOrUrl");
                    
                    $filename = ($hasFilename && isset($img["filename"])) ? $img["filename"] : basename($imagePathOrUrl);
                    $originalName = ($hasOriginalFilename && isset($img["original_filename"]) && !empty($img["original_filename"])) 
                        ? $img["original_filename"] 
                        : $filename;
                    $uploadTime = ($hasUploadedAt || $hasUploadTime) ? ($img[$timeColumn] ?? null) : null;
                    
                    // If image_path/image_url is a relative path, make it absolute
                    // Check if it's already a full URL
                    if (!preg_match('/^https?:\/\//', $imagePathOrUrl)) {
                        // It's a relative path, make it absolute
                        $imageUrl = $baseUrl . ltrim($imagePathOrUrl, '/');
                    } else {
                        $imageUrl = $imagePathOrUrl;
                    }
                    
                    // Check if file exists (for relative paths only)
                    $fileExists = true;
                    if (!preg_match('/^https?:\/\//', $imagePathOrUrl)) {
                        $relativePath = ltrim($imagePathOrUrl, '/');
                        $possiblePaths = [
                            __DIR__ . "/../../" . $relativePath,
                            __DIR__ . "/../" . $relativePath,
                            __DIR__ . "/" . $relativePath
                        ];
                        
                        $fileExists = false;
                        foreach ($possiblePaths as $path) {
                            if (file_exists($path)) {
                                $fileExists = true;
                                break;
                            }
                        }
                    }
                    
                    $imageData = [
                        "url" => $imageUrl,
                        "filename" => $filename,
                        "original_name" => $originalName,
                        "file_exists" => $fileExists
                    ];
                    
                    if ($uploadTime) {
                        $imageData["uploaded_at"] = $uploadTime;
                    }
                    
                    error_log("Request #{$request['id']}: Adding image to request - URL: {$imageData['url']}, File exists: " . ($imageData['file_exists'] ? 'yes' : 'no'));
                    $request["images"][] = $imageData;
                }
                
                error_log("Request #{$request['id']}: Total images added: " . count($request["images"]));
                
                // If no images found, log a warning
                if (count($request["images"]) === 0) {
                    error_log("Request #{$request['id']}: WARNING - No images found in custom_request_images table for this request");
                }
            } catch (Exception $e) {
                // Continue if image query fails
                error_log("Request #{$request['id']}: Error loading images: " . $e->getMessage());
                $request["images"] = [];
            }
            
            // Fallback: Check for files in upload directory (legacy support)
            if (empty($request["images"])) {
                $uploadDir = __DIR__ . "/../../uploads/custom-requests/";
                if (is_dir($uploadDir)) {
                    $patterns = [
                        $uploadDir . "cr_" . $request["id"] . "_*",
                        $uploadDir . "request_" . $request["id"] . "_*"
                    ];
                    
                    foreach ($patterns as $pattern) {
                        $files = glob($pattern);
                        foreach ($files as $file) {
                            if (is_file($file)) {
                                $filename = basename($file);
                                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                                if (in_array($ext, ["jpg", "jpeg", "png", "gif", "webp", "svg"])) {
                                    $request["images"][] = [
                                        "url" => $baseUrl . "uploads/custom-requests/" . $filename,
                                        "filename" => $filename,
                                        "original_name" => $filename,
                                        "uploaded_at" => date("Y-m-d H:i:s", filemtime($file))
                                    ];
                                }
                            }
                        }
                    }
                }
            }
            
            // Default placeholder if no images found
            if (empty($request["images"])) {
                $request["images"] = [
                    [
                        "url" => $baseUrl . "uploads/custom-requests/placeholder.svg",
                        "filename" => "placeholder.svg",
                        "original_name" => "No image uploaded",
                        "uploaded_at" => null,
                        "is_placeholder" => true
                    ]
                ];
            }
            
            // Calculate deadline
            if ($request["deadline"]) {
                $deadlineDate = new DateTime($request["deadline"]);
                $today = new DateTime();
                $interval = $today->diff($deadlineDate);
                $request["days_until_deadline"] = $interval->invert ? -$interval->days : $interval->days;
            } else {
                $request["days_until_deadline"] = 30;
            }
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) FROM custom_requests $whereClause";
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute(array_slice($params, 0, -2));
        $totalCount = $countStmt->fetchColumn();
        
        // Get statistics
        $statsStmt = $pdo->query("
            SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN status IN ('submitted', 'pending') THEN 1 ELSE 0 END) as pending_requests,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_requests,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_requests,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_requests
            FROM custom_requests
        ");
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "status" => "success",
            "requests" => $requests,
            "total_count" => (int)$totalCount,
            "showing_count" => count($requests),
            "stats" => $stats,
            "message" => "Custom requests loaded successfully",
            "filter_applied" => $status,
            "api_version" => "master-v1.0",
            "timestamp" => date("Y-m-d H:i:s")
        ]);
        
    } elseif ($method === "POST") {
        $input = json_decode(file_get_contents("php://input"), true);
        
        if (isset($input["request_id"]) && isset($input["status"])) {
            // Update status
            $stmt = $pdo->prepare("UPDATE custom_requests SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$input["status"], $input["request_id"]]);
            
            // If status is 'in_progress', check if design editor is required
            $requiresEditor = false;
            if ($input["status"] === "in_progress") {
                // Check if design editor is required
                try {
                    $checkStmt = $pdo->prepare("
                        SELECT cr.*, 
                               pc.requires_editor, 
                               pc.type as product_type
                        FROM custom_requests cr
                        LEFT JOIN product_categories pc ON LOWER(TRIM(cr.occasion)) = LOWER(TRIM(pc.name))
                           OR LOWER(TRIM(cr.title)) LIKE CONCAT('%', LOWER(TRIM(pc.name)), '%')
                           OR (cr.category IS NOT NULL AND cr.category != '' AND LOWER(TRIM(cr.category)) = LOWER(TRIM(pc.name)))
                        WHERE cr.id = ?
                    ");
                    $checkStmt->execute([$input["request_id"]]);
                    $requestData = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($requestData) {
                        if ($requestData["requires_editor"] === 1 || $requestData["requires_editor"] === true) {
                            $requiresEditor = true;
                        } else {
                            // Check by keywords
                            $title = strtolower($requestData["title"] ?? "");
                            $category = strtolower($requestData["category"] ?? "");
                            $occasion = strtolower($requestData["occasion"] ?? "");
                            
                            $designKeywords = ['frame', 'polaroid', 'wedding card', 'card', 'poster', 'name board', 'print', 'photo'];
                            foreach ($designKeywords as $keyword) {
                                if (strpos($title, $keyword) !== false || 
                                    strpos($category, $keyword) !== false || 
                                    strpos($occasion, $keyword) !== false) {
                                    $requiresEditor = true;
                                    break;
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    // Ignore errors in checking
                }
            }
            
            echo json_encode([
                "status" => "success",
                "message" => "Request status updated successfully",
                "requires_editor" => $requiresEditor,
                "next_action" => $requiresEditor ? "open_design_editor" : "continue"
            ]);
        } else {
            // Create new request
            $orderId = "CR-" . date("Ymd") . "-" . strtoupper(substr(uniqid(), -6));
            
            $stmt = $pdo->prepare("
                INSERT INTO custom_requests (
                    order_id, customer_id, customer_name, customer_email, customer_phone,
                    title, occasion, description, requirements, budget_min, budget_max,
                    deadline, priority, status, source
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $orderId,
                $input["customer_id"] ?? 0,
                $input["customer_name"] ?? "Unknown Customer",
                $input["customer_email"] ?? "",
                $input["customer_phone"] ?? "",
                $input["title"] ?? "Custom Request",
                $input["occasion"] ?? "",
                $input["description"] ?? "",
                $input["requirements"] ?? "",
                $input["budget_min"] ?? 500.00,
                $input["budget_max"] ?? 1000.00,
                $input["deadline"] ?? date("Y-m-d", strtotime("+30 days")),
                $input["priority"] ?? "medium",
                "pending",
                $input["source"] ?? "form"
            ]);
            
            echo json_encode([
                "status" => "success",
                "message" => "Custom request created successfully",
                "order_id" => $orderId,
                "request_id" => $pdo->lastInsertId()
            ]);
        }
    } else {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>