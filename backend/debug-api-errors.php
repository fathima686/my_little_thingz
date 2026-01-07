<?php
// Enable error reporting to see what's causing 500 errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>API Debug Tool</h1>";
echo "<p>Testing APIs that are returning 500 errors...</p>";

// Test 1: Database connection
echo "<h2>1. Testing Database Connection</h2>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    echo "✅ Database connection successful<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    echo "Check if config/database.php exists and has correct credentials<br>";
}

// Test 2: Check if tables exist
echo "<h2>2. Checking Required Tables</h2>";
try {
    $tables = ['users', 'tutorials', 'subscriptions', 'subscription_plans', 'user_profiles'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' missing<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking tables: " . $e->getMessage() . "<br>";
}

// Test 3: Test tutorials API directly
echo "<h2>3. Testing Tutorials API</h2>";
try {
    $_SERVER['HTTP_X_TUTORIAL_EMAIL'] = 'test@example.com';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    ob_start();
    include 'api/customer/tutorials.php';
    $output = ob_get_clean();
    
    echo "API Output:<br>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
} catch (Exception $e) {
    echo "❌ Tutorials API error: " . $e->getMessage() . "<br>";
}

// Test 4: Test subscription status API
echo "<h2>4. Testing Subscription Status API</h2>";
try {
    $_SERVER['HTTP_X_TUTORIAL_EMAIL'] = 'test@example.com';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    ob_start();
    include 'api/customer/subscription-status.php';
    $output = ob_get_clean();
    
    echo "API Output:<br>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
} catch (Exception $e) {
    echo "❌ Subscription API error: " . $e->getMessage() . "<br>";
}

// Test 5: Test profile API
echo "<h2>5. Testing Profile API</h2>";
try {
    $_SERVER['HTTP_X_TUTORIAL_EMAIL'] = 'test@example.com';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    ob_start();
    include 'api/customer/profile.php';
    $output = ob_get_clean();
    
    echo "API Output:<br>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
} catch (Exception $e) {
    echo "❌ Profile API error: " . $e->getMessage() . "<br>";
}
?>