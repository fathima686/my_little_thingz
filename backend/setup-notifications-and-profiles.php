<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    echo "Connected to database successfully.\n";
    
    // Create notifications table
    $createNotifications = "
    CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
        action_url VARCHAR(500) NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_is_read (is_read),
        INDEX idx_created_at (created_at)
    )";
    
    $pdo->exec($createNotifications);
    echo "✓ Notifications table created/verified\n";
    
    // Create user profiles table
    $createProfiles = "
    CREATE TABLE IF NOT EXISTS user_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        first_name VARCHAR(100) NULL,
        last_name VARCHAR(100) NULL,
        phone VARCHAR(20) NULL,
        address TEXT NULL,
        city VARCHAR(100) NULL,
        state VARCHAR(100) NULL,
        postal_code VARCHAR(20) NULL,
        country VARCHAR(100) DEFAULT 'India',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id)
    )";
    
    $pdo->exec($createProfiles);
    echo "✓ User profiles table created/verified\n";
    
    // Create some sample notifications for testing
    $sampleNotifications = [
        [
            'title' => 'Welcome to My Little Thingz!',
            'message' => 'Thank you for joining our craft learning platform. Start exploring our tutorials!',
            'type' => 'success',
            'action_url' => '/tutorials'
        ],
        [
            'title' => 'New Tutorial Available',
            'message' => 'Check out our latest Hand Embroidery tutorial - perfect for beginners!',
            'type' => 'info',
            'action_url' => '/tutorials'
        ],
        [
            'title' => 'Subscription Reminder',
            'message' => 'Upgrade to Premium to unlock unlimited access to all tutorials.',
            'type' => 'warning',
            'action_url' => '/tutorials#subscription'
        ]
    ];
    
    // Get all users to create sample notifications
    $stmt = $pdo->prepare("SELECT id FROM users LIMIT 10");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($users)) {
        foreach ($users as $user) {
            foreach ($sampleNotifications as $notification) {
                // Check if notification already exists
                $checkStmt = $pdo->prepare("
                    SELECT id FROM notifications 
                    WHERE user_id = ? AND title = ? 
                    LIMIT 1
                ");
                $checkStmt->execute([$user['id'], $notification['title']]);
                
                if (!$checkStmt->fetch()) {
                    $insertStmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, title, message, type, action_url, is_read) 
                        VALUES (?, ?, ?, ?, ?, 0)
                    ");
                    $insertStmt->execute([
                        $user['id'],
                        $notification['title'],
                        $notification['message'],
                        $notification['type'],
                        $notification['action_url']
                    ]);
                }
            }
        }
        echo "✓ Sample notifications created for " . count($users) . " users\n";
    }
    
    echo "\n✅ Setup completed successfully!\n";
    echo "Notifications and profiles system is ready to use.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>