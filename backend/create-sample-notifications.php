<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Creating sample notifications...\n";
    
    // Create notifications table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
        is_read BOOLEAN DEFAULT FALSE,
        action_url VARCHAR(500) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_is_read (is_read),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Get a sample user (first user in the database)
    $userStmt = $db->query("SELECT id, email FROM users LIMIT 1");
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "No users found. Please create a user first.\n";
        exit;
    }
    
    $userId = $user['id'];
    echo "Creating notifications for user: {$user['email']}\n";
    
    // Sample notifications
    $notifications = [
        [
            'title' => 'Welcome to My Little Thingz!',
            'message' => 'Start your craft learning journey with our premium tutorials.',
            'type' => 'success',
            'action_url' => '/tutorials'
        ],
        [
            'title' => 'Practice Work Approved',
            'message' => 'Your practice submission for "Resin Art Basics" has been approved! Great work!',
            'type' => 'success',
            'action_url' => '/pro-dashboard'
        ],
        [
            'title' => 'New Tutorial Available',
            'message' => 'Check out our latest tutorial: "Advanced Embroidery Techniques"',
            'type' => 'info',
            'action_url' => '/tutorials'
        ],
        [
            'title' => 'Certificate Ready!',
            'message' => 'Congratulations! You\'ve reached 80% completion and can now download your certificate.',
            'type' => 'success',
            'action_url' => '/pro-dashboard'
        ],
        [
            'title' => 'Live Workshop Tomorrow',
            'message' => 'Don\'t forget about the live jewelry making workshop tomorrow at 3 PM.',
            'type' => 'warning',
            'action_url' => '/tutorials'
        ]
    ];
    
    $stmt = $db->prepare("
        INSERT INTO notifications (user_id, title, message, type, action_url) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($notifications as $notification) {
        $stmt->execute([
            $userId,
            $notification['title'],
            $notification['message'],
            $notification['type'],
            $notification['action_url']
        ]);
        echo "✓ Created: {$notification['title']}\n";
    }
    
    echo "\n🎉 Sample notifications created successfully!\n";
    echo "User {$user['email']} now has " . count($notifications) . " notifications.\n";
    
} catch (Exception $e) {
    echo "❌ Error creating notifications: " . $e->getMessage() . "\n";
}
?>