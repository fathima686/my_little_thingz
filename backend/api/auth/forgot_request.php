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
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Invalid email']); exit; }

// Ensure table for reset tokens
$mysqli->query("CREATE TABLE IF NOT EXISTS password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(191) NOT NULL,
  token VARCHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(email),
  UNIQUE KEY uq_token (token)
) ENGINE=InnoDB");

// Verify user exists
$u = $mysqli->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
$u->bind_param('s', $email);
$u->execute();
$u->store_result();
if ($u->num_rows === 0) { http_response_code(404); echo json_encode(['status'=>'error','message'=>'Email not found']); exit; }
$u->close();

$token = bin2hex(random_bytes(16));
$expires = (new DateTime('+30 minutes'))->format('Y-m-d H:i:s');

// Upsert a token for this email
$mysqli->query("DELETE FROM password_resets WHERE email='".$mysqli->real_escape_string($email)."'");
$s = $mysqli->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?,?,?)');
$s->bind_param('sss', $email, $token, $expires);
$s->execute();
$s->close();

// In production email the token; here we return it for testing
echo json_encode(['status'=>'success','message'=>'Reset token generated','token'=>$token]);