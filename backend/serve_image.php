<?php
// Serve an image directly
$imagePath = isset($_GET['path']) ? $_GET['path'] : '';
$fullPath = __DIR__ . '/' . $imagePath;

if (file_exists($fullPath) && is_readable($fullPath)) {
    $mime = mime_content_type($fullPath);
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit;
} else {
    http_response_code(404);
    echo 'Image not found';
}
?>