<?php
require_once 'backend/config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Mark order ID 66 as delivered
    $orderId = 66;
    
    echo "🚚 Marking Order ID {$orderId} as Delivered for Unboxing Test\n\n";
    
    // Get order details first
    $orderStmt = $conn->prepare("
        SELECT o.id, o.order_number, o.user_id, o.status, o.total_amount,
               CONCAT(u.first_name, ' ', u.last_name) as customer_name, u.email as customer_email
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo "❌ Order ID {$orderId} not found!\n";
        exit;
    }
    
    echo "Order Details:\n";
    echo "- Order Number: {$order['order_number']}\n";
    echo "- Customer: {$order['customer_name']} ({$order['customer_email']})\n";
    echo "- Amount: ₹{$order['total_amount']}\n";
    echo "- Current Status: {$order['status']}\n\n";
    
    if ($order['status'] === 'delivered') {
        echo "✅ Order is already delivered! Unboxing feature should be available.\n\n";
    } else {
        // Update the order to delivered status
        $updateStmt = $conn->prepare("
            UPDATE orders 
            SET status = 'delivered', 
                delivered_at = NOW(),
                shipped_at = COALESCE(shipped_at, NOW())
            WHERE id = ?
        ");
        $updateStmt->execute([$orderId]);
        
        echo "✅ SUCCESS! Order ID {$orderId} has been marked as DELIVERED.\n\n";
    }
    
    echo "🎬 HOW TO TEST UNBOXING FEATURE:\n";
    echo "1. Login as: {$order['customer_email']}\n";
    echo "2. Go to Orders/Dashboard page\n";
    echo "3. Find order: {$order['order_number']}\n";
    echo "4. Look for 'Unboxing Video Verification' section\n";
    echo "5. Click 'Report Issue with This Order' button\n";
    echo "6. Upload a test video and submit the request\n";
    echo "7. Login as admin to review the request\n\n";
    
    echo "📹 UNBOXING FEATURE DETAILS:\n";
    echo "- Only available for orders with status = 'delivered'\n";
    echo "- Must be submitted within 48 hours of delivery\n";
    echo "- Supports video formats: MP4, MOV, AVI (Max 100MB)\n";
    echo "- Admin can approve/reject requests in Admin Dashboard\n\n";
    
    echo "🔗 TESTING ENDPOINTS:\n";
    echo "- Customer API: /backend/api/customer/unboxing-requests.php\n";
    echo "- Admin API: /backend/api/admin/unboxing-review.php\n";
    echo "- Frontend: OrderTracking component shows unboxing section\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>