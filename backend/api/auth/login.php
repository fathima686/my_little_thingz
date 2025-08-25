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
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle CORS preflight
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

$input = json_decode(file_get_contents("php://input"), true) ?? [];
$email = trim($input['email'] ?? '');
$pass  = (string)($input['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(422);
  echo json_encode(["status" => "error", "message" => "Invalid email"]);
  exit;
}

// Try password_hash first; if column missing, fallback to password
$col = 'password_hash';
$chk = $mysqli->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
if ($chk && $chk->num_rows === 0) { $col = 'password'; }

// Single-table role support: expect a 'role' column; fallback to user_roles if missing
$hasRole = false;
$chkRole = $mysqli->query("SHOW COLUMNS FROM users LIKE 'role'");
if ($chkRole && $chkRole->num_rows > 0) { $hasRole = true; }

if ($hasRole) {
  $query = "SELECT id, $col, role FROM users WHERE email=? LIMIT 1";
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $stmt->bind_result($uid, $hash, $roleVal);
  if ($stmt->fetch() && $hash && password_verify($pass, $hash)) {
    $stmt->close();
    $roleName = strtolower((string)$roleVal);
    // If supplier role, ensure approved before allowing login
    if ($roleName === 'supplier') {
      $st = $mysqli->prepare("CREATE TABLE IF NOT EXISTS supplier_profiles (user_id INT UNSIGNED PRIMARY KEY, status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, CONSTRAINT fk_supplier_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB");
      $st->execute();
      $st->close();
      $st = $mysqli->prepare("SELECT status FROM supplier_profiles WHERE user_id=? LIMIT 1");
      $st->bind_param('i', $uid);
      $st->execute();
      $st->bind_result($sstatus);
      $status = 'pending';
      if ($st->fetch()) { $status = $sstatus; }
      $st->close();
      if ($status !== 'approved') {
        echo json_encode(["status" => "pending", "message" => "Supplier account awaiting approval", "user_id" => $uid, "roles" => [$roleName], "supplier_status" => $status]);
        exit;
      }
    }
    echo json_encode(["status" => "success", "user_id" => $uid, "roles" => [$roleName]]);
  } else {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid credentials"]);
  }
} else {
  $query = "SELECT id, $col FROM users WHERE email=? LIMIT 1";
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $stmt->bind_result($uid, $hash);
  if ($stmt->fetch() && $hash && password_verify($pass, $hash)) {
    $stmt->close();
    // fetch roles from mapping tables
    $roles = [];
    $r = $mysqli->prepare("SELECT r.name FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id=?");
    $r->bind_param('i', $uid);
    $r->execute();
    $r->bind_result($rname);
    while ($r->fetch()) { $roles[] = $rname; }
    $r->close();

    if (count($roles) === 0) {
      $roles = ['customer'];
    }

    // If supplier in roles, ensure approved before allowing login
    if (in_array('supplier', array_map('strtolower', $roles), true)) {
      $st = $mysqli->prepare("CREATE TABLE IF NOT EXISTS supplier_profiles (user_id INT UNSIGNED PRIMARY KEY, status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, CONSTRAINT fk_supplier_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB");
      $st->execute();
      $st->close();
      $st = $mysqli->prepare("SELECT status FROM supplier_profiles WHERE user_id=? LIMIT 1");
      $st->bind_param('i', $uid);
      $st->execute();
      $st->bind_result($sstatus);
      $status = 'pending';
      if ($st->fetch()) { $status = $sstatus; }
      $st->close();
      if ($status !== 'approved') {
        echo json_encode(["status" => "pending", "message" => "Supplier account awaiting approval", "user_id" => $uid, "roles" => $roles, "supplier_status" => $status]);
        exit;
      }
    }
    echo json_encode(["status" => "success", "user_id" => $uid, "roles" => $roles]);
  } else {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid credentials"]);
  }
}
?>