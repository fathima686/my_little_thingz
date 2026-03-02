-- Image Authenticity and Practice Validation System Schema
-- Add these tables to support comprehensive image verification

USE my_little_thingz;

-- Create image_authenticity_metadata table for detailed verification results
CREATE TABLE IF NOT EXISTS `image_authenticity_metadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_id` varchar(255) NOT NULL,
  `image_type` enum('practice_upload', 'custom_request') NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `image_hash` varchar(64) NOT NULL,
  `perceptual_hash` varchar(64) DEFAULT NULL,
  `metadata_extracted` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata_extracted`)),
  `camera_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`camera_info`)),
  `editing_software` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`editing_software`)),
  `authenticity_score` decimal(5,2) DEFAULT 0.00,
  `risk_level` enum('clean', 'suspicious', 'highly_suspicious') DEFAULT 'clean',
  `verification_status` enum('pending', 'verified', 'flagged', 'approved', 'rejected') DEFAULT 'pending',
  `verification_method` varchar(100) DEFAULT 'automated',
  `similarity_matches` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`similarity_matches`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_image_id_type` (`image_id`, `image_type`),
  KEY `idx_image_hash` (`image_hash`),
  KEY `idx_perceptual_hash` (`perceptual_hash`),
  KEY `idx_authenticity_score` (`authenticity_score`),
  KEY `idx_risk_level` (`risk_level`),
  KEY `idx_verification_status` (`verification_status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create image_verification_queue for processing pipeline
CREATE TABLE IF NOT EXISTS `image_verification_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_id` varchar(255) NOT NULL,
  `image_type` enum('practice_upload', 'custom_request') NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tutorial_id` int(11) DEFAULT NULL,
  `priority` enum('low', 'medium', 'high') DEFAULT 'medium',
  `status` enum('queued', 'processing', 'completed', 'failed') DEFAULT 'queued',
  `attempts` int(11) DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `queued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_queued_at` (`queued_at`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_tutorial_id` (`tutorial_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create admin_review_queue for flagged images
CREATE TABLE IF NOT EXISTS `admin_review_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_id` varchar(255) NOT NULL,
  `image_type` enum('practice_upload', 'custom_request') NOT NULL,
  `user_id` int(11) NOT NULL,
  `tutorial_id` int(11) DEFAULT NULL,
  `authenticity_score` decimal(5,2) NOT NULL,
  `risk_level` enum('clean', 'suspicious', 'highly_suspicious') NOT NULL,
  `flagged_reasons` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`flagged_reasons`)),
  `admin_decision` enum('pending', 'approved', 'rejected', 'request_reupload') DEFAULT 'pending',
  `admin_feedback` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `flagged_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notification_sent` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_image_review` (`image_id`, `image_type`),
  KEY `idx_admin_decision` (`admin_decision`),
  KEY `idx_risk_level` (`risk_level`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_reviewed_by` (`reviewed_by`),
  KEY `idx_flagged_at` (`flagged_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create authenticity_audit_log for tracking all verification activities
CREATE TABLE IF NOT EXISTS `authenticity_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_id` varchar(255) NOT NULL,
  `image_type` enum('practice_upload', 'custom_request') NOT NULL,
  `action` varchar(100) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `performed_by_type` enum('system', 'admin', 'user') DEFAULT 'system',
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_image_id_type` (`image_id`, `image_type`),
  KEY `idx_action` (`action`),
  KEY `idx_performed_by` (`performed_by`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create authenticity_settings for configurable verification parameters
CREATE TABLE IF NOT EXISTS `authenticity_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` enum('string', 'number', 'boolean', 'json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default authenticity settings
INSERT INTO `authenticity_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('suspicious_score_threshold', '60.0', 'number', 'Score above which images are flagged as suspicious'),
('highly_suspicious_score_threshold', '80.0', 'number', 'Score above which images are flagged as highly suspicious'),
('similarity_threshold', '0.85', 'number', 'Perceptual hash similarity threshold for duplicate detection'),
('auto_approve_clean_threshold', '30.0', 'number', 'Score below which images are auto-approved as clean'),
('metadata_extraction_enabled', 'true', 'boolean', 'Enable EXIF metadata extraction'),
('perceptual_hash_enabled', 'true', 'boolean', 'Enable perceptual hash generation for similarity detection'),
('duplicate_detection_enabled', 'true', 'boolean', 'Enable duplicate image detection'),
('camera_validation_enabled', 'true', 'boolean', 'Enable camera information validation'),
('editing_software_detection_enabled', 'true', 'boolean', 'Enable editing software detection'),
('max_processing_attempts', '3', 'number', 'Maximum attempts for image processing'),
('notification_enabled', 'true', 'boolean', 'Enable user notifications for verification results'),
('admin_notification_enabled', 'true', 'boolean', 'Enable admin notifications for flagged images');

-- Update practice_uploads table to include authenticity fields
ALTER TABLE `practice_uploads` 
ADD COLUMN IF NOT EXISTS `authenticity_verified` tinyint(1) DEFAULT 0 AFTER `status`,
ADD COLUMN IF NOT EXISTS `authenticity_score` decimal(5,2) DEFAULT NULL AFTER `authenticity_verified`,
ADD COLUMN IF NOT EXISTS `risk_level` enum('clean', 'suspicious', 'highly_suspicious') DEFAULT NULL AFTER `authenticity_score`,
ADD COLUMN IF NOT EXISTS `verification_status` enum('pending', 'verified', 'flagged', 'approved', 'rejected') DEFAULT 'pending' AFTER `risk_level`,
ADD COLUMN IF NOT EXISTS `admin_approved` tinyint(1) DEFAULT 0 AFTER `verification_status`,
ADD COLUMN IF NOT EXISTS `verification_notes` text DEFAULT NULL AFTER `admin_approved`;

-- Update custom_request_images table to include authenticity fields
ALTER TABLE `custom_request_images` 
ADD COLUMN IF NOT EXISTS `authenticity_verified` tinyint(1) DEFAULT 0 AFTER `uploaded_at`,
ADD COLUMN IF NOT EXISTS `authenticity_score` decimal(5,2) DEFAULT NULL AFTER `authenticity_verified`,
ADD COLUMN IF NOT EXISTS `risk_level` enum('clean', 'suspicious', 'highly_suspicious') DEFAULT NULL AFTER `authenticity_score`,
ADD COLUMN IF NOT EXISTS `verification_status` enum('pending', 'verified', 'flagged', 'approved', 'rejected') DEFAULT 'pending' AFTER `risk_level`,
ADD COLUMN IF NOT EXISTS `admin_approved` tinyint(1) DEFAULT 0 AFTER `verification_status`;

-- Create indexes for performance
ALTER TABLE `practice_uploads` 
ADD INDEX IF NOT EXISTS `idx_authenticity_verified` (`authenticity_verified`),
ADD INDEX IF NOT EXISTS `idx_authenticity_score` (`authenticity_score`),
ADD INDEX IF NOT EXISTS `idx_risk_level` (`risk_level`),
ADD INDEX IF NOT EXISTS `idx_verification_status` (`verification_status`),
ADD INDEX IF NOT EXISTS `idx_admin_approved` (`admin_approved`);

ALTER TABLE `custom_request_images` 
ADD INDEX IF NOT EXISTS `idx_authenticity_verified` (`authenticity_verified`),
ADD INDEX IF NOT EXISTS `idx_authenticity_score` (`authenticity_score`),
ADD INDEX IF NOT EXISTS `idx_risk_level` (`risk_level`),
ADD INDEX IF NOT EXISTS `idx_verification_status` (`verification_status`),
ADD INDEX IF NOT EXISTS `idx_admin_approved` (`admin_approved`);

-- Create view for admin dashboard - flagged images summary
CREATE OR REPLACE VIEW `admin_flagged_images_summary` AS
SELECT 
    arq.id as review_id,
    arq.image_id,
    arq.image_type,
    arq.user_id,
    arq.tutorial_id,
    arq.authenticity_score,
    arq.risk_level,
    arq.flagged_reasons,
    arq.admin_decision,
    arq.admin_feedback,
    arq.reviewed_by,
    arq.reviewed_at,
    arq.flagged_at,
    u.first_name,
    u.last_name,
    u.email,
    t.title as tutorial_title,
    iam.file_path,
    iam.original_filename,
    iam.metadata_extracted,
    iam.camera_info,
    iam.editing_software,
    iam.similarity_matches
FROM admin_review_queue arq
LEFT JOIN users u ON arq.user_id = u.id
LEFT JOIN tutorials t ON arq.tutorial_id = t.id
LEFT JOIN image_authenticity_metadata iam ON arq.image_id = iam.image_id AND arq.image_type = iam.image_type
ORDER BY arq.flagged_at DESC;

-- Create view for authenticity statistics
CREATE OR REPLACE VIEW `authenticity_statistics` AS
SELECT 
    COUNT(*) as total_images,
    SUM(CASE WHEN verification_status = 'verified' THEN 1 ELSE 0 END) as verified_images,
    SUM(CASE WHEN verification_status = 'flagged' THEN 1 ELSE 0 END) as flagged_images,
    SUM(CASE WHEN verification_status = 'pending' THEN 1 ELSE 0 END) as pending_images,
    SUM(CASE WHEN risk_level = 'clean' THEN 1 ELSE 0 END) as clean_images,
    SUM(CASE WHEN risk_level = 'suspicious' THEN 1 ELSE 0 END) as suspicious_images,
    SUM(CASE WHEN risk_level = 'highly_suspicious' THEN 1 ELSE 0 END) as highly_suspicious_images,
    AVG(authenticity_score) as avg_authenticity_score,
    MAX(authenticity_score) as max_authenticity_score,
    MIN(authenticity_score) as min_authenticity_score
FROM image_authenticity_metadata;