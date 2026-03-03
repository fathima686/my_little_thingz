<?php
require_once 'backend/config/database.php';
$database = new Database();
$pdo = $database->getConnection();

echo "practice_uploads table structure:\n";
echo "=================================\n";
$stmt = $pdo->query('DESCRIBE practice_uploads');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
?>