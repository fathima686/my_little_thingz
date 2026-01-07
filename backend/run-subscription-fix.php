<?php
// Simple script to run the subscription fix directly
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Running Subscription Fix</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} .container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:8px;} .success{color:#10b981;} .error{color:#ef4444;} .info{color:#3b82f6;}</style>";
echo "</head><body><div class='container'>";

echo "<h1>🔧 Running Subscription Fix</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $userEmail = 'soudhame52@gmail.com';
    
    echo "<h2 class='info'>Step 1: Analyzing Current Subscriptions</h2>";
    
    // Get all subscriptions for the user
    $stmt = $db->prepare("SELECT * FROM subscriptions WHERE email = ? ORDER BY created_at DESC");
    $stmt->execute([$userEmail]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($subscriptions) . " subscription records for $userEmail</p>";
    
    foreach ($subscriptions as $sub) {
        $status = $sub['is_active'] ? '<span class="success">ACTIVE</span>' : '<span class="error">INACTIVE</span>';
        echo "<p>- Plan: {$sub['plan_code']}, Status: {$sub['subscription_status']}, Active: $status</p>";
    }
    
    echo "<h2 class='info'>Step 2: Cleaning Up Inactive Subscriptions</h2>";
    
    // Delete all inactive subscriptions
    $deleteStmt = $db->prepare("DELETE FROM subscriptions WHERE email = ? AND is_active = 0");
    $deleteStmt->execute([$userEmail]);
    $deletedCount = $deleteStmt->rowCount();
    
    echo "<p class='success'>✓ Deleted $deletedCount inactive subscriptions</p>";
    
    echo "<h2 class='info'>Step 3: Ensuring Active Subscription</h2>";
    
    // Check if user has any active subscription
    $activeStmt = $db->prepare("SELECT * FROM subscriptions WHERE email = ? AND is_active = 1");
    $activeStmt->execute([$userEmail]);
    $activeSubscription = $activeStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activeSubscription) {
        // Create a fresh free subscription
        $createStmt = $db->prepare("
            INSERT INTO subscriptions (email, plan_code, subscription_status, is_active, created_at) 
            VALUES (?, 'free', 'active', 1, NOW())
        ");
        $createStmt->execute([$userEmail]);
        echo "<p class='success'>✓ Created fresh active 'free' subscription</p>";
    } else {
        echo "<p class='info'>ℹ User already has active subscription: {$activeSubscription['plan_code']}</p>";
    }
    
    echo "<h2 class='info'>Step 4: Creating Improved APIs</h2>";
    
    // Create the improved upgrade API
    $upgradeApiContent = '<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

try {
    require_once "../../config/database.php";
    $database = new Database();
    $db = $database->getConnection();
    
    $input = json_decode(file_get_contents("php://input"), true);
    $userEmail = $_SERVER["HTTP_X_TUTORIAL_EMAIL"] ?? $input["email"] ?? null;
    $newPlanCode = $input["plan_code"] ?? null;
    
    if (!$userEmail || !$newPlanCode) {
        echo json_encode(["status" => "error", "message" => "Email and plan_code are required"]);
        exit;
    }
    
    // Validate plan exists
    $planStmt = $db->prepare("SELECT * FROM subscription_plans WHERE plan_code = ?");
    $planStmt->execute([$newPlanCode]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        echo json_encode(["status" => "error", "message" => "Invalid plan code: $newPlanCode"]);
        exit;
    }
    
    // Check if user already has an ACTIVE subscription with this plan
    $activeStmt = $db->prepare("SELECT * FROM subscriptions WHERE email = ? AND plan_code = ? AND is_active = 1");
    $activeStmt->execute([$userEmail, $newPlanCode]);
    $existingActive = $activeStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingActive) {
        echo json_encode([
            "status" => "error",
            "message" => "You already have an active " . $plan["plan_name"] . " subscription"
        ]);
        exit;
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Deactivate all existing subscriptions
        $deactivateStmt = $db->prepare("UPDATE subscriptions SET is_active = 0 WHERE email = ?");
        $deactivateStmt->execute([$userEmail]);
        
        // Create new subscription
        $createStmt = $db->prepare("
            INSERT INTO subscriptions (email, plan_code, subscription_status, is_active, created_at) 
            VALUES (?, ?, \"active\", 1, NOW())
        ");
        $createStmt->execute([$userEmail, $newPlanCode]);
        
        $db->commit();
        
        echo json_encode([
            "status" => "success",
            "message" => "Successfully upgraded to " . $plan["plan_name"],
            "subscription" => [
                "plan_code" => $newPlanCode,
                "plan_name" => $plan["plan_name"],
                "subscription_status" => "active",
                "is_active" => true
            ]
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>';
    
    file_put_contents('api/customer/upgrade-subscription-fixed.php', $upgradeApiContent);
    echo "<p class='success'>✓ Created improved upgrade API</p>";
    
    echo "<h2 class='success'>✅ Fix Complete!</h2>";
    echo "<p>The subscription fix has been applied successfully. Now you can:</p>";
    echo "<ul>";
    echo "<li>✅ User has clean active subscription</li>";
    echo "<li>✅ Inactive subscriptions removed</li>";
    echo "<li>✅ Improved upgrade API created</li>";
    echo "<li>✅ Ready for testing upgrades</li>";
    echo "</ul>";
    
    echo "<h3>🧪 Test the Fix:</h3>";
    echo "<p><a href='test-upgrade-now.php' target='_blank' style='background:#3b82f6;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;'>Test Upgrade Now</a></p>";
    
    // Show current status
    echo "<h3>📊 Current Status:</h3>";
    $finalStmt = $db->prepare("SELECT s.*, sp.plan_name FROM subscriptions s LEFT JOIN subscription_plans sp ON s.plan_code = sp.plan_code WHERE s.email = ? AND s.is_active = 1");
    $finalStmt->execute([$userEmail]);
    $finalSub = $finalStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($finalSub) {
        echo "<p class='success'>Current active subscription: <strong>{$finalSub['plan_code']}</strong> ({$finalSub['plan_name']})</p>";
    } else {
        echo "<p class='error'>No active subscription found</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background:#fee2e2;padding:15px;border-radius:5px;color:#991b1b;'>";
    echo "<h3>❌ Error occurred:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div></body></html>";
?>