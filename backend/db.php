<?php
// Central DB connection + ensure roles exist
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$servername = 'localhost'; // XAMPP default
$username   = 'root';      // XAMPP default MySQL user
$password   = '';          // Leave empty for XAMPP default
$dbname     = 'my_little_thingz';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset('utf8mb4');

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode([
        'status'  => 'error',
        'message' => 'Database connection failed: ' . $conn->connect_error,
    ]));
}

// Ensure roles + user_roles exist and seed default roles
function ensure_roles(mysqli $db) {
    // Roles table
    $db->query("CREATE TABLE IF NOT EXISTS roles (
      id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(50) NOT NULL UNIQUE
    ) ENGINE=InnoDB");

    // User to roles mapping
    $db->query("CREATE TABLE IF NOT EXISTS user_roles (
      user_id INT UNSIGNED NOT NULL,
      role_id TINYINT UNSIGNED NOT NULL,
      PRIMARY KEY (user_id, role_id),
      CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB");

    // Seed default roles (admin=1, customer=2, supplier=3)
    $db->query("INSERT IGNORE INTO roles (id, name) VALUES
      (1, 'admin'),
      (2, 'customer'),
      (3, 'supplier')");
}

try {
    ensure_roles($conn);
} catch (Throwable $e) {
    // Silently ignore to avoid breaking responses; endpoints can still proceed
}
?>