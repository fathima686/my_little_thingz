<?php
require_once 'backend/config/database.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // First, let's see what orders we have
    $stmt = $conn->prepare("
        SELECT o.id, o.order_number, o.user_id, o.status, o.payment_status, o.total_amount, o.created_at,
               CONCAT(u.first_name, ' ', u.last_name) as customer_name, u.email as customer_email
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h1>🚚 Mark Order as Delivered for Unboxing Test</h1>";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .status { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status.delivered { background: #d4edda; color: #155724; }
        .status.pending { background: #fff3cd; color: #856404; }
        .status.processing { background: #d1ecf1; color: #0c5460; }
        .status.shipped { background: #cce5ff; color: #004085; }
        .btn { padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #0056b3; }
        .btn.success { background: #28a745; }
        .btn.success:hover { background: #1e7e34; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 4px; }
        .alert.success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert.info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
    </style>";
    
    echo "<div class='container'>";
    
    // Check if we need to mark an order as delivered
    if (isset($_GET['mark_delivered']) && isset($_GET['order_id'])) {
        $orderId = (int)$_GET['order_id'];
        
        // Update the order to delivered status
        $updateStmt = $conn->prepare("
            UPDATE orders 
            SET status = 'delivered', 
                delivered_at = NOW(),
                shipped_at = COALESCE(shipped_at, NOW())
            WHERE id = ? AND status != 'delivered'
        ");
        $updateStmt->execute([$orderId]);
        
        if ($updateStmt->rowCount() > 0) {
            echo "<div class='alert success'>
                ✅ <strong>SUCCESS!</strong> Order ID {$orderId} has been marked as DELIVERED.<br>
                The unboxing feature should now be available for this order.
            </div>";
        } else {
            echo "<div class='alert info'>
                ℹ️ Order ID {$orderId} was already delivered or not found.
            </div>";
        }
        
        // Refresh the orders list
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo "<h2>📦 Current Orders Status</h2>";
    echo "<p>Click 'Mark as Delivered' for any non-delivered order to test the unboxing feature:</p>";
    
    echo "<table>";
    echo "<tr>
        <th>Order ID</th>
        <th>Order Number</th>
        <th>Customer</th>
        <th>Amount</th>
        <th>Status</th>
        <th>Payment</th>
        <th>Created</th>
        <th>Action</th>
    </tr>";
    
    foreach ($orders as $order) {
        $statusClass = strtolower($order['status']);
        $paymentClass = strtolower($order['payment_status']);
        
        echo "<tr>";
        echo "<td>{$order['id']}</td>";
        echo "<td>{$order['order_number']}</td>";
        echo "<td>{$order['customer_name']} ({$order['customer_email']})</td>";
        echo "<td>₹{$order['total_amount']}</td>";
        echo "<td><span class='status {$statusClass}'>{$order['status']}</span></td>";
        echo "<td><span class='status {$paymentClass}'>{$order['payment_status']}</span></td>";
        echo "<td>" . date('M j, Y H:i', strtotime($order['created_at'])) . "</td>";
        echo "<td>";
        
        if ($order['status'] !== 'delivered') {
            echo "<a href='?mark_delivered=1&order_id={$order['id']}' class='btn success' onclick='return confirm(\"Mark order {$order['order_number']} as delivered?\")'>Mark as Delivered</a>";
        } else {
            echo "<span style='color: #28a745; font-weight: bold;'>✅ Already Delivered</span>";
        }
        
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Show unboxing test instructions
    echo "<div class='alert info'>
        <h3>🎬 How to Test Unboxing Feature:</h3>
        <ol>
            <li><strong>Mark an order as delivered</strong> using the button above</li>
            <li><strong>Login as the customer</strong> who placed that order</li>
            <li><strong>Go to Orders/Dashboard</strong> and look for the delivered order</li>
            <li><strong>Look for 'Upload Unboxing Video'</strong> button or section</li>
            <li><strong>Test the unboxing upload</strong> functionality</li>
        </ol>
        <p><strong>Note:</strong> The unboxing feature should only appear for orders with status = 'delivered'</p>
    </div>";
    
    // Show delivered orders that can be used for unboxing testing
    $deliveredStmt = $conn->prepare("
        SELECT o.id, o.order_number, o.user_id, o.total_amount, o.delivered_at,
               CONCAT(u.first_name, ' ', u.last_name) as customer_name, u.email as customer_email
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.status = 'delivered'
        ORDER BY o.delivered_at DESC
    ");
    $deliveredStmt->execute();
    $deliveredOrders = $deliveredStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($deliveredOrders)) {
        echo "<h2>📹 Orders Ready for Unboxing Testing</h2>";
        echo "<p>These orders are marked as delivered and should show the unboxing feature:</p>";
        
        echo "<table>";
        echo "<tr>
            <th>Order ID</th>
            <th>Order Number</th>
            <th>Customer</th>
            <th>Amount</th>
            <th>Delivered At</th>
            <th>Test Instructions</th>
        </tr>";
        
        foreach ($deliveredOrders as $order) {
            echo "<tr>";
            echo "<td>{$order['id']}</td>";
            echo "<td>{$order['order_number']}</td>";
            echo "<td>{$order['customer_name']} ({$order['customer_email']})</td>";
            echo "<td>₹{$order['total_amount']}</td>";
            echo "<td>" . date('M j, Y H:i', strtotime($order['delivered_at'])) . "</td>";
            echo "<td>Login as <strong>{$order['customer_email']}</strong> to test unboxing</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>