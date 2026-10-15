-- ============================================================
-- Engineering Department Management System (EDM)
-- Sample Seed Data
-- ============================================================

USE `engineering_department_db`;

-- 1. DEPARTMENTS
INSERT INTO `departments` (`id`, `name`, `code`, `hod_name`, `description`, `status`) VALUES
(1, 'Computer Science and Engineering', 'CSE', 'Dr. K. Raman', 'Department of Computer Science and Engineering', 'active'),
(2, 'Electronics and Communication Engineering', 'ECE', 'Dr. S. Priya', 'Department of Electronics and Communication Engineering', 'active')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 2. USERS (Pass: admin123, staff123, student123)
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `status`) VALUES
(1, 'admin', 'admin@edms.edu', '$2y$10$o23bNqX.zT.l1qDkL2u71u.rT6gB3tP1Y5l0rC3bQ4fG9w8e7r6t5', 'admin', 'active'),
(2, 'rajesh.s', 'rajesh.s@edms.edu', '$2y$10$o23bNqX.zT.l1qDkL2u71u.rT6gB3tP1Y5l0rC3bQ4fG9w8e7r6t5', 'staff', 'active'),
(3, 'john.d', 'john.d@edms.edu', '$2y$10$o23bNqX.zT.l1qDkL2u71u.rT6gB3tP1Y5l0rC3bQ4fG9w8e7r6t5', 'student', 'active')
ON DUPLICATE KEY UPDATE `status`='active';

-- 3. STAFF PROFILE
INSERT INTO `staff` (`id`, `user_id`, `staff_code`, `first_name`, `last_name`, `department_id`, `designation`, `qualification`, `specialization`, `join_date`) VALUES
(1, 2, 'STF001', 'Rajesh', 'Sharma', 1, 'Associate Professor', 'M.Tech, Ph.D', 'Cloud Computing & Distributed Systems', '2020-06-15')
ON DUPLICATE KEY UPDATE `staff_code`=VALUES(`staff_code`);

-- 4. STUDENT PROFILE
INSERT INTO `students` (`id`, `user_id`, `register_number`, `first_name`, `last_name`, `department_id`, `semester`, `section`, `academic_year`, `admission_date`) VALUES
(1, 3, 'REG2024001', 'John', 'Doe', 1, 6, 'A', '2024-2025', '2021-08-01')
ON DUPLICATE KEY UPDATE `register_number`=VALUES(`register_number`);

-- 5. SUBJECTS
INSERT INTO `subjects` (`id`, `code`, `name`, `department_id`, `semester`, `credits`, `type`, `staff_id`, `status`) VALUES
(1, 'CS601', 'Distributed Cloud Architecture', 1, 6, 4, 'theory', 1, 'active'),
(2, 'CS602', 'Machine Learning & Neural Networks', 1, 6, 4, 'theory', 1, 'active'),
(3, 'CS603', 'Advanced Web Engineering Lab', 1, 6, 2, 'lab', 1, 'active')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 6. CLASSROOMS
INSERT INTO `classrooms` (`id`, `room_number`, `building`, `floor`, `capacity`, `type`, `has_projector`, `has_ac`, `has_smart_board`, `status`) VALUES
(1, 'LH-301', 'Turing Tech Block', '3rd Floor', 65, 'smart', 1, 1, 1, 'available'),
(2, 'LAB-102', 'Lovelace Computing Labs', '1st Floor', 45, 'lab', 1, 1, 1, 'available'),
(3, 'SEM-401', 'Main Academic Complex', '4th Floor', 120, 'seminar', 1, 1, 1, 'available')
ON DUPLICATE KEY UPDATE `room_number`=VALUES(`room_number`);
