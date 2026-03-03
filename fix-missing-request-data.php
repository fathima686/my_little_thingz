<?php
/**
 * Fix Missing Request Data
 * This script checks for and creates missing custom request data
 */

require_once "backend/config/database.php";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h1>🔍 Fix Missing Request Data</h1>";
    
    // Step 1: Check if custom_requests table exists
    echo "<h2>Step 1: Check Database Tables</h2>";
    
    $tables = ['custom_requests', 'custom_request_designs', 'artworks', 'cart'];
    $missingTables = [];
    
    foreach ($tables as $table) {
        $tableCheck = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($tableCheck->rowCount() == 0) {
            $missingTables[] = $table;
            echo "<p style='color: red;'>❌ Table '$table' is missing</p>";
        } else {
            echo "<p style='color: green;'>✅ Table '$table' exists</p>";
        }
    }
    
    if (!empty($missingTables)) {
        echo "<p style='color: red;'><strong>❌ Missing tables found!</strong></p>";
        echo "<p><a href='fix-database-structure-complete.php' target='_blank' class='btn'>Fix Database Structure First</a></p>";
        exit;
    }
    
    // Step 2: Check custom_requests data
    echo "<h2>Step 2: Check Custom Requests Data</h2>";
    
    $requestCount = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
    echo "<p>Total custom requests: <strong>$requestCount</strong></p>";
    
    if ($requestCount == 0) {
        echo "<p style='color: orange;'>⚠️ No custom requests found. Creating test data...</p>";
        
        // Create test requests
        $testRequests = [
            [
                'customer_id' => 1,
                'user_id' => 1,
                'title' => 'Beautiful Flower Arrangement',
                'description' => 'I need a custom design with roses and lilies for my anniversary. Please make it elegant and romantic.',
                'price' => 75.00,
                'status' => 'pending'
            ],
            [
                'customer_id' => 1,
                'user_id' => 1,
                'title' => 'Custom Birthday Gift Box',
                'description' => 'Custom gift box design for my daughter\'s 10th birthday. She loves unicorns and rainbows.',
                'price' => 50.00,
                'status' => 'pending'
            ],
            [
                'customer_id' => 2,
                'user_id' => 2,
                'title' => 'Wedding Decoration Set',
                'description' => 'Custom wedding decorations with gold and white theme. Need elegant and sophisticated design.',
                'price' => 120.00,
                'status' => 'pending'
            ]
        ];
        
        foreach ($testRequests as $index => $request) {
            try {
                $insertStmt = $pdo->prepare("
                    INSERT INTO custom_requests (customer_id, user_id, title, description, price, status, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                
                $insertStmt->execute([
                    $request['customer_id'],
                    $request['user_id'],
                    $request['title'],
                    $request['description'],
                    $request['price'],
                    $request['status']
                ]);
                
                $newId = $pdo->lastInsertId();
                echo "<p style='color: green;'>✅ Created request ID $newId: {$request['title']}</p>";
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>✗ Failed to create request " . ($index + 1) . ": " . $e->getMessage() . "</p>";
            }
        }
        
        $requestCount = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
        echo "<p style='color: green;'><strong>✅ Now have $requestCount custom requests</strong></p>";
    }
    
    // Step 3: Show current requests
    echo "<h2>Step 3: Current Custom Requests</h2>";
    
    $requests = $pdo->query("
        SELECT id, customer_id, user_id, title, description, price, status, created_at
        FROM custom_requests 
        ORDER BY id ASC
    ");
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th>ID</th><th>Customer ID</th><th>User ID</th><th>Title</th><th>Price</th><th>Status</th><th>Created</th>";
    echo "</tr>";
    
    $availableRequests = [];
    while ($request = $requests->fetch(PDO::FETCH_ASSOC)) {
        $availableRequests[] = $request;
        echo "<tr>";
        echo "<td><strong>{$request['id']}</strong></td>";
        echo "<td>{$request['customer_id']}</td>";
        echo "<td>{$request['user_id']}</td>";
        echo "<td>{$request['title']}</td>";
        echo "<td>₹{$request['price']}</td>";
        echo "<td>{$request['status']}</td>";
        echo "<td>{$request['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Step 4: Check for existing designs
    echo "<h2>Step 4: Check Existing Designs</h2>";
    
    $designCount = $pdo->query("SELECT COUNT(*) FROM custom_request_designs")->fetchColumn();
    echo "<p>Total designs: <strong>$designCount</strong></p>";
    
    if ($designCount > 0) {
        echo "<p>Recent designs:</p>";
        $designs = $pdo->query("
            SELECT crd.id, crd.request_id, crd.status, crd.design_image_url, crd.created_at,
                   cr.title as request_title
            FROM custom_request_designs crd
            JOIN custom_requests cr ON crd.request_id = cr.id
            ORDER BY crd.created_at DESC
            LIMIT 5
        ");
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Design ID</th><th>Request ID</th><th>Request Title</th><th>Status</th><th>Has Image</th><th>Created</th>";
        echo "</tr>";
        
        while ($design = $designs->fetch(PDO::FETCH_ASSOC)) {
            $hasImage = $design['design_image_url'] ? '✅' : '❌';
            $statusColor = $design['status'] === 'design_completed' ? 'green' : 'orange';
            
            echo "<tr>";
            echo "<td>{$design['id']}</td>";
            echo "<td>{$design['request_id']}</td>";
            echo "<td>{$design['request_title']}</td>";
            echo "<td style='color: $statusColor;'>{$design['status']}</td>";
            echo "<td>$hasImage</td>";
            echo "<td>{$design['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Step 5: Provide testing instructions
    echo "<h2>Step 5: Testing Instructions</h2>";
    
    if (!empty($availableRequests)) {
        $firstRequest = $availableRequests[0];
        $customerId = $firstRequest['customer_id'] ?: $firstRequest['user_id'];
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>✅ Ready for Testing!</h3>";
        echo "<p><strong>Use these values in the debug tool:</strong></p>";
        echo "<ul>";
        echo "<li><strong>Request ID:</strong> {$firstRequest['id']}</li>";
        echo "<li><strong>Customer ID:</strong> $customerId</li>";
        echo "<li><strong>Request Title:</strong> {$firstRequest['title']}</li>";
        echo "<li><strong>Price:</strong> ₹{$firstRequest['price']}</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<h3>🧪 Test Links:</h3>";
        echo "<p>";
        echo "<a href='debug-custom-design-cart-flow.html' target='_blank' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px;'>🔍 Debug Tool</a>";
        echo "<a href='test-custom-design-cart-integration.php' target='_blank' style='display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 5px;'>🧪 Integration Test</a>";
        echo "</p>";
        
        echo "<h3>📋 Testing Steps:</h3>";
        echo "<ol>";
        echo "<li>Open the <strong>Debug Tool</strong> above</li>";
        echo "<li>Enter <strong>Request ID: {$firstRequest['id']}</strong></li>";
        echo "<li>Enter <strong>Customer ID: $customerId</strong></li>";
        echo "<li>Click <strong>'Run Full Debug Flow'</strong></li>";
        echo "<li>The design completion should now work (no more 'Request not found' error)</li>";
        echo "<li>Check if the completed design appears in the cart</li>";
        echo "</ol>";
        
    } else {
        echo "<p style='color: red;'>❌ No requests available for testing</p>";
    }
    
    // Step 6: Show all available request IDs
    echo "<h2>Step 6: Available Request IDs for Testing</h2>";
    
    if (!empty($availableRequests)) {
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
        echo "<h4>You can test with any of these Request IDs:</h4>";
        foreach ($availableRequests as $req) {
            $customerId = $req['customer_id'] ?: $req['user_id'];
            echo "<p><strong>Request ID {$req['id']}:</strong> {$req['title']} (Customer ID: $customerId)</p>";
        }
        echo "</div>";
    }
    
    echo "<h2 style='color: green;'>🎉 Data Fix Complete!</h2>";
    echo "<p>You now have valid custom request data to test with. The 'Request not found' error should be resolved.</p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error fixing request data</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    table { width: 100%; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f8f9fa; font-weight: bold; }
    .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
</style>