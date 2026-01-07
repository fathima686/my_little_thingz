<?php
// Debug script to test live sessions API directly
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Live Sessions API Debug</h2>";

// Test 1: Direct API call to live-subjects
echo "<h3>1. Testing live-subjects API</h3>";
$subjectsUrl = "http://localhost/my_little_thingz/backend/api/customer/live-subjects.php";
$subjectsResponse = file_get_contents($subjectsUrl);
echo "<strong>Response:</strong><br>";
echo "<pre>" . htmlspecialchars($subjectsResponse) . "</pre>";

// Test 2: Direct API call to live-sessions
echo "<h3>2. Testing live-sessions API</h3>";
$sessionsUrl = "http://localhost/my_little_thingz/backend/api/customer/live-sessions.php";

// Create context with headers
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'X-User-ID: 1',
            'Content-Type: application/json'
        ]
    ]
]);

$sessionsResponse = file_get_contents($sessionsUrl, false, $context);
echo "<strong>Response:</strong><br>";
echo "<pre>" . htmlspecialchars($sessionsResponse) . "</pre>";

// Test 3: Check if database tables exist
echo "<h3>3. Database Table Check</h3>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Check live_subjects table
    $tableCheck = $db->query("SHOW TABLES LIKE 'live_subjects'");
    echo "live_subjects table exists: " . ($tableCheck->rowCount() > 0 ? "YES" : "NO") . "<br>";
    
    // Check live_sessions table
    $tableCheck = $db->query("SHOW TABLES LIKE 'live_sessions'");
    echo "live_sessions table exists: " . ($tableCheck->rowCount() > 0 ? "YES" : "NO") . "<br>";
    
    // Check live_session_registrations table
    $tableCheck = $db->query("SHOW TABLES LIKE 'live_session_registrations'");
    echo "live_session_registrations table exists: " . ($tableCheck->rowCount() > 0 ? "YES" : "NO") . "<br>";
    
    echo "<br><strong>Database connection: SUCCESS</strong>";
    
} catch (Exception $e) {
    echo "<br><strong>Database error:</strong> " . $e->getMessage();
}

// Test 4: Check user subscription status
echo "<h3>4. User Subscription Check (User ID: 1)</h3>";
try {
    $userStmt = $db->prepare("SELECT id, email, subscription_plan FROM users WHERE id = 1");
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "User found: " . $user['email'] . "<br>";
        echo "Subscription plan: " . ($user['subscription_plan'] ?? 'none') . "<br>";
    } else {
        echo "User ID 1 not found<br>";
    }
    
} catch (Exception $e) {
    echo "Error checking user: " . $e->getMessage();
}
?>