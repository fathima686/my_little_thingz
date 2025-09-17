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

// Ensure minimal schema exists (safe no-ops if it already exists)
function ensure_schema(mysqli $db) {
  $db->query("CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB");

  $db->query("CREATE TABLE IF NOT EXISTS roles (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
  ) ENGINE=InnoDB");

  $db->query("CREATE TABLE IF NOT EXISTS user_roles (
    user_id INT UNSIGNED NOT NULL,
    role_id TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
  ) ENGINE=InnoDB");

  // Supplier status table (pending approval by admin)
  $db->query("CREATE TABLE IF NOT EXISTS supplier_profiles (
    user_id INT UNSIGNED PRIMARY KEY,
    shop_name VARCHAR(120) NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_supplier_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  ) ENGINE=InnoDB");

  // Safe migration: ensure shop_name exists (for older DBs)
  try {
    $res = $db->query("SHOW COLUMNS FROM supplier_profiles LIKE 'shop_name'");
    if ($res && $res->num_rows === 0) {
      $db->query("ALTER TABLE supplier_profiles ADD COLUMN shop_name VARCHAR(120) NOT NULL DEFAULT '' AFTER user_id");
    }
  } catch (Throwable $e) { /* ignore */ }

  // Seed roles
  $db->query("INSERT IGNORE INTO roles (id, name) VALUES (1,'admin'),(2,'customer'),(3,'supplier')");
}

try {
  ensure_schema($mysqli);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "DB init failed", "detail" => $e->getMessage()]);
  exit;
}

$input = json_decode(file_get_contents("php://input"), true) ?? [];
$first = trim($input['firstName'] ?? '');
$last  = trim($input['lastName'] ?? '');
$email = trim($input['email'] ?? '');
$pass  = (string)($input['password'] ?? '');
// Accept role as number (2|3) or string ('customer'|'supplier'); default to customer
$roleRaw = $input['role'] ?? 2;
$roleId = 2; // default customer
if (is_numeric($roleRaw)) {
  $tmp = (int)$roleRaw;
  if (in_array($tmp, [2,3], true)) { $roleId = $tmp; }
} else {
  $tmp = strtolower(trim((string)$roleRaw));
  if ($tmp === 'supplier') { $roleId = 3; }
}
$isSupplier = ($roleId === 3);

// Validation (server-side)
// Names: allow letters, space, apostrophe, dot, hyphen; must start with a letter; length <= 30
if (!preg_match('/^[A-Za-z][A-Za-z\s\'\.\-]{1,29}$/', $first)) {
  http_response_code(422);
  echo json_encode(["status" => "error", "message" => "Invalid first name"]);
  exit;
}
if (!preg_match('/^[A-Za-z][A-Za-z\s\'\.\-]{1,29}$/', $last)) {
  http_response_code(422);
  echo json_encode(["status" => "error", "message" => "Invalid last name"]);
  exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(422);
  echo json_encode(["status" => "error", "message" => "Invalid email"]);
  exit;
}
// Password: at least 8 chars, contains upper, lower, digit, and a non-alphanumeric
if (
  strlen($pass) < 8 ||
  !preg_match('/[A-Z]/', $pass) ||
  !preg_match('/[a-z]/', $pass) ||
  !preg_match('/[0-9]/', $pass) ||
  !preg_match('/[^A-Za-z0-9]/', $pass)
) {
  http_response_code(422);
  echo json_encode(["status" => "error", "message" => "Weak password"]);
  exit;
}

// Require shop_name for suppliers
$shop = '';
if ($isSupplier) {
  $shop = trim((string)($input['shop_name'] ?? ''));
  if ($shop === '' || strlen($shop) > 120) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Shop name is required (max 120 chars) for supplier"]);
    exit;
  }
}

// Unique email
$stmt = $mysqli->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
  http_response_code(409);
  echo json_encode(["status" => "error", "message" => "Email already registered"]);
  exit;
}
$stmt->close();

$hash = password_hash($pass, PASSWORD_BCRYPT);

$mysqli->begin_transaction();
try {
  // Insert using whichever password column exists
  $col = 'password_hash';
  $chk = $mysqli->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
  if ($chk && $chk->num_rows === 0) { $col = 'password'; }

  // Single-table role support
  $hasRole = false;
  $chkRole = $mysqli->query("SHOW COLUMNS FROM users LIKE 'role'");
  if ($chkRole && $chkRole->num_rows > 0) { $hasRole = true; }

  if ($hasRole) {
    // Map numeric role to name; block admin=1
    $roleName = $isSupplier ? 'supplier' : 'customer';
    $stmt = $mysqli->prepare("INSERT INTO users(first_name,last_name,email,$col,role) VALUES(?,?,?,?,?)");
    $stmt->bind_param('sssss', $first, $last, $email, $hash, $roleName);
    $stmt->execute();
    $userId = $stmt->insert_id;
    $stmt->close();
  } else {
    // Fallback to roles mapping tables
    $stmt = $mysqli->prepare("INSERT INTO users(first_name,last_name,email,$col) VALUES(?,?,?,?)");
    $stmt->bind_param('ssss', $first, $last, $email, $hash);
    $stmt->execute();
    $userId = $stmt->insert_id;
    $stmt->close();

    // Assign role using sanitized $roleId (2=customer, 3=supplier)
    $stmt = $mysqli->prepare("INSERT INTO user_roles(user_id, role_id) VALUES(?,?)");
    $stmt->bind_param('ii', $userId, $roleId);
    $stmt->execute();
    $stmt->close();
  }

  // If supplier, create pending profile with shop_name
  if ($isSupplier) {
    // Ensure column exists (safety for older DBs)
    try {
      $chkCol = $mysqli->query("SHOW COLUMNS FROM supplier_profiles LIKE 'shop_name'");
      if ($chkCol && $chkCol->num_rows === 0) {
        $mysqli->query("ALTER TABLE supplier_profiles ADD COLUMN shop_name VARCHAR(120) NOT NULL DEFAULT '' AFTER user_id");
      }
    } catch (Throwable $e) {}

    $stp = $mysqli->prepare("INSERT INTO supplier_profiles(user_id, shop_name, status) VALUES (?,?, 'pending') ON DUPLICATE KEY UPDATE shop_name=VALUES(shop_name), status=VALUES(status)");
    $stp->bind_param('is', $userId, $shop);
    $stp->execute();
    $stp->close();
  }

  $mysqli->commit();
  if ($isSupplier) {
    echo json_encode(["status" => "pending", "message" => "Supplier account created and awaiting admin approval", "user_id" => $userId, "roles" => ["supplier"], "supplier_status" => "pending"]);
  } else {
    echo json_encode(["status" => "success", "user_id" => $userId, "roles" => ["customer"]]);
  }
} catch (Throwable $e) {
  $mysqli->rollback();
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "Registration failed"]);
}
?>