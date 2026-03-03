<?php
require 'backend/config/database.php';
$db = new Database();
$pdo = $db->getConnection();

echo "Practice upload tables:\n";
$stmt = $pdo->query("SHOW TABLES LIKE 'practice_uploads%'");
while($row = $stmt->fetch(PDO::FETCH_NUM)) {
    echo "- " . $row[0] . "\n";
}

echo "\nChecking recent uploads:\n";
$stmt = $pdo->query("SELECT id, user_id, tutorial_id, status, craft_validation_status, upload_date FROM practice_uploads ORDER BY id DESC LIMIT 5");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}, Status: {$row['status']}, Craft Status: {$row['craft_validation_status']}, Date: {$row['upload_date']}\n";
}
?>
