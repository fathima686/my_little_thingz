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
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

  // Get user ID from header
  $userId = isset($_SERVER['HTTP_X_USER_ID']) ? (int)$_SERVER['HTTP_X_USER_ID'] : 0;
  if ($userId <= 0) {
    respond(['status'=>'error','message'=>'User ID required'], 401);
  }

  // Check if orders and order_items tables exist
  $hasOrders = $db->query("SHOW TABLES LIKE 'orders'")->fetch();
  $hasOrderItems = $db->query("SHOW TABLES LIKE 'order_items'")->fetch();
  
  if (!$hasOrders || !$hasOrderItems) {
    respond(['status'=>'success','orders'=>[]]);
  }

  // Get delivered orders with their items that can be rated
  $stmt = $db->prepare("
    SELECT DISTINCT
      o.id as order_id,
      o.order_number,
      o.status as order_status,
      o.created_at as order_date,
      oi.artwork_id,
      a.title as artwork_title,
      a.image_url,
      a.price as artwork_price,
      oi.quantity,
      oi.price as item_price
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    JOIN artworks a ON a.id = oi.artwork_id
    WHERE o.user_id = :user_id 
      AND o.status IN ('delivered', 'completed')
      AND a.status = 'active'
    ORDER BY o.created_at DESC
  ");
  
  $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Group by order and format for frontend
  $orders = [];
  foreach ($rows as $row) {
    $orderId = $row['order_id'];
    if (!isset($orders[$orderId])) {
      $orders[$orderId] = [
        'id' => (int)$row['order_id'],
        'order_number' => $row['order_number'],
        'status' => $row['order_status'],
        'created_at' => $row['order_date'],
        'items' => []
      ];
    }
    
    $orders[$orderId]['items'][] = [
      'artwork_id' => (int)$row['artwork_id'],
      'artwork_title' => $row['artwork_title'],
      'image_url' => $row['image_url'],
      'price' => (float)$row['artwork_price'],
      'quantity' => (int)$row['quantity'],
      'item_price' => (float)$row['item_price']
    ];
  }

  // Convert to array and add delivered_at (using created_at for now)
  $orderList = array_values($orders);
  foreach ($orderList as &$order) {
    $order['delivered_at'] = $order['created_at']; // Assuming delivered when status is delivered
  }

  respond(['status'=>'success','orders'=>$orderList]);

} catch (Throwable $e) {
  respond(['status'=>'error','message'=>'Failed to fetch rateable orders','detail'=>$e->getMessage()], 500);
}