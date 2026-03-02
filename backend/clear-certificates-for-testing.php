<?php
header('Content-Type: application/json');

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Clear certificates for the test user
    $email = 'soudhame52@gmail.com';
    
    // Get user ID
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $userStmt->execute([$email]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $userId = $user['id'];
        
        // Delete existing certificates
        $deleteStmt = $pdo->prepare("DELETE FROM certificates WHERE user_id = ?");
        $deleteStmt->execute([$userId]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Certificates cleared for testing',
            'user_id' => $userId,
            'email' => $email
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>