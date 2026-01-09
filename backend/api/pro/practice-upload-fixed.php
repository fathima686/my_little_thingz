<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Debug logging
error_log("Practice Upload Debug - Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Practice Upload Debug - POST: " . json_encode($_POST));
error_log("Practice Upload Debug - FILES: " . json_encode($_FILES));

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
        'message' => 'Email and tutorial_id are required',
        'debug' => [
            'email' => $userEmail,
            'tutorial_id' => $tutorialId,
            'post_data' => $_POST,
            'headers' => getallheaders()
        ]
    ]);
    exit;
}

try {
    // Simple Pro check - allow soudhame52@gmail.com
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
            'upgrade_required' => true,
            'current_email' => $userEmail
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
            'message' => 'User not found',
            'email_searched' => $userEmail
        ]);
        exit;
    }
    
    $userId = $user['id'];
    
    // Create uploads directory with proper permissions
    $uploadDir = '../../uploads/practice/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to create upload directory',
                'upload_dir' => $uploadDir
            ]);
            exit;
        }
    }
    
    // Ensure directory is writable
    if (!is_writable($uploadDir)) {
        chmod($uploadDir, 0755);
    }
    
    $uploadedFiles = [];
    $errors = [];
    
    // Check if files were uploaded
    if (!isset($_FILES['practice_images'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No files uploaded',
            'debug' => [
                'files_received' => $_FILES,
                'post_data' => $_POST
            ]
        ]);
        exit;
    }
    
    // Handle both single and multiple file uploads
    $files = $_FILES['practice_images'];
    
    // Normalize file array structure
    if (is_array($files['name'])) {
        // Multiple files
        $fileCount = count($files['name']);
        $normalizedFiles = [];
        
        for ($i = 0; $i < $fileCount; $i++) {
            $normalizedFiles[] = [
                'name' => $files['name'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'size' => $files['size'][$i],
                'type' => $files['type'][$i],
                'error' => $files['error'][$i]
            ];
        }
    } else {
        // Single file
        $normalizedFiles = [$files];
    }
    
    foreach ($normalizedFiles as $index => $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "File " . ($index + 1) . ": Upload error code " . $file['error'];
            continue;
        }
        
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileType = $file['type'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array(strtolower($fileType), $allowedTypes)) {
            $errors[] = "File $fileName: Invalid file type ($fileType). Allowed: JPG, PNG, GIF, WebP";
            continue;
        }
        
        // Validate file size (10MB limit)
        if ($fileSize > 10 * 1024 * 1024) {
            $errors[] = "File $fileName: File too large (" . round($fileSize / 1024 / 1024, 1) . "MB). Max 10MB allowed";
            continue;
        }
        
        // Generate unique filename
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $uniqueFileName = 'practice_' . $userId . '_' . $tutorialId . '_' . time() . '_' . $index . '.' . $fileExtension;
        $filePath = $uploadDir . $uniqueFileName;
        
        if (move_uploaded_file($fileTmpName, $filePath)) {
            $uploadedFiles[] = [
                'original_name' => $fileName,
                'stored_name' => $uniqueFileName,
                'file_path' => 'uploads/practice/' . $uniqueFileName,
                'file_size' => $fileSize,
                'file_type' => $fileType
            ];
        } else {
            $errors[] = "File $fileName: Failed to move uploaded file";
        }
    }
    
    if (empty($uploadedFiles)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No files were uploaded successfully',
            'errors' => $errors,
            'debug' => [
                'upload_dir' => $uploadDir,
                'upload_dir_exists' => is_dir($uploadDir),
                'upload_dir_writable' => is_writable($uploadDir),
                'files_processed' => count($normalizedFiles)
            ]
        ]);
        exit;
    }
    
    // Create tables if they don't exist
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS practice_uploads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            tutorial_id INT NOT NULL,
            description TEXT,
            images JSON,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            admin_feedback TEXT,
            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            reviewed_date TIMESTAMP NULL,
            INDEX idx_user_tutorial (user_id, tutorial_id),
            INDEX idx_status (status)
        )");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS learning_progress (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            tutorial_id INT NOT NULL,
            watch_time_seconds INT DEFAULT 0,
            completion_percentage DECIMAL(5,2) DEFAULT 0.00,
            completed_at TIMESTAMP NULL,
            practice_uploaded BOOLEAN DEFAULT FALSE,
            last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_tutorial (user_id, tutorial_id)
        )");
    } catch (Exception $e) {
        // Continue even if table creation fails
        error_log("Table creation failed: " . $e->getMessage());
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
    try {
        $progressStmt = $pdo->prepare("
            INSERT INTO learning_progress (user_id, tutorial_id, practice_uploaded, last_accessed)
            VALUES (?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE 
            practice_uploaded = 1, 
            last_accessed = NOW()
        ");
        $progressStmt->execute([$userId, $tutorialId]);
    } catch (Exception $e) {
        // Continue even if progress update fails
        error_log("Progress update failed: " . $e->getMessage());
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Practice work uploaded successfully!',
        'upload_id' => $uploadId,
        'files_uploaded' => count($uploadedFiles),
        'files' => $uploadedFiles,
        'errors' => $errors,
        'user_email' => $userEmail,
        'user_id' => $userId,
        'tutorial_id' => $tutorialId,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Upload failed: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>