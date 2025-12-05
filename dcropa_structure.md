# DCROPA System Structure

## Overview

DCROPA is a PHP and SQL-based application designed to manage and monitor
student participation in the TTFFP program at UDS. The system validates
student attendance through location checks and manages user roles with
varying privilege levels.

## User Types

### 1. Students

-   Must already be registered for TTFFP via a physical Excel
    registration.
-   Registration data is uploaded into the system backend.
-   When signing up, the system verifies the student's index number
    against stored TTFFP records.
-   Students can:
    -   Submit attendance daily using a calendar-style form.
    -   Attendance submission triggers a location check to verify
        presence in the assigned community and correct radius.
    -   Send messages to coordinators regarding issues.

### 2. Coordinators

-   Monitor all assigned student activities.
-   Capabilities:
    -   View student attendance.
    -   Track student logins and logouts.
    -   Receive messages from students (one-way communication).
    -   Escalate issues to admins.

### 3. Admins

-   Manage system-wide student and coordinator data.
-   Capabilities:
    -   Upload student and coordinator data via Excel.
    -   Monitor both coordinators and students.
    -   View system activity logs such as login events, signups, and
        attendance behavior.
    -   Handle escalations.

### 4. Super User (Developer)

-   Highest privilege level.
-   Capabilities:
    -   All admin functions.
    -   Manage admin accounts (create, update, delete).
    -   Access all elevated system controls.

## Authentication

-   Email verification required during signup.

## Core Workflow

1.  TTFFP registers students in person.
2.  Excel data uploaded to backend.
3.  Student signs up; system verifies eligibility.
4.  Student submits attendance; system validates via location.
5.  Coordinator monitors students and forwards issues.
6.  Admin manages data and system functions.
7.  Super user oversees entire hierarchy.
