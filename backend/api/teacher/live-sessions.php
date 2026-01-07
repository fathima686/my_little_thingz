<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-ID, X-Admin-User-Id, X-Admin-Email');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get teacher/admin user ID from headers (support both admin and teacher headers)
$teacherId = isset($_SERVER['HTTP_X_USER_ID']) ? (int)$_SERVER['HTTP_X_USER_ID'] : 0;
if ($teacherId <= 0) {
    // Try admin header as fallback
    $teacherId = isset($_SERVER['HTTP_X_ADMIN_USER_ID']) ? (int)$_SERVER['HTTP_X_ADMIN_USER_ID'] : 0;
}
$token = isset($_SERVER['HTTP_AUTHORIZATION']) ? str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']) : '';

if ($teacherId <= 0) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized - User ID required']);
    exit;
}

// Verify teacher or admin role (token is optional for admins)
$roleCheck = $db->prepare("
    SELECT r.name as role_name FROM user_roles ur 
    JOIN roles r ON ur.role_id = r.id 
    WHERE ur.user_id = ? AND (r.name = 'teacher' OR r.name = 'admin')
    LIMIT 1
");
$roleCheck->execute([$teacherId]);
$roleResult = $roleCheck->fetch(PDO::FETCH_ASSOC);

if (!$roleResult) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Teacher or Admin access required']);
    exit;
}

// For teachers, require token; for admins, token is optional
if ($roleResult['role_name'] === 'teacher' && !$token) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authorization token required for teachers']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Get all sessions created by this teacher
        $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : null;
        
        $query = "SELECT 
            ls.*,
            lsub.name as subject_name,
            lsub.color as subject_color,
            COUNT(DISTINCT lsr.id) as registered_count
        FROM live_sessions ls
        JOIN live_subjects lsub ON ls.subject_id = lsub.id
        LEFT JOIN live_session_registrations lsr ON ls.id = lsr.session_id
        WHERE ls.teacher_id = ?";
        
        $params = [$teacherId];
        
        if ($subjectId) {
            $query .= " AND ls.subject_id = ?";
            $params[] = $subjectId;
        }
        
        $query .= " GROUP BY ls.id ORDER BY ls.scheduled_date DESC, ls.scheduled_time DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'sessions' => $sessions
        ]);
        
    } elseif ($method === 'POST') {
        // Create new session
        $data = json_decode(file_get_contents('php://input'), true);
        
        $required = ['subject_id', 'title', 'google_meet_link', 'scheduled_date', 'scheduled_time'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
                exit;
            }
        }
        
        // Validate subject exists, or create it if it's a tutorial category name
        $subjectId = null;
        
        // First try to get by ID
        if (is_numeric($data['subject_id'])) {
            $subjectCheck = $db->prepare("SELECT id FROM live_subjects WHERE id = ? AND is_active = 1");
            $subjectCheck->execute([$data['subject_id']]);
            $subject = $subjectCheck->fetch(PDO::FETCH_ASSOC);
            if ($subject) {
                $subjectId = $subject['id'];
            }
        }
        
        // If not found by ID, try to find by name (in case category name was passed)
        if (!$subjectId) {
            $subjectName = is_numeric($data['subject_id']) ? null : $data['subject_id'];
            if ($subjectName) {
                $nameCheck = $db->prepare("SELECT id FROM live_subjects WHERE name = ? AND is_active = 1");
                $nameCheck->execute([$subjectName]);
                $subject = $nameCheck->fetch(PDO::FETCH_ASSOC);
                if ($subject) {
                    $subjectId = $subject['id'];
                } else {
                    // Check if it's a valid tutorial category and create subject
                    $tutorialCheck = $db->prepare("SELECT DISTINCT category FROM tutorials WHERE category = ? AND is_active = 1 LIMIT 1");
                    $tutorialCheck->execute([$subjectName]);
                    if ($tutorialCheck->rowCount() > 0) {
                        // Create the subject
                        $categoryColors = [
                            'Hand Embroidery' => '#FFB6C1',
                            'Resin Art' => '#B0E0E6',
                            'Gift Making' => '#FFDAB9',
                            'Mylanchi / Mehandi Art' => '#E6E6FA',
                            'Mehandi Art' => '#E6E6FA',
                            'Candle Making' => '#F0E68C',
                            'Jewelry Making' => '#FFC0CB',
                            'Clay Modeling' => '#DDA0DD',
                            'default' => '#667eea'
                        ];
                        $color = $categoryColors[$subjectName] ?? $categoryColors['default'];
                        $createStmt = $db->prepare("
                            INSERT INTO live_subjects (name, description, color, is_active)
                            VALUES (?, ?, ?, 1)
                        ");
                        $createStmt->execute([
                            $subjectName,
                            "Live classes for {$subjectName}",
                            $color
                        ]);
                        $subjectId = $db->lastInsertId();
                    }
                }
            }
        }
        
        if (!$subjectId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid subject. Please select a valid tutorial category.']);
            exit;
        }
        
        // Validate date and time
        $scheduledDateTime = $data['scheduled_date'] . ' ' . $data['scheduled_time'];
        if (strtotime($scheduledDateTime) < time()) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Scheduled time must be in the future']);
            exit;
        }
        
        $stmt = $db->prepare("
            INSERT INTO live_sessions (
                subject_id, teacher_id, title, description, 
                google_meet_link, scheduled_date, scheduled_time, 
                duration_minutes, max_participants, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')
        ");
        
        $stmt->execute([
            $subjectId,
            $teacherId,
            $data['title'],
            $data['description'] ?? null,
            $data['google_meet_link'],
            $data['scheduled_date'],
            $data['scheduled_time'],
            $data['duration_minutes'] ?? 60,
            $data['max_participants'] ?? 50
        ]);
        
        $sessionId = $db->lastInsertId();
        
        // Fetch created session with subject details
        $fetchStmt = $db->prepare("
            SELECT ls.*, lsub.name as subject_name, lsub.color as subject_color
            FROM live_sessions ls
            JOIN live_subjects lsub ON ls.subject_id = lsub.id
            WHERE ls.id = ?
        ");
        $fetchStmt->execute([$sessionId]);
        $session = $fetchStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Live session created successfully',
            'session' => $session
        ]);
        
    } elseif ($method === 'PUT') {
        // Update session
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Session ID required']);
            exit;
        }
        
        $sessionId = (int)$data['id'];
        
        // Verify ownership
        $ownerCheck = $db->prepare("SELECT id FROM live_sessions WHERE id = ? AND teacher_id = ?");
        $ownerCheck->execute([$sessionId, $teacherId]);
        if ($ownerCheck->rowCount() === 0) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Session not found or access denied']);
            exit;
        }
        
        $updates = [];
        $params = [];
        
        if (isset($data['title'])) {
            $updates[] = "title = ?";
            $params[] = $data['title'];
        }
        if (isset($data['description'])) {
            $updates[] = "description = ?";
            $params[] = $data['description'];
        }
        if (isset($data['google_meet_link'])) {
            $updates[] = "google_meet_link = ?";
            $params[] = $data['google_meet_link'];
        }
        if (isset($data['scheduled_date'])) {
            $updates[] = "scheduled_date = ?";
            $params[] = $data['scheduled_date'];
        }
        if (isset($data['scheduled_time'])) {
            $updates[] = "scheduled_time = ?";
            $params[] = $data['scheduled_time'];
        }
        if (isset($data['duration_minutes'])) {
            $updates[] = "duration_minutes = ?";
            $params[] = (int)$data['duration_minutes'];
        }
        if (isset($data['max_participants'])) {
            $updates[] = "max_participants = ?";
            $params[] = (int)$data['max_participants'];
        }
        if (isset($data['status'])) {
            $updates[] = "status = ?";
            $params[] = $data['status'];
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'No fields to update']);
            exit;
        }
        
        $params[] = $sessionId;
        $params[] = $teacherId;
        
        $query = "UPDATE live_sessions SET " . implode(', ', $updates) . " WHERE id = ? AND teacher_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        // Fetch updated session
        $fetchStmt = $db->prepare("
            SELECT ls.*, lsub.name as subject_name, lsub.color as subject_color
            FROM live_sessions ls
            JOIN live_subjects lsub ON ls.subject_id = lsub.id
            WHERE ls.id = ?
        ");
        $fetchStmt->execute([$sessionId]);
        $session = $fetchStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Session updated successfully',
            'session' => $session
        ]);
        
    } elseif ($method === 'DELETE') {
        // Delete session
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Session ID required']);
            exit;
        }
        
        $sessionId = (int)$data['id'];
        
        // Verify ownership
        $ownerCheck = $db->prepare("SELECT id FROM live_sessions WHERE id = ? AND teacher_id = ?");
        $ownerCheck->execute([$sessionId, $teacherId]);
        if ($ownerCheck->rowCount() === 0) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Session not found or access denied']);
            exit;
        }
        
        // Delete session (cascade will handle registrations)
        $stmt = $db->prepare("DELETE FROM live_sessions WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$sessionId, $teacherId]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Session deleted successfully'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

