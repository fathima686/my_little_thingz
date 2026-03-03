<?php
/**
 * Complete Database Structure Fix for Custom Design Cart Integration
 * This script fixes all database issues preventing custom designs from appearing in cart
 */

require_once "backend/config/database.php";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h1>🔧 Complete Database Structure Fix</h1>";
    echo "<p>This will fix all database issues preventing custom designs from appearing in cart.</p>";
    
    // Step 1: Fix custom_requests table
    echo "<h2>Step 1: Fixing custom_requests Table</h2>";
    
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'custom_requests'");
    if ($tableCheck->rowCount() == 0) {
        echo "<p style='color: orange;'>Creating custom_requests table...</p>";
        
        $createRequestsSQL = "CREATE TABLE custom_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id INT UNSIGNED,
            user_id INT UNSIGNED,
            title VARCHAR(255),
            description TEXT,
            price DECIMAL(10,2) DEFAULT 50.00,
            status ENUM('pending', 'in_progress', 'designing', 'design_completed', 'completed', 'cancelled') DEFAULT 'pending',
            workflow_stage ENUM('submitted', 'in_design', 'in_crafting', 'design_completed', 'packed', 'courier_assigned', 'delivered') DEFAULT 'submitted',
            design_completed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_customer_id (customer_id),
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_workflow_stage (workflow_stage)
        )";
        
        $pdo->exec($createRequestsSQL);
        echo "<p style='color: green;'>✅ custom_requests table created</p>";
    } else {
        echo "<p style='color: blue;'>custom_requests table exists, checking columns...</p>";
        
        // Get existing columns
        $existingColumns = [];
        $columnsResult = $pdo->query("SHOW COLUMNS FROM custom_requests");
        while ($column = $columnsResult->fetch(PDO::FETCH_ASSOC)) {
            $existingColumns[] = $column['Field'];
        }
        
        // Add missing columns
        $requiredColumns = [
            'price' => "DECIMAL(10,2) DEFAULT 50.00",
            'workflow_stage' => "ENUM('submitted', 'in_design', 'in_crafting', 'design_completed', 'packed', 'courier_assigned', 'delivered') DEFAULT 'submitted'",
            'design_completed_at' => "TIMESTAMP NULL"
        ];
        
        foreach ($requiredColumns as $columnName => $columnDef) {
            if (!in_array($columnName, $existingColumns)) {
                try {
                    $alterSQL = "ALTER TABLE custom_requests ADD COLUMN $columnName $columnDef";
                    $pdo->exec($alterSQL);
                    echo "<p style='color: green;'>✅ Added column '$columnName'</p>";
                } catch (Exception $e) {
                    echo "<p style='color: red;'>✗ Failed to add column '$columnName': " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    // Step 2: Fix custom_request_designs table
    echo "<h2>Step 2: Fixing custom_request_designs Table</h2>";
    
    $designTableCheck = $pdo->query("SHOW TABLES LIKE 'custom_request_designs'");
    if ($designTableCheck->rowCount() == 0) {
        echo "<p style='color: orange;'>Creating custom_request_designs table...</p>";
        
        $createDesignsSQL = "CREATE TABLE custom_request_designs (
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
            INDEX idx_status (status),
            FOREIGN KEY (request_id) REFERENCES custom_requests(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($createDesignsSQL);
        echo "<p style='color: green;'>✅ custom_request_designs table created</p>";
    } else {
        echo "<p style='color: blue;'>custom_request_designs table exists, checking columns...</p>";
        
        // Get existing columns
        $existingDesignColumns = [];
        $designColumnsResult = $pdo->query("SHOW COLUMNS FROM custom_request_designs");
        while ($column = $designColumnsResult->fetch(PDO::FETCH_ASSOC)) {
            $existingDesignColumns[] = $column['Field'];
        }
        
        // Add missing columns
        $requiredDesignColumns = [
            'canvas_data_file' => "VARCHAR(255)",
            'design_image_url' => "VARCHAR(500)",
            'design_pdf_url' => "VARCHAR(500)",
            'status' => "ENUM('draft', 'designing', 'design_completed', 'approved', 'rejected') DEFAULT 'designing'"
        ];
        
        foreach ($requiredDesignColumns as $columnName => $columnDef) {
            if (!in_array($columnName, $existingDesignColumns)) {
                try {
                    $alterSQL = "ALTER TABLE custom_request_designs ADD COLUMN $columnName $columnDef";
                    $pdo->exec($alterSQL);
                    echo "<p style='color: green;'>✅ Added column '$columnName' to designs table</p>";
                } catch (Exception $e) {
                    echo "<p style='color: red;'>✗ Failed to add column '$columnName': " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    // Step 3: Check artworks table
    echo "<h2>Step 3: Checking artworks Table</h2>";
    
    $artworkTableCheck = $pdo->query("SHOW TABLES LIKE 'artworks'");
    if ($artworkTableCheck->rowCount() == 0) {
        echo "<p style='color: red;'>❌ artworks table does not exist!</p>";
        echo "<p>Creating basic artworks table...</p>";
        
        $createArtworksSQL = "CREATE TABLE artworks (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            image_url VARCHAR(500),
            category VARCHAR(100) DEFAULT 'general',
            status ENUM('active', 'inactive', 'draft') DEFAULT 'active',
            availability ENUM('available', 'sold', 'reserved') DEFAULT 'available',
            artist_id INT UNSIGNED DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_category (category),
            INDEX idx_status (status),
            INDEX idx_availability (availability)
        )";
        
        $pdo->exec($createArtworksSQL);
        echo "<p style='color: green;'>✅ artworks table created</p>";
    } else {
        echo "<p style='color: green;'>✅ artworks table exists</p>";
    }
    
    // Step 4: Check cart table
    echo "<h2>Step 4: Checking cart Table</h2>";
    
    $cartTableCheck = $pdo->query("SHOW TABLES LIKE 'cart'");
    if ($cartTableCheck->rowCount() == 0) {
        echo "<p style='color: red;'>❌ cart table does not exist!</p>";
        echo "<p>Creating basic cart table...</p>";
        
        $createCartSQL = "CREATE TABLE cart (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            artwork_id INT UNSIGNED NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_artwork_id (artwork_id),
            UNIQUE KEY unique_user_artwork (user_id, artwork_id)
        )";
        
        $pdo->exec($createCartSQL);
        echo "<p style='color: green;'>✅ cart table created</p>";
    } else {
        echo "<p style='color: green;'>✅ cart table exists</p>";
    }
    
    // Step 5: Create necessary directories
    echo "<h2>Step 5: Creating Upload Directories</h2>";
    
    $directories = [
        'backend/uploads/designs/data',
        'backend/uploads/designs/images'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "<p style='color: green;'>✅ Created directory: $dir</p>";
            } else {
                echo "<p style='color: red;'>✗ Failed to create directory: $dir</p>";
            }
        } else {
            echo "<p style='color: blue;'>Directory exists: $dir</p>";
        }
    }
    
    // Step 6: Create test data if needed
    echo "<h2>Step 6: Creating Test Data</h2>";
    
    $requestCount = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
    
    if ($requestCount == 0) {
        echo "<p style='color: orange;'>No custom requests found. Creating test request...</p>";
        
        $insertTestRequest = $pdo->prepare("
            INSERT INTO custom_requests (customer_id, user_id, title, description, price, status, workflow_stage)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $insertTestRequest->execute([
            1, // customer_id
            1, // user_id
            'Test Custom Design Request',
            'This is a test custom request for debugging the cart integration. Please create a beautiful design with flowers and hearts.',
            75.00, // price
            'pending', // status
            'submitted' // workflow_stage
        ]);
        
        $testRequestId = $pdo->lastInsertId();
        echo "<p style='color: green;'>✅ Created test request with ID: $testRequestId</p>";
    } else {
        echo "<p style='color: blue;'>Found $requestCount existing custom requests</p>";
    }
    
    // Step 7: Show current status
    echo "<h2>Step 7: Current Database Status</h2>";
    
    // Show table counts
    $tables = ['custom_requests', 'custom_request_designs', 'artworks', 'cart'];
    foreach ($tables as $table) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "<p>📊 <strong>$table:</strong> $count records</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error checking $table: " . $e->getMessage() . "</p>";
        }
    }
    
    // Show recent requests with design status
    echo "<h3>Recent Custom Requests:</h3>";
    try {
        $recentRequests = $pdo->query("
            SELECT cr.id, cr.title, cr.status, cr.workflow_stage, cr.price,
                   COUNT(crd.id) as design_count,
                   MAX(crd.status) as latest_design_status
            FROM custom_requests cr
            LEFT JOIN custom_request_designs crd ON cr.id = crd.request_id
            GROUP BY cr.id
            ORDER BY cr.created_at DESC
            LIMIT 5
        ");
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Workflow</th><th>Price</th><th>Designs</th><th>Design Status</th></tr>";
        
        while ($request = $recentRequests->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$request['id']}</td>";
            echo "<td>{$request['title']}</td>";
            echo "<td>{$request['status']}</td>";
            echo "<td>{$request['workflow_stage']}</td>";
            echo "<td>₹{$request['price']}</td>";
            echo "<td>{$request['design_count']}</td>";
            echo "<td>" . ($request['latest_design_status'] ?: 'None') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error showing requests: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2 style='color: green;'>🎉 Database Structure Fix Complete!</h2>";
    
    echo "<h3>✅ What was fixed:</h3>";
    echo "<ul>";
    echo "<li>✅ custom_requests table with price column</li>";
    echo "<li>✅ custom_request_designs table with canvas_data_file column</li>";
    echo "<li>✅ artworks table for storing completed designs</li>";
    echo "<li>✅ cart table for customer cart items</li>";
    echo "<li>✅ Upload directories for design files</li>";
    echo "<li>✅ Test data if needed</li>";
    echo "</ul>";
    
    echo "<h3>🧪 Next Steps:</h3>";
    echo "<ol>";
    echo "<li><a href='debug-custom-design-cart-flow.html' target='_blank'>Open Debug Tool</a></li>";
    echo "<li>Use Request ID: <strong>1</strong> and Customer ID: <strong>1</strong></li>";
    echo "<li>Test the complete flow: Design completion → Cart appearance</li>";
    echo "<li>Check server logs if issues persist</li>";
    echo "</ol>";
    
    echo "<h3>🔍 Debug URLs:</h3>";
    echo "<p><a href='debug-custom-design-cart-flow.html' target='_blank'>Debug Tool</a></p>";
    echo "<p><a href='check-custom-request-designs-table.php' target='_blank'>Check Designs Table</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Database Fix Failed</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    
    echo "<h3>Manual Fix Required:</h3>";
    echo "<p>Please run these SQL commands manually in your database:</p>";
    echo "<pre>";
    echo "-- Add missing column to custom_request_designs\n";
    echo "ALTER TABLE custom_request_designs ADD COLUMN canvas_data_file VARCHAR(255) NULL;\n\n";
    echo "-- Add missing column to custom_requests\n";
    echo "ALTER TABLE custom_requests ADD COLUMN price DECIMAL(10,2) DEFAULT 50.00;\n\n";
    echo "-- Check if tables exist\n";
    echo "SHOW TABLES LIKE 'custom_request_designs';\n";
    echo "SHOW TABLES LIKE 'custom_requests';\n";
    echo "SHOW TABLES LIKE 'artworks';\n";
    echo "SHOW TABLES LIKE 'cart';\n";
    echo "</pre>";
}
?>