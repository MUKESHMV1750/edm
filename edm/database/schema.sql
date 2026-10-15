-- ============================================================
-- Engineering Department Management System
-- Database Schema: engineering_department_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS `engineering_department_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `engineering_department_db`;

-- ============================================================
-- 1. DEPARTMENTS
-- ============================================================
CREATE TABLE `departments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `hod_name` VARCHAR(100) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 2. USERS  (single credentials table, role-based)
-- ============================================================
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student','staff','admin') NOT NULL,
  `status` ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `profile_image` VARCHAR(255) DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `password_reset_token` VARCHAR(255) DEFAULT NULL,
  `password_reset_expires` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 3. STUDENTS
-- ============================================================
CREATE TABLE `students` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL UNIQUE,
  `register_number` VARCHAR(30) NOT NULL UNIQUE,
  `first_name` VARCHAR(60) NOT NULL,
  `last_name` VARCHAR(60) NOT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `gender` ENUM('male','female','other') DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `department_id` INT UNSIGNED NOT NULL,
  `semester` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `section` VARCHAR(5) DEFAULT 'A',
  `academic_year` VARCHAR(20) NOT NULL,
  `admission_date` DATE DEFAULT NULL,
  `guardian_name` VARCHAR(100) DEFAULT NULL,
  `guardian_phone` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_students_dept` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- 4. STAFF
-- ============================================================
CREATE TABLE `staff` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL UNIQUE,
  `staff_code` VARCHAR(30) NOT NULL UNIQUE,
  `first_name` VARCHAR(60) NOT NULL,
  `last_name` VARCHAR(60) NOT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `gender` ENUM('male','female','other') DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `department_id` INT UNSIGNED NOT NULL,
  `designation` VARCHAR(80) DEFAULT NULL,
  `qualification` VARCHAR(150) DEFAULT NULL,
  `specialization` VARCHAR(150) DEFAULT NULL,
  `join_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_staff_dept` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- 5. SUBJECTS
-- ============================================================
CREATE TABLE `subjects` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `department_id` INT UNSIGNED NOT NULL,
  `semester` TINYINT UNSIGNED NOT NULL,
  `credits` TINYINT UNSIGNED DEFAULT 3,
  `type` ENUM('theory','lab','elective') NOT NULL DEFAULT 'theory',
  `staff_id` INT UNSIGNED DEFAULT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_subjects_dept` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_subjects_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 6. CLASSROOMS
-- ============================================================
CREATE TABLE `classrooms` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `room_number` VARCHAR(20) NOT NULL UNIQUE,
  `building` VARCHAR(80) DEFAULT NULL,
  `building_name` VARCHAR(80) DEFAULT NULL,
  `floor` VARCHAR(20) DEFAULT NULL,
  `capacity` SMALLINT UNSIGNED NOT NULL DEFAULT 60,
  `type` ENUM('lecture','lab','seminar','smart') NOT NULL DEFAULT 'lecture',
  `has_projector` TINYINT(1) NOT NULL DEFAULT 0,
  `has_ac` TINYINT(1) NOT NULL DEFAULT 0,
  `has_smart_board` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('available','maintenance','reserved') NOT NULL DEFAULT 'available',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 7. TIMETABLE
-- ============================================================
CREATE TABLE `timetable` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `department_id` INT UNSIGNED NOT NULL,
  `semester` TINYINT UNSIGNED NOT NULL,
  `section` VARCHAR(5) NOT NULL DEFAULT 'A',
  `day_of_week` ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `period_number` TINYINT UNSIGNED NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
  `staff_id` INT UNSIGNED DEFAULT NULL,
  `classroom_id` INT UNSIGNED DEFAULT NULL,
  `academic_year` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tt_dept` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tt_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tt_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tt_classroom` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE OR REPLACE VIEW `timetables` AS SELECT * FROM `timetable`;

-- ============================================================
-- 8. ATTENDANCE SESSIONS
-- ============================================================
CREATE TABLE `attendance_sessions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `staff_id` INT UNSIGNED NOT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
  `department_id` INT UNSIGNED NOT NULL,
  `semester` TINYINT UNSIGNED NOT NULL,
  `section` VARCHAR(5) NOT NULL DEFAULT 'A',
  `session_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `qr_code` VARCHAR(255) DEFAULT NULL,
  `pin_code` VARCHAR(10) DEFAULT NULL,
  `classroom_id` INT UNSIGNED DEFAULT NULL,
  `allowed_latitude` DECIMAL(10,7) DEFAULT NULL,
  `allowed_longitude` DECIMAL(10,7) DEFAULT NULL,
  `location_radius` INT UNSIGNED DEFAULT 100,
  `status` ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
  `expires_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_as_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_as_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_as_dept` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_as_classroom` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 9. ATTENDANCE RECORDS
-- ============================================================
CREATE TABLE `attendance_records` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT UNSIGNED NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `status` ENUM('present','absent','late','excused') NOT NULL DEFAULT 'absent',
  `marked_at` DATETIME DEFAULT NULL,
  `marked_via` ENUM('qr','pin','gps','manual') DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `latitude` DECIMAL(10,7) DEFAULT NULL,
  `longitude` DECIMAL(10,7) DEFAULT NULL,
  `corrected` TINYINT(1) NOT NULL DEFAULT 0,
  `corrected_by` INT UNSIGNED DEFAULT NULL,
  `correction_reason` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_session_student` (`session_id`, `student_id`),
  CONSTRAINT `fk_ar_session` FOREIGN KEY (`session_id`) REFERENCES `attendance_sessions`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ar_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ar_corrector` FOREIGN KEY (`corrected_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 10. ASSIGNMENTS
-- ============================================================
CREATE TABLE `assignments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
  `staff_id` INT UNSIGNED NOT NULL,
  `department_id` INT UNSIGNED NOT NULL,
  `semester` TINYINT UNSIGNED NOT NULL,
  `section` VARCHAR(5) DEFAULT NULL,
  `max_marks` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `file_name` VARCHAR(255) DEFAULT NULL,
  `assigned_date` DATE NOT NULL,
  `due_date` DATETIME NOT NULL,
  `submission_deadline` DATETIME DEFAULT NULL,
  `status` ENUM('active','closed','draft') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_asgn_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_asgn_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_asgn_dept` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 11. ASSIGNMENT SUBMISSIONS
-- ============================================================
CREATE TABLE `assignment_submissions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `assignment_id` INT UNSIGNED NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_late` TINYINT(1) NOT NULL DEFAULT 0,
  `marks` DECIMAL(5,2) DEFAULT NULL,
  `feedback` TEXT DEFAULT NULL,
  `graded_by` INT UNSIGNED DEFAULT NULL,
  `graded_at` DATETIME DEFAULT NULL,
  `status` ENUM('submitted','graded','resubmit','rejected') NOT NULL DEFAULT 'submitted',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_assign_student` (`assignment_id`, `student_id`),
  CONSTRAINT `fk_sub_assign` FOREIGN KEY (`assignment_id`) REFERENCES `assignments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sub_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sub_grader` FOREIGN KEY (`graded_by`) REFERENCES `staff`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 12. INTERNAL MARKS
-- ============================================================
CREATE TABLE `internal_marks` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT UNSIGNED NOT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
  `exam_type` ENUM('midterm1','midterm2','model','quiz','other') NOT NULL,
  `max_marks` DECIMAL(5,2) NOT NULL DEFAULT 50.00,
  `obtained_marks` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `marks_obtained` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `exam_date` DATE DEFAULT NULL,
  `staff_id` INT UNSIGNED DEFAULT NULL,
  `academic_year` VARCHAR(20) NOT NULL,
  `semester` TINYINT UNSIGNED NOT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_marks` (`student_id`, `subject_id`, `exam_type`, `academic_year`),
  CONSTRAINT `fk_im_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_im_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_im_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 13. ASSIGNMENT MARKS
-- ============================================================
CREATE TABLE `assignment_marks` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT UNSIGNED NOT NULL,
  `assignment_id` INT UNSIGNED NOT NULL,
  `marks` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `max_marks` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
  `staff_id` INT UNSIGNED DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_asgn_marks` (`student_id`, `assignment_id`),
  CONSTRAINT `fk_am_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_am_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_am_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 14. STUDY MATERIALS
-- ============================================================
CREATE TABLE `study_materials` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
  `staff_id` INT UNSIGNED DEFAULT NULL,
  `department_id` INT UNSIGNED NOT NULL,
  `semester` TINYINT UNSIGNED NOT NULL,
  `unit` VARCHAR(20) DEFAULT NULL,
  `category` ENUM('notes','ppt','pdf','video','other') NOT NULL DEFAULT 'pdf',
  `file_path` VARCHAR(255) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_size` INT UNSIGNED DEFAULT 0,
  `file_type` VARCHAR(20) DEFAULT NULL,
  `uploaded_by` INT UNSIGNED NOT NULL,
  `academic_year` VARCHAR(20) NOT NULL,
  `download_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('active','archived','draft') NOT NULL DEFAULT 'active',
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_sm_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sm_dept` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sm_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `staff`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 15. QUESTION PAPERS
-- ============================================================
CREATE TABLE `question_papers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
  `staff_id` INT UNSIGNED DEFAULT NULL,
  `department_id` INT UNSIGNED NOT NULL,
  `semester` TINYINT UNSIGNED NOT NULL,
  `exam_type` ENUM('midterm1','midterm2','semester','model','supplementary') NOT NULL,
  `academic_year` VARCHAR(20) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_size` INT UNSIGNED DEFAULT 0,
  `uploaded_by` INT UNSIGNED NOT NULL,
  `download_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('active','archived') NOT NULL DEFAULT 'active',
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_qp_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qp_dept` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qp_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `staff`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 16. PRESENTATIONS (uploaded files)
-- ============================================================
CREATE TABLE `presentations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
  `staff_id` INT UNSIGNED NOT NULL,
  `department_id` INT UNSIGNED NOT NULL,
  `semester` TINYINT UNSIGNED NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_type` ENUM('pdf','ppt','pptx') NOT NULL DEFAULT 'pdf',
  `file_size` INT UNSIGNED DEFAULT 0,
  `total_pages` INT UNSIGNED DEFAULT 0,
  `status` ENUM('active','archived') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pres_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pres_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pres_dept` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 17. PRESENTATION SESSIONS (live sessions)
-- ============================================================
CREATE TABLE `presentation_sessions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `presentation_id` INT UNSIGNED NOT NULL,
  `staff_id` INT UNSIGNED NOT NULL,
  `session_code` VARCHAR(20) NOT NULL UNIQUE,
  `qr_code` VARCHAR(255) DEFAULT NULL,
  `pin_code` VARCHAR(10) DEFAULT NULL,
  `current_page` INT UNSIGNED NOT NULL DEFAULT 1,
  `status` ENUM('active','paused','ended') NOT NULL DEFAULT 'active',
  `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ended_at` DATETIME DEFAULT NULL,
  `max_viewers` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ps_pres` FOREIGN KEY (`presentation_id`) REFERENCES `presentations`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ps_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 18. PRESENTATION PARTICIPANTS
-- ============================================================
CREATE TABLE `presentation_participants` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT UNSIGNED NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `left_at` DATETIME DEFAULT NULL,
  `last_heartbeat` DATETIME DEFAULT NULL,
  `connection_status` ENUM('connected','disconnected','idle') NOT NULL DEFAULT 'connected',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_session_participant` (`session_id`, `student_id`),
  CONSTRAINT `fk_pp_session` FOREIGN KEY (`session_id`) REFERENCES `presentation_sessions`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pp_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 19. CLASSROOM BOOKINGS
-- ============================================================
CREATE TABLE `classroom_bookings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `classroom_id` INT UNSIGNED NOT NULL,
  `staff_id` INT UNSIGNED NOT NULL,
  `booking_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `purpose` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `approved_by` INT UNSIGNED DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_cb_classroom` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cb_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cb_approver` FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 20. NOTIFICATIONS
-- ============================================================
CREATE TABLE `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info','success','warning','danger','assignment','attendance','presentation','booking','material') NOT NULL DEFAULT 'info',
  `link` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notif_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 21. AUDIT LOGS
-- ============================================================
CREATE TABLE `audit_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `table_name` VARCHAR(80) DEFAULT NULL,
  `record_id` INT UNSIGNED DEFAULT NULL,
  `old_values` JSON DEFAULT NULL,
  `new_values` JSON DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;
