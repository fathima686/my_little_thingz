<?php
/**
 * Check PHP Upload Configuration
 */

echo "=== PHP Upload Configuration ===\n\n";

$settings = [
    'file_uploads' => ini_get('file_uploads'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'upload_tmp_dir' => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time')
];

foreach ($settings as $key => $value) {
    $status = '';
    
    if ($key === 'file_uploads') {
        $status = $value ? '✓' : '✗';
    } elseif ($key === 'upload_max_filesize' || $key === 'post_max_size') {
        // Convert to bytes for comparison
        $bytes = return_bytes($value);
        $status = $bytes >= 5 * 1024 * 1024 ? '✓' : '⚠️';
    }
    
    echo "$status $key: $value\n";
}

echo "\n";

// Check upload directory
$uploadDir = 'backend/uploads/practice/';
echo "Upload Directory: $uploadDir\n";

if (is_dir($uploadDir)) {
    echo "✓ Directory exists\n";
    
    if (is_writable($uploadDir)) {
        echo "✓ Directory is writable\n";
    } else {
        echo "✗ Directory is NOT writable\n";
        echo "  Fix: chmod 755 $uploadDir\n";
    }
    
    // Check disk space
    $freeSpace = disk_free_space($uploadDir);
    $freeSpaceMB = round($freeSpace / 1024 / 1024, 2);
    echo "✓ Free space: {$freeSpaceMB} MB\n";
} else {
    echo "⚠️  Directory does not exist (will be created on upload)\n";
}

echo "\n";

// Check temp directory
$tmpDir = $settings['upload_tmp_dir'];
echo "Temp Directory: $tmpDir\n";

if (is_dir($tmpDir)) {
    echo "✓ Temp directory exists\n";
    
    if (is_writable($tmpDir)) {
        echo "✓ Temp directory is writable\n";
    } else {
        echo "✗ Temp directory is NOT writable\n";
    }
} else {
    echo "✗ Temp directory does not exist\n";
}

echo "\n=== Recommendations ===\n\n";

$uploadMaxBytes = return_bytes($settings['upload_max_filesize']);
$postMaxBytes = return_bytes($settings['post_max_size']);

if ($uploadMaxBytes < 10 * 1024 * 1024) {
    echo "⚠️  upload_max_filesize is less than 10MB\n";
    echo "   Recommended: 10M or higher\n";
    echo "   Edit php.ini: upload_max_filesize = 10M\n\n";
}

if ($postMaxBytes < 10 * 1024 * 1024) {
    echo "⚠️  post_max_size is less than 10MB\n";
    echo "   Recommended: 10M or higher\n";
    echo "   Edit php.ini: post_max_size = 10M\n\n";
}

if ($postMaxBytes < $uploadMaxBytes) {
    echo "⚠️  post_max_size should be larger than upload_max_filesize\n\n";
}

if (!$settings['file_uploads']) {
    echo "✗ File uploads are DISABLED\n";
    echo "  Edit php.ini: file_uploads = On\n\n";
}

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    
    return $val;
}
?>
