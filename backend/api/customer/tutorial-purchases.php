<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID, X-Tutorials-Email');

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
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }

    // Identify user by email or user_id
    $userId = null;
    $email = $_GET['email'] ?? $_SERVER['HTTP_X_TUTORIALS_EMAIL'] ?? null;
    
    if ($email) {
        // Look up user by email
        $userStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $userStmt->execute([$email]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $userId = (int)$user['id'];
        } else {
            // Create a new tutorial user if they don't exist.
            // Detect available columns to avoid schema mismatch.
            $hasPasswordHash = false;
            $hasRoleCol = false;
            try {
                $chk = $db->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
                $hasPasswordHash = $chk && $chk->rowCount() > 0;
            } catch (Throwable $e) {}
            try {
                $chk = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
                $hasRoleCol = $chk && $chk->rowCount() > 0;
            } catch (Throwable $e) {}

            if ($hasRoleCol) {
                if ($hasPasswordHash) {
                    $insertStmt = $db->prepare("INSERT INTO users (email, password_hash, role, created_at) VALUES (?, '', 'customer', NOW())");
                } else {
                    $insertStmt = $db->prepare("INSERT INTO users (email, password, role, created_at) VALUES (?, '', 'customer', NOW())");
                }
            } else {
                // roles handled via mapping tables or absent
                if ($hasPasswordHash) {
                    $insertStmt = $db->prepare("INSERT INTO users (email, password_hash, created_at) VALUES (?, '', NOW())");
                } else {
                    $insertStmt = $db->prepare("INSERT INTO users (email, password, created_at) VALUES (?, '', NOW())");
                }
            }

            $insertStmt->execute([$email]);
            $userId = (int)$db->lastInsertId();
        }
    }
    
    if (!$userId) {
        // Fallback to user_id header or query param
        if (!empty($_SERVER['HTTP_X_USER_ID'])) {
            $userId = (int)$_SERVER['HTTP_X_USER_ID'];
        } elseif (!empty($_GET['user_id'])) {
            $userId = (int)$_GET['user_id'];
        }
    }

    if (!$userId) {
        // No user identified; return empty purchases instead of 401
        echo json_encode([
            'status' => 'success',
            'purchases' => []
        ]);
        exit;
    }

    // Ensure table exists
    $db->exec("CREATE TABLE IF NOT EXISTS tutorial_purchases (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        tutorial_id INT UNSIGNED NOT NULL,
        purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expiry_date DATETIME,
        payment_method VARCHAR(50),
        payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
        amount_paid DECIMAL(10, 2),
        UNIQUE KEY unique_purchase (user_id, tutorial_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (tutorial_id) REFERENCES tutorials(id) ON DELETE CASCADE
    )");

    // Fetch user's purchased tutorials
    $stmt = $db->prepare("
        SELECT 
            tp.tutorial_id,
            tp.purchase_date,
            tp.expiry_date,
            tp.payment_method,
            t.title,
            t.video_url
        FROM tutorial_purchases tp
        JOIN tutorials t ON tp.tutorial_id = t.id
        WHERE tp.user_id = ? AND tp.payment_status = 'completed'
        ORDER BY tp.purchase_date DESC
    ");
    $stmt->execute([$userId]);
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'purchases' => $purchases
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error fetching purchases: ' . $e->getMessage()
    ]);
}
