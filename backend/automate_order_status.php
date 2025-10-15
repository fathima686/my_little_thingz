<?php
/**
 * Order Status Automation Script for Demo
 * 
 * This script automatically updates order statuses:
 * - processing → shipped (after 2 minutes)
 * - shipped → delivered (after 5 minutes from shipped)
 * 
 * Run this script periodically (e.g., via cron or manually) to simulate order progression
 */

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Order Status Automation Started ===\n\n";
    
    // Get current timestamp
    $now = new DateTime();
    
    // ========================================
    // STEP 1: Update PROCESSING → SHIPPED
    // ========================================
    echo "Step 1: Checking orders in 'processing' status...\n";
    
    $processingQuery = "SELECT id, order_number, status, created_at, payment_status 
                        FROM orders 
                        WHERE status = 'processing' 
                        AND payment_status = 'paid'
                        ORDER BY created_at ASC";
    
    $stmt = $db->prepare($processingQuery);
    $stmt->execute();
    $processingOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $shippedCount = 0;
    foreach ($processingOrders as $order) {
        $createdAt = new DateTime($order['created_at']);
        $minutesSinceCreation = ($now->getTimestamp() - $createdAt->getTimestamp()) / 60;
        
        // If order is older than 2 minutes, mark as shipped
        if ($minutesSinceCreation >= 2) {
            $updateQuery = "UPDATE orders 
                           SET status = 'shipped', 
                               shipped_at = NOW(),
                               estimated_delivery = DATE_ADD(NOW(), INTERVAL 5 DAY)
                           WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$order['id']]);
            
            echo "  ✓ Order {$order['order_number']} → SHIPPED (was processing for " . round($minutesSinceCreation, 1) . " minutes)\n";
            $shippedCount++;
        } else {
            echo "  ⏳ Order {$order['order_number']} → Still processing (only " . round($minutesSinceCreation, 1) . " minutes old)\n";
        }
    }
    
    echo "\nTotal orders moved to SHIPPED: $shippedCount\n\n";
    
    // ========================================
    // STEP 2: Update SHIPPED → DELIVERED
    // ========================================
    echo "Step 2: Checking orders in 'shipped' status...\n";
    
    $shippedQuery = "SELECT id, order_number, status, shipped_at 
                     FROM orders 
                     WHERE status = 'shipped' 
                     AND shipped_at IS NOT NULL
                     ORDER BY shipped_at ASC";
    
    $stmt = $db->prepare($shippedQuery);
    $stmt->execute();
    $shippedOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $deliveredCount = 0;
    foreach ($shippedOrders as $order) {
        $shippedAt = new DateTime($order['shipped_at']);
        $minutesSinceShipped = ($now->getTimestamp() - $shippedAt->getTimestamp()) / 60;
        
        // If order has been shipped for more than 5 minutes, mark as delivered
        if ($minutesSinceShipped >= 5) {
            $updateQuery = "UPDATE orders 
                           SET status = 'delivered', 
                               delivered_at = NOW()
                           WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$order['id']]);
            
            echo "  ✓ Order {$order['order_number']} → DELIVERED (was shipped for " . round($minutesSinceShipped, 1) . " minutes)\n";
            $deliveredCount++;
        } else {
            echo "  🚚 Order {$order['order_number']} → Still in transit (shipped " . round($minutesSinceShipped, 1) . " minutes ago)\n";
        }
    }
    
    echo "\nTotal orders moved to DELIVERED: $deliveredCount\n\n";
    
    // ========================================
    // SUMMARY
    // ========================================
    echo "=== Summary ===\n";
    
    $summaryQuery = "SELECT 
                        status,
                        COUNT(*) as count
                     FROM orders
                     WHERE payment_status = 'paid'
                     GROUP BY status";
    
    $stmt = $db->prepare($summaryQuery);
    $stmt->execute();
    $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nCurrent Order Status Distribution:\n";
    foreach ($summary as $row) {
        $statusEmoji = [
            'pending' => '⏸️',
            'processing' => '⚙️',
            'shipped' => '🚚',
            'delivered' => '✅',
            'cancelled' => '❌'
        ];
        $emoji = $statusEmoji[$row['status']] ?? '📦';
        echo "  $emoji " . strtoupper($row['status']) . ": {$row['count']} orders\n";
    }
    
    echo "\n=== Automation Complete ===\n";
    echo "Run this script again to continue updating order statuses.\n";
    echo "Tip: Set up a cron job to run this every minute for continuous automation.\n\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>