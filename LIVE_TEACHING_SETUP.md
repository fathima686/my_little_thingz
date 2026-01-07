# Live Teaching Feature - Setup Guide

## Overview
The Live Teaching feature allows teachers to create and manage live classes organized by subjects, while students can browse, filter, and join live sessions through Google Meet links.

## Features
- **Subject-based organization**: Live classes organized by craft subjects
- **Scheduled sessions**: Teachers can create sessions with date, time, and Google Meet links
- **Subject filtering**: Students can filter sessions by subject
- **Registration tracking**: Students can register for sessions
- **Access control**: Only teachers/admins can create sessions
- **Google Meet integration**: Direct links to join live classes

## Setup Instructions

### 1. Database Migration
Run the migration script to create the necessary tables:

```bash
php backend/migrate_live_teaching.php
```

This will create:
- `live_subjects` table (with sample subjects)
- `live_sessions` table
- `live_session_registrations` table
- Adds 'teacher' role to the roles table

### 2. Assign Teacher Role
To allow a user to create live sessions, assign them the 'teacher' role:

```sql
-- Get the user_id of the user you want to make a teacher
-- Then assign the teacher role (role_id = 4)
INSERT INTO user_roles (user_id, role_id) 
VALUES (YOUR_USER_ID, 4)
ON DUPLICATE KEY UPDATE role_id = 4;
```

Or assign admin role (admins can also create sessions):
```sql
INSERT INTO user_roles (user_id, role_id) 
VALUES (YOUR_USER_ID, 1)
ON DUPLICATE KEY UPDATE role_id = 1;
```

### 3. Access the Feature
1. Log in to the tutorials dashboard
2. Click on "Live Classes" in the navigation bar
3. Teachers will see a "Create Live Session" button
4. Students will see available sessions to join

## Usage

### For Teachers

#### Creating a Live Session
1. Navigate to "Live Classes" section
2. Click "Create Live Session"
3. Fill in the form:
   - **Subject**: Select from available subjects
   - **Title**: Name of the session
   - **Description**: Optional description
   - **Google Meet Link**: The meeting link (e.g., https://meet.google.com/xxx-xxxx-xxx)
   - **Date & Time**: When the session will be held
   - **Duration**: Length in minutes (default: 60)
   - **Max Participants**: Maximum number of students (default: 50)
4. Click "Create Session"

#### Managing Sessions
- View all your created sessions in the Live Classes section
- Sessions are automatically filtered by status (Live, Scheduled, Completed)
- Click on Google Meet link to open the meeting

### For Students

#### Browsing Sessions
1. Navigate to "Live Classes" section
2. Use the subject filter to find sessions by category
3. View session details:
   - Subject and title
   - Date and time
   - Duration
   - Number of registered participants
   - Status (Scheduled, Live Now, etc.)

#### Joining a Session
1. Click "Register" to register for a session (optional)
2. Click "Join Session" to open the Google Meet link
3. The link opens in a new tab

## API Endpoints

### Teacher Endpoints
- `GET /api/teacher/live-sessions.php` - Get all sessions created by teacher
- `POST /api/teacher/live-sessions.php` - Create a new session
- `PUT /api/teacher/live-sessions.php` - Update a session
- `DELETE /api/teacher/live-sessions.php` - Delete a session

**Headers Required:**
- `X-User-ID`: Teacher user ID
- `Authorization`: Bearer token

### Student Endpoints
- `GET /api/customer/live-sessions.php` - Get all available sessions (with optional `?subject_id=X` filter)
- `POST /api/customer/live-sessions.php` - Register for a session
- `GET /api/customer/live-subjects.php` - Get all active subjects

**Headers Required:**
- `X-User-ID`: User ID (optional for viewing, required for registration)

## Database Schema

### live_subjects
- `id`: Primary key
- `name`: Subject name
- `description`: Subject description
- `icon_url`: Optional icon URL
- `color`: Color code for UI
- `is_active`: Active status

### live_sessions
- `id`: Primary key
- `subject_id`: Foreign key to live_subjects
- `teacher_id`: Foreign key to users
- `title`: Session title
- `description`: Session description
- `google_meet_link`: Google Meet URL
- `scheduled_date`: Date of session
- `scheduled_time`: Time of session
- `duration_minutes`: Session duration
- `status`: scheduled, live, completed, cancelled
- `max_participants`: Maximum participants
- `created_at`, `updated_at`: Timestamps

### live_session_registrations
- `id`: Primary key
- `session_id`: Foreign key to live_sessions
- `user_id`: Foreign key to users
- `registered_at`: Registration timestamp
- `attended`: Boolean flag

## Customization

### Adding New Subjects
You can add new subjects directly to the database:

```sql
INSERT INTO live_subjects (name, description, color) 
VALUES ('New Subject', 'Description here', '#HEXCOLOR');
```

### Modifying Session Status
Sessions can be updated via the API or directly in the database:

```sql
UPDATE live_sessions 
SET status = 'live' 
WHERE id = SESSION_ID;
```

## Troubleshooting

### Teacher button not showing
- Ensure the user has 'teacher' or 'admin' role assigned
- Check that `tutorialAuth.roles` includes 'teacher' or 'admin'
- Refresh the page after assigning the role

### Sessions not appearing
- Check that subjects are marked as `is_active = 1`
- Verify session status is 'scheduled' or 'live'
- Check browser console for API errors

### Registration failing
- Ensure user is logged in
- Check that session hasn't reached max participants
- Verify session status allows registration

## Future Enhancements
- Email notifications for session reminders
- Calendar integration
- Session recordings
- Attendance tracking
- Session reviews/ratings
- Recurring sessions
- Waitlist for full sessions


