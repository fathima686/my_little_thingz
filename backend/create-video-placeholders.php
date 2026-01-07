<?php
// Create placeholder video files for testing
$uploadDir = 'uploads/tutorials/videos/';
$thumbDir = 'uploads/tutorials/';

// Create directories if they don't exist
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    echo "Created video directory: $uploadDir\n";
}

if (!file_exists($thumbDir)) {
    mkdir($thumbDir, 0755, true);
    echo "Created thumbnail directory: $thumbDir\n";
}

// Create placeholder HTML files that will display a message instead of actual videos
$placeholderVideos = [
    'video_kitkat_bouquet.mp4' => 'Kitkat Chocolate Bouquets Tutorial',
    'video_earring.mp4' => 'Earring Making Tutorial',
    'video_ring.mp4' => 'Ring Making Tutorial',
    'video_ring_advanced.mp4' => 'Advanced Ring Making Tutorial'
];

$placeholderThumbs = [
    'thumb_kitkat_bouquet.jpg' => 'Kitkat Bouquet Thumbnail',
    'thumb_earring.jpg' => 'Earring Thumbnail',
    'thumb_ring.jpg' => 'Ring Thumbnail',
    'thumb_ring_advanced.jpg' => 'Advanced Ring Thumbnail'
];

// Create placeholder video HTML files
foreach ($placeholderVideos as $filename => $title) {
    $htmlContent = "<!DOCTYPE html>
<html>
<head>
    <title>$title</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f0f0f0; }
        .placeholder { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .video-icon { font-size: 64px; color: #007cba; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class='placeholder'>
        <div class='video-icon'>🎥</div>
        <h2>$title</h2>
        <p>This is a placeholder for the tutorial video.</p>
        <p>In a real implementation, this would be an actual video file.</p>
    </div>
</body>
</html>";
    
    file_put_contents($uploadDir . $filename, $htmlContent);
    echo "Created placeholder video: $filename\n";
}

// Create placeholder thumbnail files (simple HTML)
foreach ($placeholderThumbs as $filename => $title) {
    $htmlContent = "<!DOCTYPE html>
<html>
<head>
    <title>$title</title>
    <style>
        body { margin: 0; padding: 20px; background: linear-gradient(45deg, #007cba, #005a87); color: white; text-align: center; font-family: Arial, sans-serif; }
        .thumb { width: 300px; height: 200px; display: flex; align-items: center; justify-content: center; flex-direction: column; }
    </style>
</head>
<body>
    <div class='thumb'>
        <div style='font-size: 48px; margin-bottom: 10px;'>🖼️</div>
        <div>$title</div>
    </div>
</body>
</html>";
    
    file_put_contents($thumbDir . $filename, $htmlContent);
    echo "Created placeholder thumbnail: $filename\n";
}

echo "\nAll placeholder files created successfully!\n";
echo "Note: These are HTML placeholders. In production, you would upload actual video and image files.\n";
?>