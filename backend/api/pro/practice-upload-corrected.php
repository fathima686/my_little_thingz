<?php
/**
 * Corrected Practice Upload API
 * Uses simplified authenticity system with clear, explainable results
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
    require_once '../../services/SimplifiedImageAuthenticityService.php';
    
    $database = new Database();
    $pdo = $database->getConnection();
    $authenticityService = new SimplifiedImageAuthenticityService($pdo);
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
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found'
        ]);
        exit;
    }
    
    $userId = $user['id'];
    
    // Check Pro subscription
    $isPro = ($userEmail === 'soudhame52@gmail.com'); // Admin override
    
    if (!$isPro) {
        $subStmt = $pdo->prepare("
            SELECT s.status, sp.plan_code 
            FROM subscriptions s
            LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
            WHERE s.user_id = ? AND s.status = 'active'
            ORDER BY s.created_at DESC 
            LIMIT 1
        ");
        $subStmt->execute([$userId]);
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
                        'file_size' => $fileSize,
                        'index' => $i
                    ];
                } else {
                    $errors[] = "File $fileName: Upload failed";
                }
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
    
    // Create practice upload record
    $insertStmt = $pdo->prepare("
        INSERT INTO practice_uploads (user_id, tutorial_id, description, images, status, authenticity_status, upload_date)
        VALUES (?, ?, ?, ?, 'pending', 'pending', NOW())
    ");
    
    $imagesJson = json_encode($uploadedFiles);
    $insertStmt->execute([$userId, $tutorialId, $description, $imagesJson]);
    
    $uploadId = $pdo->lastInsertId();
    
    // Process each image through simplified authenticity system
    $analysisResults = [];
    $requiresReview = false;
    
    foreach ($uploadedFiles as $file) {
        $imageId = $uploadId . '_' . $file['index'];
        $fullFilePath = $uploadDir . $file['stored_name'];
        
        try {
            // Run simplified authenticity evaluation
            $evaluation = $authenticityService->evaluateImage(
                $imageId, 
                'practice_upload', 
                $fullFilePath, 
                $userId, 
                $tutorialId
            );
            
            $analysisResults[] = [
                'image_id' => $imageId,
                'file_name' => $file['original_name'],
                'status' => $evaluation['status'],
                'explanation' => $evaluation['explanation'],
                'requires_admin_review' => $evaluation['requires_admin_review'],
                'category' => $evaluation['category'],
                'images_compared' => $evaluation['images_compared'],
                'metadata_notes' => $evaluation['metadata_notes'],
                'flagged_reason' => $evaluation['flagged_reason'],
                'similar_image' => $evaluation['similar_image'],
                'file_size' => $file['file_size']
            ];
            
            if ($evaluation['requires_admin_review']) {
                $requiresReview = true;
            }
            
        } catch (Exception $e) {
            error_log("Authenticity evaluation error for $imageId: " . $e->getMessage());
            $analysisResults[] = [
                'image_id' => $imageId,
                'file_name' => $file['original_name'],
                'status' => 'needs_admin_review',
                'explanation' => 'Technical error occurred during analysis',
                'requires_admin_review' => true,
                'category' => $tutorial['category'] ?? 'general',
                'images_compared' => 0,
                'metadata_notes' => 'Error: ' . $e->getMessage(),
                'flagged_reason' => 'Processing error',
                'similar_image' => null,
                'file_size' => $file['file_size'],
                'error' => true
            ];
            $requiresReview = true;
        }
    }
    
    // Update practice upload status based on analysis
    $overallStatus = $requiresReview ? 'flagged' : 'verified';
    $updateStmt = $pdo->prepare("
        UPDATE practice_uploads 
        SET authenticity_status = ? 
        WHERE id = ?
    ");
    $updateStmt->execute([$overallStatus, $uploadId]);
    
    // Update learning progress (but don't mark as complete until admin approval if flagged)
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
    $reusedCount = count(array_filter($analysisResults, fn($r) => $r['status'] === 'reused'));
    $similarCount = count(array_filter($analysisResults, fn($r) => $r['status'] === 'highly_similar'));
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
        'tutorial_title' => $tutorial['title'],
        'tutorial_category' => $tutorial['category'] ?? 'general',
        'user_email' => $userEmail,
        'timestamp' => date('Y-m-d H:i:s'),
        'authenticity_analysis' => [
            'system_version' => 'simplified_v1.0',
            'detection_method' => 'perceptual_hash_similarity',
            'comparison_scope' => 'same_category_only',
            'threshold_used' => 'hamming_distance_≤_5',
            'results' => $analysisResults,
            'summary' => [
                'total_images' => count($analysisResults),
                'unique_images' => $uniqueCount,
                'reused_images' => $reusedCount,
                'similar_images' => $similarCount,
                'requires_admin_review' => $reviewCount,
                'auto_approved' => !$requiresReview
            ],
            'explanation' => [
                'unique' => 'No similar images found within the same tutorial category on our platform',
                'reused' => 'Nearly identical image found within the same category - likely reused practice work',
                'highly_similar' => 'Very similar image found within the same category - may be duplicate practice',
                'needs_admin_review' => 'Flagged for manual review due to similarity or technical issues'
            ],
            'important_notes' => [
                'We only compare images within the same tutorial category',
                'We do not claim to detect images from Google or the internet',
                'Our system detects reuse of practice work within our platform only',
                'Progress credit requires admin approval for flagged images',
                'Certificate eligibility requires 80% overall course progress'
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