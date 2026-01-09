<?php
// Fix Custom Requests Visibility Issue
header("Content-Type: text/html; charset=UTF-8");

echo "<h2>🔧 Fixing Custom Requests Visibility</h2>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h3>Step 1: Ensure Table Compatibility</h3>";
    
    // First, check if the table exists and get its structure
    $tableExists = $pdo->query("SHOW TABLES LIKE 'custom_requests'")->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<p style='color: red;'>❌ custom_requests table doesn't exist. Creating it...</p>";
        
        // Create the table with all necessary columns
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
            workflow_stage ENUM('submitted', 'in_design', 'in_crafting', 'design_completed', 'packed', 'courier_assigned', 'delivered') DEFAULT 'submitted',
            product_type ENUM('design-based', 'handmade', 'mixed') DEFAULT 'design-based',
            category VARCHAR(100) DEFAULT '',
            admin_id INT UNSIGNED DEFAULT NULL,
            started_at TIMESTAMP NULL,
            design_completed_at TIMESTAMP NULL,
            packed_at TIMESTAMP NULL,
            courier_assigned_at TIMESTAMP NULL,
            delivered_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_workflow_stage (workflow_stage),
            INDEX idx_customer_email (customer_email),
            INDEX idx_created_at (created_at)
        )";
        
        $pdo->exec($createTable);
        echo "<p style='color: green;'>✅ Created custom_requests table with all columns</p>";
    } else {
        echo "<p style='color: green;'>✅ custom_requests table exists</p>";
        
        // Check for missing workflow columns and add them
        $columns = $pdo->query("DESCRIBE custom_requests")->fetchAll(PDO::FETCH_COLUMN);
        
        $workflowColumns = [
            'workflow_stage' => "ENUM('submitted', 'in_design', 'in_crafting', 'design_completed', 'packed', 'courier_assigned', 'delivered') DEFAULT 'submitted'",
            'product_type' => "ENUM('design-based', 'handmade', 'mixed') DEFAULT 'design-based'",
            'category' => "VARCHAR(100) DEFAULT ''",
            'admin_id' => "INT UNSIGNED DEFAULT NULL",
            'started_at' => "TIMESTAMP NULL",
            'design_completed_at' => "TIMESTAMP NULL",
            'packed_at' => "TIMESTAMP NULL",
            'courier_assigned_at' => "TIMESTAMP NULL",
            'delivered_at' => "TIMESTAMP NULL"
        ];
        
        foreach ($workflowColumns as $colName => $colDef) {
            if (!in_array($colName, $columns)) {
                try {
                    $pdo->exec("ALTER TABLE custom_requests ADD COLUMN $colName $colDef");
                    echo "<p style='color: green;'>✅ Added column: $colName</p>";
                } catch (Exception $e) {
                    echo "<p style='color: red;'>❌ Failed to add column $colName: " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    echo "<h3>Step 2: Create Supporting Tables</h3>";
    
    // Create custom_request_images table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS custom_request_images (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            request_id INT UNSIGNED NOT NULL,
            image_url VARCHAR(500) NOT NULL,
            filename VARCHAR(255) NOT NULL,
            original_filename VARCHAR(255) DEFAULT '',
            file_size INT UNSIGNED DEFAULT 0,
            mime_type VARCHAR(100) DEFAULT '',
            uploaded_by ENUM('customer', 'admin') DEFAULT 'customer',
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_request_id (request_id),
            INDEX idx_uploaded_at (uploaded_at)
        )");
        echo "<p style='color: green;'>✅ custom_request_images table ready</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error creating images table: " . $e->getMessage() . "</p>";
    }
    
    // Create product_categories table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS product_categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            type ENUM('design-based', 'handmade', 'mixed') NOT NULL,
            description TEXT,
            requires_editor BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_name (name)
        )");
        
        // Insert default categories
        $pdo->exec("INSERT IGNORE INTO product_categories (name, type, requires_editor, description) VALUES
            ('Photo Frames', 'design-based', TRUE, 'Custom photo frames with personalized designs'),
            ('Polaroids', 'design-based', TRUE, 'Custom polaroid prints with editing'),
            ('Wedding Cards', 'design-based', TRUE, 'Wedding invitation cards with custom designs'),
            ('Name Boards', 'design-based', TRUE, 'Personalized name boards and signs'),
            ('Bouquets', 'handmade', FALSE, 'Handcrafted flower bouquets'),
            ('Handcrafted Gifts', 'handmade', FALSE, 'Custom handmade gift items'),
            ('Jewelry', 'handmade', FALSE, 'Custom jewelry pieces'),
            ('Cakes', 'handmade', FALSE, 'Custom decorated cakes')");
        
        echo "<p style='color: green;'>✅ product_categories table ready with default data</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error creating categories table: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>Step 3: Update Existing Data</h3>";
    
    // Update existing requests to have proper workflow_stage and product_type
    $updateStmt = $pdo->prepare("
        UPDATE custom_requests 
        SET 
            workflow_stage = CASE 
                WHEN status = 'submitted' THEN 'submitted'
                WHEN status = 'pending' THEN 'submitted'
                WHEN status = 'in_progress' THEN 'in_design'
                WHEN status = 'completed' THEN 'delivered'
                ELSE 'submitted'
            END,
            product_type = CASE 
                WHEN title LIKE '%photo%' OR title LIKE '%frame%' OR title LIKE '%wedding%' OR title LIKE '%card%' OR title LIKE '%name%' OR title LIKE '%board%' THEN 'design-based'
                ELSE 'handmade'
            END,
            category = CASE 
                WHEN title LIKE '%photo%' OR title LIKE '%frame%' THEN 'Photo Frames'
                WHEN title LIKE '%wedding%' OR title LIKE '%anniversary%' THEN 'Wedding Cards'
                WHEN title LIKE '%baby%' OR title LIKE '%name%' THEN 'Name Boards'
                WHEN title LIKE '%bouquet%' OR title LIKE '%flower%' THEN 'Bouquets'
                ELSE 'Handcrafted Gifts'
            END
        WHERE workflow_stage IS NULL OR workflow_stage = '' OR product_type IS NULL OR product_type = ''
    ");
    
    $updated = $updateStmt->execute();
    $rowsUpdated = $updateStmt->rowCount();
    echo "<p style='color: green;'>✅ Updated $rowsUpdated existing requests with workflow data</p>";
    
    echo "<h3>Step 4: Create Test Data (if needed)</h3>";
    
    // Check if we have any requests
    $requestCount = $pdo->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn();
    
    if ($requestCount == 0) {
        echo "<p style='color: orange;'>⚠️ No requests found. Creating sample data...</p>";
        
        $sampleRequests = [
            [
                'CR-' . date('Ymd') . '-001',
                'Test Customer 1',
                'customer1@test.com',
                '+1-555-0001',
                'Custom Photo Frame',
                'Anniversary',
                'Need a beautiful photo frame for our anniversary photo',
                'Silver frame with elegant design, size 8x10 inches',
                'Photo Frames',
                'design-based'
            ],
            [
                'CR-' . date('Ymd') . '-002',
                'Test Customer 2',
                'customer2@test.com',
                '+1-555-0002',
                'Handmade Bouquet',
                'Birthday',
                'Beautiful flower bouquet for birthday celebration',
                'Mixed flowers with roses and lilies, bright colors',
                'Bouquets',
                'handmade'
            ]
        ];
        
        $insertStmt = $pdo->prepare("
            INSERT INTO custom_requests (
                order_id, customer_name, customer_email, customer_phone,
                title, occasion, description, requirements, category, product_type,
                status, workflow_stage, source
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'submitted', 'form')
        ");
        
        foreach ($sampleRequests as $request) {
            $insertStmt->execute($request);
        }
        
        echo "<p style='color: green;'>✅ Created " . count($sampleRequests) . " sample requests</p>";
    } else {
        echo "<p style='color: green;'>✅ Found $requestCount existing requests</p>";
    }
    
    echo "<h3>Step 5: Test API Endpoints</h3>";
    
    // Test the old admin API
    try {
        $requests = $pdo->query("SELECT * FROM custom_requests ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Database query successful - found " . count($requests) . " requests</p>";
        
        foreach ($requests as $req) {
            echo "- ID {$req['id']}: {$req['title']} by {$req['customer_name']} (Stage: {$req['workflow_stage']})<br>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Database query failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>Step 6: Verification</h3>";
    
    $finalStats = $pdo->query("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN workflow_stage = 'submitted' THEN 1 END) as submitted,
            COUNT(CASE WHEN product_type = 'design-based' THEN 1 END) as design_based,
            COUNT(CASE WHEN product_type = 'handmade' THEN 1 END) as handmade
        FROM custom_requests
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Final Statistics:</strong></p>";
    echo "<ul>";
    echo "<li>Total Requests: {$finalStats['total']}</li>";
    echo "<li>Submitted: {$finalStats['submitted']}</li>";
    echo "<li>Design-based: {$finalStats['design_based']}</li>";
    echo "<li>Handmade: {$finalStats['handmade']}</li>";
    echo "</ul>";
    
    echo "<h3>✅ Fix Complete!</h3>";
    echo "<p>Custom requests should now be visible in both admin systems.</p>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li><a href='api/admin/custom-requests-database-only.php' target='_blank'>Test Old Admin API</a></li>";
    echo "<li><a href='api/admin/workflow-manager.php?action=requests' target='_blank'>Test New Workflow API</a></li>";
    echo "<li><a href='frontend/admin/admin-workflow-dashboard.html' target='_blank'>Open Admin Dashboard</a></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}
?>