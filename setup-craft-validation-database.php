<?php
/**
 * Setup Craft Validation Database Tables
 * Creates all required tables for the craft validation system
 */

echo "🗄️ Setting up Craft Validation Database\n";
echo "========================================\n\n";

try {
    require_once 'backend/config/database.php';
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "✅ Database connection successful\n\n";
    
    // Create image_authenticity_v2 table
    echo "Creating image_authenticity_v2 table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `image_authenticity_v2` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `image_id` varchar(255) NOT NULL,
          `image_type` enum('practice_upload', 'custom_request') NOT NULL,
          `user_id` int(11) NOT NULL,
          `tutorial_id` int(11) DEFAULT NULL,
          `category` varchar(50) NOT NULL DEFAULT 'general',
          `phash` text DEFAULT NULL,
          `evaluation_status` enum('unique', 'possible_reuse', 'possibly_unrelated', 'needs_admin_review') NOT NULL DEFAULT 'unique',
          `admin_decision` enum('pending', 'approved', 'rejected') DEFAULT 'pending',
          `requires_review` tinyint(1) DEFAULT 0,
          `flagged_reason` text DEFAULT NULL,
          `metadata_notes` text DEFAULT NULL,
          `ai_labels` json DEFAULT NULL,
          `ai_warning` text DEFAULT NULL,
          `admin_notes` text DEFAULT NULL,
          `reviewed_by` int(11) DEFAULT NULL,
          `reviewed_at` timestamp NULL DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_image` (`image_id`, `image_type`),
          KEY `idx_category` (`category`),
          KEY `idx_evaluation_status` (`evaluation_status`),
          KEY `idx_requires_review` (`requires_review`),
          KEY `idx_admin_decision` (`admin_decision`),
          KEY `idx_user_tutorial` (`user_id`, `tutorial_id`),
          KEY `idx_category_phash` (`category`, `phash`(50))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ image_authenticity_v2 table created\n";
    
    // Create admin_review_v2 table
    echo "Creating admin_review_v2 table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admin_review_v2` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `image_id` varchar(255) NOT NULL,
          `image_type` enum('practice_upload', 'custom_request') NOT NULL,
          `user_id` int(11) NOT NULL,
          `tutorial_id` int(11) DEFAULT NULL,
          `category` varchar(50) NOT NULL,
          `evaluation_status` enum('possible_reuse', 'possibly_unrelated', 'needs_admin_review') NOT NULL,
          `flagged_reason` text NOT NULL,
          `similar_image_info` json DEFAULT NULL,
          `ai_warning` text DEFAULT NULL,
          `admin_decision` enum('pending', 'approved', 'rejected') DEFAULT 'pending',
          `admin_notes` text DEFAULT NULL,
          `reviewed_by` int(11) DEFAULT NULL,
          `reviewed_at` timestamp NULL DEFAULT NULL,
          `flagged_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_review` (`image_id`, `image_type`),
          KEY `idx_admin_decision` (`admin_decision`),
          KEY `idx_category` (`category`),
          KEY `idx_evaluation_status` (`evaluation_status`),
          KEY `idx_flagged_at` (`flagged_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ admin_review_v2 table created\n";
    
    // Create craft_image_validation table
    echo "Creating craft_image_validation table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `craft_image_validation` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `image_id` varchar(255) NOT NULL,
          `image_type` enum('practice_upload', 'custom_request') NOT NULL,
          `user_id` int(11) NOT NULL,
          `tutorial_id` int(11) DEFAULT NULL,
          `predicted_category` varchar(50) DEFAULT NULL,
          `prediction_confidence` decimal(5,4) DEFAULT 0.0000,
          `category_matches` tinyint(1) DEFAULT 0,
          `ai_generated_detected` tinyint(1) DEFAULT 0,
          `ai_generator` varchar(50) DEFAULT NULL,
          `ai_confidence` enum('unknown', 'suspicious', 'high') DEFAULT 'unknown',
          `validation_status` enum('approved', 'flagged', 'rejected') DEFAULT 'approved',
          `rejection_reason` text DEFAULT NULL,
          `flag_reason` text DEFAULT NULL,
          `all_predictions` json DEFAULT NULL,
          `ai_evidence` json DEFAULT NULL,
          `metadata_analysis` json DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_image_validation` (`image_id`, `image_type`),
          KEY `idx_validation_status` (`validation_status`),
          KEY `idx_predicted_category` (`predicted_category`),
          KEY `idx_ai_generated` (`ai_generated_detected`),
          KEY `idx_user_tutorial` (`user_id`, `tutorial_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ craft_image_validation table created\n";
    
    // Create admin_actions_log table
    echo "Creating admin_actions_log table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admin_actions_log` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `admin_id` int(11) NOT NULL,
          `action_type` varchar(50) NOT NULL,
          `target_type` varchar(50) NOT NULL,
          `target_id` varchar(100) NOT NULL,
          `details` json DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `idx_admin_action` (`admin_id`, `action_type`),
          KEY `idx_target` (`target_type`, `target_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ admin_actions_log table created\n";
    
    // Update practice_uploads table to include craft validation columns
    echo "Updating practice_uploads table...\n";
    
    // Add authenticity_status column first
    $pdo->exec("
        ALTER TABLE `practice_uploads` 
        ADD COLUMN IF NOT EXISTS `authenticity_status` enum('pending', 'verified', 'flagged', 'approved') DEFAULT 'pending' AFTER `status`
    ");
    
    // Add craft_validation_status column
    $pdo->exec("
        ALTER TABLE `practice_uploads` 
        ADD COLUMN IF NOT EXISTS `craft_validation_status` enum('pending', 'approved', 'flagged', 'rejected') DEFAULT 'pending' AFTER `authenticity_status`
    ");
    
    // Add progress_approved column
    $pdo->exec("
        ALTER TABLE `practice_uploads` 
        ADD COLUMN IF NOT EXISTS `progress_approved` tinyint(1) DEFAULT 0 AFTER `craft_validation_status`
    ");
    
    echo "✅ practice_uploads table updated\n";
    
    // Create notifications table if it doesn't exist
    echo "Creating notifications table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `notifications` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `title` varchar(255) NOT NULL,
          `message` text NOT NULL,
          `type` enum('info', 'success', 'warning', 'error') DEFAULT 'info',
          `is_read` tinyint(1) DEFAULT 0,
          `action_url` varchar(255) DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `read_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_user_id` (`user_id`),
          KEY `idx_is_read` (`is_read`),
          KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ notifications table created\n";
    
    echo "\n🎉 Database setup complete!\n";
    echo "\nAll required tables have been created:\n";
    echo "- image_authenticity_v2 (authenticity validation)\n";
    echo "- admin_review_v2 (admin review queue)\n";
    echo "- craft_image_validation (craft-specific validation)\n";
    echo "- admin_actions_log (audit trail)\n";
    echo "- notifications (user notifications)\n";
    echo "- practice_uploads (updated with craft validation status)\n";
    
} catch (Exception $e) {
    echo "❌ Database setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>