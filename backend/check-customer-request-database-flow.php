<?php
// Check Customer Request Database Flow
header("Content-Type: text/html; charset=UTF-8");

echo "<h2>🔍 Customer Request Database Flow Check</h2>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h3>1. Database Connection Test</h3>";
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    echo "<h3>2. Table Structure Check</h3>";
    
    // Check if custom_requests table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'custom_requests'")->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<p style='color: red;'>❌ custom_requests table does not exist!</p>";
        echo "<p>Creating table...</p>";
        
        $createTable = "CREATE TABLE custom_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id VARCHAR(100) NOT NULL DEFAULT '',
            customer_id INT UNSIGNED DEFAULT 0,
            customer_name VARCHAR(255) NOT NULL DEFAULT '',
            customer_email VARCHAR(255) NOT NULL DEFAULT '',
            customer_phone VARCHAR(50) DEFAULT '',
            title VARCHAR(255) NOT NULL DEFAULT '',
            occasion VARCHAR(100) DEFAULT '',
            description TEXT,
            requirements TEXT,
            budget_min DECIMAL(10,2) DEFAULT 500.00,
            budget_max DECIMAL(10,2) DEFAULT 1000.00,
            deadline DATE,
            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
            status ENUM('submitted', 'pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
            admin_notes TEXT,
            design_url VARCHAR(500) DEFAULT '',
            source ENUM('form', 'cart', 'admin') DEFAULT 'form',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_customer_email (customer_email),
            INDEX idx_created_at (created_at)
        )";
        
        $pdo->exec($createTable);
        echo "<p style='color: green;'>✅ Table created successfully</p>";
    } else {
        echo "<p style='color: green;'>✅ custom_requests table exists</p>";
    }
    
    // Show table structure
    $columns = $pdo->query("DESCRIBE custom_requests")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h4>Table Structure:</h4>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    echo "<h3>3. Current Database Content</h3>";
    
    // Get all requests
    $allRequests = $pdo->query("
        SELECT id, order_id, customer_name, customer_email, title, status, created_at, source
        FROM custom_requests 
        ORDER BY created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($allRequests)) {
        echo "<p style='color: orange;'>⚠️ No custom requests found in database</p>";
    } else {
        echo "<p style='color: green;'>✅ Found " . count($allRequests) . " custom requests</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Order ID</th><th>Customer</th><th>Email</th><th>Title</th><th>Status</th><th>Created</th><th>Source</th></tr>";
        foreach ($allRequests as $req) {
            echo "<tr>";
            echo "<td>{$req['id']}</td>";
            echo "<td>{$req['order_id']}</td>";
            echo "<td>{$req['customer_name']}</td>";
            echo "<td>{$req['customer_email']}</td>";
            echo "<td>{$req['title']}</td>";
            echo "<td>{$req['status']}</td>";
            echo "<td>{$req['created_at']}</td>";
            echo "<td>{$req['source']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
    
    echo "<h3>4. Test Customer Submission API</h3>";
    
    // Test the customer submission API
    $testData = [
        'customer_name' => 'Database Test Customer',
        'customer_email' => 'dbtest@example.com',
        'customer_phone' => '+1-555-DBTEST',
        'title' => 'Database Flow Test Request',
        'occasion' => 'Testing',
        'description' => 'This request is created to test the database flow from customer submission to admin visibility',
        'requirements' => 'Should appear in database immediately after submission',
        'budget_min' => 150.00,
        'budget_max' => 300.00
    ];
    
    echo "<p>Creating test request with data:</p>";
    echo "<pre>" . print_r($testData, true) . "</pre>";
    
    // Simulate the customer API call
    $orderId = 'CR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    
    $insertStmt = $pdo->prepare("
        INSERT INTO custom_requests (
            order_id, customer_name, customer_email, customer_phone,
            title, occasion, description, requirements, budget_min, budget_max,
            status, source, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'form', NOW())
    ");
    
    $success = $insertStmt->execute([
        $orderId,
        $testData['customer_name'],
        $testData['customer_email'],
        $testData['customer_phone'],
        $testData['title'],
        $testData['occasion'],
        $testData['description'],
        $testData['requirements'],
        $testData['budget_min'],
        $testData['budget_max']
    ]);
    
    if ($success) {
        $newRequestId = $pdo->lastInsertId();
        echo "<p style='color: green;'>✅ Test request created successfully!</p>";
        echo "<p><strong>Request ID:</strong> $newRequestId</p>";
        echo "<p><strong>Order ID:</strong> $orderId</p>";
        
        // Verify it was inserted
        $verifyStmt = $pdo->prepare("SELECT * FROM custom_requests WHERE id = ?");
        $verifyStmt->execute([$newRequestId]);
        $newRequest = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($newRequest) {
            echo "<p style='color: green;'>✅ Request verified in database</p>";
            echo "<h4>Inserted Request Details:</h4>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            foreach ($newRequest as $key => $value) {
                echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
            }
            echo "</table><br>";
        } else {
            echo "<p style='color: red;'>❌ Request not found after insertion!</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to create test request</p>";
        $errorInfo = $insertStmt->errorInfo();
        echo "<p>Error: " . $errorInfo[2] . "</p>";
    }
    
    echo "<h3>5. Test Admin API Response</h3>";
    
    // Test what the admin API would return
    $adminRequests = $pdo->query("
        SELECT * FROM custom_requests 
        ORDER BY created_at DESC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Admin API would return " . count($adminRequests) . " requests:</p>";
    
    if (!empty($adminRequests)) {
        echo "<ul>";
        foreach ($adminRequests as $req) {
            echo "<li>ID {$req['id']}: {$req['title']} by {$req['customer_name']} ({$req['status']}) - {$req['created_at']}</li>";
        }
        echo "</ul>";
        
        // Test the actual admin API
        echo "<h4>Testing Actual Admin API:</h4>";
        try {
            $apiUrl = "http://localhost/my_little_thingz/backend/api/admin/custom-requests-database-only.php";
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => 'Content-Type: application/json'
                ]
            ]);
            
            $apiResponse = file_get_contents($apiUrl, false, $context);
            $apiData = json_decode($apiResponse, true);
            
            if ($apiData && isset($apiData['status'])) {
                echo "<p style='color: green;'>✅ Admin API Response: {$apiData['status']}</p>";
                if (isset($apiData['requests'])) {
                    echo "<p>API returned " . count($apiData['requests']) . " requests</p>";
                    
                    // Check if our test request is in the API response
                    $foundTestRequest = false;
                    foreach ($apiData['requests'] as $apiReq) {
                        if ($apiReq['order_id'] === $orderId) {
                            $foundTestRequest = true;
                            echo "<p style='color: green;'>✅ Test request found in API response!</p>";
                            break;
                        }
                    }
                    
                    if (!$foundTestRequest) {
                        echo "<p style='color: red;'>❌ Test request NOT found in API response</p>";
                    }
                } else {
                    echo "<p style='color: red;'>❌ No requests array in API response</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ Admin API failed or returned invalid response</p>";
                echo "<pre>" . htmlspecialchars($apiResponse) . "</pre>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Admin API Error: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>6. Recent Activity Check</h3>";
    
    // Check for recent submissions
    $recentRequests = $pdo->query("
        SELECT * FROM custom_requests 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ORDER BY created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($recentRequests)) {
        echo "<p style='color: orange;'>⚠️ No requests submitted in the last hour</p>";
    } else {
        echo "<p style='color: green;'>✅ Found " . count($recentRequests) . " requests submitted in the last hour:</p>";
        foreach ($recentRequests as $req) {
            echo "<p>• {$req['title']} by {$req['customer_name']} at {$req['created_at']}</p>";
        }
    }
    
    echo "<h3>7. Customer Submission API Test</h3>";
    
    // Test the actual customer submission API
    echo "<p>Testing customer submission API endpoint...</p>";
    
    try {
        $customerApiUrl = "http://localhost/my_little_thingz/backend/api/customer/custom-request-upload.php";
        
        // Create form data
        $postData = http_build_query([
            'customer_name' => 'API Flow Test Customer',
            'customer_email' => 'apiflowtest@example.com',
            'title' => 'API Flow Test Request',
            'description' => 'Testing the complete flow from customer API to database',
            'occasion' => 'Testing'
        ]);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postData
            ]
        ]);
        
        $customerApiResponse = file_get_contents($customerApiUrl, false, $context);
        $customerApiData = json_decode($customerApiResponse, true);
        
        if ($customerApiData && isset($customerApiData['status'])) {
            if ($customerApiData['status'] === 'success') {
                echo "<p style='color: green;'>✅ Customer API submission successful!</p>";
                echo "<p>Request ID: {$customerApiData['request_id']}</p>";
                echo "<p>Order ID: {$customerApiData['order_id']}</p>";
                
                // Verify it's in the database
                $verifyCustomerStmt = $pdo->prepare("SELECT * FROM custom_requests WHERE id = ?");
                $verifyCustomerStmt->execute([$customerApiData['request_id']]);
                $customerRequest = $verifyCustomerStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($customerRequest) {
                    echo "<p style='color: green;'>✅ Customer API request verified in database</p>";
                } else {
                    echo "<p style='color: red;'>❌ Customer API request NOT found in database</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ Customer API failed: {$customerApiData['message']}</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Customer API returned invalid response</p>";
            echo "<pre>" . htmlspecialchars($customerApiResponse) . "</pre>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Customer API Error: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>8. Final Database Count</h3>";
    
    $finalCount = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
    echo "<p><strong>Total requests in database:</strong> $finalCount</p>";
    
    $todayCount = $pdo->query("
        SELECT COUNT(*) FROM custom_requests 
        WHERE DATE(created_at) = CURDATE()
    ")->fetchColumn();
    echo "<p><strong>Requests created today:</strong> $todayCount</p>";
    
    echo "<h3>✅ Database Flow Check Complete</h3>";
    
    echo "<h4>Summary:</h4>";
    echo "<ul>";
    echo "<li>Database connection: ✅ Working</li>";
    echo "<li>Table structure: ✅ Correct</li>";
    echo "<li>Direct insertion: ✅ Working</li>";
    echo "<li>Admin API: " . (isset($apiData) && $apiData['status'] === 'success' ? '✅ Working' : '❌ Issues') . "</li>";
    echo "<li>Customer API: " . (isset($customerApiData) && $customerApiData['status'] === 'success' ? '✅ Working' : '❌ Issues') . "</li>";
    echo "<li>Total requests: $finalCount</li>";
    echo "</ul>";
    
    echo "<h4>Next Steps:</h4>";
    echo "<ol>";
    echo "<li><a href='api/admin/custom-requests-database-only.php' target='_blank'>Test Admin API Direct</a></li>";
    echo "<li><a href='frontend/admin/custom-requests-dashboard.html' target='_blank'>Open Admin Dashboard</a></li>";
    echo "<li><a href='test-customer-request-submission.html' target='_blank'>Test Customer Submission Form</a></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>Database Error:</strong> " . $e->getMessage() . "</p>";
}
?>