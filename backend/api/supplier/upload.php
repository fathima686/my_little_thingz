<?php
// Supplier image upload endpoint
// Accepts multipart/form-data with field "image"

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-SUPPLIER-ID");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(["status"=>"error","message"=>"Method not allowed"]);
  exit;
}

$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : (int)($_SERVER['HTTP_X_SUPPLIER_ID'] ?? 0);
if ($supplier_id <= 0) {
  http_response_code(401);
  echo json_encode(["status"=>"error","message"=>"Missing supplier_id"]);
  exit;
}

if (!isset($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
  http_response_code(400);
  echo json_encode(["status"=>"error","message"=>"No image uploaded"]);
  exit;
}

$err = $_FILES['image']['error'];
if ($err !== UPLOAD_ERR_OK) {
  http_response_code(400);
  echo json_encode(["status"=>"error","message"=>"Upload error code: " . $err]);
  exit;
}

$allowed = [
  'image/jpeg' => 'jpg',
  'image/png'  => 'png',
  'image/webp' => 'webp'
];
$mime = mime_content_type($_FILES['image']['tmp_name']);
if (!isset($allowed[$mime])) {
  http_response_code(422);
  echo json_encode(["status"=>"error","message"=>"Only JPG, PNG, WEBP allowed"]);
  exit;
}

$maxBytes = 5 * 1024 * 1024; // 5MB
if ($_FILES['image']['size'] > $maxBytes) {
  http_response_code(413);
  echo json_encode(["status"=>"error","message"=>"File too large (max 5MB)"]);
  exit;
}

$ext = $allowed[$mime];
$baseDir = __DIR__ . '/../../uploads/supplier-products/' . $supplier_id;
if (!is_dir($baseDir)) {
  // Ensure nested directories
  @mkdir($baseDir, 0777, true);
}

$rand = bin2hex(random_bytes(6));
$fname = 'sp_' . date('Ymd_His') . '_' . $rand . '.' . $ext;
$targetPath = $baseDir . '/' . $fname;

if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
  http_response_code(500);
  echo json_encode(["status"=>"error","message"=>"Failed to save file"]);
  exit;
}

// Build public URL assuming Apache serves /my_little_thingz from project root
$publicUrl = sprintf(
  '%s/my_little_thingz/backend/uploads/supplier-products/%d/%s',
  (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
  $supplier_id,
  $fname
);

$publicPath = sprintf('/my_little_thingz/backend/uploads/supplier-products/%d/%s', $supplier_id, $fname);

echo json_encode(["status"=>"success","url"=>$publicUrl, "path"=>$publicPath, "file"=>$fname]);