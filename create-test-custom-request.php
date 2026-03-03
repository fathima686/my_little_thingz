<?php
/**
 * Create Test Custom Request
 * This script creates a test custom request for testing the cart integration
 */

require_once "backend/config/database.php";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2>Creating Test Custom Request</h2>";
    
    // Check if custom_requests table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'custom_requests'");
    if ($tableCheck->rowCount() == 0) {
        echo "<p style='color: red;'>❌ custom_requests table does not exist!</p>";
        echo "<p>Please create the table first or check your database setup.</p>";
        exit;
    }
    
    // Get table structure
    echo "<h3>Current custom_requests table structure:</h3>";
    $columns = $pdo->query("SHOW COLUMNS FROM custom_requests");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if we have required columns
    $requiredColumns = ['id', 'customer_id', 'user_id', 'title', 'description'];
    $existingColumns = [];
    $columnsResult = $pdo->query("SHOW COLUMNS FROM custom_requests");
    while ($column = $columnsResult->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $column['Field'];
    }
    
    $missingColumns = array_diff($requiredColumns, $existingColumns);
    if (!empty($missingColumns)) {
        echo "<p style='color: red;'>❌ Missing required columns: " . implode(', ', $missingColumns) . "</p>";
        echo "<p>Please add these columns to your custom_requests table.</p>";
        exit;
    }
    
    // Create test request
    $testData = [
        'customer_id' => 1,
        'user_id' => 1,
        'title' => 'Test Custom Design Request',
        'description' => 'This is a test custom request for debugging the cart integration. Please create a beautiful design with flowers and hearts.',
        'status' => 'pending'
    ];
    
    // Build insert query dynamically based on available columns
    $insertColumns = [];
    $insertValues = [];
    $insertParams = [];
    
    foreach ($testData as $column => $value) {
        if (in_array($column, $existingColumns)) {
            $insertColumns[] = $column;
            $insertValues[] = '?';
            $insertParams[] = $value;
        }
    }
    
    // Add optional columns if they exist
    $optionalColumns = [
        'workflow_stage' => 'submitted',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    foreach ($optionalColumns as $column => $value) {
        if (in_array($column, $existingColumns)) {
            $insertColumns[] = $column;
            $insertValues[] = '?';
            $insertParams[] = $value;
        }
    }
    
    $insertSQL = "INSERT INTO custom_requests (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertValues) . ")";
    
    echo "<h3>Creating test request...</h3>";
    echo "<p>SQL: <code>$insertSQL</code></p>";
    
    $insertStmt = $pdo->prepare($insertSQL);
    $insertStmt->execute($insertParams);
    
    $newRequestId = $pdo->lastInsertId();
    
    echo "<p style='color: green;'>✅ Test custom request created successfully!</p>";
    echo "<p><strong>Request ID:</strong> $newRequestId</p>";
    echo "<p><strong>Customer ID:</strong> 1</p>";
    echo "<p><strong>Title:</strong> {$testData['title']}</p>";
    
    // Show the created request
    echo "<h3>Created Request Details:</h3>";
    $selectStmt = $pdo->prepare("SELECT * FROM custom_requests WHERE id = ?");
    $selectStmt->execute([$newRequestId]);
    $createdRequest = $selectStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    foreach ($createdRequest as $key => $value) {
        echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
    }
    echo "</table>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Use Request ID <strong>$newRequestId</strong> in your debug tool</li>";
    echo "<li>Use Customer ID <strong>1</strong> in your debug tool</li>";
    echo "<li>Run the debug flow to test design completion → cart integration</li>";
    echo "</ol>";
    
    echo "<h3>Test URLs:</h3>";
    echo "<p><a href='debug-custom-design-cart-flow.html' target='_blank'>Open Debug Tool</a></p>";
    echo "<p>Enter Request ID: <strong>$newRequestId</strong> and Customer ID: <strong>1</strong></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error creating test request</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>