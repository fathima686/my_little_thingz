<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $uploadDir = 'uploads/practice/';
    $fullPath = __DIR__ . '/' . $uploadDir;
    
    $result = [
        'status' => 'success',
        'upload_directory' => $uploadDir,
        'full_path' => $fullPath,
        'exists' => is_dir($fullPath),
        'writable' => is_writable(dirname($fullPath)),
        'permissions' => substr(sprintf('%o', fileperms(dirname($fullPath))), -4) ?? 'unknown',
        'parent_writable' => is_writable(__DIR__),
        'current_dir' => __DIR__,
        'php_settings' => [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads'),
            'file_uploads' => ini_get('file_uploads') ? 'enabled' : 'disabled'
        ]
    ];
    
    // Try to create directory if it doesn't exist
    if (!$result['exists']) {
        $created = mkdir($fullPath, 0755, true);
        $result['directory_created'] = $created;
        $result['exists'] = is_dir($fullPath);
        $result['writable'] = is_writable($fullPath);
    }
    
    // Test write permissions
    if ($result['exists']) {
        $testFile = $fullPath . 'test_write.txt';
        $writeTest = file_put_contents($testFile, 'test');
        $result['write_test'] = $writeTest !== false;
        
        if ($writeTest) {
            unlink($testFile);
        }
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>