-- Add Progress Tracking Tables to my_little_thingz Database
-- Run this SQL directly in phpMyAdmin or MySQL command line

USE my_little_thingz;

-- Create learning_progress table
CREATE TABLE IF NOT EXISTS `learning_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tutorial_id` int(11) NOT NULL,
  `watch_time_seconds` int(11) DEFAULT 0,
  `completion_percentage` decimal(5,2) DEFAULT 0.00,
  `completed_at` timestamp NULL DEFAULT NULL,
  `practice_uploaded` tinyint(1) DEFAULT 0,
  `last_accessed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_tutorial` (`user_id`,`tutorial_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_tutorial_id` (`tutorial_id`),
  KEY `idx_completion` (`completion_percentage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create practice_uploads table
CREATE TABLE IF NOT EXISTS `practice_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tutorial_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_feedback` text DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_tutorial` (`user_id`,`tutorial_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create certificates table
CREATE TABLE IF NOT EXISTS `certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `certificate_id` varchar(50) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `completion_date` date NOT NULL,
  `tutorials_completed` int(11) DEFAULT 0,
  `overall_progress` decimal(5,2) DEFAULT 0.00,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificate_id` (`certificate_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_certificate_id` (`certificate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample progress data for soudhame52@gmail.com
INSERT IGNORE INTO `learning_progress` (`user_id`, `tutorial_id`, `watch_time_seconds`, `completion_percentage`, `completed_at`, `practice_uploaded`, `last_accessed`, `created_at`) VALUES
(19, 1, 2700, 90.00, NULL, 0, NOW(), NOW()),
(19, 2, 1800, 85.00, NULL, 0, NOW(), NOW()),
(19, 3, 3600, 100.00, NOW(), 1, NOW(), NOW()),
(19, 4, 2400, 75.00, NULL, 0, NOW(), NOW()),
(19, 5, 1500, 60.00, NULL, 0, NOW(), NOW());

-- Insert sample practice upload for tutorial 3
INSERT IGNORE INTO `practice_uploads` (`user_id`, `tutorial_id`, `description`, `images`, `status`, `admin_feedback`, `upload_date`) VALUES
(19, 3, 'My completed gift box project', '[{"original_name": "gift_box_final.jpg", "stored_name": "practice_19_3_sample.jpg", "file_path": "uploads/practice/practice_19_3_sample.jpg", "file_size": 245760}]', 'approved', 'Excellent work! Great attention to detail and creativity.', NOW());

-- Verify tables were created
SELECT 'learning_progress table created' as status, COUNT(*) as records FROM learning_progress;
SELECT 'practice_uploads table created' as status, COUNT(*) as records FROM practice_uploads;
SELECT 'certificates table created' as status, COUNT(*) as records FROM certificates;