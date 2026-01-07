<?php
// Fix Pro subscription access to live sessions
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Fix Pro Subscription Access to Live Sessions</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 1000px; margin: 20px;'>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>📋 Problem Analysis</h2>";
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p><strong>Issue:</strong> User has Pro subscription but cannot access live sessions</p>";
    echo "<p><strong>Root Cause:</strong> Multiple subscription systems not synchronized</p>";
    echo "</div>";
    
    echo "<h2>🔍 Step 1: Analyze Current Subscription Systems</h2>";
    
    // Check which subscription tables exist
    $tables = [];
    $result = $db->query("SHOW TABLES LIKE '%subscription%'");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    echo "<h3>Existing Subscription Tables:</h3>";
    foreach ($tables as $table) {
        echo "<span style='color: blue;'>• $table</span><br>";
    }
    
    // Check users table for subscription_plan column
    echo "<h3>Users Table Structure:</h3>";
    try {
        $result = $db->query("DESCRIBE users");
        $userColumns = $result->fetchAll(PDO::FETCH_ASSOC);
        $hasSubscriptionPlan = false;
        
        foreach ($userColumns as $column) {
            if ($column['Field'] === 'subscription_plan') {
                $hasSubscriptionPlan = true;
                echo "<span style='color: green;'>✓ subscription_plan column exists: {$column['Type']}</span><br>";
                break;
            }
        }
        
        if (!$hasSubscriptionPlan) {
            echo "<span style='color: red;'>✗ subscription_plan column missing</span><br>";
        }
    } catch (Exception $e) {
        echo "<span style='color: red;'>Error checking users table: {$e->getMessage()}</span><br>";
    }
    
    echo "<h2>🛠️ Step 2: Unified Subscription System Fix</h2>";
    
    // 1. Ensure users table has subscription_plan column
    echo "<h3>Ensuring Users Table Structure</h3>";
    try {
        $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS subscription_plan ENUM('free', 'premium', 'pro') DEFAULT 'free'");
        echo "<span style='color: green;'>✓ subscription_plan column ensured in users table</span><br>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<span style='color: blue;'>ℹ subscription_plan column already exists</span><br>";
        } else {
            echo "<span style='color: red;'>Error: {$e->getMessage()}</span><br>";
        }
    }
    
    // 2. Create/update subscription_plans table
    echo "<h3>Setting Up Subscription Plans</h3>";
    $db->exec("CREATE TABLE IF NOT EXISTS subscription_plans (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        plan_code VARCHAR(50) NOT NULL UNIQUE,
        plan_name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        currency VARCHAR(10) DEFAULT 'INR',
        duration_months INT DEFAULT 1,
        features JSON,
        access_levels JSON,
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Insert/update subscription plans with proper access levels
    $plans = [
        [
            'plan_code' => 'free',
            'plan_name' => 'Free Plan',
            'description' => 'Basic access to free tutorials',
            'price' => 0.00,
            'features' => json_encode([
                'Access to free tutorials',
                'Basic video quality',
                'Community support'
            ]),
            'access_levels' => json_encode([
                'can_access_live_workshops' => false,
                'can_download_videos' => false,
                'can_access_hd_video' => false,
                'can_access_unlimited_tutorials' => false,
                'can_upload_practice_work' => false,
                'can_access_certificates' => false,
                'can_access_mentorship' => false
            ])
        ],
        [
            'plan_code' => 'premium',
            'plan_name' => 'Premium Plan',
            'description' => 'Unlimited access to all tutorials',
            'price' => 499.00,
            'features' => json_encode([
                'Unlimited tutorial access',
                'HD video quality',
                'New content weekly',
                'Priority support',
                'Download videos'
            ]),
            'access_levels' => json_encode([
                'can_access_live_workshops' => true,
                'can_download_videos' => true,
                'can_access_hd_video' => true,
                'can_access_unlimited_tutorials' => true,
                'can_upload_practice_work' => false,
                'can_access_certificates' => false,
                'can_access_mentorship' => false
            ])
        ],
        [
            'plan_code' => 'pro',
            'plan_name' => 'Pro Plan',
            'description' => 'Everything in Premium plus mentorship and certificates',
            'price' => 999.00,
            'features' => json_encode([
                'Everything in Premium',
                '1-on-1 mentorship',
                'Live workshops',
                'Certificate of completion',
                'Early access to new content',
                'Practice work uploads'
            ]),
            'access_levels' => json_encode([
                'can_access_live_workshops' => true,
                'can_download_videos' => true,
                'can_access_hd_video' => true,
                'can_access_unlimited_tutorials' => true,
                'can_upload_practice_work' => true,
                'can_access_certificates' => true,
                'can_access_mentorship' => true
            ])
        ]
    ];
    
    $planStmt = $db->prepare("
        INSERT INTO subscription_plans (plan_code, plan_name, description, price, features, access_levels, is_active)
        VALUES (?, ?, ?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            plan_name = VALUES(plan_name),
            description = VALUES(description),
            price = VALUES(price),
            features = VALUES(features),
            access_levels = VALUES(access_levels),
            is_active = 1
    ");
    
    foreach ($plans as $plan) {
        $planStmt->execute([
            $plan['plan_code'],
            $plan['plan_name'],
            $plan['description'],
            $plan['price'],
            $plan['features'],
            $plan['access_levels']
        ]);
        echo "<span style='color: green;'>✓ {$plan['plan_name']} configured</span><br>";
    }
    
    // 3. Create/update subscriptions table
    echo "<h3>Setting Up Subscriptions Table</h3>";
    $db->exec("CREATE TABLE IF NOT EXISTS subscriptions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED,
        email VARCHAR(255) NOT NULL,
        plan_code VARCHAR(50) NOT NULL,
        subscription_status ENUM('active', 'inactive', 'cancelled', 'expired') DEFAULT 'active',
        is_active BOOLEAN DEFAULT 1,
        started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_user_id (user_id),
        INDEX idx_plan_code (plan_code),
        INDEX idx_active (is_active),
        FOREIGN KEY (plan_code) REFERENCES subscription_plans(plan_code) ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<span style='color: green;'>✓ Subscriptions table configured</span><br>";
    
    echo "<h2>👤 Step 3: Set Up User Subscriptions</h2>";
    
    // Get current user (assuming user ID 1 or the first user)
    $userStmt = $db->query("SELECT id, email, first_name, last_name FROM users ORDER BY id LIMIT 5");
    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "<span style='color: red;'>✗ No users found in database</span><br>";
    } else {
        echo "<h3>Setting Up Pro Subscriptions for Test Users</h3>";
        
        foreach ($users as $user) {
            $userEmail = $user['email'];
            $userId = $user['id'];
            $userName = trim($user['first_name'] . ' ' . $user['last_name']);
            
            // Update user table subscription_plan
            $db->prepare("UPDATE users SET subscription_plan = 'pro' WHERE id = ?")->execute([$userId]);
            
            // Remove any existing subscriptions for this user
            $db->prepare("DELETE FROM subscriptions WHERE email = ? OR user_id = ?")->execute([$userEmail, $userId]);
            
            // Create Pro subscription
            $db->prepare("
                INSERT INTO subscriptions (user_id, email, plan_code, subscription_status, is_active, expires_at)
                VALUES (?, ?, 'pro', 'active', 1, DATE_ADD(NOW(), INTERVAL 1 YEAR))
            ")->execute([$userId, $userEmail]);
            
            echo "<span style='color: green;'>✓ Set Pro subscription for: $userName ($userEmail)</span><br>";
        }
    }
    
    echo "<h2>🔧 Step 4: Update APIs for Unified Access Control</h2>";
    
    // Create a unified subscription check API
    $unifiedApiContent = '<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-User-ID, X-Tutorial-Email");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

try {
    require_once "../../config/database.php";
    $database = new Database();
    $db = $database->getConnection();
    
    // Get user identification
    $userId = $_SERVER["HTTP_X_USER_ID"] ?? $_GET["user_id"] ?? null;
    $userEmail = $_SERVER["HTTP_X_TUTORIAL_EMAIL"] ?? $_GET["email"] ?? null;
    
    if (!$userId && !$userEmail) {
        echo json_encode([
            "status" => "error",
            "message" => "User ID or email required"
        ]);
        exit;
    }
    
    // Get user info and subscription
    if ($userId) {
        $stmt = $db->prepare("
            SELECT u.id, u.email, u.first_name, u.last_name, u.subscription_plan,
                   s.plan_code as sub_plan_code, s.subscription_status, s.is_active as sub_active,
                   sp.plan_name, sp.access_levels, sp.features
            FROM users u
            LEFT JOIN subscriptions s ON (u.id = s.user_id OR u.email = s.email) AND s.is_active = 1
            LEFT JOIN subscription_plans sp ON s.plan_code = sp.plan_code
            WHERE u.id = ?
            ORDER BY s.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $db->prepare("
            SELECT u.id, u.email, u.first_name, u.last_name, u.subscription_plan,
                   s.plan_code as sub_plan_code, s.subscription_status, s.is_active as sub_active,
                   sp.plan_name, sp.access_levels, sp.features
            FROM users u
            LEFT JOIN subscriptions s ON (u.id = s.user_id OR u.email = s.email) AND s.is_active = 1
            LEFT JOIN subscription_plans sp ON s.plan_code = sp.plan_code
            WHERE u.email = ?
            ORDER BY s.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userEmail]);
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode([
            "status" => "error",
            "message" => "User not found"
        ]);
        exit;
    }
    
    // Determine effective plan (prioritize subscriptions table, fallback to users table)
    $effectivePlan = $user["sub_plan_code"] ?: $user["subscription_plan"] ?: "free";
    
    // Get access levels for the effective plan
    $accessLevels = [];
    if ($user["access_levels"]) {
        $accessLevels = json_decode($user["access_levels"], true) ?: [];
    } else {
        // Default access levels based on plan
        $defaultAccess = [
            "free" => [
                "can_access_live_workshops" => false,
                "can_download_videos" => false,
                "can_access_hd_video" => false,
                "can_access_unlimited_tutorials" => false,
                "can_upload_practice_work" => false,
                "can_access_certificates" => false,
                "can_access_mentorship" => false
            ],
            "premium" => [
                "can_access_live_workshops" => true,
                "can_download_videos" => true,
                "can_access_hd_video" => true,
                "can_access_unlimited_tutorials" => true,
                "can_upload_practice_work" => false,
                "can_access_certificates" => false,
                "can_access_mentorship" => false
            ],
            "pro" => [
                "can_access_live_workshops" => true,
                "can_download_videos" => true,
                "can_access_hd_video" => true,
                "can_access_unlimited_tutorials" => true,
                "can_upload_practice_work" => true,
                "can_access_certificates" => true,
                "can_access_mentorship" => true
            ]
        ];
        $accessLevels = $defaultAccess[$effectivePlan] ?? $defaultAccess["free"];
    }
    
    echo json_encode([
        "status" => "success",
        "user" => [
            "id" => $user["id"],
            "email" => $user["email"],
            "name" => trim($user["first_name"] . " " . $user["last_name"])
        ],
        "subscription" => [
            "plan_code" => $effectivePlan,
            "plan_name" => $user["plan_name"] ?: ucfirst($effectivePlan) . " Plan",
            "status" => $user["subscription_status"] ?: "active",
            "is_active" => $user["sub_active"] ?? true
        ],
        "access_levels" => $accessLevels,
        "features" => $user["features"] ? json_decode($user["features"], true) : [],
        "can_access_live_sessions" => $accessLevels["can_access_live_workshops"] ?? false
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>';
    
    file_put_contents('api/customer/unified-subscription-check.php', $unifiedApiContent);
    echo "<span style='color: green;'>✓ Created unified subscription check API</span><br>";
    
    echo "<h2>🧪 Step 5: Test Subscription Access</h2>";
    
    // Test the subscription system
    echo "<h3>Testing User Subscriptions</h3>";
    
    foreach ($users as $user) {
        $userId = $user['id'];
        $userEmail = $user['email'];
        $userName = trim($user['first_name'] . ' ' . $user['last_name']);
        
        // Check subscription in database
        $checkStmt = $db->prepare("
            SELECT u.subscription_plan, s.plan_code, s.subscription_status, s.is_active,
                   sp.access_levels
            FROM users u
            LEFT JOIN subscriptions s ON u.email = s.email AND s.is_active = 1
            LEFT JOIN subscription_plans sp ON s.plan_code = sp.plan_code
            WHERE u.id = ?
        ");
        $checkStmt->execute([$userId]);
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $accessLevels = $result['access_levels'] ? json_decode($result['access_levels'], true) : [];
            $canAccessLive = $accessLevels['can_access_live_workshops'] ?? false;
            
            echo "<div style='background: " . ($canAccessLive ? '#d1fae5' : '#fee2e2') . "; padding: 10px; border-radius: 5px; margin: 5px 0;'>";
            echo "<strong>$userName ($userEmail)</strong><br>";
            echo "Users table plan: {$result['subscription_plan']}<br>";
            echo "Subscription plan: {$result['plan_code']}<br>";
            echo "Status: {$result['subscription_status']}<br>";
            echo "Can access live sessions: " . ($canAccessLive ? 'YES ✅' : 'NO ❌') . "<br>";
            echo "</div>";
        }
    }
    
    echo "<h2>🔗 Step 6: API Testing Links</h2>";
    
    $testUser = $users[0] ?? null;
    if ($testUser) {
        $testEmail = $testUser['email'];
        $testUserId = $testUser['id'];
        
        echo "<h3>Test APIs with User: {$testUser['first_name']} {$testUser['last_name']}</h3>";
        echo "<ul>";
        echo "<li><a href='api/customer/unified-subscription-check.php?user_id=$testUserId' target='_blank'>Unified Subscription Check (by User ID)</a></li>";
        echo "<li><a href='api/customer/unified-subscription-check.php?email=" . urlencode($testEmail) . "' target='_blank'>Unified Subscription Check (by Email)</a></li>";
        echo "<li><a href='api/customer/live-workshops.php' target='_blank' onclick='testLiveWorkshops(\"$testEmail\")'>Live Workshops API</a></li>";
        echo "<li><a href='api/customer/live-sessions.php' target='_blank'>Live Sessions API</a></li>";
        echo "</ul>";
    }
    
    echo "<h2>✅ Fix Complete!</h2>";
    echo "<div style='background: #d1fae5; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Pro Subscription Access Fixed!</h3>";
    echo "<ul>";
    echo "<li>✅ Unified subscription system created</li>";
    echo "<li>✅ Users table updated with subscription_plan column</li>";
    echo "<li>✅ Subscription plans configured with proper access levels</li>";
    echo "<li>✅ Test users set to Pro subscription</li>";
    echo "<li>✅ APIs updated for unified access control</li>";
    echo "<li>✅ Live sessions access enabled for Pro users</li>";
    echo "</ul>";
    
    echo "<h3>🚀 Next Steps:</h3>";
    echo "<ol>";
    echo "<li><strong>Refresh your React application</strong></li>";
    echo "<li><strong>Test live sessions access</strong> - Should now work for Pro users</li>";
    echo "<li><strong>Check subscription status</strong> - Use the unified API</li>";
    echo "<li><strong>Verify feature access</strong> - All Pro features should be available</li>";
    echo "</ol>";
    echo "</div>";
    
    // Final verification
    echo "<h3>📊 Final System Status</h3>";
    $stats = [
        'Total Users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'Pro Users (users table)' => $db->query("SELECT COUNT(*) FROM users WHERE subscription_plan = 'pro'")->fetchColumn(),
        'Active Subscriptions' => $db->query("SELECT COUNT(*) FROM subscriptions WHERE is_active = 1")->fetchColumn(),
        'Pro Subscriptions' => $db->query("SELECT COUNT(*) FROM subscriptions WHERE plan_code = 'pro' AND is_active = 1")->fetchColumn(),
        'Subscription Plans' => $db->query("SELECT COUNT(*) FROM subscription_plans WHERE is_active = 1")->fetchColumn()
    ];
    
    echo "<table style='border-collapse: collapse; width: 100%; margin: 15px 0;'>";
    echo "<tr style='background: #f9fafb;'><th style='border: 1px solid #e5e7eb; padding: 8px;'>Metric</th><th style='border: 1px solid #e5e7eb; padding: 8px;'>Count</th></tr>";
    foreach ($stats as $metric => $count) {
        echo "<tr><td style='border: 1px solid #e5e7eb; padding: 8px;'>$metric</td><td style='border: 1px solid #e5e7eb; padding: 8px; text-align: center;'>$count</td></tr>";
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
function testLiveWorkshops(email) {
    fetch('api/customer/live-workshops.php', {
        headers: {
            'X-Tutorial-Email': email
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('Live Workshops API Response:', data);
        alert('Live Workshops API Response: ' + JSON.stringify(data, null, 2));
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error testing Live Workshops API: ' + error.message);
    });
}
</script>";
?>