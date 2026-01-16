<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    require_once '../../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

$userEmail = $_GET['email'] ?? $_POST['email'] ?? $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? '';
$requestedName = null;

// Debug info
$debugInfo = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'email' => $userEmail,
    'raw_input' => '',
    'decoded_input' => null,
    'requested_name_raw' => null,
    'requested_name_processed' => null
];

// Capture name override from POST body (JSON or form-data)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $debugInfo['raw_input'] = $rawInput;
    
    $inputData = [];
    if (!empty($rawInput)) {
        $decoded = json_decode($rawInput, true);
        $debugInfo['decoded_input'] = $decoded;
        if (is_array($decoded)) {
            $inputData = $decoded;
        }
    }
    
    $requestedName = $inputData['name'] ?? ($_POST['name'] ?? null);
    $debugInfo['requested_name_raw'] = $requestedName;
    
    if (is_string($requestedName)) {
        $requestedName = trim($requestedName);
        // Remove repeated whitespace
        $requestedName = preg_replace('/\s+/', ' ', $requestedName);
        // Limit length to avoid abuse
        if (strlen($requestedName) > 80) {
            $requestedName = substr($requestedName, 0, 80);
        }
        if ($requestedName === '') {
            $requestedName = null;
        }
    } else {
        $requestedName = null;
    }
    
    $debugInfo['requested_name_processed'] = $requestedName;
}

if (empty($userEmail)) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Email parameter required',
        'debug' => $debugInfo
    ]);
    exit;
}

try {
    // Get user ID first
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $userStmt->execute([$userEmail]);
    $userForSub = $userStmt->fetch(PDO::FETCH_ASSOC);
    $userIdForSub = $userForSub['id'] ?? null;
    
    // Check if user has Pro subscription
    $isPro = ($userEmail === 'soudhame52@gmail.com');
    
    if (!$isPro && $userIdForSub) {
        $subStmt = $pdo->prepare("
            SELECT s.status, sp.plan_code 
            FROM subscriptions s
            LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
            WHERE s.user_id = ? AND s.status = 'active'
            ORDER BY s.created_at DESC 
            LIMIT 1
        ");
        $subStmt->execute([$userIdForSub]);
        $subscription = $subStmt->fetch(PDO::FETCH_ASSOC);
        
        $isPro = ($subscription && $subscription['plan_code'] === 'pro' && $subscription['status'] === 'active');
    }
    
    if (!$isPro) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Certificates are only available for Pro subscribers',
            'upgrade_required' => true,
            'debug' => $debugInfo
        ]);
        exit;
    }
    
    // Get user details - try to get name field first, then first_name/last_name
    try {
        // Try to get name field (if it exists)
        $userStmt = $pdo->prepare("SELECT id, name, first_name, last_name FROM users WHERE email = ?");
        $userStmt->execute([$userEmail]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // If name column doesn't exist, try without it
        $userStmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE email = ?");
        $userStmt->execute([$userEmail]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$user) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found',
            'debug' => $debugInfo
        ]);
        exit;
    }
    
    $userId = $user['id'];
    
    // Debug user data
    $debugInfo['user_data'] = $user;
    
    // Get user name - priority: request override > name field > first_name + last_name > email extraction
    $userName = '';
    $nameSource = '';
    
    // 1) Use requested override if provided
    if (!empty($requestedName)) {
        $userName = $requestedName;
        $nameSource = 'requested_override';
    }
    
    // 2) Try name field
    if (empty($userName) && !empty($user['name'])) {
        $userName = trim($user['name']);
        $nameSource = 'database_name_field';
    }
    
    // 3) Try first_name + last_name
    if (empty($userName)) {
        $userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        $userName = trim($userName);
        if (!empty($userName)) {
            $nameSource = 'database_first_last_name';
        }
    }
    
    // 4) Extract from email
    if (empty($userName)) {
        $emailParts = explode('@', $userEmail);
        $emailName = $emailParts[0] ?? '';
        
        // Clean up email name (remove numbers, dots, underscores, etc.)
        $emailName = preg_replace('/[0-9._-]+/', ' ', $emailName);
        $emailName = trim($emailName);
        
        // Capitalize first letter of each word
        if (!empty($emailName)) {
            $emailName = ucwords(strtolower($emailName));
            $userName = $emailName;
            $nameSource = 'email_extraction';
        }
    }
    
    // Final fallback
    if (empty($userName)) {
        $userName = 'Student';
        $nameSource = 'fallback';
    }
    
    $debugInfo['final_name'] = $userName;
    $debugInfo['name_source'] = $nameSource;
    
    // Return debug information
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'debug_success',
        'message' => 'Certificate name resolution debug',
        'certificate_name' => $userName,
        'debug' => $debugInfo
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Certificate generation failed: ' . $e->getMessage(),
        'debug' => $debugInfo
    ]);
}
?>