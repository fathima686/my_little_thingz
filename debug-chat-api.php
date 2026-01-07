<?php
// Debug script to check chat API issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Chat API Debug</h2>";

// Test database connection
try {
    require_once 'backend/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Database connection successful<br>";
    
    // Check if product_chat_messages table exists
    $stmt = $db->query("SHOW TABLES LIKE 'product_chat_messages'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo "✅ product_chat_messages table exists<br>";
        
        // Check table structure
        $stmt = $db->query("DESCRIBE product_chat_messages");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "📋 Table columns:<br>";
        foreach ($columns as $col) {
            echo "- {$col['Field']} ({$col['Type']})<br>";
        }
        
        // Check if there are any messages
        $stmt = $db->query("SELECT COUNT(*) as count FROM product_chat_messages");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "📊 Total messages: {$count['count']}<br>";
        
    } else {
        echo "❌ product_chat_messages table does NOT exist<br>";
        echo "🔧 Need to run database setup<br>";
    }
    
    // Check if users table exists (for JOIN)
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    $users_exists = $stmt->rowCount() > 0;
    echo ($users_exists ? "✅" : "❌") . " users table " . ($users_exists ? "exists" : "does NOT exist") . "<br>";
    
    // Check if products/artworks table exists
    $stmt = $db->query("SHOW TABLES LIKE 'products'");
    $products_exists = $stmt->rowCount() > 0;
    echo ($products_exists ? "✅" : "❌") . " products table " . ($products_exists ? "exists" : "does NOT exist") . "<br>";
    
    $stmt = $db->query("SHOW TABLES LIKE 'artworks'");
    $artworks_exists = $stmt->rowCount() > 0;
    echo ($artworks_exists ? "✅" : "❌") . " artworks table " . ($artworks_exists ? "exists" : "does NOT exist") . "<br>";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<br><h3>🛠️ Next Steps:</h3>";
if (!$table_exists) {
    echo "<p>1. <a href='setup-chat-database.php'>Run Database Setup</a></p>";
}
echo "<p>2. <a href='test-product-chat.html'>Test Chat System</a></p>";
echo "<p>3. Check browser console for errors</p>";

// Test API directly
echo "<br><h3>🧪 API Test:</h3>";
echo "<button onclick='testAPI()'>Test Get Messages API</button>";
echo "<div id='api-result'></div>";

?>

<script>
async function testAPI() {
    const resultDiv = document.getElementById('api-result');
    resultDiv.innerHTML = 'Testing...';
    
    try {
        const response = await fetch('backend/api/product_chat/get_messages.php?product_id=1&user_id=1', {
            method: 'GET',
            headers: {
                'X-User-ID': '1'
            }
        });
        
        const text = await response.text();
        resultDiv.innerHTML = `<pre>Status: ${response.status}\nResponse: ${text}</pre>`;
    } catch (error) {
        resultDiv.innerHTML = `<pre>Error: ${error.message}</pre>`;
    }
}
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
button { padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; }
button:hover { background: #005a87; }
pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>