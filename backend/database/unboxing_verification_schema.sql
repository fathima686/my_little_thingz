-- ================================
-- UNBOXING VIDEO VERIFICATION SYSTEM
-- Database Schema for Academic Project
-- ================================

-- Table to store unboxing video uploads and refund/replacement requests
CREATE TABLE IF NOT EXISTS unboxing_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Order and Customer Information
    order_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    
    -- Issue Details
    issue_type ENUM('product_damaged', 'frame_broken', 'wrong_item_received', 'quality_issue') NOT NULL,
    request_type ENUM('refund', 'replacement') NOT NULL,
    
    -- Video Evidence
    video_filename VARCHAR(255) NOT NULL,
    video_path VARCHAR(500) NOT NULL,
    video_size_bytes INT UNSIGNED NOT NULL,
    
    -- Request Status Tracking
    request_status ENUM('pending', 'under_review', 'refund_approved', 'replacement_approved', 'rejected', 'refund_processed') DEFAULT 'pending',
    
    -- Customer Description
    customer_description TEXT,
    
    -- Admin Review
    admin_id INT UNSIGNED NULL,
    admin_notes TEXT NULL,
    admin_reviewed_at TIMESTAMP NULL,
    
    -- Refund Processing
    refund_id VARCHAR(100) NULL COMMENT 'Razorpay refund ID',
    refund_amount DECIMAL(10,2) NULL COMMENT 'Refund amount processed',
    refund_processed_at TIMESTAMP NULL COMMENT 'When refund was processed',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Constraints
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_order_request (order_id), -- Only one request per order
    
    -- Indexes for performance
    INDEX idx_customer_id (customer_id),
    INDEX idx_order_id (order_id),
    INDEX idx_request_status (request_status),
    INDEX idx_created_at (created_at)
);

-- Table to track request status history for transparency
CREATE TABLE IF NOT EXISTS unboxing_request_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNSIGNED NOT NULL,
    
    -- Status Change Details
    old_status ENUM('pending', 'under_review', 'refund_approved', 'replacement_approved', 'rejected', 'refund_processed'),
    new_status ENUM('pending', 'under_review', 'refund_approved', 'replacement_approved', 'rejected', 'refund_processed') NOT NULL,
    
    -- Who made the change
    changed_by_user_id INT UNSIGNED NOT NULL,
    change_reason TEXT,
    
    -- Timestamp
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Constraints
    FOREIGN KEY (request_id) REFERENCES unboxing_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_request_id (request_id),
    INDEX idx_created_at (created_at)
);

-- Add columns to existing orders table if they don't exist
-- (This is safe to run multiple times)
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS allows_unboxing_request TINYINT(1) DEFAULT 1 COMMENT 'Whether this order allows unboxing video requests',
ADD COLUMN IF NOT EXISTS delivered_at TIMESTAMP NULL COMMENT 'When the order was delivered';

-- Sample data for testing (optional)
-- INSERT INTO unboxing_requests (order_id, customer_id, issue_type, request_type, video_filename, video_path, video_size_bytes, customer_description) 
-- VALUES (1, 1, 'frame_broken', 'replacement', 'unboxing_order_1.mp4', 'uploads/unboxing_videos/unboxing_order_1.mp4', 15728640, 'The frame arrived with a crack on the left side. You can see it clearly in the video when I open the package.');