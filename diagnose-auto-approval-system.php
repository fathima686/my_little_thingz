<?php
/**
 * Diagnose Auto-Approval System
 * 
 * Complete diagnostic check for the tutorial practice upload auto-approval system
 */

echo "=== AUTO-APPROVAL SYSTEM DIAGNOSTIC ===\n\n";

// Check 1: Database Connection and Tables
echo "1. CHECKING DATABASE...\n";

try {
    require_once 'backend/config/database.php';
    require_once 'backend/config/env-loader.php';
    
    EnvLoader::load();
    $database = new Database();
    $pdo = $database->getConnection();
    echo "✅ Database connection successful\n";
    
    // Check required tables
    $requiredTables = [
        'practice_uploads' => 'Practice upload records',
        'craft_image_validation' => 'AI validation results', 
        'learning_progress' => 'Student progress tracking',
        'tutorials' => 'Tutorial information',
        'users' => 'User accounts'
    ];
    
    foreach ($requiredTables as $table => $description) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $countStmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "✅ $description table exists ($count records)\n";
        } else {
            echo "❌ $description table MISSING\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Check 2: AI Services
echo "\n2. CHECKING AI SERVICES...\n";

// Local classifier (port 5000)
$ch = curl_init('http://localhost:5000/health');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $healthData = json_decode($response, true);
    echo "✅ Local Classifier (Port 5000): " . ($healthData['status'] ?? 'Running') . "\n";
    echo "   Model: " . ($healthData['model'] ?? 'Unknown') . "\n";
} else {
    echo "❌ Local Classifier (Port 5000): Not responding\n";
}

// Enhanced craft classifier (port 5001)
$ch = curl_init('http://localhost:5001/health');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $healthData = json_decode($response, true);
    echo "✅ Enhanced Craft Classifier (Port 5001): " . ($healthData['status'] ?? 'Running') . "\n";
    echo "   Classifier Available: " . ($healthData['classifier_available'] ? 'Yes' : 'No') . "\n";
} else {
    echo "⚠️  Enhanced Craft Classifier (Port 5001): Not responding (will use fallback)\n";
}

// Check 3: Upload API Endpoints
echo "\n3. CHECKING UPLOAD API ENDPOINTS...\n";

$apiEndpoints = [
    'practice-upload.php' => 'Main upload API (used by PracticeUpload.jsx)',
    'practice-upload-simple.php' => 'Simple upload API (used by TutorialViewer.jsx)',
    'practice-upload-direct.php' => 'Direct upload API (used by TutorialViewer.jsx)'
];

foreach ($apiEndpoints as $endpoint => $description) {
    $apiPath = "backend/api/pro/$endpoint";
    if (file_exists($apiPath)) {
        echo "✅ $description exists\n";
        
        // Check if it has AI validation
        $content = file_get_contents($apiPath);
        if (strpos($content, 'CraftImageValidationService') !== false) {
            echo "   ✅ Contains AI validation logic\n";
        } else {
            echo "   ❌ Missing AI validation logic\n";
        }
    } else {
        echo "❌ $description MISSING\n";
    }
}

// Check 4: Upload Directory
echo "\n4. CHECKING UPLOAD DIRECTORY...\n";

$uploadDir = 'backend/uploads/practice/';
if (is_dir($uploadDir)) {
    echo "✅ Upload directory exists: $uploadDir\n";
    
    if (is_writable($uploadDir)) {
        echo "✅ Upload directory is writable\n";
    } else {
        echo "❌ Upload directory is NOT writable\n";
    }
    
    $files = glob($uploadDir . '*');
    echo "   Files in directory: " . count($files) . "\n";
} else {
    echo "❌ Upload directory does NOT exist: $uploadDir\n";
}

// Check 5: Recent System Activity
echo "\n5. CHECKING RECENT ACTIVITY...\n";

try {
    // Recent uploads
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM practice_uploads 
        WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 24 HOURS)
    ");
    $recentUploads = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Recent uploads (24 hours): $recentUploads\n";
    
    // Recent validations
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM craft_image_validation 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOURS)
    ");
    $recentValidations = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Recent validations (24 hours): $recentValidations\n";
    
    // Validation status breakdown
    $stmt = $pdo->query("
        SELECT validation_status, COUNT(*) as count
        FROM craft_image_validation 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOURS)
        GROUP BY validation_status
    ");
    
    echo "Validation results (24 hours):\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   {$row['validation_status']}: {$row['count']}\n";
    }
    
    // Auto-approval rate
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM practice_uploads 
        WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 24 HOURS)
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($stats['total'] > 0) {
        $approvalRate = round(($stats['approved'] / $stats['total']) * 100, 1);
        echo "\nUpload Status (24 hours):\n";
        echo "   Total: {$stats['total']}\n";
        echo "   Approved: {$stats['approved']} ({$approvalRate}%)\n";
        echo "   Pending: {$stats['pending']}\n";
        echo "   Rejected: {$stats['rejected']}\n";
        
        if ($approvalRate > 70) {
            echo "   ✅ Good auto-approval rate\n";
        } elseif ($stats['pending'] > $stats['approved']) {
            echo "   ⚠️  Many uploads still pending - auto-approval may not be working\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error checking activity: " . $e->getMessage() . "\n";
}

// Check 6: Test Auto-Approval
echo "\n6. TESTING AUTO-APPROVAL...\n";

$testImage = 'backend/uploads/practice/direct_1767783095_1_🎧.jpg';
if (file_exists($testImage)) {
    echo "Found test image: " . basename($testImage) . "\n";
    
    // Test the main upload API
    $apiUrl = 'http://localhost/my_little_thingz/backend/api/pro/practice-upload.php';
    
    // Create test upload
    $boundary = '----WebKitFormBoundary' . uniqid();
    $postData = '';
    
    $postData .= "--$boundary\r\n";
    $postData .= "Content-Disposition: form-data; name=\"email\"\r\n\r\n";
    $postData .= "soudhame52@gmail.com\r\n";
    
    $postData .= "--$boundary\r\n";
    $postData .= "Content-Disposition: form-data; name=\"tutorial_id\"\r\n\r\n";
    $postData .= "2\r\n";
    
    $postData .= "--$boundary\r\n";
    $postData .= "Content-Disposition: form-data; name=\"description\"\r\n\r\n";
    $postData .= "Auto-approval diagnostic test\r\n";
    
    $postData .= "--$boundary\r\n";
    $postData .= "Content-Disposition: form-data; name=\"practice_images[]\"; filename=\"" . basename($testImage) . "\"\r\n";
    $postData .= "Content-Type: " . mime_content_type($testImage) . "\r\n\r\n";
    $postData .= file_get_contents($testImage) . "\r\n";
    $postData .= "--$boundary--\r\n";
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: multipart/form-data; boundary=' . $boundary,
        'X-Tutorial-Email: soudhame52@gmail.com'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $responseData = json_decode($response, true);
        
        if ($responseData && isset($responseData['validation_summary'])) {
            $summary = $responseData['validation_summary'];
            
            echo "Test Upload Results:\n";
            echo "   Status: " . ($responseData['status'] ?? 'unknown') . "\n";
            echo "   Overall Status: " . ($summary['overall_status'] ?? 'unknown') . "\n";
            echo "   Requires Review: " . ($summary['requires_admin_review'] ? 'YES' : 'NO') . "\n";
            echo "   Upload ID: " . ($responseData['upload_id'] ?? 'none') . "\n";
            
            if ($summary['overall_status'] === 'approved' && !$summary['requires_admin_review']) {
                echo "   ✅ AUTO-APPROVAL IS WORKING!\n";
            } else {
                echo "   ⚠️  Auto-approval may not be working properly\n";
            }
        } else {
            echo "   ❌ Invalid response from upload API\n";
        }
    } else {
        echo "   ❌ Upload API test failed (HTTP $httpCode)\n";
    }
} else {
    echo "No test image available for auto-approval test\n";
}

// Check 7: Configuration
echo "\n7. CHECKING CONFIGURATION...\n";

// Check if AUTO_APPROVE_MODE is enabled
$validationServicePath = 'backend/services/CraftImageValidationService.php';
if (file_exists($validationServicePath)) {
    $content = file_get_contents($validationServicePath);
    
    if (strpos($content, 'AUTO_APPROVE_MODE = true') !== false) {
        echo "✅ AUTO_APPROVE_MODE is enabled (permissive for testing)\n";
    } elseif (strpos($content, 'AUTO_APPROVE_MODE = false') !== false) {
        echo "⚠️  AUTO_APPROVE_MODE is disabled (strict validation)\n";
    } else {
        echo "❓ AUTO_APPROVE_MODE setting not found\n";
    }
} else {
    echo "❌ CraftImageValidationService.php not found\n";
}

// Summary
echo "\n=== DIAGNOSTIC SUMMARY ===\n";

$issues = [];
$warnings = [];

// Critical issues that prevent auto-approval
if (!isset($pdo)) {
    $issues[] = "Database connection failed";
}

if ($httpCode !== 200) { // From AI service check
    $warnings[] = "AI services not fully operational (using fallback)";
}

if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
    $issues[] = "Upload directory issues";
}

// Provide final assessment
if (empty($issues)) {
    echo "🎉 SYSTEM STATUS: OPERATIONAL\n";
    echo "✅ Auto-approval system should be working correctly\n";
    
    if (!empty($warnings)) {
        echo "\n⚠️  WARNINGS:\n";
        foreach ($warnings as $warning) {
            echo "   - $warning\n";
        }
    }
    
    echo "\n📋 NEXT STEPS:\n";
    echo "1. Try uploading a practice image through the frontend\n";
    echo "2. Check if it gets auto-approved without admin review\n";
    echo "3. Verify learning progress is updated\n";
    
} else {
    echo "❌ SYSTEM STATUS: ISSUES FOUND\n";
    echo "The following issues need to be resolved:\n";
    foreach ($issues as $issue) {
        echo "   - $issue\n";
    }
    
    echo "\n🔧 TROUBLESHOOTING:\n";
    echo "1. Fix the issues listed above\n";
    echo "2. Restart AI services if needed\n";
    echo "3. Check file permissions\n";
    echo "4. Verify database tables exist\n";
}

echo "\n=== END OF DIAGNOSTIC ===\n";
?>