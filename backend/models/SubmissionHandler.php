
<?php
/**
 * Submission Handler Class
 * Manages student submissions and file handling
 * Integrates with existing tutorial file upload system
 */

class SubmissionHandler {
    private $mysqli;
    private $uploadDir;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
        $this->uploadDir = '../../uploads/assignments/';
        
        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Submit an assignment (text or file)
     */
    public function submitAssignment($studentId, $assignmentId, $submissionData) {
        // Validate assignment exists and is active
        $assignment = $this->getAssignmentDetails($assignmentId);
        if (!$assignment) {
            throw new Exception("Assignment not found");
        }
        
        if ($assignment['status'] !== 'active') {
            throw new Exception("Assignment is not active");
        }
        
        // Check if assignment is overdue
        $dueDate = new DateTime($assignment['due_date']);
        $now = new DateTime();
        $isLate = $now > $dueDate;
        
        // Check if student already submitted
        if ($this->hasStudentSubmitted($studentId, $assignmentId)) {
            throw new Exception("You have already submitted this assignment");
        }
        
        // Validate submission data
        $submissionType = $submissionData['type'] ?? null;
        if (!in_array($submissionType, ['text', 'file'])) {
            throw new Exception("Invalid submission type. Must be 'text' or 'file'");
        }
        
        $content = null;
        $filePath = null;
        $fileName = null;
        $fileSize = null;
        
        if ($submissionType === 'text') {
            $content = $submissionData['content'] ?? '';
            if (empty(trim($content))) {
                throw new Exception("Text content cannot be empty");
            }
        } else {
            // Handle file upload
            $file = $submissionData['file'] ?? null;
            if (!$file || !isset($file['tmp_name'])) {
                throw new Exception("File is required for file submissions");
            }
            
            $uploadResult = $this->handleFileUpload($file);
            $filePath = $uploadResult['path'];
            $fileName = $uploadResult['name'];
            $fileSize = $uploadResult['size'];
        }
        
        // Begin transaction
        $this->mysqli->begin_transaction();
        
        try {
            // Insert submission
            $stmt = $this->mysqli->prepare("
                INSERT INTO submissions (assignment_id, student_id, submission_type, content, file_path, file_name, file_size, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $status = $isLate ? 'late' : 'submitted';
            
            $stmt->bind_param(
                "iissssii",
                $assignmentId,
                $studentId,
                $submissionType,
                $content,
                $filePath,
                $fileName,
                $fileSize,
                $status
            );
            
            $stmt->execute();
            $submissionId = $this->mysqli->insert_id;
            
            // Log the submission
            $this->logActivity($studentId, 'CREATE', 'submissions', $submissionId, null, [
                'assignment_id' => $assignmentId,
                'submission_type' => $submissionType,
                'status' => $status
            ]);
            
            $this->mysqli->commit();
            
            return $this->getSubmission($submissionId);
            
        } catch (Exception $e) {
            $this->mysqli->rollback();
            
            // Clean up uploaded file if transaction failed
            if ($filePath && file_exists($this->uploadDir . $filePath)) {
                unlink($this->uploadDir . $filePath);
            }
            
            throw $e;
        }
    }
    
    /**
     * Get submission by ID
     */
    public function getSubmission($submissionId) {
        $stmt = $this->mysqli->prepare("
            SELECT 
                s.*,
                a.title as assignment_title,
                a.max_marks,
                a.due_date,
                sub.name as subject_name,
                t.name as topic_name,
                e.marks_awarded,
                e.feedback,
                e.evaluated_at
            FROM submissions s
            JOIN assignments a ON s.assignment_id = a.id
            JOIN subjects sub ON a.subject_id = sub.id
            JOIN topics t ON a.topic_id = t.id
            LEFT JOIN evaluations e ON s.id = e.submission_id
            WHERE s.id = ?
        ");
        
        $stmt->bind_param("i", $submissionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $this->formatSubmissionData($row);
        }
        
        return null;
    }
    
    /**
     * Get submissions by assignment
     */
    public function getSubmissionsByAssignment($assignmentId) {
        $stmt = $this->mysqli->prepare("
            SELECT 
                s.*,
                u.email as student_email,
                e.marks_awarded,
                e.feedback,
                e.evaluated_at
            FROM submissions s
            JOIN users u ON s.student_id = u.id
            LEFT JOIN evaluations e ON s.id = e.submission_id
            WHERE s.assignment_id = ?
            ORDER BY s.submitted_at DESC
        ");
        
        $stmt->bind_param("i", $assignmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $submissions = [];
        while ($row = $result->fetch_assoc()) {
            $submissions[] = $this->formatSubmissionData($row);
        }
        
        return $submissions;
    }
    
    /**
     * Update submission status
     */
    public function updateSubmissionStatus($submissionId, $status) {
        $validStatuses = ['submitted', 'evaluated', 'late'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception("Invalid status");
        }
        
        $stmt = $this->mysqli->prepare("UPDATE submissions SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $submissionId);
        
        return $stmt->execute();
    }
    
    /**
     * Validate file upload
     */
    public function validateFileUpload($file) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error');
        }
        
        // Check file size (max 10MB)
        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            throw new Exception('File size must be less than 10MB');
        }
        
        // Check file type
        $allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/zip',
            'application/x-zip-compressed'
        ];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Invalid file type. Allowed: PDF, DOC, DOCX, TXT, JPG, PNG, GIF, ZIP');
        }
        
        // Basic security scan - check for executable files
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $dangerousExtensions = ['exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar', 'php', 'asp', 'jsp'];
        
        if (in_array($extension, $dangerousExtensions)) {
            throw new Exception('File type not allowed for security reasons');
        }
        
        return true;
    }
    
    /**
     * Handle file upload
     */
    private function handleFileUpload($file) {
        $this->validateFileUpload($file);
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'submission_' . time() . '_' . uniqid() . '.' . $extension;
        $filePath = $fileName;
        $fullPath = $this->uploadDir . $fileName;
        
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw new Exception('Failed to upload file');
        }
        
        return [
            'path' => $filePath,
            'name' => $file['name'],
            'size' => $file['size']
        ];
    }
    
    /**
     * Get assignment details
     */
    private function getAssignmentDetails($assignmentId) {
        $stmt = $this->mysqli->prepare("SELECT * FROM assignments WHERE id = ?");
        $stmt->bind_param("i", $assignmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Check if student has already submitted
     */
    private function hasStudentSubmitted($studentId, $assignmentId) {
        $stmt = $this->mysqli->prepare("SELECT id FROM submissions WHERE student_id = ? AND assignment_id = ?");
        $stmt->bind_param("ii", $studentId, $assignmentId);
        $stmt->execute();
        
        return $stmt->get_result()->num_rows > 0;
    }
    
    /**
     * Format submission data for API response
     */
    private function formatSubmissionData($row) {
        $submission = [
            'id' => (int)$row['id'],
            'assignment_id' => (int)$row['assignment_id'],
            'student_id' => (int)$row['student_id'],
            'submission_type' => $row['submission_type'],
            'content' => $row['content'],
            'file_path' => $row['file_path'],
            'file_name' => $row['file_name'],
            'file_size' => $row['file_size'] ? (int)$row['file_size'] : null,
            'submitted_at' => $row['submitted_at'],
            'status' => $row['status'],
            'assignment_title' => $row['assignment_title'] ?? null,
            'max_marks' => isset($row['max_marks']) ? (int)$row['max_marks'] : null,
            'due_date' => $row['due_date'] ?? null,
            'subject_name' => $row['subject_name'] ?? null,
            'topic_name' => $row['topic_name'] ?? null,
            'student_email' => $row['student_email'] ?? null,
            'marks_awarded' => $row['marks_awarded'] ? (int)$row['marks_awarded'] : null,
            'feedback' => $row['feedback'],
            'evaluated_at' => $row['evaluated_at'],
            'is_evaluated' => !is_null($row['marks_awarded'])
        ];
        
        // Add file download URL if file exists
        if ($submission['file_path']) {
            $submission['file_download_url'] = $this->getFileDownloadUrl($submission['file_path']);
        }
        
        return $submission;
    }
    
    /**
     * Get file download URL
     */
    private function getFileDownloadUrl($filePath) {
        $baseUrl = 'http://localhost/my_little_thingz/backend';
        return $baseUrl . '/uploads/assignments/' . $filePath;
    }
    
    /**
     * Log activity for audit trail
     */
    private function logActivity($userId, $action, $tableName, $recordId, $oldValues, $newValues) {
        $stmt = $this->mysqli->prepare("
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
}
?>