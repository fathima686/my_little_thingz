<?php
// Check All Certificates
header('Content-Type: application/json');

echo "🔍 Checking All Certificates in Database\n\n";

$userEmail = 'soudhame52@gmail.com';

try {
    require_once 'backend/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Find user
    $userStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $userStmt->execute([$userEmail]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    $userId = $user['id'];
    
    echo "User ID: $userId\n\n";
    
    // Check ALL certificates for this user with detailed info
    $certStmt = $db->prepare("SELECT * FROM certificates WHERE user_id = ? ORDER BY issued_at DESC");
    $certStmt->execute([$userId]);
    $certificates = $certStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($certificates) . " certificate records:\n\n";
    
    foreach ($certificates as $index => $cert) {
        echo "Certificate #" . ($index + 1) . ":\n";
        echo "- ID: " . $cert['certificate_id'] . "\n";
        echo "- User Name: '" . ($cert['user_name'] ?? 'NULL') . "'\n";
        echo "- Issued At: " . ($cert['issued_at'] ?? $cert['completion_date'] ?? 'Unknown') . "\n";
        echo "- Created At: " . ($cert['created_at'] ?? 'Unknown') . "\n";
        echo "- Updated At: " . ($cert['updated_at'] ?? 'Unknown') . "\n";
        echo str_repeat("-", 50) . "\n";
    }
    
    // Test the exact query used in the certificate API
    echo "\n🔍 Testing the exact database query from certificate API...\n";
    
    $testStmt = $db->prepare("SELECT user_name, certificate_id FROM certificates WHERE user_id = ? ORDER BY issued_at DESC LIMIT 1");
    $testStmt->execute([$userId]);
    $testResult = $testStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Query result: " . json_encode($testResult) . "\n";
    
    if ($testResult) {
        echo "Latest certificate name: '" . $testResult['user_name'] . "'\n";
        echo "Latest certificate ID: " . $testResult['certificate_id'] . "\n";
    }
    
    // Check if there are multiple certificate tables
    echo "\n🔍 Checking for other certificate tables...\n";
    
    $tablesStmt = $db->query("SHOW TABLES LIKE '%certificate%'");
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Certificate-related tables:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
        
        // Check if this table has records for our user
        try {
            $countStmt = $db->prepare("SELECT COUNT(*) as count FROM $table WHERE user_id = ?");
            $countStmt->execute([$userId]);
            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "  Records for user: $count\n";
        } catch (Exception $e) {
            echo "  Could not check records: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Check completed at: " . date('Y-m-d H:i:s') . "\n";
?>