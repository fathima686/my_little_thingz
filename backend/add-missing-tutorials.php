<?php
require_once 'config/database.php';

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "Connected to database successfully.\n";
    
    // Add missing tutorials
    $tutorials = [
        [
            'id' => 9,
            'title' => 'Kitkat Chocolate boquetes',
            'description' => 'Learn to make beautiful chocolate bouquets using Kitkat bars',
            'thumbnail_url' => 'uploads/tutorials/thumb_kitkat_bouquet.jpg',
            'video_url' => 'uploads/tutorials/videos/video_kitkat_bouquet.mp4',
            'duration' => 45,
            'difficulty_level' => 'intermediate',
            'price' => 45.00,
            'is_free' => 0,
            'category' => 'Chocolate Crafts'
        ],
        [
            'id' => 10,
            'title' => 'Earing',
            'description' => 'Create beautiful handmade earrings with simple techniques',
            'thumbnail_url' => 'uploads/tutorials/thumb_earring.jpg',
            'video_url' => 'uploads/tutorials/videos/video_earring.mp4',
            'duration' => 30,
            'difficulty_level' => 'beginner',
            'price' => 0.00,
            'is_free' => 1,
            'category' => 'Jewelry Making'
        ],
        [
            'id' => 11,
            'title' => 'Ring',
            'description' => 'Learn to craft elegant rings using various materials',
            'thumbnail_url' => 'uploads/tutorials/thumb_ring.jpg',
            'video_url' => 'uploads/tutorials/videos/video_ring.mp4',
            'duration' => 35,
            'difficulty_level' => 'intermediate',
            'price' => 35.00,
            'is_free' => 0,
            'category' => 'Jewelry Making'
        ],
        [
            'id' => 12,
            'title' => 'Ring',
            'description' => 'Advanced ring making techniques with precious materials',
            'thumbnail_url' => 'uploads/tutorials/thumb_ring_advanced.jpg',
            'video_url' => 'uploads/tutorials/videos/video_ring_advanced.mp4',
            'duration' => 60,
            'difficulty_level' => 'advanced',
            'price' => 55.00,
            'is_free' => 0,
            'category' => 'Jewelry Making'
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO tutorials 
        (id, title, description, thumbnail_url, video_url, duration, difficulty_level, price, is_free, category, created_by, created_at, updated_at, is_active) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 5, NOW(), NOW(), 1)
        ON DUPLICATE KEY UPDATE
        title = VALUES(title),
        description = VALUES(description),
        thumbnail_url = VALUES(thumbnail_url),
        video_url = VALUES(video_url),
        duration = VALUES(duration),
        difficulty_level = VALUES(difficulty_level),
        price = VALUES(price),
        is_free = VALUES(is_free),
        category = VALUES(category),
        updated_at = NOW()
    ");
    
    foreach ($tutorials as $tutorial) {
        $stmt->execute([
            $tutorial['id'],
            $tutorial['title'],
            $tutorial['description'],
            $tutorial['thumbnail_url'],
            $tutorial['video_url'],
            $tutorial['duration'],
            $tutorial['difficulty_level'],
            $tutorial['price'],
            $tutorial['is_free'],
            $tutorial['category']
        ]);
        echo "✓ Added/Updated tutorial: {$tutorial['title']} (ID: {$tutorial['id']})\n";
    }
    
    echo "\nAll missing tutorials added successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>