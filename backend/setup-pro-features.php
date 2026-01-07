<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Setting up Pro Features Database...\n";
    
    // Read and execute the schema
    $schema = file_get_contents(__DIR__ . '/database/pro_features_schema.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $db->exec($statement);
                echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (Exception $e) {
                echo "✗ Error: " . $e->getMessage() . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    // Update subscription plans with Pro features
    echo "\nUpdating subscription plans...\n";
    
    // Add practice_uploads to Pro plan features
    $stmt = $db->prepare("UPDATE subscription_plans SET features = ? WHERE plan_code = 'pro'");
    $proFeatures = json_encode([
        'Unlimited tutorial access',
        'HD video quality', 
        'Weekly new content',
        'Priority support',
        'Download videos',
        'Live workshops',
        '1-on-1 mentorship',
        'Certificate of completion',
        'Early access to new content',
        'Practice work uploads',
        'Progress tracking'
    ]);
    $stmt->execute([$proFeatures]);
    
    // Create default course and link existing tutorials
    echo "Setting up default course...\n";
    
    // Count existing tutorials
    $tutorialCount = $db->query("SELECT COUNT(*) as count FROM tutorials")->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Update course tutorial count
    $db->prepare("UPDATE courses SET total_tutorials = ? WHERE id = 1")->execute([$tutorialCount]);
    
    // Link existing tutorials to the default course
    $tutorials = $db->query("SELECT id FROM tutorials ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $sequence = 1;
    
    foreach ($tutorials as $tutorial) {
        $db->prepare("
            INSERT IGNORE INTO course_tutorials (course_id, tutorial_id, sequence_order, is_required)
            VALUES (1, ?, ?, 1)
        ")->execute([$tutorial['id'], $sequence]);
        $sequence++;
    }
    
    echo "✓ Linked {$tutorialCount} tutorials to default course\n";
    
    echo "\n🎉 Pro Features setup completed successfully!\n";
    echo "\nPro Features Available:\n";
    echo "- Practice work uploads\n";
    echo "- Learning progress tracking\n";
    echo "- Certificate generation (80% completion required)\n";
    echo "- Live workshops access\n";
    echo "- Admin dashboard for reviewing submissions\n";
    
} catch (Exception $e) {
    echo "❌ Setup failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>