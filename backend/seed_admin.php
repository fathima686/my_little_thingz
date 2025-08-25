<?php
// One-time admin seeder. Visit once, then delete this file for security.
// Creates/updates an admin user with the provided email and password.

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
header('Content-Type: application/json');

try {
  $db = new mysqli('localhost', 'root', '', 'my_little_thingz');
  $db->set_charset('utf8mb4');

  // Desired admin credentials (provided by user)
  $email = 'fathima470077@gmail.com';
  $passwordPlain = 'admin123';
  $first = 'Admin';
  $last  = 'User';

  // Ensure minimal schema exists (safe no-ops if already there)
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

  // Seed roles
  $db->query("INSERT IGNORE INTO roles (id, name) VALUES (1,'admin'),(2,'customer'),(3,'supplier')");

  // Detect if single-table role column exists
  $hasRole = false;
  $chkRole = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
  if ($chkRole && $chkRole->num_rows > 0) { $hasRole = true; }

  // Detect password column used
  $passCol = 'password_hash';
  $chkPass = $db->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
  if ($chkPass && $chkPass->num_rows === 0) { $passCol = 'password'; }

  $hash = password_hash($passwordPlain, PASSWORD_BCRYPT);

  // Does user already exist?
  $uid = null;
  $stmt = $db->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $stmt->bind_result($foundId);
  if ($stmt->fetch()) { $uid = (int)$foundId; }
  $stmt->close();

  if ($uid === null) {
    // Insert new user
    if ($hasRole) {
      $roleName = 'admin';
      $stmt = $db->prepare("INSERT INTO users (first_name,last_name,email,{$passCol},role) VALUES (?,?,?,?,?)");
      $stmt->bind_param('sssss', $first, $last, $email, $hash, $roleName);
      $stmt->execute();
      $uid = $stmt->insert_id;
      $stmt->close();
    } else {
      $stmt = $db->prepare("INSERT INTO users (first_name,last_name,email,{$passCol}) VALUES (?,?,?,?)");
      $stmt->bind_param('ssss', $first, $last, $email, $hash);
      $stmt->execute();
      $uid = $stmt->insert_id;
      $stmt->close();

      // Map admin role (id=1)
      $stmt = $db->prepare('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, 1)');
      $stmt->bind_param('i', $uid);
      $stmt->execute();
      $stmt->close();
    }
  } else {
    // Update password; ensure admin role
    $stmt = $db->prepare("UPDATE users SET {$passCol}=? WHERE id=?");
    $stmt->bind_param('si', $hash, $uid);
    $stmt->execute();
    $stmt->close();

    if ($hasRole) {
      $stmt = $db->prepare("UPDATE users SET role='admin' WHERE id=?");
      $stmt->bind_param('i', $uid);
      $stmt->execute();
      $stmt->close();
    } else {
      $stmt = $db->prepare('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, 1)');
      $stmt->bind_param('i', $uid);
      $stmt->execute();
      $stmt->close();
    }
  }

  echo json_encode([
    'status' => 'ok',
    'message' => 'Admin user ensured. You can now log in.',
    'login' => [
      'email' => $email,
      'password' => $passwordPlain
    ],
    'user_id' => $uid,
    'note' => 'Delete backend/seed_admin.php after first use.'
  ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Seeding failed', 'detail' => $e->getMessage()]);
}