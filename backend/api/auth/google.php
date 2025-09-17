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
$credential = $input['credential'] ?? '';
$desiredRole = (int)($input['desired_role'] ?? 2); // 2=customer, 3=supplier
if (!in_array($desiredRole, [2,3], true)) { $desiredRole = 2; }
$shopName = isset($input['shop_name']) ? trim((string)$input['shop_name']) : '';

if (!$credential) {
  http_response_code(400);
  echo json_encode(["status" => "error", "message" => "Missing credential"]);
  exit;
}

// Verify the Google ID token (server-side). For simplicity, we call Google tokeninfo.
// In production, verify JWT signature & audience with Google library.
function verify_google_id_token($idToken, $expectedAudiences = []) {
  $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
  $ctx = stream_context_create(['http' => ['timeout' => 5]]);
  $json = @file_get_contents($url, false, $ctx);
  if ($json === false) return null;
  $payload = json_decode($json, true);
  if (!$payload) return null;
  if (!empty($expectedAudiences)) {
    $aud = $payload['aud'] ?? '';
    if (!in_array($aud, $expectedAudiences, true)) {
      return null; // audience mismatch
    }
  }
  return $payload;
}

// Fill with your real client id(s). Keep this in sync with frontend .env VITE_GOOGLE_CLIENT_ID
$EXPECTED_AUDS = [
  "12668430306-fg4m3l8mh7hqb84m5s2j7qrtgk7naojm.apps.googleusercontent.com",
];

$payload = verify_google_id_token($credential, $EXPECTED_AUDS);
if (!$payload || !isset($payload['email'])) {
  http_response_code(401);
  echo json_encode(["status" => "error", "message" => "Invalid Google token"]);
  exit;
}

$email = $payload['email'];
$sub   = $payload['sub']; // Google user id
$first = $payload['given_name'] ?? '';
$last  = $payload['family_name'] ?? '';

// Upsert user
$mysqli->begin_transaction();
try {
  // 1) Check if provider mapping exists
  $stmt = $mysqli->prepare("SELECT user_id FROM auth_providers WHERE provider='google' AND provider_user_id=? LIMIT 1");
  $stmt->bind_param('s', $sub);
  $stmt->execute();
  $stmt->bind_result($userId);
  if ($stmt->fetch()) {
    $stmt->close();
    // Existing mapping: perform role fetch and supplier approval check before returning
    $roles = [];
    $r = $mysqli->prepare("SELECT r.name FROM user_roles ur JOIN roles r ON ur.role_id=r.id WHERE ur.user_id=?");
    $r->bind_param('i', $userId);
    $r->execute();
    $r->bind_result($rname);
    while ($r->fetch()) { $roles[] = $rname; }
    $r->close();
    if (count($roles) === 0) { $roles = ['customer']; }

    if (in_array('supplier', array_map('strtolower', $roles), true)) {
      $mysqli->query("CREATE TABLE IF NOT EXISTS supplier_profiles (user_id INT UNSIGNED PRIMARY KEY, status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, CONSTRAINT fk_supplier_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB");
      $st = $mysqli->prepare("SELECT status FROM supplier_profiles WHERE user_id=? LIMIT 1");
      $st->bind_param('i', $userId);
      $st->execute();
      $st->bind_result($sstatus);
      $status = 'pending';
      if ($st->fetch()) { $status = $sstatus; }
      $st->close();
      if ($status !== 'approved') {
        echo json_encode(["status" => "pending", "message" => "Supplier account awaiting approval", "user_id" => $userId, "roles" => $roles, "supplier_status" => $status]);
        exit;
      }
    }

    echo json_encode(["status" => "success", "user_id" => $userId, "roles" => $roles, "mode" => "login"]);
    exit;
  }
  $stmt->close();

  // 2) If not, try by email
  $stmt = $mysqli->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $stmt->bind_result($existingId);
  if ($stmt->fetch()) {
    $userId = $existingId;
  } else {
    // create new user
    $stmt->close();
    $stmt = $mysqli->prepare("INSERT INTO users(first_name,last_name,email) VALUES(?,?,?)");
    $stmt->bind_param('sss', $first, $last, $email);
    $stmt->execute();
    $userId = $stmt->insert_id;
  }
  $stmt->close();

  // Assign role (customer by default, allow supplier if requested)
  $roleId = in_array($desiredRole, [2,3], true) ? $desiredRole : 2;
  $stmt = $mysqli->prepare("INSERT IGNORE INTO user_roles(user_id, role_id) VALUES(?,?)");
  $stmt->bind_param('ii', $userId, $roleId);
  $stmt->execute();
  $stmt->close();

  // If supplier selected, ensure pending supplier profile exists and store shop_name if provided
  if ($roleId === 3) {
    // Ensure table and shop_name column exist
    $mysqli->query("CREATE TABLE IF NOT EXISTS supplier_profiles (user_id INT UNSIGNED PRIMARY KEY, shop_name VARCHAR(120) NOT NULL, status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, CONSTRAINT fk_supplier_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB");
    try {
      $resCol = $mysqli->query("SHOW COLUMNS FROM supplier_profiles LIKE 'shop_name'");
      if ($resCol && $resCol->num_rows === 0) {
        $mysqli->query("ALTER TABLE supplier_profiles ADD COLUMN shop_name VARCHAR(120) NOT NULL DEFAULT '' AFTER user_id");
      }
    } catch (Throwable $e) {}

    if ($shopName !== '' && strlen($shopName) <= 120) {
      $st = $mysqli->prepare("INSERT INTO supplier_profiles(user_id, shop_name, status) VALUES (?,?, 'pending') ON DUPLICATE KEY UPDATE shop_name=VALUES(shop_name), status=status");
      $st->bind_param('is', $userId, $shopName);
      $st->execute();
      $st->close();
    } else {
      $mysqli->query("INSERT INTO supplier_profiles(user_id, status) VALUES ($userId, 'pending') ON DUPLICATE KEY UPDATE status=status");
    }
  }

  // 3) Map provider
  $stmt = $mysqli->prepare("INSERT INTO auth_providers(user_id, provider, provider_user_id) VALUES(?, 'google', ?)");
  $stmt->bind_param('is', $userId, $sub);
  $stmt->execute();
  $stmt->close();

  $mysqli->commit();

  // Fetch roles
  $roles = [];
  $r = $mysqli->prepare("SELECT r.name FROM user_roles ur JOIN roles r ON ur.role_id=r.id WHERE ur.user_id=?");
  $r->bind_param('i', $userId);
  $r->execute();
  $r->bind_result($rname);
  while ($r->fetch()) { $roles[] = $rname; }
  $r->close();
  if (count($roles) === 0) { $roles = ['customer']; }

  // Supplier approval check
  if (in_array('supplier', array_map('strtolower', $roles), true)) {
    // ensure table & shop_name column
    $mysqli->query("CREATE TABLE IF NOT EXISTS supplier_profiles (user_id INT UNSIGNED PRIMARY KEY, shop_name VARCHAR(120) NOT NULL, status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, CONSTRAINT fk_supplier_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB");
    try {
      $resCol = $mysqli->query("SHOW COLUMNS FROM supplier_profiles LIKE 'shop_name'");
      if ($resCol && $resCol->num_rows === 0) {
        $mysqli->query("ALTER TABLE supplier_profiles ADD COLUMN shop_name VARCHAR(120) NOT NULL DEFAULT '' AFTER user_id");
      }
    } catch (Throwable $e) {}

    $st = $mysqli->prepare("SELECT status FROM supplier_profiles WHERE user_id=? LIMIT 1");
    $st->bind_param('i', $userId);
    $st->execute();
    $st->bind_result($sstatus);
    $status = 'pending';
    if ($st->fetch()) { $status = $sstatus; }
    $st->close();
    if ($status !== 'approved') {
      echo json_encode(["status" => "pending", "message" => "Supplier account awaiting approval", "user_id" => $userId, "roles" => $roles, "supplier_status" => $status]);
      exit;
    }
  }

  echo json_encode(["status" => "success", "user_id" => $userId, "roles" => $roles]);
} catch (Throwable $e) {
  $mysqli->rollback();
  http_response_code(500);
  echo json_encode([
    "status" => "error",
    "message" => "Google auth failed",
    "detail" => $e->getMessage()
  ]);
}
?>