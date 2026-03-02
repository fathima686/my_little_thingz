-- Template System Database Schema
-- Add these tables to support the Canva-style template editor

-- Design Templates Table
CREATE TABLE IF NOT EXISTS `design_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'other',
  `description` text,
  `thumbnail_path` varchar(500),
  `template_data` longtext NOT NULL COMMENT 'JSON data containing canvas and elements',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_active` (`is_active`),
  KEY `idx_featured` (`is_featured`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_created_at` (`created_at`),
  FULLTEXT KEY `idx_search` (`name`, `description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Template Categories Table (for better category management)
CREATE TABLE IF NOT EXISTS `template_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL UNIQUE,
  `name` varchar(100) NOT NULL,
  `description` text,
  `icon` varchar(50),
  `color` varchar(7) DEFAULT '#6366f1',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_slug` (`slug`),
  KEY `idx_active` (`is_active`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Template Usage Tracking (optional - for analytics)
CREATE TABLE IF NOT EXISTS `template_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `user_id` int(11),
  `session_id` varchar(100),
  `ip_address` varchar(45),
  `user_agent` text,
  `used_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_used_at` (`used_at`),
  FOREIGN KEY (`template_id`) REFERENCES `design_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default categories
INSERT INTO `template_categories` (`slug`, `name`, `description`, `icon`, `color`, `sort_order`) VALUES
('birthday', 'Birthday', 'Birthday cards and celebration designs', 'fas fa-birthday-cake', '#ff6b6b', 1),
('name-frame', 'Name Frame', 'Personalized name frames and borders', 'fas fa-frame', '#6366f1', 2),
('quotes', 'Quotes', 'Inspirational and motivational quote designs', 'fas fa-quote-left', '#8b5cf6', 3),
('anniversary', 'Anniversary', 'Anniversary and special occasion designs', 'fas fa-heart', '#ec4899', 4),
('business', 'Business', 'Business cards and professional designs', 'fas fa-briefcase', '#059669', 5),
('social', 'Social Media', 'Social media posts and stories', 'fas fa-share-alt', '#06b6d4', 6),
('other', 'Other', 'Miscellaneous templates', 'fas fa-star', '#6b7280', 99)
ON DUPLICATE KEY UPDATE 
  `name` = VALUES(`name`),
  `description` = VALUES(`description`),
  `icon` = VALUES(`icon`),
  `color` = VALUES(`color`),
  `sort_order` = VALUES(`sort_order`);

-- Insert sample templates
INSERT INTO `design_templates` (`name`, `category`, `description`, `template_data`, `created_by`) VALUES
(
  'Happy Birthday Card',
  'birthday',
  'A cheerful birthday card template with colorful design',
  '{
    "canvas": {
      "width": 800,
      "height": 600,
      "backgroundColor": "#ffe6cc"
    },
    "elements": [
      {
        "type": "text",
        "content": "Happy Birthday!",
        "x": 300,
        "y": 250,
        "fontSize": 48,
        "fontFamily": "Arial",
        "fontWeight": "bold",
        "fill": "#ff6b6b",
        "textAlign": "center"
      },
      {
        "type": "text",
        "content": "Hope your day is wonderful!",
        "x": 400,
        "y": 350,
        "fontSize": 24,
        "fontFamily": "Arial",
        "fill": "#333333",
        "textAlign": "center"
      }
    ]
  }',
  1
),
(
  'Elegant Name Frame',
  'name-frame',
  'A sophisticated name frame with border design',
  '{
    "canvas": {
      "width": 800,
      "height": 600,
      "backgroundColor": "#f8fafc"
    },
    "elements": [
      {
        "type": "shape",
        "shape": "rectangle",
        "x": 100,
        "y": 150,
        "width": 600,
        "height": 300,
        "fill": "transparent",
        "stroke": "#6366f1",
        "strokeWidth": 8
      },
      {
        "type": "text",
        "content": "Your Name Here",
        "x": 400,
        "y": 280,
        "fontSize": 42,
        "fontFamily": "Georgia",
        "fill": "#1f2937",
        "textAlign": "center"
      }
    ]
  }',
  1
),
(
  'Motivational Quote',
  'quotes',
  'Inspirational quote template with gradient background',
  '{
    "canvas": {
      "width": 800,
      "height": 600,
      "backgroundColor": {
        "type": "gradient",
        "direction": "linear",
        "colors": ["#667eea", "#764ba2"]
      }
    },
    "elements": [
      {
        "type": "text",
        "content": "\"The only way to do great work",
        "x": 400,
        "y": 220,
        "fontSize": 32,
        "fontFamily": "Georgia",
        "fontStyle": "italic",
        "fill": "#ffffff",
        "textAlign": "center"
      },
      {
        "type": "text",
        "content": "is to love what you do.\"",
        "x": 400,
        "y": 280,
        "fontSize": 32,
        "fontFamily": "Georgia",
        "fontStyle": "italic",
        "fill": "#ffffff",
        "textAlign": "center"
      },
      {
        "type": "text",
        "content": "- Steve Jobs",
        "x": 400,
        "y": 360,
        "fontSize": 20,
        "fontFamily": "Arial",
        "fill": "#e5e7eb",
        "textAlign": "center"
      }
    ]
  }',
  1
),
(
  'Anniversary Celebration',
  'anniversary',
  'Romantic anniversary template with heart elements',
  '{
    "canvas": {
      "width": 800,
      "height": 600,
      "backgroundColor": "#fdf2f8"
    },
    "elements": [
      {
        "type": "text",
        "content": "Happy Anniversary",
        "x": 400,
        "y": 200,
        "fontSize": 44,
        "fontFamily": "Georgia",
        "fontWeight": "bold",
        "fill": "#ec4899",
        "textAlign": "center"
      },
      {
        "type": "shape",
        "shape": "circle",
        "x": 350,
        "y": 300,
        "width": 100,
        "height": 100,
        "fill": "#fce7f3",
        "stroke": "#ec4899",
        "strokeWidth": 3
      },
      {
        "type": "text",
        "content": "♥",
        "x": 400,
        "y": 350,
        "fontSize": 48,
        "fontFamily": "Arial",
        "fill": "#ec4899",
        "textAlign": "center"
      }
    ]
  }',
  1
);

-- Add indexes for better performance
ALTER TABLE `design_templates` ADD INDEX `idx_category_active` (`category`, `is_active`);
ALTER TABLE `design_templates` ADD INDEX `idx_featured_active` (`is_featured`, `is_active`);

-- Update existing custom_requests table to link with templates (if needed)
-- ALTER TABLE `custom_requests` ADD COLUMN `template_id` int(11) NULL AFTER `design_id`;
-- ALTER TABLE `custom_requests` ADD KEY `idx_template_id` (`template_id`);

-- Create view for template statistics
CREATE OR REPLACE VIEW `template_stats` AS
SELECT 
    t.id,
    t.name,
    t.category,
    t.usage_count,
    COUNT(tu.id) as actual_usage_count,
    t.created_at,
    COALESCE(MAX(tu.used_at), t.created_at) as last_used_at
FROM design_templates t
LEFT JOIN template_usage tu ON t.id = tu.template_id
WHERE t.is_active = 1
GROUP BY t.id, t.name, t.category, t.usage_count, t.created_at;

-- Trigger to update usage count when template is used
DELIMITER $$
CREATE TRIGGER `update_template_usage_count` 
AFTER INSERT ON `template_usage`
FOR EACH ROW
BEGIN
    UPDATE `design_templates` 
    SET `usage_count` = `usage_count` + 1 
    WHERE `id` = NEW.template_id;
END$$
DELIMITER ;

-- Create stored procedure to clean up old usage tracking data
DELIMITER $$
CREATE PROCEDURE `CleanupTemplateUsage`(IN days_to_keep INT)
BEGIN
    DELETE FROM `template_usage` 
    WHERE `used_at` < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
END$$
DELIMITER ;

-- Comments for documentation
ALTER TABLE `design_templates` COMMENT = 'Stores reusable design templates for the Canva-style editor';
ALTER TABLE `template_categories` COMMENT = 'Categories for organizing design templates';
ALTER TABLE `template_usage` COMMENT = 'Tracks template usage for analytics and popular templates';