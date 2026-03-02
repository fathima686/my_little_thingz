<?php
/**
 * UNBOXING VIDEO VERIFICATION SYSTEM SETUP
 * Academic Project - Database Setup Script
 * 
 * Run this script to set up the database tables for the unboxing verification feature
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Setting up Unboxing Video Verification System...\n\n";
    
    // Read and execute the schema
    $schema = file_get_contents('database/unboxing_verification_schema.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !str_starts_with(trim($statement), '--')) {
            try {
                $pdo->exec($statement);
                echo "✓ Executed: " . substr(trim($statement), 0, 50) . "...\n";
            } catch (Exception $e) {
                echo "⚠ Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Create upload directory
    $uploadDir = 'uploads/unboxing_videos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "✓ Created upload directory: $uploadDir\n";
    } else {
        echo "✓ Upload directory already exists: $uploadDir\n";
    }
    
    // Set proper permissions
    chmod($uploadDir, 0755);
    echo "✓ Set directory permissions\n";
    
    // Add sample delivered order for testing
    try {
        $pdo->exec("
            UPDATE orders 
            SET status = 'delivered', 
                delivered_at = DATE_SUB(NOW(), INTERVAL 1 HOUR),
                allows_unboxing_request = 1
            WHERE id = 1 AND status != 'delivered'
        ");
        echo "✓ Updated sample order for testing\n";
    } catch (Exception $e) {
        echo "⚠ Note: Could not update sample order (may not exist)\n";
    }
    
    echo "\n=== SETUP COMPLETE ===\n";
    echo "Unboxing Video Verification System is ready!\n\n";
    
    echo "FEATURES ENABLED:\n";
    echo "- Customer can upload unboxing videos for delivered orders\n";
    echo "- 48-hour time window validation\n";
    echo "- One request per order limit\n";
    echo "- Admin review workflow\n";
    echo "- Status tracking and history\n";
    echo "- Secure video file storage\n\n";
    
    echo "NEXT STEPS:\n";
    echo "1. Add UnboxingVideoRequest component to customer order pages\n";
    echo "2. Add UnboxingVideoReview to admin dashboard\n";
    echo "3. Test with a delivered order\n\n";
    
    echo "API ENDPOINTS:\n";
    echo "- Customer: /api/customer/unboxing-requests.php\n";
    echo "- Admin: /api/admin/unboxing-review.php\n\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>