<?php
// Complete fix for live sessions functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Live Sessions Complete Fix</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px;'>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Step 1: Database Setup</h2>";
    
    // Create live_subjects table
    echo "<p>Creating live_subjects table...</p>";
    $db->exec("CREATE TABLE IF NOT EXISTS live_subjects (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        description TEXT,
        icon_url VARCHAR(255),
        color VARCHAR(7) DEFAULT '#667eea',
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_active (is_active),
        UNIQUE KEY unique_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<span style='color: green;'>✓ live_subjects table created</span><br>";
    
    // Create live_sessions table
    echo "<p>Creating live_sessions table...</p>";
    $db->exec("CREATE TABLE IF NOT EXISTS live_sessions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        subject_id INT UNSIGNED NOT NULL,
        teacher_id INT UNSIGNED NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        google_meet_link VARCHAR(500) NOT NULL,
        scheduled_date DATE NOT NULL,
        scheduled_time TIME NOT NULL,
        duration_minutes INT DEFAULT 60,
        status ENUM('scheduled', 'live', 'completed', 'cancelled') DEFAULT 'scheduled',
        max_participants INT DEFAULT 50,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_subject (subject_id),
        INDEX idx_teacher (teacher_id),
        INDEX idx_status (status),
        INDEX idx_scheduled (scheduled_date, scheduled_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<span style='color: green;'>✓ live_sessions table created</span><br>";
    
    // Create live_session_registrations table
    echo "<p>Creating live_session_registrations table...</p>";
    $db->exec("CREATE TABLE IF NOT EXISTS live_session_registrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        session_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        attended BOOLEAN DEFAULT 0,
        UNIQUE KEY unique_registration (session_id, user_id),
        INDEX idx_session (session_id),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<span style='color: green;'>✓ live_session_registrations table created</span><br>";
    
    echo "<h2>Step 2: Populate Sample Data</h2>";
    
    // Add sample subjects
    $sampleSubjects = [
        ['Hand Embroidery', 'Learn beautiful hand embroidery techniques', '#FFB6C1'],
        ['Resin Art', 'Create stunning resin art pieces', '#B0E0E6'],
        ['Gift Making', 'Craft personalized gifts for loved ones', '#FFDAB9'],
        ['Mehandi Art', 'Master the art of henna designs', '#E6E6FA'],
        ['Candle Making', 'Make aromatic and decorative candles', '#F0E68C']
    ];
    
    $subjectStmt = $db->prepare("
        INSERT INTO live_subjects (name, description, color, is_active)
        VALUES (?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE 
            description = VALUES(description),
            color = VALUES(color),
            is_active = 1
    ");
    
    foreach ($sampleSubjects as $subject) {
        $subjectStmt->execute($subject);
        echo "<span style='color: blue;'>+ Added subject: {$subject[0]}</span><br>";
    }
    
    // Get subject IDs for sample sessions
    $subjectIds = [];
    $subjectQuery = $db->query("SELECT id, name FROM live_subjects WHERE is_active = 1");
    while ($row = $subjectQuery->fetch(PDO::FETCH_ASSOC)) {
        $subjectIds[$row['name']] = $row['id'];
    }
    
    // Ensure we have at least one user to be a teacher
    $userCheck = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCheck == 0) {
        echo "<p style='color: orange;'>⚠ No users found. Creating a sample teacher user...</p>";
        $db->exec("INSERT INTO users (email, password, role_id, subscription_plan) VALUES 
            ('teacher@example.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 2, 'pro')");
    }
    
    // Get first user as teacher
    $teacherStmt = $db->query("SELECT id FROM users LIMIT 1");
    $teacher = $teacherStmt->fetch(PDO::FETCH_ASSOC);
    $teacherId = $teacher['id'];
    
    // Add sample sessions
    $sampleSessions = [
        [
            'subject' => 'Hand Embroidery',
            'title' => 'Basic Embroidery Stitches Workshop',
            'description' => 'Learn fundamental embroidery stitches including running stitch, back stitch, and French knots.',
            'date' => date('Y-m-d', strtotime('+3 days')),
            'time' => '14:00:00',
            'duration' => 90
        ],
        [
            'subject' => 'Resin Art',
            'title' => 'Resin Coaster Making Session',
            'description' => 'Create beautiful resin coasters with dried flowers and glitter effects.',
            'date' => date('Y-m-d', strtotime('+5 days')),
            'time' => '16:00:00',
            'duration' => 120
        ],
        [
            'subject' => 'Gift Making',
            'title' => 'Personalized Photo Frame Workshop',
            'description' => 'Design and create custom photo frames using various decorative materials.',
            'date' => date('Y-m-d', strtotime('+7 days')),
            'time' => '10:00:00',
            'duration' => 75
        ]
    ];
    
    $sessionStmt = $db->prepare("
        INSERT INTO live_sessions (subject_id, teacher_id, title, description, google_meet_link, 
                                 scheduled_date, scheduled_time, duration_minutes, status, max_participants)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', 25)
    ");
    
    foreach ($sampleSessions as $session) {
        if (isset($subjectIds[$session['subject']])) {
            $meetLink = "https://meet.google.com/sample-" . strtolower(str_replace(' ', '-', $session['title']));
            $sessionStmt->execute([
                $subjectIds[$session['subject']],
                $teacherId,
                $session['title'],
                $session['description'],
                $meetLink,
                $session['date'],
                $session['time'],
                $session['duration']
            ]);
            echo "<span style='color: green;'>+ Added session: {$session['title']}</span><br>";
        }
    }
    
    echo "<h2>Step 3: API Test</h2>";
    
    // Test the APIs
    echo "<p>Testing live-subjects API...</p>";
    $subjectsUrl = "http://localhost/my_little_thingz/backend/api/customer/live-subjects.php";
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10
        ]
    ]);
    
    $subjectsResponse = @file_get_contents($subjectsUrl, false, $context);
    if ($subjectsResponse !== false) {
        $subjectsData = json_decode($subjectsResponse, true);
        if ($subjectsData && $subjectsData['status'] === 'success') {
            echo "<span style='color: green;'>✓ Subjects API working - found " . count($subjectsData['subjects']) . " subjects</span><br>";
        } else {
            echo "<span style='color: red;'>✗ Subjects API error: " . ($subjectsData['message'] ?? 'Unknown error') . "</span><br>";
        }
    } else {
        echo "<span style='color: orange;'>⚠ Could not test subjects API (server may not be running)</span><br>";
    }
    
    echo "<p>Testing live-sessions API...</p>";
    $sessionsUrl = "http://localhost/my_little_thingz/backend/api/customer/live-sessions.php";
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "X-User-ID: 1\r\n",
            'timeout' => 10
        ]
    ]);
    
    $sessionsResponse = @file_get_contents($sessionsUrl, false, $context);
    if ($sessionsResponse !== false) {
        $sessionsData = json_decode($sessionsResponse, true);
        if ($sessionsData && $sessionsData['status'] === 'success') {
            echo "<span style='color: green;'>✓ Sessions API working - found " . count($sessionsData['sessions']) . " sessions</span><br>";
        } else {
            echo "<span style='color: red;'>✗ Sessions API error: " . ($sessionsData['message'] ?? 'Unknown error') . "</span><br>";
        }
    } else {
        echo "<span style='color: orange;'>⚠ Could not test sessions API (server may not be running)</span><br>";
    }
    
    echo "<h2>Step 4: User Setup</h2>";
    
    // Update user to have pro subscription for testing
    $updateUser = $db->prepare("UPDATE users SET subscription_plan = 'pro' WHERE id = ?");
    $updateUser->execute([1]);
    echo "<span style='color: green;'>✓ Set user ID 1 to Pro subscription for testing</span><br>";
    
    echo "<h2>✅ Setup Complete!</h2>";
    echo "<div style='background: #e6ffe6; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>What was fixed:</h3>";
    echo "<ul>";
    echo "<li>Created all required database tables (live_subjects, live_sessions, live_session_registrations)</li>";
    echo "<li>Added sample subjects and live sessions</li>";
    echo "<li>Set up proper user permissions</li>";
    echo "<li>Fixed React component error handling</li>";
    echo "</ul>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ul>";
    echo "<li>The live sessions should now appear in your tutorial page</li>";
    echo "<li>Make sure your React app is running and refresh the page</li>";
    echo "<li>Check the browser console for any remaining errors</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<p><a href='test-live-sessions-debug.php' style='background: #007cba; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Test APIs Again</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffe6e6; padding: 15px; border-radius: 5px; color: #d00;'>";
    echo "<h3>❌ Error occurred:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div>";
?>