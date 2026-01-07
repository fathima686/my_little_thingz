# Implementation Plan

## Integration with Existing Tutorial System

This assignment management feature will integrate with your existing tutorial system by:
- **Subjects**: Using existing tutorial categories (Hand Embroidery, Resin Art, etc.)
- **Topics**: Adding sub-topics within each category
- **Teachers**: Leveraging existing user roles (teacher/admin users)
- **Students**: Using existing tutorial users
- **Database**: Extending current schema with assignment-specific tables

- [-] 1. Set up database schema and extend existing tutorial system

  - Create database migration files for assignments, submissions, evaluations, and topics tables
  - Extend existing tutorial categories to work as subjects
  - Create topics table linked to existing categories
  - Implement PHP model classes with validation and relationships
  - Set up foreign key constraints and indexes for performance
  - _Requirements: 1.1, 1.2, 1.3, 8.1, 8.2_

- [ ]* 1.1 Write property test for assignment creation validation
  - **Property 1: Assignment creation validation**
  - **Validates: Requirements 1.1**

- [ ]* 1.2 Write property test for assignment data completeness
  - **Property 2: Assignment data completeness**
  - **Validates: Requirements 1.2, 1.3**

- [ ]* 1.3 Write property test for assignment data validation
  - **Property 3: Assignment data validation**
  - **Validates: Requirements 1.4**

- [ ]* 1.4 Write property test for hierarchical data integrity
  - **Property 24: Hierarchical data integrity**
  - **Validates: Requirements 8.1, 8.2, 8.3, 8.4, 8.5**

- [ ] 2. Implement assignment management service integrated with tutorial system
  - Create AssignmentManager class extending existing tutorial functionality
  - Implement assignment creation with category-topic validation using existing categories
  - Add assignment listing and filtering by topic functionality
  - Implement assignment update and deletion with teacher ownership checks
  - Integrate with existing user authentication and role system
  - _Requirements: 1.1, 1.2, 1.4, 1.5, 2.1, 2.3, 6.1, 6.5_

- [ ]* 2.1 Write property test for assignment creation confirmation
  - **Property 4: Assignment creation confirmation**
  - **Validates: Requirements 1.5**

- [ ]* 2.2 Write property test for assignment organization consistency
  - **Property 5: Assignment organization consistency**
  - **Validates: Requirements 2.1, 2.2, 2.4**

- [ ]* 2.3 Write property test for topic filtering accuracy
  - **Property 6: Topic filtering accuracy**
  - **Validates: Requirements 2.3**

- [ ]* 2.4 Write property test for teacher ownership access control
  - **Property 21: Teacher ownership access control**
  - **Validates: Requirements 6.5**

- [ ] 3. Create teacher API endpoints integrated with existing tutorial APIs
  - Implement POST /api/teacher/assignments for assignment creation (similar to tutorial creation)
  - Create GET /api/teacher/assignments for listing teacher's assignments
  - Add PUT /api/teacher/assignments/{id} for assignment updates
  - Implement DELETE /api/teacher/assignments/{id} for assignment deletion
  - Add GET /api/teacher/submissions/{assignmentId} for viewing submissions
  - Integrate with existing authentication headers (X-Admin-User-Id)
  - _Requirements: 1.1, 1.2, 1.4, 1.5, 5.1, 6.1, 6.5_

- [ ]* 3.1 Write unit tests for teacher API endpoints
  - Test assignment creation, listing, updating, and deletion endpoints
  - Test submission viewing and access control
  - _Requirements: 1.1, 1.2, 1.4, 1.5, 5.1, 6.1, 6.5_

- [ ] 4. Implement submission handling service
  - Create SubmissionHandler class for managing student submissions
  - Implement text and file submission processing
  - Add file upload validation (type, size, security)
  - Implement submission status tracking and updates
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 7.3_

- [ ]* 4.1 Write property test for submission format acceptance
  - **Property 8: Submission format acceptance**
  - **Validates: Requirements 3.1**

- [ ]* 4.2 Write property test for file upload validation
  - **Property 9: File upload validation**
  - **Validates: Requirements 3.2, 7.3**

- [ ]* 4.3 Write property test for submission data integrity
  - **Property 10: Submission data integrity**
  - **Validates: Requirements 3.3, 3.4**

- [ ]* 4.4 Write property test for submission status update
  - **Property 11: Submission status update**
  - **Validates: Requirements 3.5**

- [ ] 5. Create student API endpoints integrated with existing tutorial system
  - Implement GET /api/student/assignments for listing assignments by topic (similar to tutorial listing)
  - Create GET /api/student/assignments/{id} for assignment details
  - Add POST /api/student/submit for assignment submission with file upload (reuse tutorial file upload logic)
  - Implement GET /api/student/submissions for submission history
  - Add GET /api/student/progress for progress tracking
  - Integrate with existing tutorial authentication (X-Tutorial-Email)
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 3.1, 3.2, 3.5, 4.1, 4.2, 4.5_

- [ ]* 5.1 Write property test for student access control
  - **Property 7: Student access control**
  - **Validates: Requirements 2.5**

- [ ]* 5.2 Write property test for status information completeness
  - **Property 12: Status information completeness**
  - **Validates: Requirements 4.1, 4.2, 4.3**

- [ ]* 5.3 Write property test for real-time data consistency
  - **Property 14: Real-time data consistency**
  - **Validates: Requirements 4.5**

- [ ]* 5.4 Write unit tests for student API endpoints
  - Test assignment listing, details, submission, and progress endpoints
  - Test access control and data filtering
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 3.1, 3.2, 3.5, 4.1, 4.2, 4.5_

- [ ] 6. Implement evaluation processing service
  - Create EvaluationProcessor class for handling teacher evaluations
  - Implement marks assignment with validation against maximum limits
  - Add feedback processing and storage
  - Implement evaluation history tracking
  - Add notification system for evaluation completion
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ]* 6.1 Write property test for teacher submission access
  - **Property 15: Teacher submission access**
  - **Validates: Requirements 5.1**

- [ ]* 6.2 Write property test for evaluation constraints
  - **Property 16: Evaluation constraints**
  - **Validates: Requirements 5.2, 5.3**

- [ ]* 6.3 Write property test for evaluation completion workflow
  - **Property 17: Evaluation completion workflow**
  - **Validates: Requirements 5.4, 5.5**

- [ ]* 6.4 Write property test for score calculation accuracy
  - **Property 13: Score calculation accuracy**
  - **Validates: Requirements 4.4**

- [ ] 7. Create evaluation API endpoints for teachers
  - Implement POST /api/teacher/evaluate for submitting evaluations
  - Add GET /api/teacher/evaluations/{submissionId} for evaluation history
  - Create GET /api/teacher/statistics/{assignmentId} for assignment statistics
  - _Requirements: 5.2, 5.3, 5.4, 5.5, 6.2_

- [ ]* 7.1 Write property test for statistics calculation accuracy
  - **Property 19: Statistics calculation accuracy**
  - **Validates: Requirements 6.2**

- [ ]* 7.2 Write unit tests for evaluation API endpoints
  - Test evaluation submission, history retrieval, and statistics
  - Test marks validation and feedback processing
  - _Requirements: 5.2, 5.3, 5.4, 5.5, 6.2_

- [ ] 8. Implement deadline management and reporting features
  - Add deadline enforcement logic to prevent late submissions
  - Implement assignment statistics calculation
  - Create report generation functionality for student performance
  - Add overdue assignment marking system
  - _Requirements: 6.1, 6.2, 6.3, 6.4_

- [ ]* 8.1 Write property test for deadline management rules
  - **Property 18: Deadline management rules**
  - **Validates: Requirements 6.1, 6.3**

- [ ]* 8.2 Write property test for report data completeness
  - **Property 20: Report data completeness**
  - **Validates: Requirements 6.4**

- [ ] 9. Implement security and authentication layer
  - Add role-based access control for all assignment features
  - Implement user authentication verification for API endpoints
  - Add audit logging for system activities and data modifications
  - Implement session management and security policies
  - _Requirements: 7.1, 7.5_

- [ ]* 9.1 Write property test for authentication and authorization
  - **Property 22: Authentication and authorization**
  - **Validates: Requirements 7.1**

- [ ]* 9.2 Write property test for audit logging completeness
  - **Property 23: Audit logging completeness**
  - **Validates: Requirements 7.5**

- [ ]* 9.3 Write unit tests for security layer
  - Test role-based access control and authentication
  - Test audit logging and session management
  - _Requirements: 7.1, 7.5_

- [ ] 10. Create frontend components for teacher interface integrated with tutorial dashboard
  - Build assignment creation form with existing category-topic selection
  - Implement assignment listing and management dashboard (extend TutorialsDashboard)
  - Create submission review interface with evaluation forms
  - Add assignment statistics and reporting views
  - Integrate with existing tutorial navigation and styling
  - _Requirements: 1.1, 1.2, 1.5, 5.1, 5.2, 5.3, 6.2, 6.4_

- [ ]* 10.1 Write unit tests for teacher frontend components
  - Test assignment creation, management, and evaluation interfaces
  - Test form validation and user interactions
  - _Requirements: 1.1, 1.2, 1.5, 5.1, 5.2, 5.3, 6.2, 6.4_

- [ ] 11. Create frontend components for student interface integrated with tutorial system
  - Build assignment browsing interface with topic filtering (extend existing category system)
  - Implement assignment submission form with file upload support (reuse tutorial file upload)
  - Create progress tracking dashboard with submission status (integrate with existing learning dashboard)
  - Add feedback and marks display interface
  - Add "Assignments" section to existing TutorialsDashboard navigation
  - _Requirements: 2.1, 2.2, 2.3, 3.1, 3.2, 4.1, 4.2, 4.3, 4.4_

- [ ]* 11.1 Write unit tests for student frontend components
  - Test assignment browsing, submission, and progress tracking
  - Test file upload functionality and status displays
  - _Requirements: 2.1, 2.2, 2.3, 3.1, 3.2, 4.1, 4.2, 4.3, 4.4_

- [ ] 12. Integrate file storage with existing tutorial file management system
  - Extend existing tutorial file upload system for assignment submissions
  - Implement file upload handling with security scanning (reuse tutorial security logic)
  - Add file download functionality for teachers using existing file serving
  - Create file cleanup and management utilities
  - _Requirements: 3.2, 7.3_

- [ ]* 12.1 Write unit tests for file storage system
  - Test file upload, storage, and retrieval functionality
  - Test security scanning and file validation
  - _Requirements: 3.2, 7.3_

- [ ] 13. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 14. Set up notification system for assignment updates
  - Implement email notifications for assignment creation and deadlines
  - Add notifications for evaluation completion and feedback
  - Create in-app notification system for real-time updates
  - _Requirements: 5.4, 6.3_

- [ ]* 14.1 Write unit tests for notification system
  - Test email and in-app notification delivery
  - Test notification triggers and content
  - _Requirements: 5.4, 6.3_

- [ ] 15. Final integration and system testing
  - Integrate all components and test end-to-end workflows
  - Perform comprehensive testing of teacher and student user journeys
  - Validate all requirements are met through system testing
  - _Requirements: All requirements_

- [ ] 16. Final Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.