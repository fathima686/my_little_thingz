<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID, X-Tutorial-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config/database.php';
require_once '../../middleware/FeatureGuard.php';

$database = new Database();
$db = $database->getConnection();
$featureGuard = new FeatureGuard();

// Get user ID from header
$userId = null;
$email = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? null;

if ($email) {
    $userStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $userStmt->execute([$email]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $userId = (int)$user['id'];
    }
}

if (!$userId && !empty($_SERVER['HTTP_X_USER_ID'])) {
    $userId = (int)$_SERVER['HTTP_X_USER_ID'];
}

// ENFORCE FEATURE ACCESS - Live workshops are Pro only
if ($userId) {
    $accessCheck = $featureGuard->guardFeature($userId, 'live_workshops');
    
    if (!$accessCheck['allowed']) {
        $featureGuard->sendAccessDeniedResponse($accessCheck);
        exit;
    }
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Get all active sessions with optional filtering
        $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : null;
        $status = isset($_GET['status']) ? $_GET['status'] : null; // scheduled, live, completed
        
        // Check if tables exist first
        $tableCheck = $db->query("SHOW TABLES LIKE 'live_sessions'");
        if ($tableCheck->rowCount() === 0) {
            echo json_encode([
                'status' => 'success',
                'sessions' => []
            ]);
            exit;
        }
        
        $query = "SELECT 
            ls.*,
            lsub.name as subject_name,
            lsub.description as subject_description,
            lsub.color as subject_color,
            u.email as teacher_email,
            COALESCE(COUNT(DISTINCT lsr.id), 0) as registered_count,
            MAX(CASE WHEN lsr.user_id = ? THEN 1 ELSE 0 END) as is_registered
        FROM live_sessions ls
        JOIN live_subjects lsub ON ls.subject_id = lsub.id
        JOIN users u ON ls.teacher_id = u.id
        LEFT JOIN live_session_registrations lsr ON ls.id = lsr.session_id
        WHERE lsub.is_active = 1";
        
        $params = [$userId];
        
        if ($subjectId) {
            $query .= " AND ls.subject_id = ?";
            $params[] = $subjectId;
        }
        
        if ($status) {
            $query .= " AND ls.status = ?";
            $params[] = $status;
        } else {
            // Default: show scheduled and live sessions
            $query .= " AND ls.status IN ('scheduled', 'live')";
        }
        
        $query .= " GROUP BY ls.id 
                    ORDER BY 
                        CASE ls.status 
                            WHEN 'live' THEN 1 
                            WHEN 'scheduled' THEN 2 
                            ELSE 3 
                        END,
                        ls.scheduled_date ASC, 
                        ls.scheduled_time ASC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format dates and times for frontend
        foreach ($sessions as &$session) {
            $session['scheduled_datetime'] = $session['scheduled_date'] . ' ' . $session['scheduled_time'];
            $session['is_registered'] = (bool)$session['is_registered'];
        }
        
        echo json_encode([
            'status' => 'success',
            'sessions' => $sessions
        ]);
        
    } elseif ($method === 'POST') {
        // Register for a session
        if ($userId <= 0) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['session_id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Session ID required']);
            exit;
        }
        
        $sessionId = (int)$data['session_id'];
        
        // Check if session exists and has capacity
        $sessionCheck = $db->prepare("
            SELECT ls.*, COUNT(DISTINCT lsr.id) as registered_count
            FROM live_sessions ls
            LEFT JOIN live_session_registrations lsr ON ls.id = lsr.session_id
            WHERE ls.id = ? AND ls.status IN ('scheduled', 'live')
            GROUP BY ls.id
        ");
        $sessionCheck->execute([$sessionId]);
        $session = $sessionCheck->fetch(PDO::FETCH_ASSOC);
        
        if (!$session) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Session not found or not available']);
            exit;
        }
        
        if ($session['registered_count'] >= $session['max_participants']) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Session is full']);
            exit;
        }
        
        // Check if already registered
        $regCheck = $db->prepare("SELECT id FROM live_session_registrations WHERE session_id = ? AND user_id = ?");
        $regCheck->execute([$sessionId, $userId]);
        if ($regCheck->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Already registered for this session']);
            exit;
        }
        
        // Register
        $stmt = $db->prepare("INSERT INTO live_session_registrations (session_id, user_id) VALUES (?, ?)");
        $stmt->execute([$sessionId, $userId]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Registered successfully for the live session'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

