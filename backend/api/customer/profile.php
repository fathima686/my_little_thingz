<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode([ 'status' => 'error', 'message' => 'Method not allowed' ]);
        exit;
    }

    // Identify user
    $userId = null;
    if (!empty($_SERVER['HTTP_X_USER_ID'])) {
        $userId = (int)$_SERVER['HTTP_X_USER_ID'];
    } elseif (!empty($_GET['user_id'])) {
        $userId = (int)$_GET['user_id'];
    }

    if (!$userId) {
        http_response_code(401);
        echo json_encode([ 'status' => 'error', 'message' => 'Missing user identity' ]);
        exit;
    }

    // Fetch basic profile
    $stmt = $db->prepare("SELECT id, first_name, last_name, email, created_at, updated_at FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        http_response_code(404);
        echo json_encode([ 'status' => 'error', 'message' => 'User not found' ]);
        exit;
    }

    // Fetch roles
    $rolesStmt = $db->prepare("SELECT r.name FROM user_roles ur JOIN roles r ON ur.role_id=r.id WHERE ur.user_id=?");
    $rolesStmt->execute([$userId]);
    $roles = [];
    while ($row = $rolesStmt->fetch(PDO::FETCH_ASSOC)) { $roles[] = $row['name']; }
    if (count($roles) === 0) { $roles = ['customer']; }

    // Supplier status if applicable
    $supplierStatus = null;
    if (in_array('supplier', array_map('strtolower', $roles), true)) {
        // ensure table exists gracefully (no-op if exists)
        $db->exec("CREATE TABLE IF NOT EXISTS supplier_profiles (user_id INT UNSIGNED PRIMARY KEY, status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");
        $sp = $db->prepare("SELECT status FROM supplier_profiles WHERE user_id=? LIMIT 1");
        $sp->execute([$userId]);
        $row = $sp->fetch(PDO::FETCH_ASSOC);
        $supplierStatus = $row['status'] ?? 'pending';
    }

    // Linked auth providers (e.g., Google)
    $providers = [];
    try {
        $ap = $db->prepare("SELECT provider, provider_user_id, created_at FROM auth_providers WHERE user_id=?");
        $ap->execute([$userId]);
        while ($r = $ap->fetch(PDO::FETCH_ASSOC)) {
            $providers[] = [
                'provider' => $r['provider'],
                'provider_user_id' => $r['provider_user_id'],
                'linked_at' => $r['created_at']
            ];
        }
    } catch (Throwable $e) { /* table may not exist; ignore */ }

    // Detect profile image under uploads/profile-images/user_{id}.*
    $uploadRelDir = '/my_little_thingz/backend/uploads/profile-images';
    $uploadFsDir = __DIR__ . '/../../uploads/profile-images';
    if (!is_dir($uploadFsDir)) { @mkdir($uploadFsDir, 0775, true); }
    $imgUrl = null;
    foreach (['jpg','jpeg','png','webp'] as $ext) {
        $candidate = $uploadFsDir . "/user_{$userId}.{$ext}";
        if (file_exists($candidate)) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $imgUrl = $scheme . '://' . $host . $uploadRelDir . "/user_{$userId}.{$ext}?v=" . filemtime($candidate);
            break;
        }
    }

    echo json_encode([
        'status' => 'success',
        'profile' => [
            'id' => (int)$user['id'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'roles' => $roles,
            'supplier_status' => $supplierStatus,
            'created_at' => $user['created_at'],
            'updated_at' => $user['updated_at'],
            'providers' => $providers,
            'profile_image_url' => $imgUrl,
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}