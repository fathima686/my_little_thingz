<?php
// Update Subscription Pricing - Fast Fix
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Update Subscription Pricing</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} .container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:8px;} .success{color:#10b981;} .error{color:#ef4444;} .info{color:#3b82f6;}</style>";
echo "</head><body><div class='container'>";

echo "<h1>💰 Update Subscription Pricing</h1>";
echo "<p>Updating pricing to: Basic ₹199, Premium ₹499, Pro ₹999</p>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2 class='info'>Step 1: Check Current Pricing</h2>";
    
    $currentPlans = $pdo->query("SELECT * FROM subscription_plans ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($currentPlans)) {
        echo "<p class='info'>No plans found. Creating new plans...</p>";
        
        // Create subscription_plans table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS subscription_plans (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            plan_code VARCHAR(50) UNIQUE NOT NULL,
            plan_name VARCHAR(100) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'INR',
            duration_months INT DEFAULT 1,
            billing_period VARCHAR(20) DEFAULT 'monthly',
            features JSON,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        echo "<p class='success'>✓ Created subscription_plans table</p>";
    } else {
        echo "<p>Current plans:</p>";
        echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
        echo "<tr><th>Plan Code</th><th>Name</th><th>Current Price</th></tr>";
        foreach ($currentPlans as $plan) {
            echo "<tr>";
            echo "<td>{$plan['plan_code']}</td>";
            echo "<td>" . ($plan['plan_name'] ?? $plan['name']) . "</td>";
            echo "<td>₹{$plan['price']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2 class='info'>Step 2: Update Pricing</h2>";
    
    // New pricing structure
    $newPricing = [
        'basic' => ['name' => 'Basic Plan', 'price' => 199.00],
        'premium' => ['name' => 'Premium Plan', 'price' => 499.00],
        'pro' => ['name' => 'Pro Plan', 'price' => 999.00]
    ];
    
    foreach ($newPricing as $planCode => $planData) {
        // Check if plan exists
        $existsStmt = $pdo->prepare("SELECT id FROM subscription_plans WHERE plan_code = ?");
        $existsStmt->execute([$planCode]);
        
        if ($existsStmt->fetch()) {
            // Update existing plan
            $updateStmt = $pdo->prepare("
                UPDATE subscription_plans 
                SET price = ?, plan_name = ?, name = ?, updated_at = NOW() 
                WHERE plan_code = ?
            ");
            $updateStmt->execute([$planData['price'], $planData['name'], $planData['name'], $planCode]);
            echo "<p class='success'>✓ Updated {$planCode}: ₹{$planData['price']}</p>";
        } else {
            // Create new plan
            $features = [];
            if ($planCode === 'basic') {
                $features = ["Access to free tutorials", "Individual tutorial purchases", "Standard video quality", "Community support", "Mobile access"];
            } elseif ($planCode === 'premium') {
                $features = ["Access to ALL tutorials", "HD video quality", "Download videos", "Priority support", "Weekly new content"];
            } elseif ($planCode === 'pro') {
                $features = ["All Premium features", "Live workshops", "Practice uploads", "Certificates", "1-on-1 mentorship", "Early access"];
            }
            
            $insertStmt = $pdo->prepare("
                INSERT INTO subscription_plans 
                (plan_code, plan_name, name, description, price, currency, duration_months, billing_period, features, is_active) 
                VALUES (?, ?, ?, ?, ?, 'INR', 1, 'monthly', ?, 1)
            ");
            
            $description = $planData['name'] . " - " . implode(", ", $features);
            
            $insertStmt->execute([
                $planCode, 
                $planData['name'], 
                $planData['name'], 
                $description, 
                $planData['price'], 
                json_encode($features)
            ]);
            echo "<p class='success'>✓ Created {$planCode}: ₹{$planData['price']}</p>";
        }
    }
    
    echo "<h2 class='info'>Step 3: Update Frontend Pricing Display</h2>";
    
    // Check if there are any hardcoded prices in frontend files
    $frontendFiles = [
        '../frontend/src/pages/TutorialsDashboard.jsx',
        '../frontend/src/components/SubscriptionPlans.jsx',
        '../frontend/src/pages/Subscription.jsx'
    ];
    
    foreach ($frontendFiles as $file) {
        if (file_exists($file)) {
            echo "<p class='info'>Found frontend file: " . basename($file) . "</p>";
        }
    }
    
    echo "<h2 class='info'>Step 4: Verify Updated Pricing</h2>";
    
    $updatedPlans = $pdo->query("SELECT * FROM subscription_plans ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse:collapse;width:100%;margin:15px 0;'>";
    echo "<tr style='background:#f8f9fa;'><th>Plan Code</th><th>Name</th><th>New Price</th><th>Features</th></tr>";
    
    foreach ($updatedPlans as $plan) {
        $features = json_decode($plan['features'] ?? '[]', true);
        $featuresList = is_array($features) ? implode(', ', array_slice($features, 0, 3)) . '...' : 'N/A';
        
        echo "<tr>";
        echo "<td><strong>{$plan['plan_code']}</strong></td>";
        echo "<td>" . ($plan['plan_name'] ?? $plan['name']) . "</td>";
        echo "<td><strong>₹{$plan['price']}</strong></td>";
        echo "<td>{$featuresList}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2 class='success'>✅ Pricing Update Complete!</h2>";
    echo "<div style='background:#d1fae5;padding:20px;border-radius:5px;margin:20px 0;'>";
    echo "<h3>🎉 New Pricing Structure:</h3>";
    echo "<ul>";
    echo "<li><strong>Basic Plan:</strong> ₹199/month</li>";
    echo "<li><strong>Premium Plan:</strong> ₹499/month</li>";
    echo "<li><strong>Pro Plan:</strong> ₹999/month</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>📋 Next Steps:</h3>";
    echo "<ul>";
    echo "<li>✅ Database pricing updated</li>";
    echo "<li>🔄 Frontend may need manual updates for hardcoded prices</li>";
    echo "<li>🧪 Test subscription flow with new pricing</li>";
    echo "<li>💳 Update payment gateway configurations if needed</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
?>