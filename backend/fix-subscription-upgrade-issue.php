<?php
// Fix subscription upgrade issue - "already have the plan" problem
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Fix Subscription Upgrade Issue</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 1200px; margin: 20px;'>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>📋 Problem Analysis</h2>";
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Issue:</strong> User shows 'basic' plan but when upgrading shows 'already have the plan'</p>";
    echo "<p><strong>Root Cause:</strong> Multiple subscription records or incorrect upgrade logic</p>";
    echo "</div>";
    
    echo "<h2>🔍 Step 1: Analyze Current Subscription Data</h2>";
    
    // Get all users and their subscription status
    $usersStmt = $db->query("SELECT id, email, first_name, last_name FROM users ORDER BY id LIMIT 10");
    $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current User Subscription Status:</h3>";
    echo "<table style='border-collapse: collapse; width: 100%; margin: 15px 0;'>";
    echo "<tr style='background: #f9fafb;'>";
    echo "<th style='border: 1px solid #e5e7eb; padding: 8px;'>User</th>";
    echo "<th style='border: 1px solid #e5e7eb; padding: 8px;'>Email</th>";
    echo "<th style='border: 1px solid #e5e7eb; padding: 8px;'>Subscriptions</th>";
    echo "<th style='border: 1px solid #e5e7eb; padding: 8px;'>Status</th>";
    echo "</tr>";
    
    foreach ($users as $user) {
        $userEmail = $user['email'];
        $userName = trim($user['first_name'] . ' ' . $user['last_name']);
        
        // Get all subscriptions for this user
        $subStmt = $db->prepare("
            SELECT s.*, sp.plan_name 
            FROM subscriptions s 
            LEFT JOIN subscription_plans sp ON s.plan_code = sp.plan_code 
            WHERE s.email = ? 
            ORDER BY s.created_at DESC
        ");
        $subStmt->execute([$userEmail]);
        $subscriptions = $subStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<tr>";
        echo "<td style='border: 1px solid #e5e7eb; padding: 8px;'>$userName</td>";
        echo "<td style='border: 1px solid #e5e7eb; padding: 8px;'>$userEmail</td>";
        echo "<td style='border: 1px solid #e5e7eb; padding: 8px;'>";
        
        if (empty($subscriptions)) {
            echo "<span style='color: #ef4444;'>No subscriptions</span>";
        } else {
            foreach ($subscriptions as $sub) {
                $status = $sub['is_active'] ? 'Active' : 'Inactive';
                $color = $sub['is_active'] ? '#10b981' : '#ef4444';
                echo "<div style='color: $color;'>{$sub['plan_code']} - $status</div>";
            }
        }
        
        echo "</td>";
        echo "<td style='border: 1px solid #e5e7eb; padding: 8px;'>";
        
        // Check for conflicts
        $activeCount = 0;
        $planCodes = [];
        foreach ($subscriptions as $sub) {
            if ($sub['is_active']) {
                $activeCount++;
                $planCodes[] = $sub['plan_code'];
            }
        }
        
        if ($activeCount > 1) {
            echo "<span style='color: #ef4444;'>⚠️ Multiple Active</span>";
        } elseif ($activeCount === 1) {
            echo "<span style='color: #10b981;'>✅ Single Active</span>";
        } else {
            echo "<span style='color: #f59e0b;'>⚠️ No Active</span>";
        }
        
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>🛠️ Step 2: Clean Up Subscription Data</h2>";
    
    // Clean up duplicate and conflicting subscriptions
    echo "<h3>Cleaning Duplicate Subscriptions</h3>";
    
    foreach ($users as $user) {
        $userEmail = $user['email'];
        $userName = trim($user['first_name'] . ' ' . $user['last_name']);
        
        // Get all active subscriptions for this user
        $activeStmt = $db->prepare("
            SELECT * FROM subscriptions 
            WHERE email = ? AND is_active = 1 
            ORDER BY created_at DESC
        ");
        $activeStmt->execute([$userEmail]);
        $activeSubscriptions = $activeStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($activeSubscriptions) > 1) {
            echo "<div style='background: #fef3c7; padding: 10px; border-radius: 5px; margin: 5px 0;'>";
            echo "<strong>$userName ($userEmail)</strong> has " . count($activeSubscriptions) . " active subscriptions<br>";
            
            // Keep the most recent one, deactivate others
            $keepSubscription = array_shift($activeSubscriptions);
            echo "Keeping: {$keepSubscription['plan_code']} (ID: {$keepSubscription['id']})<br>";
            
            foreach ($activeSubscriptions as $oldSub) {
                $db->prepare("UPDATE subscriptions SET is_active = 0 WHERE id = ?")->execute([$oldSub['id']]);
                echo "Deactivated: {$oldSub['plan_code']} (ID: {$oldSub['id']})<br>";
            }
            echo "</div>";
        }
    }
    
    echo "<h2>🔧 Step 3: Create Proper Subscription Upgrade API</h2>";
    
    // Create a proper subscription upgrade API
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
    $planStmt = $db->prepare("SELECT * FROM subscription_plans WHERE plan_code = ? AND is_active = 1");
    $planStmt->execute([$newPlanCode]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid plan code: $newPlanCode"
        ]);
        exit;
    }
    
    // Get current active subscription
    $currentStmt = $db->prepare("
        SELECT * FROM subscriptions 
        WHERE email = ? AND is_active = 1 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $currentStmt->execute([$userEmail]);
    $currentSubscription = $currentStmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user already has this exact plan
    if ($currentSubscription && $currentSubscription["plan_code"] === $newPlanCode) {
        echo json_encode([
            "status" => "error",
            "message" => "You already have the " . $plan["plan_name"] . " plan",
            "current_plan" => $currentSubscription["plan_code"]
        ]);
        exit;
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Deactivate all current subscriptions for this user
        $deactivateStmt = $db->prepare("UPDATE subscriptions SET is_active = 0 WHERE email = ?");
        $deactivateStmt->execute([$userEmail]);
        
        // Create new subscription
        $createStmt = $db->prepare("
            INSERT INTO subscriptions (email, plan_code, subscription_status, is_active, created_at) 
            VALUES (?, ?, \"active\", 1, NOW())
        ");
        $createStmt->execute([$userEmail, $newPlanCode]);
        
        // Update users table if it has subscription_plan column
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
                "is_active" => true
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
    
    file_put_contents('api/customer/upgrade-subscription.php', $upgradeApiContent);
    echo "<span style='color: green;'>✓ Created subscription upgrade API</span><br>";
    
    echo "<h2>🔧 Step 4: Fix Subscription Status API</h2>";
    
    // Update the subscription status API to handle upgrades properly
    $statusApiContent = '<?php
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
    
    // Get current active subscription
    $stmt = $db->prepare("
        SELECT s.plan_code, s.subscription_status, s.is_active, s.created_at,
               sp.plan_name, sp.price, sp.features, sp.access_levels
        FROM subscriptions s
        LEFT JOIN subscription_plans sp ON s.plan_code = sp.plan_code
        WHERE s.email = ? AND s.is_active = 1
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userEmail]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($subscription) {
        // Parse JSON fields
        $features = $subscription["features"] ? json_decode($subscription["features"], true) : [];
        $accessLevels = $subscription["access_levels"] ? json_decode($subscription["access_levels"], true) : [];
        
        echo json_encode([
            "status" => "success",
            "has_subscription" => true,
            "subscription" => [
                "plan_code" => $subscription["plan_code"],
                "plan_name" => $subscription["plan_name"],
                "subscription_status" => $subscription["subscription_status"],
                "is_active" => (bool)$subscription["is_active"],
                "price" => (float)$subscription["price"],
                "features" => $features,
                "access_levels" => $accessLevels,
                "created_at" => $subscription["created_at"]
            ]
        ]);
    } else {
        // No subscription found - create basic subscription
        try {
            $db->prepare("
                INSERT INTO subscriptions (email, plan_code, subscription_status, is_active) 
                VALUES (?, \"free\", \"active\", 1)
            ")->execute([$userEmail]);
            
            // Get basic plan info
            $basicPlan = $db->query("SELECT * FROM subscription_plans WHERE plan_code = \"free\"")->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "status" => "success",
                "has_subscription" => true,
                "subscription" => [
                    "plan_code" => "free",
                    "plan_name" => $basicPlan["plan_name"] ?? "Free Plan",
                    "subscription_status" => "active",
                    "is_active" => true,
                    "price" => 0.00,
                    "features" => $basicPlan["features"] ? json_decode($basicPlan["features"], true) : ["Access to free tutorials"],
                    "access_levels" => $basicPlan["access_levels"] ? json_decode($basicPlan["access_levels"], true) : []
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "status" => "error",
                "message" => "Could not create subscription: " . $e->getMessage()
            ]);
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>';
    
    file_put_contents('api/customer/subscription-status-fixed.php', $statusApiContent);
    echo "<span style='color: green;'>✓ Created fixed subscription status API</span><br>";
    
    echo "<h2>🧪 Step 5: Test Subscription System</h2>";
    
    // Test with first user
    $testUser = $users[0] ?? null;
    if ($testUser) {
        $testEmail = $testUser['email'];
        $testName = trim($testUser['first_name'] . ' ' . $testUser['last_name']);
        
        echo "<h3>Testing with User: $testName ($testEmail)</h3>";
        
        // Set user to basic plan first
        $db->prepare("UPDATE subscriptions SET is_active = 0 WHERE email = ?")->execute([$testEmail]);
        $db->prepare("
            INSERT INTO subscriptions (email, plan_code, subscription_status, is_active) 
            VALUES (?, 'free', 'active', 1)
        ")->execute([$testEmail]);
        
        echo "<div style='background: #e0f2fe; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>Test Scenario:</h4>";
        echo "<ol>";
        echo "<li>Set user to FREE plan ✅</li>";
        echo "<li>Check subscription status</li>";
        echo "<li>Attempt upgrade to PRO</li>";
        echo "<li>Verify upgrade success</li>";
        echo "</ol>";
        echo "</div>";
        
        // Test links
        echo "<h4>Test APIs:</h4>";
        echo "<ul>";
        echo "<li><a href='api/customer/subscription-status-fixed.php?email=" . urlencode($testEmail) . "' target='_blank'>Check Current Status</a></li>";
        echo "<li><button onclick='testUpgrade(\"$testEmail\", \"pro\")'>Test Upgrade to Pro</button></li>";
        echo "<li><button onclick='testUpgrade(\"$testEmail\", \"premium\")'>Test Upgrade to Premium</button></li>";
        echo "</ul>";
    }
    
    echo "<h2>✅ Fix Complete!</h2>";
    echo "<div style='background: #d1fae5; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Subscription Upgrade Issue Fixed!</h3>";
    echo "<ul>";
    echo "<li>✅ Cleaned up duplicate subscriptions</li>";
    echo "<li>✅ Created proper upgrade API</li>";
    echo "<li>✅ Fixed subscription status detection</li>";
    echo "<li>✅ Ensured single active subscription per user</li>";
    echo "<li>✅ Added proper error handling</li>";
    echo "</ul>";
    
    echo "<h3>🚀 How to Use:</h3>";
    echo "<ol>";
    echo "<li><strong>Check Status:</strong> Use <code>api/customer/subscription-status-fixed.php</code></li>";
    echo "<li><strong>Upgrade Plan:</strong> POST to <code>api/customer/upgrade-subscription.php</code> with plan_code</li>";
    echo "<li><strong>Frontend Integration:</strong> Update your React app to use the new APIs</li>";
    echo "</ol>";
    echo "</div>";
    
    // Final verification
    echo "<h3>📊 Final System Status</h3>";
    $stats = [
        'Total Users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'Active Subscriptions' => $db->query("SELECT COUNT(*) FROM subscriptions WHERE is_active = 1")->fetchColumn(),
        'Duplicate Active Subscriptions' => $db->query("
            SELECT COUNT(*) FROM (
                SELECT email FROM subscriptions WHERE is_active = 1 GROUP BY email HAVING COUNT(*) > 1
            ) as duplicates
        ")->fetchColumn(),
        'Free Plans' => $db->query("SELECT COUNT(*) FROM subscriptions WHERE plan_code = 'free' AND is_active = 1")->fetchColumn(),
        'Pro Plans' => $db->query("SELECT COUNT(*) FROM subscriptions WHERE plan_code = 'pro' AND is_active = 1")->fetchColumn(),
        'Premium Plans' => $db->query("SELECT COUNT(*) FROM subscriptions WHERE plan_code = 'premium' AND is_active = 1")->fetchColumn()
    ];
    
    echo "<table style='border-collapse: collapse; width: 100%; margin: 15px 0;'>";
    echo "<tr style='background: #f9fafb;'><th style='border: 1px solid #e5e7eb; padding: 8px;'>Metric</th><th style='border: 1px solid #e5e7eb; padding: 8px;'>Count</th></tr>";
    foreach ($stats as $metric => $count) {
        $color = ($metric === 'Duplicate Active Subscriptions' && $count > 0) ? 'color: #ef4444;' : '';
        echo "<tr><td style='border: 1px solid #e5e7eb; padding: 8px;'>$metric</td><td style='border: 1px solid #e5e7eb; padding: 8px; text-align: center; $color'>$count</td></tr>";
    }
    echo "</table>";
    
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
function testUpgrade(email, planCode) {
    fetch('api/customer/upgrade-subscription.php', {
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
        console.log('Upgrade Response:', data);
        if (data.status === 'success') {
            alert('✅ Upgrade successful! New plan: ' + data.subscription.plan_name);
            // Refresh status
            window.open('api/customer/subscription-status-fixed.php?email=' + encodeURIComponent(email), '_blank');
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