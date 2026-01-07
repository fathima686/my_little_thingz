<?php
// Create Sample Images for Custom Requests
echo "<!DOCTYPE html>";
echo "<html><head><title>Create Sample Images</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} .container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:8px;} .success{color:#10b981;} .error{color:#ef4444;}</style>";
echo "</head><body><div class='container'>";

echo "<h1>🖼️ Create Sample Images for Custom Requests</h1>";

try {
    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/uploads/custom-requests/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "<p class='success'>✓ Created uploads directory</p>";
    }
    
    // Create simple colored placeholder images
    $imageData = [
        ['cr_1_wedding.jpg', '#FFB6C1', 'Wedding Gift'],
        ['cr_2_baby.jpg', '#E6E6FA', 'Baby Gift'],
        ['cr_3_corporate.jpg', '#B0E0E6', 'Corporate Award'],
        ['cr_4_memorial.jpg', '#F0E68C', 'Pet Memorial']
    ];
    
    foreach ($imageData as $data) {
        $filename = $data[0];
        $color = $data[1];
        $text = $data[2];
        $filepath = $uploadDir . $filename;
        
        // Create a simple colored image with text
        $width = 300;
        $height = 200;
        $image = imagecreate($width, $height);
        
        // Convert hex color to RGB
        $hex = str_replace('#', '', $color);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        $bgColor = imagecolorallocate($image, $r, $g, $b);
        $textColor = imagecolorallocate($image, 0, 0, 0);
        
        // Add text to image
        $font = 5;
        $textWidth = imagefontwidth($font) * strlen($text);
        $textHeight = imagefontheight($font);
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2;
        
        imagestring($image, $font, $x, $y, $text, $textColor);
        
        // Save as JPEG
        imagejpeg($image, $filepath, 90);
        imagedestroy($image);
        
        echo "<p class='success'>✓ Created: $filename</p>";
    }
    
    echo "<h2 class='success'>✅ Sample Images Created!</h2>";
    echo "<p>Sample images have been created in: <code>$uploadDir</code></p>";
    
    // Test the images
    echo "<h3>🧪 Test Images:</h3>";
    foreach ($imageData as $data) {
        $filename = $data[0];
        $webPath = 'http://localhost/my_little_thingz/backend/uploads/custom-requests/' . $filename;
        echo "<p><img src='$webPath' alt='$filename' style='width:150px;height:100px;margin:5px;border:1px solid #ccc;'> $filename</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
?>