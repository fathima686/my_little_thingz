<?php
/**
 * Reset Orders to Processing Status
 * 
 * This script resets all paid orders back to 'processing' status
 * Useful for re-running demos multiple times
 */

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Reset Orders to Processing ===\n\n";
    
    // Reset all paid orders to processing
    $resetQuery = "UPDATE orders 
                   SET status = 'processing',
                       shipped_at = NULL,
                       delivered_at = NULL,
                       tracking_number = NULL,
                       estimated_delivery = NULL
                   WHERE payment_status = 'paid'
                   AND status IN ('shipped', 'delivered')";
    
    $stmt = $db->prepare($resetQuery);
    $stmt->execute();
    $resetCount = $stmt->rowCount();
    
    echo "✓ Reset $resetCount orders back to PROCESSING status\n\n";
    
    // Show current status
    $summaryQuery = "SELECT 
                        status,
                        COUNT(*) as count
                     FROM orders
                     WHERE payment_status = 'paid'
                     GROUP BY status";
    
    $stmt = $db->prepare($summaryQuery);
    $stmt->execute();
    $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current Order Status Distribution:\n";
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
    
    echo "\n=== Reset Complete! ===\n";
    echo "All orders are now ready for a fresh demo run.\n\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>