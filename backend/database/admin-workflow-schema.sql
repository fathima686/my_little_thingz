-- Admin Customization Workflow Database Schema

-- Enhanced custom_requests table with workflow fields
ALTER TABLE custom_requests 
ADD COLUMN IF NOT EXISTS product_type ENUM('design-based', 'handmade', 'mixed') DEFAULT 'design-based',
ADD COLUMN IF NOT EXISTS category VARCHAR(100) DEFAULT '',
ADD COLUMN IF NOT EXISTS workflow_stage ENUM('submitted', 'in_design', 'in_crafting', 'design_completed', 'packed', 'courier_assigned', 'delivered') DEFAULT 'submitted',
ADD COLUMN IF NOT EXISTS admin_id INT UNSIGNED DEFAULT NULL,
ADD COLUMN IF NOT EXISTS started_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS design_completed_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS packed_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS courier_assigned_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS delivered_at TIMESTAMP NULL;

-- Product categories and types configuration
CREATE TABLE IF NOT EXISTS product_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('design-based', 'handmade', 'mixed') NOT NULL,
    description TEXT,
    requires_editor BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name (name)
);

-- Insert default product categories
INSERT IGNORE INTO product_categories (name, type, requires_editor, description) VALUES
('Photo Frames', 'design-based', TRUE, 'Custom photo frames with personalized designs'),
('Polaroids', 'design-based', TRUE, 'Custom polaroid prints with editing'),
('Wedding Cards', 'design-based', TRUE, 'Wedding invitation cards with custom designs'),
('Name Boards', 'design-based', TRUE, 'Personalized name boards and signs'),
('Bouquets', 'handmade', FALSE, 'Handcrafted flower bouquets'),
('Handcrafted Gifts', 'handmade', FALSE, 'Custom handmade gift items'),
('Jewelry', 'handmade', FALSE, 'Custom jewelry pieces'),
('Cakes', 'handmade', FALSE, 'Custom decorated cakes');

-- Design files and editor data
CREATE TABLE IF NOT EXISTS custom_request_designs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNSIGNED NOT NULL,
    design_data JSON,
    preview_image_url VARCHAR(500),
    final_image_url VARCHAR(500),
    editor_session_id VARCHAR(100),
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_request_id (request_id),
    FOREIGN KEY (request_id) REFERENCES custom_requests(id) ON DELETE CASCADE
);

-- Workflow progress tracking
CREATE TABLE IF NOT EXISTS workflow_progress (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNSIGNED NOT NULL,
    stage ENUM('submitted', 'in_design', 'in_crafting', 'design_completed', 'packed', 'courier_assigned', 'delivered') NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'skipped') DEFAULT 'pending',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    admin_id INT UNSIGNED DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_request_id (request_id),
    INDEX idx_stage (stage),
    FOREIGN KEY (request_id) REFERENCES custom_requests(id) ON DELETE CASCADE
);

-- Courier and shipping details
CREATE TABLE IF NOT EXISTS shipping_details (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNSIGNED NOT NULL,
    courier_service VARCHAR(100),
    tracking_id VARCHAR(200),
    courier_contact VARCHAR(50),
    estimated_delivery DATE,
    actual_delivery_date DATE,
    shipping_address TEXT,
    special_instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_request_id (request_id),
    INDEX idx_tracking_id (tracking_id),
    FOREIGN KEY (request_id) REFERENCES custom_requests(id) ON DELETE CASCADE
);

-- Admin users table (if not exists)
CREATE TABLE IF NOT EXISTS admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    role ENUM('admin', 'designer', 'craftsperson', 'manager') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user
INSERT IGNORE INTO admin_users (username, email, password_hash, full_name, role) VALUES
('admin', 'admin@mylittlethingz.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');