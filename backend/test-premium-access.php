<?php
// Test script to verify premium subscription access
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Premium Subscription Access Test</h2>\n";
    
    // Check if subscription tables exist
    echo "<h3>1. Checking subscription tables...</h3>\n";
    
    try {
        $result = $db->query("SHOW TABLES LIKE 'subscription_plans'");
        if ($result->rowCount() > 0) {
            echo "✓ subscription_plans table exists<br>\n";
        } else {
            echo "✗ subscription_plans table missing<br>\n";
        }
        
        $result = $db->query("SHOW TABLES LIKE 'subscriptions'");
        if ($result->rowCount() > 0) {
            echo "✓ subscriptions table exists<br>\n";
        } else {
            echo "✗ subscriptions table missing<br>\n";
        }
    } catch (Exception $e) {
        echo "Error checking tables: " . $e->getMessage() . "<br>\n";
    }
    
    // Check subscription plans
    echo "<h3>2. Checking subscription plans...</h3>\n";
    try {
        $stmt = $db->query("SELECT * FROM subscription_plans ORDER BY plan_code");
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($plans)) {
            echo "No subscription plans found<br>\n";
        } else {
            foreach ($plans as $plan) {
                echo "Plan: {$plan['plan_code']} - {$plan['name']} - ₹{$plan['price']}<br>\n";
            }
        }
    } catch (Exception $e) {
        echo "Error fetching plans: " . $e->getMessage() . "<br>\n";
    }
    
    // Check active subscriptions
    echo "<h3>3. Checking active subscriptions...</h3>\n";
    try {
        $stmt = $db->query("
            SELECT s.*, sp.plan_code, sp.name as plan_name, u.email
            FROM subscriptions s
            JOIN subscription_plans sp ON s.plan_id = sp.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE s.status IN ('active', 'authenticated')
            ORDER BY s.created_at DESC
        ");
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($subscriptions)) {
            echo "No active subscriptions found<br>\n";
        } else {
            foreach ($subscriptions as $sub) {
                echo "User: {$sub['email']} - Plan: {$sub['plan_code']} - Status: {$sub['status']} - Start: {$sub['current_start']} - End: {$sub['current_end']}<br>\n";
            }
        }
    } catch (Exception $e) {
        echo "Error fetching subscriptions: " . $e->getMessage() . "<br>\n";
    }
    
    // Test access check for a sample user
    echo "<h3>4. Testing access check...</h3>\n";
    try {
        // Get a user with active subscription
        $stmt = $db->query("
            SELECT u.id, u.email, s.status, sp.plan_code
            FROM users u
            JOIN subscriptions s ON u.id = s.user_id
            JOIN subscription_plans sp ON s.plan_id = sp.id
            WHERE s.status IN ('active', 'authenticated')
            AND sp.plan_code IN ('premium', 'pro')
            LIMIT 1
        ");
        $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($testUser) {
            echo "Testing access for user: {$testUser['email']} (Plan: {$testUser['plan_code']})<br>\n";
            
            // Simulate the access check logic
            $accessStmt = $db->prepare("
                SELECT s.*, sp.plan_code 
                FROM subscriptions s
                JOIN subscription_plans sp ON s.plan_id = sp.id
                WHERE s.user_id = ? 
                AND s.status IN ('active', 'authenticated')
                AND (s.current_end IS NULL OR s.current_end > NOW())
                AND sp.plan_code IN ('premium', 'pro')
                ORDER BY s.created_at DESC
                LIMIT 1
            ");
            $accessStmt->execute([$testUser['id']]);
            $access = $accessStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($access) {
                echo "✓ User has premium access (Plan: {$access['plan_code']})<br>\n";
            } else {
                echo "✗ User does not have premium access<br>\n";
            }
        } else {
            echo "No users with active premium subscriptions found for testing<br>\n";
        }
    } catch (Exception $e) {
        echo "Error testing access: " . $e->getMessage() . "<br>\n";
    }
    
} catch (Exception $e) {
    echo "Database connection error: " . $e->getMessage() . "\n";
}
?>