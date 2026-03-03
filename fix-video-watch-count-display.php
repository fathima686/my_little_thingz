<?php
// Fix Video Watch Count Display
header('Content-Type: application/json');

echo "🔧 Fixing Video Watch Count Display\n\n";

$userEmail = 'soudhame52@gmail.com';
echo "Fixing video count display for: $userEmail\n\n";

try {
    require_once 'backend/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Find user
    $userStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $userStmt->execute([$userEmail]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    $userId = $user['id'];
    
    echo "✅ User ID: $userId\n\n";
    
    // Step 1: Analyze current progress data
    echo "📊 Step 1: Analyzing current progress data...\n";
    
    $analysisStmt = $db->prepare("
        SELECT 
            COUNT(*) as total_progress_records,
            COUNT(CASE WHEN completion_percentage > 0 THEN 1 END) as watched_videos,
            COUNT(CASE WHEN completion_percentage >= 80 THEN 1 END) as completed_videos_80,
            COUNT(CASE WHEN completion_percentage >= 90 THEN 1 END) as completed_videos_90,
            COUNT(CASE WHEN completion_percentage = 100 THEN 1 END) as completed_videos_100,
            AVG(completion_percentage) as avg_completion,
            SUM(watch_time_seconds) as total_watch_time
        FROM learning_progress 
        WHERE user_id = ?
    ");
    $analysisStmt->execute([$userId]);
    $analysis = $analysisStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Current Progress Analysis:\n";
    echo "- Total progress records: {$analysis['total_progress_records']}\n";
    echo "- Watched videos (>0% progress): {$analysis['watched_videos']}\n";
    echo "- Completed videos (≥80%): {$analysis['completed_videos_80']}\n";
    echo "- Completed videos (≥90%): {$analysis['completed_videos_90']}\n";
    echo "- Completed videos (100%): {$analysis['completed_videos_100']}\n";
    echo "- Average completion: " . round($analysis['avg_completion'], 1) . "%\n";
    echo "- Total watch time: " . round($analysis['total_watch_time'] / 3600, 1) . " hours\n\n";
    
    // Step 2: Show detailed breakdown
    echo "📋 Step 2: Detailed video breakdown...\n";
    
    $detailStmt = $db->prepare("
        SELECT lp.tutorial_id, t.title, lp.completion_percentage, lp.watch_time_seconds,
               CASE 
                   WHEN lp.completion_percentage >= 80 THEN 'Completed'
                   WHEN lp.completion_percentage > 0 THEN 'Watched'
                   ELSE 'Not Started'
               END as status
        FROM learning_progress lp
        LEFT JOIN tutorials t ON lp.tutorial_id = t.id
        WHERE lp.user_id = ?
        ORDER BY lp.completion_percentage DESC
    ");
    $detailStmt->execute([$userId]);
    $details = $detailStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo str_repeat("-", 80) . "\n";
    printf("%-5s %-30s %-12s %-10s %-12s\n", "ID", "Title", "Completion", "Watch Time", "Status");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($details as $detail) {
        $watchTimeMin = round($detail['watch_time_seconds'] / 60);
        printf("%-5s %-30s %-12s %-10s %-12s\n",
            $detail['tutorial_id'],
            substr($detail['title'] ?? 'Unknown', 0, 28),
            $detail['completion_percentage'] . '%',
            $watchTimeMin . 'min',
            $detail['status']
        );
    }
    echo str_repeat("-", 80) . "\n\n";
    
    // Step 3: Update the learning progress API to show both counts
    echo "🔧 Step 3: The issue is in how 'completed' vs 'watched' is defined...\n\n";
    
    echo "CURRENT SYSTEM LOGIC:\n";
    echo "- 'Completed' = completion_percentage >= 80% OR practice approved\n";
    echo "- 'Watched' = any video with completion_percentage > 0%\n\n";
    
    echo "YOUR SITUATION:\n";
    echo "- You have watched {$analysis['watched_videos']} videos (any progress > 0%)\n";
    echo "- System shows {$analysis['completed_videos_80']} as 'completed' (≥80% progress)\n";
    echo "- The frontend displays 'completed' count, not 'watched' count\n\n";
    
    // Step 4: Test the API response
    echo "🧪 Step 4: Testing current API response...\n";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => ['X-Tutorial-Email: ' . $userEmail],
            'timeout' => 30
        ]
    ]);
    
    $apiUrl = 'http://localhost/my_little_thingz/backend/api/pro/learning-progress-simple.php';
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && $data['status'] === 'success') {
            echo "API Currently Returns:\n";
            echo "- Total tutorials: " . $data['overall_progress']['total_tutorials'] . "\n";
            echo "- Completed tutorials: " . $data['overall_progress']['completed_tutorials'] . "\n";
            echo "- Completion percentage: " . $data['overall_progress']['completion_percentage'] . "%\n\n";
            
            echo "What you want to see:\n";
            echo "- Watched videos: {$analysis['watched_videos']} (videos with any progress)\n";
            echo "- Completed videos: {$analysis['completed_videos_80']} (videos ≥80% complete)\n\n";
        }
    }
    
    // Step 5: Provide solution options
    echo "💡 SOLUTION OPTIONS:\n\n";
    
    echo "Option 1: MODIFY API to include 'watched_videos' count\n";
    echo "- Add watched_videos field to API response\n";
    echo "- Frontend can show both watched and completed counts\n";
    echo "- Example: 'Watched: 10 videos, Completed: 4 videos'\n\n";
    
    echo "Option 2: CHANGE completion criteria to be less strict\n";
    echo "- Lower completion threshold from 80% to 50%\n";
    echo "- More videos will show as 'completed'\n";
    echo "- Risk: Less meaningful completion status\n\n";
    
    echo "Option 3: UPDATE frontend to show 'watched' instead of 'completed'\n";
    echo "- Change UI labels from 'Completed' to 'Watched'\n";
    echo "- Count any video with progress > 0% as 'watched'\n";
    echo "- More intuitive for users\n\n";
    
    echo "🎯 RECOMMENDED SOLUTION: Option 1 (Add watched count to API)\n";
    echo "This gives users the most complete information:\n";
    echo "- Shows total videos watched (any progress)\n";
    echo "- Shows videos completed (high progress)\n";
    echo "- Maintains meaningful completion criteria\n\n";
    
    // Step 6: Implement the recommended solution
    echo "🚀 Step 6: Implementing recommended solution...\n";
    echo "I'll modify the learning progress API to include watched_videos count.\n\n";
    
    echo "✅ SUMMARY:\n";
    echo "- You have watched {$analysis['watched_videos']} videos (any progress > 0%)\n";
    echo "- System currently shows {$analysis['completed_videos_80']} as completed (≥80%)\n";
    echo "- The discrepancy is due to strict completion criteria\n";
    echo "- Solution: Update API and frontend to show both counts\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Analysis completed at: " . date('Y-m-d H:i:s') . "\n";
?>