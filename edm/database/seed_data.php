<?php
/**
 * Database Seeder for EDM
 * Seeds demo accounts and sample department data
 */
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    $db->beginTransaction();

    // 1. Departments
    $stmt = $db->prepare("
        INSERT INTO departments (id, name, code, hod_name, description, status) 
        VALUES (1, 'Computer Science and Engineering', 'CSE', 'Dr. K. Raman', 'Department of Computer Science and Engineering', 'active')
        ON DUPLICATE KEY UPDATE name=VALUES(name)
    ");
    $stmt->execute();

    $stmt = $db->prepare("
        INSERT INTO departments (id, name, code, hod_name, description, status) 
        VALUES (2, 'Electronics and Communication Engineering', 'ECE', 'Dr. S. Priya', 'Department of Electronics and Communication Engineering', 'active')
        ON DUPLICATE KEY UPDATE name=VALUES(name)
    ");
    $stmt->execute();

    // Passwords
    $adminHash = password_hash('admin123', PASSWORD_BCRYPT);
    $staffHash = password_hash('staff123', PASSWORD_BCRYPT);
    $studentHash = password_hash('student123', PASSWORD_BCRYPT);

    // 2. Users (Admin, Staff, Student)
    $db->prepare("
        INSERT INTO users (id, username, email, password, role, status)
        VALUES (1, 'admin', 'admin@edms.edu', :p, 'admin', 'active')
        ON DUPLICATE KEY UPDATE password=VALUES(password), status='active'
    ")->execute(['p' => $adminHash]);

    $db->prepare("
        INSERT INTO users (id, username, email, password, role, status)
        VALUES (2, 'rajesh.s', 'rajesh.s@edms.edu', :p, 'staff', 'active')
        ON DUPLICATE KEY UPDATE password=VALUES(password), status='active'
    ")->execute(['p' => $staffHash]);

    $db->prepare("
        INSERT INTO users (id, username, email, password, role, status)
        VALUES (3, 'john.d', 'john.d@edms.edu', :p, 'student', 'active')
        ON DUPLICATE KEY UPDATE password=VALUES(password), status='active'
    ")->execute(['p' => $studentHash]);

    // 3. Staff profile
    $db->prepare("
        INSERT INTO staff (id, user_id, staff_code, first_name, last_name, department_id, designation, qualification, specialization, join_date)
        VALUES (1, 2, 'STF001', 'Rajesh', 'Sharma', 1, 'Associate Professor', 'M.Tech, Ph.D', 'Cloud Computing & Distributed Systems', '2020-06-15')
        ON DUPLICATE KEY UPDATE staff_code=VALUES(staff_code)
    ")->execute();

    // 4. Student profile
    $db->prepare("
        INSERT INTO students (id, user_id, register_number, first_name, last_name, department_id, semester, section, academic_year, admission_date)
        VALUES (1, 3, 'REG2024001', 'John', 'Doe', 1, 6, 'A', '2024-2025', '2021-08-01')
        ON DUPLICATE KEY UPDATE register_number=VALUES(register_number)
    ")->execute();

    // 5. Sample Subjects
    $db->prepare("
        INSERT INTO subjects (id, code, name, department_id, semester, credits, type, staff_id, status)
        VALUES 
        (1, 'CS601', 'Distributed Cloud Architecture', 1, 6, 4, 'theory', 1, 'active'),
        (2, 'CS602', 'Machine Learning & Neural Networks', 1, 6, 4, 'theory', 1, 'active'),
        (3, 'CS603', 'Advanced Web Engineering Lab', 1, 6, 2, 'lab', 1, 'active')
        ON DUPLICATE KEY UPDATE name=VALUES(name)
    ")->execute();

    // 6. Sample Classrooms
    $db->prepare("
        INSERT INTO classrooms (id, room_number, building, floor, capacity, type, has_projector, has_ac, has_smart_board, status)
        VALUES 
        (1, 'LH-301', 'Turing Tech Block', '3rd Floor', 65, 'smart', 1, 1, 1, 'available'),
        (2, 'LAB-102', 'Lovelace Computing Labs', '1st Floor', 45, 'lab', 1, 1, 1, 'available'),
        (3, 'SEM-401', 'Main Academic Complex', '4th Floor', 120, 'seminar', 1, 1, 1, 'available')
        ON DUPLICATE KEY UPDATE room_number=VALUES(room_number)
    ")->execute();

    // 7. Sample Attendance Records for Student
    $db->prepare("
        INSERT INTO attendance_sessions (id, staff_id, subject_id, department_id, semester, section, session_date, start_time, end_time, pin_code, status, expires_at)
        VALUES (1, 1, 1, 1, 6, 'A', CURDATE(), '09:00:00', '10:00:00', '4821', 'completed', NOW())
        ON DUPLICATE KEY UPDATE status=VALUES(status)
    ")->execute();

    $db->prepare("
        INSERT INTO attendance_records (id, session_id, student_id, status, marked_at, marked_via)
        VALUES (1, 1, 1, 'present', NOW(), 'pin')
        ON DUPLICATE KEY UPDATE status=VALUES(status)
    ")->execute();

    // 8. Sample Notifications
    $db->prepare("
        INSERT INTO notifications (user_id, title, message, type, created_at)
        VALUES 
        (1, 'System Initialized', 'Engineering Department Management System is ready for operation.', 'info', NOW()),
        (2, 'Welcome Prof. Sharma', 'You can create attendance sessions and broadcast live slide presentations.', 'success', NOW()),
        (3, 'Welcome John', 'Your semester 6 dashboard and course attendance are synced.', 'success', NOW())
    ")->execute();

    $db->commit();
    echo "SEEDING_SUCCESSFUL\n";
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "SEEDING_ERROR: " . $e->getMessage() . "\n";
}
