-- Pro Features Database Schema
-- Practice work uploads, progress tracking, and certificates

-- Table for storing practice work uploads
CREATE TABLE IF NOT EXISTS practice_uploads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    tutorial_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('image', 'video', 'document') NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_size INT UNSIGNED DEFAULT 0,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_feedback TEXT DEFAULT NULL,
    reviewed_by INT UNSIGNED DEFAULT NULL,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_user_tutorial (user_id, tutorial_id),
    INDEX idx_status (status),
    INDEX idx_upload_date (upload_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for tracking learning progress
CREATE TABLE IF NOT EXISTS learning_progress (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    tutorial_id INT UNSIGNED NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    watch_time_seconds INT UNSIGNED DEFAULT 0,
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    has_practice_upload BOOLEAN DEFAULT FALSE,
    practice_approved BOOLEAN DEFAULT FALSE,
    last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_tutorial (user_id, tutorial_id),
    INDEX idx_user (user_id),
    INDEX idx_completion (completion_percentage),
    INDEX idx_completed (completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for certificates
CREATE TABLE IF NOT EXISTS certificates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    certificate_code VARCHAR(50) NOT NULL UNIQUE,
    learner_name VARCHAR(100) NOT NULL,
    course_name VARCHAR(200) NOT NULL,
    completion_percentage DECIMAL(5,2) NOT NULL,
    total_tutorials INT UNSIGNED NOT NULL,
    completed_tutorials INT UNSIGNED NOT NULL,
    issued_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    certificate_path VARCHAR(500) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_user (user_id),
    INDEX idx_code (certificate_code),
    INDEX idx_issued_date (issued_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for course definitions (grouping tutorials)
CREATE TABLE IF NOT EXISTS courses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    total_tutorials INT UNSIGNED DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Junction table for course-tutorial relationships
CREATE TABLE IF NOT EXISTS course_tutorials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    tutorial_id INT UNSIGNED NOT NULL,
    sequence_order INT UNSIGNED DEFAULT 0,
    is_required BOOLEAN DEFAULT TRUE,
    UNIQUE KEY unique_course_tutorial (course_id, tutorial_id),
    INDEX idx_course (course_id),
    INDEX idx_tutorial (tutorial_id),
    INDEX idx_sequence (sequence_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default course data
INSERT IGNORE INTO courses (id, name, description, category, total_tutorials) VALUES
(1, 'Complete Craft Mastery', 'Master all craft techniques with hands-on practice', 'General', 0);

-- Update total_tutorials count for the default course
-- This will be updated dynamically as tutorials are added