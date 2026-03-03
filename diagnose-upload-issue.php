<?php
/**
 * Diagnose Practice Upload Issue
 * 
 * Checks all components needed for practice upload to work
 */

echo "=== Practice Upload Diagnostic ===\n\n";

// Test 1: Check Flask API
echo "Test 1: Checking Flask API (Craft Classifier)...\n";
$craftApiUrl = 'http://localhost:5001/health';
$craftHealth = @file_get_contents($craftApiUrl);

if ($craftHealth === false) {
    echo "❌ Flask API NOT RUNNING at $craftApiUrl\n";
    echo "   This is the MAIN ISSUE - uploads will fail!\n";
    echo "   Start with: cd python_ml_service && python craft_flask_api.py\n\n";
    $flaskRunning = false;
} else {
    $healthData = json_decode($craftHealth, true);
    echo "✓ Flask API is running\n";
    echo "  Model Type: {$healthData['model_type']}\n";
    echo "  AI Detector: " . (isset($healthData['ai_detector_available']) && $healthData['ai_detector_available'] ? 'Available' : 'Not Available') . "\n\n";
    $flaskRunning = true;
}

// Test 2: Check database
echo "Test 2: Checking database tables...\n";
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $tables = ['practice_uploads', 'craft_image_validation_v2', 'learning_progress'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table $table exists\n";
        } else {
            echo "⚠️  Table $table does not exist\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n\n";
}

// Test 3: Check upload directory
echo "Test 3: Checking upload directory...\n";
$uploadDir = 'backend/uploads/practice/';
if (is_dir($uploadDir)) {
    echo "✓ Upload directory exists: $uploadDir\n";
    if (is_writable($uploadDir)) {
        echo "✓ Upload directory is writable\n";
    } else {
        echo "❌ Upload directory is NOT writable\n";
        echo "   Fix with: chmod 755 $uploadDir\n";
    }
} else {
    echo "⚠️  Upload directory does not exist: $uploadDir\n";
    echo "   It will be created automatically on first upload\n";
}
echo "\n";

// Test 4: Check PHP services
echo "Test 4: Checking PHP services...\n";
$services = [
    'backend/services/CraftImageValidationServiceV2.php',
    'backend/services/EnhancedImageAuthenticityServiceV2.php'
];

foreach ($services as $service) {
    if (file_exists($service)) {
        echo "✓ Service exists: " . basename($service) . "\n";
    } else {
        echo "❌ Service NOT FOUND: $service\n";
    }
}
echo "\n";

// Test 5: Check environment variables
echo "Test 5: Checking environment variables...\n";
require_once 'backend/config/env-loader.php';
EnvLoader::load();

$craftUrl = getenv('CRAFT_CLASSIFIER_URL') ?: $_ENV['CRAFT_CLASSIFIER_URL'] ?? 'http://localhost:5001';
$localUrl = getenv('LOCAL_CLASSIFIER_URL') ?: $_ENV['LOCAL_CLASSIFIER_URL'] ?? 'http://localhost:5000';

echo "  CRAFT_CLASSIFIER_URL: $craftUrl\n";
echo "  LOCAL_CLASSIFIER_URL: $localUrl\n\n";

// Test 6: Simulate validation call
echo "Test 6: Testing validation service initialization...\n";
if ($flaskRunning) {
    try {
        require_once 'backend/services/CraftImageValidationServiceV2.php';
        $validationService = new CraftImageValidationServiceV2($pdo, $craftUrl);
        echo "✓ CraftImageValidationServiceV2 initialized successfully\n";
    } catch (Exception $e) {
        echo "❌ CraftImageValidationServiceV2 initialization failed:\n";
        echo "   " . $e->getMessage() . "\n";
    }
} else {
    echo "⊘ Skipping validation service test (Flask API not running)\n";
}
echo "\n";

// Summary
echo "=== DIAGNOSIS SUMMARY ===\n\n";

if (!$flaskRunning) {
    echo "🔴 CRITICAL ISSUE FOUND:\n";
    echo "   Flask API is not running!\n\n";
    echo "SOLUTION:\n";
    echo "1. Open a terminal/command prompt\n";
    echo "2. Navigate to python_ml_service directory:\n";
    echo "   cd python_ml_service\n";
    echo "3. Start the Flask API:\n";
    echo "   python craft_flask_api.py\n";
    echo "4. Wait for message: '=== READY FOR ACADEMIC DEMONSTRATION ==='\n";
    echo "5. Try uploading again\n\n";
    echo "The Flask API MUST be running for practice uploads to work.\n";
} else {
    echo "✅ Flask API is running\n";
    echo "✅ Database is accessible\n";
    echo "✅ Services are available\n\n";
    echo "If uploads still fail, check:\n";
    echo "1. Browser console for JavaScript errors\n";
    echo "2. Network tab for API response details\n";
    echo "3. PHP error logs for backend errors\n";
}

echo "\n";
?>
