DCROP Backend - Technical Documentation
System Architecture
Frontend (HTML/JS/CSS)
    ↓ HTTP/JSON
API Layer (api/*.php)
    ↓
Controller Layer (controllers/*.php)
    ↓
Model Layer (models/*.php)
    ↓
Database (MySQL via PDO)

File Structure & Purpose
Configuration

config/db.php - PDO database connection (localhost, dcrop_db, root, no password)

Models (Direct database operations)

models/studentModel.php - Student CRUD, authentication, attendance history
models/coordinatorModel.php - Coordinator CRUD, view students by community, reports
models/adminModel.php - Admin CRUD, bulk uploads, system statistics

Controllers (Business logic & validation)

controllers/studentController.php - Registration, login, attendance submission, messaging
controllers/coordinatorController.php - View students/attendance, escalate issues, create reports
controllers/adminController.php - Manage all users, upload Excel, system analytics

APIs (RESTful endpoints)

api/attendance.php - Submit/view/verify attendance
api/messages.php - Send/receive messages between roles
api/reports.php - Create/view coordinator reports
index.php - Main router (login, register, profile, logout)


How It Works
Database Connection
php$database = new Database();
$conn = $database->getConnection(); // Returns PDO connection
```
- Uses prepared statements (SQL injection prevention)
- Error mode: Exceptions
- Fetch mode: Associative arrays

### **Authentication Flow**
1. User sends email + password + role to `/index.php?endpoint=login`
2. Controller validates input
3. Model fetches user by email
4. `password_verify()` checks hashed password
5. Check `email_verified = 1`
6. Log activity to `activity_logs`
7. Return user data (without password)

### **Student Registration Flow**
1. Student submits: name, index_number, email, password, community
2. Controller checks if index_number exists (must be pre-uploaded by admin)
3. If not found → Reject
4. Generate 64-char verification token
5. Hash password with `password_hash()`
6. Insert into database
7. Return token (TODO: send verification email)

### **Attendance Submission Flow**
1. Student sends: date, latitude, longitude
2. Controller gets student's community
3. Verify GPS coordinates against community location
4. If within radius → `status = 'present', verified = 1`
5. If outside radius → `status = 'pending', verified = 0`
6. Insert/Update (UNIQUE on student_id + date)
7. Log activity

### **Message Flow**
- **Student → Coordinator**: POST to `/api/messages.php?action=send_to_coordinator`
- **Coordinator → Admin**: POST to `/api/messages.php?action=escalate`
- Messages stored with sender/receiver IDs and roles
- Complex CASE queries fetch names from appropriate tables

### **Report Generation**
1. Coordinator creates report (type: daily/weekly/monthly/incident)
2. Content validated
3. Insert into `reports` table with `coordinator_id`
4. Admin can view all reports via `/api/reports.php?action=all`

### **Bulk Upload (Admin)**
1. Admin uploads Excel with student/coordinator data
2. Parser extracts rows (placeholder for PhpSpreadsheet)
3. Begin transaction
4. Loop through rows, insert with default passwords
5. Track successes/errors
6. Commit or rollback
7. Return success count + error list

---

## API Endpoints Quick Reference

### **Main Entry (`index.php`)**
```
GET    /?endpoint=test              Test DB connection
POST   /?endpoint=login             Login (all roles)
POST   /?endpoint=register          Student registration
GET    /?endpoint=verify_email      Email verification
GET    /?endpoint=profile           Get profile
PUT    /?endpoint=profile           Update profile
POST   /?endpoint=logout            Logout
GET    /?endpoint=system_stats      System statistics
```

### **Attendance API**
```
POST   /attendance.php?action=submit                 Submit attendance
GET    /attendance.php?action=history&student_id=X   Student history
GET    /attendance.php?action=student_attendance     Coordinator view
GET    /attendance.php?action=stats                  Statistics
GET    /attendance.php?action=overview               Admin overview
PUT    /attendance.php?action=verify                 Verify record
DELETE /attendance.php?action=delete                 Delete record
```

### **Messages API**
```
POST   /messages.php?action=send                Send message
POST   /messages.php?action=send_to_coordinator Student→Coordinator
POST   /messages.php?action=escalate            Coordinator→Admin
GET    /messages.php?action=received            Get inbox
GET    /messages.php?action=sent                Get sent
GET    /messages.php?action=unread_count        Unread badge
PUT    /messages.php?action=mark_read           Mark as read
DELETE /messages.php?action=delete              Delete message
```

### **Reports API**
```
POST   /reports.php?action=create             Create report
GET    /reports.php?action=coordinator_reports Own reports
GET    /reports.php?action=all                All reports (admin)
GET    /reports.php?action=by_type            Filter by type
GET    /reports.php?action=by_date_range      Date range
GET    /reports.php?action=statistics         Report stats
PUT    /reports.php?action=update             Update report
DELETE /reports.php?action=delete             Delete report
```

---

## Security Features

| Feature | Implementation |
|---------|----------------|
| **Password Hashing** | `password_hash()` with bcrypt |
| **SQL Injection** | PDO prepared statements with `bindParam()` |
| **Email Verification** | Random 64-char token, blocks login until verified |
| **Activity Logging** | All actions logged with IP + user agent |
| **CORS** | Headers allow cross-origin requests |
| **Role-Based Access** | Separate models/controllers per user type |

---

## Database Tables
```
students          - Student accounts (index_number UNIQUE)
coordinators      - Coordinator accounts (assigned_community)
admins            - Admin accounts
super_users       - Super admin accounts
attendance        - Daily attendance (student_id + date UNIQUE)
messages          - Cross-role messaging (sender/receiver with roles)
reports           - Coordinator reports (type: daily/weekly/monthly/incident)
activity_logs     - Audit trail (login/logout/attendance/messages)

Request/Response Examples
Login Request
jsonPOST /index.php?endpoint=login
{
  "email": "student@example.com",
  "password": "password123",
  "role": "student"
}
Login Response
json{
  "success": true,
  "message": "Login successful",
  "student": {
    "id": 5,
    "full_name": "John Doe",
    "email": "student@example.com",
    "community": "Tamale",
    "email_verified": 1
  }
}
Attendance Submission
jsonPOST /api/attendance.php?action=submit
{
  "student_id": 5,
  "date": "2024-12-05",
  "latitude": 9.4034,
  "longitude": -0.8448
}
Attendance Response
json{
  "success": true,
  "message": "Attendance submitted and verified",
  "verified": true
}
```

---

## Key Functions by Role

### **Students Can:**
- Register (if pre-uploaded by admin)
- Login with email verification
- Submit daily attendance with GPS
- View attendance history
- Send messages to coordinators

### **Coordinators Can:**
- Login
- View assigned students (by community)
- View student attendance (with date filters)
- Receive messages from students
- Escalate issues to admins
- Create reports (daily/weekly/monthly/incident)

### **Admins Can:**
- Login
- Upload student/coordinator data (bulk Excel)
- View all students with statistics
- View all coordinators
- View all messages system-wide
- View all reports
- Delete students/coordinators
- View activity logs
- Generate system analytics

---

## Data Flow Example: Coordinator Views Attendance
```
1. Frontend → GET /api/attendance.php?action=student_attendance&coordinator_id=3

2. API → CoordinatorController->viewStudentAttendance(3)

3. Controller → CoordinatorModel->getStudentAttendance(3)

4. Model executes:
   SELECT a.*, s.full_name, s.index_number
   FROM attendance a
   JOIN students s ON a.student_id = s.id
   JOIN coordinators c ON s.community = c.assigned_community
   WHERE c.id = 3

5. Returns JSON:
   {
     "success": true,
     "attendance": [...array of records...],
     "count": 25
   }

Setup Instructions

Import Database: Import dcrop_db.sql into phpMyAdmin
Copy Files: Place all backend files in htdocs/DCROPA/backend/
Test Connection: Visit http://localhost/DCROPA/backend/index.php?endpoint=test
Default Super User:

Email: admin@dcrop.com
Password: Admin@123




Notes

Location Verification: Currently placeholder - implement Haversine formula for actual GPS validation
Excel Parsing: Placeholder - integrate PhpSpreadsheet library for actual Excel reading
Email Sending: TODO - integrate mail service (PHPMailer/SendGrid) for verification emails
Session Management: TODO - implement JWT tokens or PHP sessions for authentication persistence