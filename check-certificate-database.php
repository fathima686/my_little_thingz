<?php
// Check Certificate Database
header('Content-Type: application/json');

echo "🔍 Checking Certificate Database\n\n";

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
    
    // Check all certificates for this user
    $certStmt = $db->prepare("SELECT * FROM certificates WHERE user_id = ? ORDER BY issued_at DESC");
    $certStmt->execute([$userId]);
    $certificates = $certStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($certificates) . " certificate records:\n\n";
    
    foreach ($certificates as $cert) {
        echo "Certificate ID: " . $cert['certificate_id'] . "\n";
        echo "User Name: '" . ($cert['user_name'] ?? 'NULL') . "'\n";
        echo "Issued At: " . ($cert['issued_at'] ?? $cert['completion_date'] ?? 'Unknown') . "\n";
        echo "Tutorials Completed: " . ($cert['tutorials_completed'] ?? 'N/A') . "\n";
        echo "Overall Progress: " . ($cert['overall_progress'] ?? 'N/A') . "\n";
        echo str_repeat("-", 40) . "\n";
    }
    
    // Now test generating a new certificate with a custom name
    echo "\n🧪 Testing certificate generation with custom name...\n";
    
    $customName = 'Test Custom Name ' . date('H:i:s');
    echo "Custom name: '$customName'\n";
    
    // Delete existing certificate to force regeneration
    $deleteStmt = $db->prepare("DELETE FROM certificates WHERE user_id = ?");
    $deleteStmt->execute([$userId]);
    echo "Deleted existing certificates\n";
    
    // Generate new certificate with custom name
    $postData = json_encode(['name' => $customName]);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'X-Tutorial-Email: ' . $userEmail
            ],
            'content' => $postData,
            'timeout' => 30
        ]
    ]);
    
    $apiUrl = 'http://localhost/my_little_thingz/backend/api/pro/certificate.php';
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && $data['status'] === 'success') {
            echo "✅ Certificate generated successfully\n";
            echo "API returned name: '" . $data['certificate_name'] . "'\n";
            
            // Check what was actually stored in database
            $checkStmt = $db->prepare("SELECT user_name FROM certificates WHERE user_id = ? ORDER BY issued_at DESC LIMIT 1");
            $checkStmt->execute([$userId]);
            $storedCert = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($storedCert) {
                echo "Database stored name: '" . $storedCert['user_name'] . "'\n";
                
                if ($storedCert['user_name'] === $customName) {
                    echo "✅ SUCCESS: Custom name stored correctly in database!\n";
                } else {
                    echo "❌ PROBLEM: Custom name not stored correctly\n";
                    echo "Expected: '$customName'\n";
                    echo "Got: '" . $storedCert['user_name'] . "'\n";
                }
            } else {
                echo "❌ No certificate found in database after generation\n";
            }
            
        } else {
            echo "❌ Certificate generation failed: " . ($data['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "❌ Failed to call certificate API\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Check completed at: " . date('Y-m-d H:i:s') . "\n";
?>