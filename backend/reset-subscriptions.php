<?php
// Reset subscriptions for testing - ONLY FOR DEVELOPMENT
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? null;
    
    if (!$email) {
        echo json_encode(['error' => 'Email required']);
        exit;
    }
    
    // Find user
    $userStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $userStmt->execute([$email]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['message' => 'No user found with that email']);
        exit;
    }
    
    $userId = $user['id'];
    
    // Get existing subscriptions
    $existingStmt = $db->prepare("SELECT * FROM subscriptions WHERE user_id = ?");
    $existingStmt->execute([$userId]);
    $existing = $existingStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Delete all subscriptions for this user
    $deleteStmt = $db->prepare("DELETE FROM subscriptions WHERE user_id = ?");
    $deleteStmt->execute([$userId]);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Reset subscriptions for ' . $email,
        'deleted_subscriptions' => count($existing),
        'subscriptions' => $existing
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>