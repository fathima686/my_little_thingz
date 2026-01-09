<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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
    // Create tables if they don't exist
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
        INDEX idx_uploaded_at (uploaded_at)
    )");
    
    // Handle both new request creation and image upload to existing request
    if (isset($_POST['action']) && $_POST['action'] === 'upload_image') {
        // Upload image to existing request
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
        
        if (!isset($_FILES['reference_image']) || $_FILES['reference_image']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'No valid image file uploaded']);
            exit;
        }
        
        $file = $_FILES['reference_image'];
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
                VALUES (?, ?, ?, ?, ?, ?, 'customer')
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
                'message' => 'Reference image uploaded successfully',
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
        
    } else {
        // Create new custom request with optional images
        $customerName = $_POST['customer_name'] ?? '';
        $customerEmail = $_POST['customer_email'] ?? '';
        $customerPhone = $_POST['customer_phone'] ?? '';
        $title = $_POST['title'] ?? '';
        $occasion = $_POST['occasion'] ?? '';
        $description = $_POST['description'] ?? '';
        $requirements = $_POST['requirements'] ?? '';
        $budgetMin = floatval($_POST['budget_min'] ?? 500);
        $budgetMax = floatval($_POST['budget_max'] ?? 1000);
        $deadline = $_POST['deadline'] ?? date('Y-m-d', strtotime('+30 days'));
        
        if (empty($customerName) || empty($customerEmail) || empty($title)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Customer name, email, and title are required']);
            exit;
        }
        
        // Generate order ID
        $orderId = 'CR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        
        // Insert custom request
        $insertStmt = $pdo->prepare("
            INSERT INTO custom_requests (
                order_id, customer_name, customer_email, customer_phone,
                title, occasion, description, requirements, budget_min, budget_max,
                deadline, status, source
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted', 'form')
        ");
        
        $insertStmt->execute([
            $orderId,
            $customerName,
            $customerEmail,
            $customerPhone,
            $title,
            $occasion,
            $description,
            $requirements,
            $budgetMin,
            $budgetMax,
            $deadline
        ]);
        
        $requestId = $pdo->lastInsertId();
        $uploadedImages = [];
        
        // Handle multiple reference images
        if (isset($_FILES['reference_images'])) {
            $files = $_FILES['reference_images'];
            
            // Create upload directory if it doesn't exist
            $uploadDir = __DIR__ . '/../../uploads/custom-requests/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Handle multiple files
            if (is_array($files['name'])) {
                $fileCount = count($files['name']);
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $files['name'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'size' => $files['size'][$i],
                            'type' => $files['type'][$i]
                        ];
                        
                        $uploadedImages[] = uploadSingleImage($pdo, $requestId, $file, $uploadDir);
                    }
                }
            } else {
                // Single file
                if ($files['error'] === UPLOAD_ERR_OK) {
                    $uploadedImages[] = uploadSingleImage($pdo, $requestId, $files, $uploadDir);
                }
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Custom request created successfully',
            'request_id' => $requestId,
            'order_id' => $orderId,
            'images_uploaded' => count($uploadedImages),
            'images' => $uploadedImages,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}

function uploadSingleImage($pdo, $requestId, $file, $uploadDir) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['error' => 'Invalid file type: ' . $file['name']];
    }
    
    if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
        return ['error' => 'File too large: ' . $file['name']];
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
            VALUES (?, ?, ?, ?, ?, ?, 'customer')
        ");
        
        $insertStmt->execute([
            $requestId,
            $imageUrl,
            $filename,
            $file['name'],
            $file['size'],
            $file['type']
        ]);
        
        return [
            'image_id' => $pdo->lastInsertId(),
            'image_url' => $imageUrl,
            'full_url' => 'http://localhost/my_little_thingz/backend/' . $imageUrl,
            'filename' => $filename,
            'original_filename' => $file['name'],
            'file_size' => $file['size']
        ];
    } else {
        return ['error' => 'Failed to upload: ' . $file['name']];
    }
}
?>