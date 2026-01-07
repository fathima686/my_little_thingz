<?php
// Simple debug test for custom requests API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Custom Requests API Debug</h2>";

// Test 1: Basic PHP syntax
echo "<h3>Test 1: PHP Syntax Check</h3>";
echo "<span style='color: green;'>✓ PHP is working</span><br>";

// Test 2: Database connection
echo "<h3>Test 2: Database Connection</h3>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    echo "<span style='color: green;'>✓ Database connected</span><br>";
} catch (Exception $e) {
    echo "<span style='color: red;'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</span><br>";
    exit;
}

// Test 3: Check if custom_requests table exists
echo "<h3>Test 3: Table Check</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'custom_requests'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "<span style='color: green;'>✓ custom_requests table exists</span><br>";
        
        // Count records
        $count = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
        echo "<span style='color: blue;'>→ Found $count records</span><br>";
    } else {
        echo "<span style='color: orange;'>⚠ custom_requests table does not exist</span><br>";
        
        // Create the table
        $pdo->exec("CREATE TABLE IF NOT EXISTS custom_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id VARCHAR(50) UNIQUE NOT NULL,
            customer_id INT UNSIGNED NOT NULL,
            customer_name VARCHAR(255) NOT NULL,
            customer_email VARCHAR(255) NOT NULL,
            title VARCHAR(255) NOT NULL,
            occasion VARCHAR(100),
            description TEXT,
            requirements TEXT,
            deadline DATE,
            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
            status ENUM('submitted', 'drafted_by_admin', 'changes_requested', 'approved_by_customer', 'locked_for_production') DEFAULT 'submitted',
            design_url VARCHAR(500),
            admin_notes TEXT,
            customer_feedback TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        echo "<span style='color: green;'>✓ Table created successfully</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color: red;'>✗ Table error: " . htmlspecialchars($e->getMessage()) . "</span><br>";
}

// Test 4: Test the actual query that might be failing
echo "<h3>Test 4: Query Test</h3>";
try {
    $stmt = $pdo->prepare("
        SELECT 
            cr.*,
            DATEDIFF(cr.deadline, CURDATE()) as days_until_deadline
        FROM custom_requests cr
        ORDER BY 
            CASE cr.priority 
                WHEN 'high' THEN 1 
                WHEN 'medium' THEN 2 
                WHEN 'low' THEN 3 
            END,
            cr.deadline ASC,
            cr.created_at DESC
        LIMIT 50 OFFSET 0
    ");
    
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<span style='color: green;'>✓ Main query successful</span><br>";
    echo "<span style='color: blue;'>→ Found " . count($requests) . " requests</span><br>";
    
    // Test statistics query
    $statsStmt = $pdo->query("
        SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN status IN ('submitted', 'changes_requested') THEN 1 ELSE 0 END) as pending_requests,
            SUM(CASE WHEN status IN ('approved_by_customer', 'locked_for_production') THEN 1 ELSE 0 END) as completed_requests,
            SUM(CASE WHEN priority = 'high' AND DATEDIFF(deadline, CURDATE()) <= 3 THEN 1 ELSE 0 END) as urgent_requests
        FROM custom_requests
    ");
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<span style='color: green;'>✓ Statistics query successful</span><br>";
    echo "<span style='color: blue;'>→ Stats: " . json_encode($stats) . "</span><br>";
    
} catch (Exception $e) {
    echo "<span style='color: red;'>✗ Query error: " . htmlspecialchars($e->getMessage()) . "</span><br>";
}

// Test 5: Create sample data if none exists
echo "<h3>Test 5: Sample Data</h3>";
try {
    $count = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
    
    if ($count == 0) {
        echo "<span style='color: orange;'>⚠ No sample data found, creating...</span><br>";
        
        $stmt = $pdo->prepare("
            INSERT INTO custom_requests (
                order_id, customer_id, customer_name, customer_email, 
                title, occasion, description, requirements, deadline, priority, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $sampleData = [
            'CR-' . date('Ymd') . '-001',
            1,
            'John Doe',
            'john@example.com',
            'Custom Wedding Invitation',
            'Wedding',
            'Need elegant wedding invitations',
            'Size: 5x7 inches, Quantity: 100',
            date('Y-m-d', strtotime('+14 days')),
            'high',
            'submitted'
        ];
        
        $stmt->execute($sampleData);
        echo "<span style='color: green;'>✓ Sample data created</span><br>";
    } else {
        echo "<span style='color: green;'>✓ Found $count existing records</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color: red;'>✗ Sample data error: " . htmlspecialchars($e->getMessage()) . "</span><br>";
}

echo "<h3>✅ Debug Complete</h3>";
echo "<p>If all tests pass, the API should work. Try accessing the API directly:</p>";
echo "<p><a href='api/admin/custom-requests-simple.php'>Test API</a></p>";
?>