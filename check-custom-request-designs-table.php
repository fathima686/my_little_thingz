<?php
/**
 * Check and Fix custom_request_designs Table
 * This is the key table that stores completed designs
 */

require_once "backend/config/database.php";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2>🔍 Checking custom_request_designs Table</h2>";
    
    // Check if table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'custom_request_designs'");
    if ($tableCheck->rowCount() == 0) {
        echo "<p style='color: red;'>❌ custom_request_designs table does NOT exist!</p>";
        echo "<p>This table is essential for storing completed designs. Creating it now...</p>";
        
        $createTableSQL = "CREATE TABLE custom_request_designs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            request_id INT UNSIGNED NOT NULL,
            template_id INT UNSIGNED,
            canvas_width INT NOT NULL DEFAULT 800,
            canvas_height INT NOT NULL DEFAULT 600,
            canvas_data LONGTEXT,
            canvas_data_file VARCHAR(255),
            design_image_url VARCHAR(500),
            design_pdf_url VARCHAR(500),
            version INT DEFAULT 1,
            status ENUM('draft', 'designing', 'design_completed', 'approved', 'rejected') DEFAULT 'designing',
            admin_notes TEXT,
            customer_feedback TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_request_id (request_id),
            INDEX idx_status (status)
        )";
        
        $pdo->exec($createTableSQL);
        echo "<p style='color: green;'>✅ custom_request_designs table created successfully!</p>";
    } else {
        echo "<p style='color: green;'>✅ custom_request_designs table exists</p>";
    }
    
    // Show current table structure
    echo "<h3>Current Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    
    $columns = $pdo->query("SHOW COLUMNS FROM custom_request_designs");
    $existingColumns = [];
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
    
    // Check for required columns and add if missing
    $requiredColumns = [
        'canvas_data_file' => "VARCHAR(255)",
        'design_image_url' => "VARCHAR(500)",
        'status' => "ENUM('draft', 'designing', 'design_completed', 'approved', 'rejected') DEFAULT 'designing'"
    ];
    
    foreach ($requiredColumns as $columnName => $columnDef) {
        if (!in_array($columnName, $existingColumns)) {
            try {
                $alterSQL = "ALTER TABLE custom_request_designs ADD COLUMN $columnName $columnDef";
                $pdo->exec($alterSQL);
                echo "<p style='color: green;'>✅ Added missing column '$columnName'</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>✗ Failed to add column '$columnName': " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // Check current data
    $countStmt = $pdo->query("SELECT COUNT(*) as count FROM custom_request_designs");
    $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<h3>Current Design Records:</h3>";
    echo "<p>Total designs: <strong>$count</strong></p>";
    
    if ($count > 0) {
        echo "<p>Recent designs:</p>";
        $recentStmt = $pdo->query("
            SELECT id, request_id, status, design_image_url, canvas_data_file, created_at 
            FROM custom_request_designs 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Request ID</th><th>Status</th><th>Has Image</th><th>Has Canvas File</th><th>Created</th></tr>";
        
        while ($design = $recentStmt->fetch(PDO::FETCH_ASSOC)) {
            $hasImage = $design['design_image_url'] ? '✅' : '❌';
            $hasCanvas = $design['canvas_data_file'] ? '✅' : '❌';
            $statusColor = $design['status'] === 'design_completed' ? 'green' : 'orange';
            
            echo "<tr>";
            echo "<td>{$design['id']}</td>";
            echo "<td>{$design['request_id']}</td>";
            echo "<td style='color: $statusColor;'>{$design['status']}</td>";
            echo "<td>$hasImage</td>";
            echo "<td>$hasCanvas</td>";
            echo "<td>{$design['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check for completed designs
        $completedStmt = $pdo->query("SELECT COUNT(*) as count FROM custom_request_designs WHERE status = 'design_completed'");
        $completedCount = $completedStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "<p><strong>Completed designs:</strong> $completedCount</p>";
        
        if ($completedCount > 0) {
            echo "<p style='color: green;'>✅ You have completed designs! Let's check if they're in the cart...</p>";
            
            // Check if completed designs have corresponding artworks
            echo "<h3>Checking Artwork Creation for Completed Designs:</h3>";
            
            $completedDesigns = $pdo->query("
                SELECT crd.id, crd.request_id, cr.customer_id, cr.user_id, cr.title 
                FROM custom_request_designs crd
                JOIN custom_requests cr ON crd.request_id = cr.id
                WHERE crd.status = 'design_completed'
                ORDER BY crd.created_at DESC
            ");
            
            while ($design = $completedDesigns->fetch(PDO::FETCH_ASSOC)) {
                $requestId = $design['request_id'];
                $customerId = $design['customer_id'] ?: $design['user_id'];
                
                echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
                echo "<h4>Design for Request #{$requestId}</h4>";
                echo "<p><strong>Customer ID:</strong> $customerId</p>";
                echo "<p><strong>Title:</strong> {$design['title']}</p>";
                
                // Check if artwork exists
                $artworkStmt = $pdo->prepare("
                    SELECT id, title, price, category, status 
                    FROM artworks 
                    WHERE category = 'custom' AND description LIKE ?
                ");
                $artworkStmt->execute(["%Request #$requestId%"]);
                $artwork = $artworkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($artwork) {
                    echo "<p style='color: green;'>✅ Artwork created: ID {$artwork['id']}, Title: {$artwork['title']}, Price: ₹{$artwork['price']}</p>";
                    
                    // Check if in cart
                    if ($customerId) {
                        $cartStmt = $pdo->prepare("
                            SELECT c.id, c.quantity, c.added_at 
                            FROM cart c 
                            WHERE c.user_id = ? AND c.artwork_id = ?
                        ");
                        $cartStmt->execute([$customerId, $artwork['id']]);
                        $cartItem = $cartStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($cartItem) {
                            echo "<p style='color: green;'>✅ In cart: Quantity {$cartItem['quantity']}, Added {$cartItem['added_at']}</p>";
                        } else {
                            echo "<p style='color: red;'>❌ NOT in cart for customer $customerId</p>";
                            echo "<p style='color: orange;'>🔧 This design should be added to cart automatically!</p>";
                        }
                    } else {
                        echo "<p style='color: red;'>❌ No customer ID found - cannot add to cart</p>";
                    }
                } else {
                    echo "<p style='color: red;'>❌ No artwork created for this design</p>";
                    echo "<p style='color: orange;'>🔧 The addCompletedDesignToCart function may not be working</p>";
                }
                
                echo "</div>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ No completed designs found. Designs need to be marked as 'design_completed' to appear in cart.</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No design records found. This table gets populated when admin completes designs.</p>";
    }
    
    // Show the complete flow
    echo "<h3>📋 Complete Flow Explanation:</h3>";
    echo "<div style='background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<ol>";
    echo "<li><strong>Customer creates request</strong> → Record in <code>custom_requests</code> table</li>";
    echo "<li><strong>Admin opens design editor</strong> → Uses request data</li>";
    echo "<li><strong>Admin completes design</strong> → Record in <code>custom_request_designs</code> with status 'design_completed'</li>";
    echo "<li><strong>System automatically:</strong>";
    echo "<ul>";
    echo "<li>Creates artwork in <code>artworks</code> table</li>";
    echo "<li>Adds artwork to customer's <code>cart</code></li>";
    echo "</ul>";
    echo "</li>";
    echo "<li><strong>Customer sees design in cart</strong> → Can proceed with payment</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h3>🔧 Next Steps:</h3>";
    echo "<ul>";
    echo "<li>If you have completed designs but no artworks: Check server logs for addCompletedDesignToCart errors</li>";
    echo "<li>If you have artworks but not in cart: Check customer ID mapping</li>";
    echo "<li>If no completed designs: Use admin design editor to complete a design</li>";
    echo "<li>Test the flow using the debug tools</li>";
    echo "</ul>";
    
    echo "<h3>🧪 Test Links:</h3>";
    echo "<p><a href='debug-custom-design-cart-flow.html' target='_blank'>Debug Tool</a> | ";
    echo "<a href='create-test-custom-request.php' target='_blank'>Create Test Request</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error checking custom_request_designs table</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>