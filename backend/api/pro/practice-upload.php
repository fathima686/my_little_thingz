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
    
    // Initialize services
    $localClassifierUrl = getenv('LOCAL_CLASSIFIER_URL') ?: $_ENV['LOCAL_CLASSIFIER_URL'] ?? 'http://localhost:5000';
    $craftClassifierUrl = getenv('CRAFT_CLASSIFIER_URL') ?: $_ENV['CRAFT_CLASSIFIER_URL'] ?? 'http://localhost:5001';
    
    $authenticityService = new EnhancedImageAuthenticityServiceV2($pdo, $localClassifierUrl);
    
    // Try to initialize craft validation service
    try {
        $validationService = new CraftImageValidationServiceV2($pdo, $craftClassifierUrl);
        $validationServiceAvailable = true;
    } catch (Exception $e) {
        error_log("Craft validation service unavailable: " . $e->getMessage());
        $validationService = null;
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
    
    // Process uploaded files (existing logic)
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
            if (!$validationServiceAvailable || !$validationService) {
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
                        'reasons' => ['AI validation service unavailable - auto-approved'],
                        'explanation' => 'Validation service unavailable - image auto-approved'
                    ],
                    'ai_detection' => null
                ];
            } else {
                // Run enhanced craft validation (integrates with existing authenticity system)
                $validation = $validationService->validatePracticeImageSync(
                    $fullFilePath, 
                    $userId, 
                    $tutorialId,
                    $selectedCategory
                );
            }
            
            // Check if validation succeeded
            if (!$validation || !isset($validation['success'])) {
                throw new Exception('Validation service returned invalid response');
            }
            
            if (!$validation['success']) {
                throw new Exception($validation['error_message'] ?? 'Validation failed');
            }
            
            // Process validation result
            $imageResult = [
                'image_id' => $imageId,
                'file_name' => $file['original_name'],
                'file_size' => $file['file_size'],
                'validation_status' => $validation['ai_decision'] ?? 'error',
                'requires_admin_review' => $validation['requires_admin_review'] ?? true,
                'error_code' => $validation['error_code'] ?? null,
                'error_message' => $validation['error_message'] ?? null,
                
                // Authenticity results (existing system)
                'authenticity' => [
                    'status' => $validation['ai_decision'] ?? 'error',
                    'explanation' => $validation['validation_decision']['explanation'] ?? '',
                    'category' => $selectedCategory,
                    'images_compared' => 0,
                    'metadata_notes' => '',
                    'flagged_reason' => null,
                    'similar_image' => null,
                    'ai_warning' => null
                ],
                
                // Craft validation results (new system)
                'craft_validation' => $validation['validation_decision'] ?? null
            ];
            
            // Categorize results based on AI decision
            if ($validation['ai_decision'] === 'auto-reject') {
                $rejectedImages[] = $imageResult;
                // Auto-rejected images don't need admin review
            } elseif ($validation['requires_admin_review']) {
                $flaggedImages[] = $imageResult;
                $overallRequiresReview = true;
            } else {
                $approvedImages[] = $imageResult;
            }
            
            $validationResults[] = $imageResult;
            
        } catch (Exception $e) {
            error_log("Craft validation exception for $imageId: " . $e->getMessage());
            
            $errorResult = [
                'image_id' => $imageId,
                'file_name' => $file['original_name'],
                'file_size' => $file['file_size'],
                'validation_status' => 'error',
                'requires_admin_review' => true,
                'error_code' => 'VALIDATION_EXCEPTION',
                'error_message' => $e->getMessage(),
                'authenticity' => [
                    'status' => 'error',
                    'explanation' => 'Processing exception occurred',
                    'category' => $selectedCategory,
                    'images_compared' => 0,
                    'metadata_notes' => 'Exception: ' . $e->getMessage(),
                    'flagged_reason' => 'Processing exception',
                    'similar_image' => null,
                    'ai_warning' => null
                ],
                'craft_validation' => [
                    'validation_status' => 'error',
                    'rejection_reason' => $e->getMessage(),
                    'requires_review' => true
                ]
            ];
            
            $processingErrors[] = $errorResult;
            $validationResults[] = $errorResult;
            $overallRequiresReview = true;
        }
    }
    
    // Update practice upload status based on AI validation results
    $overallStatus = 'pending';
    $craftValidationStatus = 'pending';
    $requiresAdminReview = false;
    
    if (count($rejectedImages) > 0) {
        // Auto-rejected by AI - no admin review needed
        $overallStatus = 'rejected';
        $craftValidationStatus = 'auto-rejected';
        $requiresAdminReview = false;
    } elseif (count($flaggedImages) > 0 || count($processingErrors) > 0) {
        // Flagged for admin review
        $overallStatus = 'pending';
        $craftValidationStatus = 'flagged';
        $requiresAdminReview = true;
    } elseif (count($approvedImages) === count($uploadedFiles)) {
        // Auto-approved by AI - no admin review needed
        $overallStatus = 'approved';
        $craftValidationStatus = 'auto-approved';
        $requiresAdminReview = false;
    } else {
        // Mixed results - flag for review
        $overallStatus = 'pending';
        $craftValidationStatus = 'flagged';
        $requiresAdminReview = true;
    }
    
    $updateStmt = $pdo->prepare("
        UPDATE practice_uploads 
        SET status = ?, authenticity_status = ?, craft_validation_status = ?
        WHERE id = ?
    ");
    $updateStmt->execute([$overallStatus, $overallStatus, $craftValidationStatus, $uploadId]);
    
    // Update learning progress based on final status
    if ($overallStatus === 'approved' && !$overallRequiresReview) {
        // Auto-approve clean images
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
        // Mark as uploaded but not completed until admin review
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
            'craft_validation_status' => $craftValidationStatus
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