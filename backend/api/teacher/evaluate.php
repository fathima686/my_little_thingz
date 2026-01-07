<?php
/**
 * Teacher Evaluation API
 * Handles evaluation of student submissions
 * Methods: POST (create evaluation), PUT (update evaluation), GET (get evaluation)
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
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
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
    switch ($method) {
        case 'GET':
            handleGetEvaluation($mysqli, $teacherId);
            break;
        case 'POST':
            handleCreateEvaluation($mysqli, $teacherId);
            break;
        case 'PUT':
            handleUpdateEvaluation($mysqli, $teacherId);
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
 * Handle GET requests - get evaluation details
 */
function handleGetEvaluation($mysqli, $teacherId) {
    $submissionId = $_GET['submission_id'] ?? null;
    if (!$submissionId) {
        throw new Exception("Submission ID is required");
    }
    
    // Verify teacher owns the assignment for this submission
    $stmt = $mysqli->prepare("
        SELECT 
            s.id,
            a.teacher_id,
            a.title as assignment_title,
            a.max_marks,
            u.email as student_email,
            e.marks_awarded,
            e.feedback,
            e.evaluated_at
        FROM submissions s
        JOIN assignments a ON s.assignment_id = a.id
        JOIN users u ON s.student_id = u.id
        LEFT JOIN evaluations e ON s.id = e.submission_id
        WHERE s.id = ?
    ");
    
    $stmt->bind_param("i", $submissionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$row = $result->fetch_assoc()) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Submission not found"]);
        return;
    }
    
    if ($row['teacher_id'] != $teacherId) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "You can only evaluate submissions for your own assignments"]);
        return;
    }
    
    $evaluation = [
        'submission_id' => (int)$submissionId,
        'assignment_title' => $row['assignment_title'],
        'max_marks' => (int)$row['max_marks'],
        'student_email' => $row['student_email'],
        'marks_awarded' => $row['marks_awarded'] ? (int)$row['marks_awarded'] : null,
        'feedback' => $row['feedback'],
        'evaluated_at' => $row['evaluated_at'],
        'is_evaluated' => !is_null($row['marks_awarded'])
    ];
    
    echo json_encode([
        "status" => "success",
        "evaluation" => $evaluation
    ]);
}

/**
 * Handle POST requests - create new evaluation
 */
function handleCreateEvaluation($mysqli, $teacherId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception("Invalid JSON input");
    }
    
    $submissionId = $input['submission_id'] ?? null;
    $marksAwarded = $input['marks_awarded'] ?? null;
    $feedback = $input['feedback'] ?? '';
    
    if (!$submissionId) {
        throw new Exception("Submission ID is required");
    }
    
    if ($marksAwarded === null || $marksAwarded === '') {
        throw new Exception("Marks awarded is required");
    }
    
    $marksAwarded = (int)$marksAwarded;
    
    // Get submission and assignment details
    $stmt = $mysqli->prepare("
        SELECT 
            s.id,
            s.assignment_id,
            a.teacher_id,
            a.max_marks,
            a.title as assignment_title,
            u.email as student_email,
            u.id as student_id
        FROM submissions s
        JOIN assignments a ON s.assignment_id = a.id
        JOIN users u ON s.student_id = u.id
        WHERE s.id = ?
    ");
    
    $stmt->bind_param("i", $submissionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$row = $result->fetch_assoc()) {
        throw new Exception("Submission not found");
    }
    
    if ($row['teacher_id'] != $teacherId) {
        throw new Exception("You can only evaluate submissions for your own assignments");
    }
    
    // Validate marks are within range
    if ($marksAwarded < 0 || $marksAwarded > $row['max_marks']) {
        throw new Exception("Marks must be between 0 and " . $row['max_marks']);
    }
    
    // Check if evaluation already exists
    $stmt = $mysqli->prepare("SELECT id FROM evaluations WHERE submission_id = ?");
    $stmt->bind_param("i", $submissionId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception("This submission has already been evaluated. Use PUT to update.");
    }
    
    // Begin transaction
    $mysqli->begin_transaction();
    
    try {
        // Insert evaluation
        $stmt = $mysqli->prepare("
            INSERT INTO evaluations (submission_id, teacher_id, marks_awarded, feedback)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->bind_param("iiis", $submissionId, $teacherId, $marksAwarded, $feedback);
        $stmt->execute();
        $evaluationId = $mysqli->insert_id;
        
        // Update submission status
        $stmt = $mysqli->prepare("UPDATE submissions SET status = 'evaluated' WHERE id = ?");
        $stmt->bind_param("i", $submissionId);
        $stmt->execute();
        
        // Log the evaluation
        logActivity($mysqli, $teacherId, 'CREATE', 'evaluations', $evaluationId, null, [
            'submission_id' => $submissionId,
            'marks_awarded' => $marksAwarded,
            'feedback' => $feedback
        ]);
        
        $mysqli->commit();
        
        echo json_encode([
            "status" => "success",
            "message" => "Evaluation submitted successfully",
            "evaluation" => [
                'id' => $evaluationId,
                'submission_id' => $submissionId,
                'marks_awarded' => $marksAwarded,
                'feedback' => $feedback,
                'assignment_title' => $row['assignment_title'],
                'student_email' => $row['student_email'],
                'max_marks' => (int)$row['max_marks']
            ]
        ]);
        
    } catch (Exception $e) {
        $mysqli->rollback();
        throw $e;
    }
}

/**
 * Handle PUT requests - update existing evaluation
 */
function handleUpdateEvaluation($mysqli, $teacherId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception("Invalid JSON input");
    }
    
    $submissionId = $input['submission_id'] ?? null;
    $marksAwarded = $input['marks_awarded'] ?? null;
    $feedback = $input['feedback'] ?? '';
    
    if (!$submissionId) {
        throw new Exception("Submission ID is required");
    }
    
    if ($marksAwarded === null || $marksAwarded === '') {
        throw new Exception("Marks awarded is required");
    }
    
    $marksAwarded = (int)$marksAwarded;
    
    // Get existing evaluation and verify ownership
    $stmt = $mysqli->prepare("
        SELECT 
            e.id as evaluation_id,
            e.marks_awarded as old_marks,
            e.feedback as old_feedback,
            s.assignment_id,
            a.teacher_id,
            a.max_marks,
            a.title as assignment_title,
            u.email as student_email
        FROM evaluations e
        JOIN submissions s ON e.submission_id = s.id
        JOIN assignments a ON s.assignment_id = a.id
        JOIN users u ON s.student_id = u.id
        WHERE e.submission_id = ?
    ");
    
    $stmt->bind_param("i", $submissionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$row = $result->fetch_assoc()) {
        throw new Exception("Evaluation not found");
    }
    
    if ($row['teacher_id'] != $teacherId) {
        throw new Exception("You can only update evaluations for your own assignments");
    }
    
    // Validate marks are within range
    if ($marksAwarded < 0 || $marksAwarded > $row['max_marks']) {
        throw new Exception("Marks must be between 0 and " . $row['max_marks']);
    }
    
    // Update evaluation
    $stmt = $mysqli->prepare("
        UPDATE evaluations 
        SET marks_awarded = ?, feedback = ?, evaluated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->bind_param("isi", $marksAwarded, $feedback, $row['evaluation_id']);
    $stmt->execute();
    
    // Log the update
    logActivity($mysqli, $teacherId, 'UPDATE', 'evaluations', $row['evaluation_id'], [
        'marks_awarded' => (int)$row['old_marks'],
        'feedback' => $row['old_feedback']
    ], [
        'marks_awarded' => $marksAwarded,
        'feedback' => $feedback
    ]);
    
    echo json_encode([
        "status" => "success",
        "message" => "Evaluation updated successfully",
        "evaluation" => [
            'id' => (int)$row['evaluation_id'],
            'submission_id' => $submissionId,
            'marks_awarded' => $marksAwarded,
            'feedback' => $feedback,
            'assignment_title' => $row['assignment_title'],
            'student_email' => $row['student_email'],
            'max_marks' => (int)$row['max_marks']
        ]
    ]);
}

/**
 * Log activity for audit trail
 */
function logActivity($mysqli, $userId, $action, $tableName, $recordId, $oldValues, $newValues) {
    $stmt = $mysqli->prepare("
        INSERT INTO assignment_audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $oldValuesJson = $oldValues ? json_encode($oldValues) : null;
    $newValuesJson = $newValues ? json_encode($newValues) : null;
    
    $stmt->bind_param(
        "ississss",
        $userId,
        $action,
        $tableName,
        $recordId,
        $oldValuesJson,
        $newValuesJson,
        $ipAddress,
        $userAgent
    );
    
    $stmt->execute();
}

$mysqli->close();
?>