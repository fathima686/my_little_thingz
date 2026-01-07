<?php
/**
 * Student Submission API
 * Handles assignment submissions by students
 * Methods: POST (submit assignment)
 * Integrates with existing tutorial file upload system
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
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Tutorial-Email");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Include required files
require_once '../../models/SubmissionHandler.php';

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

try {
    if ($method === 'POST') {
        // Initialize submission handler
        $submissionHandler = new SubmissionHandler($mysqli);
        
        // Get assignment ID
        $assignmentId = $_POST['assignment_id'] ?? null;
        if (!$assignmentId) {
            throw new Exception("Assignment ID is required");
        }
        
        // Get submission type
        $submissionType = $_POST['submission_type'] ?? null;
        if (!$submissionType || !in_array($submissionType, ['text', 'file'])) {
            throw new Exception("Valid submission type is required (text or file)");
        }
        
        $submissionData = [
            'type' => $submissionType
        ];
        
        if ($submissionType === 'text') {
            $content = $_POST['content'] ?? '';
            if (empty(trim($content))) {
                throw new Exception("Text content cannot be empty");
            }
            $submissionData['content'] = $content;
        } else {
            // Handle file upload
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("File upload is required for file submissions");
            }
            $submissionData['file'] = $_FILES['file'];
        }
        
        // Submit the assignment
        $submission = $submissionHandler->submitAssignment($studentId, $assignmentId, $submissionData);
        
        echo json_encode([
            "status" => "success",
            "message" => "Assignment submitted successfully",
            "submission" => $submission
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