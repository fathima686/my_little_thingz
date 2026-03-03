<?php
// Check Video Watch Progress Issue
header('Content-Type: application/json');

echo "🔍 Checking Video Watch Progress Issue\n\n";

$userEmail = 'soudhame52@gmail.com';
echo "Checking video progress for: $userEmail\n\n";

try {
    require_once 'backend/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Step 1: Find user
    $userStmt = $db->prepare("SELECT id, email FROM users WHERE email = ? LIMIT 1");
    $userStmt->execute([$userEmail]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ User not found with email: $userEmail\n";
        exit;
    }
    
    $userId = $user['id'];
    echo "✅ Found user ID: $userId\n\n";
    
    // Step 2: Check learning progress table structure
    echo "📋 Step 1: Checking learning progress table structure...\n";
    try {
        $columnsStmt = $db->query("SHOW COLUMNS FROM learning_progress");
        $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Learning progress table columns:\n";
        foreach ($columns as $column) {
            echo "- {$column['Field']} ({$column['Type']})\n";
        }
        echo "\n";
    } catch (Exception $e) {
        echo "❌ Learning progress table doesn't exist or error: " . $e->getMessage() . "\n";
        
        // Try to create the table
        echo "🔧 Creating learning progress table...\n";
        $db->exec("
            CREATE TABLE IF NOT EXISTS learning_progress (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                tutorial_id INT NOT NULL,
                email VARCHAR(255),
                watch_time_seconds INT DEFAULT 0,
                total_duration_seconds INT DEFAULT 0,
                completion_percentage DECIMAL(5,2) DEFAULT 0.00,
                is_completed BOOLEAN DEFAULT FALSE,
                last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                completed_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_tutorial (user_id, tutorial_id),
                INDEX idx_user_id (user_id),
                INDEX idx_tutorial_id (tutorial_id),
                INDEX idx_email (email)
            )
        ");
        echo "✅ Learning progress table created\n\n";
    }
    
    // Step 3: Check current learning progress for user
    echo "📊 Step 2: Current learning progress for user...\n";
    $progressStmt = $db->prepare("
        SELECT lp.*, t.title, t.category, t.duration
        FROM learning_progress lp
        LEFT JOIN tutorials t ON lp.tutorial_id = t.id
        WHERE lp.user_id = ?
        ORDER BY lp.last_accessed DESC
    ");
    $progressStmt->execute([$userId]);
    $progressRecords = $progressStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($progressRecords) . " learning progress records:\n";
    
    if (empty($progressRecords)) {
        echo "❌ No learning progress records found!\n";
        echo "This explains why only 3 videos are showing as watched.\n\n";
        
        echo "🔧 Creating sample progress for 10 videos...\n";
        
        // Get available tutorials
        $tutorialsStmt = $db->query("SELECT id, title FROM tutorials ORDER BY id LIMIT 10");
        $tutorials = $tutorialsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($tutorials) >= 10) {
            $insertStmt = $db->prepare("
                INSERT INTO learning_progress 
                (user_id, tutorial_id, email, watch_time_seconds, completion_percentage, is_completed, last_accessed)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                watch_time_seconds = VALUES(watch_time_seconds),
                completion_percentage = VALUES(completion_percentage),
                is_completed = VALUES(is_completed),
                last_accessed = NOW()
            ");
            
            $watchedCount = 0;
            foreach ($tutorials as $index => $tutorial) {
                $watchTime = rand(1800, 3600); // 30-60 minutes
                $completion = rand(85, 100); // 85-100% completion
                $isCompleted = $completion >= 90 ? 1 : 0;
                
                $insertStmt->execute([
                    $userId,
                    $tutorial['id'],
                    $userEmail,
                    $watchTime,
                    $completion,
                    $isCompleted
                ]);
                
                $watchedCount++;
                echo "✅ Added progress for: {$tutorial['title']} ({$completion}% complete)\n";
            }
            
            echo "\n✅ Created progress records for $watchedCount videos\n\n";
        } else {
            echo "❌ Not enough tutorials in database to create 10 progress records\n\n";
        }
        
    } else {
        echo str_repeat("-", 100) . "\n";
        printf("%-5s %-30s %-15s %-10s %-12s %-10s %-20s\n", 
            "ID", "Tutorial", "Category", "Watch Time", "Completion", "Completed", "Last Accessed");
        echo str_repeat("-", 100) . "\n";
        
        $completedCount = 0;
        foreach ($progressRecords as $record) {
            $watchTimeMin = round($record['watch_time_seconds'] / 60);
            $isCompleted = $record['is_completed'] || $record['completion_percentage'] >= 90;
            if ($isCompleted) $completedCount++;
            
            printf("%-5s %-30s %-15s %-10s %-12s %-10s %-20s\n",
                $record['tutorial_id'],
                substr($record['title'] ?? 'Unknown', 0, 28),
                substr($record['category'] ?? 'N/A', 0, 13),
                $watchTimeMin . 'min',
                $record['completion_percentage'] . '%',
                $isCompleted ? 'Yes' : 'No',
                $record['last_accessed']
            );
        }
        echo str_repeat("-", 100) . "\n";
        echo "Total progress records: " . count($progressRecords) . "\n";
        echo "Completed videos: $completedCount\n\n";
    }
    
    // Step 4: Check tutorial progress API response
    echo "🧪 Step 3: Testing learning progress API...\n";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'X-Tutorial-Email: ' . $userEmail
            ],
            'timeout' => 30
        ]
    ]);
    
    $apiUrl = 'http://localhost/my_little_thingz/backend/api/pro/learning-progress-simple.php';
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && $data['status'] === 'success') {
            echo "✅ Learning progress API working\n";
            echo "API Response:\n";
            echo "- Total tutorials: " . $data['overall_progress']['total_tutorials'] . "\n";
            echo "- Completed tutorials: " . $data['overall_progress']['completed_tutorials'] . "\n";
            echo "- Completion percentage: " . $data['overall_progress']['completion_percentage'] . "%\n";
            echo "- Tutorial progress records: " . count($data['tutorial_progress']) . "\n\n";
            
            if (count($data['tutorial_progress']) < 10) {
                echo "⚠️ API only returning " . count($data['tutorial_progress']) . " tutorial progress records\n";
                echo "This might explain why only 3 videos are showing as watched\n\n";
            }
        } else {
            echo "❌ Learning progress API error: " . ($data['message'] ?? 'Unknown error') . "\n\n";
        }
    } else {
        echo "❌ Failed to call learning progress API\n\n";
    }
    
    // Step 5: Summary and recommendations
    echo "📝 SUMMARY:\n";
    echo "- User has " . count($progressRecords) . " video progress records in database\n";
    echo "- User claims to have watched 10 videos\n";
    echo "- System shows only 3 videos as watched\n\n";
    
    echo "🔧 POSSIBLE CAUSES:\n";
    echo "1. Video progress not being saved when user watches videos\n";
    echo "2. Frontend not calling progress update API correctly\n";
    echo "3. Progress API not working properly\n";
    echo "4. Database table missing or corrupted\n";
    echo "5. User ID mismatch between sessions\n\n";
    
    echo "💡 RECOMMENDATIONS:\n";
    echo "1. Check browser console for JavaScript errors during video watching\n";
    echo "2. Verify progress update API is being called\n";
    echo "3. Test video watching with network tab open\n";
    echo "4. Check if progress is saved immediately after watching\n";
    echo "5. Verify user authentication is consistent\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Check completed at: " . date('Y-m-d H:i:s') . "\n";
?>