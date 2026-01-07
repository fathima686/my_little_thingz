<?php
// Simple test script to verify profile and notifications APIs
require_once 'config/database.php';

echo "Testing Profile and Notifications APIs\n";
echo "=====================================\n\n";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Test 1: Check if tables exist
    echo "1. Checking database tables...\n";
    
    $tables = ['notifications', 'user_profiles', 'users'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE '$table'");
        $stmt->execute();
        if ($stmt->fetch()) {
            echo "   ✓ Table '$table' exists\n";
        } else {
            echo "   ❌ Table '$table' missing\n";
        }
    }
    
    // Test 2: Check if we have users
    echo "\n2. Checking users...\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   Users in database: $userCount\n";
    
    if ($userCount > 0) {
        // Get a sample user
        $stmt = $pdo->prepare("SELECT id, email FROM users LIMIT 1");
        $stmt->execute();
        $sampleUser = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   Sample user: {$sampleUser['email']} (ID: {$sampleUser['id']})\n";
        
        // Test 3: Check notifications for sample user
        echo "\n3. Checking notifications...\n";
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ?");
        $stmt->execute([$sampleUser['id']]);
        $notificationCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "   Notifications for user: $notificationCount\n";
        
        // Test 4: Check profile for sample user
        echo "\n4. Checking user profile...\n";
        $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$sampleUser['id']]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($profile) {
            echo "   ✓ Profile exists for user\n";
            echo "   Name: {$profile['first_name']} {$profile['last_name']}\n";
        } else {
            echo "   ⚠ No profile found for user (this is normal for new users)\n";
        }
    }
    
    echo "\n✅ Database tests completed successfully!\n";
    echo "\nAPI Endpoints to test:\n";
    echo "- GET  http://localhost/my_little_thingz/backend/api/customer/notifications.php\n";
    echo "- GET  http://localhost/my_little_thingz/backend/api/customer/profile.php\n";
    echo "- PUT  http://localhost/my_little_thingz/backend/api/customer/profile.php\n";
    echo "\nMake sure to include 'X-Tutorial-Email' header with a valid user email.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>