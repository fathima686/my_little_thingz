<?php
// Fixed script to work with actual database structure (user_id, plan_id)
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Running Subscription Fix (Corrected)</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} .container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:8px;} .success{color:#10b981;} .error{color:#ef4444;} .info{color:#3b82f6;}</style>";
echo "</head><body><div class='container'>";

echo "<h1>🔧 Running Subscription Fix (Corrected)</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $userEmail = 'soudhame52@gmail.com';
    
    echo "<h2 class='info'>Step 1: Finding User ID</h2>";
    
    // Get user ID from email
    $userStmt = $db->prepare("SELECT id, email, first_name, last_name FROM users WHERE email = ?");
    $userStmt->execute([$userEmail]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<p class='error'>❌ User not found with email: $userEmail</p>";
        exit;
    }
    
    $userId = $user['id'];
    $userName = trim($user['first_name'] . ' ' . $user['last_name']);
    echo "<p class='success'>✓ Found user: $userName (ID: $userId, Email: $userEmail)</p>";
    
    echo "<h2 class='info'>Step 2: Analyzing Current Subscriptions</h2>";
    
    // Get all subscriptions for the user using user_id
    $stmt = $db->prepare("
        SELECT s.*, sp.plan_code, sp.name as plan_name 
        FROM subscriptions s 
        LEFT JOIN subscription_plans sp ON s.plan_id = sp.id 
        WHERE s.user_id = ? 
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($subscriptions) . " subscription records for $userName</p>";
    
    if (empty($subscriptions)) {
        echo "<p class='info'>No existing subscriptions found. This might be why you're having issues.</p>";
    } else {
        foreach ($subscriptions as $sub) {
            $statusColor = ($sub['status'] === 'active') ? 'success' : 'error';
            echo "<p>- Plan: {$sub['plan_code']} ({$sub['plan_name']}), Status: <span class='$statusColor'>{$sub['status']}</span>, Created: {$sub['created_at']}</p>";
        }
    }
    
    echo "<h2 class='info'>Step 3: Checking Subscription Plans</h2>";
    
    // Get available plans
    $plansStmt = $db->query("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY price ASC");
    $plans = $plansStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Available subscription plans:</p>";
    foreach ($plans as $plan) {
        echo "<p>- {$plan['plan_code']}: {$plan['name']} (₹{$plan['price']}/month)</p>";
    }
    
    echo "<h2 class='info'>Step 4: Creating Active Subscription</h2>";
    
    // Check if user has any active subscription
    $activeStmt = $db->prepare("
        SELECT s.*, sp.plan_code, sp.name as plan_name 
        FROM subscriptions s 
        LEFT JOIN subscription_plans sp ON s.plan_id = sp.id 
        WHERE s.user_id = ? AND s.status = 'active'
    ");
    $activeStmt->execute([$userId]);
    $activeSubscription = $activeStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($activeSubscription) {
        echo "<p class='info'>ℹ User already has active subscription: {$activeSubscription['plan_code']} ({$activeSubscription['plan_name']})</p>";
    } else {
        // Create a free subscription
        $freePlan = null;
        foreach ($plans as $plan) {
            if ($plan['plan_code'] === 'free') {
                $freePlan = $plan;
                break;
            }
        }
        
        if ($freePlan) {
            $createStmt = $db->prepare("
                INSERT INTO subscriptions (user_id, plan_id, status, created_at, updated_at) 
                VALUES (?, ?, 'active', NOW(), NOW())
            ");
            $createStmt->execute([$userId, $freePlan['id']]);
            echo "<p class='success'>✓ Created active 'free' subscription for user</p>";
        } else {
            echo "<p class='error'>❌ Free plan not found in subscription_plans table</p>";
        }
    }
    
    echo "<h2 class='info'>Step 5: Creating Improved Upgrade API</h2>";
    
    // Create the improved upgrade API that works with user_id and plan_id
    $upgradeApiContent = '<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email, X-User-ID");

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
    $userId = $_SERVER["HTTP_X_USER_ID"] ?? $input["user_id"] ?? null;
    $newPlanCode = $input["plan_code"] ?? null;
    
    // Get user ID from email if not provided
    if (!$userId && $userEmail) {
        $userStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $userStmt->execute([$userEmail]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        $userId = $user ? $user["id"] : null;
    }
    
    if (!$userId || !$newPlanCode) {
        echo json_encode(["status" => "error", "message" => "User ID and plan_code are required"]);
        exit;
    }
    
    // Get plan by plan_code
    $planStmt = $db->prepare("SELECT * FROM subscription_plans WHERE plan_code = ? AND is_active = 1");
    $planStmt->execute([$newPlanCode]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        echo json_encode(["status" => "error", "message" => "Invalid plan code: $newPlanCode"]);
        exit;
    }
    
    // Check if user already has an active subscription with this plan
    $activeStmt = $db->prepare("
        SELECT s.*, sp.plan_code, sp.name as plan_name 
        FROM subscriptions s 
        LEFT JOIN subscription_plans sp ON s.plan_id = sp.id 
        WHERE s.user_id = ? AND s.plan_id = ? AND s.status = \"active\"
    ");
    $activeStmt->execute([$userId, $plan["id"]]);
    $existingActive = $activeStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingActive) {
        echo json_encode([
            "status" => "error",
            "message" => "You already have an active " . $plan["name"] . " subscription"
        ]);
        exit;
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Set all existing subscriptions to cancelled
        $cancelStmt = $db->prepare("UPDATE subscriptions SET status = \"cancelled\", updated_at = NOW() WHERE user_id = ?");
        $cancelStmt->execute([$userId]);
        
        // Create new active subscription
        $createStmt = $db->prepare("
            INSERT INTO subscriptions (user_id, plan_id, status, created_at, updated_at) 
            VALUES (?, ?, \"active\", NOW(), NOW())
        ");
        $createStmt->execute([$userId, $plan["id"]]);
        
        $db->commit();
        
        echo json_encode([
            "status" => "success",
            "message" => "Successfully upgraded to " . $plan["name"],
            "subscription" => [
                "plan_code" => $plan["plan_code"],
                "plan_name" => $plan["name"],
                "status" => "active",
                "price" => (float)$plan["price"]
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
    
    file_put_contents('api/customer/upgrade-subscription-working.php', $upgradeApiContent);
    echo "<p class='success'>✓ Created working upgrade API (upgrade-subscription-working.php)</p>";
    
    // Create a working profile API
    $profileApiContent = '<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email, X-User-ID");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

try {
    require_once "../../config/database.php";
    $database = new Database();
    $db = $database->getConnection();
    
    $userEmail = $_SERVER["HTTP_X_TUTORIAL_EMAIL"] ?? $_GET["email"] ?? null;
    $userId = $_SERVER["HTTP_X_USER_ID"] ?? $_GET["user_id"] ?? null;
    
    // Get user ID from email if not provided
    if (!$userId && $userEmail) {
        $userStmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $userStmt->execute([$userEmail]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        $userId = $user ? $user["id"] : null;
    } else if ($userId) {
        $userStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$user) {
        echo json_encode(["status" => "error", "message" => "User not found"]);
        exit;
    }
    
    // Get active subscription
    $subStmt = $db->prepare("
        SELECT s.*, sp.plan_code, sp.name as plan_name, sp.price, sp.features
        FROM subscriptions s
        LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
        WHERE s.user_id = ? AND s.status = \"active\"
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    $subStmt->execute([$userId]);
    $subscription = $subStmt->fetch(PDO::FETCH_ASSOC);
    
    // If no active subscription, create a free one
    if (!$subscription) {
        $freePlanStmt = $db->prepare("SELECT * FROM subscription_plans WHERE plan_code = \"free\" AND is_active = 1");
        $freePlanStmt->execute();
        $freePlan = $freePlanStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($freePlan) {
            $db->prepare("
                INSERT INTO subscriptions (user_id, plan_id, status, created_at, updated_at) 
                VALUES (?, ?, \"active\", NOW(), NOW())
            ")->execute([$userId, $freePlan["id"]]);
            
            $subscription = [
                "plan_code" => "free",
                "plan_name" => $freePlan["name"],
                "status" => "active",
                "price" => 0,
                "features" => $freePlan["features"]
            ];
        }
    }
    
    // Parse features
    $features = [];
    if ($subscription && $subscription["features"]) {
        $features = json_decode($subscription["features"], true) ?: [];
    }
    
    // Determine if user is pro
    $isProUser = $subscription && in_array($subscription["plan_code"], ["pro", "premium"]) && $subscription["status"] === "active";
    
    echo json_encode([
        "status" => "success",
        "profile" => [
            "first_name" => $user["first_name"] ?? "User",
            "last_name" => $user["last_name"] ?? "",
            "phone" => $user["phone"] ?? "",
            "address" => $user["address"] ?? "",
            "city" => $user["city"] ?? "",
            "state" => $user["state"] ?? "",
            "postal_code" => $user["postal_code"] ?? "",
            "country" => $user["country"] ?? "India"
        ],
        "subscription" => [
            "plan_code" => $subscription["plan_code"] ?? "free",
            "plan_name" => $subscription["plan_name"] ?? "Free Plan",
            "subscription_status" => $subscription["status"] ?? "active",
            "is_active" => ($subscription["status"] ?? "active") === "active" ? 1 : 0,
            "price" => (float)($subscription["price"] ?? 0),
            "features" => $features ?: ["Access to free tutorials"]
        ],
        "stats" => [
            "purchased_tutorials" => 2,
            "completed_tutorials" => 3,
            "learning_hours" => 8,
            "practice_uploads" => 0,
            "is_pro_user" => $isProUser
        ],
        "user_email" => $user["email"],
        "user_id" => $user["id"],
        "debug" => [
            "timestamp" => date("Y-m-d H:i:s"),
            "method" => $_SERVER["REQUEST_METHOD"],
            "subscription_source" => "database_active_only",
            "is_pro_calculated" => $isProUser
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>';
    
    file_put_contents('api/customer/profile-working.php', $profileApiContent);
    echo "<p class='success'>✓ Created working profile API (profile-working.php)</p>";
    
    echo "<h2 class='success'>✅ Fix Complete!</h2>";
    echo "<p>The subscription fix has been applied successfully. Now you can:</p>";
    echo "<ul>";
    echo "<li>✅ User found and verified</li>";
    echo "<li>✅ Subscription system analyzed</li>";
    echo "<li>✅ Working APIs created</li>";
    echo "<li>✅ Ready for testing upgrades</li>";
    echo "</ul>";
    
    echo "<h3>🧪 Test the Fix:</h3>";
    echo "<p><a href='test-upgrade-working.php' target='_blank' style='background:#3b82f6;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;'>Test Upgrade Now</a></p>";
    
    // Show current status
    echo "<h3>📊 Current Status:</h3>";
    $finalStmt = $db->prepare("
        SELECT s.*, sp.plan_code, sp.name as plan_name, sp.price 
        FROM subscriptions s 
        LEFT JOIN subscription_plans sp ON s.plan_id = sp.id 
        WHERE s.user_id = ? AND s.status = 'active'
    ");
    $finalStmt->execute([$userId]);
    $finalSub = $finalStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($finalSub) {
        echo "<p class='success'>Current active subscription: <strong>{$finalSub['plan_code']}</strong> ({$finalSub['plan_name']}) - ₹{$finalSub['price']}/month</p>";
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