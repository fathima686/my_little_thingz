-- Simplified Image Authenticity System Schema
-- Focuses on essential data only, removes complex scoring and unused fields

USE my_little_thingz;

-- Drop existing complex tables if they exist
DROP TABLE IF EXISTS `similarity_comparison_results`;
DROP TABLE IF EXISTS `authenticity_evaluation_rules`;
DROP TABLE IF EXISTS `authenticity_statistics`;
DROP TABLE IF EXISTS `admin_review_decisions`;
DROP TABLE IF EXISTS `tutorial_categories`;

-- Create simplified image authenticity table
CREATE TABLE IF NOT EXISTS `image_authenticity_simple` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_id` varchar(255) NOT NULL,
  `image_type` enum('practice_upload', 'custom_request') NOT NULL,
  `user_id` int(11) NOT NULL,
  `tutorial_id` int(11) DEFAULT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `phash` text DEFAULT NULL,
  `evaluation_status` enum('unique', 'reused', 'highly_similar', 'needs_admin_review') NOT NULL DEFAULT 'unique',
  `admin_decision` enum('pending', 'approved', 'rejected', 'false_positive') DEFAULT 'pending',
  `requires_review` tinyint(1) DEFAULT 0,
  `flagged_reason` text DEFAULT NULL,
  `metadata_notes` text DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create simplified admin review queue
CREATE TABLE IF NOT EXISTS `admin_review_simple` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_id` varchar(255) NOT NULL,
  `image_type` enum('practice_upload', 'custom_request') NOT NULL,
  `user_id` int(11) NOT NULL,
  `tutorial_id` int(11) DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `evaluation_status` enum('reused', 'highly_similar', 'needs_admin_review') NOT NULL,
  `flagged_reason` text NOT NULL,
  `similar_image_info` json DEFAULT NULL,
  `admin_decision` enum('pending', 'approved', 'rejected', 'false_positive') DEFAULT 'pending',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update practice_uploads table to work with simplified system
ALTER TABLE `practice_uploads` 
ADD COLUMN IF NOT EXISTS `authenticity_status` enum('pending', 'verified', 'flagged', 'approved') DEFAULT 'pending' AFTER `status`,
ADD COLUMN IF NOT EXISTS `progress_approved` tinyint(1) DEFAULT 0 AFTER `authenticity_status`;

-- Update learning_progress to track admin approval
ALTER TABLE `learning_progress`
ADD COLUMN IF NOT EXISTS `practice_admin_approved` tinyint(1) DEFAULT 0 AFTER `practice_uploaded`;

-- Create index for efficient pHash similarity searches
CREATE INDEX IF NOT EXISTS `idx_phash_similarity` ON `image_authenticity_simple` (`category`, `phash`(64));

-- Insert sample categories for tutorials (simplified)
UPDATE tutorials SET category = 'embroidery' WHERE LOWER(title) LIKE '%embroidery%' OR LOWER(title) LIKE '%stitch%';
UPDATE tutorials SET category = 'painting' WHERE LOWER(title) LIKE '%paint%' OR LOWER(title) LIKE '%canvas%';
UPDATE tutorials SET category = 'drawing' WHERE LOWER(title) LIKE '%draw%' OR LOWER(title) LIKE '%sketch%';
UPDATE tutorials SET category = 'crafts' WHERE LOWER(title) LIKE '%craft%' OR LOWER(title) LIKE '%diy%';
UPDATE tutorials SET category = 'jewelry' WHERE LOWER(title) LIKE '%jewelry%' OR LOWER(title) LIKE '%bead%';
UPDATE tutorials SET category = 'pottery' WHERE LOWER(title) LIKE '%pottery%' OR LOWER(title) LIKE '%clay%';
UPDATE tutorials SET category = 'woodwork' WHERE LOWER(title) LIKE '%wood%' OR LOWER(title) LIKE '%carving%';
UPDATE tutorials SET category = 'textile' WHERE LOWER(title) LIKE '%fabric%' OR LOWER(title) LIKE '%sewing%';
UPDATE tutorials SET category = 'photography' WHERE LOWER(title) LIKE '%photo%' OR LOWER(title) LIKE '%camera%';
UPDATE tutorials SET category = 'digital_art' WHERE LOWER(title) LIKE '%digital%' OR LOWER(title) LIKE '%graphic%';

-- Set default category for uncategorized tutorials
UPDATE tutorials SET category = 'general' WHERE category IS NULL OR category = '';

-- Create trigger to update practice progress only when admin approves
DELIMITER //

CREATE TRIGGER IF NOT EXISTS `update_progress_on_approval` 
AFTER UPDATE ON `image_authenticity_simple` 
FOR EACH ROW
BEGIN
    -- Only update progress when admin approves practice images
    IF NEW.admin_decision = 'approved' AND OLD.admin_decision != 'approved' AND NEW.image_type = 'practice_upload' THEN
        -- Extract practice upload ID from image_id (assuming format like "uploadId_imageIndex")
        SET @upload_id = CAST(SUBSTRING_INDEX(NEW.image_id, '_', 1) AS UNSIGNED);
        
        -- Update practice upload status
        UPDATE practice_uploads 
        SET authenticity_status = 'approved', progress_approved = 1
        WHERE id = @upload_id;
        
        -- Update learning progress only when practice is approved
        UPDATE learning_progress 
        SET practice_admin_approved = 1, practice_completed = 1
        WHERE user_id = NEW.user_id AND tutorial_id = NEW.tutorial_id;
    END IF;
END//

DELIMITER ;

-- Clean up old complex data (optional - uncomment if you want to remove old system)
-- DROP TABLE IF EXISTS `image_authenticity_metadata`;
-- DROP TABLE IF EXISTS `admin_review_queue`;
-- DROP TABLE IF EXISTS `image_verification_queue`;
-- DROP TABLE IF EXISTS `authenticity_audit_log`;

COMMIT;