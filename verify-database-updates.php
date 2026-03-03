<?php
require_once 'backend/config/database.php';

$database = new Database();
$pdo = $database->getConnection();

echo "📊 DATABASE VERIFICATION\n";
echo "=======================\n\n";

echo "Recent Practice Uploads:\n";
echo "------------------------\n";

$stmt = $pdo->query('
    SELECT id, user_id, tutorial_id, status, authenticity_status, craft_validation_status, 
           upload_date 
    FROM practice_uploads 
    ORDER BY id DESC 
    LIMIT 5
');

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("ID: %d | User: %d | Tutorial: %d | Status: %s | Auth: %s | Craft: %s | Date: %s\n",
        $row['id'], $row['user_id'], $row['tutorial_id'], 
        $row['status'], $row['authenticity_status'], $row['craft_validation_status'],
        $row['upload_date']
    );
}

echo "\nRecent Learning Progress (User 19):\n";
echo "-----------------------------------\n";

$stmt = $pdo->query('
    SELECT user_id, tutorial_id, practice_uploaded, practice_completed, practice_admin_approved, last_accessed
    FROM learning_progress 
    WHERE user_id = 19 
    ORDER BY last_accessed DESC 
    LIMIT 5
');

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("User: %d | Tutorial: %d | Uploaded: %s | Completed: %s | Approved: %s | Last: %s\n",
        $row['user_id'], $row['tutorial_id'],
        $row['practice_uploaded'] ? 'Yes' : 'No',
        $row['practice_completed'] ? 'Yes' : 'No', 
        $row['practice_admin_approved'] ? 'Yes' : 'No',
        $row['last_accessed']
    );
}

echo "\nCraft Validation Records:\n";
echo "-------------------------\n";

$stmt = $pdo->query('
    SELECT image_id, predicted_category, prediction_confidence, category_matches, 
           validation_status, created_at
    FROM craft_image_validation 
    ORDER BY id DESC 
    LIMIT 5
');

$count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $count++;
    echo sprintf("Image: %s | Category: %s | Confidence: %.1f%% | Match: %s | Status: %s | Date: %s\n",
        $row['image_id'], 
        $row['predicted_category'] ?? 'Unknown',
        ($row['prediction_confidence'] ?? 0) * 100,
        $row['category_matches'] ? 'Yes' : 'No',
        $row['validation_status'],
        $row['created_at']
    );
}

if ($count === 0) {
    echo "No craft validation records found\n";
}

echo "\n✅ Database verification complete!\n";
?>