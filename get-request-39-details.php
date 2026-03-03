<?php
/**
 * Get Request 39 Details for Testing
 */

require_once "backend/config/database.php";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h1>📋 Request 39 Details</h1>";
    
    // Get request 39 details
    $stmt = $pdo->prepare("
        SELECT cr.*, crd.id as design_id, crd.status as design_status, crd.design_image_url
        FROM custom_requests cr
        LEFT JOIN custom_request_designs crd ON cr.id = crd.request_id
        WHERE cr.id = 39
    ");
    $stmt->execute();
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        echo "<p style='color: red;'>❌ Request 39 not found</p>";
        exit;
    }
    
    $customerId = $request['customer_id'] ?: $request['user_id'];
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>🎯 Perfect Test Data Found!</h2>";
    echo "<p><strong>Use these values in the debug tool:</strong></p>";
    echo "<ul style='font-size: 18px; font-weight: bold;'>";
    echo "<li>🆔 <strong>Request ID:</strong> <span style='color: #007bff;'>39</span></li>";
    echo "<li>👤 <strong>Customer ID:</strong> <span style='color: #007bff;'>$customerId</span></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>📊 Request Details:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td><strong>ID</strong></td><td>{$request['id']}</td></tr>";
    echo "<tr><td><strong>Title</strong></td><td>{$request['title']}</td></tr>";
    echo "<tr><td><strong>Customer ID</strong></td><td>{$request['customer_id']}</td></tr>";
    echo "<tr><td><strong>User ID</strong></td><td>{$request['user_id']}</td></tr>";
    echo "<tr><td><strong>Price</strong></td><td>₹{$request['price']}</td></tr>";
    echo "<tr><td><strong>Status</strong></td><td>{$request['status']}</td></tr>";
    echo "<tr><td><strong>Workflow Stage</strong></td><td>{$request['workflow_stage']}</td></tr>";
    echo "<tr><td><strong>Design Status</strong></td><td>" . ($request['design_status'] ?: 'None') . "</td></tr>";
    echo "<tr><td><strong>Has Design Image</strong></td><td>" . ($request['design_image_url'] ? '✅ Yes' : '❌ No') . "</td></tr>";
    echo "</table>";
    
    // Check if already in cart
    echo "<h3>🛒 Cart Status Check:</h3>";
    $cartStmt = $pdo->prepare("
        SELECT c.id, c.quantity, c.added_at, a.title, a.price, a.category
        FROM cart c
        JOIN artworks a ON c.artwork_id = a.id
        WHERE c.user_id = ? AND (a.category = 'custom' OR a.description LIKE ?)
    ");
    $cartStmt->execute([$customerId, "%Request #39%"]);
    $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($cartItems)) {
        echo "<p style='color: orange;'>⚠️ No custom designs found in cart for customer $customerId</p>";
        echo "<p>This is perfect for testing - the design should be added automatically when completed!</p>";
    } else {
        echo "<p style='color: green;'>✅ Found " . count($cartItems) . " custom item(s) in cart:</p>";
        foreach ($cartItems as $item) {
            echo "<p>• {$item['title']} - ₹{$item['price']} (Added: {$item['added_at']})</p>";
        }
    }
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🧪 Ready to Test!</h3>";
    echo "<p><strong>Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Open the <a href='debug-custom-design-cart-flow.html' target='_blank'><strong>Debug Tool</strong></a></li>";
    echo "<li>Enter <strong>Request ID: 39</strong></li>";
    echo "<li>Enter <strong>Customer ID: $customerId</strong></li>";
    echo "<li>Click <strong>'Run Full Debug Flow'</strong></li>";
    echo "<li>You should see the design completion work (no more 'Request not found' error)</li>";
    echo "<li>Check if the completed design appears in the cart</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h3>🔗 Quick Links:</h3>";
    echo "<p>";
    echo "<a href='debug-custom-design-cart-flow.html' target='_blank' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px;'>🔍 Debug Tool</a>";
    echo "<a href='test-custom-design-cart-integration.php' target='_blank' style='display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 5px;'>🧪 Integration Test</a>";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error getting request details</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    table { width: 100%; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f8f9fa; font-weight: bold; }
</style>