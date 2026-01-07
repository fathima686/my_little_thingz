<?php
/**
 * Student Assignments API
 * Handles assignment viewing for students
 * Methods: GET (list assignments by topic/subject)
 * Integrates with existing tutorial authentication
 */

// CORS headers
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
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Tutorial-Email");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Include required files
require_once '../../models/AssignmentManager.php';

// Database connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli("localhost", "root", "", "my_little_thingz");
$mysqli->set_charset('utf8mb4');
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "DB connect failed: " . $mysqli->connect_error]);
    exit;
}

// Authentication using tutorial email
$tutorialEmail = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? null;
if (!$tutorialEmail) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Authentication required"]);
    exit;
}

// Get student ID from email
$stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $tutorialEmail);
$stmt->execute();
$result = $stmt->get_result();
if (!$row = $result->fetch_assoc()) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit;
}
$studentId = $row['id'];

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    if ($method === 'GET') {
        if ($action === 'subjects') {
            // Get all subjects with assignment counts
            $stmt = $mysqli->prepare("
                SELECT 
                    s.id,
                    s.name,
                    s.description,
                    COUNT(a.id) as assignment_count
                FROM subjects s
                LEFT JOIN topics t ON s.id = t.subject_id
                LEFT JOIN assignments a ON t.id = a.topic_id AND a.status = 'active'
                GROUP BY s.id
                ORDER BY s.name
            ");
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $subjects = [];
            while ($row = $result->fetch_assoc()) {
                $subjects[] = [
                    'id' => (int)$row['id'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'assignment_count' => (int)$row['assignment_count']
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
            
            // Get topics with assignment counts
            $stmt = $mysqli->prepare("
                SELECT 
                    t.id,
                    t.name,
                    t.description,
                    COUNT(a.id) as assignment_count,
                    COUNT(s.id) as my_submissions
                FROM topics t
                LEFT JOIN assignments a ON t.id = a.topic_id AND a.status = 'active'
                LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = ?
                WHERE t.subject_id = ?
                GROUP BY t.id
                ORDER BY t.name
            ");
            
            $stmt->bind_param("ii", $studentId, $subjectId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $topics = [];
            while ($row = $result->fetch_assoc()) {
                $topics[] = [
                    'id' => (int)$row['id'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'assignment_count' => (int)$row['assignment_count'],
                    'my_submissions' => (int)$row['my_submissions']
                ];
            }
            
            echo json_encode([
                "status" => "success",
                "topics" => $topics
            ]);
            return;
        }
        
        // Default: Get assignments
        $topicId = $_GET['topic_id'] ?? null;
        $subjectId = $_GET['subject_id'] ?? null;
        $assignmentId = $_GET['assignment_id'] ?? null;
        
        if ($assignmentId) {
            // Get specific assignment details
            $assignmentManager = new AssignmentManager($mysqli);
            $assignment = $assignmentManager->getAssignmentById($assignmentId);
            
            if (!$assignment) {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Assignment not found"]);
                return;
            }
            
            // Get student's submission if exists
            $stmt = $mysqli->prepare("
                SELECT 
                    s.*,
                    e.marks_awarded,
                    e.feedback,
                    e.evaluated_at
                FROM submissions s
                LEFT JOIN evaluations e ON s.id = e.submission_id
                WHERE s.assignment_id = ? AND s.student_id = ?
            ");
            
            $stmt->bind_param("ii", $assignmentId, $studentId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $submission = null;
            if ($row = $result->fetch_assoc()) {
                $submission = [
                    'id' => (int)$row['id'],
                    'submission_type' => $row['submission_type'],
                    'content' => $row['content'],
                    'file_path' => $row['file_path'],
                    'file_name' => $row['file_name'],
                    'file_size' => $row['file_size'] ? (int)$row['file_size'] : null,
                    'submitted_at' => $row['submitted_at'],
                    'status' => $row['status'],
                    'marks_awarded' => $row['marks_awarded'] ? (int)$row['marks_awarded'] : null,
                    'feedback' => $row['feedback'],
                    'evaluated_at' => $row['evaluated_at'],
                    'is_evaluated' => !is_null($row['marks_awarded'])
                ];
                
                if ($submission['file_path']) {
                    $submission['file_download_url'] = 'http://localhost/my_little_thingz/backend/uploads/assignments/' . $submission['file_path'];
                }
            }
            
            $assignment['submission'] = $submission;
            $assignment['can_submit'] = is_null($submission) && new DateTime($assignment['due_date']) > new DateTime();
            
            echo json_encode([
                "status" => "success",
                "assignment" => $assignment
            ]);
            return;
        }
        
        // Get assignments list
        $sql = "
            SELECT 
                a.*,
                s.name as subject_name,
                t.name as topic_name,
                u.email as teacher_email,
                sub.id as submission_id,
                sub.status as submission_status,
                sub.submitted_at,
                e.marks_awarded,
                e.feedback,
                CASE 
                    WHEN a.due_date < NOW() THEN 'overdue'
                    WHEN a.due_date < DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'due_soon'
                    ELSE 'active'
                END as urgency_status
            FROM assignments a
            JOIN subjects s ON a.subject_id = s.id
            JOIN topics t ON a.topic_id = t.id
            JOIN users u ON a.teacher_id = u.id
            LEFT JOIN submissions sub ON a.id = sub.assignment_id AND sub.student_id = ?
            LEFT JOIN evaluations e ON sub.id = e.submission_id
            WHERE a.status = 'active'
        ";
        
        $params = [$studentId];
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
        
        $sql .= " ORDER BY a.due_date ASC, a.created_at DESC";
        
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
                'created_at' => $row['created_at'],
                'subject_name' => $row['subject_name'],
                'topic_name' => $row['topic_name'],
                'teacher_email' => $row['teacher_email'],
                'urgency_status' => $row['urgency_status'],
                'submission_id' => $row['submission_id'] ? (int)$row['submission_id'] : null,
                'submission_status' => $row['submission_status'],
                'submitted_at' => $row['submitted_at'],
                'marks_awarded' => $row['marks_awarded'] ? (int)$row['marks_awarded'] : null,
                'feedback' => $row['feedback'],
                'is_submitted' => !is_null($row['submission_id']),
                'is_evaluated' => !is_null($row['marks_awarded']),
                'can_submit' => is_null($row['submission_id']) && new DateTime($row['due_date']) > new DateTime()
            ];
            
            $assignments[] = $assignment;
        }
        
        echo json_encode([
            "status" => "success",
            "assignments" => $assignments
        ]);
        
    } else {
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

$mysqli->close();
?>