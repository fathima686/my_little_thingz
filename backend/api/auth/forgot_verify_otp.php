<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['http://localhost:5173','http://127.0.0.1:5173'];
header('Vary: Origin');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if (in_array($origin, $allowed, true)) header("Access-Control-Allow-Origin: $origin"); else header('Access-Control-Allow-Origin: http://localhost:5173');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli('localhost','root','', 'my_little_thingz');
$mysqli->set_charset('utf8mb4');
if ($mysqli->connect_error) { http_response_code(500); echo json_encode(['status'=>'error','message'=>'DB connect failed']); exit; }

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim($input['email'] ?? '');
$otp   = trim($input['otp'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^\d{6}$/', $otp)) {
  http_response_code(422);
  echo json_encode(['status'=>'error','message'=>'Invalid input']);
  exit;
}

// Ensure table
$mysqli->query("CREATE TABLE IF NOT EXISTS password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(191) NOT NULL,
  token VARCHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(email)
) ENGINE=InnoDB");

// Validate OTP for email and not expired
$now = (new DateTime())->format('Y-m-d H:i:s');
$q = $mysqli->prepare('SELECT id FROM password_resets WHERE email=? AND token=? AND expires_at > ? LIMIT 1');
$q->bind_param('sss', $email, $otp, $now);
$q->execute();
$q->store_result();
if ($q->num_rows === 0) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'Invalid or expired code']);
  exit;
}
$q->close();

echo json_encode(['status'=>'success','message'=>'Code verified']);