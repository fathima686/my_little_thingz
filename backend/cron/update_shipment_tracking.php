<?php
/**
 * Cron Job: Update Shipment Tracking Status
 * 
 * This script should be run periodically (every 1-2 hours) to automatically
 * update order status from Shiprocket tracking data.
 * 
 * Setup Instructions:
 * 
 * Windows Task Scheduler:
 * 1. Open Task Scheduler
 * 2. Create Basic Task
 * 3. Name: "Update Shipment Tracking"
 * 4. Trigger: Daily, repeat every 2 hours
 * 5. Action: Start a program
 * 6. Program: c:\xampp\php\php.exe
 * 7. Arguments: "c:\xampp\htdocs\my_little_thingz\backend\cron\update_shipment_tracking.php"
 * 
 * Linux Cron:
 * Add to crontab: 0 */2 * * * /usr/bin/php /path/to/backend/cron/update_shipment_tracking.php
 */

// Prevent direct browser access (optional)
if (php_sapi_name() !== 'cli' && !isset($_GET['manual_run'])) {
    die('This script should be run from command line or with ?manual_run parameter');
}

require_once __DIR__ . '/../services/ShiprocketAutomation.php';

echo "=== Shipment Tracking Update Cron Job ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $automation = new ShiprocketAutomation();
    
    echo "Fetching pending shipments...\n";
    $result = $automation->updateAllPendingShipments();
    
    if ($result['status'] === 'success') {
        echo "\n✅ SUCCESS!\n";
        echo "Total orders checked: {$result['total']}\n";
        echo "Successfully updated: {$result['updated']}\n";
        echo "Errors: {$result['errors']}\n";
    } else {
        echo "\n❌ ERROR!\n";
        echo "Message: {$result['message']}\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ EXCEPTION!\n";
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nCompleted at: " . date('Y-m-d H:i:s') . "\n";
echo "==========================================\n";