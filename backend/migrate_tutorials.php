<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "Creating tutorials table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS tutorials (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        thumbnail_url VARCHAR(255),
        video_url VARCHAR(255) NOT NULL,
        duration INT,
        difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
        price DECIMAL(10, 2) DEFAULT 0,
        is_free BOOLEAN DEFAULT 0,
        category VARCHAR(100),
        created_by INT UNSIGNED,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT 1,
        INDEX idx_active (is_active),
        INDEX idx_category (category)
    )");
    echo "✓ Tutorials table created\n";

    echo "Creating tutorial_purchases table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS tutorial_purchases (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        tutorial_id INT UNSIGNED NOT NULL,
        purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expiry_date DATETIME,
        payment_method VARCHAR(50),
        razorpay_order_id VARCHAR(100),
        razorpay_payment_id VARCHAR(100),
        payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
        amount_paid DECIMAL(10, 2),
        UNIQUE KEY unique_purchase (user_id, tutorial_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (tutorial_id) REFERENCES tutorials(id) ON DELETE CASCADE,
        INDEX idx_user (user_id),
        INDEX idx_tutorial (tutorial_id),
        INDEX idx_status (payment_status)
    )");
    echo "✓ Tutorial purchases table created\n";

    echo "\nInserting sample tutorials...\n";
    
    // Check if tutorials already exist
    $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM tutorials");
    $checkStmt->execute();
    $result = $checkStmt->fetch();
    
    if ($result['count'] == 0) {
        $sampleTutorials = [
            [
                'title' => 'Getting Started with Custom Frames',
                'description' => 'Learn how to create beautiful custom photo frames from scratch. This beginner-friendly guide covers materials, tools, and techniques to make stunning personalized frames.',
                'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'thumbnail_url' => 'https://via.placeholder.com/300x200?text=Custom+Frames',
                'duration' => 15,
                'difficulty_level' => 'beginner',
                'price' => 99.00,
                'is_free' => false,
                'category' => 'Frames'
            ],
            [
                'title' => 'DIY Floral Arrangements Masterclass',
                'description' => 'Master the art of creating professional-looking flower arrangements. Discover color theory, composition techniques, and preservation methods for long-lasting arrangements.',
                'video_url' => 'https://www.youtube.com/embed/9bZkp7q19f0',
                'thumbnail_url' => 'https://via.placeholder.com/300x200?text=Floral+Art',
                'duration' => 25,
                'difficulty_level' => 'intermediate',
                'price' => 199.00,
                'is_free' => false,
                'category' => 'Flowers'
            ],
            [
                'title' => 'Advanced Gift Box Assembly',
                'description' => 'Take your gift box assembly skills to the next level. Learn advanced techniques for luxury packaging, custom inserts, and professional finishing touches.',
                'video_url' => 'https://www.youtube.com/embed/JwYX52BP2Sk',
                'thumbnail_url' => 'https://via.placeholder.com/300x200?text=Gift+Boxes',
                'duration' => 30,
                'difficulty_level' => 'advanced',
                'price' => 299.00,
                'is_free' => false,
                'category' => 'Packaging'
            ],
            [
                'title' => 'Introduction to Photo Editing for Gifts',
                'description' => 'Learn basic photo editing techniques to enhance images used in personalized gifts. This free tutorial covers cropping, color correction, and basic filters.',
                'video_url' => 'https://www.youtube.com/embed/YRYQ1iBNt54',
                'thumbnail_url' => 'https://via.placeholder.com/300x200?text=Photo+Editing',
                'duration' => 12,
                'difficulty_level' => 'beginner',
                'price' => 0.00,
                'is_free' => true,
                'category' => 'Photo Editing'
            ],
            [
                'title' => 'Wedding Card Design & Printing',
                'description' => 'Create beautiful wedding cards from design to print. Learn about paper selection, printing methods, embellishments, and professional packaging.',
                'video_url' => 'https://www.youtube.com/embed/zROtRMbDKf0',
                'thumbnail_url' => 'https://via.placeholder.com/300x200?text=Wedding+Cards',
                'duration' => 35,
                'difficulty_level' => 'intermediate',
                'price' => 249.00,
                'is_free' => false,
                'category' => 'Wedding'
            ]
        ];

        $stmt = $db->prepare("
            INSERT INTO tutorials 
            (title, description, video_url, thumbnail_url, duration, difficulty_level, price, is_free, category, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");

        foreach ($sampleTutorials as $tutorial) {
            $stmt->execute([
                $tutorial['title'],
                $tutorial['description'],
                $tutorial['video_url'],
                $tutorial['thumbnail_url'],
                $tutorial['duration'],
                $tutorial['difficulty_level'],
                $tutorial['price'],
                $tutorial['is_free'],
                $tutorial['category']
            ]);
            echo "✓ Created: " . $tutorial['title'] . "\n";
        }
    } else {
        echo "Tutorials already exist, skipping sample data insertion\n";
    }

    echo "\n✓ Migration completed successfully!\n";

} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
