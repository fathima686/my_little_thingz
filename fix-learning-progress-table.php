<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Adding missing columns to learning_progress table...\n";
    
    // Add practice_completed column
    try {
        $pdo->exec('ALTER TABLE learning_progress ADD COLUMN practice_completed TINYINT(1) DEFAULT 0 AFTER practice_uploaded');
        echo "✅ Added practice_completed column\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ️  practice_completed column already exists\n";
        } else {
            echo "❌ Error adding practice_completed: " . $e->getMessage() . "\n";
        }
    }
    
    // Add practice_admin_approved column
    try {
        $pdo->exec('ALTER TABLE learning_progress ADD COLUMN practice_admin_approved TINYINT(1) DEFAULT 0 AFTER practice_completed');
        echo "✅ Added practice_admin_approved column\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ️  practice_admin_approved column already exists\n";
        } else {
            echo "❌ Error adding practice_admin_approved: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nUpdated table structure:\n";
    echo "========================\n";
    
    $stmt = $pdo->query('DESCRIBE learning_progress');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-25s %-20s %-10s %s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Default'] ?? 'NULL'
        );
    }
    
    echo "\n✅ Learning progress table updated successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>