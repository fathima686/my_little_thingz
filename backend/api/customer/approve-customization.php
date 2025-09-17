<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once '../../config/database.php';

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
  }

  $database = new Database();
  $db = $database->getConnection();

  // Resolve user_id
  $user_id = null;
  if (isset($_SERVER['HTTP_X_USER_ID']) && $_SERVER['HTTP_X_USER_ID'] !== '') { $user_id = $_SERVER['HTTP_X_USER_ID']; }
  if (!$user_id && function_exists('getallheaders')) {
    foreach (getallheaders() as $k => $v) { if (strtolower($k) === 'x-user-id' && $v !== '') { $user_id = $v; break; } }
  }
  if (!$user_id && isset($_GET['user_id']) && $_GET['user_id'] !== '') { $user_id = $_GET['user_id']; }

  $input = json_decode(file_get_contents('php://input'), true) ?: [];
  if (!$user_id && !empty($input['user_id'])) { $user_id = $input['user_id']; }
  if (!$user_id) { echo json_encode(['status' => 'error', 'message' => 'User ID required']); exit; }

  $request_id = isset($input['request_id']) ? (int)$input['request_id'] : 0;

  if ($request_id > 0) {
    // Approve a specific request if it belongs to user and is pending
    $stmt = $db->prepare("UPDATE custom_requests SET status='completed' WHERE id=? AND user_id=? AND status='pending'");
    $stmt->execute([$request_id, $user_id]);
    $count = $stmt->rowCount();
  } else {
    // Approve all pending cart-originated requests for this user
    $stmt = $db->prepare("UPDATE custom_requests SET status='completed' WHERE user_id=? AND status='pending'");
    // Note: Using broad approval for demo; tighten with source='cart' if column present
    // Try to check if 'source' column exists
    try {
      $chk = $db->query("SHOW COLUMNS FROM custom_requests LIKE 'source'");
      if ($chk && $chk->rowCount() > 0) {
        $stmt = $db->prepare("UPDATE custom_requests SET status='completed' WHERE user_id=? AND status='pending' AND source='cart'");
      }
    } catch (Throwable $e) {}
    $stmt->execute([$user_id]);
    $count = $stmt->rowCount();
  }

  echo json_encode(['status' => 'success', 'approved' => $count]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Approval failed', 'detail' => $e->getMessage()]);
}
?>


