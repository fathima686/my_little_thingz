<?php
/**
 * Instant Order Status Update for Demo
 * 
 * This script immediately updates ALL paid orders to show the complete lifecycle:
 * - All processing orders → shipped (with tracking info)
 * - All shipped orders → delivered
 * 
 * USE THIS FOR DEMO PURPOSES ONLY!
 */

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== DEMO: Instant Order Status Update ===\n\n";
    
    // ========================================
    // STEP 1: Update ALL PROCESSING → SHIPPED
    // ========================================
    echo "Step 1: Moving all PROCESSING orders to SHIPPED...\n";
    
    $updateProcessing = "UPDATE orders 
                        SET status = 'shipped', 
                            shipped_at = NOW(),
                            estimated_delivery = DATE_ADD(NOW(), INTERVAL 3 DAY),
                            tracking_number = CONCAT('DEMO-', SUBSTRING(MD5(RAND()), 1, 10))
                        WHERE status = 'processing' 
                        AND payment_status = 'paid'";
    
    $stmt = $db->prepare($updateProcessing);
    $stmt->execute();
    $shippedCount = $stmt->rowCount();
    
    echo "  ✓ Moved $shippedCount orders to SHIPPED status\n\n";
    
    // ========================================
    // STEP 2: Update ALL SHIPPED → DELIVERED
    // ========================================
    echo "Step 2: Moving all SHIPPED orders to DELIVERED...\n";
    
    $updateShipped = "UPDATE orders 
                     SET status = 'delivered', 
                         delivered_at = NOW()
                     WHERE status = 'shipped'";
    
    $stmt = $db->prepare($updateShipped);
    $stmt->execute();
    $deliveredCount = $stmt->rowCount();
    
    echo "  ✓ Moved $deliveredCount orders to DELIVERED status\n\n";
    
    // ========================================
    // DISPLAY UPDATED ORDERS
    // ========================================
    echo "=== Updated Orders ===\n\n";
    
    $ordersQuery = "SELECT 
                        o.id,
                        o.order_number,
                        o.status,
                        o.payment_status,
                        o.total_amount,
                        o.created_at,
                        o.shipped_at,
                        o.delivered_at,
                        o.tracking_number,
                        CONCAT(u.first_name, ' ', u.last_name) as customer_name
                    FROM orders o
                    JOIN users u ON o.user_id = u.id
                    WHERE o.payment_status = 'paid'
                    ORDER BY o.created_at DESC
                    LIMIT 20";
    
    $stmt = $db->prepare($ordersQuery);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($orders as $order) {
        $statusEmoji = [
            'pending' => '⏸️',
            'processing' => '⚙️',
            'shipped' => '🚚',
            'delivered' => '✅',
            'cancelled' => '❌'
        ];
        $emoji = $statusEmoji[$order['status']] ?? '📦';
        
        echo "$emoji Order: {$order['order_number']}\n";
        echo "   Customer: {$order['customer_name']}\n";
        echo "   Status: " . strtoupper($order['status']) . "\n";
        echo "   Amount: ₹{$order['total_amount']}\n";
        
        if ($order['tracking_number']) {
            echo "   Tracking: {$order['tracking_number']}\n";
        }
        
        if ($order['shipped_at']) {
            echo "   Shipped: {$order['shipped_at']}\n";
        }
        
        if ($order['delivered_at']) {
            echo "   Delivered: {$order['delivered_at']}\n";
        }
        
        echo "\n";
    }
    
    // ========================================
    // SUMMARY
    // ========================================
    echo "=== Final Summary ===\n";
    
    $summaryQuery = "SELECT 
                        status,
                        COUNT(*) as count,
                        SUM(total_amount) as total_revenue
                     FROM orders
                     WHERE payment_status = 'paid'
                     GROUP BY status";
    
    $stmt = $db->prepare($summaryQuery);
    $stmt->execute();
    $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nOrder Status Distribution:\n";
    $totalOrders = 0;
    $totalRevenue = 0;
    
    foreach ($summary as $row) {
        $statusEmoji = [
            'pending' => '⏸️',
            'processing' => '⚙️',
            'shipped' => '🚚',
            'delivered' => '✅',
            'cancelled' => '❌'
        ];
        $emoji = $statusEmoji[$row['status']] ?? '📦';
        echo "  $emoji " . strtoupper($row['status']) . ": {$row['count']} orders (₹{$row['total_revenue']})\n";
        $totalOrders += $row['count'];
        $totalRevenue += $row['total_revenue'];
    }
    
    echo "\n📊 Total Paid Orders: $totalOrders\n";
    echo "💰 Total Revenue: ₹$totalRevenue\n";
    
    echo "\n=== Demo Update Complete! ===\n";
    echo "All orders have been updated for demonstration.\n";
    echo "Check your website to see the changes!\n\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>