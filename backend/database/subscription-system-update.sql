-- Subscription System Database Updates
-- Run this to ensure all required tables and data exist

-- Update subscription_plans table with proper plan structure
INSERT INTO subscription_plans (plan_code, plan_name, price, duration_months, features, access_levels, created_at) VALUES
('basic', 'Basic', 0.00, 0, 
 '["Limited video access (preview only)", "Basic video quality", "Community support", "Access to free tutorials"]',
 '{"can_access_live_workshops": false, "can_download_videos": false, "can_access_hd_video": false, "can_access_unlimited_tutorials": false, "can_upload_practice_work": false, "can_access_certificates": false, "can_access_mentorship": false}',
 NOW()),
('premium', 'Premium', 499.00, 1,
 '["Full video access (watch complete videos)", "HD video quality", "Download videos for offline viewing", "Priority support", "Weekly new content", "Access to all tutorials"]',
 '{"can_access_live_workshops": false, "can_download_videos": true, "can_access_hd_video": true, "can_access_unlimited_tutorials": true, "can_upload_practice_work": false, "can_access_certificates": false, "can_access_mentorship": false}',
 NOW()),
('pro', 'Pro', 999.00, 1,
 '["Everything in Premium", "Access to live classes (Google Meet links)", "Upload practice images", "Progress tracking with certificates", "1-on-1 mentorship sessions", "Early access to new content", "Certificate generation on 100% completion"]',
 '{"can_access_live_workshops": true, "can_download_videos": true, "can_access_hd_video": true, "can_access_unlimited_tutorials": true, "can_upload_practice_work": true, "can_access_certificates": true, "can_access_mentorship": true}',
 NOW())
ON DUPLICATE KEY UPDATE
plan_name = VALUES(plan_name),
price = VALUES(price),
features = VALUES(features),
access_levels = VALUES(access_levels),
updated_at = NOW();

-- Update subscriptions table to support email-based subscriptions
ALTER TABLE subscriptions 
ADD COLUMN IF NOT EXISTS email VARCHAR(255) AFTER user_id,
ADD COLUMN IF NOT EXISTS plan_code VARCHAR(50) AFTER plan_id,
ADD INDEX IF NOT EXISTS idx_email (email),
ADD INDEX IF NOT EXISTS idx_plan_code (plan_code);

-- Create learning_progress table if it doesn't exist
CREATE TABLE IF NOT EXISTS learning_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tutorial_id INT NOT NULL,
    watch_time_seconds INT DEFAULT 0,
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    completed_at TIMESTAMP NULL,
    practice_uploaded BOOLEAN DEFAULT FALSE,
    last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_tutorial (user_id, tutorial_id),
    INDEX idx_user_id (user_id),
    INDEX idx_tutorial_id (tutorial_id),
    INDEX idx_completion (completion_percentage)
);

-- Create practice_uploads table if it doesn't exist
CREATE TABLE IF NOT EXISTS practice_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tutorial_id INT NOT NULL,
    description TEXT,
    images JSON,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_feedback TEXT,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_date TIMESTAMP NULL,
    INDEX idx_user_tutorial (user_id, tutorial_id),
    INDEX idx_status (status)
);

-- Update tutorials table to ensure all required columns exist
ALTER TABLE tutorials 
ADD COLUMN IF NOT EXISTS category VARCHAR(100) DEFAULT 'general' AFTER difficulty_level,
ADD COLUMN IF NOT EXISTS duration INT DEFAULT 0 AFTER thumbnail_url,
ADD COLUMN IF NOT EXISTS is_free BOOLEAN DEFAULT 0 AFTER category;

-- Insert sample tutorials if none exist
INSERT IGNORE INTO tutorials (title, description, video_url, thumbnail_url, duration, difficulty_level, category, is_free, price, created_at) VALUES
('Hand Embroidery Basics', 'Learn the fundamentals of hand embroidery with step-by-step instructions', 'https://example.com/embroidery.mp4', 'uploads/thumbnails/embroidery.jpg', 45, 'beginner', 'embroidery', 0, 299.00, NOW()),
('Resin Art Clock Making', 'Create beautiful resin art clocks with this comprehensive tutorial', 'https://example.com/resin-clock.mp4', 'uploads/thumbnails/resin-clock.jpg', 90, 'intermediate', 'resin', 0, 499.00, NOW()),
('Gift Box Creation', 'Master the art of creating personalized gift boxes', 'https://example.com/gift-box.mp4', 'uploads/thumbnails/gift-box.jpg', 60, 'beginner', 'gifts', 1, 0.00, NOW()),
('Mehandi Design Patterns', 'Learn intricate mehandi patterns and techniques', 'https://example.com/mehandi.mp4', 'uploads/thumbnails/mehandi.jpg', 75, 'intermediate', 'mehandi', 0, 399.00, NOW()),
('Candle Making Workshop', 'Complete guide to making scented and decorative candles', 'https://example.com/candles.mp4', 'uploads/thumbnails/candles.jpg', 120, 'beginner', 'candles', 0, 349.00, NOW()),
('Jewelry Making Basics', 'Introduction to handmade jewelry creation', 'https://example.com/jewelry.mp4', 'uploads/thumbnails/jewelry.jpg', 85, 'intermediate', 'jewelry', 0, 449.00, NOW()),
('Paper Craft Techniques', 'Explore various paper crafting methods and projects', 'https://example.com/paper-craft.mp4', 'uploads/thumbnails/paper-craft.jpg', 50, 'beginner', 'paper', 1, 0.00, NOW()),
('Clay Modeling Advanced', 'Advanced techniques for clay modeling and sculpting', 'https://example.com/clay.mp4', 'uploads/thumbnails/clay.jpg', 100, 'advanced', 'clay', 0, 599.00, NOW());

-- Create certificates table for tracking issued certificates
CREATE TABLE IF NOT EXISTS certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    certificate_id VARCHAR(50) UNIQUE NOT NULL,
    user_name VARCHAR(255) NOT NULL,
    completion_date DATE NOT NULL,
    tutorials_completed INT DEFAULT 0,
    overall_progress DECIMAL(5,2) DEFAULT 0.00,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_certificate_id (certificate_id)
);

-- Update live_subjects table to ensure proper structure
INSERT IGNORE INTO live_subjects (name, description, color, created_at) VALUES
('Hand Embroidery', 'Learn traditional and modern embroidery techniques', '#FF6B6B', NOW()),
('Resin Art', 'Create stunning resin art pieces and functional items', '#4ECDC4', NOW()),
('Gift Making', 'Craft personalized gifts for special occasions', '#45B7D1', NOW()),
('Mehandi Art', 'Master the art of henna design and application', '#96CEB4', NOW()),
('Candle Making', 'Learn to make scented and decorative candles', '#FFEAA7', NOW()),
('Jewelry Making', 'Create beautiful handmade jewelry pieces', '#DDA0DD', NOW()),
('Paper Crafts', 'Explore creative paper crafting techniques', '#98D8C8', NOW());

-- Create user progress summary view
CREATE OR REPLACE VIEW user_progress_summary AS
SELECT 
    u.id as user_id,
    u.email,
    u.first_name,
    u.last_name,
    COUNT(lp.id) as tutorials_started,
    SUM(CASE WHEN lp.completion_percentage >= 80 THEN 1 ELSE 0 END) as tutorials_completed,
    AVG(lp.completion_percentage) as overall_progress,
    SUM(lp.practice_uploaded) as practice_uploads,
    s.plan_code,
    s.subscription_status
FROM users u
LEFT JOIN learning_progress lp ON u.id = lp.user_id
LEFT JOIN subscriptions s ON u.email = s.email AND s.is_active = 1
GROUP BY u.id, u.email, u.first_name, u.last_name, s.plan_code, s.subscription_status;

-- Insert test subscription for soudhame52@gmail.com
INSERT INTO subscriptions (email, plan_code, subscription_status, is_active, created_at, updated_at) 
VALUES ('soudhame52@gmail.com', 'pro', 'active', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE 
plan_code = 'pro', 
subscription_status = 'active', 
is_active = 1, 
updated_at = NOW();

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_subscriptions_email_active ON subscriptions (email, is_active);
CREATE INDEX IF NOT EXISTS idx_learning_progress_user_completion ON learning_progress (user_id, completion_percentage);
CREATE INDEX IF NOT EXISTS idx_practice_uploads_user_status ON practice_uploads (user_id, status);

COMMIT;