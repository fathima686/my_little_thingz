<?php
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
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
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
    // Simple Pro check
    $isPro = ($userEmail === 'soudhame52@gmail.com');
    
    if (!$isPro) {
        // Check subscription
        $subStmt = $pdo->prepare("SELECT plan_code FROM subscriptions WHERE email = ? AND is_active = 1 ORDER BY created_at DESC LIMIT 1");
        $subStmt->execute([$userEmail]);
        $sub = $subStmt->fetch(PDO::FETCH_ASSOC);
        $isPro = ($sub && $sub['plan_code'] === 'pro');
    }
    
    if (!$isPro) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Practice uploads are only available for Pro subscribers',
            'upgrade_required' => true
        ]);
        exit;
    }
    
    // Get user ID
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
    
    // Insert practice upload record
    $insertStmt = $pdo->prepare("
        INSERT INTO practice_uploads (user_id, tutorial_id, description, images, status, upload_date)
        VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    
    $imagesJson = json_encode($uploadedFiles);
    $insertStmt->execute([$userId, $tutorialId, $description, $imagesJson]);
    
    $uploadId = $pdo->lastInsertId();
    
    // Update learning progress
    $progressStmt = $pdo->prepare("
        INSERT INTO learning_progress (user_id, tutorial_id, practice_uploaded, last_accessed)
        VALUES (?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE 
        practice_uploaded = 1, 
        last_accessed = NOW()
    ");
    $progressStmt->execute([$userId, $tutorialId]);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Practice work uploaded successfully',
        'upload_id' => $uploadId,
        'files_uploaded' => count($uploadedFiles),
        'files' => $uploadedFiles,
        'errors' => $errors,
        'user_email' => $userEmail,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Upload failed: ' . $e->getMessage()
    ]);
}
?>