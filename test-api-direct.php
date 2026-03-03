<?php
/**
 * Direct test of the remove-background API endpoint
 */

// Create a simple test image (white square)
$testImageData = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAFUlEQVR42mP8/5+hnoEIwDiqkL4KAcT9GO0U4BxoAAAAAElFTkSuQmCC';

echo "🧪 Testing Remove Background API Directly\n";
echo "=========================================\n\n";

// Simulate the API call
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['image_base64'] = $testImageData;

// Capture output
ob_start();

// Include the API file
include __DIR__ . '/backend/api/admin/remove-background.php';

$output = ob_get_clean();

echo "API Response:\n";
echo $output . "\n";

// Parse the JSON response
$response = json_decode($output, true);

if ($response) {
    echo "\nParsed Response:\n";
    echo "Success: " . ($response['success'] ? 'true' : 'false') . "\n";
    
    if (isset($response['error'])) {
        echo "Error: " . $response['error'] . "\n";
    }
    
    if (isset($response['fallback'])) {
        echo "Fallback: " . ($response['fallback'] ? 'true' : 'false') . "\n";
    }
    
    if (isset($response['debug'])) {
        echo "Debug info:\n";
        foreach ($response['debug'] as $key => $value) {
            echo "  $key: " . var_export($value, true) . "\n";
        }
    }
    
    if (isset($response['image'])) {
        echo "Image returned: " . (strlen($response['image']) > 100 ? 'Yes (' . strlen($response['image']) . ' chars)' : 'No') . "\n";
    }
} else {
    echo "❌ Failed to parse JSON response\n";
}

echo "\n";
?>