# Assignment Management System Design

## Overview

The Assignment Management System provides a comprehensive platform for educational assignment workflows. The system enables teachers to create structured assignments organized by subjects and topics, while students can view, submit, and track their assignment progress. The architecture supports both text and file-based submissions with comprehensive evaluation capabilities including marking and feedback mechanisms.

The system integrates with the existing user authentication framework and leverages the current database infrastructure while introducing new tables for assignment-specific data management.

## Architecture

The Assignment Management System follows a three-tier architecture:

### Presentation Layer
- **Teacher Interface**: Assignment creation, submission review, evaluation dashboard
- **Student Interface**: Assignment browsing, submission portal, progress tracking
- **Admin Interface**: System monitoring, user management, security oversight

### Business Logic Layer
- **Assignment Service**: Handles assignment CRUD operations and business rules
- **Submission Service**: Manages student submissions and file handling
- **Evaluation Service**: Processes teacher evaluations and feedback
- **Notification Service**: Handles status updates and deadline alerts
- **Security Service**: Manages access control and data protection

### Data Layer
- **Assignment Repository**: Assignment metadata and relationships
- **Submission Repository**: Student submissions and file storage
- **Evaluation Repository**: Marks, feedback, and evaluation history
- **User Repository**: Integration with existing user management

## Components and Interfaces

### Core Components

#### Assignment Manager
```php
class AssignmentManager {
    public function createAssignment($teacherId, $subjectId, $topicId, $assignmentData);
    public function getAssignmentsByTopic($topicId, $studentId = null);
    public function updateAssignment($assignmentId, $updateData);
    public function deleteAssignment($assignmentId, $teacherId);
    public function getAssignmentStatistics($assignmentId);
}
```

#### Submission Handler
```php
class SubmissionHandler {
    public function submitAssignment($studentId, $assignmentId, $submissionData);
    public function getSubmission($submissionId);
    public function getSubmissionsByAssignment($assignmentId);
    public function updateSubmissionStatus($submissionId, $status);
    public function validateFileUpload($file);
}
```

#### Evaluation Processor
```php
class EvaluationProcessor {
    public function evaluateSubmission($submissionId, $teacherId, $marks, $feedback);
    public function getEvaluationHistory($submissionId);
    public function generateProgressReport($studentId, $subjectId = null);
    public function calculateStatistics($assignmentId);
}
```

### API Endpoints

#### Teacher Endpoints
- `POST /api/teacher/assignments` - Create new assignment
- `GET /api/teacher/assignments` - List teacher's assignments
- `PUT /api/teacher/assignments/{id}` - Update assignment
- `DELETE /api/teacher/assignments/{id}` - Delete assignment
- `GET /api/teacher/submissions/{assignmentId}` - Get submissions for assignment
- `POST /api/teacher/evaluate` - Submit evaluation for submission

#### Student Endpoints
- `GET /api/student/assignments` - List available assignments by topic
- `GET /api/student/assignments/{id}` - Get assignment details
- `POST /api/student/submit` - Submit assignment
- `GET /api/student/submissions` - Get student's submission history
- `GET /api/student/progress` - Get progress tracking data

## Data Models

### Assignment Entity
```sql
CREATE TABLE assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    topic_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATETIME NOT NULL,
    max_marks INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    FOREIGN KEY (teacher_id) REFERENCES users(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (topic_id) REFERENCES topics(id)
);
```

### Submission Entity
```sql
CREATE TABLE submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_type ENUM('text', 'file') NOT NULL,
    content TEXT,
    file_path VARCHAR(500),
    file_name VARCHAR(255),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('submitted', 'evaluated', 'late') DEFAULT 'submitted',
    FOREIGN KEY (assignment_id) REFERENCES assignments(id),
    FOREIGN KEY (student_id) REFERENCES users(id),
    UNIQUE KEY unique_submission (assignment_id, student_id)
);
```

### Evaluation Entity
```sql
CREATE TABLE evaluations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    submission_id INT NOT NULL,
    teacher_id INT NOT NULL,
    marks_awarded INT NOT NULL,
    feedback TEXT,
    evaluated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES submissions(id),
    FOREIGN KEY (teacher_id) REFERENCES users(id)
);
```

### Subject and Topic Entities
```sql
CREATE TABLE subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE topics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
);
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

After analyzing the acceptance criteria, I've identified the following testable properties while eliminating redundancy through property reflection:

### Core Assignment Management Properties

**Property 1: Assignment creation validation**
*For any* assignment creation attempt, the system should require both subject selection and topic association, and reject attempts missing either requirement
**Validates: Requirements 1.1**

**Property 2: Assignment data completeness**
*For any* successfully created assignment, the system should capture and store title, description, due date, maximum marks, unique ID, and creation timestamp
**Validates: Requirements 1.2, 1.3**

**Property 3: Assignment data validation**
*For any* assignment data input, the system should validate all required fields are present and properly formatted before storage
**Validates: Requirements 1.4**

**Property 4: Assignment creation confirmation**
*For any* successful assignment creation, the system should return the complete assignment details as confirmation
**Validates: Requirements 1.5**

### Student Access and Submission Properties

**Property 5: Assignment organization consistency**
*For any* student assignment access, the system should display assignments grouped by subject and topic with all required information (title, description, due date, maximum marks, submission status)
**Validates: Requirements 2.1, 2.2, 2.4**

**Property 6: Topic filtering accuracy**
*For any* topic selection by a student, the system should return only assignments associated with that specific topic
**Validates: Requirements 2.3**

**Property 7: Student access control**
*For any* assignment data retrieval, the system should ensure students only see assignments they are authorized to access
**Validates: Requirements 2.5**

**Property 8: Submission format acceptance**
*For any* assignment submission, the system should accept both text content and valid file uploads as submission formats
**Validates: Requirements 3.1**

**Property 9: File upload validation**
*For any* file upload attempt, the system should validate file type and size limits before acceptance, rejecting invalid files
**Validates: Requirements 3.2, 7.3**

**Property 10: Submission data integrity**
*For any* submission, the system should record submission timestamp, associate it correctly with student and assignment, and ensure data matches what was submitted
**Validates: Requirements 3.3, 3.4**

**Property 11: Submission status update**
*For any* successful submission, the system should update assignment status to submitted and provide confirmation
**Validates: Requirements 3.5**

### Progress Tracking and Evaluation Properties

**Property 12: Status information completeness**
*For any* assignment status request, the system should display current submission state, submission date, evaluation status, marks received, and feedback when available
**Validates: Requirements 4.1, 4.2, 4.3**

**Property 13: Score calculation accuracy**
*For any* marks assignment, the system should display correct numerical score and percentage calculation against maximum marks
**Validates: Requirements 4.4**

**Property 14: Real-time data consistency**
*For any* status information request, the system should retrieve and display current submission data reflecting all recent updates
**Validates: Requirements 4.5**

**Property 15: Teacher submission access**
*For any* assignment selected by a teacher, the system should display all student submissions for that assignment
**Validates: Requirements 5.1**

**Property 16: Evaluation constraints**
*For any* evaluation attempt, the system should only allow marks within the maximum limit and accept feedback text
**Validates: Requirements 5.2, 5.3**

**Property 17: Evaluation completion workflow**
*For any* completed evaluation, the system should update submission status to evaluated and maintain evaluation history
**Validates: Requirements 5.4, 5.5**

### Assignment Management and Security Properties

**Property 18: Deadline management rules**
*For any* assignment, teachers should be able to modify due dates before deadline, but the system should prevent new submissions after due date and mark assignments as overdue
**Validates: Requirements 6.1, 6.3**

**Property 19: Statistics calculation accuracy**
*For any* assignment statistics request, the system should calculate and display accurate submission counts, completion rates, and average scores
**Validates: Requirements 6.2**

**Property 20: Report data completeness**
*For any* report generation request, the system should provide exportable data containing student performance and assignment completion information
**Validates: Requirements 6.4**

**Property 21: Teacher ownership access control**
*For any* assignment data access by a teacher, the system should ensure teachers can only view and manage assignments they created
**Validates: Requirements 6.5**

**Property 22: Authentication and authorization**
*For any* assignment feature access, the system should verify user authentication and enforce role-based permissions
**Validates: Requirements 7.1**

**Property 23: Audit logging completeness**
*For any* system activity, the system should record access attempts and data modifications for audit purposes
**Validates: Requirements 7.5**

**Property 24: Hierarchical data integrity**
*For any* assignment operation, the system should enforce subject-topic hierarchy, validate topic-subject relationships, prevent orphaned assignments, and preserve hierarchical relationships in all queries
**Validates: Requirements 8.1, 8.2, 8.3, 8.4, 8.5**

## Error Handling

### Input Validation Errors
- **Invalid Assignment Data**: Return structured error messages for missing or malformed required fields
- **File Upload Errors**: Provide specific feedback for file type, size, or security violations
- **Authentication Failures**: Return appropriate HTTP status codes with clear error messages
- **Authorization Violations**: Log security attempts and return generic access denied messages

### Business Logic Errors
- **Duplicate Submissions**: Prevent multiple submissions per student per assignment
- **Deadline Violations**: Block submissions after due dates with clear messaging
- **Invalid Evaluations**: Reject marks exceeding maximum limits or evaluations by unauthorized teachers
- **Orphaned Data**: Prevent creation of assignments without valid subject-topic associations

### System Errors
- **Database Failures**: Implement transaction rollback and provide user-friendly error messages
- **File Storage Issues**: Handle storage failures gracefully with retry mechanisms
- **Network Timeouts**: Implement appropriate timeout handling and user feedback
- **Concurrent Access**: Handle simultaneous operations with proper locking mechanisms

## Testing Strategy

### Dual Testing Approach

The Assignment Management System will employ both unit testing and property-based testing to ensure comprehensive correctness validation:

**Unit Testing Focus:**
- Specific examples demonstrating correct behavior for each component
- Integration points between assignment, submission, and evaluation services
- Edge cases like deadline boundaries and file upload limits
- Error conditions and exception handling scenarios

**Property-Based Testing Focus:**
- Universal properties that should hold across all valid inputs using **fast-check** library for JavaScript/TypeScript
- Each property-based test will run a minimum of 100 iterations to ensure thorough validation
- Properties will be tagged with comments referencing the design document using format: **Feature: assignment-management, Property {number}: {property_text}**

**Testing Framework:**
- **Unit Tests**: Jest for JavaScript/TypeScript components, PHPUnit for PHP backend services
- **Property-Based Tests**: fast-check library for comprehensive property validation
- **Integration Tests**: Supertest for API endpoint testing
- **Database Tests**: In-memory database for isolated testing scenarios

**Test Coverage Requirements:**
- All correctness properties must be implemented as individual property-based tests
- Each property test must explicitly reference its corresponding design property
- Unit tests must cover specific examples and edge cases not covered by properties
- Integration tests must validate end-to-end workflows across system boundaries