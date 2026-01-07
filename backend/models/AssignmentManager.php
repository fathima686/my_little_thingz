<?php
/**
 * Assignment Manager Class
 * Handles assignment CRUD operations and business rules
 * Integrates with existing tutorial system
 */

class AssignmentManager {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    /**
     * Create a new assignment
     * Validates subject-topic relationship and teacher permissions
     */
    public function createAssignment($teacherId, $subjectId, $topicId, $assignmentData) {
        // Validate required fields
        $requiredFields = ['title', 'description', 'due_date', 'max_marks'];
        foreach ($requiredFields as $field) {
            if (empty($assignmentData[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // Validate teacher exists and has teacher role
        if (!$this->validateTeacherRole($teacherId)) {
            throw new Exception("User does not have teacher permissions");
        }
        
        // Validate subject exists
        if (!$this->validateSubject($subjectId)) {
            throw new Exception("Invalid subject ID");
        }
        
        // Validate topic belongs to subject
        if (!$this->validateTopicBelongsToSubject($topicId, $subjectId)) {
            throw new Exception("Topic does not belong to the specified subject");
        }
        
        // Validate due date is in the future
        $dueDate = new DateTime($assignmentData['due_date']);
        $now = new DateTime();
        if ($dueDate <= $now) {
            throw new Exception("Due date must be in the future");
        }
        
        // Validate max marks is positive
        if ($assignmentData['max_marks'] <= 0) {
            throw new Exception("Maximum marks must be greater than 0");
        }
        
        // Begin transaction
        $this->mysqli->begin_transaction();
        
        try {
            // Insert assignment
            $stmt = $this->mysqli->prepare("
                INSERT INTO assignments (teacher_id, subject_id, topic_id, title, description, due_date, max_marks, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            
            $stmt->bind_param(
                "iiisssi",
                $teacherId,
                $subjectId,
                $topicId,
                $assignmentData['title'],
                $assignmentData['description'],
                $assignmentData['due_date'],
                $assignmentData['max_marks']
            );
            
            $stmt->execute();
            $assignmentId = $this->mysqli->insert_id;
            
            // Log the creation
            $this->logActivity($teacherId, 'CREATE', 'assignments', $assignmentId, null, $assignmentData);
            
            $this->mysqli->commit();
            
            // Return the created assignment
            return $this->getAssignmentById($assignmentId);
            
        } catch (Exception $e) {
            $this->mysqli->rollback();
            throw $e;
        }
    }
    
    /**
     * Get assignments by topic with optional student filtering
     */
    public function getAssignmentsByTopic($topicId, $studentId = null) {
        $sql = "
            SELECT 
                a.*,
                s.name as subject_name,
                t.name as topic_name,
                u.email as teacher_email,
                CASE 
                    WHEN a.due_date < NOW() THEN 'overdue'
                    WHEN a.due_date < DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 'due_soon'
                    ELSE 'active'
                END as urgency_status
        ";
        
        if ($studentId) {
            $sql .= ",
                sub.id as submission_id,
                sub.status as submission_status,
                sub.submitted_at,
                e.marks_awarded,
                e.feedback
            ";
        }
        
        $sql .= "
            FROM assignments a
            JOIN subjects s ON a.subject_id = s.id
            JOIN topics t ON a.topic_id = t.id
            JOIN users u ON a.teacher_id = u.id
        ";
        
        if ($studentId) {
            $sql .= "
                LEFT JOIN submissions sub ON a.id = sub.assignment_id AND sub.student_id = ?
                LEFT JOIN evaluations e ON sub.id = e.submission_id
            ";
        }
        
        $sql .= "
            WHERE a.topic_id = ? AND a.status = 'active'
            ORDER BY a.due_date ASC, a.created_at DESC
        ";
        
        if ($studentId) {
            $stmt = $this->mysqli->prepare($sql);
            $stmt->bind_param("ii", $studentId, $topicId);
        } else {
            $stmt = $this->mysqli->prepare($sql);
            $stmt->bind_param("i", $topicId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $assignments = [];
        while ($row = $result->fetch_assoc()) {
            $assignments[] = $this->formatAssignmentData($row);
        }
        
        return $assignments;
    }
    
    /**
     * Update an assignment
     */
    public function updateAssignment($assignmentId, $updateData, $teacherId) {
        // Verify assignment exists and teacher owns it
        $assignment = $this->getAssignmentById($assignmentId);
        if (!$assignment) {
            throw new Exception("Assignment not found");
        }
        
        if ($assignment['teacher_id'] != $teacherId) {
            throw new Exception("You can only update your own assignments");
        }
        
        // Check if assignment has submissions - restrict some updates
        $hasSubmissions = $this->assignmentHasSubmissions($assignmentId);
        
        $allowedFields = ['title', 'description', 'due_date'];
        if (!$hasSubmissions) {
            $allowedFields[] = 'max_marks';
        }
        
        $updateFields = [];
        $values = [];
        $types = '';
        
        foreach ($updateData as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updateFields[] = "$field = ?";
                $values[] = $value;
                $types .= 's';
                
                // Special validation for due_date
                if ($field === 'due_date') {
                    $dueDate = new DateTime($value);
                    $now = new DateTime();
                    if ($dueDate <= $now) {
                        throw new Exception("Due date must be in the future");
                    }
                }
            }
        }
        
        if (empty($updateFields)) {
            throw new Exception("No valid fields to update");
        }
        
        // Add updated_at
        $updateFields[] = "updated_at = NOW()";
        
        $sql = "UPDATE assignments SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $values[] = $assignmentId;
        $types .= 'i';
        
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            // Log the update
            $this->logActivity($teacherId, 'UPDATE', 'assignments', $assignmentId, $assignment, $updateData);
            return $this->getAssignmentById($assignmentId);
        } else {
            throw new Exception("Failed to update assignment");
        }
    }
    
    /**
     * Delete an assignment (soft delete by setting status to archived)
     */
    public function deleteAssignment($assignmentId, $teacherId) {
        $assignment = $this->getAssignmentById($assignmentId);
        if (!$assignment) {
            throw new Exception("Assignment not found");
        }
        
        if ($assignment['teacher_id'] != $teacherId) {
            throw new Exception("You can only delete your own assignments");
        }
        
        // Check if assignment has submissions
        if ($this->assignmentHasSubmissions($assignmentId)) {
            // Soft delete - set status to archived
            $stmt = $this->mysqli->prepare("UPDATE assignments SET status = 'archived', updated_at = NOW() WHERE id = ?");
        } else {
            // Hard delete if no submissions
            $stmt = $this->mysqli->prepare("DELETE FROM assignments WHERE id = ?");
        }
        
        $stmt->bind_param("i", $assignmentId);
        
        if ($stmt->execute()) {
            $this->logActivity($teacherId, 'DELETE', 'assignments', $assignmentId, $assignment, null);
            return true;
        } else {
            throw new Exception("Failed to delete assignment");
        }
    }
    
    /**
     * Get assignment statistics
     */
    public function getAssignmentStatistics($assignmentId) {
        $stmt = $this->mysqli->prepare("
            SELECT 
                a.id,
                a.title,
                a.max_marks,
                a.due_date,
                COUNT(s.id) as total_submissions,
                COUNT(e.id) as evaluated_submissions,
                ROUND(AVG(e.marks_awarded), 2) as average_marks,
                MIN(e.marks_awarded) as min_marks,
                MAX(e.marks_awarded) as max_marks,
                ROUND((COUNT(e.id) / NULLIF(COUNT(s.id), 0)) * 100, 2) as evaluation_percentage,
                COUNT(CASE WHEN s.status = 'late' THEN 1 END) as late_submissions
            FROM assignments a
            LEFT JOIN submissions s ON a.id = s.assignment_id
            LEFT JOIN evaluations e ON s.id = e.submission_id
            WHERE a.id = ?
            GROUP BY a.id
        ");
        
        $stmt->bind_param("i", $assignmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get assignment by ID
     */
    public function getAssignmentById($assignmentId) {
        $stmt = $this->mysqli->prepare("
            SELECT 
                a.*,
                s.name as subject_name,
                t.name as topic_name,
                u.email as teacher_email
            FROM assignments a
            JOIN subjects s ON a.subject_id = s.id
            JOIN topics t ON a.topic_id = t.id
            JOIN users u ON a.teacher_id = u.id
            WHERE a.id = ?
        ");
        
        $stmt->bind_param("i", $assignmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $this->formatAssignmentData($row);
        }
        
        return null;
    }
    
    /**
     * Validate teacher role
     */
    private function validateTeacherRole($userId) {
        $stmt = $this->mysqli->prepare("SELECT roles FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $roles = json_decode($row['roles'], true) ?: [];
            return in_array('teacher', $roles) || in_array('admin', $roles);
        }
        
        return false;
    }
    
    /**
     * Validate subject exists
     */
    private function validateSubject($subjectId) {
        $stmt = $this->mysqli->prepare("SELECT id FROM subjects WHERE id = ?");
        $stmt->bind_param("i", $subjectId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
    
    /**
     * Validate topic belongs to subject
     */
    private function validateTopicBelongsToSubject($topicId, $subjectId) {
        $stmt = $this->mysqli->prepare("SELECT id FROM topics WHERE id = ? AND subject_id = ?");
        $stmt->bind_param("ii", $topicId, $subjectId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
    
    /**
     * Check if assignment has submissions
     */
    private function assignmentHasSubmissions($assignmentId) {
        $stmt = $this->mysqli->prepare("SELECT COUNT(*) as count FROM submissions WHERE assignment_id = ?");
        $stmt->bind_param("i", $assignmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }
    
    /**
     * Format assignment data for API response
     */
    private function formatAssignmentData($row) {
        return [
            'id' => (int)$row['id'],
            'teacher_id' => (int)$row['teacher_id'],
            'subject_id' => (int)$row['subject_id'],
            'topic_id' => (int)$row['topic_id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'due_date' => $row['due_date'],
            'max_marks' => (int)$row['max_marks'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'subject_name' => $row['subject_name'] ?? null,
            'topic_name' => $row['topic_name'] ?? null,
            'teacher_email' => $row['teacher_email'] ?? null,
            'urgency_status' => $row['urgency_status'] ?? null,
            'submission_id' => isset($row['submission_id']) ? (int)$row['submission_id'] : null,
            'submission_status' => $row['submission_status'] ?? null,
            'submitted_at' => $row['submitted_at'] ?? null,
            'marks_awarded' => isset($row['marks_awarded']) ? (int)$row['marks_awarded'] : null,
            'feedback' => $row['feedback'] ?? null
        ];
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