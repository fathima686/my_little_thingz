-- Assignment Management System Database Schema
-- Integrates with existing tutorial system

-- First, create subjects table using existing tutorial categories
-- This will map existing tutorial categories to subjects
CREATE TABLE IF NOT EXISTS subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert existing tutorial categories as subjects
INSERT IGNORE INTO subjects (name, description) VALUES
('Hand Embroidery', 'Traditional and modern hand embroidery techniques'),
('Resin Art', 'Creative resin art projects and techniques'),
('Gift Making', 'Handmade gift creation and wrapping'),
('Mylanchi / Mehandi Art', 'Traditional henna and mehandi designs'),
('Candle Making', 'Candle crafting and decoration techniques'),
('Jewelry Making', 'Handmade jewelry design and creation'),
('Clay Modeling', 'Clay sculpting and pottery techniques');

-- Create topics table for sub-categories within subjects
CREATE TABLE IF NOT EXISTS topics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    INDEX idx_subject_id (subject_id),
    INDEX idx_name (name),
    UNIQUE KEY unique_topic_per_subject (subject_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample topics for each subject
INSERT IGNORE INTO topics (subject_id, name, description) VALUES
-- Hand Embroidery topics
((SELECT id FROM subjects WHERE name = 'Hand Embroidery'), 'Basic Stitches', 'Fundamental embroidery stitches and techniques'),
((SELECT id FROM subjects WHERE name = 'Hand Embroidery'), 'Advanced Patterns', 'Complex embroidery patterns and designs'),
((SELECT id FROM subjects WHERE name = 'Hand Embroidery'), 'Thread Selection', 'Choosing the right threads and materials'),

-- Resin Art topics
((SELECT id FROM subjects WHERE name = 'Resin Art'), 'Mixing Techniques', 'Proper resin mixing and preparation'),
((SELECT id FROM subjects WHERE name = 'Resin Art'), 'Mold Making', 'Creating and using resin molds'),
((SELECT id FROM subjects WHERE name = 'Resin Art'), 'Color Techniques', 'Adding colors and effects to resin'),

-- Gift Making topics
((SELECT id FROM subjects WHERE name = 'Gift Making'), 'Packaging Design', 'Creative gift wrapping and presentation'),
((SELECT id FROM subjects WHERE name = 'Gift Making'), 'Personalization', 'Adding personal touches to gifts'),
((SELECT id FROM subjects WHERE name = 'Gift Making'), 'Seasonal Crafts', 'Holiday and seasonal gift ideas'),

-- Mehandi Art topics
((SELECT id FROM subjects WHERE name = 'Mylanchi / Mehandi Art'), 'Basic Patterns', 'Simple mehandi designs for beginners'),
((SELECT id FROM subjects WHERE name = 'Mylanchi / Mehandi Art'), 'Bridal Designs', 'Elaborate bridal mehandi patterns'),
((SELECT id FROM subjects WHERE name = 'Mylanchi / Mehandi Art'), 'Application Techniques', 'Proper mehandi application methods'),

-- Candle Making topics
((SELECT id FROM subjects WHERE name = 'Candle Making'), 'Wax Selection', 'Different types of wax and their uses'),
((SELECT id FROM subjects WHERE name = 'Candle Making'), 'Scent Blending', 'Creating custom candle fragrances'),
((SELECT id FROM subjects WHERE name = 'Candle Making'), 'Decorative Techniques', 'Adding decorative elements to candles'),

-- Jewelry Making topics
((SELECT id FROM subjects WHERE name = 'Jewelry Making'), 'Wire Wrapping', 'Wire jewelry techniques and patterns'),
((SELECT id FROM subjects WHERE name = 'Jewelry Making'), 'Bead Work', 'Bead selection and stringing techniques'),
((SELECT id FROM subjects WHERE name = 'Jewelry Making'), 'Metal Working', 'Basic metalworking for jewelry'),

-- Clay Modeling topics
((SELECT id FROM subjects WHERE name = 'Clay Modeling'), 'Basic Shaping', 'Fundamental clay shaping techniques'),
((SELECT id FROM subjects WHERE name = 'Clay Modeling'), 'Glazing Techniques', 'Applying glazes and finishes'),
((SELECT id FROM subjects WHERE name = 'Clay Modeling'), 'Firing Methods', 'Kiln firing and finishing processes');

-- Create assignments table
CREATE TABLE IF NOT EXISTS assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT UNSIGNED NOT NULL,
    subject_id INT NOT NULL,
    topic_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATETIME NOT NULL,
    max_marks INT NOT NULL DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE,
    INDEX idx_teacher_id (teacher_id),
    INDEX idx_subject_id (subject_id),
    INDEX idx_topic_id (topic_id),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create submissions table
CREATE TABLE IF NOT EXISTS submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    assignment_id INT NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    submission_type ENUM('text', 'file') NOT NULL,
    content TEXT,
    file_path VARCHAR(500),
    file_name VARCHAR(255),
    file_size INT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('submitted', 'evaluated', 'late') DEFAULT 'submitted',
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_submission (assignment_id, student_id),
    INDEX idx_assignment_id (assignment_id),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status),
    INDEX idx_submitted_at (submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create evaluations table
CREATE TABLE IF NOT EXISTS evaluations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    submission_id INT NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    marks_awarded INT NOT NULL,
    feedback TEXT,
    evaluated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_submission_id (submission_id),
    INDEX idx_teacher_id (teacher_id),
    INDEX idx_evaluated_at (evaluated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create assignment_audit_log table for tracking system activities
CREATE TABLE IF NOT EXISTS assignment_audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_table_name (table_name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add roles to users table if not exists (for teacher/student distinction)
-- This extends the existing users table to support assignment roles
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS roles JSON DEFAULT NULL,
ADD COLUMN IF NOT EXISTS assignment_permissions JSON DEFAULT NULL;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_users_roles ON users(roles(100));

-- Create a view for easy subject-topic relationships
CREATE OR REPLACE VIEW assignment_hierarchy AS
SELECT 
    s.id as subject_id,
    s.name as subject_name,
    s.description as subject_description,
    t.id as topic_id,
    t.name as topic_name,
    t.description as topic_description,
    COUNT(a.id) as assignment_count
FROM subjects s
LEFT JOIN topics t ON s.id = t.subject_id
LEFT JOIN assignments a ON t.id = a.topic_id AND a.status = 'active'
GROUP BY s.id, t.id
ORDER BY s.name, t.name;

-- Create a view for assignment statistics
CREATE OR REPLACE VIEW assignment_statistics AS
SELECT 
    a.id as assignment_id,
    a.title,
    a.max_marks,
    a.due_date,
    COUNT(s.id) as total_submissions,
    COUNT(e.id) as evaluated_submissions,
    ROUND(AVG(e.marks_awarded), 2) as average_marks,
    ROUND((COUNT(e.id) / COUNT(s.id)) * 100, 2) as evaluation_percentage
FROM assignments a
LEFT JOIN submissions s ON a.id = s.assignment_id
LEFT JOIN evaluations e ON s.id = e.submission_id
WHERE a.status = 'active'
GROUP BY a.id;