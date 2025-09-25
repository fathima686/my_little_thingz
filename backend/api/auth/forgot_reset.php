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
$token = trim($input['token'] ?? '');
$newPassword = (string)($input['newPassword'] ?? '');

if ($email === '' || $token === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($token) < 6 || strlen($newPassword) < 8) {
  http_response_code(422);
  echo json_encode(['status'=>'error','message'=>'Invalid input']);
  exit;
}

// Check token
$now = (new DateTime())->format('Y-m-d H:i:s');
$q = $mysqli->prepare('SELECT id FROM password_resets WHERE email=? AND token=? AND expires_at > ? LIMIT 1');
$q->bind_param('sss', $email, $token, $now);
$q->execute();
$q->store_result();
if ($q->num_rows === 0) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Invalid or expired token']); exit; }
$q->close();

$hash = password_hash($newPassword, PASSWORD_BCRYPT);

// Update users table regardless of column name
$col = 'password_hash';
$chk = $mysqli->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
if ($chk && $chk->num_rows === 0) { $col = 'password'; }

$u = $mysqli->prepare("UPDATE users SET $col=? WHERE email=?");
$u->bind_param('ss', $hash, $email);
$u->execute();
$u->close();

// Invalidate token
$d = $mysqli->prepare('DELETE FROM password_resets WHERE email=?');
$d->bind_param('s', $email);
$d->execute();
$d->close();

echo json_encode(['status'=>'success','message'=>'Password updated']);