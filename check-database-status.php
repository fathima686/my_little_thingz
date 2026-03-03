<?php
/**
 * Quick Database Status Check
 * Shows the current state of all relevant tables
 */

require_once "backend/config/database.php";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h1>📊 Database Status Check</h1>";
    echo "<p>Current status of all tables related to custom design cart integration.</p>";
    
    // Check each table
    $tables = [
        'custom_requests' => [
            'description' => 'Customer custom design requests',
            'key_columns' => ['id', 'customer_id', 'user_id', 'title', 'price', 'status']
        ],
        'custom_request_designs' => [
            'description' => 'Admin-created designs for requests',
            'key_columns' => ['id', 'request_id', 'canvas_data_file', 'design_image_url', 'status']
        ],
        'artworks' => [
            'description' => 'All artworks including custom designs',
            'key_columns' => ['id', 'title', 'price', 'category', 'status']
        ],
        'cart' => [
            'description' => 'Customer shopping cart items',
            'key_columns' => ['id', 'user_id', 'artwork_id', 'quantity']
        ]
    ];
    
    foreach ($tables as $tableName => $tableInfo) {
        echo "<div style='border: 1px solid #ddd; margin: 15px 0; padding: 15px; border-radius: 5px;'>";
        echo "<h2>📋 Table: $tableName</h2>";
        echo "<p><em>{$tableInfo['description']}</em></p>";
        
        // Check if table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE '$tableName'");
        if ($tableCheck->rowCount() == 0) {
            echo "<p style='color: red; font-weight: bold;'>❌ TABLE DOES NOT EXIST</p>";
            echo "<p>This table needs to be created for the integration to work.</p>";
            continue;
        }
        
        echo "<p style='color: green;'>✅ Table exists</p>";
        
        // Get table structure
        echo "<h3>Table Structure:</h3>";
        $columns = $pdo->query("SHOW COLUMNS FROM $tableName");
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
        echo "<tr style='background: #f8f9fa;'><th>Column</th><th>Type</th><th>Null</th><th>Default</th><th>Status</th></tr>";
        
        $existingColumns = [];
        while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
            $existingColumns[] = $column['Field'];
            $isRequired = in_array($column['Field'], $tableInfo['key_columns']);
            $status = $isRequired ? '🔑 Required' : '📝 Optional';
            $rowColor = $isRequired ? 'background: #e8f5e8;' : '';
            
            echo "<tr style='$rowColor'>";
            echo "<td><strong>{$column['Field']}</strong></td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>$status</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check for missing required columns
        $missingColumns = array_diff($tableInfo['key_columns'], $existingColumns);
        if (!empty($missingColumns)) {
            echo "<p style='color: red;'><strong>❌ Missing required columns:</strong> " . implode(', ', $missingColumns) . "</p>";
        } else {
            echo "<p style='color: green;'>✅ All required columns present</p>";
        }
        
        // Get row count and sample data
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM $tableName")->fetchColumn();
            echo "<h3>Data Summary:</h3>";
            echo "<p><strong>Total records:</strong> $count</p>";
            
            if ($count > 0) {
                echo "<p style='color: green;'>✅ Has data</p>";
                
                // Show sample data for key tables
                if ($tableName === 'custom_requests') {
                    echo "<h4>Recent Requests:</h4>";
                    $samples = $pdo->query("SELECT id, title, status, created_at FROM $tableName ORDER BY created_at DESC LIMIT 3");
                    echo "<ul>";
                    while ($sample = $samples->fetch(PDO::FETCH_ASSOC)) {
                        echo "<li><strong>ID {$sample['id']}:</strong> {$sample['title']} ({$sample['status']}) - {$sample['created_at']}</li>";
                    }
                    echo "</ul>";
                } elseif ($tableName === 'custom_request_designs') {
                    echo "<h4>Recent Designs:</h4>";
                    $samples = $pdo->query("SELECT id, request_id, status, created_at FROM $tableName ORDER BY created_at DESC LIMIT 3");
                    echo "<ul>";
                    while ($sample = $samples->fetch(PDO::FETCH_ASSOC)) {
                        echo "<li><strong>Design ID {$sample['id']}:</strong> Request #{$sample['request_id']} ({$sample['status']}) - {$sample['created_at']}</li>";
                    }
                    echo "</ul>";
                } elseif ($tableName === 'artworks') {
                    $customCount = $pdo->query("SELECT COUNT(*) FROM artworks WHERE category = 'custom'")->fetchColumn();
                    echo "<p><strong>Custom artworks:</strong> $customCount</p>";
                    if ($customCount > 0) {
                        $samples = $pdo->query("SELECT id, title, price FROM artworks WHERE category = 'custom' ORDER BY created_at DESC LIMIT 3");
                        echo "<ul>";
                        while ($sample = $samples->fetch(PDO::FETCH_ASSOC)) {
                            echo "<li><strong>ID {$sample['id']}:</strong> {$sample['title']} - ₹{$sample['price']}</li>";
                        }
                        echo "</ul>";
                    }
                } elseif ($tableName === 'cart') {
                    $customInCart = $pdo->query("
                        SELECT COUNT(*) FROM cart c 
                        JOIN artworks a ON c.artwork_id = a.id 
                        WHERE a.category = 'custom'
                    ")->fetchColumn();
                    echo "<p><strong>Custom designs in cart:</strong> $customInCart</p>";
                }
            } else {
                echo "<p style='color: orange;'>⚠️ No data (empty table)</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error reading data: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    
    // Overall status summary
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>🎯 Overall Status Summary</h2>";
    
    // Check if we have the minimum required data for testing
    try {
        $requestCount = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
        $hasRequests = $requestCount > 0;
        
        echo "<ul>";
        echo "<li>" . ($hasRequests ? "✅" : "❌") . " Custom requests: $requestCount</li>";
        
        if ($hasRequests) {
            $firstRequest = $pdo->query("SELECT id, customer_id, user_id FROM custom_requests ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            $customerId = $firstRequest['customer_id'] ?: $firstRequest['user_id'];
            echo "<li>🎯 <strong>Ready for testing with Request ID: {$firstRequest['id']}, Customer ID: $customerId</strong></li>";
        }
        
        $designCount = $pdo->query("SELECT COUNT(*) FROM custom_request_designs")->fetchColumn();
        echo "<li>" . ($designCount > 0 ? "✅" : "ℹ️") . " Designs created: $designCount</li>";
        
        $artworkCount = $pdo->query("SELECT COUNT(*) FROM artworks WHERE category = 'custom'")->fetchColumn();
        echo "<li>" . ($artworkCount > 0 ? "✅" : "ℹ️") . " Custom artworks: $artworkCount</li>";
        
        $cartCount = $pdo->query("
            SELECT COUNT(*) FROM cart c 
            JOIN artworks a ON c.artwork_id = a.id 
            WHERE a.category = 'custom'
        ")->fetchColumn();
        echo "<li>" . ($cartCount > 0 ? "✅" : "ℹ️") . " Custom designs in cart: $cartCount</li>";
        
        echo "</ul>";
        
        if ($hasRequests) {
            echo "<h3>🧪 Ready to Test!</h3>";
            echo "<p>You can now test the custom design → cart integration:</p>";
            echo "<p>";
            echo "<a href='debug-custom-design-cart-flow.html' target='_blank' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px;'>🔍 Debug Tool</a>";
            echo "<a href='test-custom-design-cart-integration.php' target='_blank' style='display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 5px;'>🧪 Integration Test</a>";
            echo "</p>";
        } else {
            echo "<h3>⚠️ Need Test Data</h3>";
            echo "<p>No custom requests found. Create some test data first:</p>";
            echo "<p><a href='fix-missing-request-data.php' target='_blank' style='display: inline-block; padding: 10px 20px; background: #ffc107; color: black; text-decoration: none; border-radius: 5px;'>📝 Create Test Data</a></p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error checking overall status: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Database connection failed</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in <code>backend/config/database.php</code></p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    table { width: 100%; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f8f9fa; font-weight: bold; }
    h1 { color: #333; }
    h2 { color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
    h3 { color: #495057; }
</style>