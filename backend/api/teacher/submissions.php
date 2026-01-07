<?php
/**
 * Teacher Submissions API
 * Handles viewing and managing student submissions
 * Methods: GET (list submissions for assignment)
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
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-User-Id, X-Tutorial-Email");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Database connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli("localhost", "root", "", "my_little_thingz");
$mysqli->set_charset('utf8mb4');
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "DB connect failed: " . $mysqli->connect_error]);
    exit;
}

// Authentication
$teacherId = null;
$adminUserId = $_SERVER['HTTP_X_ADMIN_USER_ID'] ?? null;
if ($adminUserId) {
    $teacherId = $adminUserId;
}

$tutorialEmail = $_SERVER['HTTP_X_TUTORIAL_EMAIL'] ?? null;
if ($tutorialEmail && !$teacherId) {
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $tutorialEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $teacherId = $row['id'];
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

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $assignmentId = $_GET['assignment_id'] ?? null;
        if (!$assignmentId) {
            throw new Exception("Assignment ID is required");
        }
        
        // Verify teacher owns this assignment
        $stmt = $mysqli->prepare("SELECT id FROM assignments WHERE id = ? AND teacher_id = ?");
        $stmt->bind_param("ii", $assignmentId, $teacherId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "You can only view submissions for your own assignments"]);
            exit;
        }
        
        // Get all submissions for this assignment
        $stmt = $mysqli->prepare("
            SELECT 
                s.*,
                u.email as student_email,
                u.id as student_id,
                e.marks_awarded,
                e.feedback,
                e.evaluated_at,
                a.title as assignment_title,
                a.max_marks
            FROM submissions s
            JOIN users u ON s.student_id = u.id
            JOIN assignments a ON s.assignment_id = a.id
            LEFT JOIN evaluations e ON s.id = e.submission_id
            WHERE s.assignment_id = ?
            ORDER BY s.submitted_at DESC
        ");
        
        $stmt->bind_param("i", $assignmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $submissions = [];
        while ($row = $result->fetch_assoc()) {
            $submission = [
                'id' => (int)$row['id'],
                'assignment_id' => (int)$row['assignment_id'],
                'student_id' => (int)$row['student_id'],
                'student_email' => $row['student_email'],
                'submission_type' => $row['submission_type'],
                'content' => $row['content'],
                'file_path' => $row['file_path'],
                'file_name' => $row['file_name'],
                'file_size' => $row['file_size'] ? (int)$row['file_size'] : null,
                'submitted_at' => $row['submitted_at'],
                'status' => $row['status'],
                'assignment_title' => $row['assignment_title'],
                'max_marks' => (int)$row['max_marks'],
                'marks_awarded' => $row['marks_awarded'] ? (int)$row['marks_awarded'] : null,
                'feedback' => $row['feedback'],
                'evaluated_at' => $row['evaluated_at'],
                'is_evaluated' => !is_null($row['marks_awarded'])
            ];
            
            // Add file download URL if file exists
            if ($submission['file_path']) {
                $submission['file_download_url'] = getFileDownloadUrl($submission['file_path']);
            }
            
            $submissions[] = $submission;
        }
        
        // Get assignment details
        $stmt = $mysqli->prepare("
            SELECT 
                a.title,
                a.description,
                a.due_date,
                a.max_marks,
                s.name as subject_name,
                t.name as topic_name
            FROM assignments a
            JOIN subjects s ON a.subject_id = s.id
            JOIN topics t ON a.topic_id = t.id
            WHERE a.id = ?
        ");
        
        $stmt->bind_param("i", $assignmentId);
        $stmt->execute();
        $assignmentResult = $stmt->get_result();
        $assignmentDetails = $assignmentResult->fetch_assoc();
        
        echo json_encode([
            "status" => "success",
            "assignment" => $assignmentDetails,
            "submissions" => $submissions,
            "summary" => [
                'total_submissions' => count($submissions),
                'evaluated_submissions' => count(array_filter($submissions, function($s) { return $s['is_evaluated']; })),
                'pending_evaluations' => count(array_filter($submissions, function($s) { return !$s['is_evaluated']; }))
            ]
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

/**
 * Generate file download URL
 */
function getFileDownloadUrl($filePath) {
    // Reuse existing tutorial file serving logic
    $baseUrl = 'http://localhost/my_little_thingz/backend';
    
    // Clean the file path
    $cleanPath = ltrim($filePath, '/');
    
    // If path doesn't start with uploads/, add it
    if (!str_starts_with($cleanPath, 'uploads/')) {
        $cleanPath = 'uploads/assignments/' . $cleanPath;
    }
    
    return $baseUrl . '/' . $cleanPath;
}

$mysqli->close();
?>