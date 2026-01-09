<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Emergency debug logging
$debugLog = [];
$debugLog[] = "=== EMERGENCY UPLOAD DEBUG ===";
$debugLog[] = "Method: " . $_SERVER['REQUEST_METHOD'];
$debugLog[] = "POST data: " . json_encode($_POST);
$debugLog[] = "FILES data: " . json_encode($_FILES);
$debugLog[] = "Headers: " . json_encode(getallheaders());

try {
    require_once '../../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    $debugLog[] = "Database connection: SUCCESS";
} catch (Exception $e) {
    $debugLog[] = "Database connection: FAILED - " . $e->getMessage();
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage(),
        'debug_log' => $debugLog
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Only POST method allowed',
        'debug_log' => $debugLog
    ]);
    exit;
}

$userEmail = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? $_POST['email'] ?? '';
$tutorialId = $_POST['tutorial_id'] ?? '';
$description = $_POST['description'] ?? '';

$debugLog[] = "Email: $userEmail";
$debugLog[] = "Tutorial ID: $tutorialId";
$debugLog[] = "Description: $description";

if (empty($userEmail) || empty($tutorialId)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email and tutorial_id are required',
        'debug_log' => $debugLog
    ]);
    exit;
}

try {
    // Force Pro access for soudhame52@gmail.com
    if ($userEmail !== 'soudhame52@gmail.com') {
        echo json_encode([
            'status' => 'error',
            'message' => 'This emergency fix is only for soudhame52@gmail.com',
            'debug_log' => $debugLog
        ]);
        exit;
    }
    
    $debugLog[] = "Pro access: GRANTED";
    
    // Get user ID
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $userStmt->execute([$userEmail]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found',
            'debug_log' => $debugLog
        ]);
        exit;
    }
    
    $userId = $user['id'];
    $debugLog[] = "User ID: $userId";
    
    // Create upload directory with maximum permissions
    $uploadDir = '../../uploads/practice/';
    $debugLog[] = "Upload directory: $uploadDir";
    
    if (!is_dir($uploadDir)) {
        $created = mkdir($uploadDir, 0777, true);
        $debugLog[] = "Directory created: " . ($created ? 'YES' : 'NO');
    } else {
        $debugLog[] = "Directory exists: YES";
    }
    
    // Set maximum permissions
    chmod($uploadDir, 0777);
    $debugLog[] = "Permissions set to 777";
    
    // Check directory status
    $debugLog[] = "Directory writable: " . (is_writable($uploadDir) ? 'YES' : 'NO');
    $debugLog[] = "Directory readable: " . (is_readable($uploadDir) ? 'YES' : 'NO');
    
    // Check if any files were sent
    if (empty($_FILES)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No files received in $_FILES',
            'debug_log' => $debugLog
        ]);
        exit;
    }
    
    $debugLog[] = "Files received: " . count($_FILES);
    
    // Check practice_images specifically
    if (!isset($_FILES['practice_images'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No practice_images field found',
            'available_fields' => array_keys($_FILES),
            'debug_log' => $debugLog
        ]);
        exit;
    }
    
    $files = $_FILES['practice_images'];
    $debugLog[] = "practice_images structure: " . json_encode($files);
    
    $uploadedFiles = [];
    $errors = [];
    
    // Handle different file upload structures
    if (is_array($files['name'])) {
        // Multiple files
        $fileCount = count($files['name']);
        $debugLog[] = "Multiple files detected: $fileCount";
        
        for ($i = 0; $i < $fileCount; $i++) {
            $debugLog[] = "Processing file $i:";
            $debugLog[] = "  Name: " . $files['name'][$i];
            $debugLog[] = "  Error: " . $files['error'][$i];
            $debugLog[] = "  Size: " . $files['size'][$i];
            $debugLog[] = "  Type: " . $files['type'][$i];
            $debugLog[] = "  Tmp: " . $files['tmp_name'][$i];
            
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $errors[] = "File " . ($i + 1) . ": Upload error " . $files['error'][$i];
                $debugLog[] = "  Upload error: " . $files['error'][$i];
                continue;
            }
            
            if (!is_uploaded_file($files['tmp_name'][$i])) {
                $errors[] = "File " . ($i + 1) . ": Not a valid uploaded file";
                $debugLog[] = "  Not valid uploaded file";
                continue;
            }
            
            // Generate simple filename
            $fileName = $files['name'][$i];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $uniqueFileName = 'emergency_' . time() . '_' . $i . '.' . $fileExtension;
            $filePath = $uploadDir . $uniqueFileName;
            
            $debugLog[] = "  Target path: $filePath";
            
            if (move_uploaded_file($files['tmp_name'][$i], $filePath)) {
                $uploadedFiles[] = [
                    'original_name' => $fileName,
                    'stored_name' => $uniqueFileName,
                    'file_path' => 'uploads/practice/' . $uniqueFileName,
                    'file_size' => $files['size'][$i],
                    'file_type' => $files['type'][$i]
                ];
                $debugLog[] = "  Upload SUCCESS";
            } else {
                $errors[] = "File " . ($i + 1) . ": Failed to move uploaded file";
                $debugLog[] = "  Upload FAILED";
            }
        }
    } else {
        // Single file
        $debugLog[] = "Single file detected";
        $debugLog[] = "  Name: " . $files['name'];
        $debugLog[] = "  Error: " . $files['error'];
        $debugLog[] = "  Size: " . $files['size'];
        $debugLog[] = "  Type: " . $files['type'];
        $debugLog[] = "  Tmp: " . $files['tmp_name'];
        
        if ($files['error'] === UPLOAD_ERR_OK && is_uploaded_file($files['tmp_name'])) {
            $fileName = $files['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $uniqueFileName = 'emergency_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $uniqueFileName;
            
            $debugLog[] = "  Target path: $filePath";
            
            if (move_uploaded_file($files['tmp_name'], $filePath)) {
                $uploadedFiles[] = [
                    'original_name' => $fileName,
                    'stored_name' => $uniqueFileName,
                    'file_path' => 'uploads/practice/' . $uniqueFileName,
                    'file_size' => $files['size'],
                    'file_type' => $files['type']
                ];
                $debugLog[] = "  Upload SUCCESS";
            } else {
                $errors[] = "Failed to move uploaded file";
                $debugLog[] = "  Upload FAILED";
            }
        } else {
            $errors[] = "Upload error: " . $files['error'];
            $debugLog[] = "  Upload error or invalid file";
        }
    }
    
    $debugLog[] = "Total uploaded files: " . count($uploadedFiles);
    $debugLog[] = "Total errors: " . count($errors);
    
    if (empty($uploadedFiles)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No files were uploaded successfully',
            'errors' => $errors,
            'debug_log' => $debugLog,
            'php_settings' => [
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'max_file_uploads' => ini_get('max_file_uploads'),
                'file_uploads' => ini_get('file_uploads') ? 'enabled' : 'disabled',
                'memory_limit' => ini_get('memory_limit')
            ]
        ]);
        exit;
    }
    
    // Create tables if needed (simplified)
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS practice_uploads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            tutorial_id INT NOT NULL,
            description TEXT,
            images JSON,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $debugLog[] = "Table creation: SUCCESS";
    } catch (Exception $e) {
        $debugLog[] = "Table creation: FAILED - " . $e->getMessage();
    }
    
    // Insert record
    try {
        $insertStmt = $pdo->prepare("
            INSERT INTO practice_uploads (user_id, tutorial_id, description, images, status, upload_date)
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
        
        $imagesJson = json_encode($uploadedFiles);
        $insertStmt->execute([$userId, $tutorialId, $description, $imagesJson]);
        
        $uploadId = $pdo->lastInsertId();
        $debugLog[] = "Database insert: SUCCESS - ID $uploadId";
    } catch (Exception $e) {
        $debugLog[] = "Database insert: FAILED - " . $e->getMessage();
        $uploadId = 'unknown';
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Emergency upload successful!',
        'upload_id' => $uploadId,
        'files_uploaded' => count($uploadedFiles),
        'files' => $uploadedFiles,
        'errors' => $errors,
        'debug_log' => $debugLog,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    $debugLog[] = "Exception: " . $e->getMessage();
    echo json_encode([
        'status' => 'error',
        'message' => 'Emergency upload failed: ' . $e->getMessage(),
        'debug_log' => $debugLog,
        'trace' => $e->getTraceAsString()
    ]);
}
?>