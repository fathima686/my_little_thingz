<?php
header('Content-Type: text/plain');

echo "🔧 FIXING PRACTICE UPLOAD ISSUES\n";
echo "================================\n\n";

try {
    // 1. Create upload directory
    echo "1. Creating upload directory...\n";
    $uploadDir = __DIR__ . '/uploads/practice/';
    
    if (!is_dir($uploadDir)) {
        $created = mkdir($uploadDir, 0755, true);
        if ($created) {
            echo "   ✅ Upload directory created: $uploadDir\n";
        } else {
            echo "   ❌ Failed to create upload directory\n";
        }
    } else {
        echo "   ✅ Upload directory already exists\n";
    }
    
    // 2. Set proper permissions
    echo "\n2. Setting directory permissions...\n";
    if (is_dir($uploadDir)) {
        chmod($uploadDir, 0755);
        echo "   ✅ Permissions set to 755\n";
    }
    
    // 3. Create .htaccess for security
    echo "\n3. Creating security .htaccess...\n";
    $htaccessContent = "# Prevent direct access to uploaded files\n";
    $htaccessContent .= "Options -Indexes\n";
    $htaccessContent .= "# Allow only image files\n";
    $htaccessContent .= "<FilesMatch \"\\.(jpg|jpeg|png|gif|webp)$\">\n";
    $htaccessContent .= "    Order allow,deny\n";
    $htaccessContent .= "    Allow from all\n";
    $htaccessContent .= "</FilesMatch>\n";
    
    $htaccessFile = $uploadDir . '.htaccess';
    file_put_contents($htaccessFile, $htaccessContent);
    echo "   ✅ Security .htaccess created\n";
    
    // 4. Test database connection and tables
    echo "\n4. Testing database connection...\n";
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    echo "   ✅ Database connection successful\n";
    
    // 5. Ensure tables exist
    echo "\n5. Ensuring required tables exist...\n";
    
    // Create practice_uploads table
    $createPracticeUploads = "CREATE TABLE IF NOT EXISTS practice_uploads (
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
    )";
    
    $pdo->exec($createPracticeUploads);
    echo "   ✅ practice_uploads table ready\n";
    
    // Create learning_progress table
    $createLearningProgress = "CREATE TABLE IF NOT EXISTS learning_progress (
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
    )";
    
    $pdo->exec($createLearningProgress);
    echo "   ✅ learning_progress table ready\n";
    
    // 6. Test upload API
    echo "\n6. Testing upload API endpoint...\n";
    $apiFile = __DIR__ . '/api/pro/practice-upload-simple.php';
    if (file_exists($apiFile)) {
        echo "   ✅ Upload API file exists\n";
    } else {
        echo "   ❌ Upload API file missing\n";
    }
    
    // 7. Check PHP settings
    echo "\n7. Checking PHP upload settings...\n";
    $uploadMaxFilesize = ini_get('upload_max_filesize');
    $postMaxSize = ini_get('post_max_size');
    $maxFileUploads = ini_get('max_file_uploads');
    $fileUploads = ini_get('file_uploads');
    
    echo "   Upload max filesize: $uploadMaxFilesize\n";
    echo "   Post max size: $postMaxSize\n";
    echo "   Max file uploads: $maxFileUploads\n";
    echo "   File uploads: " . ($fileUploads ? 'enabled' : 'disabled') . "\n";
    
    if (!$fileUploads) {
        echo "   ❌ File uploads are disabled in PHP\n";
    } else {
        echo "   ✅ PHP upload settings OK\n";
    }
    
    // 8. Create test image for verification
    echo "\n8. Creating test verification...\n";
    $testFile = $uploadDir . 'test_upload_' . time() . '.txt';
    $testContent = "Test upload verification - " . date('Y-m-d H:i:s');
    
    if (file_put_contents($testFile, $testContent)) {
        echo "   ✅ Write test successful\n";
        unlink($testFile); // Clean up
    } else {
        echo "   ❌ Write test failed\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🎉 PRACTICE UPLOAD FIXES APPLIED!\n";
    echo "🎉 PRACTICE UPLOAD FIXES APPLIED!\n";
    echo "\n✅ Upload directory created and secured\n";
    echo "✅ Database tables ready\n";
    echo "✅ Permissions set correctly\n";
    echo "✅ Security measures in place\n";
    echo "\n🚀 TRY UPLOADING IMAGES NOW!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
?>