<?php
// Check what images are actually uploaded
echo "<h2>Checking Uploaded Images</h2>";

$uploadDir = __DIR__ . '/uploads/custom-requests/';

echo "<p><strong>Upload Directory:</strong> $uploadDir</p>";

if (!is_dir($uploadDir)) {
    echo "<p style='color: red;'>❌ Upload directory does not exist</p>";
    echo "<p>Creating directory...</p>";
    
    if (mkdir($uploadDir, 0755, true)) {
        echo "<p style='color: green;'>✅ Directory created</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create directory</p>";
        exit;
    }
} else {
    echo "<p style='color: green;'>✅ Upload directory exists</p>";
}

// List all files in the directory
$files = scandir($uploadDir);
$imageFiles = [];

echo "<h3>Files in Upload Directory:</h3>";

if (count($files) <= 2) { // Only . and ..
    echo "<p style='color: orange;'>⚠️ No files found in upload directory</p>";
    echo "<p>Let me create some test images...</p>";
    
    // Create test images
    for ($i = 1; $i <= 3; $i++) {
        $filename = "test-image-$i.jpg";
        $filepath = $uploadDir . $filename;
        
        // Create a simple colored rectangle as test image
        $image = imagecreate(300, 200);
        $colors = [
            imagecolorallocate($image, 255, 100, 100), // Red
            imagecolorallocate($image, 100, 255, 100), // Green  
            imagecolorallocate($image, 100, 100, 255)  // Blue
        ];
        
        $bg_color = $colors[$i - 1];
        $text_color = imagecolorallocate($image, 255, 255, 255);
        
        imagefill($image, 0, 0, $bg_color);
        
        $text = "Test Image $i";
        $font_size = 5;
        $text_width = imagefontwidth($font_size) * strlen($text);
        $text_height = imagefontheight($font_size);
        $x = (300 - $text_width) / 2;
        $y = (200 - $text_height) / 2;
        
        imagestring($image, $font_size, $x, $y, $text, $text_color);
        
        if (imagejpeg($image, $filepath, 80)) {
            echo "<p style='color: green;'>✅ Created: $filename</p>";
            $imageFiles[] = $filename;
        } else {
            echo "<p style='color: red;'>❌ Failed to create: $filename</p>";
        }
        
        imagedestroy($image);
    }
} else {
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filepath = $uploadDir . $file;
            $size = filesize($filepath);
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $imageFiles[] = $file;
                echo "<p style='color: green;'>📷 $file (" . number_format($size) . " bytes)</p>";
            } else {
                echo "<p style='color: gray;'>📄 $file (" . number_format($size) . " bytes) - Not an image</p>";
            }
        }
    }
}

echo "<h3>Summary:</h3>";
echo "<p><strong>Total Image Files:</strong> " . count($imageFiles) . "</p>";

if (count($imageFiles) > 0) {
    echo "<h4>Image Gallery:</h4>";
    echo "<div style='display: flex; gap: 10px; flex-wrap: wrap;'>";
    
    foreach ($imageFiles as $file) {
        $url = "http://localhost/my_little_thingz/backend/uploads/custom-requests/$file";
        echo "<div style='border: 1px solid #ddd; padding: 10px; border-radius: 8px;'>";
        echo "<img src='$url' alt='$file' style='width: 150px; height: 100px; object-fit: cover; border-radius: 4px;'><br>";
        echo "<small>$file</small>";
        echo "</div>";
    }
    
    echo "</div>";
    
    echo "<h4>API URLs:</h4>";
    echo "<ul>";
    foreach ($imageFiles as $file) {
        $url = "http://localhost/my_little_thingz/backend/uploads/custom-requests/$file";
        echo "<li><a href='$url' target='_blank'>$url</a></li>";
    }
    echo "</ul>";
    
    echo "<h4>✅ These images should now appear in the admin dashboard!</h4>";
    echo "<p><a href='test-all-custom-request-apis.html'>Test the API</a></p>";
} else {
    echo "<p style='color: red;'>❌ No images found. Upload some images first.</p>";
}
?>