<?php
// Test if images are accessible
$testImage = 'uploads/custom-requests/68a94aee4130e3.31514105_gift_box.png';
$fullPath = __DIR__ . '/' . $testImage;

echo "Testing image access:\n";
echo "Full path: $fullPath\n";
echo "File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
echo "Is readable: " . (is_readable($fullPath) ? 'YES' : 'NO') . "\n";

if (file_exists($fullPath)) {
    echo "File size: " . filesize($fullPath) . " bytes\n";
    echo "File permissions: " . substr(sprintf('%o', fileperms($fullPath)), -4) . "\n";
}

// Test URL construction
$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
$imageUrl = $baseUrl . '/' . $testImage;
echo "Image URL: $imageUrl\n";
?>