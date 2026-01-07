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
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-User-Id, X-Admin-Email');

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

  // Ensure reviews table exists (customer endpoint also creates it)
  $db->exec("CREATE TABLE IF NOT EXISTS reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    artwork_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    admin_reply TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_artwork (user_id, artwork_id),
    INDEX idx_status_created (status, created_at),
    CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_artwork FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

  if ($method === 'GET') {
    $status = isset($_GET['status']) ? strtolower(trim((string)$_GET['status'])) : '';
    if (!in_array($status, ['pending','approved','rejected'], true)) { $status = ''; }
    $artworkId = isset($_GET['artwork_id']) ? (int)$_GET['artwork_id'] : 0;
    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 50;
    $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

    $where = [];
    $params = [];
    if ($status !== '') { $where[] = 'r.status = :status'; $params[':status'] = $status; }
    if ($artworkId > 0) { $where[] = 'r.artwork_id = :aid'; $params[':aid'] = $artworkId; }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $sql = "SELECT r.id, r.user_id, r.artwork_id, r.rating, r.comment, r.status, r.admin_reply, r.created_at,
                   a.title AS artwork_title
            FROM reviews r
            LEFT JOIN artworks a ON a.id = r.artwork_id
            $whereSql
            ORDER BY r.created_at DESC
            LIMIT :lim OFFSET :off";
    $stmt = $db->prepare($sql);
    foreach ($params as $k=>$v) {
      $stmt->bindValue($k, $v, $k === ':aid' ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    respond(['status'=>'success','items'=>$rows]);
  }

  if ($method === 'PATCH' || $method === 'PUT' || $method === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $b = [];
    if (stripos($contentType, 'application/json') !== false) {
      $b = json_decode(file_get_contents('php://input'), true) ?: [];
    } else {
      $b = $_POST;
    }

    $id = (int)($b['id'] ?? 0);
    if ($id <= 0) { respond(['status'=>'error','message'=>'id is required'], 422); }

    $fields = [];
    $params = [':id' => $id];

    if (isset($b['status'])) {
      $st = strtolower(trim((string)$b['status']));
      if (!in_array($st, ['pending','approved','rejected'], true)) {
        respond(['status'=>'error','message'=>'invalid status'], 422);
      }
      $fields[] = 'status = :status';
      $params[':status'] = $st;
    }
    if (array_key_exists('admin_reply', $b)) {
      $reply = trim((string)$b['admin_reply']);
      $fields[] = 'admin_reply = :reply';
      $params[':reply'] = $reply === '' ? null : $reply;
    }

    if (!$fields) { respond(['status'=>'error','message'=>'no fields to update'], 422); }

    $sql = 'UPDATE reviews SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $stmt = $db->prepare($sql);
    foreach ($params as $k=>$v) {
      $stmt->bindValue($k, $v, ($k === ':id') ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    respond(['status'=>'success']);
  }

  respond(['status'=>'error','message'=>'Method not allowed'], 405);
} catch (Throwable $e) {
  respond(['status'=>'error','message'=>'Admin reviews op failed','detail'=>$e->getMessage()], 500);
}



