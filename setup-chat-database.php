<?php
// Simple script to set up the chat database tables
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Setting up Product Chat Database...</h2>\n";
    
    // Read and execute the SQL schema
    $sql = file_get_contents('backend/database/order_chat_schema.sql');
    
    // Split by semicolon and execute each statement
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $db->exec($statement);
                echo "✓ Executed: " . substr($statement, 0, 50) . "...<br>\n";
            } catch (Exception $e) {
                echo "✗ Error: " . $e->getMessage() . "<br>\n";
            }
        }
    }
    
    echo "<br><strong>✅ Database setup complete!</strong><br>\n";
    echo "<p>You can now use the product chat system in your cart.</p>\n";
    
} catch (Exception $e) {
    echo "<strong>❌ Error:</strong> " . $e->getMessage() . "\n";
}
?>