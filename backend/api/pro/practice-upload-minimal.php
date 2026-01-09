<?php
// Minimal upload test - no fancy features, just basic file upload
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Start output buffering to capture any errors
ob_start();

$response = [
    'status' => 'error',
    'message' => 'Unknown error',
    'debug' => []
];

try {
    $response['debug'][] = "Script started";
    $response['debug'][] = "Method: " . $_SERVER['REQUEST_METHOD'];
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Only POST allowed';
        echo json_encode($response);
        exit;
    }
    
    $response['debug'][] = "POST data: " . json_encode($_POST);
    $response['debug'][] = "FILES data: " . json_encode($_FILES);
    
    // Check if files exist
    if (empty($_FILES)) {
        $response['message'] = 'No $_FILES data received';
        echo json_encode($response);
        exit;
    }
    
    if (!isset($_FILES['practice_images'])) {
        $response['message'] = 'No practice_images field found';
        $response['available_fields'] = array_keys($_FILES);
        echo json_encode($response);
        exit;
    }
    
    $files = $_FILES['practice_images'];
    $response['debug'][] = "Files structure: " . json_encode($files);
    
    // Create upload directory
    $uploadDir = __DIR__ . '/../../uploads/practice/';
    $response['debug'][] = "Upload directory: $uploadDir";
    
    if (!is_dir($uploadDir)) {
        $created = mkdir($uploadDir, 0777, true);
        $response['debug'][] = "Directory created: " . ($created ? 'YES' : 'NO');
    }
    
    // Set permissions
    chmod($uploadDir, 0777);
    $response['debug'][] = "Directory writable: " . (is_writable($uploadDir) ? 'YES' : 'NO');
    
    $uploadedFiles = [];
    
    // Handle file upload - check if it's array or single file
    if (is_array($files['name'])) {
        // Multiple files
        $fileCount = count($files['name']);
        $response['debug'][] = "Multiple files: $fileCount";
        
        for ($i = 0; $i < $fileCount; $i++) {
            $response['debug'][] = "Processing file $i:";
            $response['debug'][] = "  Name: " . $files['name'][$i];
            $response['debug'][] = "  Error: " . $files['error'][$i];
            $response['debug'][] = "  Size: " . $files['size'][$i];
            $response['debug'][] = "  Tmp: " . $files['tmp_name'][$i];
            
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $response['debug'][] = "  Upload error: " . $files['error'][$i];
                continue;
            }
            
            if (!is_uploaded_file($files['tmp_name'][$i])) {
                $response['debug'][] = "  Not uploaded file";
                continue;
            }
            
            $fileName = basename($files['name'][$i]);
            $targetPath = $uploadDir . 'test_' . time() . '_' . $i . '_' . $fileName;
            
            $response['debug'][] = "  Target: $targetPath";
            
            if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                $uploadedFiles[] = [
                    'original' => $fileName,
                    'saved' => basename($targetPath),
                    'size' => $files['size'][$i]
                ];
                $response['debug'][] = "  SUCCESS";
            } else {
                $response['debug'][] = "  FAILED to move file";
            }
        }
    } else {
        // Single file
        $response['debug'][] = "Single file:";
        $response['debug'][] = "  Name: " . $files['name'];
        $response['debug'][] = "  Error: " . $files['error'];
        $response['debug'][] = "  Size: " . $files['size'];
        $response['debug'][] = "  Tmp: " . $files['tmp_name'];
        
        if ($files['error'] === UPLOAD_ERR_OK && is_uploaded_file($files['tmp_name'])) {
            $fileName = basename($files['name']);
            $targetPath = $uploadDir . 'test_' . time() . '_' . $fileName;
            
            $response['debug'][] = "  Target: $targetPath";
            
            if (move_uploaded_file($files['tmp_name'], $targetPath)) {
                $uploadedFiles[] = [
                    'original' => $fileName,
                    'saved' => basename($targetPath),
                    'size' => $files['size']
                ];
                $response['debug'][] = "  SUCCESS";
            } else {
                $response['debug'][] = "  FAILED to move file";
            }
        } else {
            $response['debug'][] = "  Upload error or invalid file";
        }
    }
    
    $response['debug'][] = "Total uploaded: " . count($uploadedFiles);
    
    if (count($uploadedFiles) > 0) {
        $response['status'] = 'success';
        $response['message'] = 'Files uploaded successfully!';
        $response['files'] = $uploadedFiles;
        $response['count'] = count($uploadedFiles);
    } else {
        $response['message'] = 'No files were uploaded successfully';
        $response['php_settings'] = [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads'),
            'file_uploads' => ini_get('file_uploads') ? 'enabled' : 'disabled'
        ];
    }
    
} catch (Exception $e) {
    $response['message'] = 'Exception: ' . $e->getMessage();
    $response['debug'][] = 'Exception: ' . $e->getMessage();
    $response['debug'][] = 'Trace: ' . $e->getTraceAsString();
}

// Get any output that might have been generated
$output = ob_get_clean();
if (!empty($output)) {
    $response['debug'][] = 'Output buffer: ' . $output;
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>