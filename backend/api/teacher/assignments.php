<?php
/**
 * Teacher Assignments API
 * Handles assignment management for teachers
 * Methods: GET (list), POST (create), PUT (update), DELETE (delete)
 * Integrates with existing tutorial system authentication
 */

// CORS headers - reuse existing tutorial system CORS setup
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_origins = [
    'http://localhost',
    'http://127.0.0.1',
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://localhost:8080'
];
if ($origin && in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: *");
}
header("Vary: Origin");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-User-Id, X-Tutorial-Email");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Include required files
require_once '../../models/AssignmentManager.php';

// Database connection - reuse existing tutorial system connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli("localhost", "root", "", "my_little_thingz");
$mysqli->set_charset('utf8mb4');
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "DB connect failed: " . $mysqli->connect_error]);
    exit;
}

// Authentication - support both admin header and tutorial email
$teacherId = null;
$teacherEmail = null;

// Check for admin authentication (existing system)
$adminUserId = $_SERVER['HTTP_X_ADMIN_USER_ID'] ?? null;
if ($adminUserId) {
    $teacherId = $adminUserId;
}

// Check for tutorial email authentication
$tutorialEmail = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? null;
if ($tutorialEmail && !$teacherId) {
    // Get user ID from email
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $tutorialEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $teacherId = $row['id'];
        $teacherEmail = $tutorialEmail;
    }
}

if (!$teacherId) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Authentication required"]);
    exit;
}

// Verify teacher role
$stmt = $mysqli->prepare("SELECT roles FROM users WHERE id = ?");
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $roles = json_decode($row['roles'], true) ?: [];
    if (!in_array('teacher', $roles) && !in_array('admin', $roles)) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Teacher permissions required"]);
        exit;
    }
} else {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit;
}

// Initialize Assignment Manager
$assignmentManager = new AssignmentManager($mysqli);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($assignmentManager, $teacherId, $action);
            break;
        case 'POST':
            handlePostRequest($assignmentManager, $teacherId);
            break;
        case 'PUT':
            handlePutRequest($assignmentManager, $teacherId);
            break;
        case 'DELETE':
            handleDeleteRequest($assignmentManager, $teacherId);
            break;
        default:
            http_response_code(405);
            echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

/**
 * Handle GET requests
 */
function handleGetRequest($assignmentManager, $teacherId, $action) {
    global $mysqli;
    
    if ($action === 'subjects') {
        // Get all subjects (tutorial categories)
        $result = $mysqli->query("SELECT id, name, description FROM subjects ORDER BY name");
        $subjects = [];
        while ($row = $result->fetch_assoc()) {
            $subjects[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'description' => $row['description']
            ];
        }
        echo json_encode([
            "status" => "success",
            "subjects" => $subjects
        ]);
        return;
    }
    
    if ($action === 'topics') {
        $subjectId = $_GET['subject_id'] ?? null;
        if (!$subjectId) {
            throw new Exception("Subject ID is required");
        }
        
        $stmt = $mysqli->prepare("SELECT id, name, description FROM topics WHERE subject_id = ? ORDER BY name");
        $stmt->bind_param("i", $subjectId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $topics = [];
        while ($row = $result->fetch_assoc()) {
            $topics[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'description' => $row['description']
            ];
        }
        
        echo json_encode([
            "status" => "success",
            "topics" => $topics
        ]);
        return;
    }
    
    if ($action === 'statistics') {
        $assignmentId = $_GET['assignment_id'] ?? null;
        if (!$assignmentId) {
            throw new Exception("Assignment ID is required");
        }
        
        $stats = $assignmentManager->getAssignmentStatistics($assignmentId);
        echo json_encode([
            "status" => "success",
            "statistics" => $stats
        ]);
        return;
    }
    
    // Default: Get teacher's assignments
    $topicId = $_GET['topic_id'] ?? null;
    $subjectId = $_GET['subject_id'] ?? null;
    
    $sql = "
        SELECT 
            a.*,
            s.name as subject_name,
            t.name as topic_name,
            COUNT(sub.id) as submission_count,
            COUNT(e.id) as evaluated_count
        FROM assignments a
        JOIN subjects s ON a.subject_id = s.id
        JOIN topics t ON a.topic_id = t.id
        LEFT JOIN submissions sub ON a.id = sub.assignment_id
        LEFT JOIN evaluations e ON sub.id = e.submission_id
        WHERE a.teacher_id = ? AND a.status != 'archived'
    ";
    
    $params = [$teacherId];
    $types = "i";
    
    if ($topicId) {
        $sql .= " AND a.topic_id = ?";
        $params[] = $topicId;
        $types .= "i";
    } elseif ($subjectId) {
        $sql .= " AND a.subject_id = ?";
        $params[] = $subjectId;
        $types .= "i";
    }
    
    $sql .= " GROUP BY a.id ORDER BY a.due_date ASC, a.created_at DESC";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $assignments = [];
    while ($row = $result->fetch_assoc()) {
        $assignment = [
            'id' => (int)$row['id'],
            'subject_id' => (int)$row['subject_id'],
            'topic_id' => (int)$row['topic_id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'due_date' => $row['due_date'],
            'max_marks' => (int)$row['max_marks'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'subject_name' => $row['subject_name'],
            'topic_name' => $row['topic_name'],
            'submission_count' => (int)$row['submission_count'],
            'evaluated_count' => (int)$row['evaluated_count'],
            'urgency_status' => getUrgencyStatus($row['due_date'])
        ];
        $assignments[] = $assignment;
    }
    
    echo json_encode([
        "status" => "success",
        "assignments" => $assignments
    ]);
}

/**
 * Handle POST requests (create assignment)
 */
function handlePostRequest($assignmentManager, $teacherId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception("Invalid JSON input");
    }
    
    $requiredFields = ['subject_id', 'topic_id', 'title', 'description', 'due_date', 'max_marks'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || $input[$field] === '') {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $assignmentData = [
        'title' => $input['title'],
        'description' => $input['description'],
        'due_date' => $input['due_date'],
        'max_marks' => (int)$input['max_marks']
    ];
    
    $assignment = $assignmentManager->createAssignment(
        $teacherId,
        (int)$input['subject_id'],
        (int)$input['topic_id'],
        $assignmentData
    );
    
    echo json_encode([
        "status" => "success",
        "message" => "Assignment created successfully",
        "assignment" => $assignment
    ]);
}

/**
 * Handle PUT requests (update assignment)
 */
function handlePutRequest($assignmentManager, $teacherId) {
    $assignmentId = $_GET['id'] ?? null;
    if (!$assignmentId) {
        throw new Exception("Assignment ID is required");
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception("Invalid JSON input");
    }
    
    $assignment = $assignmentManager->updateAssignment($assignmentId, $input, $teacherId);
    
    echo json_encode([
        "status" => "success",
        "message" => "Assignment updated successfully",
        "assignment" => $assignment
    ]);
}

/**
 * Handle DELETE requests
 */
function handleDeleteRequest($assignmentManager, $teacherId) {
    $assignmentId = $_GET['id'] ?? null;
    if (!$assignmentId) {
        throw new Exception("Assignment ID is required");
    }
    
    $assignmentManager->deleteAssignment($assignmentId, $teacherId);
    
    echo json_encode([
        "status" => "success",
        "message" => "Assignment deleted successfully"
    ]);
}

/**
 * Get urgency status based on due date
 */
function getUrgencyStatus($dueDate) {
    $due = new DateTime($dueDate);
    $now = new DateTime();
    $diff = $now->diff($due);
    
    if ($due < $now) {
        return 'overdue';
    } elseif ($diff->days <= 7) {
        return 'due_soon';
    } else {
        return 'active';
    }
}

$mysqli->close();
?>