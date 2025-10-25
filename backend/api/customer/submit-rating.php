<?php
header('Content-Type: application/json');

// CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['http://localhost:5173', 'http://127.0.0.1:5173'];
if (in_array($origin, $allowed, true)) {
  header("Access-Control-Allow-Origin: $origin");
} else {
  header('Access-Control-Allow-Origin: *');
}
header('Vary: Origin');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

require_once __DIR__ . '/../../config/database.php';

function respond($data, $code = 200) {
  http_response_code($code);
  echo json_encode($data);
  exit;
}

try {
  $db = (new Database())->getConnection();

  // Ensure reviews table exists
  $db->exec("CREATE TABLE IF NOT EXISTS reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    artwork_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    admin_reply TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_artwork (user_id, artwork_id),
    INDEX idx_artwork_status (artwork_id, status),
    CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_artwork FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['status'=>'error','message'=>'Method not allowed'], 405);
  }

  $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
  $input = [];
  if (stripos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
  } else {
    $input = $_POST;
  }

  // Get user ID from header or body
  $userIdHeader = isset($_SERVER['HTTP_X_USER_ID']) ? (int)$_SERVER['HTTP_X_USER_ID'] : 0;
  $userId = (int)($input['user_id'] ?? 0);
  if ($userId <= 0 && $userIdHeader > 0) { $userId = $userIdHeader; }

  $artworkId = (int)($input['artwork_id'] ?? 0);
  $rating = (int)($input['rating'] ?? 0);
  $feedback = isset($input['feedback']) ? trim((string)$input['feedback']) : null;
  $isAnonymous = !empty($input['is_anonymous']);

  if ($userId <= 0 || $artworkId <= 0 || $rating < 1 || $rating > 5) {
    respond(['status'=>'error','message'=>'user_id, artwork_id and rating (1-5) are required'], 422);
  }

  // Optional: check if user has delivered order for this artwork
  $eligible = true;
  try {
    $hasOrders = $db->query("SHOW TABLES LIKE 'orders'")->fetch();
    $hasOrderItems = $db->query("SHOW TABLES LIKE 'order_items'")->fetch();
    if ($hasOrders && $hasOrderItems) {
      $eligStmt = $db->prepare("SELECT 1 FROM orders o
                                JOIN order_items oi ON oi.order_id = o.id
                                WHERE o.user_id = :uid AND oi.artwork_id = :aid AND o.status IN ('delivered','completed')
                                LIMIT 1");
      $eligStmt->bindValue(':uid', $userId, PDO::PARAM_INT);
      $eligStmt->bindValue(':aid', $artworkId, PDO::PARAM_INT);
      $eligStmt->execute();
      $eligible = (bool)$eligStmt->fetchColumn();
    }
  } catch (Throwable $e) {
    $eligible = true; // Don't block if schema differs
  }

  if (!$eligible) {
    respond(['status'=>'error','message'=>'Only customers who received the product can review'], 403);
  }

  // Upsert review
  $stmt = $db->prepare("INSERT INTO reviews (user_id, artwork_id, rating, comment)
                        VALUES (:uid, :aid, :rating, :comment)
                        ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), status = 'pending', updated_at = CURRENT_TIMESTAMP");
  $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
  $stmt->bindValue(':aid', $artworkId, PDO::PARAM_INT);
  $stmt->bindValue(':rating', $rating, PDO::PARAM_INT);
  $stmt->bindValue(':comment', $feedback, PDO::PARAM_STR);
  $stmt->execute();

  respond(['status'=>'success','message'=>'Rating submitted successfully','moderation'=>'pending']);
} catch (Throwable $e) {
  respond(['status'=>'error','message'=>'Failed to submit rating','detail'=>$e->getMessage()], 500);
}