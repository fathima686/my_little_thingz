<?php
// Simple verification of learning data

require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Test the getLearningStats function
    function getLearningStats($pdo, $userId) {
        try {
            $progressStmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_watched,
                    COUNT(CASE WHEN completion_percentage >= 80 THEN 1 END) as completed_tutorials,
                    COUNT(CASE WHEN completion_percentage > 0 AND completion_percentage < 80 THEN 1 END) as in_progress_tutorials,
                    SUM(COALESCE(watch_time_seconds, 0)) as total_watch_seconds
                FROM learning_progress 
                WHERE user_id = ?
            ");
            $progressStmt->execute([$userId]);
            $progress = $progressStmt->fetch(PDO::FETCH_ASSOC);
            
            $learning_hours = round(($progress['total_watch_seconds'] ?? 0) / 3600, 1);
            
            return [
                'completed_tutorials' => (int)($progress['completed_tutorials'] ?? 0),
                'in_progress_tutorials' => (int)($progress['in_progress_tutorials'] ?? 0),
                'learning_hours' => $learning_hours
            ];
            
        } catch (Exception $e) {
            return [
                'completed_tutorials' => 0,
                'in_progress_tutorials' => 0,
                'learning_hours' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Get user ID
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $userStmt->execute(['soudhame52@gmail.com']);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    $userId = $user['id'];
    
    echo "Testing learning stats for User ID: {$userId}\n\n";
    
    $stats = getLearningStats($pdo, $userId);
    
    echo "=== LEARNING STATISTICS ===\n";
    echo "Completed tutorials: " . $stats['completed_tutorials'] . "\n";
    echo "In progress tutorials: " . $stats['in_progress_tutorials'] . "\n";
    echo "Learning hours: " . $stats['learning_hours'] . "\n";
    
    if (isset($stats['error'])) {
        echo "Error: " . $stats['error'] . "\n";
    }
    
    echo "\nTotal watched videos: " . ($stats['completed_tutorials'] + $stats['in_progress_tutorials']) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>