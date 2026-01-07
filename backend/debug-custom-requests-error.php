<?php
// Debug the 500 error in custom-requests-simple.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debugging Custom Requests API Error</h2>";

// Test 1: Check if we can include the database
echo "<h3>Test 1: Database Connection</h3>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    echo "<span style='color: green;'>✓ Database connection successful</span><br>";
} catch (Exception $e) {
    echo "<span style='color: red;'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</span><br>";
    exit;
}

// Test 2: Check if table can be created
echo "<h3>Test 2: Table Creation</h3>";
try {
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
    echo "<span style='color: green;'>✓ Table creation successful</span><br>";
} catch (Exception $e) {
    echo "<span style='color: red;'>✗ Table creation error: " . htmlspecialchars($e->getMessage()) . "</span><br>";
}

// Test 3: Test the actual API call
echo "<h3>Test 3: API Function Test</h3>";
try {
    // Simulate the GET request logic
    $status = 'pending';
    $priority = '';
    $search = '';
    $limit = 50;
    $offset = 0;
    
    $whereConditions = [];
    $params = [];
    
    if (!empty($status)) {
        $whereConditions[] = "cr.status = ?";
        $params[] = $status;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $stmt = $pdo->prepare("
        SELECT 
            cr.*,
            DATEDIFF(cr.deadline, CURDATE()) as days_until_deadline
        FROM custom_requests cr
        $whereClause
        ORDER BY 
            CASE cr.priority 
                WHEN 'high' THEN 1 
                WHEN 'medium' THEN 2 
                WHEN 'low' THEN 3 
            END,
            cr.deadline ASC,
            cr.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<span style='color: green;'>✓ Query executed successfully</span><br>";
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

// Test 4: Direct API call
echo "<h3>Test 4: Direct API Call</h3>";
$apiUrl = "http://localhost/my_little_thingz/backend/api/admin/custom-requests-simple.php?status=pending";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$response = @file_get_contents($apiUrl, false, $context);
if ($response !== false) {
    echo "<p><strong>API Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    // Check if it's JSON
    $data = json_decode($response, true);
    if ($data) {
        echo "<p><strong>Parsed JSON:</strong></p>";
        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
    }
} else {
    echo "<span style='color: red;'>✗ Failed to call API directly</span><br>";
}

echo "<h3>Summary</h3>";
echo "<p>If all tests pass but the API still returns 500, the issue might be in the API file itself.</p>";
?>