-- Design System Database Schema
-- Admin-Only Live Design Preview System

-- Table for storing design versions
CREATE TABLE design_versions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    version_number INT NOT NULL DEFAULT 1,
    canvas_data JSON NOT NULL,
    preview_image_path VARCHAR(500),
    created_by_admin_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by_admin_id) REFERENCES users(id),
    UNIQUE KEY unique_order_version (order_id, version_number),
    INDEX idx_order_id (order_id),
    INDEX idx_created_at (created_at)
);

-- Table for tracking order design status
CREATE TABLE order_design_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL UNIQUE,
    current_status ENUM('submitted', 'drafted_by_admin', 'changes_requested', 'approved_by_customer', 'locked_for_production') NOT NULL DEFAULT 'submitted',
    current_version INT DEFAULT 1,
    last_updated_by INT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    customer_feedback TEXT,
    admin_notes TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (last_updated_by) REFERENCES users(id),
    INDEX idx_status (current_status),
    INDEX idx_updated_at (updated_at)
);

-- Table for customer customization requests (initial submission)
CREATE TABLE customer_design_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    customer_text VARCHAR(500),
    customer_image_path VARCHAR(500),
    preferred_color VARCHAR(50),
    special_notes TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id)
);

-- Table for design approval history
CREATE TABLE design_approval_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    version_number INT NOT NULL,
    action ENUM('approved', 'changes_requested', 'admin_draft_saved') NOT NULL,
    performed_by INT NOT NULL,
    feedback TEXT,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id),
    INDEX idx_order_version (order_id, version_number),
    INDEX idx_performed_at (performed_at)
);

-- Insert sample data for testing
INSERT INTO customer_design_requests (order_id, customer_text, preferred_color, special_notes) VALUES
(1, 'Happy Birthday Mom!', '#FF69B4', 'Please make it elegant and beautiful'),
(2, 'Best Dad Ever', '#4169E1', 'Bold and masculine design preferred');

INSERT INTO order_design_status (order_id, current_status, last_updated_by) VALUES
(1, 'submitted', 1),
(2, 'submitted', 1);