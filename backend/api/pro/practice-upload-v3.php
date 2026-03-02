<?php
/**
 * Practice Upload API V3 - Production Version
 * 
 * Enforces synchronous AI validation before database writes
 * Uses ONLY the trained craft_image_classifier.keras model
 * Implements strict auto-approve/auto-reject/flag decisions
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
    require_once '../../services/CraftImageValidationServiceV2.php';
    
    // Load environment variables
    EnvLoader::load();
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Initialize validation service
    $craftClassifierUrl = getenv('CRAFT_CLASSIFIER_URL') ?: $_ENV['CRAFT_CLASSIFIER_URL'] ?? 'http://localhost:5001';
    $validationService = new CraftImageValidationServiceV2($pdo, $craftClassifierUrl);
    
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
    
    // Get tutorial information including category
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
                        'full_path' => $filePath,
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
    
    // SYNCHRONOUS AI VALIDATION - Process each image BEFORE database writes
    $validationResults = [];
    $autoApprovedImages = [];
    $autoRejectedImages = [];
    $flaggedImages = [];
    $processingErrors = [];
    
    foreach ($uploadedFiles as $index => $file) {
        $imageId = 'temp_' . $userId . '_' . $tutorialId . '_' . time() . '_' . $index;
        
        try {
            // CRITICAL: Synchronous AI validation using trained model ONLY
            $validation = $validationService->validatePracticeImageSync(
                $file['full_path'], 
                $userId, 
                $tutorialId,
                $selectedCategory
            );
            
            if (!$validation['success']) {
                // Validation failed - treat as error
                $errorResult = [
                    'image_id' => $imageId,
                    'file_name' => $file['original_name'],
                    'file_size' => $file['file_size'],
                    'ai_decision' => 'flag-for-review',
                    'requires_admin_review' => true,
                    'error_code' => $validation['error_code'],
                    'error_message' => $validation['error_message'],
                    'validation' => $validation
                ];
                
                $processingErrors[] = $errorResult;
                $validationResults[] = $errorResult;
                continue;
            }
            
            // Process successful validation
            $imageResult = [
                'image_id' => $imageId,
                'file_name' => $file['original_name'],
                'file_size' => $file['file_size'],
                'ai_decision' => $validation['ai_decision'],
                'requires_admin_review' => $validation['requires_admin_review'],
                'predicted_category' => $validation['classification']['predicted_category'],
                'confidence' => $validation['classification']['confidence'],
                'category_match' => $validation['validation_decision']['category_match'],
                'confidence_level' => $validation['validation_decision']['confidence_level'],
                'decision_reasons' => $validation['validation_decision']['reasons'],
                'explanation' => $validation['validation_decision']['explanation'],
                'validation' => $validation
            ];
            
            // Categorize based on AI decision
            switch ($validation['ai_decision']) {
                case 'auto-approve':
                    $autoApprovedImages[] = $imageResult;
                    break;
                case 'auto-reject':
                    $autoRejectedImages[] = $imageResult;
                    break;
                case 'flag-for-review':
                default:
                    $flaggedImages[] = $imageResult;
                    break;
            }
            
            $validationResults[] = $imageResult;
            
        } catch (Exception $e) {
            error_log("Synchronous validation exception for $imageId: " . $e->getMessage());
            
            $errorResult = [
                'image_id' => $imageId,
                'file_name' => $file['original_name'],
                'file_size' => $file['file_size'],
                'ai_decision' => 'flag-for-review',
                'requires_admin_review' => true,
                'error_code' => 'VALIDATION_EXCEPTION',
                'error_message' => $e->getMessage(),
                'validation' => [
                    'success' => false,
                    'error_message' => $e->getMessage()
                ]
            ];
            
            $processingErrors[] = $errorResult;
            $validationResults[] = $errorResult;
        }
    }
    
    // Determine overall status based on AI decisions
    $overallStatus = 'pending';
    $requiresAdminReview = false;
    
    if (count($autoRejectedImages) > 0) {
        $overallStatus = 'rejected';
        $requiresAdminReview = true;
    } elseif (count($flaggedImages) > 0 || count($processingErrors) > 0) {
        $overallStatus = 'pending';
        $requiresAdminReview = true;
    } elseif (count($autoApprovedImages) === count($uploadedFiles)) {
        $overallStatus = 'approved';
        $requiresAdminReview = false;
    } else {
        $overallStatus = 'pending';
        $requiresAdminReview = true;
    }
    
    // DATABASE WRITES - Only after AI validation is complete
    
    // Ensure practice_uploads table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS practice_uploads_v3 (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        tutorial_id INT NOT NULL,
        description TEXT,
        images JSON,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        ai_validation_status ENUM('auto-approved', 'auto-rejected', 'flagged', 'error') DEFAULT 'flagged',
        requires_admin_review TINYINT(1) DEFAULT 1,
        progress_approved TINYINT(1) DEFAULT 0,
        admin_feedback TEXT,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        reviewed_date TIMESTAMP NULL,
        INDEX idx_user_tutorial (user_id, tutorial_id),
        INDEX idx_ai_validation (ai_validation_status),
        INDEX idx_requires_review (requires_admin_review)
    )");
    
    // Insert practice upload record with AI validation results
    $insertStmt = $pdo->prepare("
        INSERT INTO practice_uploads_v3 (user_id, tutorial_id, description, images, status, ai_validation_status, requires_admin_review, progress_approved, upload_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $imagesJson = json_encode($uploadedFiles);
    $aiValidationStatus = 'flagged';
    
    if (count($autoApprovedImages) === count($uploadedFiles)) {
        $aiValidationStatus = 'auto-approved';
    } elseif (count($autoRejectedImages) > 0) {
        $aiValidationStatus = 'auto-rejected';
    } elseif (count($processingErrors) > 0) {
        $aiValidationStatus = 'error';
    }
    
    $progressApproved = ($overallStatus === 'approved' && !$requiresAdminReview) ? 1 : 0;
    
    $insertStmt->execute([
        $userId, 
        $tutorialId, 
        $description, 
        $imagesJson, 
        $overallStatus, 
        $aiValidationStatus,
        $requiresAdminReview ? 1 : 0,
        $progressApproved
    ]);
    
    $uploadId = $pdo->lastInsertId();
    
    // Store individual validation results
    foreach ($validationResults as $index => $result) {
        $finalImageId = $uploadId . '_' . $index;
        
        if (isset($result['validation']) && $result['validation']['success']) {
            $validationService->storeValidationResult(
                $finalImageId,
                'practice_upload',
                $result['validation'],
                $userId,
                $tutorialId
            );
        }
    }
    
    // Update learning progress based on AI decisions
    if ($overallStatus === 'approved' && !$requiresAdminReview) {
        // Auto-approved by AI - mark as completed
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
        // Requires review or rejected - mark as uploaded but not completed
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
    
    // Prepare comprehensive response
    $response = [
        'status' => 'success',
        'message' => generateResponseMessage($overallStatus, $requiresAdminReview, count($autoApprovedImages), count($autoRejectedImages), count($flaggedImages)),
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
        
        // AI Validation Summary
        'ai_validation_summary' => [
            'total_images' => count($validationResults),
            'auto_approved' => count($autoApprovedImages),
            'auto_rejected' => count($autoRejectedImages),
            'flagged_for_review' => count($flaggedImages),
            'processing_errors' => count($processingErrors),
            'overall_status' => $overallStatus,
            'ai_validation_status' => $aiValidationStatus,
            'requires_admin_review' => $requiresAdminReview,
            'progress_approved' => $progressApproved
        ],
        
        // Detailed validation results
        'validation_results' => $validationResults,
        
        // System information
        'system_info' => [
            'version' => 'practice_upload_v3.0_production',
            'ai_model' => 'trained_craft_image_classifier.keras',
            'validation_mode' => 'synchronous_before_database',
            'fallback_disabled' => true,
            'decision_types' => ['auto-approve', 'auto-reject', 'flag-for-review'],
            'craft_classifier_url' => $craftClassifierUrl
        ],
        
        // User guidance
        'user_guidance' => [
            'auto_approved' => 'Images automatically approved by AI - no admin review needed',
            'auto_rejected' => 'Images automatically rejected by AI - please upload different images',
            'flagged_for_review' => 'Images sent for admin review - you will be notified of the decision',
            'supported_categories' => array_values(CraftImageValidationServiceV2::CRAFT_CATEGORIES ?? [])
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Practice upload V3 error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Upload failed: ' . $e->getMessage()
    ]);
}

/**
 * Generate appropriate response message based on AI decisions
 */
function generateResponseMessage($overallStatus, $requiresAdminReview, $autoApproved, $autoRejected, $flagged) {
    if ($overallStatus === 'approved' && !$requiresAdminReview) {
        return "Practice work uploaded and automatically approved by AI! ($autoApproved images approved)";
    } elseif ($autoRejected > 0) {
        return "Some images were automatically rejected by AI. Please upload different images that match the tutorial category.";
    } elseif ($flagged > 0) {
        return "Practice work uploaded and sent for admin review. You will be notified of the decision.";
    } else {
        return "Practice work uploaded successfully.";
    }
}
?>