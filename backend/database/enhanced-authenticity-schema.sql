-- Enhanced Image Authenticity System Schema
-- Supports multi-layered evaluation, category-based comparison, and strict thresholds

USE my_little_thingz;

-- Update image_authenticity_metadata table with enhanced fields
ALTER TABLE `image_authenticity_metadata` 
ADD COLUMN IF NOT EXISTS `tutorial_category` VARCHAR(50) DEFAULT 'general' AFTER `image_type`,
ADD COLUMN IF NOT EXISTS `perceptual_hash` JSON DEFAULT NULL AFTER `image_hash`,
ADD COLUMN IF NOT EXISTS `image_properties` JSON DEFAULT NULL AFTER `perceptual_hash`,
ADD COLUMN IF NOT EXISTS `flagged_reasons` JSON DEFAULT NULL AFTER `similarity_matches`,
ADD COLUMN IF NOT EXISTS `evaluation_details` JSON DEFAULT NULL AFTER `flagged_reasons`,
ADD COLUMN IF NOT EXISTS `requires_admin_review` TINYINT(1) DEFAULT 0 AFTER `evaluation_details`,
ADD COLUMN IF NOT EXISTS `confidence_level` ENUM('low', 'medium', 'high') DEFAULT 'medium' AFTER `requires_admin_review`,
ADD COLUMN IF NOT EXISTS `similarity_context` JSON DEFAULT NULL AFTER `confidence_level`,
ADD COLUMN IF NOT EXISTS `multi_hash_results` JSON DEFAULT NULL AFTER `similarity_context`,
ADD COLUMN IF NOT EXISTS `category_comparison_count` INT DEFAULT 0 AFTER `multi_hash_results`,
ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Add indexes for enhanced performance
ALTER TABLE `image_authenticity_metadata`
ADD INDEX `idx_tutorial_category` (`tutorial_category`),
ADD INDEX `idx_requires_admin_review` (`requires_admin_review`),
ADD INDEX `idx_confidence_level` (`confidence_level`),
ADD INDEX `idx_category_risk` (`tutorial_category`, `risk_level`),
ADD INDEX `idx_category_created` (`tutorial_category`, `created_at`);

-- Enhanced admin review queue with detailed evaluation context
ALTER TABLE `admin_review_queue`
ADD COLUMN IF NOT EXISTS `tutorial_category` VARCHAR(50) DEFAULT 'general' AFTER `tutorial_id`,
ADD COLUMN IF NOT EXISTS `evaluation_details` JSON DEFAULT NULL AFTER `flagged_reasons`,
ADD COLUMN IF NOT EXISTS `similarity_matches` JSON DEFAULT NULL AFTER `evaluation_details`,
ADD COLUMN IF NOT EXISTS `confidence_level` ENUM('low', 'medium', 'high') DEFAULT 'medium' AFTER `similarity_matches`,
ADD COLUMN IF NOT EXISTS `auto_flagged` TINYINT(1) DEFAULT 1 AFTER `confidence_level`,
ADD COLUMN IF NOT EXISTS `priority_level` ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium' AFTER `auto_flagged`;

-- Create tutorial categories mapping table
CREATE TABLE IF NOT EXISTS `tutorial_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tutorial_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `keywords` JSON DEFAULT NULL,
  `confidence` decimal(3,2) DEFAULT 0.80,
  `manually_set` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tutorial_category` (`tutorial_id`),
  KEY `idx_category` (`category`),
  KEY `idx_confidence` (`confidence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create similarity comparison results table for detailed tracking
CREATE TABLE IF NOT EXISTS `similarity_comparison_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source_image_id` varchar(255) NOT NULL,
  `target_image_id` varchar(255) NOT NULL,
  `source_image_type` enum('practice_upload', 'custom_request') NOT NULL,
  `target_image_type` enum('practice_upload', 'custom_request') NOT NULL,
  `tutorial_category` varchar(50) NOT NULL,
  `similarity_methods` JSON NOT NULL,
  `max_similarity_score` decimal(5,4) NOT NULL,
  `similarity_threshold_met` enum('none', 'moderate', 'high', 'very_high') NOT NULL,
  `flagged_as_suspicious` tinyint(1) DEFAULT 0,
  `comparison_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_source_image` (`source_image_id`, `source_image_type`),
  KEY `idx_target_image` (`target_image_id`, `target_image_type`),
  KEY `idx_category_similarity` (`tutorial_category`, `max_similarity_score`),
  KEY `idx_flagged_suspicious` (`flagged_as_suspicious`),
  KEY `idx_threshold_met` (`similarity_threshold_met`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create authenticity evaluation rules table for configuration
CREATE TABLE IF NOT EXISTS `authenticity_evaluation_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_name` varchar(100) NOT NULL,
  `rule_type` enum('similarity', 'metadata', 'image_properties', 'combined') NOT NULL,
  `severity` enum('low', 'moderate', 'high', 'critical') NOT NULL,
  `suspicion_points` int(11) NOT NULL DEFAULT 0,
  `threshold_values` JSON DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_rule_name` (`rule_name`),
  KEY `idx_rule_type` (`rule_type`),
  KEY `idx_severity` (`severity`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default evaluation rules
INSERT INTO `authenticity_evaluation_rules` (`rule_name`, `rule_type`, `severity`, `suspicion_points`, `threshold_values`, `description`) VALUES
('very_high_similarity_same_category', 'similarity', 'critical', 40, '{"similarity_threshold": 0.95, "category_match_required": true}', 'Very high similarity (95%+) detected in same tutorial category'),
('suspicious_metadata_patterns', 'metadata', 'moderate', 20, '{"metadata_score_threshold": 5}', 'Multiple suspicious metadata patterns detected'),
('missing_camera_with_editing', 'combined', 'moderate', 15, '{"requires_missing_camera": true, "requires_editing_software": true}', 'No camera data but editing software signatures found'),
('unusual_image_dimensions', 'image_properties', 'low', 5, '{"aspect_ratio_min": 0.1, "aspect_ratio_max": 10, "min_width": 100, "min_height": 100}', 'Unusual image dimensions detected'),
('multiple_editing_software', 'metadata', 'moderate', 10, '{"min_software_count": 2}', 'Multiple editing software signatures detected'),
('high_similarity_same_category', 'similarity', 'moderate', 25, '{"similarity_threshold": 0.85, "category_match_required": true}', 'High similarity (85%+) detected in same tutorial category'),
('missing_exif_data', 'metadata', 'low', 8, '{"requires_missing_exif": true}', 'No EXIF data found in image'),
('timestamp_inconsistency', 'metadata', 'moderate', 12, '{"max_timestamp_diff_hours": 24}', 'Inconsistent timestamp patterns detected');

-- Create authenticity statistics table for monitoring
CREATE TABLE IF NOT EXISTS `authenticity_statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `tutorial_category` varchar(50) NOT NULL,
  `total_images_processed` int(11) DEFAULT 0,
  `clean_images` int(11) DEFAULT 0,
  `suspicious_images` int(11) DEFAULT 0,
  `highly_suspicious_images` int(11) DEFAULT 0,
  `admin_review_required` int(11) DEFAULT 0,
  `false_positive_reports` int(11) DEFAULT 0,
  `similarity_matches_found` int(11) DEFAULT 0,
  `avg_authenticity_score` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_date_category` (`date`, `tutorial_category`),
  KEY `idx_date` (`date`),
  KEY `idx_category` (`tutorial_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create admin review decisions tracking
CREATE TABLE IF NOT EXISTS `admin_review_decisions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_id` varchar(255) NOT NULL,
  `image_type` enum('practice_upload', 'custom_request') NOT NULL,
  `original_risk_level` enum('clean', 'suspicious', 'highly_suspicious') NOT NULL,
  `admin_decision` enum('approved', 'rejected', 'request_reupload', 'false_positive') NOT NULL,
  `admin_feedback` text DEFAULT NULL,
  `decision_reasoning` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `review_time_seconds` int(11) DEFAULT NULL,
  `was_correctly_flagged` tinyint(1) DEFAULT NULL,
  `reviewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_image_review` (`image_id`, `image_type`),
  KEY `idx_admin_decision` (`admin_decision`),
  KEY `idx_original_risk` (`original_risk_level`),
  KEY `idx_reviewed_by` (`reviewed_by`),
  KEY `idx_correctly_flagged` (`was_correctly_flagged`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update existing tables to ensure compatibility
ALTER TABLE `practice_uploads` 
ADD COLUMN IF NOT EXISTS `authenticity_verified` TINYINT(1) DEFAULT 0 AFTER `status`,
ADD COLUMN IF NOT EXISTS `authenticity_score` DECIMAL(5,2) DEFAULT NULL AFTER `authenticity_verified`,
ADD COLUMN IF NOT EXISTS `requires_manual_review` TINYINT(1) DEFAULT 0 AFTER `authenticity_score`;

-- Add triggers to automatically categorize tutorials
DELIMITER //

CREATE TRIGGER IF NOT EXISTS `auto_categorize_tutorial` 
AFTER INSERT ON `tutorials` 
FOR EACH ROW
BEGIN
    DECLARE detected_category VARCHAR(50) DEFAULT 'general';
    DECLARE search_text TEXT;
    
    SET search_text = LOWER(CONCAT(
        COALESCE(NEW.title, ''), ' ',
        COALESCE(NEW.description, ''), ' ',
        COALESCE(NEW.category, ''), ' ',
        COALESCE(NEW.tags, '')
    ));
    
    -- Simple category detection logic
    IF search_text REGEXP '(embroidery|borduur|stitch|needle)' THEN
        SET detected_category = 'embroidery';
    ELSEIF search_text REGEXP '(paint|canvas|brush|acrylic|watercolor)' THEN
        SET detected_category = 'painting';
    ELSEIF search_text REGEXP '(draw|sketch|pencil|charcoal)' THEN
        SET detected_category = 'drawing';
    ELSEIF search_text REGEXP '(craft|diy|handmade|creative)' THEN
        SET detected_category = 'crafts';
    ELSEIF search_text REGEXP '(jewelry|beads|wire|pendant)' THEN
        SET detected_category = 'jewelry';
    ELSEIF search_text REGEXP '(pottery|clay|ceramic|wheel)' THEN
        SET detected_category = 'pottery';
    ELSEIF search_text REGEXP '(wood|carving|furniture|timber)' THEN
        SET detected_category = 'woodwork';
    ELSEIF search_text REGEXP '(fabric|sewing|quilting|weaving)' THEN
        SET detected_category = 'textile';
    ELSEIF search_text REGEXP '(photo|camera|lens|portrait)' THEN
        SET detected_category = 'photography';
    ELSEIF search_text REGEXP '(digital|photoshop|illustrator|graphic)' THEN
        SET detected_category = 'digital_art';
    END IF;
    
    INSERT INTO `tutorial_categories` (`tutorial_id`, `category`, `confidence`, `manually_set`)
    VALUES (NEW.id, detected_category, 0.80, 0)
    ON DUPLICATE KEY UPDATE 
        `category` = detected_category,
        `confidence` = 0.80,
        `updated_at` = CURRENT_TIMESTAMP;
END//

DELIMITER ;

-- Create indexes for optimal performance
CREATE INDEX IF NOT EXISTS `idx_authenticity_metadata_composite` ON `image_authenticity_metadata` 
(`tutorial_category`, `risk_level`, `requires_admin_review`, `created_at`);

CREATE INDEX IF NOT EXISTS `idx_similarity_results_composite` ON `similarity_comparison_results` 
(`tutorial_category`, `similarity_threshold_met`, `flagged_as_suspicious`);

-- Insert sample configuration data
INSERT IGNORE INTO `authenticity_evaluation_rules` (`rule_name`, `rule_type`, `severity`, `suspicion_points`, `threshold_values`, `description`) VALUES
('category_mismatch_high_similarity', 'combined', 'high', 30, '{"similarity_threshold": 0.80, "different_category_penalty": true}', 'High similarity detected across different tutorial categories'),
('professional_camera_missing_basic_exif', 'metadata', 'moderate', 18, '{"requires_missing_basic_exif": true, "file_size_threshold": 5000000}', 'Large file size but missing basic EXIF data'),
('batch_upload_similarity', 'similarity', 'high', 35, '{"batch_similarity_threshold": 0.75, "min_batch_size": 3}', 'Multiple similar images uploaded in batch');

COMMIT;