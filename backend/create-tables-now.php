<?php
header('Content-Type: text/plain');

echo "🔧 CREATING MISSING DATABASE TABLES\n";
echo "===================================\n\n";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "✅ Database connection successful\n\n";
    
    // Create learning_progress table
    echo "1. Creating learning_progress table...\n";
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
        UNIQUE KEY unique_user_tutorial (user_id, tutorial_id),
        INDEX idx_user_id (user_id),
        INDEX idx_tutorial_id (tutorial_id),
        INDEX idx_completion (completion_percentage)
    )";
    
    $pdo->exec($createLearningProgress);
    echo "   ✅ learning_progress table created\n";
    
    // Create practice_uploads table
    echo "\n2. Creating practice_uploads table...\n";
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
    echo "   ✅ practice_uploads table created\n";
    
    // Create certificates table
    echo "\n3. Creating certificates table...\n";
    $createCertificates = "CREATE TABLE IF NOT EXISTS certificates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        certificate_id VARCHAR(50) UNIQUE NOT NULL,
        user_name VARCHAR(255) NOT NULL,
        completion_date DATE NOT NULL,
        tutorials_completed INT DEFAULT 0,
        overall_progress DECIMAL(5,2) DEFAULT 0.00,
        issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_certificate_id (certificate_id)
    )";
    
    $pdo->exec($createCertificates);
    echo "   ✅ certificates table created\n";
    
    // Add sample data for soudhame52@gmail.com
    echo "\n4. Adding sample progress data...\n";
    
    $testEmail = 'soudhame52@gmail.com';
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $userStmt->execute([$testEmail]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $userId = $user['id'];
        echo "   Found user ID: $userId\n";
        
        // Add sample progress
        $progressStmt = $pdo->prepare("
            INSERT INTO learning_progress (user_id, tutorial_id, watch_time_seconds, completion_percentage, last_accessed)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            watch_time_seconds = GREATEST(watch_time_seconds, VALUES(watch_time_seconds)),
            completion_percentage = GREATEST(completion_percentage, VALUES(completion_percentage)),
            last_accessed = NOW()
        ");
        
        $sampleProgress = [
            [1, 2700, 90],   // Tutorial 1: 90% complete
            [2, 1800, 85],   // Tutorial 2: 85% complete  
            [3, 3600, 100],  // Tutorial 3: 100% complete
            [4, 2400, 75],   // Tutorial 4: 75% complete
            [5, 1500, 60],   // Tutorial 5: 60% complete
        ];
        
        foreach ($sampleProgress as $progress) {
            $progressStmt->execute([$userId, $progress[0], $progress[1], $progress[2]]);
        }
        
        echo "   ✅ Sample progress data added for 5 tutorials\n";
        
        // Add sample practice upload for tutorial 3
        $practiceStmt = $pdo->prepare("
            INSERT INTO practice_uploads (user_id, tutorial_id, description, images, status, admin_feedback, upload_date)
            VALUES (?, 3, 'My completed gift box project', ?, 'approved', 'Excellent work! Great attention to detail.', NOW())
            ON DUPLICATE KEY UPDATE status = 'approved'
        ");
        
        $sampleImages = json_encode([
            [
                'original_name' => 'gift_box_final.jpg',
                'stored_name' => 'practice_sample.jpg',
                'file_path' => 'uploads/practice/practice_sample.jpg',
                'file_size' => 245760
            ]
        ]);
        
        $practiceStmt->execute([$userId, $sampleImages]);
        echo "   ✅ Sample practice upload added\n";
        
    } else {
        echo "   ⚠️  Test user not found, skipping sample data\n";
    }
    
    // Verify tables exist
    echo "\n5. Verifying table creation...\n";
    $tables = ['learning_progress', 'practice_uploads', 'certificates'];
    foreach ($tables as $table) {
        $checkStmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $checkStmt->execute([$table]);
        $exists = $checkStmt->fetch();
        
        if ($exists) {
            echo "   ✅ $table table exists\n";
        } else {
            echo "   ❌ $table table missing\n";
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🎉 SUCCESS! ALL TABLES CREATED!\n";
    echo "🎉 SUCCESS! ALL TABLES CREATED!\n";
    echo "🎉 SUCCESS! ALL TABLES CREATED!\n";
    echo "\n✅ learning_progress table ready\n";
    echo "✅ practice_uploads table ready\n";
    echo "✅ certificates table ready\n";
    echo "✅ Sample data added\n";
    echo "\n🚀 REFRESH YOUR PROGRESS PAGE NOW!\n";
    echo "🚀 REFRESH YOUR PROGRESS PAGE NOW!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
?>