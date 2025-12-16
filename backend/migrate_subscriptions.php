<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "Creating subscription_plans table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS subscription_plans (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        plan_code VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10, 2) NOT NULL,
        currency VARCHAR(10) DEFAULT 'INR',
        billing_period ENUM('monthly', 'yearly') DEFAULT 'monthly',
        razorpay_plan_id VARCHAR(100),
        features JSON,
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_plan_code (plan_code),
        INDEX idx_active (is_active)
    )");
    echo "✓ Subscription plans table created\n";

    echo "Creating subscriptions table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS subscriptions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        plan_id INT UNSIGNED NOT NULL,
        razorpay_subscription_id VARCHAR(100) UNIQUE,
        razorpay_plan_id VARCHAR(100),
        status ENUM('created', 'authenticated', 'active', 'pending', 'halted', 'cancelled', 'completed', 'expired') DEFAULT 'created',
        current_start TIMESTAMP NULL,
        current_end TIMESTAMP NULL,
        quantity INT DEFAULT 1,
        total_count INT DEFAULT NULL COMMENT 'Total billing cycles, NULL for infinite',
        paid_count INT DEFAULT 0,
        remaining_count INT DEFAULT NULL,
        notes JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE RESTRICT,
        INDEX idx_user (user_id),
        INDEX idx_status (status),
        INDEX idx_razorpay_subscription_id (razorpay_subscription_id)
    )");
    echo "✓ Subscriptions table created\n";

    echo "Creating subscription_invoices table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS subscription_invoices (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        subscription_id INT UNSIGNED NOT NULL,
        razorpay_invoice_id VARCHAR(100) UNIQUE,
        razorpay_payment_id VARCHAR(100),
        invoice_number VARCHAR(50),
        amount DECIMAL(10, 2) NOT NULL,
        currency VARCHAR(10) DEFAULT 'INR',
        status ENUM('issued', 'paid', 'partially_paid', 'cancelled', 'expired') DEFAULT 'issued',
        invoice_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        due_date TIMESTAMP NULL,
        paid_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE,
        INDEX idx_subscription (subscription_id),
        INDEX idx_status (status),
        INDEX idx_razorpay_invoice_id (razorpay_invoice_id)
    )");
    echo "✓ Subscription invoices table created\n";

    echo "\nInserting subscription plans...\n";
    
    // Check if plans already exist
    $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM subscription_plans");
    $checkStmt->execute();
    $result = $checkStmt->fetch();
    
    if ($result['count'] == 0) {
        $plans = [
            [
                'plan_code' => 'free',
                'name' => 'Free',
                'description' => 'Limited access to free tutorials',
                'price' => 0.00,
                'billing_period' => 'monthly',
                'features' => json_encode([
                    'Limited free tutorials',
                    'Basic video quality',
                    'Community support'
                ])
            ],
            [
                'plan_code' => 'premium',
                'name' => 'Premium',
                'description' => 'Unlimited access to all tutorials',
                'price' => 499.00,
                'billing_period' => 'monthly',
                'features' => json_encode([
                    'Unlimited tutorial access',
                    'HD video quality',
                    'New content weekly',
                    'Priority support',
                    'Download videos'
                ])
            ],
            [
                'plan_code' => 'pro',
                'name' => 'Pro',
                'description' => 'Everything in Premium plus mentorship',
                'price' => 999.00,
                'billing_period' => 'monthly',
                'features' => json_encode([
                    'Everything in Premium',
                    '1-on-1 mentorship',
                    'Live workshops',
                    'Certificate of completion',
                    'Early access to new content'
                ])
            ]
        ];

        $stmt = $db->prepare("
            INSERT INTO subscription_plans 
            (plan_code, name, description, price, billing_period, features, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");

        foreach ($plans as $plan) {
            $stmt->execute([
                $plan['plan_code'],
                $plan['name'],
                $plan['description'],
                $plan['price'],
                $plan['billing_period'],
                $plan['features']
            ]);
            echo "✓ Created plan: " . $plan['name'] . "\n";
        }
    } else {
        echo "Subscription plans already exist, skipping sample data insertion\n";
    }

    echo "\n✓ Subscription migration completed successfully!\n";

} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}


