<?php
require_once 'config/database.php';

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "Connected to database successfully.\n";
    
    // Create user_profiles table
    $sql = "CREATE TABLE IF NOT EXISTS user_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        first_name VARCHAR(100),
        last_name VARCHAR(100),
        phone VARCHAR(20),
        address TEXT,
        city VARCHAR(100),
        state VARCHAR(100),
        postal_code VARCHAR(20),
        country VARCHAR(100) DEFAULT 'India',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_profile (user_id)
    )";
    
    $pdo->exec($sql);
    echo "✓ user_profiles table created successfully.\n";
    
    // Create practice_uploads table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS practice_uploads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        tutorial_id INT NOT NULL,
        original_filename VARCHAR(255) NOT NULL,
        stored_filename VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_type VARCHAR(50) NOT NULL,
        file_size INT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        admin_feedback TEXT,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        reviewed_date TIMESTAMP NULL,
        reviewed_by INT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_tutorial (user_id, tutorial_id),
        INDEX idx_status (status)
    )";
    
    $pdo->exec($sql);
    echo "✓ practice_uploads table created successfully.\n";
    
    echo "\nAll tables created successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>