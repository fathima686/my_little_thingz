<?php
// Cleanup Incomplete Subscriptions
// This script should be run periodically to clean up subscriptions that were created but never paid

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Mark subscriptions as expired if they've been in 'created' status for more than 2 hours
    $expireStmt = $db->prepare("
        UPDATE subscriptions 
        SET status = 'expired', updated_at = NOW() 
        WHERE status = 'created' 
        AND created_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)
    ");
    
    $expiredCount = $expireStmt->execute() ? $expireStmt->rowCount() : 0;
    
    echo "Cleanup completed: $expiredCount incomplete subscriptions marked as expired\n";
    
    // Log the cleanup
    error_log("Subscription cleanup: Expired $expiredCount incomplete subscriptions");
    
} catch (Exception $e) {
    echo "Cleanup error: " . $e->getMessage() . "\n";
    error_log("Subscription cleanup error: " . $e->getMessage());
}
?>