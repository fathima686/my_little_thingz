<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['http://localhost:5173', 'http://127.0.0.1:5173', 'http://localhost'];
if (in_array($origin, $allowed, true)) { header("Access-Control-Allow-Origin: $origin"); } else { header("Access-Control-Allow-Origin: http://localhost:5173"); }
header('Vary: Origin');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// Very simple auth: require X-User-ID
$userId = (int)($_SERVER['HTTP_X_USER_ID'] ?? 0);
if (!$userId) {
  http_response_code(401);
  echo json_encode(['status' => 'error', 'message' => 'Missing user identity']);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
  exit;
}

if (!isset($_FILES['image'])) {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
  exit;
}

$file = $_FILES['image'];
if ($file['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'message' => 'Upload error code ' . $file['error']]);
  exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$ext = null;
switch ($mime) {
  case 'image/jpeg': $ext = 'jpg'; break;
  case 'image/png':  $ext = 'png'; break;
  case 'image/webp': $ext = 'webp'; break;
  default:
    http_response_code(415);
    echo json_encode(['status' => 'error', 'message' => 'Unsupported image type']);
    exit;
}

$targetDir = __DIR__ . '/../../uploads/profile-images';
if (!is_dir($targetDir)) { @mkdir($targetDir, 0775, true); }

// Remove any existing images for this user
foreach (['jpg','jpeg','png','webp'] as $e) {
  $p = $targetDir . "/user_{$userId}.{$e}";
  if (file_exists($p)) @unlink($p);
}

$targetPath = $targetDir . "/user_{$userId}.{$ext}";
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Failed to save file']);
  exit;
}

$rel = '/my_little_thingz/backend/uploads/profile-images/' . "user_{$userId}.{$ext}";
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$publicUrl = $scheme . '://' . $host . $rel . '?v=' . filemtime($targetPath);
echo json_encode(['status' => 'success', 'url' => $publicUrl]);