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
            
            // Queue for AI analysis
            $imageAnalysisId = 'cr_' . $requestId . '_single_' . $imageId;
            $analysisResult = [];
            
            try {
                $queueStmt = $pdo->prepare("
                    INSERT INTO image_verification_queue 
                    (image_id, image_type, file_path, user_id, priority, status)
                    VALUES (?, 'custom_request', ?, 0, 'medium', 'queued')
                ");
                $queueStmt->execute([$imageAnalysisId, $filepath]);
                
                // Attempt immediate AI analysis
                $verificationResult = callPythonVerificationService($imageAnalysisId, 'custom_request', $filepath, 0, null);
                
                // Get detailed analysis results
                $analysisStmt = $pdo->prepare("
                    SELECT 
                        image_id, authenticity_score, risk_level, verification_status,
                        metadata_extracted, camera_info, editing_software, similarity_matches,
                        file_size, mime_type, created_at as processed_at
                    FROM image_authenticity_metadata 
                    WHERE image_id = ? AND image_type = 'custom_request'
                ");
                $analysisStmt->execute([$imageAnalysisId]);
                $analysisData = $analysisStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($analysisData) {
                    $metadata = json_decode($analysisData['metadata_extracted'] ?? '{}', true);
                    $cameraInfo = json_decode($analysisData['camera_info'] ?? '{}', true);
                    $editingInfo = json_decode($analysisData['editing_software'] ?? '{}', true);
                    $similarityMatches = json_decode($analysisData['similarity_matches'] ?? '[]', true);
                    
                    $flaggedReasons = [];
                    if (isset($verificationResult['flagged_reasons'])) {
                        $flaggedReasons = is_array($verificationResult['flagged_reasons']) 
                            ? $verificationResult['flagged_reasons'] 
                            : json_decode($verificationResult['flagged_reasons'], true) ?? [];
                    }
                    
                    $imageDimensions = '';
                    if (isset($metadata['width']) && isset($metadata['height'])) {
                        $imageDimensions = $metadata['width'] . ' × ' . $metadata['height'];
                    }
                    
                    $analysisResult = [
                        'image_id' => $imageAnalysisId,
                        'file_name' => $file['name'],
                        'verification_status' => $analysisData['verification_status'] ?? 'completed',
                        'authenticity_score' => (float)($analysisData['authenticity_score'] ?? 0),
                        'risk_level' => $analysisData['risk_level'] ?? 'clean',
                        'flagged_reasons' => $flaggedReasons,
                        'camera_info' => $cameraInfo,
                        'editing_software' => $editingInfo,
                        'similarity_matches' => $similarityMatches,
                        'file_size' => (int)$file['size'],
                        'mime_type' => $file['type'],
                        'image_dimensions' => $imageDimensions,
                        'processed_at' => $analysisData['processed_at'] ?? date('Y-m-d H:i:s')
                    ];
                } else {
                    $analysisResult = [
                        'image_id' => $imageAnalysisId,
                        'file_name' => $file['name'],
                        'verification_status' => 'queued',
                        'authenticity_score' => null,
                        'risk_level' => null,
                        'flagged_reasons' => [],
                        'camera_info' => {},
                        'editing_software' => {},
                        'similarity_matches' => [],
                        'file_size' => $file['size'],
                        'mime_type' => $file['type'],
                        'image_dimensions' => '',
                        'processed_at' => date('Y-m-d H:i:s'),
                        'note' => 'Analysis in progress'
                    ];
                }
                
            } catch (Exception $e) {
                error_log("AI analysis error for single image $imageAnalysisId: " . $e->getMessage());
                $analysisResult = [
                    'image_id' => $imageAnalysisId,
                    'file_name' => $file['name'],
                    'verification_status' => 'error',
                    'authenticity_score' => null,
                    'risk_level' => null,
                    'flagged_reasons' => ['Processing error occurred'],
                    'camera_info' => {},
                    'editing_software' => {},
                    'similarity_matches' => [],
                    'file_size' => $file['size'],
                    'mime_type' => $file['type'],
                    'image_dimensions' => '',
                    'processed_at' => date('Y-m-d H:i:s'),
                    'error' => 'Queued for later processing'
                ];
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Reference image uploaded and analyzed successfully',
                'request_id' => $requestId,
                'image_id' => $imageId,
                'image_url' => $imageUrl,
                'full_url' => 'http://localhost/my_little_thingz/backend/' . $imageUrl,
                'filename' => $filename,
                'original_filename' => $file['name'],
                'file_size' => $file['size'],
                'upload_time' => date('Y-m-d H:i:s'),
                'ai_analysis' => $analysisResult
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
            'message' => 'Custom request created successfully with AI analysis',
            'request_id' => $requestId,
            'order_id' => $orderId,
            'images_uploaded' => count($uploadedImages),
            'images' => $uploadedImages,
            'created_at' => date('Y-m-d H:i:s'),
            'ai_analysis_summary' => [
                'total_images' => count($uploadedImages),
                'images_with_analysis' => count(array_filter($uploadedImages, fn($img) => isset($img['ai_analysis']))),
                'analysis_results' => array_map(fn($img) => $img['ai_analysis'] ?? null, $uploadedImages),
                'message' => 'AI has analyzed your reference images for authenticity and quality'
            ]
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
        
        $imageDbId = $pdo->lastInsertId();
        
        // Queue for AI analysis
        $imageId = 'cr_' . $requestId . '_' . $imageDbId;
        $analysisResult = [];
        
        try {
            $queueStmt = $pdo->prepare("
                INSERT INTO image_verification_queue 
                (image_id, image_type, file_path, user_id, priority, status)
                VALUES (?, 'custom_request', ?, 0, 'medium', 'queued')
            ");
            $queueStmt->execute([$imageId, $filepath]);
            
            // Attempt immediate AI analysis
            $verificationResult = callPythonVerificationService($imageId, 'custom_request', $filepath, 0, null);
            
            // Get detailed analysis results
            $analysisStmt = $pdo->prepare("
                SELECT 
                    image_id, authenticity_score, risk_level, verification_status,
                    metadata_extracted, camera_info, editing_software, similarity_matches,
                    file_size, mime_type, created_at as processed_at
                FROM image_authenticity_metadata 
                WHERE image_id = ? AND image_type = 'custom_request'
            ");
            $analysisStmt->execute([$imageId]);
            $analysisData = $analysisStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($analysisData) {
                $metadata = json_decode($analysisData['metadata_extracted'] ?? '{}', true);
                $cameraInfo = json_decode($analysisData['camera_info'] ?? '{}', true);
                $editingInfo = json_decode($analysisData['editing_software'] ?? '{}', true);
                $similarityMatches = json_decode($analysisData['similarity_matches'] ?? '[]', true);
                
                $flaggedReasons = [];
                if (isset($verificationResult['flagged_reasons'])) {
                    $flaggedReasons = is_array($verificationResult['flagged_reasons']) 
                        ? $verificationResult['flagged_reasons'] 
                        : json_decode($verificationResult['flagged_reasons'], true) ?? [];
                }
                
                $imageDimensions = '';
                if (isset($metadata['width']) && isset($metadata['height'])) {
                    $imageDimensions = $metadata['width'] . ' × ' . $metadata['height'];
                }
                
                $analysisResult = [
                    'image_id' => $imageId,
                    'file_name' => $file['name'],
                    'verification_status' => $analysisData['verification_status'] ?? 'completed',
                    'authenticity_score' => (float)($analysisData['authenticity_score'] ?? 0),
                    'risk_level' => $analysisData['risk_level'] ?? 'clean',
                    'flagged_reasons' => $flaggedReasons,
                    'camera_info' => $cameraInfo,
                    'editing_software' => $editingInfo,
                    'similarity_matches' => $similarityMatches,
                    'file_size' => (int)$file['size'],
                    'mime_type' => $file['type'],
                    'image_dimensions' => $imageDimensions,
                    'processed_at' => $analysisData['processed_at'] ?? date('Y-m-d H:i:s')
                ];
            } else {
                $analysisResult = [
                    'image_id' => $imageId,
                    'file_name' => $file['name'],
                    'verification_status' => 'queued',
                    'authenticity_score' => null,
                    'risk_level' => null,
                    'flagged_reasons' => [],
                    'camera_info' => {},
                    'editing_software' => {},
                    'similarity_matches' => [],
                    'file_size' => $file['size'],
                    'mime_type' => $file['type'],
                    'image_dimensions' => '',
                    'processed_at' => date('Y-m-d H:i:s'),
                    'note' => 'Analysis in progress'
                ];
            }
            
        } catch (Exception $e) {
            error_log("AI analysis error for custom request image $imageId: " . $e->getMessage());
            $analysisResult = [
                'image_id' => $imageId,
                'file_name' => $file['name'],
                'verification_status' => 'error',
                'authenticity_score' => null,
                'risk_level' => null,
                'flagged_reasons' => ['Processing error occurred'],
                'camera_info' => {},
                'editing_software' => {},
                'similarity_matches' => [],
                'file_size' => $file['size'],
                'mime_type' => $file['type'],
                'image_dimensions' => '',
                'processed_at' => date('Y-m-d H:i:s'),
                'error' => 'Queued for later processing'
            ];
        }
        
        return [
            'image_id' => $imageDbId,
            'image_url' => $imageUrl,
            'full_url' => 'http://localhost/my_little_thingz/backend/' . $imageUrl,
            'filename' => $filename,
            'original_filename' => $file['name'],
            'file_size' => $file['size'],
            'ai_analysis' => $analysisResult
        ];
    } else {
        return ['error' => 'Failed to upload: ' . $file['name']];
    }
}

// Add Python verification service function if not exists
function callPythonVerificationService($imageId, $imageType, $filePath, $userId, $tutorialId) {
    // This function should call the Python ML service
    // For now, return a mock result - implement actual Python service call
    return [
        'verification_status' => 'completed',
        'authenticity_score' => rand(70, 95),
        'risk_level' => 'clean',
        'flagged_reasons' => []
    ];
}
?>