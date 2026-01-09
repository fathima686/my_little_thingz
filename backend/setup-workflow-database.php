<?php
// Setup Admin Workflow Database
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2>Setting up Admin Workflow Database</h2>";
    
    // Read and execute schema
    $schemaFile = __DIR__ . "/database/admin-workflow-schema.sql";
    if (file_exists($schemaFile)) {
        $schema = file_get_contents($schemaFile);
        $statements = explode(';', $schema);
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                    $successCount++;
                    echo "✅ Executed: " . substr($statement, 0, 50) . "...<br>";
                } catch (Exception $e) {
                    $errorCount++;
                    echo "❌ Error: " . $e->getMessage() . "<br>";
                    echo "&nbsp;&nbsp;&nbsp;Statement: " . substr($statement, 0, 100) . "...<br>";
                }
            }
        }
        
        echo "<br><strong>Summary:</strong><br>";
        echo "✅ Successful statements: $successCount<br>";
        echo "❌ Failed statements: $errorCount<br>";
        
    } else {
        echo "❌ Schema file not found: $schemaFile<br>";
    }
    
    // Update existing custom_requests with sample categories
    echo "<br><h3>Updating Sample Data</h3>";
    
    $updateStmt = $pdo->prepare("
        UPDATE custom_requests 
        SET category = CASE 
            WHEN title LIKE '%photo%' OR title LIKE '%frame%' THEN 'Photo Frames'
            WHEN title LIKE '%wedding%' OR title LIKE '%anniversary%' THEN 'Wedding Cards'
            WHEN title LIKE '%baby%' OR title LIKE '%name%' THEN 'Name Boards'
            WHEN title LIKE '%bouquet%' OR title LIKE '%flower%' THEN 'Bouquets'
            ELSE 'Handcrafted Gifts'
        END,
        product_type = CASE 
            WHEN title LIKE '%photo%' OR title LIKE '%frame%' OR title LIKE '%wedding%' OR title LIKE '%name%' THEN 'design-based'
            ELSE 'handmade'
        END
        WHERE category IS NULL OR category = ''
    ");
    
    $updated = $updateStmt->execute();
    $rowsUpdated = $updateStmt->rowCount();
    
    if ($updated) {
        echo "✅ Updated $rowsUpdated custom requests with categories and product types<br>";
    } else {
        echo "❌ Failed to update custom requests<br>";
    }
    
    // Check current data
    echo "<br><h3>Current Data Summary</h3>";
    
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total_requests,
            COUNT(CASE WHEN workflow_stage = 'submitted' THEN 1 END) as submitted,
            COUNT(CASE WHEN workflow_stage = 'in_design' THEN 1 END) as in_design,
            COUNT(CASE WHEN workflow_stage = 'in_crafting' THEN 1 END) as in_crafting,
            COUNT(CASE WHEN workflow_stage = 'packed' THEN 1 END) as packed,
            COUNT(CASE WHEN workflow_stage = 'delivered' THEN 1 END) as delivered
        FROM custom_requests
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "📊 <strong>Custom Requests:</strong><br>";
    echo "&nbsp;&nbsp;Total: {$stats['total_requests']}<br>";
    echo "&nbsp;&nbsp;Submitted: {$stats['submitted']}<br>";
    echo "&nbsp;&nbsp;In Design: {$stats['in_design']}<br>";
    echo "&nbsp;&nbsp;In Crafting: {$stats['in_crafting']}<br>";
    echo "&nbsp;&nbsp;Packed: {$stats['packed']}<br>";
    echo "&nbsp;&nbsp;Delivered: {$stats['delivered']}<br>";
    
    $categories = $pdo->query("SELECT COUNT(*) as count FROM product_categories")->fetchColumn();
    echo "<br>📋 <strong>Product Categories:</strong> $categories<br>";
    
    $images = $pdo->query("SELECT COUNT(*) as count FROM custom_request_images")->fetchColumn();
    echo "🖼️ <strong>Request Images:</strong> $images<br>";
    
    echo "<br><h3>✅ Database Setup Complete!</h3>";
    echo "<p>You can now use the admin workflow dashboard to manage custom requests.</p>";
    
    echo "<br><h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li><a href='frontend/admin/admin-workflow-dashboard.html' target='_blank'>Open Admin Workflow Dashboard</a></li>";
    echo "<li><a href='frontend/customer/order-tracking.html' target='_blank'>Test Customer Order Tracking</a></li>";
    echo "<li><a href='backend/api/admin/workflow-manager.php?action=requests' target='_blank'>Test API Endpoint</a></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "❌ <strong>Error:</strong> " . $e->getMessage();
}
?>