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

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get user ID from header
    $userId = isset($_SERVER['HTTP_X_USER_ID']) ? (int)$_SERVER['HTTP_X_USER_ID'] : 1;
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Check if tables exist first
        $tableCheck = $db->query("SHOW TABLES LIKE 'live_sessions'");
        if ($tableCheck->rowCount() === 0) {
            echo json_encode([
                'status' => 'success',
                'sessions' => [],
                'message' => 'No live_sessions table found. Please run the database setup.'
            ]);
            exit;
        }
        
        // Get all active sessions with optional filtering
        $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : null;
        
        $query = "SELECT 
            ls.*,
            COALESCE(lsub.name, 'Unknown Subject') as subject_name,
            COALESCE(lsub.description, '') as subject_description,
            COALESCE(lsub.color, '#667eea') as subject_color,
            COALESCE(u.email, 'Unknown Teacher') as teacher_email,
            COALESCE(COUNT(DISTINCT lsr.id), 0) as registered_count,
            MAX(CASE WHEN lsr.user_id = ? THEN 1 ELSE 0 END) as is_registered
        FROM live_sessions ls
        LEFT JOIN live_subjects lsub ON ls.subject_id = lsub.id
        LEFT JOIN users u ON ls.teacher_id = u.id
        LEFT JOIN live_session_registrations lsr ON ls.id = lsr.session_id
        WHERE 1=1";
        
        $params = [$userId];
        
        if ($subjectId) {
            $query .= " AND ls.subject_id = ?";
            $params[] = $subjectId;
        }
        
        // Default: show scheduled and live sessions
        $query .= " AND ls.status IN ('scheduled', 'live')";
        
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
            'sessions' => $sessions,
            'message' => 'Sessions loaded successfully (bypassing feature guard for testing)'
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage(),
        'debug' => 'Error in live-sessions-simple.php'
    ]);
}
?>