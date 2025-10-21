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

  // Ensure custom_requests has 'source' column
  try {
    $rs = $db->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='custom_requests' AND COLUMN_NAME='source'");
    $rw = $rs->fetch_assoc();
    if ((int)$rw['c'] === 0) {
      $db->query("ALTER TABLE custom_requests ADD COLUMN source ENUM('form','cart') NOT NULL DEFAULT 'form' AFTER special_instructions");
    }
  } catch (Throwable $e) {}

  // Ensure custom_requests has 'gift_tier' column
  try {
    $rs = $db->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='custom_requests' AND COLUMN_NAME='gift_tier'");
    $rw = $rs->fetch_assoc();
    if ((int)$rw['c'] === 0) {
      $db->query("ALTER TABLE custom_requests ADD COLUMN gift_tier ENUM('budget','premium') NULL DEFAULT 'budget' AFTER special_instructions");
    }
  } catch (Throwable $e) {}
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
    $split = isset($_GET['split']) && (int)$_GET['split'] === 1;

    $sql = "SELECT cr.id, cr.user_id, u.first_name, u.last_name, u.email,
                   cr.title, cr.occasion, cr.description,
                   cr.category_id, c.name AS category_name,
                   cr.budget_min, cr.budget_max, cr.deadline,
                   cr.special_instructions, cr.gift_tier, cr.source, cr.status, cr.created_at,
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

    // Attach images for each request so the admin can view them
    $ids = array_column($rows, 'id');
    if (!empty($ids)) {
      // Build a dynamic IN clause safely with prepared statement
      $in = implode(',', array_fill(0, count($ids), '?'));
      $types = str_repeat('i', count($ids));
      $sqlImgs = "SELECT request_id, image_path FROM custom_request_images WHERE request_id IN ($in) ORDER BY uploaded_at ASC";
      $stImgs = $mysqli->prepare($sqlImgs);
      // Spread IDs as params
      $stImgs->bind_param($types, ...$ids);
      $stImgs->execute();
      $rsImgs = $stImgs->get_result();
      $byReq = [];
      // Build absolute URLs using serve_image.php so the frontend can load images from PHP
      $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
      $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
      $servePrefix = $scheme . '://' . $host . '/my_little_thingz/backend/serve_image.php?path=';
      while ($im = $rsImgs->fetch_assoc()) {
        $rid = (int)$im['request_id'];
        if (!isset($byReq[$rid])) { $byReq[$rid] = []; }
        $path = (string)($im['image_path'] ?? '');
        if (preg_match('~^https?://~i', $path)) {
          $url = $path; // already absolute
        } else {
          $url = $servePrefix . rawurlencode(ltrim($path, '/'));
        }
        $byReq[$rid][] = $url;
      }
      $stImgs->close();
      foreach ($rows as &$r) { $r['images'] = $byReq[$r['id']] ?? []; }
      unset($r);
    }

    if ($split) {
      $normal = [];
      $fromCart = [];
      foreach ($rows as $r) {
        if (($r['source'] ?? 'form') === 'cart') { $fromCart[] = $r; }
        else { $normal[] = $r; }
      }
      echo json_encode(["status" => "success", "normal_requests" => $normal, "cart_requests" => $fromCart]);
    } else {
      echo json_encode(["status" => "success", "requests" => $rows]);
    }
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

    // If admin marks as completed, move the intended item into the user's cart
    if ($newStatus === 'completed') {
      // Fetch mapping
      $q = $mysqli->prepare("SELECT user_id, COALESCE(artwork_id, 0) AS artwork_id, COALESCE(requested_quantity, 1) AS requested_quantity FROM custom_requests WHERE id=?");
      $q->bind_param('i', $requestId);
      $q->execute();
      $res = $q->get_result();
      $map = $res->fetch_assoc();
      $q->close();

      if ($map && (int)$map['artwork_id'] > 0) {
        $uid = (int)$map['user_id'];
        $aid = (int)$map['artwork_id'];
        $qty = max(1, (int)$map['requested_quantity']);

        // Validate artwork exists and is active
        $chk = $mysqli->prepare("SELECT id FROM artworks WHERE id=? AND status='active' LIMIT 1");
        $chk->bind_param('i', $aid);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
          $chk->close();
          // Insert/update cart
          $sel = $mysqli->prepare("SELECT id, quantity FROM cart WHERE user_id=? AND artwork_id=? LIMIT 1");
          $sel->bind_param('ii', $uid, $aid);
          $sel->execute();
          $r = $sel->get_result()->fetch_assoc();
          $sel->close();
          if ($r) {
            $newQ = (int)$r['quantity'] + $qty;
            $upd = $mysqli->prepare("UPDATE cart SET quantity=? WHERE id=?");
            $upd->bind_param('ii', $newQ, $r['id']);
            $upd->execute();
            $upd->close();
          } else {
            $ins = $mysqli->prepare("INSERT INTO cart (user_id, artwork_id, quantity, added_at) VALUES (?, ?, ?, NOW())");
            $ins->bind_param('iii', $uid, $aid, $qty);
            $ins->execute();
            $ins->close();
          }
        } else {
          $chk->close();
        }
      }
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