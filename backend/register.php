<?php
// Harmonized legacy register endpoint with new schema
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: " . ($origin ?: '*'));
header("Vary: Origin");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli("localhost", "root", "", "my_little_thingz");
$conn->set_charset('utf8mb4');
if ($conn->connect_error) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
  exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true) ?? [];
$firstName = trim($data['firstName'] ?? '');
$lastName  = trim($data['lastName'] ?? '');
$email     = trim($data['email'] ?? '');
$password  = (string)($data['password'] ?? '');
$role      = (int)($data['role'] ?? 2); // default customer

if (!$firstName || !$lastName || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
  http_response_code(422);
  echo json_encode(["status" => "error", "message" => "Invalid input"]);
  exit;
}

$hash = password_hash($password, PASSWORD_BCRYPT);

// Ensure roles exist
$conn->query("CREATE TABLE IF NOT EXISTS roles (id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) UNIQUE) ENGINE=InnoDB");
$conn->query("INSERT IGNORE INTO roles (id,name) VALUES (1,'admin'),(2,'customer'),(3,'supplier')");

// Unique email
$stmt = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
  http_response_code(409);
  echo json_encode(["status" => "error", "message" => "Email already registered"]);
  exit;
}
$stmt->close();

$conn->begin_transaction();
try {
  // Insert using whichever password column exists
  $col = 'password_hash';
  // probe table columns
  $chk = $conn->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
  if ($chk && $chk->num_rows === 0) { $col = 'password'; }
  $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, $col) VALUES (?,?,?,?)");
  $stmt->bind_param("ssss", $firstName, $lastName, $email, $hash);
  $stmt->execute();
  $uid = $stmt->insert_id;
  $stmt->close();

  $roleId = in_array($role, [2,3], true) ? $role : 2;
  $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?,?)");
  $stmt->bind_param("ii", $uid, $roleId);
  $stmt->execute();
  $stmt->close();

  $conn->commit();
  echo json_encode(["status" => "success", "user_id" => $uid]);
} catch (Throwable $e) {
  $conn->rollback();
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "Registration failed", "detail" => $e->getMessage()]);
}
?>
