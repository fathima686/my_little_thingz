<?php
require_once 'config/database.php';

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "Setting up learning progress tracking...\n\n";
    
    // Create learning_progress table
    $sql = "CREATE TABLE IF NOT EXISTS learning_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        tutorial_id INT NOT NULL,
        email VARCHAR(255) NOT NULL,
        watch_time_seconds INT DEFAULT 0,
        total_duration_seconds INT DEFAULT 0,
        progress_percentage DECIMAL(5,2) DEFAULT 0.00,
        is_completed BOOLEAN DEFAULT FALSE,
        last_watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_tutorial (user_id, tutorial_id),
        INDEX idx_email (email),
        INDEX idx_progress (progress_percentage),
        INDEX idx_completed (is_completed)
    )";
    
    $pdo->exec($sql);
    echo "✓ learning_progress table created\n";
    
    // Create learning_sessions table for detailed tracking
    $sql = "CREATE TABLE IF NOT EXISTS learning_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        tutorial_id INT NOT NULL,
        email VARCHAR(255) NOT NULL,
        session_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        session_end TIMESTAMP NULL,
        watch_duration_seconds INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_tutorial (user_id, tutorial_id),
        INDEX idx_email (email),
        INDEX idx_session_date (session_start)
    )";
    
    $pdo->exec($sql);
    echo "✓ learning_sessions table created\n";
    
    // Add some sample learning progress for Pro users
    $userEmail = 'soudhame52@gmail.com';
    
    // Get user ID
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$userEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $userId = $user['id'];
        
        // Add sample learning progress
        $sampleProgress = [
            ['tutorial_id' => 2, 'watch_time' => 1800, 'total_duration' => 2400, 'progress' => 75.00],
            ['tutorial_id' => 3, 'watch_time' => 2100, 'total_duration' => 2100, 'progress' => 100.00],
            ['tutorial_id' => 4, 'watch_time' => 900, 'total_duration' => 1800, 'progress' => 50.00],
            ['tutorial_id' => 5, 'watch_time' => 1500, 'total_duration' => 3000, 'progress' => 50.00],
            ['tutorial_id' => 6, 'watch_time' => 2700, 'total_duration' => 2700, 'progress' => 100.00],
        ];
        
        foreach ($sampleProgress as $progress) {
            $stmt = $pdo->prepare("
                INSERT INTO learning_progress 
                (user_id, tutorial_id, email, watch_time_seconds, total_duration_seconds, progress_percentage, is_completed, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                watch_time_seconds = VALUES(watch_time_seconds),
                progress_percentage = VALUES(progress_percentage),
                is_completed = VALUES(is_completed)
            ");
            
            $isCompleted = $progress['progress'] >= 100;
            $stmt->execute([
                $userId,
                $progress['tutorial_id'],
                $userEmail,
                $progress['watch_time'],
                $progress['total_duration'],
                $progress['progress'],
                $isCompleted
            ]);
        }
        
        echo "✓ Sample learning progress added for $userEmail\n";
        
        // Add some learning sessions
        $sessions = [
            ['tutorial_id' => 2, 'duration' => 600],
            ['tutorial_id' => 2, 'duration' => 720],
            ['tutorial_id' => 2, 'duration' => 480],
            ['tutorial_id' => 3, 'duration' => 1200],
            ['tutorial_id' => 3, 'duration' => 900],
            ['tutorial_id' => 4, 'duration' => 900],
            ['tutorial_id' => 5, 'duration' => 800],
            ['tutorial_id' => 5, 'duration' => 700],
            ['tutorial_id' => 6, 'duration' => 1350],
            ['tutorial_id' => 6, 'duration' => 1350],
        ];
        
        foreach ($sessions as $session) {
            $stmt = $pdo->prepare("
                INSERT INTO learning_sessions 
                (user_id, tutorial_id, email, watch_duration_seconds, session_start, session_end) 
                VALUES (?, ?, ?, ?, NOW() - INTERVAL FLOOR(RAND() * 30) DAY, NOW() - INTERVAL FLOOR(RAND() * 30) DAY + INTERVAL ? SECOND)
            ");
            $stmt->execute([
                $userId,
                $session['tutorial_id'],
                $userEmail,
                $session['duration'],
                $session['duration']
            ]);
        }
        
        echo "✓ Sample learning sessions added\n";
    }
    
    echo "\n✅ Learning progress tracking setup complete!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>