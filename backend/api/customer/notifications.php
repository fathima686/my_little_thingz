<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Tutorial-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Get user email from header with fallback
$userEmail = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? $_GET['email'] ?? 'soudhame52@gmail.com';

// Log for debugging
error_log("Notifications API - Email received: " . ($userEmail ?? 'none'));

if (empty($userEmail)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error', 
        'message' => 'User email required',
        'debug' => [
            'headers' => array_filter($_SERVER, function($key) {
                return strpos($key, 'HTTP_') === 0;
            }, ARRAY_FILTER_USE_KEY),
            'get_params' => $_GET
        ]
    ]);
    exit;
}

// Get user ID from email
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$userEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }
    
    $userId = $user['id'];
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to get user']);
    exit;
}

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetNotifications($pdo, $userId);
        break;
    case 'PUT':
        handleUpdateNotification($pdo, $userId);
        break;
    case 'POST':
        handleCreateNotification($pdo, $userId);
        break;
    default:
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        break;
}

function handleGetNotifications($pdo, $userId) {
    try {
        $limit = $_GET['limit'] ?? 10;
        $offset = $_GET['offset'] ?? 0;
        
        // Get notifications for user
        $stmt = $pdo->prepare("
            SELECT id, title, message, type, action_url, is_read, created_at 
            FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, (int)$limit, (int)$offset]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get unread count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        $unreadCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo json_encode([
            'status' => 'success',
            'notifications' => $notifications,
            'unread_count' => (int)$unreadCount
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch notifications']);
    }
}

function handleUpdateNotification($pdo, $userId) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['mark_all_read']) && $input['mark_all_read']) {
            // Mark all notifications as read
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            echo json_encode(['status' => 'success', 'message' => 'All notifications marked as read']);
        } elseif (isset($input['notification_id'])) {
            // Mark specific notification as read
            $notificationId = $input['notification_id'];
            
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$notificationId, $userId]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Notification marked as read']);
            } else {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Notification not found']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to update notification']);
    }
}

function handleCreateNotification($pdo, $userId) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $title = $input['title'] ?? '';
        $message = $input['message'] ?? '';
        $type = $input['type'] ?? 'info';
        $actionUrl = $input['action_url'] ?? null;
        
        if (empty($title) || empty($message)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Title and message are required']);
            return;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, action_url, is_read, created_at) 
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$userId, $title, $message, $type, $actionUrl]);
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Notification created',
            'notification_id' => $pdo->lastInsertId()
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to create notification']);
    }
}
?>