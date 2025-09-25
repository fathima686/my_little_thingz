<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once '../../config/database.php';

try {
  // This endpoint is disabled to enforce admin-only approval
  http_response_code(403);
  echo json_encode(['status' => 'error', 'message' => 'Customers cannot approve customization. Admin approval required.']);
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Approval failed', 'detail' => $e->getMessage()]);
}
?>


