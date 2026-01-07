<?php
// Check server status and PHP configuration
echo "Server Status Check\n";
echo "==================\n\n";

echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
echo "Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'Unknown') . "\n\n";

echo "Error Reporting Settings:\n";
echo "error_reporting: " . error_reporting() . "\n";
echo "display_errors: " . ini_get('display_errors') . "\n";
echo "display_startup_errors: " . ini_get('display_startup_errors') . "\n";
echo "log_errors: " . ini_get('log_errors') . "\n\n";

echo "Testing database connection:\n";
try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    echo "✓ Database connection successful\n";
    
    // Test notifications table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Notifications table accessible, {$count['count']} records\n";
    
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}

echo "\nTesting API endpoint access:\n";
$apiPath = __DIR__ . '/api/customer/notifications.php';
if (file_exists($apiPath)) {
    echo "✓ API file exists: $apiPath\n";
} else {
    echo "✗ API file not found: $apiPath\n";
}

echo "\nURL that should work:\n";
echo "http://localhost/my_little_thingz/backend/api/customer/notifications.php\n";
?>