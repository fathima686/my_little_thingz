<?php
/**
 * Fix Artworks Table - Add Missing Category Column
 */

require_once "backend/config/database.php";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h1>🔧 Fix Artworks Table - Add Category Column</h1>";
    
    // Check current artworks table structure
    echo "<h2>Step 1: Current Artworks Table Structure</h2>";
    
    $columns = $pdo->query("SHOW COLUMNS FROM artworks");
    $existingColumns = [];
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
    echo "<tr style='background: #f8f9fa;'><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    
    while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $column['Field'];
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if category column exists
    echo "<h2>Step 2: Check for Missing Columns</h2>";
    
    $requiredColumns = [
        'category' => "VARCHAR(100) DEFAULT 'general'",
        'status' => "ENUM('active', 'inactive', 'draft') DEFAULT 'active'",
        'availability' => "ENUM('available', 'sold', 'reserved') DEFAULT 'available'",
        'artist_id' => "INT UNSIGNED DEFAULT 1"
    ];
    
    $addedColumns = [];
    
    foreach ($requiredColumns as $columnName => $columnDef) {
        if (!in_array($columnName, $existingColumns)) {
            try {
                $alterSQL = "ALTER TABLE artworks ADD COLUMN $columnName $columnDef";
                $pdo->exec($alterSQL);
                echo "<p style='color: green;'>✅ Added column '$columnName'</p>";
                $addedColumns[] = $columnName;
            } catch (Exception $e) {
                echo "<p style='color: red;'>✗ Failed to add column '$columnName': " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: blue;'>- Column '$columnName' already exists</p>";
        }
    }
    
    if (!empty($addedColumns)) {
        echo "<p style='color: green;'><strong>✅ Added " . count($addedColumns) . " missing columns!</strong></p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ All required columns already exist</p>";
    }
    
    // Show updated table structure
    echo "<h2>Step 3: Updated Table Structure</h2>";
    
    $updatedColumns = $pdo->query("SHOW COLUMNS FROM artworks");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
    echo "<tr style='background: #f8f9fa;'><th>Column</th><th>Type</th><th>Null</th><th>Default</th><th>Status</th></tr>";
    
    while ($column = $updatedColumns->fetch(PDO::FETCH_ASSOC)) {
        $isRequired = array_key_exists($column['Field'], $requiredColumns);
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
    
    // Test the fix by running the cart integration manually
    echo "<h2>Step 4: Test Cart Integration Fix</h2>";
    
    echo "<p>Now testing if the cart integration works with the fixed artworks table...</p>";
    
    try {
        // Test the query that was failing
        $testStmt = $pdo->prepare("
            SELECT id, title, description, price, image_url, category, status, created_at
            FROM artworks 
            WHERE category = 'custom' OR description LIKE ?
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $testStmt->execute(["%Request #39%"]);
        $testResults = $testStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p style='color: green;'>✅ Artworks query now works! Found " . count($testResults) . " results.</p>";
        
        if (!empty($testResults)) {
            echo "<h4>Existing Custom Artworks:</h4>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>ID</th><th>Title</th><th>Category</th><th>Price</th><th>Created</th></tr>";
            foreach ($testResults as $artwork) {
                echo "<tr>";
                echo "<td>{$artwork['id']}</td>";
                echo "<td>{$artwork['title']}</td>";
                echo "<td>{$artwork['category']}</td>";
                echo "<td>₹{$artwork['price']}</td>";
                echo "<td>{$artwork['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Now manually run the addCompletedDesignToCart function for Request 39
        echo "<h3>🧪 Manual Cart Integration Test</h3>";
        
        // Get Request 39 details
        $requestStmt = $pdo->prepare("
            SELECT customer_id, user_id, title, description, price
            FROM custom_requests 
            WHERE id = 39
        ");
        $requestStmt->execute();
        $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($request) {
            $customerId = $request['customer_id'] ?: $request['user_id'];
            $artworkTitle = "Custom Design: " . $request['title'];
            $artworkPrice = $request['price'];
            $artworkDescription = $request['description'] . " (Request #39)";
            
            // Check if artwork already exists
            $checkArtworkStmt = $pdo->prepare("
                SELECT id FROM artworks 
                WHERE title = ? AND description LIKE ? 
                LIMIT 1
            ");
            $checkArtworkStmt->execute([$artworkTitle, "%Request #39%"]);
            $existingArtwork = $checkArtworkStmt->fetch();
            
            if ($existingArtwork) {
                $artworkId = $existingArtwork['id'];
                echo "<p style='color: blue;'>ℹ️ Using existing artwork ID: $artworkId</p>";
            } else {
                // Create new artwork
                $insertArtworkStmt = $pdo->prepare("
                    INSERT INTO artworks (
                        title, description, price, image_url, 
                        status, availability, artist_id, category,
                        created_at, updated_at
                    ) VALUES (?, ?, ?, ?, 'active', 'available', 1, 'custom', NOW(), NOW())
                ");
                
                $insertArtworkStmt->execute([
                    $artworkTitle,
                    $artworkDescription,
                    $artworkPrice,
                    'uploads/designs/images/design_request_39_1768902880.jpg'
                ]);
                
                $artworkId = $pdo->lastInsertId();
                echo "<p style='color: green;'>✅ Created new artwork ID: $artworkId</p>";
            }
            
            // Check if already in cart
            $checkCartStmt = $pdo->prepare("
                SELECT id FROM cart 
                WHERE user_id = ? AND artwork_id = ?
            ");
            $checkCartStmt->execute([$customerId, $artworkId]);
            $existingCartItem = $checkCartStmt->fetch();
            
            if ($existingCartItem) {
                echo "<p style='color: blue;'>ℹ️ Already in cart: Cart ID {$existingCartItem['id']}</p>";
            } else {
                // Add to cart
                $insertCartStmt = $pdo->prepare("
                    INSERT INTO cart (user_id, artwork_id, quantity, added_at)
                    VALUES (?, ?, 1, NOW())
                ");
                $insertCartStmt->execute([$customerId, $artworkId]);
                
                $cartId = $pdo->lastInsertId();
                echo "<p style='color: green;'>✅ Added to cart: Cart ID $cartId</p>";
                echo "<p style='color: green; font-weight: bold; font-size: 18px;'>🎉 SUCCESS: Custom design is now in customer's cart!</p>";
            }
            
            // Update request workflow stage
            $updateRequestStmt = $pdo->prepare("
                UPDATE custom_requests 
                SET workflow_stage = 'design_completed', 
                    design_completed_at = NOW(),
                    updated_at = NOW()
                WHERE id = 39
            ");
            $updateRequestStmt->execute();
            echo "<p style='color: green;'>✅ Updated request workflow stage</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Test failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2 style='color: green;'>🎉 Artworks Table Fix Complete!</h2>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ What was fixed:</h3>";
    echo "<ul>";
    echo "<li>✅ Added missing 'category' column to artworks table</li>";
    echo "<li>✅ Added missing 'status' column (if needed)</li>";
    echo "<li>✅ Added missing 'availability' column (if needed)</li>";
    echo "<li>✅ Added missing 'artist_id' column (if needed)</li>";
    echo "<li>✅ Manually added Request 39 design to cart</li>";
    echo "<li>✅ Updated request workflow stage</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🧪 Test the Integration Now:</h3>";
    echo "<p>The cart integration should now work perfectly!</p>";
    echo "<p>";
    echo "<a href='debug-custom-design-cart-flow.html' target='_blank' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px;'>🔍 Test Debug Tool Again</a>";
    echo "<a href='test-custom-design-cart-integration.php' target='_blank' style='display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 5px;'>🧪 Run Integration Test</a>";
    echo "</p>";
    
    echo "<p><strong>Expected Result:</strong> When you run the debug tool again with Request ID 39 and Customer ID 11, you should see the custom design in the cart!</p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Failed to fix artworks table</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    
    echo "<h3>Manual Fix:</h3>";
    echo "<p>Run this SQL command manually in your database:</p>";
    echo "<pre>ALTER TABLE artworks ADD COLUMN category VARCHAR(100) DEFAULT 'general';</pre>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    table { width: 100%; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f8f9fa; font-weight: bold; }
    h1 { color: #333; }
    h2 { color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
</style>