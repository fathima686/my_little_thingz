-- Template Gallery Database Schema
-- Integrates with existing custom requests system

-- Design templates table
CREATE TABLE IF NOT EXISTS design_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('Birthday', 'Wedding', 'Invitation', 'Posters', 'Photo Frames') NOT NULL,
    canvas_width INT UNSIGNED NOT NULL DEFAULT 800,
    canvas_height INT UNSIGNED NOT NULL DEFAULT 600,
    template_data JSON NOT NULL COMMENT 'Complete template definition with elements',
    preview_image_url VARCHAR(500),
    thumbnail_url VARCHAR(500),
    is_public BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    created_by INT UNSIGNED DEFAULT NULL,
    usage_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_public (is_public),
    INDEX idx_featured (is_featured),
    INDEX idx_created_by (created_by)
);

-- Template categories configuration
CREATE TABLE IF NOT EXISTS template_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default categories
INSERT IGNORE INTO template_categories (name, display_name, description, icon, sort_order) VALUES
('Birthday', 'Birthday', 'Birthday celebration templates', 'gift', 1),
('Wedding', 'Wedding', 'Wedding and anniversary templates', 'heart', 2),
('Invitation', 'Invitation', 'Event invitation templates', 'mail', 3),
('Posters', 'Posters', 'Poster and banner templates', 'image', 4),
('Photo Frames', 'Photo Frames', 'Photo frame templates', 'frame', 5);

-- Template usage tracking
CREATE TABLE IF NOT EXISTS template_usage (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_id INT UNSIGNED NOT NULL,
    request_id INT UNSIGNED DEFAULT NULL,
    user_id INT UNSIGNED DEFAULT NULL,
    user_type ENUM('customer', 'admin') DEFAULT 'customer',
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_template_id (template_id),
    INDEX idx_request_id (request_id),
    INDEX idx_user_id (user_id),
    FOREIGN KEY (template_id) REFERENCES design_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES custom_requests(id) ON DELETE SET NULL
);

-- Extend custom_request_designs table for template integration
ALTER TABLE custom_request_designs 
ADD COLUMN IF NOT EXISTS template_id INT UNSIGNED DEFAULT NULL,
ADD COLUMN IF NOT EXISTS template_version VARCHAR(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS export_format ENUM('png', 'pdf', 'jpg') DEFAULT 'png',
ADD COLUMN IF NOT EXISTS export_quality ENUM('draft', 'standard', 'high', 'print') DEFAULT 'standard',
ADD COLUMN IF NOT EXISTS is_template_locked BOOLEAN DEFAULT FALSE,
ADD INDEX idx_template_id (template_id);

-- Add foreign key constraint if it doesn't exist
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'custom_request_designs' 
     AND CONSTRAINT_NAME = 'fk_template_id') = 0,
    'ALTER TABLE custom_request_designs ADD CONSTRAINT fk_template_id FOREIGN KEY (template_id) REFERENCES design_templates(id) ON DELETE SET NULL',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Insert sample templates with original designs
INSERT IGNORE INTO design_templates (name, description, category, canvas_width, canvas_height, template_data, is_public, is_featured) VALUES

-- Birthday Templates
('Happy Birthday Frame', 'Colorful birthday celebration frame with balloons and confetti', 'Birthday', 800, 600, 
'{"version":"1.0","background":{"type":"gradient","colors":["#FFE5B4","#FFCCCB"],"direction":"diagonal"},"elements":[{"type":"text","content":"Happy Birthday!","x":400,"y":100,"fontSize":48,"fontFamily":"Arial","fontWeight":"bold","fill":"#FF6B6B","textAlign":"center"},{"type":"shape","shape":"circle","x":150,"y":200,"width":80,"height":80,"fill":"#4ECDC4","stroke":"#45B7B8","strokeWidth":3},{"type":"shape","shape":"circle","x":650,"y":200,"width":80,"height":80,"fill":"#45B7B8","stroke":"#4ECDC4","strokeWidth":3},{"type":"text","content":"[Name]","x":400,"y":300,"fontSize":32,"fontFamily":"Arial","fill":"#2C3E50","textAlign":"center","placeholder":true},{"type":"image","x":300,"y":350,"width":200,"height":150,"placeholder":true,"label":"Photo"}]}', 
true, true),

('Birthday Balloon Card', 'Fun birthday card with floating balloons design', 'Birthday', 600, 800, 
'{"version":"1.0","background":{"type":"solid","color":"#F8F9FA"},"elements":[{"type":"shape","shape":"rectangle","x":50,"y":50,"width":500,"height":700,"fill":"#FFFFFF","stroke":"#E9ECEF","strokeWidth":2,"rx":15},{"type":"text","content":"Happy Birthday","x":300,"y":150,"fontSize":36,"fontFamily":"Georgia","fontWeight":"bold","fill":"#495057","textAlign":"center"},{"type":"shape","shape":"circle","x":200,"y":250,"width":40,"height":60,"fill":"#FF6B6B","rx":20},{"type":"shape","shape":"circle","x":280,"y":230,"width":40,"height":60,"fill":"#4ECDC4","rx":20},{"type":"shape","shape":"circle","x":360,"y":250,"width":40,"height":60,"fill":"#45B7B8","rx":20},{"type":"text","content":"[Message]","x":300,"y":400,"fontSize":18,"fontFamily":"Arial","fill":"#6C757D","textAlign":"center","placeholder":true,"multiline":true},{"type":"image","x":200,"y":500,"width":200,"height":150,"placeholder":true,"label":"Birthday Photo"}]}', 
true, false),

-- Wedding Templates
('Elegant Wedding Invitation', 'Classic wedding invitation with floral border', 'Wedding', 600, 800, 
'{"version":"1.0","background":{"type":"solid","color":"#FFFEF7"},"elements":[{"type":"shape","shape":"rectangle","x":50,"y":50,"width":500,"height":700,"fill":"#FFFFFF","stroke":"#D4AF37","strokeWidth":3,"rx":10},{"type":"text","content":"You are invited","x":300,"y":150,"fontSize":24,"fontFamily":"Playfair Display","fontStyle":"italic","fill":"#8B4513","textAlign":"center"},{"type":"text","content":"[Bride] & [Groom]","x":300,"y":250,"fontSize":32,"fontFamily":"Playfair Display","fontWeight":"bold","fill":"#2C3E50","textAlign":"center","placeholder":true},{"type":"text","content":"[Date]","x":300,"y":350,"fontSize":20,"fontFamily":"Arial","fill":"#6C757D","textAlign":"center","placeholder":true},{"type":"text","content":"[Venue]","x":300,"y":400,"fontSize":18,"fontFamily":"Arial","fill":"#6C757D","textAlign":"center","placeholder":true},{"type":"shape","shape":"rectangle","x":100,"y":500,"width":400,"height":2,"fill":"#D4AF37"},{"type":"text","content":"[Additional Details]","x":300,"y":600,"fontSize":16,"fontFamily":"Arial","fill":"#6C757D","textAlign":"center","placeholder":true,"multiline":true}]}', 
true, true),

('Wedding Photo Frame', 'Romantic wedding photo frame with hearts', 'Wedding', 800, 600, 
'{"version":"1.0","background":{"type":"gradient","colors":["#FFF8DC","#F5F5DC"],"direction":"radial"},"elements":[{"type":"text","content":"Our Wedding Day","x":400,"y":80,"fontSize":36,"fontFamily":"Playfair Display","fontWeight":"bold","fill":"#8B4513","textAlign":"center"},{"type":"shape","shape":"heart","x":150,"y":150,"width":30,"height":30,"fill":"#DC143C"},{"type":"shape","shape":"heart","x":620,"y":150,"width":30,"height":30,"fill":"#DC143C"},{"type":"image","x":250,"y":200,"width":300,"height":200,"placeholder":true,"label":"Wedding Photo"},{"type":"text","content":"[Couple Names]","x":400,"y":450,"fontSize":24,"fontFamily":"Playfair Display","fill":"#2C3E50","textAlign":"center","placeholder":true},{"type":"text","content":"[Date]","x":400,"y":500,"fontSize":18,"fontFamily":"Arial","fill":"#6C757D","textAlign":"center","placeholder":true}]}', 
true, false),

-- Invitation Templates
('Party Invitation', 'Vibrant party invitation template', 'Invitation', 600, 800, 
'{"version":"1.0","background":{"type":"gradient","colors":["#667eea","#764ba2"],"direction":"diagonal"},"elements":[{"type":"shape","shape":"rectangle","x":50,"y":50,"width":500,"height":700,"fill":"rgba(255,255,255,0.9)","rx":20},{"type":"text","content":"You\'re Invited!","x":300,"y":150,"fontSize":42,"fontFamily":"Arial","fontWeight":"bold","fill":"#4A90E2","textAlign":"center"},{"type":"text","content":"[Event Name]","x":300,"y":250,"fontSize":28,"fontFamily":"Arial","fontWeight":"bold","fill":"#2C3E50","textAlign":"center","placeholder":true},{"type":"text","content":"Date: [Date]","x":300,"y":350,"fontSize":20,"fontFamily":"Arial","fill":"#34495E","textAlign":"center","placeholder":true},{"type":"text","content":"Time: [Time]","x":300,"y":400,"fontSize":20,"fontFamily":"Arial","fill":"#34495E","textAlign":"center","placeholder":true},{"type":"text","content":"Venue: [Venue]","x":300,"y":450,"fontSize":20,"fontFamily":"Arial","fill":"#34495E","textAlign":"center","placeholder":true},{"type":"text","content":"RSVP: [Contact]","x":300,"y":550,"fontSize":18,"fontFamily":"Arial","fill":"#7F8C8D","textAlign":"center","placeholder":true}]}', 
true, true),

-- Poster Templates
('Event Poster', 'Modern event poster design', 'Posters', 600, 900, 
'{"version":"1.0","background":{"type":"gradient","colors":["#2C3E50","#3498DB"],"direction":"vertical"},"elements":[{"type":"text","content":"[EVENT NAME]","x":300,"y":150,"fontSize":48,"fontFamily":"Arial","fontWeight":"bold","fill":"#FFFFFF","textAlign":"center","placeholder":true},{"type":"shape","shape":"rectangle","x":100,"y":200,"width":400,"height":4,"fill":"#E74C3C"},{"type":"text","content":"[Date & Time]","x":300,"y":300,"fontSize":24,"fontFamily":"Arial","fill":"#ECF0F1","textAlign":"center","placeholder":true},{"type":"text","content":"[Venue]","x":300,"y":350,"fontSize":20,"fontFamily":"Arial","fill":"#BDC3C7","textAlign":"center","placeholder":true},{"type":"image","x":200,"y":450,"width":200,"height":200,"placeholder":true,"label":"Event Image"},{"type":"text","content":"[Description]","x":300,"y":700,"fontSize":16,"fontFamily":"Arial","fill":"#ECF0F1","textAlign":"center","placeholder":true,"multiline":true},{"type":"text","content":"[Contact Info]","x":300,"y":800,"fontSize":14,"fontFamily":"Arial","fill":"#95A5A6","textAlign":"center","placeholder":true}]}', 
true, false),

-- Photo Frame Templates
('Family Photo Frame', 'Warm family photo frame design', 'Photo Frames', 800, 600, 
'{"version":"1.0","background":{"type":"gradient","colors":["#FFF8E1","#FFECB3"],"direction":"radial"},"elements":[{"type":"shape","shape":"rectangle","x":50,"y":50,"width":700,"height":500,"fill":"#FFFFFF","stroke":"#8D6E63","strokeWidth":8,"rx":10},{"type":"text","content":"Family Memories","x":400,"y":100,"fontSize":32,"fontFamily":"Georgia","fontWeight":"bold","fill":"#5D4037","textAlign":"center"},{"type":"image","x":150,"y":150,"width":500,"height":300,"placeholder":true,"label":"Family Photo"},{"type":"text","content":"[Family Name]","x":400,"y":500,"fontSize":24,"fontFamily":"Georgia","fill":"#3E2723","textAlign":"center","placeholder":true}]}', 
true, true),

('Collage Frame', 'Multi-photo collage frame', 'Photo Frames', 800, 600, 
'{"version":"1.0","background":{"type":"solid","color":"#F5F5F5"},"elements":[{"type":"shape","shape":"rectangle","x":50,"y":50,"width":700,"height":500,"fill":"#FFFFFF","stroke":"#CCCCCC","strokeWidth":2,"rx":5},{"type":"image","x":100,"y":100,"width":200,"height":150,"placeholder":true,"label":"Photo 1"},{"type":"image","x":350,"y":100,"width":200,"height":150,"placeholder":true,"label":"Photo 2"},{"type":"image","x":100,"y":300,"width":200,"height":150,"placeholder":true,"label":"Photo 3"},{"type":"image","x":350,"y":300,"width":200,"height":150,"placeholder":true,"label":"Photo 4"},{"type":"text","content":"[Title]","x":400,"y":500,"fontSize":28,"fontFamily":"Arial","fontWeight":"bold","fill":"#2C3E50","textAlign":"center","placeholder":true}]}', 
true, false);