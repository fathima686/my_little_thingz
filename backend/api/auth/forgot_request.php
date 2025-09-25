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
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Invalid email']); exit; }

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

// Adjust indexes for OTP approach: allow same 6-digit tokens across different emails
try { $mysqli->query("ALTER TABLE password_resets DROP INDEX uq_token"); } catch (Throwable $e) {}
try { $mysqli->query("ALTER TABLE password_resets ADD UNIQUE KEY uq_email (email)"); } catch (Throwable $e) {}
try { $mysqli->query("ALTER TABLE password_resets ADD INDEX idx_email_token (email, token)"); } catch (Throwable $e) { }

// Check if user exists (do not reveal result later)
$exists = false;
$u = $mysqli->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
$u->bind_param('s', $email);
$u->execute();
$u->store_result();
$exists = $u->num_rows > 0;
$u->close();

if ($exists) {
  // Generate 6-digit OTP and expire in 30 minutes
  $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
  $expires = (new DateTime('+30 minutes'))->format('Y-m-d H:i:s');

  // Upsert OTP for this email (store in token column)
  $mysqli->query("DELETE FROM password_resets WHERE email='".$mysqli->real_escape_string($email)."'");
  $s = $mysqli->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?,?,?)');
  $s->bind_param('sss', $email, $otp, $expires);
  $s->execute();
  $s->close();

  // Send OTP email (best-effort)
  try {
    require_once __DIR__ . '/../../includes/AuthEmail.php';
    $mailer = new AuthEmail();
    $mailer->sendResetOtpEmail($email, '', $otp);
    @file_put_contents(__DIR__ . '/../../../email_log.txt', '[RESET-OTP] Email: ' . $email . ' OTP: ' . $otp . "\n", FILE_APPEND);
  } catch (Throwable $e) {
    // Log silently; do not expose info
    @file_put_contents(__DIR__ . '/../../../email_log.txt', '[RESET-EMAIL-ERROR] ' . $e->getMessage() . "\n", FILE_APPEND);
  }
}

// Always respond success to avoid user enumeration
http_response_code(200);
echo json_encode(['status'=>'success','message'=>'If an account exists for this email, a reset code has been sent.']);