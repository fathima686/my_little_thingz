<?php
require 'backend/config/database.php';
$db = new Database();
$pdo = $db->getConnection();
$stmt = $pdo->query('SELECT id, title, category FROM tutorials LIMIT 1');
$t = $stmt->fetch(PDO::FETCH_ASSOC);
if ($t) {
    echo "Tutorial ID: {$t['id']}\n";
    echo "Title: {$t['title']}\n";
    echo "Category: {$t['category']}\n";
} else {
    echo "No tutorials found\n";
}
?>
