<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_origins = ['http://localhost:5173', 'http://127.0.0.1:5173'];
if (in_array($origin, $allowed_origins, true)) {
  header("Access-Control-Allow-Origin: $origin");
} else {
  header("Access-Control-Allow-Origin: http://localhost:5173");
}
header("Vary: Origin");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-User-Id");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli("localhost", "root", "", "my_little_thingz");
$mysqli->set_charset('utf8mb4');
if ($mysqli->connect_error) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "DB connect failed: " . $mysqli->connect_error]);
  exit;
}

function ensure_schema(mysqli $db) {
  // Ensure roles, user_roles exist (similar to suppliers endpoint)
  $db->query("CREATE TABLE IF NOT EXISTS roles (id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL UNIQUE) ENGINE=InnoDB");
  $db->query("INSERT IGNORE INTO roles (id, name) VALUES (1,'admin'),(2,'customer'),(3,'supplier')");
  $db->query("CREATE TABLE IF NOT EXISTS user_roles (user_id INT UNSIGNED NOT NULL, role_id TINYINT UNSIGNED NOT NULL, PRIMARY KEY (user_id, role_id)) ENGINE=InnoDB");

  // Ensure custom_requests table has 'occasion' column
  $sql = "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='custom_requests' AND COLUMN_NAME='occasion'";
  $res = $db->query($sql);
  $row = $res->fetch_assoc();
  if ((int)$row['c'] === 0) {
    try { $db->query("ALTER TABLE custom_requests ADD COLUMN occasion VARCHAR(100) NULL AFTER category_id"); } catch (Throwable $e) {}
  }
}

try { ensure_schema($mysqli); } catch (Throwable $e) {}

// Admin auth
$adminUserId = isset($_SERVER['HTTP_X_ADMIN_USER_ID']) ? (int)$_SERVER['HTTP_X_ADMIN_USER_ID'] : 0;
if ($adminUserId <= 0) {
  http_response_code(401);
  echo json_encode(["status" => "error", "message" => "Missing admin identity"]);
  exit;
}

$isAdmin = false;
$chk = $mysqli->prepare("SELECT 1 FROM user_roles ur JOIN roles r ON ur.role_id=r.id WHERE ur.user_id=? AND r.name='admin' LIMIT 1");
$chk->bind_param('i', $adminUserId);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) { $isAdmin = true; }
$chk->close();

if (!$isAdmin) {
  http_response_code(403);
  echo json_encode(["status" => "error", "message" => "Not an admin user"]);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
  if ($method === 'GET') {
    $status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : 'pending';
    $allowed = ['pending','in_progress','completed','cancelled','all'];
    if (!in_array($status, $allowed, true)) { $status = 'pending'; }

    $sql = "SELECT cr.id, cr.user_id, u.first_name, u.last_name, u.email,
                   cr.title, cr.occasion, cr.description,
                   cr.category_id, c.name AS category_name,
                   cr.budget_min, cr.budget_max, cr.deadline,
                   cr.special_instructions, cr.status, cr.created_at,
                   (
                     SELECT COUNT(*) FROM custom_request_images cri WHERE cri.request_id=cr.id
                   ) AS images_count
            FROM custom_requests cr
            JOIN users u ON u.id=cr.user_id
            LEFT JOIN categories c ON c.id=cr.category_id";
    if ($status !== 'all') { $sql .= " WHERE cr.status=?"; }
    $sql .= " ORDER BY cr.created_at DESC";

    if ($status !== 'all') {
      $st = $mysqli->prepare($sql);
      $st->bind_param('s', $status);
    } else {
      $st = $mysqli->prepare($sql);
    }
    $st->execute();
    $res = $st->get_result();
    $rows = [];
    while ($row = $res->fetch_assoc()) { $rows[] = $row; }
    $st->close();

    echo json_encode(["status" => "success", "requests" => $rows]);
    exit;
  }

  if ($method === 'POST') {
    // Update status of a request
    $input = json_decode(file_get_contents("php://input"), true) ?? [];
    $requestId = (int)($input['request_id'] ?? 0);
    $newStatus = strtolower((string)($input['status'] ?? ''));
    $allowed = ['pending','in_progress','completed','cancelled'];
    if ($requestId <= 0 || !in_array($newStatus, $allowed, true)) {
      http_response_code(400);
      echo json_encode(["status" => "error", "message" => "Bad request"]);
      exit;
    }

    $st = $mysqli->prepare("UPDATE custom_requests SET status=? WHERE id=?");
    $st->bind_param('si', $newStatus, $requestId);
    $st->execute();
    $affected = $st->affected_rows;
    $st->close();

    if ($affected === 0) {
      http_response_code(404);
      echo json_encode(["status" => "error", "message" => "Request not found"]);
      exit;
    }

    echo json_encode(["status" => "success", "request_id" => $requestId, "new_status" => $newStatus]);
    exit;
  }

  http_response_code(405);
  echo json_encode(["status" => "error", "message" => "Method not allowed"]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "Admin request handling failed", "detail" => $e->getMessage()]);
}