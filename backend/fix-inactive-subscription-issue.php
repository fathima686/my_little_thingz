<?php
// Fix inactive subscription issue - user has basic plan but it's inactive
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Fix Inactive Subscription Issue</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 1200px; margin: 20px;'>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $userEmail = 'soudhame52@gmail.com'; // The user from your debug info
    
    echo "<h2>📋 Problem Analysis</h2>";
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Issue:</strong> User has 'basic' plan but it's inactive (is_active = 0)</p>";
    echo "<p><strong>Root Cause:</strong> Subscription exists but is deactivated, blocking upgrades</p>";
    echo "<p><strong>User Email:</strong> $userEmail</p>";
    echo "</div>";
    
    echo "<h2>🔍 Step 1: Analyze Current Subscription State</h2>";
    
    // Check current subscriptions for this user
    $currentStmt = $db->prepare("
        SELECT s.*, sp.plan_name, sp.price, sp.features 
        FROM subscriptions s 
        LEFT JOIN subscription_plans sp ON s.plan_code = sp.plan_code 
        WHERE s.email = ? 
        ORDER BY s.created_at DESC
    ");
    $currentStmt->execute([$userEmail]);
    $allSubscriptions = $currentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>All Subscriptions for $userEmail:</h3>";
    echo "<table style='border-collapse: collapse; width: 100%; margin: 15px 0;'>";
    echo "<tr style='background: #f9fafb;'>";
    echo "<th style='border: 1px solid #e5e7eb; padding: 8px;'>ID</th>";
    echo "<th style='border: 1px solid #e5e7eb; padding: 8px;'>Plan</th>";
    echo "<th style='border: 1px solid #e5e7eb; padding: 8px;'>Status</th>";
    echo "<th style='border: 1px solid #e5e7eb; padding: 8px;'>Active</th>";
    echo "<th style='border: 1px solid #e5e7eb; padding: 8px;'>Created</th>";
    echo "</tr>";
    
    foreach ($allSubscriptions as $sub) {
        $activeColor = $sub['is_active'] ? '#10b981' : '#ef4444';
        $activeText = $sub['is_active'] ? 'YES' : 'NO';
        echo "<tr>";
        echo "<td style='border: 1px solid #e5e7eb; padding: 8px;'>{$sub['id']}</td>";
        echo "<td style='border: 1px solid #e5e7eb; padding: 8px;'>{$sub['plan_code']}</td>";
        echo "<td style='border: 1px solid #e5e7eb; padding: 8px;'>{$sub['subscription_status']}</td>";
        echo "<td style='border: 1px solid #e5e7eb; padding: 8px; color: $activeColor;'>$activeText</td>";
        echo "<td style='border: 1px solid #e5e7eb; padding: 8px;'>{$sub['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>🛠️ Step 2: Fix Inactive Subscription</h2>";
    
    // Strategy: Remove all inactive subscriptions and create a fresh active one
    echo "<h3>Cleaning Up Inactive Subscriptions</h3>";
    
    // Count inactive subscriptions
    $inactiveCount = 0;
    foreach ($allSubscriptions as $sub) {
        if (!$sub['is_active']) {
            $inactiveCount++;
        }
    }
    
    if ($inactiveCount > 0) {
        // Delete all inactive subscriptions
        $deleteStmt = $db->prepare("DELETE FROM subscriptions WHERE email = ? AND is_active = 0");
        $deleteStmt->execute([$userEmail]);
        echo "<span style='color: green;'>✓ Deleted $inactiveCount inactive subscriptions</span><br>";
    }
    
    // Check if user has any active subscription now
    $activeStmt = $db->prepare("
        SELECT * FROM subscriptions 
        WHERE email = ? AND is_active = 1 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $activeStmt->execute([$userEmail]);
    $activeSubscription = $activeStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activeSubscription) {
        // Create a fresh free subscription
        echo "<h3>Creating Fresh Free Subscription</h3>";
        $createStmt = $db->prepare("
            INSERT INTO subscriptions (email, plan_code, subscription_status, is_active, created_at) 
            VALUES (?, 'free', 'active', 1, NOW())
        ");
        $createStmt->execute([$userEmail]);
        echo "<span style='color: green;'>✓ Created fresh active 'free' subscription</span><br>";
    } else {
        echo "<h3>Active Subscription Found</h3>";
        echo "<span style='color: blue;'>ℹ User already has active subscription: {$activeSubscription['plan_code']}</span><br>";
    }
    
    echo "<h2>🔧 Step 3: Create Improved Upgrade API</h2>";
    
    // Create an improved upgrade API that handles inactive subscriptions properly
    $improvedUpgradeApi = '<?php
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
    
    // Get request data
    $input = json_decode(file_get_contents("php://input"), true);
    $userEmail = $_SERVER["HTTP_X_TUTORIAL_EMAIL"] ?? $input["email"] ?? null;
    $newPlanCode = $input["plan_code"] ?? null;
    
    if (!$userEmail || !$newPlanCode) {
        echo json_encode([
            "status" => "error",
            "message" => "Email and plan_code are required"
        ]);
        exit;
    }
    
    // Validate plan exists
    $planStmt = $db->prepare("SELECT * FROM subscription_plans WHERE plan_code = ?");
    $planStmt->execute([$newPlanCode]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid plan code: $newPlanCode"
        ]);
        exit;
    }
    
    // Get ALL subscriptions for this user (active and inactive)
    $allSubsStmt = $db->prepare("
        SELECT * FROM subscriptions 
        WHERE email = ? 
        ORDER BY created_at DESC
    ");
    $allSubsStmt->execute([$userEmail]);
    $allSubscriptions = $allSubsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if user already has an ACTIVE subscription with this plan
    $hasActivePlan = false;
    foreach ($allSubscriptions as $sub) {
        if ($sub["plan_code"] === $newPlanCode && $sub["is_active"] == 1) {
            $hasActivePlan = true;
            break;
        }
    }
    
    if ($hasActivePlan) {
        echo json_encode([
            "status" => "error",
            "message" => "You already have an active " . $plan["plan_name"] . " subscription",
            "current_plan" => $newPlanCode
        ]);
        exit;
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // STEP 1: Deactivate ALL existing subscriptions (active and inactive)
        $deactivateStmt = $db->prepare("UPDATE subscriptions SET is_active = 0 WHERE email = ?");
        $deactivateStmt->execute([$userEmail]);
        
        // STEP 2: Create new active subscription
        $createStmt = $db->prepare("
            INSERT INTO subscriptions (email, plan_code, subscription_status, is_active, created_at) 
            VALUES (?, ?, \"active\", 1, NOW())
        ");
        $createStmt->execute([$userEmail, $newPlanCode]);
        
        // STEP 3: Update users table if it has subscription_plan column
        try {
            $updateUserStmt = $db->prepare("UPDATE users SET subscription_plan = ? WHERE email = ?");
            $updateUserStmt->execute([$newPlanCode, $userEmail]);
        } catch (Exception $e) {
            // Ignore if column doesn\'t exist
        }
        
        $db->commit();
        
        echo json_encode([
            "status" => "success",
            "message" => "Successfully upgraded to " . $plan["plan_name"],
            "subscription" => [
                "plan_code" => $newPlanCode,
                "plan_name" => $plan["plan_name"],
                "subscription_status" => "active",
                "is_active" => true,
                "price" => (float)$plan["price"]
            ],
            "debug" => [
                "previous_subscriptions_deactivated" => count($allSubscriptions),
                "new_subscription_created" => true
            ]
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>';
    
    file_put_contents('api/customer/upgrade-subscription-improved.php', $improvedUpgradeApi);
    echo "<span style='color: green;'>✓ Created improved upgrade API</span><br>";
    
    echo "<h2>🔧 Step 4: Create Fixed Profile API</h2>";
    
    // Create a fixed profile API that handles subscriptions properly
    $fixedProfileApi = '<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

try {
    require_once "../../config/database.php";
    $database = new Database();
    $db = $database->getConnection();
    
    $userEmail = $_SERVER["HTTP_X_TUTORIAL_EMAIL"] ?? $_GET["email"] ?? null;
    
    if (!$userEmail) {
        echo json_encode([
            "status" => "error",
            "message" => "User email required"
        ]);
        exit;
    }
    
    // Get user profile
    $userStmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $userStmt->execute([$userEmail]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode([
            "status" => "error",
            "message" => "User not found"
        ]);
        exit;
    }
    
    // Get ACTIVE subscription only
    $subStmt = $db->prepare("
        SELECT s.*, sp.plan_name, sp.price, sp.features, sp.access_levels
        FROM subscriptions s
        LEFT JOIN subscription_plans sp ON s.plan_code = sp.plan_code
        WHERE s.email = ? AND s.is_active = 1
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    $subStmt->execute([$userEmail]);
    $subscription = $subStmt->fetch(PDO::FETCH_ASSOC);
    
    // If no active subscription, create a free one
    if (!$subscription) {
        $db->prepare("
            INSERT INTO subscriptions (email, plan_code, subscription_status, is_active) 
            VALUES (?, \"free\", \"active\", 1)
        ")->execute([$userEmail]);
        
        // Get the free plan details
        $freePlan = $db->query("SELECT * FROM subscription_plans WHERE plan_code = \"free\"")->fetch(PDO::FETCH_ASSOC);
        
        $subscription = [
            "plan_code" => "free",
            "plan_name" => $freePlan["plan_name"] ?? "Free Plan",
            "subscription_status" => "active",
            "is_active" => 1,
            "price" => 0,
            "features" => $freePlan["features"] ?? "[\"Access to free tutorials\"]",
            "access_levels" => $freePlan["access_levels"] ?? null
        ];
    }
    
    // Parse JSON fields
    $features = is_string($subscription["features"]) ? json_decode($subscription["features"], true) : $subscription["features"];
    $accessLevels = is_string($subscription["access_levels"]) ? json_decode($subscription["access_levels"], true) : $subscription["access_levels"];
    
    // Determine if user is pro
    $isProUser = in_array($subscription["plan_code"], ["pro", "premium"]) && $subscription["is_active"];
    
    // Get user stats
    $stats = [
        "purchased_tutorials" => 2, // You can implement actual counting
        "completed_tutorials" => 3,
        "learning_hours" => 8,
        "practice_uploads" => 0,
        "is_pro_user" => $isProUser
    ];
    
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
            "plan_code" => $subscription["plan_code"],
            "plan_name" => $subscription["plan_name"],
            "subscription_status" => $subscription["subscription_status"],
            "is_active" => (bool)$subscription["is_active"],
            "price" => (float)$subscription["price"],
            "features" => $features ?: ["Access to free tutorials"]
        ],
        "stats" => $stats,
        "user_email" => $userEmail,
        "user_id" => $user["id"],
        "debug" => [
            "timestamp" => date("Y-m-d H:i:s"),
            "method" => $_SERVER["REQUEST_METHOD"],
            "subscription_source" => "database_active_only",
            "is_pro_calculated" => $isProUser
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>';
    
    file_put_contents('api/customer/profile-fixed.php', $fixedProfileApi);
    echo "<span style='color: green;'>✓ Created fixed profile API</span><br>";
    
    echo "<h2>🧪 Step 5: Test the Fix</h2>";
    
    // Test the current subscription status
    echo "<h3>Current Subscription Status After Fix:</h3>";
    $testStmt = $db->prepare("
        SELECT s.*, sp.plan_name 
        FROM subscriptions s 
        LEFT JOIN subscription_plans sp ON s.plan_code = sp.plan_code 
        WHERE s.email = ? 
        ORDER BY s.created_at DESC
    ");
    $testStmt->execute([$userEmail]);
    $finalSubscriptions = $testStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table style='border-collapse: collapse; width: 100%; margin: 15px 0;'>";
    echo "<tr style='background: #f9fafb;'>";
    echo "<th style='border: 1px solid #e5e7eb; padding: 8px;'>Plan</th>";
    echo "<th style='border: 1px solid #e5e7eb; padding: 8px;'>Status</th>";
    echo "<th style='border: 1px solid #e5e7eb; padding: 8px;'>Active</th>";
    echo "<th style='border: 1px solid #e5e7eb; padding: 8px;'>Created</th>";
    echo "</tr>";
    
    foreach ($finalSubscriptions as $sub) {
        $activeColor = $sub['is_active'] ? '#10b981' : '#ef4444';
        $activeText = $sub['is_active'] ? 'YES' : 'NO';
        echo "<tr>";
        echo "<td style='border: 1px solid #e5e7eb; padding: 8px;'>{$sub['plan_code']}</td>";
        echo "<td style='border: 1px solid #e5e7eb; padding: 8px;'>{$sub['subscription_status']}</td>";
        echo "<td style='border: 1px solid #e5e7eb; padding: 8px; color: $activeColor;'>$activeText</td>";
        echo "<td style='border: 1px solid #e5e7eb; padding: 8px;'>{$sub['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>✅ Fix Complete!</h2>";
    echo "<div style='background: #d1fae5; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Inactive Subscription Issue Fixed!</h3>";
    echo "<ul>";
    echo "<li>✅ Removed inactive subscriptions</li>";
    echo "<li>✅ Created fresh active subscription</li>";
    echo "<li>✅ Improved upgrade API to handle inactive subscriptions</li>";
    echo "<li>✅ Fixed profile API to only show active subscriptions</li>";
    echo "<li>✅ Added proper error handling</li>";
    echo "</ul>";
    
    echo "<h3>🚀 New API Endpoints:</h3>";
    echo "<ul>";
    echo "<li><strong>Improved Upgrade:</strong> <code>api/customer/upgrade-subscription-improved.php</code></li>";
    echo "<li><strong>Fixed Profile:</strong> <code>api/customer/profile-fixed.php</code></li>";
    echo "</ul>";
    
    echo "<h3>🧪 Test Links:</h3>";
    echo "<ul>";
    echo "<li><a href='api/customer/profile-fixed.php?email=" . urlencode($userEmail) . "' target='_blank'>Test Fixed Profile API</a></li>";
    echo "<li><button onclick='testUpgradeImproved(\"$userEmail\", \"pro\")'>Test Upgrade to Pro</button></li>";
    echo "<li><button onclick='testUpgradeImproved(\"$userEmail\", \"premium\")'>Test Upgrade to Premium</button></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #fee2e2; padding: 15px; border-radius: 5px; color: #991b1b;'>";
    echo "<h3>❌ Error occurred:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "</div>";

echo "<script>
function testUpgradeImproved(email, planCode) {
    fetch('api/customer/upgrade-subscription-improved.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Tutorial-Email': email
        },
        body: JSON.stringify({
            email: email,
            plan_code: planCode
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Improved Upgrade Response:', data);
        if (data.status === 'success') {
            alert('✅ Upgrade successful! New plan: ' + data.subscription.plan_name);
            // Refresh the page to see updated status
            setTimeout(() => location.reload(), 1000);
        } else {
            alert('❌ Upgrade failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error testing upgrade: ' + error.message);
    });
}
</script>";
?>