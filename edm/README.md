# Engineering Department Management System (EDM)

A comprehensive, modern web application for managing academic operations in engineering colleges and universities.

Built with PHP PDO, MySQL, HTML5, Vanilla CSS Design System, and JavaScript.

---

## 🌟 Key System Features

### 👤 Role-Based Portals (3 Roles)
1. **Student Portal**
   - Live Attendance Marking (4-Digit PIN, QR Code Scanner, GPS Location Verification)
   - Subject-wise & Session-wise Attendance History with percentage indicators
   - Coursework Assignments Submission (Text responses + PDF/ZIP file uploads)
   - Internal Exam Marks & Grades (IA1, IA2, Model Exam scores)
   - Real-time Synchronized Live Presentation Viewer
   - Study Materials, Notes, and Question Paper Archive Download
   - Personal Academic Report Card Generator (PDF / CSV)

2. **Faculty & Staff Portal**
   - Live Attendance Session Creation with Dynamic 4-Digit PIN & QR Code
   - Live Attendance Monitor Dashboard with real-time check-in table & manual toggles
   - Live Presentation Broadcast Control Console (Prev/Next slide, Jump, Broadcast Notes to student screens)
   - Course Assignments Publishing & Submissions Evaluation (Grading + Feedback)
   - Internal Marks Entry (IA1, IA2, Model exam scores)
   - Study Materials & Question Paper Bank Uploads
   - Smart Lab & Classroom Reservation System
   - Faculty Analytics & CSV/PDF Report Generators

3. **Administrator Portal**
   - System-wide Dashboard with User & Department Metrics
   - CRUD Management for Students, Faculty, Departments, Subjects & Classrooms
   - Master Weekly Timetable Schedule Builder
   - Smart Classroom Booking Approval / Rejection Console
   - Security Audit Trail & Activity Logs
   - System-wide Analytics & Departmental PDF Summary Export

---

## 📂 System Folder Structure

```
edm/
├── assets/
│   ├── css/
│   │   └── style.css            # Global CSS Design System
│   └── js/
│       └── main.js             # Core JavaScript Utilities & Toasts
├── config/
│   ├── app.php                 # Application Configuration Constants
│   ├── database.php            # PDO Database Connection
│   └── permissions.php         # Role-Based Permission Definitions
├── database/
│   ├── schema.sql              # Database Tables Schema (21 Tables)
│   ├── sample_data.sql         # Seed Demo Data
│   └── indexes.sql             # Performance Index Definitions
├── includes/
│   ├── auth_check.php          # Session Auth Guard
│   ├── functions.php           # Utility & CSRF Helpers
│   ├── header.php              # Header & HTML Head
│   ├── footer.php              # Footer Scripts
│   ├── navbar.php              # Top Bar (Role-Aware)
│   ├── sidebar.php             # Navigation Menu (Role-Aware)
│   └── report_helpers.php      # Reporting Utilities
├── auth/
│   ├── student_login.php
│   ├── staff_login.php
│   ├── admin_login.php
│   ├── process_login.php
│   ├── forgot_password.php
│   ├── reset_password.php
│   └── logout.php
├── student/                   # Student Module (17 Files)
├── staff/                     # Faculty Module (24 Files)
├── admin/                     # Admin Module (23 Files)
├── api/                       # Dynamic REST/AJAX Endpoints (14 Files)
├── uploads/                   # Media & Document Attachments
└── index.php                  # Landing Page Portal Selection
```

---

## 🔑 Demo Account Credentials

| Role | Username | Password |
|------|----------|----------|
| **Student** | `john.d` | `student123` |
| **Faculty Staff** | `rajesh.s` | `staff123` |
| **Admin** | `admin` | `admin123` |

---

## ⚙️ Installation & Setup

1. **Copy Files to XAMPP htdocs:**
   Place project inside `C:\xampp\htdocs\edm\`.

2. **Import Database in MySQL (phpMyAdmin or CLI):**
   ```sql
   CREATE DATABASE engineering_department_db;
   USE engineering_department_db;
   SOURCE database/schema.sql;
   SOURCE database/sample_data.sql;
   SOURCE database/indexes.sql;
   ```

3. **Access Portal in Browser:**
   Open `http://localhost/edm/index.php`.
