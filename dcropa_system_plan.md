# DCROPA System Structure and Execution Plan

## Overview

DCROPA is a PHP and SQL-based application designed to manage and monitor student participation in the TTFFP program at UDS. The system validates student attendance through location checks and manages user roles with varying privilege levels.

## User Types

### 1. Students

* Must already be registered for TTFFP via a physical Excel registration.
* Registration data is uploaded into the system backend.
* When signing up, the system verifies the student's index number against stored TTFFP records.
* Students can:

  * Submit attendance daily using a calendar-style form.
  * Attendance submission triggers a location check to verify presence in the assigned community and correct radius.
  * Send messages to coordinators regarding issues.
  * View personal attendance history.

### 2. Coordinators

* Monitor all assigned student activities.
* Capabilities:

  * View student attendance.
  * Track student logins and logouts.
  * Receive messages from students (one-way communication).
  * Escalate issues to admins.
  * Generate coordinator-level reports.

### 3. Admins

* Manage system-wide student and coordinator data.
* Capabilities:

  * Upload student and coordinator data via Excel.
  * Monitor both coordinators and students.
  * View system activity logs such as login events, signups, and attendance behavior.
  * Handle escalations.
  * Generate admin-level analytics and reports.

### 4. Super User (Developer)

* Highest privilege level.
* Capabilities:

  * All admin functions.
  * Manage admin accounts (create, update, delete).
  * Access all elevated system controls.
  * Modify system configurations and database schema.

## Authentication

* Email verification required during signup.
* Role-based access control enforced across all pages.

## Core Workflow

1. TTFFP registers students in person.
2. Excel data uploaded to backend.
3. Student signs up; system verifies eligibility.
4. Student submits attendance; system validates via location.
5. Coordinator monitors students and forwards issues.
6. Admin manages data and system functions.
7. Super user oversees entire hierarchy.

## Technical Directory Structure

```
DCROPA/
├── backend/ (PHP + SQL via XAMPP)
│   ├── config/
│   │   └── db.php
│   ├── controllers/
│   │   ├── studentController.php
│   │   ├── coordinatorController.php
│   │   └── adminController.php
│   ├── models/
│   │   ├── studentModel.php
│   │   ├── coordinatorModel.php
│   │   └── adminModel.php
│   ├── uploads/ (Excel files)
│   ├── index.php
│   └── api/ (REST endpoints)
│       ├── attendance.php
│       ├── messages.php
│       └── reports.php

├── frontend/ (HTML + CSS + JS)
│   ├── index.html (Login/Home page)
│   ├── dashboard/
│   │   ├── student.html
│   │   ├── coordinator.html
│   │   ├── admin.html
│   │   └── superuser.html
│   ├── attendance/
│   │   ├── submit.html
│   │   └── history.html
│   ├── messaging/
│   │   └── messages.html
│   ├── reports/
│   │   ├── coordinatorReport.html
│   │   └── adminReport.html
│   ├── css/
│   │   └── styles.css
│   ├── js/
│   │   ├── main.js
│   │   ├── attendance.js
│   │   └── messaging.js
│   └── assets/
│       ├── images/
│       └── icons/
```

## Execution Plan

### Phase 1: Backend Setup

1. Set up XAMPP and configure MySQL database.
2. Create database schema for students, coordinators, admins, attendance logs, messages.
3. Implement PHP models and controllers for CRUD operations.
4. Develop APIs for frontend interaction (attendance submission, messaging, reports).

### Phase 2: Frontend Development

1. Build HTML structure for login, dashboards, attendance forms, messages, and reports.
2. Style pages with CSS for responsiveness and usability.
3. Implement JS logic for form submissions, validation, dynamic updates, and navigation.
4. Integrate frontend with backend APIs.

### Phase 3: Testing and Validation

1. Test student registration verification.
2. Test attendance submission with location validation.
3. Verify coordinator, admin, and super user functionalities.
4. Conduct security checks (email verification, SQL injection prevention, role-based access control).

### Phase 4: Deployment and Future Improvements

1. Deploy on local or cloud server.
2. Monitor system activity and logs.
3. Add new features (notifications, analytics dashboards, reporting tools).
4. Maintain modularity to allow continuous improvements.
