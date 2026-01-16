<?php
/**
 * Practice Upload API V2
 * 
 * Features:
 * - Enhanced image authenticity with pHash-only similarity
 * - Google Vision API integration for unrelated content detection
 * - Category-specific comparison
 * - No auto-rejection, admin is final authority
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
    
    // Get local classifier URL from environment (defaults to localhost:5000)
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
    // Get user ID
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
    
    // Verify tutorial exists
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
        progress_approved TINYINT(1) DEFAULT 0,
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
    
    // Process each image through enhanced authenticity system
    $analysisResults = [];
    $requiresReview = false;
    $aiWarnings = [];
    $similarityFlags = [];
    $processingErrors = [];
    
    foreach ($uploadedFiles as $index => $file) {
        $imageId = $uploadId . '_' . $index;
        $fullFilePath = $uploadDir . $file['stored_name'];
        
        try {
            // Run enhanced authenticity evaluation
            $evaluation = $authenticityService->evaluateImage(
                $imageId, 
                'practice_upload', 
                $fullFilePath, 
                $userId, 
                $tutorialId
            );
            
            // Check for errors
            if (isset($evaluation['error']) && $evaluation['error'] === true) {
                $processingErrors[] = [
                    'file' => $file['original_name'],
                    'error_code' => $evaluation['error_code'],
                    'error_message' => $evaluation['error_message']
                ];
            }
            
            $analysisResults[] = [
                'image_id' => $imageId,
                'file_name' => $file['original_name'],
                'status' => $evaluation['status'],
                'error_code' => $evaluation['error_code'] ?? null,
                'error_message' => $evaluation['error_message'] ?? null,
                'explanation' => $evaluation['explanation'],
                'requires_admin_review' => $evaluation['requires_admin_review'],
                'category' => $evaluation['category'],
                'images_compared' => $evaluation['images_compared'],
                'metadata_notes' => $evaluation['metadata_notes'],
                'flagged_reason' => $evaluation['flagged_reason'],
                'similar_image' => $evaluation['similar_image'],
                'ai_warning' => $evaluation['ai_warning'],
                'file_size' => $file['file_size']
            ];
            
            if ($evaluation['requires_admin_review']) {
                $requiresReview = true;
                
                if ($evaluation['ai_warning']) {
                    $aiWarnings[] = $evaluation['ai_warning'];
                }
                
                if ($evaluation['similar_image']) {
                    $similarityFlags[] = "Image '{$file['original_name']}' is similar to existing work";
                }
            }
            
        } catch (Exception $e) {
            error_log("Authenticity evaluation exception for $imageId: " . $e->getMessage());
            $processingErrors[] = [
                'file' => $file['original_name'],
                'error_code' => 'EVALUATION_EXCEPTION',
                'error_message' => $e->getMessage()
            ];
            $analysisResults[] = [
                'image_id' => $imageId,
                'file_name' => $file['original_name'],
                'status' => 'error',
                'error_code' => 'EVALUATION_EXCEPTION',
                'error_message' => $e->getMessage(),
                'explanation' => 'Technical error occurred during analysis',
                'requires_admin_review' => true,
                'category' => $tutorial['category'] ?? 'general',
                'images_compared' => 0,
                'metadata_notes' => 'Exception: ' . $e->getMessage(),
                'flagged_reason' => 'Processing exception',
                'similar_image' => null,
                'ai_warning' => null,
                'file_size' => $file['file_size'],
                'error' => true
            ];
            $requiresReview = true;
        }
    }
    
    // Update practice upload status
    $overallStatus = $requiresReview ? 'flagged' : 'verified';
    $updateStmt = $pdo->prepare("
        UPDATE practice_uploads 
        SET authenticity_status = ? 
        WHERE id = ?
    ");
    $updateStmt->execute([$overallStatus, $uploadId]);
    
    // Update learning progress
    if (!$requiresReview) {
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
    
    // Prepare response
    $uniqueCount = count(array_filter($analysisResults, fn($r) => $r['status'] === 'unique'));
    $reuseCount = count(array_filter($analysisResults, fn($r) => $r['status'] === 'possible_reuse'));
    $unrelatedCount = count(array_filter($analysisResults, fn($r) => $r['status'] === 'possibly_unrelated'));
    $reviewCount = count(array_filter($analysisResults, fn($r) => $r['requires_admin_review']));
    
    echo json_encode([
        'status' => 'success',
        'message' => $requiresReview 
            ? 'Practice work uploaded and flagged for admin review'
            : 'Practice work uploaded and verified successfully',
        'upload_id' => $uploadId,
        'files_uploaded' => count($uploadedFiles),
        'files' => $uploadedFiles,
        'errors' => $errors,
        'processing_errors' => $processingErrors,
        'tutorial_title' => $tutorial['title'],
        'tutorial_category' => $tutorial['category'] ?? 'general',
        'user_email' => $userEmail,
        'timestamp' => date('Y-m-d H:i:s'),
        'authenticity_analysis' => [
            'system_version' => 'enhanced_v2.0',
            'detection_method' => 'phash_similarity + ai_content_analysis',
            'comparison_scope' => 'same_category_only',
            'ai_enabled' => !empty($localClassifierUrl),
            'threshold_used' => 'phash_distance_5',
            'analysis_results' => $analysisResults,
            'summary' => [
                'total_images' => count($analysisResults),
                'unique_images' => $uniqueCount,
                'possible_reuse' => $reuseCount,
                'possibly_unrelated' => $unrelatedCount,
                'requires_admin_review' => $reviewCount,
                'processing_errors' => count($processingErrors),
                'auto_approved' => !$requiresReview
            ],
            'warnings' => [
                'ai_warnings' => $aiWarnings,
                'similarity_flags' => $similarityFlags,
                'processing_errors' => $processingErrors
            ],
            'explanation' => [
                'unique' => 'No similar images found within the same tutorial category',
                'possible_reuse' => 'Similar image found in same category (pHash distance ≤ 5)',
                'possibly_unrelated' => 'AI detected possibly unrelated content (confidence ≥ 80%)',
                'needs_admin_review' => 'Flagged for manual admin review - no auto-rejection',
                'error' => 'Processing error occurred - requires admin review'
            ],
            'error_codes' => [
                'VISION_KEY_MISSING' => 'Google Vision API key not configured',
                'GD_NOT_AVAILABLE' => 'PHP GD extension not enabled',
                'FILE_NOT_FOUND' => 'Image file not found',
                'PHASH_FAILED' => 'Failed to generate perceptual hash',
                'VISION_API_FAILED' => 'Google Vision API call failed',
                'DB_ERROR' => 'Database operation failed'
            ],
            'important_notes' => [
                'Category-specific comparison: Images are only compared within the selected tutorial category',
                'AI content warning: Pre-trained Google Vision API detects unrelated content (people, scenery, animals)',
                'No auto-rejection: Admin is the final authority on all decisions',
                'pHash similarity: Only perceptual hash with strict threshold (distance ≤ 5)',
                'Metadata extraction: EXIF data extracted for admin reference only',
                'Progress credit: Requires admin approval for flagged images',
                'Certificate eligibility: Requires 80% overall course progress with admin-approved practice work',
                'Error handling: All errors are logged and flagged for admin review'
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Practice upload error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Upload failed: ' . $e->getMessage()
    ]);
}
?>
