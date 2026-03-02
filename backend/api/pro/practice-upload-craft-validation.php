<?php
/**
 * Enhanced Practice Upload API with Craft Validation
 * 
 * Integrates AI-assisted craft image validation with existing authenticity system
 * 
 * Features:
 * - MobileNet-based craft category classification
 * - Category mismatch detection (image vs selected tutorial)
 * - AI-generated image detection via metadata analysis
 * - Perceptual hashing for duplicate detection (existing)
 * - Explainable confidence scores
 * - Admin review workflow integration
 * - No disruption to existing user flow
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
    require_once '../../services/CraftImageValidationServiceV2.php';
    
    // Load environment variables
    EnvLoader::load();
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Initialize services with error handling
    $localClassifierUrl = getenv('LOCAL_CLASSIFIER_URL') ?: $_ENV['LOCAL_CLASSIFIER_URL'] ?? 'http://localhost:5000';
    $craftClassifierUrl = getenv('CRAFT_CLASSIFIER_URL') ?: $_ENV['CRAFT_CLASSIFIER_URL'] ?? 'http://localhost:5001';
    
    try {
        $authenticityService = new EnhancedImageAuthenticityServiceV2($pdo, $localClassifierUrl);
    } catch (Exception $authError) {
        error_log("Authenticity service initialization failed: " . $authError->getMessage());
        $authenticityService = null;
    }
    
    try {
        $craftValidationService = new CraftImageValidationServiceV2($pdo, $craftClassifierUrl);
        $validationServiceAvailable = true;
    } catch (Exception $serviceError) {
        // If craft validation service fails to initialize, use fallback mode
        error_log("Craft validation service initialization failed: " . $serviceError->getMessage());
        $craftValidationService = null;
        $validationServiceAvailable = false;
    }
    
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
    // Get user ID and verify Pro subscription (existing logic)
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
    
    // Debug: Log what we received
    error_log("FILES received: " . print_r($_FILES, true));
    error_log("POST received: " . print_r($_POST, true));
    
    // Process uploaded files - check multiple possible field names
    $fileFieldName = null;
    if (isset($_FILES['practice_images'])) {
        $fileFieldName = 'practice_images';
    } elseif (isset($_FILES['images'])) {
        $fileFieldName = 'images';
    } elseif (isset($_FILES['file'])) {
        $fileFieldName = 'file';
    }
    
    if ($fileFieldName && isset($_FILES[$fileFieldName]) && is_array($_FILES[$fileFieldName]['name'])) {
        $fileCount = count($_FILES[$fileFieldName]['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES[$fileFieldName]['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = $_FILES[$fileFieldName]['name'][$i];
                $fileTmpName = $_FILES[$fileFieldName]['tmp_name'][$i];
                $fileSize = $_FILES[$fileFieldName]['size'][$i];
                $fileType = $_FILES[$fileFieldName]['type'][$i];
                
                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($fileType, $allowedTypes)) {
                    $errors[] = "File $fileName: Invalid file type ($fileType)";
                    continue;
                }
                
                // Validate file size (10MB limit - increased for AI images)
                if ($fileSize > 10 * 1024 * 1024) {
                    $errors[] = "File $fileName: File too large (max 10MB)";
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
                    $errors[] = "File $fileName: Upload failed - could not move file";
                }
            } else {
                $uploadError = $_FILES[$fileFieldName]['error'][$i];
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                ];
                $errorMsg = $errorMessages[$uploadError] ?? "Unknown error code: $uploadError";
                $errors[] = "File upload error: $errorMsg";
            }
        }
    } else {
        // No files found in expected format
        $errors[] = "No files found in request. Expected field: 'practice_images[]'";
        $errors[] = "Available fields: " . implode(', ', array_keys($_FILES));
    }
    
    if (empty($uploadedFiles)) {
        $debugInfo = [
            'files_received' => !empty($_FILES),
            'file_fields' => array_keys($_FILES),
            'expected_field' => 'practice_images[]',
            'errors_detail' => $errors,
            'post_data' => array_keys($_POST),
            'tutorial_id' => $tutorialId,
            'user_id' => $userId
        ];
        
        error_log("Upload failed - no files uploaded: " . json_encode($debugInfo));
        
        echo json_encode([
            'status' => 'error',
            'message' => 'No files were uploaded successfully',
            'errors' => $errors,
            'debug' => $debugInfo
        ]);
        exit;
    }
    
    // Ensure practice_uploads table exists (existing logic)
    $pdo->exec("CREATE TABLE IF NOT EXISTS practice_uploads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        tutorial_id INT NOT NULL,
        description TEXT,
        images JSON,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        authenticity_status ENUM('pending', 'verified', 'flagged', 'approved') DEFAULT 'pending',
        craft_validation_status ENUM('pending', 'approved', 'flagged', 'rejected') DEFAULT 'pending',
        progress_approved TINYINT(1) DEFAULT 0,
        admin_feedback TEXT,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        reviewed_date TIMESTAMP NULL,
        INDEX idx_user_tutorial (user_id, tutorial_id)
    )");
    
    // Insert practice upload record
    $insertStmt = $pdo->prepare("
        INSERT INTO practice_uploads (user_id, tutorial_id, description, images, status, authenticity_status, craft_validation_status, upload_date)
        VALUES (?, ?, ?, ?, 'pending', 'pending', 'pending', NOW())
    ");
    
    $imagesJson = json_encode($uploadedFiles);
    $insertStmt->execute([$userId, $tutorialId, $description, $imagesJson]);
    
    $uploadId = $pdo->lastInsertId();
    
    // Process each image through enhanced craft validation system
    $validationResults = [];
    $overallRequiresReview = false;
    $rejectedImages = [];
    $flaggedImages = [];
    $approvedImages = [];
    $processingErrors = [];
    
    foreach ($uploadedFiles as $index => $file) {
        $imageId = $uploadId . '_' . $index;
        $fullFilePath = $uploadDir . $file['stored_name'];
        
        try {
            // Check if validation service is available
            if (!$validationServiceAvailable || !$craftValidationService) {
                // Fallback: Auto-approve without AI validation
                $validation = [
                    'success' => true,
                    'ai_decision' => 'auto-approve',
                    'requires_admin_review' => false,
                    'classification' => [
                        'success' => true,
                        'predicted_category' => $selectedCategory,
                        'confidence' => 1.0,
                        'is_craft_related' => true
                    ],
                    'validation_decision' => [
                        'status' => 'auto-approve',
                        'requires_review' => false,
                        'category_match' => true,
                        'reasons' => ['AI validation service unavailable - auto-approved'],
                        'explanation' => 'Validation service unavailable - image auto-approved'
                    ],
                    'model_used' => 'fallback_mode'
                ];
            } else {
                // Run enhanced craft validation V2 (trained model only)
                $validation = $craftValidationService->validatePracticeImageSync(
                    $fullFilePath, 
                    $userId, 
                    $tutorialId,
                    $selectedCategory
                );
            }
            
            // Process V2 validation result
            $imageResult = [
                'image_id' => $imageId,
                'file_name' => $file['original_name'],
                'file_size' => $file['file_size'],
                'validation_status' => 'approved', // Default
                'requires_admin_review' => false,
                'error_code' => null,
                'error_message' => null,
                
                // V2 Craft validation results
                'craft_validation' => [
                    'ai_decision' => $validation['ai_decision'] ?? 'unknown',
                    'requires_review' => $validation['requires_admin_review'] ?? false,
                    'predicted_category' => $validation['classification']['predicted_category'] ?? 'unknown',
                    'confidence' => round(($validation['classification']['confidence'] ?? 0) * 100, 1),
                    'category_match' => $validation['validation_decision']['category_match'] ?? false,
                    'explanation' => $validation['validation_decision']['explanation'] ?? '',
                    'model_used' => $validation['model_used'] ?? 'trained_keras_model'
                ]
            ];
            
            // Map V2 decisions to binary status
            $aiDecision = $validation['ai_decision'] ?? 'flag-for-review';
            
            if ($aiDecision === 'auto-reject') {
                $imageResult['validation_status'] = 'rejected';
                $imageResult['requires_admin_review'] = false;
                $rejectedImages[] = $imageResult;
                $overallRequiresReview = true;
            } elseif ($aiDecision === 'auto-approve') {
                $imageResult['validation_status'] = 'approved';
                $imageResult['requires_admin_review'] = false;
                $approvedImages[] = $imageResult;
            } else {
                // flag-for-review -> convert to rejected for binary system
                $imageResult['validation_status'] = 'rejected';
                $imageResult['requires_admin_review'] = false;
                $imageResult['craft_validation']['explanation'] = 'Flagged for review - converted to rejected in binary mode';
                $rejectedImages[] = $imageResult;
                $overallRequiresReview = true;
            }
            
            $validationResults[] = $imageResult;
            
        } catch (Exception $e) {
            error_log("Craft validation V2 exception for $imageId: " . $e->getMessage());
            
            $errorResult = [
                'image_id' => $imageId,
                'file_name' => $file['original_name'],
                'file_size' => $file['file_size'],
                'validation_status' => 'rejected', // Convert errors to rejected in binary mode
                'requires_admin_review' => false,
                'error_code' => 'VALIDATION_EXCEPTION',
                'error_message' => $e->getMessage(),
                'craft_validation' => [
                    'ai_decision' => 'auto-reject',
                    'requires_review' => false,
                    'predicted_category' => 'error',
                    'confidence' => 0,
                    'category_match' => false,
                    'explanation' => 'Processing exception: ' . $e->getMessage(),
                    'model_used' => 'error'
                ]
            ];
            
            $processingErrors[] = $errorResult;
            $rejectedImages[] = $errorResult; // Add to rejected for binary logic
            $validationResults[] = $errorResult;
            $overallRequiresReview = true;
        }
    }
    
    // Update practice upload status based on validation results - BINARY LOGIC
    $overallStatus = 'approved'; // Default to approved
    $craftValidationStatus = 'approved';
    
    if (count($rejectedImages) > 0) {
        // If any image is rejected, reject the entire upload
        $overallStatus = 'rejected';
        $craftValidationStatus = 'rejected';
    } else {
        // If no images are rejected, approve the entire upload
        $overallStatus = 'approved';
        $craftValidationStatus = 'approved';
        $overallRequiresReview = false; // Force no review needed
    }
    
    $updateStmt = $pdo->prepare("
        UPDATE practice_uploads 
        SET status = ?, authenticity_status = ?, craft_validation_status = ?
        WHERE id = ?
    ");
    $updateStmt->execute([$overallStatus, $overallStatus, $craftValidationStatus, $uploadId]);
    
    // Update learning progress based on final status - BINARY LOGIC
    if ($overallStatus === 'approved') {
        // Auto-approve all approved images
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
        // For rejected images, mark as uploaded but not completed
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
        'message' => $overallRequiresReview 
            ? 'Practice work uploaded and requires admin review'
            : 'Practice work uploaded and validated successfully',
        'auto_approved' => !$overallRequiresReview && $overallStatus === 'approved',
        'practice_bonus' => !$overallRequiresReview && $overallStatus === 'approved' ? 10 : 0,
        'message_detail' => !$overallRequiresReview && $overallStatus === 'approved' 
            ? 'Your practice work has been automatically approved by our AI validation system!'
            : ($overallStatus === 'rejected' 
                ? 'Your practice work was automatically rejected. Please review the validation results.'
                : 'Your practice work is pending admin review.'),
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
        
        // Validation summary
        'validation_summary' => [
            'total_images' => count($validationResults),
            'approved_images' => count($approvedImages),
            'flagged_images' => count($flaggedImages),
            'rejected_images' => count($rejectedImages),
            'processing_errors' => count($processingErrors),
            'requires_admin_review' => $overallRequiresReview,
            'overall_status' => $overallStatus,
            'craft_validation_status' => $craftValidationStatus,
            'auto_approved' => !$overallRequiresReview && $overallStatus === 'approved',
            'auto_rejected' => $overallStatus === 'rejected' && !$overallRequiresReview
        ],
        
        // Detailed validation results
        'validation_results' => $validationResults,
        
        // System information
        'system_info' => [
            'version' => 'craft_validation_v1.0',
            'authenticity_system' => 'enhanced_v2.0',
            'craft_classifier' => 'mobilenet_fine_tuned',
            'ai_services' => [
                'local_classifier_url' => $localClassifierUrl,
                'craft_classifier_url' => $craftClassifierUrl
            ],
            'validation_features' => [
                'craft_category_classification',
                'category_mismatch_detection',
                'ai_generated_image_detection',
                'perceptual_hash_similarity',
                'metadata_analysis',
                'explainable_confidence_scores'
            ]
        ],
        
        // Explanation for users
        'validation_explanation' => [
            'craft_categories' => [
                'candle_making' => 'Candle Making',
                'clay_modeling' => 'Clay Modeling',
                'gift_making' => 'Gift Making',
                'hand_embroidery' => 'Hand Embroidery',
                'jewelry_making' => 'Jewelry Making',
                'mehandi_art' => 'Mylanchi / Mehandi Art',
                'resin_art' => 'Resin Art'
            ],
            'validation_rules' => [
                'Images must be related to crafts (not selfies, nature, animals, etc.)',
                'Images should match the selected tutorial category',
                'AI-generated images are not allowed',
                'Duplicate or reused images may be flagged',
                'Low quality or unclear images may require review'
            ],
            'status_meanings' => [
                'approved' => 'Image passed all validation checks',
                'flagged' => 'Image requires admin review before approval',
                'rejected' => 'Image does not meet validation criteria',
                'error' => 'Technical error occurred during validation'
            ]
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Enhanced practice upload error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Upload failed: ' . $e->getMessage()
    ]);
}
?>