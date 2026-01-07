<?php
echo "Starting database update for subscription system...\n";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Read and execute the SQL file
    $sql = file_get_contents('database/subscription-system-update.sql');
    $statements = explode(';', $sql);
    
    $executed = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && $statement !== 'COMMIT') {
            try {
                $pdo->exec($statement);
                echo "✓ Executed: " . substr($statement, 0, 60) . "...\n";
                $executed++;
            } catch (Exception $e) {
                echo "✗ Error in statement: " . $e->getMessage() . "\n";
                echo "  Statement: " . substr($statement, 0, 100) . "...\n";
                $errors++;
            }
        }
    }
    
    echo "\n=== Database Update Summary ===\n";
    echo "Statements executed: $executed\n";
    echo "Errors encountered: $errors\n";
    
    if ($errors === 0) {
        echo "✅ Database update completed successfully!\n";
    } else {
        echo "⚠️  Database update completed with some errors.\n";
    }
    
    // Test the subscription system
    echo "\n=== Testing Subscription System ===\n";
    
    // Check if subscription_plans table has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM subscription_plans");
    $planCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Subscription plans available: $planCount\n";
    
    // Check if tutorials table has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tutorials");
    $tutorialCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Tutorials available: $tutorialCount\n";
    
    // Check test user subscription
    $stmt = $pdo->prepare("SELECT plan_code, subscription_status FROM subscriptions WHERE email = ?");
    $stmt->execute(['soudhame52@gmail.com']);
    $testSub = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testSub) {
        echo "Test user subscription: {$testSub['plan_code']} ({$testSub['subscription_status']})\n";
    } else {
        echo "Test user subscription: Not found\n";
    }
    
    echo "\n🎉 Subscription system is ready for testing!\n";
    echo "Open test-subscription-system.html in your browser to test all features.\n";
    
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "\n";
    exit(1);
}
?>