<?php
/**
 * Enhanced Practice Upload API with Full AI Validation Pipeline
 * 
 * Implements comprehensive AI-assisted validation before database insertion
 * Designed for academic demonstration and research purposes
 * 
 * Features:
 * - Pre-upload AI validation (no pending states)
 * - Explainable AI decision making
 * - Automatic approval/rejection based on AI analysis
 * - Comprehensive logging for research
 * - Academic-grade documentation
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email, X-Request-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Generate unique request ID for tracking
$requestId = 'req_' . uniqid() . '_' . time();
$startTime = microtime(true);

// Log request start
error_log("[$requestId] Enhanced Practice Upload API - Request started");

try {
    require_once '../../config/database.php';
    require_once '../../config/env-loader.php';
    require_once '../../services/AIValidationPipeline.php';
    
    // Load environment variables
    EnvLoader::load();
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Initialize AI validation pipeline
    $aiValidationPipeline = new AIValidationPipeline($pdo, null, [
        'enable_strict_validation' => true,
        'enable_duplicate_detection' => true,
        'enable_ai_generation_detection' => true,
        'log_decisions' => true,
        'academic_mode' => true
    ]);
    
    error_log("[$requestId] AI Validation Pipeline initialized");
    
} catch (Exception $e) {
    error_log("[$requestId] System initialization failed: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'error_code' => 'SYSTEM_INITIALIZATION_FAILED',
        'message' => 'System initialization failed: ' . $e->getMessage(),
        'request_id' => $requestId,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'error_code' => 'METHOD_NOT_ALLOWED',
        'message' => 'Only POST method allowed',
        'request_id' => $requestId
    ]);
    exit;
}

// Extract request parameters
$userEmail = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? $_POST['email'] ?? '';
$tutorialId = $_POST['tutorial_id'] ?? '';
$description = $_POST['description'] ?? '';

if (empty($userEmail) || empty($tutorialId)) {
    echo json_encode([
        'status' => 'error',
        'error_code' => 'MISSING_REQUIRED_PARAMETERS',
        'message' => 'Email and tutorial_id are required',
        'request_id' => $requestId
    ]);
    exit;
}

error_log("[$requestId] Processing upload for user: $userEmail, tutorial: $tutorialId");

try {
    // Step 1: User authentication and subscription verification
    error_log("[$requestId] Step 1: User authentication and subscription verification");
    
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $userStmt->execute([$userEmail]);
    $userForSub = $userStmt->fetch(PDO::FETCH_ASSOC);
    $userIdForSub = $userForSub['id'] ?? null;
    
    // Check Pro subscription (simplified for demo - in production, implement proper subscription check)
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
            'error_code' => 'SUBSCRIPTION_REQUIRED',
            'message' => 'Practice uploads are only available for Pro subscribers',
            'upgrade_required' => true,
            'request_id' => $requestId
        ]);
        exit;
    }
    
    if (!$userIdForSub) {
        echo json_encode([
            'status' => 'error',
            'error_code' => 'USER_NOT_FOUND',
            'message' => 'User not found',
            'request_id' => $requestId
        ]);
        exit;
    }
    
    $userId = $userIdForSub;
    error_log("[$requestId] User authenticated: ID $userId, Pro status: " . ($isPro ? 'Yes' : 'No'));
    
    // Step 2: Tutorial validation and category extraction
    error_log("[$requestId] Step 2: Tutorial validation and category extraction");
    
    $tutorialStmt = $pdo->prepare("SELECT title, category, description FROM tutorials WHERE id = ?");
    $tutorialStmt->execute([$tutorialId]);
    $tutorial = $tutorialStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tutorial) {
        echo json_encode([
            'status' => 'error',
            'error_code' => 'TUTORIAL_NOT_FOUND',
            'message' => 'Tutorial not found',
            'request_id' => $requestId
        ]);
        exit;
    }
    
    $selectedCategory = $tutorial['category'] ?? 'general';
    error_log("[$requestId] Tutorial validated: {$tutorial['title']}, Category: $selectedCategory");
    
    // Step 3: File upload processing
    error_log("[$requestId] Step 3: File upload processing");
    
    $uploadDir = '../../uploads/practice/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadedFiles = [];
    $uploadErrors = [];
    
    if (isset($_FILES['practice_images']) && is_array($_FILES['practice_images']['name'])) {
        $fileCount = count($_FILES['practice_images']['name']);
        error_log("[$requestId] Processing $fileCount uploaded files");
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['practice_images']['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['practice_images']['name'][$i];
                $fileTmpName = $_FILES['practice_images']['tmp_name'][$i];
                $fileSize = $_FILES['practice_images']['size'][$i];
                $fileType = $_FILES['practice_images']['type'][$i];
                
                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($fileType, $allowedTypes)) {
                    $uploadErrors[] = "File $fileName: Invalid file type";
                    continue;
                }
                
                // Validate file size (10MB limit)
                if ($fileSize > 10 * 1024 * 1024) {
                    $uploadErrors[] = "File $fileName: File too large (max 10MB)";
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
                        'file_path' => $filePath,
                        'relative_path' => 'uploads/practice/' . $uniqueFileName,
                        'file_size' => $fileSize,
                        'file_type' => $fileType
                    ];
                    error_log("[$requestId] File uploaded successfully: $fileName -> $uniqueFileName");
                } else {
                    $uploadErrors[] = "File $fileName: Upload failed";
                }
            } else {
                $uploadErrors[] = "File upload error: " . $_FILES['practice_images']['error'][$i];
            }
        }
    }
    
    if (empty($uploadedFiles)) {
        echo json_encode([
            'status' => 'error',
            'error_code' => 'NO_FILES_UPLOADED',
            'message' => 'No files were uploaded successfully',
            'errors' => $uploadErrors,
            'request_id' => $requestId
        ]);
        exit;
    }
    
    error_log("[$requestId] Successfully uploaded " . count($uploadedFiles) . " files");
    
    // Step 4: AI Validation Pipeline - Process each image BEFORE database insertion
    error_log("[$requestId] Step 4: AI Validation Pipeline - Pre-insertion validation");
    
    $validationResults = [];
    $autoApprovedImages = [];
    $autoRejectedImages = [];
    $aiFlaggedImages = [];
    $processingErrors = [];
    
    foreach ($uploadedFiles as $index => $file) {
        $imageValidationId = $requestId . '_img_' . $index;
        error_log("[$requestId] Validating image $index: {$file['original_name']}");
        
        try {
            // Prepare image data for AI validation
            $imageData = [
                'user_id' => $userId,
                'tutorial_id' => $tutorialId,
                'selected_category' => $selectedCategory,
                'image_path' => $file['file_path'],
                'original_filename' => $file['original_name'],
                'file_size' => $file['file_size']
            ];
            
            // Run comprehensive AI validation
            $validationStart = microtime(true);
            $validation = $aiValidationPipeline->validatePracticeUpload($imageData);
            $validationTime = microtime(true) - $validationStart;
            
            // Process validation result
            $finalStatus = $validation['final_decision']['status'];
            $explanation = $validation['final_decision']['explanation'];
            $flags = $validation['final_decision']['flags'] ?? [];
            
            $imageResult = [
                'image_index' => $index,
                'validation_id' => $validation['validation_id'],
                'file_name' => $file['original_name'],
                'file_size' => $file['file_size'],
                'final_status' => $finalStatus,
                'explanation' => $explanation,
                'flags' => $flags,
                'processing_time' => $validationTime,
                'confidence_scores' => $validation['confidence_scores'] ?? [],
                'pipeline_stages' => $validation['pipeline_stages'] ?? [],
                
                // Academic research data
                'academic_insights' => [
                    'classification_details' => $validation['pipeline_stages']['classification'] ?? [],
                    'category_matching' => $validation['pipeline_stages']['category_matching'] ?? [],
                    'ai_detection' => $validation['pipeline_stages']['ai_detection'] ?? [],
                    'duplicate_detection' => $validation['pipeline_stages']['duplicate_detection'] ?? [],
                    'decision_factors' => $validation['pipeline_stages']['decision_engine']['decision_factors'] ?? []
                ]
            ];
            
            // Categorize results based on AI decision
            switch ($finalStatus) {
                case AIValidationPipeline::STATUS_AUTO_APPROVED:
                    $autoApprovedImages[] = $imageResult;
                    error_log("[$requestId] Image $index: AUTO-APPROVED - $explanation");
                    break;
                    
                case AIValidationPipeline::STATUS_AUTO_REJECTED:
                    $autoRejectedImages[] = $imageResult;
                    error_log("[$requestId] Image $index: AUTO-REJECTED - $explanation");
                    break;
                    
                case AIValidationPipeline::STATUS_AI_FLAGGED:
                    $aiFlaggedImages[] = $imageResult;
                    error_log("[$requestId] Image $index: AI-FLAGGED - $explanation");
                    break;
                    
                default:
                    $processingErrors[] = $imageResult;
                    error_log("[$requestId] Image $index: PROCESSING ERROR - $explanation");
                    break;
            }
            
            $validationResults[] = $imageResult;
            
        } catch (Exception $e) {
            error_log("[$requestId] Validation exception for image $index: " . $e->getMessage());
            
            $errorResult = [
                'image_index' => $index,
                'validation_id' => $imageValidationId,
                'file_name' => $file['original_name'],
                'file_size' => $file['file_size'],
                'final_status' => AIValidationPipeline::STATUS_PROCESSING_ERROR,
                'explanation' => 'Validation processing error: ' . $e->getMessage(),
                'flags' => ['processing_exception'],
                'processing_time' => 0,
                'error_details' => $e->getMessage()
            ];
            
            $processingErrors[] = $errorResult;
            $validationResults[] = $errorResult;
        }
    }
    
    // Step 5: Determine overall upload status based on AI validation results
    error_log("[$requestId] Step 5: Determining overall upload status");
    
    $overallStatus = 'mixed_results';
    $overallExplanation = '';
    $requiresAdminReview = false;
    
    if (count($autoRejectedImages) > 0) {
        $overallStatus = 'rejected';
        $overallExplanation = 'One or more images were automatically rejected by AI validation';
        $requiresAdminReview = false; // Rejected images don't need admin review
    } elseif (count($processingErrors) > 0) {
        $overallStatus = 'processing_error';
        $overallExplanation = 'Processing errors occurred during validation';
        $requiresAdminReview = true;
    } elseif (count($aiFlaggedImages) > 0) {
        $overallStatus = 'ai_flagged';
        $overallExplanation = 'One or more images require manual admin review';
        $requiresAdminReview = true;
    } elseif (count($autoApprovedImages) === count($uploadedFiles)) {
        $overallStatus = 'auto_approved';
        $overallExplanation = 'All images passed AI validation and were automatically approved';
        $requiresAdminReview = false;
    }
    
    error_log("[$requestId] Overall status determined: $overallStatus");
    
    // Step 6: Database insertion based on AI validation results
    error_log("[$requestId] Step 6: Database insertion based on AI validation results");
    
    $uploadId = null;
    
    // Only insert into database if there are images to process
    if (count($autoApprovedImages) > 0 || count($aiFlaggedImages) > 0) {
        // Insert main upload record
        $insertStmt = $pdo->prepare("
            INSERT INTO practice_uploads_enhanced (
                user_id, tutorial_id, description, images, status, ai_validation_status,
                predicted_category, classification_confidence, category_match,
                duplicate_detected, ai_generated_detected, upload_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        // Calculate aggregate metrics
        $avgConfidence = 0.0;
        $categoryMatches = 0;
        $duplicatesDetected = 0;
        $aiGeneratedDetected = 0;
        
        foreach ($validationResults as $result) {
            if (isset($result['confidence_scores']['craft_classification'])) {
                $avgConfidence += $result['confidence_scores']['craft_classification'];
            }
            if (isset($result['academic_insights']['category_matching']['exact_match']) && 
                $result['academic_insights']['category_matching']['exact_match']) {
                $categoryMatches++;
            }
            if (isset($result['confidence_scores']['duplicate_similarity']) && 
                $result['confidence_scores']['duplicate_similarity'] > 0.9) {
                $duplicatesDetected++;
            }
            if (isset($result['academic_insights']['ai_detection']['is_ai_generated']) && 
                $result['academic_insights']['ai_detection']['is_ai_generated']) {
                $aiGeneratedDetected++;
            }
        }
        
        $avgConfidence = count($validationResults) > 0 ? $avgConfidence / count($validationResults) : 0.0;
        
        // Determine AI validation status
        $aiValidationStatus = 'passed';
        if (count($autoRejectedImages) > 0 || count($processingErrors) > 0) {
            $aiValidationStatus = 'failed';
        } elseif (count($aiFlaggedImages) > 0) {
            $aiValidationStatus = 'flagged';
        }
        
        $insertStmt->execute([
            $userId,
            $tutorialId,
            $description,
            json_encode($uploadedFiles),
            $overallStatus,
            $aiValidationStatus,
            $validationResults[0]['academic_insights']['classification_details']['predicted_category'] ?? null,
            $avgConfidence,
            $categoryMatches > 0 ? 1 : 0,
            $duplicatesDetected > 0 ? 1 : 0,
            $aiGeneratedDetected > 0 ? 1 : 0
        ]);
        
        $uploadId = $pdo->lastInsertId();
        error_log("[$requestId] Upload record created with ID: $uploadId");
        
        // Update learning progress for auto-approved submissions
        if ($overallStatus === 'auto_approved') {
            $progressStmt = $pdo->prepare("
                INSERT INTO learning_progress (
                    user_id, tutorial_id, practice_uploaded, practice_completed, 
                    practice_admin_approved, last_accessed
                ) VALUES (?, ?, 1, 1, 1, NOW())
                ON DUPLICATE KEY UPDATE 
                practice_uploaded = 1, 
                practice_completed = 1,
                practice_admin_approved = 1,
                last_accessed = NOW()
            ");
            $progressStmt->execute([$userId, $tutorialId]);
            error_log("[$requestId] Learning progress updated for auto-approved submission");
        }
    }
    
    // Step 7: Prepare comprehensive response for academic demonstration
    $processingTime = microtime(true) - $startTime;
    error_log("[$requestId] Total processing time: {$processingTime}s");
    
    $response = [
        'status' => 'success',
        'request_id' => $requestId,
        'message' => $overallExplanation,
        'upload_id' => $uploadId,
        'processing_time' => $processingTime,
        'timestamp' => date('Y-m-d H:i:s'),
        
        // Upload summary
        'upload_summary' => [
            'total_files' => count($uploadedFiles),
            'auto_approved' => count($autoApprovedImages),
            'auto_rejected' => count($autoRejectedImages),
            'ai_flagged' => count($aiFlaggedImages),
            'processing_errors' => count($processingErrors),
            'overall_status' => $overallStatus,
            'requires_admin_review' => $requiresAdminReview
        ],
        
        // Tutorial information
        'tutorial_info' => [
            'id' => $tutorialId,
            'title' => $tutorial['title'],
            'category' => $selectedCategory
        ],
        
        // User information
        'user_info' => [
            'email' => $userEmail,
            'user_id' => $userId,
            'subscription_status' => 'pro'
        ],
        
        // Detailed validation results for academic analysis
        'validation_results' => $validationResults,
        
        // AI pipeline performance metrics
        'ai_pipeline_metrics' => [
            'total_validation_time' => array_sum(array_column($validationResults, 'processing_time')),
            'average_validation_time' => count($validationResults) > 0 ? 
                array_sum(array_column($validationResults, 'processing_time')) / count($validationResults) : 0,
            'pipeline_stages_executed' => [
                'preprocessing', 'classification', 'category_matching', 
                'ai_detection', 'duplicate_detection', 'decision_engine'
            ],
            'confidence_score_distribution' => [
                'craft_classification' => array_column(array_column($validationResults, 'confidence_scores'), 'craft_classification'),
                'category_match' => array_column(array_column($validationResults, 'confidence_scores'), 'category_match'),
                'duplicate_similarity' => array_column(array_column($validationResults, 'confidence_scores'), 'duplicate_similarity')
            ]
        ],
        
        // System information for academic documentation
        'system_info' => [
            'api_version' => '3.0.0_academic',
            'ai_pipeline_version' => '1.0.0',
            'validation_features' => [
                'craft_category_classification',
                'category_mismatch_detection', 
                'ai_generated_image_detection',
                'perceptual_hash_duplicate_detection',
                'explainable_decision_making',
                'automatic_approval_rejection'
            ],
            'academic_features' => [
                'comprehensive_logging',
                'decision_traceability',
                'confidence_score_analysis',
                'pipeline_stage_breakdown',
                'performance_metrics'
            ]
        ],
        
        // Upload errors (if any)
        'upload_errors' => $uploadErrors
    ];
    
    // Add specific status messages based on results
    if ($overallStatus === 'auto_approved') {
        $response['success_message'] = 'All images passed AI validation and were automatically approved. Learning progress has been updated.';
    } elseif ($overallStatus === 'rejected') {
        $response['rejection_message'] = 'One or more images were automatically rejected by AI validation. Please review the validation results and upload appropriate craft images.';
    } elseif ($overallStatus === 'ai_flagged') {
        $response['flagged_message'] = 'Some images require manual admin review. Approved images have been processed, flagged images are pending review.';
    }
    
    error_log("[$requestId] Response prepared, sending to client");
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("[$requestId] Critical error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'error_code' => 'CRITICAL_PROCESSING_ERROR',
        'message' => 'Critical processing error: ' . $e->getMessage(),
        'request_id' => $requestId,
        'timestamp' => date('Y-m-d H:i:s'),
        'processing_time' => microtime(true) - $startTime
    ]);
}
?>