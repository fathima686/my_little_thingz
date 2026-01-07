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
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-User-Id, X-Admin-Email");

// Preflight
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
  // roles and supplier_profiles may be needed if not present
  $db->query("CREATE TABLE IF NOT EXISTS roles (id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL UNIQUE) ENGINE=InnoDB");
  $db->query("INSERT IGNORE INTO roles (id, name) VALUES (1,'admin'),(2,'customer'),(3,'supplier')");
  $db->query("CREATE TABLE IF NOT EXISTS user_roles (user_id INT UNSIGNED NOT NULL, role_id TINYINT UNSIGNED NOT NULL, PRIMARY KEY (user_id, role_id)) ENGINE=InnoDB");
  $db->query("CREATE TABLE IF NOT EXISTS supplier_profiles (user_id INT UNSIGNED PRIMARY KEY, status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB");
}

try { ensure_schema($mysqli); } catch (Throwable $e) {}

// Simple header-based admin check (because no sessions/JWT here)
$adminUserId = isset($_SERVER['HTTP_X_ADMIN_USER_ID']) ? (int)$_SERVER['HTTP_X_ADMIN_USER_ID'] : 0;
if ($adminUserId <= 0) {
  http_response_code(401);
  echo json_encode(["status" => "error", "message" => "Missing admin identity"]);
  exit;
}

// verify the caller is admin
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
    // Optional filter: status=pending|approved|rejected|all
    $status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : 'pending';
    $allowed = ['pending','approved','rejected','all'];
    if (!in_array($status, $allowed, true)) { $status = 'pending'; }

    $sql = "SELECT u.id, u.first_name, u.last_name, u.email, sp.status
            FROM supplier_profiles sp JOIN users u ON u.id=sp.user_id";
    if ($status !== 'all') { $sql .= " WHERE sp.status=?"; }
    $sql .= " ORDER BY sp.created_at DESC";

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

    echo json_encode(["status" => "success", "suppliers" => $rows]);
    exit;
  }

  if ($method === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true) ?? [];
    $userId = (int)($input['user_id'] ?? 0);
    $action = strtolower((string)($input['action'] ?? ''));
    if ($userId <= 0 || !in_array($action, ['approve','reject'], true)) {
      http_response_code(400);
      echo json_encode(["status" => "error", "message" => "Bad request"]);
      exit;
    }

    $newStatus = $action === 'approve' ? 'approved' : 'rejected';
    $st = $mysqli->prepare("UPDATE supplier_profiles SET status=? WHERE user_id=?");
    $st->bind_param('si', $newStatus, $userId);
    $st->execute();
    $affected = $st->affected_rows;
    $st->close();

    if ($affected === 0) {
      http_response_code(404);
      echo json_encode(["status" => "error", "message" => "Supplier not found"]);
      exit;
    }

    // On approval, send an approval email to the supplier
    $emailSent = false;
    if ($newStatus === 'approved') {
      // Fetch supplier's name and email
      $ust = $mysqli->prepare("SELECT first_name, last_name, email FROM users WHERE id=? LIMIT 1");
      $ust->bind_param('i', $userId);
      $ust->execute();
      $res = $ust->get_result();
      if ($row = $res->fetch_assoc()) {
        $fullName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        if ($row['email']) {
          require_once __DIR__ . '/../../includes/SimpleEmailSender.php';
          $mailer = new SimpleEmailSender();
          $emailSent = $mailer->sendSupplierApprovalEmail($row['email'], $fullName !== '' ? $fullName : 'Supplier');
        }
      }
      $ust->close();
    }

    echo json_encode(["status" => "success", "user_id" => $userId, "supplier_status" => $newStatus, "email_sent" => $emailSent]);
    exit;
  }

  http_response_code(405);
  echo json_encode(["status" => "error", "message" => "Method not allowed"]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "Admin action failed", "detail" => $e->getMessage()]);
}