<?php
// Script to fix learning progress for soudhame52@gmail.com

require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get user ID for soudhame52@gmail.com
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $userStmt->execute(['soudhame52@gmail.com']);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "User not found!\n";
        exit;
    }
    
    $userId = $user['id'];
    echo "Fixing learning progress for User ID: {$userId}\n\n";
    
    // Get all tutorials
    $tutorialStmt = $pdo->prepare("SELECT id, title FROM tutorials WHERE is_active = 1 ORDER BY id");
    $tutorialStmt->execute();
    $tutorials = $tutorialStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $progressData = [
        // Tutorial progress with realistic completion percentages
        ['tutorial_id' => 2, 'completion_percentage' => 100, 'watch_time_seconds' => 1800], // cap embroidery - completed
        ['tutorial_id' => 3, 'completion_percentage' => 85, 'watch_time_seconds' => 1500],  // Mehandi Tutorial - completed
        ['tutorial_id' => 4, 'completion_percentage' => 92, 'watch_time_seconds' => 1650],  // Watermelon Candle - completed
        ['tutorial_id' => 5, 'completion_percentage' => 78, 'watch_time_seconds' => 1400],  // Pearl Jewelry - in progress
        ['tutorial_id' => 6, 'completion_percentage' => 65, 'watch_time_seconds' => 1200],  // Mirror clay - in progress
        ['tutorial_id' => 7, 'completion_percentage' => 100, 'watch_time_seconds' => 2100], // Clock resin art - completed
        ['tutorial_id' => 9, 'completion_percentage' => 45, 'watch_time_seconds' => 900],   // Kitkat Chocolate - in progress
        ['tutorial_id' => 10, 'completion_percentage' => 88, 'watch_time_seconds' => 1600], // Earing - completed
        ['tutorial_id' => 11, 'completion_percentage' => 30, 'watch_time_seconds' => 600],  // Ring - in progress
        ['tutorial_id' => 12, 'completion_percentage' => 95, 'watch_time_seconds' => 1750], // Ring 2 - completed
    ];
    
    echo "Adding learning progress for " . count($progressData) . " tutorials...\n";
    
    foreach ($progressData as $data) {
        // Check if tutorial exists
        $tutorialExists = false;
        foreach ($tutorials as $tutorial) {
            if ($tutorial['id'] == $data['tutorial_id']) {
                $tutorialExists = true;
                break;
            }
        }
        
        if (!$tutorialExists) {
            echo "Skipping tutorial ID {$data['tutorial_id']} - not found\n";
            continue;
        }
        
        // Insert or update learning progress
        $progressStmt = $pdo->prepare("
            INSERT INTO learning_progress (user_id, tutorial_id, watch_time_seconds, completion_percentage, completed_at, last_accessed, created_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
                watch_time_seconds = VALUES(watch_time_seconds),
                completion_percentage = VALUES(completion_percentage),
                completed_at = CASE 
                    WHEN VALUES(completion_percentage) >= 80 THEN NOW() 
                    ELSE NULL 
                END,
                last_accessed = NOW()
        ");
        
        $completedAt = $data['completion_percentage'] >= 80 ? date('Y-m-d H:i:s') : null;
        
        $progressStmt->execute([
            $userId,
            $data['tutorial_id'],
            $data['watch_time_seconds'],
            $data['completion_percentage'],
            $completedAt
        ]);
        
        $status = $data['completion_percentage'] >= 80 ? 'completed' : 'in progress';
        echo "✓ Tutorial {$data['tutorial_id']}: {$data['completion_percentage']}% ({$status})\n";
    }
    
    echo "\n=== SUMMARY ===\n";
    
    // Calculate final statistics
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_watched,
            COUNT(CASE WHEN completion_percentage >= 80 THEN 1 END) as completed_tutorials,
            COUNT(CASE WHEN completion_percentage > 0 AND completion_percentage < 80 THEN 1 END) as in_progress_tutorials,
            SUM(watch_time_seconds) as total_watch_seconds
        FROM learning_progress 
        WHERE user_id = ?
    ");
    $statsStmt->execute([$userId]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    $learning_hours = round($stats['total_watch_seconds'] / 3600, 1);
    
    echo "Total watched tutorials: {$stats['total_watched']}\n";
    echo "Completed tutorials: {$stats['completed_tutorials']}\n";
    echo "In progress tutorials: {$stats['in_progress_tutorials']}\n";
    echo "Total learning hours: {$learning_hours}\n";
    
    echo "\nLearning progress has been successfully updated!\n";
    echo "Please refresh your My Learning dashboard to see the changes.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>