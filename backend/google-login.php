<?php
// backend/google-login.php
// CORS for Vite dev server
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

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// DB connect (same as backend/db.php values)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli("localhost", "root", "", "my_little_thingz");
$mysqli->set_charset('utf8mb4');
if ($mysqli->connect_error) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "DB connect failed: " . $mysqli->connect_error]);
  exit;
}

// Read input
$input = json_decode(file_get_contents("php://input"), true) ?? [];
$credential = $input['credential'] ?? '';

if (!$credential) {
  http_response_code(400);
  echo json_encode(["status" => "error", "message" => "Missing credential"]);
  exit;
}

// Verify Google ID token (server-side) using Google's tokeninfo endpoint.
// For production, prefer verifying JWT signature with Google PHP client.
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

// Enforce your Google OAuth Client ID
$expectedClientIds = [
  "45556451570-klcn8baba7p1evqusg4td82okvskjkd1.apps.googleusercontent.com",
];

$payload = verify_google_id_token($credential, $expectedClientIds);
if (!$payload || !isset($payload['email'])) {
  http_response_code(401);
  echo json_encode(["status" => "error", "message" => "Invalid Google token"]);
  exit;
}

$email = $payload['email'];
$sub   = $payload['sub']; // Google user id
$first = $payload['given_name'] ?? '';
$last  = $payload['family_name'] ?? '';

$mysqli->begin_transaction();
try {
  // 1) Check existing provider link
  $stmt = $mysqli->prepare("SELECT user_id FROM auth_providers WHERE provider='google' AND provider_user_id=? LIMIT 1");
  $stmt->bind_param('s', $sub);
  $stmt->execute();
  $stmt->bind_result($userId);
  if ($stmt->fetch()) {
    $stmt->close();
    $mysqli->commit();
    echo json_encode(["status" => "success", "user_id" => $userId, "mode" => "login"]);
    exit;
  }
  $stmt->close();

  // 2) Strict: Only allow if email already exists; do NOT create new user
  $stmt = $mysqli->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $stmt->bind_result($existingId);
  if ($stmt->fetch()) {
    $userId = $existingId;
    $stmt->close();

    // Optional: link provider for future logins
    $stmt = $mysqli->prepare("INSERT INTO auth_providers(user_id, provider, provider_user_id) VALUES(?, 'google', ?)");
    $stmt->bind_param('is', $userId, $sub);
    $stmt->execute();
    $stmt->close();

    // Ensure default role (customer = 2)
    $roleId = 2;
    $stmt = $mysqli->prepare("INSERT IGNORE INTO user_roles(user_id, role_id) VALUES(?,?)");
    $stmt->bind_param('ii', $userId, $roleId);
    $stmt->execute();
    $stmt->close();

    $mysqli->commit();
    echo json_encode(["status" => "success", "user_id" => $userId, "mode" => "login_linked"]);
  } else {
    $stmt->close();
    $mysqli->rollback();
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Email not registered. Please sign up first."]);
  }
} catch (Throwable $e) {
  $mysqli->rollback();
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "Google auth failed"]);
}
