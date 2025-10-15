<?php
/**
 * Test Script: Update Tracking Status
 * 
 * Tests the automatic tracking update functionality
 * Run this to manually update tracking status for all pending shipments
 */

require_once __DIR__ . '/services/ShiprocketAutomation.php';

echo "=== Test Tracking Status Update ===\n\n";

try {
    $automation = new ShiprocketAutomation();
    
    echo "Updating all pending shipments...\n\n";
    $result = $automation->updateAllPendingShipments();
    
    if ($result['status'] === 'success') {
        echo "✅ SUCCESS!\n\n";
        echo "Results:\n";
        echo "  Total orders checked: {$result['total']}\n";
        echo "  Successfully updated: {$result['updated']}\n";
        echo "  Errors: {$result['errors']}\n\n";
        
        if ($result['updated'] > 0) {
            echo "Check the logs for details:\n";
            echo "  backend/logs/shiprocket_automation.log\n\n";
            
            echo "Check database to see updated statuses:\n";
            echo "  php backend/check_orders.php\n";
        }
    } else {
        echo "❌ ERROR!\n";
        echo "Message: {$result['message']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPTION!\n";
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";