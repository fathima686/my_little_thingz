<?php
// Create placeholder image for custom requests
$uploadDir = __DIR__ . '/uploads/custom-requests/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Create a simple SVG placeholder
$placeholderSvg = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="200" height="150" xmlns="http://www.w3.org/2000/svg">
  <rect width="200" height="150" fill="#f0f0f0" stroke="#ccc" stroke-width="2"/>
  <text x="100" y="75" text-anchor="middle" font-family="Arial" font-size="14" fill="#666">
    No Image Uploaded
  </text>
  <text x="100" y="95" text-anchor="middle" font-family="Arial" font-size="12" fill="#999">
    Placeholder Image
  </text>
</svg>';

$placeholderPath = $uploadDir . 'placeholder.svg';
if (file_put_contents($placeholderPath, $placeholderSvg)) {
    echo "Created placeholder image: $placeholderPath<br>";
} else {
    echo "Failed to create placeholder image<br>";
}
?>