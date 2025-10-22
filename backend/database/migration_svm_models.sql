-- SVM Models Migration
-- This script creates the svm_models table for storing trained SVM models

CREATE TABLE IF NOT EXISTS svm_models (
    id INT AUTO_INCREMENT PRIMARY KEY,
    model_name VARCHAR(100) NOT NULL,
    weights JSON NOT NULL,
    bias DECIMAL(10, 6) NOT NULL DEFAULT 0,
    is_active BOOLEAN DEFAULT FALSE,
    accuracy DECIMAL(5, 4) DEFAULT NULL,
    training_samples INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_model_name (model_name),
    INDEX idx_is_active (is_active),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default model configuration
INSERT INTO svm_models (model_name, weights, bias, is_active, training_samples) 
VALUES (
    'svm_gift_classifier',
    '{"price": 0.8, "category_luxury": 0.6, "title_keywords": 0.4, "description_keywords": 0.3, "availability": 0.2}',
    -0.5,
    TRUE,
    0
) ON DUPLICATE KEY UPDATE
weights = VALUES(weights),
bias = VALUES(bias),
is_active = VALUES(is_active);



