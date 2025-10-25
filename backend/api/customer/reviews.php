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
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

  // Ensure schema
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

  $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

  if ($method === 'GET') {
    $artworkId = isset($_GET['artwork_id']) ? (int)$_GET['artwork_id'] : 0;
    if ($artworkId <= 0) {
      respond(['status'=>'error','message'=>'artwork_id is required'], 422);
    }

    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 20;
    $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

    // Only approved reviews visible to customers
    $stmt = $db->prepare("SELECT r.id, r.user_id, r.artwork_id, r.rating, r.comment, r.admin_reply, r.created_at
                          FROM reviews r
                          WHERE r.artwork_id = :aid AND r.status = 'approved'
                          ORDER BY r.created_at DESC
                          LIMIT :lim OFFSET :off");
    $stmt->bindValue(':aid', $artworkId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $aggStmt = $db->prepare("SELECT COUNT(*) as count, AVG(rating) as avg_rating
                             FROM reviews WHERE artwork_id = :aid AND status = 'approved'");
    $aggStmt->bindValue(':aid', $artworkId, PDO::PARAM_INT);
    $aggStmt->execute();
    $agg = $aggStmt->fetch(PDO::FETCH_ASSOC) ?: ['count'=>0,'avg_rating'=>null];

    respond(['status'=>'success','items'=>$rows,'total'=>(int)$agg['count'],'avg_rating'=>$agg['avg_rating'] ? round((float)$agg['avg_rating'], 2) : null]);
  }

  if ($method === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $input = [];
    if (stripos($contentType, 'application/json') !== false) {
      $input = json_decode(file_get_contents('php://input'), true) ?: [];
    } else {
      $input = $_POST;
    }

    $userIdHeader = isset($_SERVER['HTTP_X_USER_ID']) ? (int)$_SERVER['HTTP_X_USER_ID'] : 0;
    $userId = (int)($input['user_id'] ?? 0);
    if ($userId <= 0 && $userIdHeader > 0) { $userId = $userIdHeader; }

    $artworkId = (int)($input['artwork_id'] ?? 0);
    $rating = (int)($input['rating'] ?? 0);
    $comment = isset($input['comment']) ? trim((string)$input['comment']) : null;

    if ($userId <= 0 || $artworkId <= 0 || $rating < 1 || $rating > 5) {
      respond(['status'=>'error','message'=>'user_id, artwork_id and rating (1-5) are required'], 422);
    }

    // Optional: basic purchase check if orders tables exist
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
      $eligible = true; // Do not block if schema differs
    }

    if (!$eligible) {
      respond(['status'=>'error','message'=>'Only customers who received the product can review'], 403);
    }

    // Upsert review: if user already reviewed, update rating/comment and reset status to pending
    // Ensure unique key exists from table creation
    $stmt = $db->prepare("INSERT INTO reviews (user_id, artwork_id, rating, comment)
                          VALUES (:uid, :aid, :rating, :comment)
                          ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), status = 'pending', updated_at = CURRENT_TIMESTAMP");
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':aid', $artworkId, PDO::PARAM_INT);
    $stmt->bindValue(':rating', $rating, PDO::PARAM_INT);
    $stmt->bindValue(':comment', $comment, PDO::PARAM_STR);
    $stmt->execute();

    respond(['status'=>'success','message'=>'Review submitted','moderation'=>'pending']);
  }

  respond(['status'=>'error','message'=>'Method not allowed'], 405);
} catch (Throwable $e) {
  respond(['status'=>'error','message'=>'Failed to process request','detail'=>$e->getMessage()], 500);
}














