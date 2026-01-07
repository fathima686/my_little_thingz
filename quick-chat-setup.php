<!DOCTYPE html>
<html>
<head>
    <title>Quick Chat Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        button { padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        button:hover { background: #005a87; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .status.success { background: #d4edda; border: 1px solid #c3e6cb; }
        .status.error { background: #f8d7da; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <h1>🚀 Quick Chat System Setup</h1>
    
    <div id="status"></div>
    
    <button onclick="setupDatabase()">1. Setup Database</button>
    <button onclick="testAPI()">2. Test API</button>
    <button onclick="testChat()">3. Test Chat</button>
    
    <div id="results"></div>
    
    <script>
        function showStatus(message, type = 'info') {
            document.getElementById('status').innerHTML = `<div class="status ${type}">${message}</div>`;
        }
        
        function addResult(message) {
            document.getElementById('results').innerHTML += `<div>${message}</div>`;
        }
        
        async function setupDatabase() {
            showStatus('Setting up database...', 'info');
            try {
                const response = await fetch('setup-chat-tables.php');
                const result = await response.text();
                
                if (result.includes('Database setup complete')) {
                    showStatus('✅ Database setup successful!', 'success');
                    addResult('<h3>Database Setup Result:</h3><pre>' + result + '</pre>');
                } else {
                    showStatus('❌ Database setup failed', 'error');
                    addResult('<h3>Database Setup Error:</h3><pre>' + result + '</pre>');
                }
            } catch (error) {
                showStatus('❌ Error: ' + error.message, 'error');
            }
        }
        
        async function testAPI() {
            showStatus('Testing API endpoints...', 'info');
            try {
                // Test GET
                const getResponse = await fetch('test-chat-api-simple.php?product_id=1&user_id=1');
                const getResult = await getResponse.json();
                
                if (getResult.success) {
                    showStatus('✅ API GET test successful!', 'success');
                    addResult('<h3>GET API Result:</h3><pre>' + JSON.stringify(getResult, null, 2) + '</pre>');
                    
                    // Test POST
                    const postResponse = await fetch('test-chat-api-simple.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            product_id: 1,
                            user_id: 1,
                            message: 'Test message from setup script'
                        })
                    });
                    const postResult = await postResponse.json();
                    
                    if (postResult.success) {
                        showStatus('✅ API POST test successful! Chat system is ready!', 'success');
                        addResult('<h3>POST API Result:</h3><pre>' + JSON.stringify(postResult, null, 2) + '</pre>');
                    } else {
                        showStatus('❌ API POST test failed', 'error');
                        addResult('<h3>POST API Error:</h3><pre>' + JSON.stringify(postResult, null, 2) + '</pre>');
                    }
                } else {
                    showStatus('❌ API GET test failed', 'error');
                    addResult('<h3>GET API Error:</h3><pre>' + JSON.stringify(getResult, null, 2) + '</pre>');
                }
            } catch (error) {
                showStatus('❌ API Error: ' + error.message, 'error');
            }
        }
        
        function testChat() {
            showStatus('Opening chat test page...', 'info');
            window.open('test-product-chat.html', '_blank');
        }
    </script>
    
    <h2>📋 Manual Steps:</h2>
    <ol>
        <li>Click "Setup Database" button above</li>
        <li>Click "Test API" button to verify it works</li>
        <li>Go to your cart page: <a href="http://localhost:5173/cart" target="_blank">http://localhost:5173/cart</a></li>
        <li>Add items to cart and click the "💬 Chat" button</li>
    </ol>
    
    <h2>🔧 Troubleshooting:</h2>
    <ul>
        <li>If database setup fails, check your database connection in <code>backend/config/database.php</code></li>
        <li>If API test fails, check the browser console for errors</li>
        <li>If chat doesn't appear, make sure you have items in your cart</li>
    </ul>
</body>
</html>