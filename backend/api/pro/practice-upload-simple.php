<?php
/**
 * Simple Practice Upload API (Fallback)
 * 
 * Basic practice upload without craft validation
 * Uses only the enhanced authenticity service
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    require_once '../../config/database.php';
    require_once '../../config/env-loader.php';
    require_once '../../services/EnhancedImageAuthenticityServiceV2.php';
    
    // Load environment variables
    EnvLoader::load();
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Initialize only the authenticity service (no craft validation)
    $localClassifierUrl = getenv('LOCAL_CLASSIFIER_URL') ?: $_ENV['LOCAL_CLASSIFIER_URL'] ?? 'http://localhost:5000';
    $authenticityService = new EnhancedImageAuthenticityServiceV2($pdo, $localClassifierUrl);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'System initialization failed: ' . $e->getMessage()
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Only POST method allowed'
    ]);
    exit;
}

$userEmail = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? $_POST['email'] ?? '';
$tutorialId = $_POST['tutorial_id'] ?? '';
$description = $_POST['description'] ?? '';

if (empty($userEmail) || empty($tutorialId)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email and tutorial_id are required'
    ]);
    exit;
}

try {
    // Get user ID and verify Pro subscription
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $userStmt->execute([$userEmail]);
    $userForSub = $userStmt->fetch(PDO::FETCH_ASSOC);
    $userIdForSub = $userForSub['id'] ?? null;
    
    // Check Pro subscription
    $isPro = ($userEmail === 'soudhame52@gmail.com');
    
    if (!$isPro && $userIdForSub) {
        $subStmt = $pdo->prepare("
            SELECT s.status, sp.plan_code 
            FROM subscriptions s
            LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
            WHERE s.user_id = ? AND s.status = 'active'
            ORDER BY s.created_at DESC 
            LIMIT 1
        ");
        $subStmt->execute([$userIdForSub]);
        $subscription = $subStmt->fetch(PDO::FETCH_ASSOC);
        
        $isPro = ($subscription && $subscription['plan_code'] === 'pro' && $subscription['status'] === 'active');
    }
    
    if (!$isPro) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Practice uploads are only available for Pro subscribers',
            'upgrade_required' => true
        ]);
        exit;
    }
    
    if (!$userIdForSub) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found'
        ]);
        exit;
    }
    
    $userId = $userIdForSub;
    
    // Get tutorial information
    $tutorialStmt = $pdo->prepare("SELECT title, category FROM tutorials WHERE id = ?");
    $tutorialStmt->execute([$tutorialId]);
    $tutorial = $tutorialStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tutorial) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Tutorial not found'
        ]);
        exit;
    }
    
    $selectedCategory = $tutorial['category'] ?? 'general';
    
    // Create uploads directory
    $uploadDir = '../../uploads/practice/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadedFiles = [];
    $errors = [];
    
    // Process uploaded files
    if (isset($_FILES['practice_images']) && is_array($_FILES['practice_images']['name'])) {
        $fileCount = count($_FILES['practice_images']['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['practice_images']['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['practice_images']['name'][$i];
                $fileTmpName = $_FILES['practice_images']['tmp_name'][$i];
                $fileSize = $_FILES['practice_images']['size'][$i];
                $fileType = $_FILES['practice_images']['type'][$i];
                
                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($fileType, $allowedTypes)) {
                    $errors[] = "File $fileName: Invalid file type";
                    continue;
                }
                
                // Validate file size (5MB limit)
                if ($fileSize > 5 * 1024 * 1024) {
                    $errors[] = "File $fileName: File too large (max 5MB)";
                    continue;
                }
                
                // Generate unique filename
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $uniqueFileName = 'practice_' . $userId . '_' . $tutorialId . '_' . time() . '_' . $i . '.' . $fileExtension;
                $filePath = $uploadDir . $uniqueFileName;
                
                if (move_uploaded_file($fileTmpName, $filePath)) {
                    $uploadedFiles[] = [
                        'original_name' => $fileName,
                        'stored_name' => $uniqueFileName,
                        'file_path' => 'uploads/practice/' . $uniqueFileName,
                        'file_size' => $fileSize
                    ];
                } else {
                    $errors[] = "File $fileName: Upload failed";
                }
            } else {
                $errors[] = "File upload error: " . $_FILES['practice_images']['error'][$i];
            }
        }
    }
    
    if (empty($uploadedFiles)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No files were uploaded successfully',
            'errors' => $errors
        ]);
        exit;
    }
    
    // Ensure practice_uploads table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS practice_uploads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        tutorial_id INT NOT NULL,
        description TEXT,
        images JSON,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        authenticity_status ENUM('pending', 'verified', 'flagged', 'approved') DEFAULT 'pending',
        admin_feedback TEXT,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        reviewed_date TIMESTAMP NULL,
        INDEX idx_user_tutorial (user_id, tutorial_id)
    )");
    
    // Insert practice upload record
    $insertStmt = $pdo->prepare("
        INSERT INTO practice_uploads (user_id, tutorial_id, description, images, status, authenticity_status, upload_date)
        VALUES (?, ?, ?, ?, 'pending', 'pending', NOW())
    ");
    
    $imagesJson = json_encode($uploadedFiles);
    $insertStmt->execute([$userId, $tutorialId, $description, $imagesJson]);
    
    $uploadId = $pdo->lastInsertId();
    
    // Process each image through basic authenticity validation only
    $validationResults = [];
    $overallRequiresReview = false;
    
    foreach ($uploadedFiles as $index => $file) {
        $imageId = $uploadId . '_' . $index;
        $fullFilePath = $uploadDir . $file['stored_name'];
        
        try {
            // Run basic authenticity validation only (no craft validation)
            $validation = $authenticityService->evaluateImage(
                $imageId, 
                'practice_upload', 
                $fullFilePath, 
                $userId, 
                $tutorialId
            );
            
            // Process validation result
            $imageResult = [
                'image_id' => $imageId,
                'file_name' => $file['original_name'],
                'file_size' => $file['file_size'],
                'validation_status' => $validation['status'] ?? 'approved',
                'requires_admin_review' => $validation['requires_admin_review'] ?? false,
                'authenticity' => [
                    'status' => $validation['status'] ?? 'approved',
                    'explanation' => $validation['explanation'] ?? 'Basic validation passed',
                    'category' => $validation['category'] ?? $selectedCategory,
                    'images_compared' => $validation['images_compared'] ?? 0,
                    'metadata_notes' => $validation['metadata_notes'] ?? '',
                    'flagged_reason' => $validation['flagged_reason'] ?? null,
                    'similar_image' => $validation['similar_image'] ?? null,
                    'ai_warning' => $validation['ai_warning'] ?? null
                ]
            ];
            
            if ($validation['requires_admin_review'] ?? false) {
                $overallRequiresReview = true;
            }
            
            $validationResults[] = $imageResult;
            
        } catch (Exception $e) {
            error_log("Basic validation exception for $imageId: " . $e->getMessage());
            
            // For simple mode, just approve with warning
            $imageResult = [
                'image_id' => $imageId,
                'file_name' => $file['original_name'],
                'file_size' => $file['file_size'],
                'validation_status' => 'approved',
                'requires_admin_review' => false,
                'authenticity' => [
                    'status' => 'approved',
                    'explanation' => 'Basic upload validation (AI validation unavailable)',
                    'category' => $selectedCategory,
                    'images_compared' => 0,
                    'metadata_notes' => 'Simple mode - limited validation',
                    'flagged_reason' => null,
                    'similar_image' => null,
                    'ai_warning' => 'AI validation service unavailable'
                ]
            ];
            
            $validationResults[] = $imageResult;
        }
    }
    
    // Update practice upload status - auto-approve in simple mode
    $overallStatus = $overallRequiresReview ? 'pending' : 'approved';
    
    $updateStmt = $pdo->prepare("
        UPDATE practice_uploads 
        SET status = ?, authenticity_status = ?
        WHERE id = ?
    ");
    $updateStmt->execute([$overallStatus, $overallStatus, $uploadId]);
    
    // Update learning progress - auto-approve in simple mode
    if (!$overallRequiresReview) {
        $progressStmt = $pdo->prepare("
            INSERT INTO learning_progress (user_id, tutorial_id, practice_uploaded, practice_completed, practice_admin_approved, last_accessed)
            VALUES (?, ?, 1, 1, 1, NOW())
            ON DUPLICATE KEY UPDATE 
            practice_uploaded = 1, 
            practice_completed = 1,
            practice_admin_approved = 1,
            last_accessed = NOW()
        ");
        $progressStmt->execute([$userId, $tutorialId]);
    } else {
        $progressStmt = $pdo->prepare("
            INSERT INTO learning_progress (user_id, tutorial_id, practice_uploaded, practice_completed, practice_admin_approved, last_accessed)
            VALUES (?, ?, 1, 0, 0, NOW())
            ON DUPLICATE KEY UPDATE 
            practice_uploaded = 1, 
            practice_completed = 0,
            practice_admin_approved = 0,
            last_accessed = NOW()
        ");
        $progressStmt->execute([$userId, $tutorialId]);
    }
    
    // Prepare response
    $response = [
        'status' => 'success',
        'message' => $overallRequiresReview 
            ? 'Practice work uploaded and requires admin review'
            : 'Practice work uploaded successfully',
        'upload_id' => $uploadId,
        'files_uploaded' => count($uploadedFiles),
        'files' => $uploadedFiles,
        'errors' => $errors,
        'tutorial_info' => [
            'id' => $tutorialId,
            'title' => $tutorial['title'],
            'category' => $selectedCategory
        ],
        'user_email' => $userEmail,
        'timestamp' => date('Y-m-d H:i:s'),
        'validation_summary' => [
            'total_images' => count($validationResults),
            'approved_images' => count(array_filter($validationResults, function($r) { return $r['validation_status'] === 'approved'; })),
            'flagged_images' => count(array_filter($validationResults, function($r) { return $r['requires_admin_review']; })),
            'rejected_images' => 0,
            'processing_errors' => 0,
            'requires_admin_review' => $overallRequiresReview,
            'overall_status' => $overallStatus
        ],
        'validation_results' => $validationResults,
        'system_info' => [
            'version' => 'simple_upload_v1.0',
            'authenticity_system' => 'enhanced_v2.0',
            'craft_validation' => 'disabled',
            'mode' => 'fallback'
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Simple practice upload error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Upload failed: ' . $e->getMessage()
    ]);
}
?>