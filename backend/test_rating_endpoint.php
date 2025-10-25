<?php
// Test script for submit-rating.php endpoint
require_once 'config/database.php';

try {
    $db = (new Database())->getConnection();
    
    // Test data
    $testData = [
        'user_id' => 1,
        'artwork_id' => 1,
        'rating' => 5,
        'feedback' => 'Test review from script'
    ];
    
    // Simulate the endpoint logic
    $userId = $testData['user_id'];
    $artworkId = $testData['artwork_id'];
    $rating = $testData['rating'];
    $feedback = $testData['feedback'];
    
    if ($userId <= 0 || $artworkId <= 0 || $rating < 1 || $rating > 5) {
        echo "Error: Invalid parameters\n";
        exit;
    }
    
    // Check if user and artwork exist
    $userCheck = $db->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
    $userCheck->execute([$userId]);
    if (!$userCheck->fetch()) {
        echo "Error: User not found\n";
        exit;
    }
    
    $artworkCheck = $db->prepare("SELECT id FROM artworks WHERE id = ? LIMIT 1");
    $artworkCheck->execute([$artworkId]);
    if (!$artworkCheck->fetch()) {
        echo "Error: Artwork not found\n";
        exit;
    }
    
    // Test the review insertion
    $stmt = $db->prepare("INSERT INTO reviews (user_id, artwork_id, rating, comment)
                          VALUES (?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), status = 'pending', updated_at = CURRENT_TIMESTAMP");
    $result = $stmt->execute([$userId, $artworkId, $rating, $feedback]);
    
    if ($result) {
        echo "Success: Review submitted successfully\n";
        echo "Review ID: " . $db->lastInsertId() . "\n";
    } else {
        echo "Error: Failed to submit review\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>











