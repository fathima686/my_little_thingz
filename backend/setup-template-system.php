<?php
// Template System Setup Script
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

ini_set("display_errors", 1);
error_reporting(E_ALL);

try {
    require_once "config/database.php";
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h1>Template System Setup</h1>\n";
    echo "<p>Setting up template gallery database schema...</p>\n";
    
    // Read and execute schema file
    $schemaFile = __DIR__ . '/database/template-gallery-schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    $sql = file_get_contents($schemaFile);
    if (!$sql) {
        throw new Exception("Could not read schema file");
    }
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $successCount++;
            echo "<p style='color: green;'>✓ Executed: " . substr($statement, 0, 50) . "...</p>\n";
        } catch (Exception $e) {
            $errorCount++;
            echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>\n";
            echo "<p style='color: gray;'>Statement: " . substr($statement, 0, 100) . "...</p>\n";
        }
    }
    
    echo "<hr>\n";
    echo "<h2>Setup Summary</h2>\n";
    echo "<p>Successful statements: $successCount</p>\n";
    echo "<p>Failed statements: $errorCount</p>\n";
    
    // Test the setup by checking tables
    echo "<h2>Table Verification</h2>\n";
    $tables = ['design_templates', 'template_categories', 'template_usage'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>Table: $table</h3>\n";
            echo "<ul>\n";
            foreach ($columns as $column) {
                echo "<li>{$column['Field']} - {$column['Type']}</li>\n";
            }
            echo "</ul>\n";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Table $table not found or error: " . $e->getMessage() . "</p>\n";
        }
    }
    
    // Check if sample data exists
    echo "<h2>Sample Data Check</h2>\n";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM design_templates");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Templates in database: $count</p>\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM template_categories");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Categories in database: $count</p>\n";
        
        if ($count > 0) {
            $stmt = $pdo->query("SELECT name, display_name FROM template_categories ORDER BY sort_order");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>Available Categories:</h3>\n";
            echo "<ul>\n";
            foreach ($categories as $category) {
                echo "<li>{$category['display_name']} ({$category['name']})</li>\n";
            }
            echo "</ul>\n";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error checking sample data: " . $e->getMessage() . "</p>\n";
    }
    
    echo "<hr>\n";
    echo "<h2>Next Steps</h2>\n";
    echo "<p>1. Test the template gallery API: <a href='api/admin/template-gallery.php' target='_blank'>template-gallery.php</a></p>\n";
    echo "<p>2. Test the template editor API: <a href='api/admin/template-editor.php' target='_blank'>template-editor.php</a></p>\n";
    echo "<p>3. Run the system test: <a href='test-template-system.html' target='_blank'>test-template-system.html</a></p>\n";
    echo "<p>4. Try the template editor: <a href='../frontend/test-template-editor.html' target='_blank'>test-template-editor.html</a></p>\n";
    
} catch (Exception $e) {
    echo "<h1 style='color: red;'>Setup Failed</h1>\n";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
    echo "<p>Please check your database configuration and try again.</p>\n";
}
?>