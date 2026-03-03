<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Learning Progress Table Structure:\n";
    echo "=================================\n";
    
    $stmt = $pdo->query('DESCRIBE learning_progress');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-25s %-20s %-10s %s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Default'] ?? 'NULL'
        );
    }
    
    echo "\nSample Data:\n";
    echo "============\n";
    
    $stmt = $pdo->query('SELECT * FROM learning_progress LIMIT 3');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($rows) {
        $headers = array_keys($rows[0]);
        echo implode(' | ', $headers) . "\n";
        echo str_repeat('-', count($headers) * 15) . "\n";
        
        foreach ($rows as $row) {
            echo implode(' | ', array_map(function($v) { return substr($v ?? 'NULL', 0, 12); }, $row)) . "\n";
        }
    } else {
        echo "No data found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>