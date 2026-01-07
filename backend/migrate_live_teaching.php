<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "Creating live teaching tables...\n";

    // 1. Add 'teacher' role if it doesn't exist
    echo "1. Adding teacher role...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS roles (
        id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $db->exec("INSERT IGNORE INTO roles (id, name) VALUES 
        (1, 'admin'),
        (2, 'customer'),
        (3, 'supplier'),
        (4, 'teacher')
    ");
    echo "✓ Teacher role added\n";

    // 2. Create live_subjects table
    echo "2. Creating live_subjects table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS live_subjects (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        description TEXT,
        icon_url VARCHAR(255),
        color VARCHAR(7) DEFAULT '#667eea',
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_active (is_active),
        UNIQUE KEY unique_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✓ Live subjects table created\n";

    // 3. Create live_sessions table
    echo "3. Creating live_sessions table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS live_sessions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        subject_id INT UNSIGNED NOT NULL,
        teacher_id INT UNSIGNED NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        google_meet_link VARCHAR(500) NOT NULL,
        scheduled_date DATE NOT NULL,
        scheduled_time TIME NOT NULL,
        duration_minutes INT DEFAULT 60,
        status ENUM('scheduled', 'live', 'completed', 'cancelled') DEFAULT 'scheduled',
        max_participants INT DEFAULT 50,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (subject_id) REFERENCES live_subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_subject (subject_id),
        INDEX idx_teacher (teacher_id),
        INDEX idx_status (status),
        INDEX idx_scheduled (scheduled_date, scheduled_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✓ Live sessions table created\n";

    // 4. Create live_session_registrations table (optional - for tracking who registered)
    echo "4. Creating live_session_registrations table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS live_session_registrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        session_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        attended BOOLEAN DEFAULT 0,
        FOREIGN KEY (session_id) REFERENCES live_sessions(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_registration (session_id, user_id),
        INDEX idx_session (session_id),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✓ Live session registrations table created\n";

    // 5. Sync subjects from tutorial categories
    echo "\n5. Syncing subjects from tutorial categories...\n";
    
    // Get unique categories from tutorials
    $categoryStmt = $db->prepare("
        SELECT DISTINCT category 
        FROM tutorials 
        WHERE category IS NOT NULL 
          AND category != '' 
          AND category != '0'
          AND is_active = 1
    ");
    $categoryStmt->execute();
    $tutorialCategories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Category colors matching frontend
    $categoryColors = [
        'Hand Embroidery' => '#FFB6C1',
        'Resin Art' => '#B0E0E6',
        'Gift Making' => '#FFDAB9',
        'Mylanchi / Mehandi Art' => '#E6E6FA',
        'Mehandi Art' => '#E6E6FA',
        'Candle Making' => '#F0E68C',
        'Jewelry Making' => '#FFC0CB',
        'Clay Modeling' => '#DDA0DD',
        'default' => '#667eea'
    ];
    
    if (empty($tutorialCategories)) {
        echo "  ⚠ No tutorial categories found. Subjects will be created automatically when tutorials are added.\n";
    } else {
        // Remove duplicates and empty values
        $uniqueCategories = array_filter(array_unique(array_map('trim', $tutorialCategories)), function($cat) {
            return !empty($cat) && $cat !== '0';
        });
        
        $syncStmt = $db->prepare("
            INSERT INTO live_subjects (name, description, color, is_active)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE 
                description = VALUES(description),
                color = VALUES(color),
                is_active = 1,
                updated_at = CURRENT_TIMESTAMP
        ");
        
        foreach ($uniqueCategories as $categoryName) {
            $color = $categoryColors[$categoryName] ?? $categoryColors['default'];
            $syncStmt->execute([
                $categoryName,
                "Live classes for {$categoryName}",
                $color
            ]);
            echo "  ✓ {$categoryName}\n";
        }
    }

    echo "\n✅ Live teaching migration completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Assign 'teacher' role to users who should create live sessions\n";
    echo "2. Access the live teaching feature from the tutorials dashboard\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

