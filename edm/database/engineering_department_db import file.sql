-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 15, 2026 at 10:57 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `engineering_department_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `staff_id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `semester` tinyint(3) UNSIGNED NOT NULL,
  `section` varchar(20) DEFAULT 'Batch A',
  `max_marks` decimal(5,2) NOT NULL DEFAULT 100.00,
  `file_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `assigned_date` date NOT NULL,
  `due_date` datetime NOT NULL,
  `submission_deadline` datetime DEFAULT NULL,
  `status` enum('active','closed','draft') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignment_marks`
--

CREATE TABLE `assignment_marks` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `assignment_id` int(10) UNSIGNED NOT NULL,
  `marks` decimal(5,2) NOT NULL DEFAULT 0.00,
  `max_marks` decimal(5,2) NOT NULL DEFAULT 100.00,
  `staff_id` int(10) UNSIGNED DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignment_submissions`
--

CREATE TABLE `assignment_submissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `assignment_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_late` tinyint(1) NOT NULL DEFAULT 0,
  `marks` decimal(5,2) DEFAULT NULL,
  `marks_obtained` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `graded_by` int(10) UNSIGNED DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL,
  `status` enum('submitted','graded','resubmit','rejected') NOT NULL DEFAULT 'submitted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_records`
--

CREATE TABLE `attendance_records` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL DEFAULT 'absent',
  `marked_at` datetime DEFAULT NULL,
  `marked_via` enum('qr','pin','gps','manual') DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `distance_meters` int(10) UNSIGNED DEFAULT NULL,
  `corrected` tinyint(1) NOT NULL DEFAULT 0,
  `corrected_by` int(10) UNSIGNED DEFAULT NULL,
  `correction_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_records`
--

INSERT INTO `attendance_records` (`id`, `session_id`, `student_id`, `status`, `marked_at`, `marked_via`, `ip_address`, `latitude`, `longitude`, `distance_meters`, `corrected`, `corrected_by`, `correction_reason`, `created_at`, `updated_at`) VALUES
(11, 10, 1, 'present', '2026-10-13 16:34:39', 'pin', NULL, 11.3913930, 79.7049764, 0, 0, NULL, NULL, '2026-10-13 11:04:39', '2026-10-13 11:04:39'),
(12, 10, 12, 'present', '2026-10-13 16:38:54', 'pin', NULL, 11.3913930, 79.7049764, 0, 0, NULL, NULL, '2026-10-13 11:08:54', '2026-10-13 11:08:54'),
(13, 11, 2, 'absent', '2026-10-15 14:21:46', 'manual', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-10-15 08:51:42', '2026-10-15 08:51:46'),
(15, 11, 1, 'present', '2026-10-15 14:23:09', 'gps', NULL, 11.3913930, 79.7049764, 78, 0, NULL, NULL, '2026-10-15 08:53:09', '2026-10-15 08:53:09');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_sessions`
--

CREATE TABLE `attendance_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `staff_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `semester` tinyint(3) UNSIGNED NOT NULL,
  `section` varchar(20) DEFAULT 'Batch A',
  `classroom_id` int(10) UNSIGNED DEFAULT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `pin_code` varchar(10) DEFAULT NULL,
  `allowed_latitude` decimal(10,7) DEFAULT NULL,
  `allowed_longitude` decimal(10,7) DEFAULT NULL,
  `location_radius` int(10) UNSIGNED DEFAULT 100,
  `status` enum('active','completed','cancelled') NOT NULL DEFAULT 'active',
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_sessions`
--

INSERT INTO `attendance_sessions` (`id`, `staff_id`, `subject_id`, `department_id`, `semester`, `section`, `classroom_id`, `session_date`, `start_time`, `end_time`, `qr_code`, `pin_code`, `allowed_latitude`, `allowed_longitude`, `location_radius`, `status`, `expires_at`, `created_at`, `updated_at`) VALUES
(10, 1, 4, 1, 8, 'Batch A', 7, '2026-10-13', '16:34:14', '16:44:14', 'SES-CD8D8BA8', '7748', 11.3913930, 79.7049764, 500, 'completed', '2026-10-13 16:44:14', '2026-10-13 11:04:14', '2026-10-15 08:44:22'),
(11, 1, 4, 1, 6, 'Batch B', 12, '2026-10-15', '14:15:18', '15:15:18', 'SES-A86A66BE', '4343', 11.3908867, 79.7044868, 500, 'active', '2026-10-15 15:15:18', '2026-10-15 08:45:18', '2026-10-15 08:45:18');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(80) DEFAULT NULL,
  `record_id` int(10) UNSIGNED DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `description`, `created_at`) VALUES
(90, 1, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-12 11:21:09'),
(91, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as staff', '2026-10-12 11:21:30'),
(92, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-12 11:25:17'),
(93, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as staff', '2026-10-12 11:25:45'),
(94, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as admin', '2026-10-12 11:25:48'),
(95, 1, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-12 11:26:00'),
(96, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as staff', '2026-10-12 11:29:01'),
(97, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-12 11:38:42'),
(98, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as admin', '2026-10-12 11:38:46'),
(99, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as admin', '2026-10-12 11:40:02'),
(100, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as admin', '2026-10-12 11:40:08'),
(101, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as admin', '2026-10-12 11:40:26'),
(102, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as admin', '2026-10-12 11:51:14'),
(103, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as staff', '2026-10-13 04:34:51'),
(104, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-13 04:35:39'),
(105, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as admin', '2026-10-13 04:35:42'),
(106, 1, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-13 04:57:12'),
(107, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as staff', '2026-10-13 04:57:19'),
(108, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-13 05:20:08'),
(109, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as staff', '2026-10-13 05:23:12'),
(110, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as staff', '2026-10-13 09:20:03'),
(111, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-13 09:23:06'),
(112, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as staff', '2026-10-13 09:24:13'),
(113, 3, 'LOGIN', NULL, NULL, NULL, NULL, '127.0.0.1', NULL, 'User logged in successfully as student', '2026-10-13 09:29:36'),
(114, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-13 09:54:46'),
(115, 3, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as student', '2026-10-13 09:54:51'),
(116, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-13 09:55:02'),
(117, 3, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as student', '2026-10-13 10:26:46'),
(118, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-13 10:30:02'),
(119, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as staff', '2026-10-13 10:30:04'),
(120, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-13 10:31:14'),
(121, 3, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as student', '2026-10-13 10:31:16'),
(122, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-13 10:33:58'),
(123, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as staff', '2026-10-13 10:34:00'),
(124, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-13 11:00:11'),
(125, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as staff', '2026-10-13 11:00:15'),
(126, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-13 11:01:11'),
(127, 3, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as student', '2026-10-13 11:01:14'),
(128, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-13 11:01:47'),
(129, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as staff', '2026-10-13 11:01:51'),
(130, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-13 11:04:24'),
(131, 3, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as student', '2026-10-13 11:04:27'),
(132, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-13 11:04:48'),
(133, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as staff', '2026-10-13 11:04:52'),
(134, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-13 11:05:10'),
(135, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as admin', '2026-10-13 11:05:27'),
(136, 1, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-13 11:08:18'),
(137, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as staff', '2026-10-13 11:08:24'),
(138, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-13 11:08:30'),
(139, 15, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as student', '2026-10-13 11:08:41'),
(140, 15, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-13 11:09:33'),
(141, 15, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as student', '2026-10-13 11:11:04'),
(142, 15, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-13 11:11:20'),
(143, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as staff', '2026-10-13 11:11:25'),
(144, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-13 11:12:13'),
(145, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as admin', '2026-10-13 11:12:17'),
(146, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as staff', '2026-10-15 08:44:00'),
(147, 2, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-15 08:47:47'),
(148, 1, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as admin', '2026-10-15 08:47:49'),
(149, 1, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-15 08:50:34'),
(150, 3, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as student', '2026-10-15 08:50:37'),
(151, 3, 'LOGOUT', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged out', '2026-10-15 08:50:54'),
(152, 2, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as staff', '2026-10-15 08:50:57'),
(153, 3, 'LOGIN', NULL, NULL, NULL, NULL, '::1', NULL, 'User logged in successfully as student', '2026-10-15 08:52:37');

-- --------------------------------------------------------

--
-- Table structure for table `classrooms`
--

CREATE TABLE `classrooms` (
  `id` int(10) UNSIGNED NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `building` varchar(80) DEFAULT NULL,
  `building_name` varchar(80) DEFAULT NULL,
  `floor` varchar(20) DEFAULT NULL,
  `capacity` smallint(5) UNSIGNED NOT NULL DEFAULT 60,
  `type` enum('lecture','lab','seminar','smart') NOT NULL DEFAULT 'lecture',
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `has_projector` tinyint(1) NOT NULL DEFAULT 0,
  `has_ac` tinyint(1) NOT NULL DEFAULT 0,
  `has_smart_board` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('available','maintenance','reserved') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `classrooms`
--

INSERT INTO `classrooms` (`id`, `room_number`, `building`, `building_name`, `floor`, `capacity`, `type`, `latitude`, `longitude`, `has_projector`, `has_ac`, `has_smart_board`, `status`, `created_at`, `updated_at`) VALUES
(1, 'LH-301', 'Turing Tech Block', 'IT bilding', '3rd Floor', 65, 'lecture', 13.0827000, 80.2707000, 0, 0, 1, 'available', '2026-10-10 09:30:07', '2026-10-13 09:26:50'),
(2, 'LAB-102', 'Lovelace Computing Labs', NULL, '1st Floor', 45, 'lab', NULL, NULL, 1, 1, 1, 'available', '2026-10-13 09:26:50', '2026-10-13 09:26:50'),
(3, 'SEM-401', 'Main Academic Complex', NULL, '4th Floor', 120, 'seminar', NULL, NULL, 1, 1, 1, 'available', '2026-10-13 09:26:50', '2026-10-13 09:26:50'),
(4, '2215', NULL, 'IT bilding', NULL, 60, 'lecture', 13.0827000, 80.2707000, 1, 0, 0, 'available', '2026-10-12 10:09:33', '2026-10-13 05:10:26'),
(7, '2213', NULL, NULL, NULL, 60, 'lecture', 13.0827000, 80.2707000, 0, 0, 0, 'available', '2026-10-12 11:34:30', '2026-10-13 05:10:26'),
(8, '2211', NULL, NULL, NULL, 60, 'lecture', 13.0827000, 80.2707000, 0, 0, 0, 'available', '2026-10-12 11:34:30', '2026-10-13 05:10:26'),
(10, '2316', NULL, NULL, NULL, 60, 'lecture', 13.0827000, 80.2707000, 0, 0, 0, 'available', '2026-10-13 04:47:18', '2026-10-13 05:10:26'),
(11, '2317', NULL, NULL, NULL, 60, 'lecture', 13.0827000, 80.2707000, 0, 0, 0, 'available', '2026-10-13 04:47:18', '2026-10-13 05:10:26'),
(12, '2214', NULL, NULL, NULL, 60, 'lecture', 13.0827000, 80.2707000, 0, 0, 0, 'available', '2026-10-13 04:47:18', '2026-10-13 05:10:26'),
(13, '2318', NULL, NULL, NULL, 60, 'lecture', 13.0827000, 80.2707000, 0, 0, 0, 'available', '2026-10-13 04:47:18', '2026-10-13 05:10:26');

-- --------------------------------------------------------

--
-- Table structure for table `classroom_bookings`
--

CREATE TABLE `classroom_bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `classroom_id` int(10) UNSIGNED NOT NULL,
  `staff_id` int(10) UNSIGNED NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `code` varchar(20) NOT NULL,
  `hod_name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `code`, `hod_name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Computer Science and Engineering', 'CSE', 'Dr. K. Raman', 'Department of Computer Science and Engineering', 'active', '2026-10-10 09:30:07', '2026-10-10 09:30:07'),
(2, 'Electronics and Communication Engineering', 'ECE', 'Dr. S. Priya', 'Department of Computer Science and Engineering', 'active', '2026-10-10 09:30:07', '2026-10-13 09:26:50');

-- --------------------------------------------------------

--
-- Table structure for table `internal_marks`
--

CREATE TABLE `internal_marks` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `exam_type` enum('midterm1','midterm2','model','quiz','other') NOT NULL,
  `max_marks` decimal(5,2) NOT NULL DEFAULT 50.00,
  `obtained_marks` decimal(5,2) NOT NULL DEFAULT 0.00,
  `marks_obtained` decimal(5,2) DEFAULT NULL,
  `exam_date` date DEFAULT NULL,
  `staff_id` int(10) UNSIGNED DEFAULT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` tinyint(3) UNSIGNED NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger','assignment','attendance','presentation','booking','material') NOT NULL DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `link`, `is_read`, `created_by`, `created_at`) VALUES
(1, 1, 'System Initialized', 'Engineering Department Management System is ready for operation.', 'info', NULL, 0, NULL, '2026-10-10 09:30:07'),
(2, 2, 'Welcome Prof. Sharma', 'You can create attendance sessions and broadcast live slide presentations.', 'success', NULL, 0, NULL, '2026-10-10 09:30:07'),
(4, 1, 'System Initialized', 'Engineering Department Management System is ready for operation.', 'info', NULL, 0, NULL, '2026-10-13 09:26:50'),
(5, 2, 'Welcome Prof. Sharma', 'You can create attendance sessions and broadcast live slide presentations.', 'success', NULL, 0, NULL, '2026-10-13 09:26:50'),
(6, 3, 'Welcome John', 'Your semester 6 dashboard and course attendance are synced.', 'success', NULL, 0, NULL, '2026-10-13 09:26:50');

-- --------------------------------------------------------

--
-- Table structure for table `presentations`
--

CREATE TABLE `presentations` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `staff_id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `semester` tinyint(3) UNSIGNED NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` enum('pdf','ppt','pptx') NOT NULL DEFAULT 'pdf',
  `file_size` int(10) UNSIGNED DEFAULT 0,
  `total_pages` int(10) UNSIGNED DEFAULT 0,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `presentation_participants`
--

CREATE TABLE `presentation_participants` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `joined_at` datetime NOT NULL DEFAULT current_timestamp(),
  `left_at` datetime DEFAULT NULL,
  `last_heartbeat` datetime DEFAULT NULL,
  `connection_status` enum('connected','disconnected','idle') NOT NULL DEFAULT 'connected',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `presentation_sessions`
--

CREATE TABLE `presentation_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `presentation_id` int(10) UNSIGNED NOT NULL,
  `staff_id` int(10) UNSIGNED NOT NULL,
  `session_code` varchar(20) NOT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `pin_code` varchar(10) DEFAULT NULL,
  `current_page` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `status` enum('active','paused','ended') NOT NULL DEFAULT 'active',
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ended_at` datetime DEFAULT NULL,
  `max_viewers` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question_papers`
--

CREATE TABLE `question_papers` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `staff_id` int(10) UNSIGNED DEFAULT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `semester` tinyint(3) UNSIGNED NOT NULL,
  `exam_type` enum('midterm1','midterm2','semester','model','supplementary') NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` int(10) UNSIGNED DEFAULT 0,
  `uploaded_by` int(10) UNSIGNED NOT NULL,
  `download_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `staff_code` varchar(30) NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `last_name` varchar(60) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `designation` varchar(80) DEFAULT NULL,
  `qualification` varchar(150) DEFAULT NULL,
  `specialization` varchar(150) DEFAULT NULL,
  `join_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `user_id`, `staff_code`, `first_name`, `last_name`, `date_of_birth`, `gender`, `phone`, `address`, `department_id`, `designation`, `qualification`, `specialization`, `join_date`, `created_at`, `updated_at`) VALUES
(1, 2, 'STF001', 'Rajesh', 'Sharma', NULL, NULL, NULL, NULL, 1, 'Associate Professor', 'M.Tech, Ph.D', 'Cloud Computing & Distributed Systems', '2020-06-15', '2026-10-10 09:30:07', '2026-10-10 09:30:07');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `register_number` varchar(30) NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `last_name` varchar(60) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `semester` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `section` varchar(20) DEFAULT 'Batch A',
  `academic_year` varchar(20) NOT NULL,
  `admission_date` date DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `register_number`, `first_name`, `last_name`, `date_of_birth`, `gender`, `phone`, `address`, `department_id`, `semester`, `section`, `academic_year`, `admission_date`, `guardian_name`, `guardian_phone`, `created_at`, `updated_at`) VALUES
(1, 3, 'REG2024001', 'John', 'Doe', NULL, NULL, NULL, NULL, 1, 6, 'A', '2024-2025', '2021-08-01', NULL, NULL, '2026-10-13 09:26:50', '2026-10-13 09:26:50'),
(2, 4, '2436090001', 'John', 'Doe', NULL, NULL, '', NULL, 1, 6, 'A', '2024-2025', '2021-08-01', NULL, NULL, '2026-10-12 09:41:42', '2026-10-12 09:41:42'),
(3, 5, '2436090002', 'John', 'Doe', NULL, NULL, '', NULL, 1, 7, 'A', '2024-2026', '2021-08-02', NULL, NULL, '2026-10-12 09:41:42', '2026-10-12 09:41:42'),
(4, 6, '2436090003', 'John', 'Doe', NULL, NULL, '', NULL, 1, 8, 'A', '2024-2027', '2021-08-03', NULL, NULL, '2026-10-12 09:41:42', '2026-10-12 09:41:42'),
(5, 7, '2436090004', 'John', 'Doe', NULL, NULL, '', NULL, 1, 9, 'A', '2024-2028', '2021-08-04', NULL, NULL, '2026-10-12 09:41:42', '2026-10-12 09:41:42'),
(6, 8, '2436090005', 'John', 'Doe', NULL, NULL, '', NULL, 1, 10, 'A', '2024-2029', '2021-08-05', NULL, NULL, '2026-10-12 09:41:42', '2026-10-12 09:41:42'),
(7, 9, '2436090006', 'John', 'Doe', NULL, NULL, '', NULL, 1, 11, 'A', '2024-2030', '2021-08-06', NULL, NULL, '2026-10-12 09:41:42', '2026-10-12 09:41:42'),
(8, 10, '2436090007', 'John', 'Doe', NULL, NULL, '', NULL, 1, 12, 'A', '2024-2031', '2021-08-07', NULL, NULL, '2026-10-12 09:41:42', '2026-10-12 09:41:42'),
(9, 11, '2436090008', 'John', 'Doe', NULL, NULL, '', NULL, 1, 13, 'A', '2024-2032', '2021-08-08', NULL, NULL, '2026-10-12 09:41:42', '2026-10-12 09:41:42'),
(10, 12, '2436090009', 'John', 'Doe', NULL, NULL, '', NULL, 1, 14, 'A', '2024-2033', '2021-08-09', NULL, NULL, '2026-10-12 09:41:42', '2026-10-12 09:41:42'),
(11, 13, '2436090010', 'John', 'Doe', NULL, NULL, '', NULL, 1, 15, 'A', '2024-2034', '2021-08-10', NULL, NULL, '2026-10-12 09:41:42', '2026-10-12 09:41:42'),
(12, 15, '1234567890', 'mukesh', 'm', NULL, NULL, '09790112839', NULL, 1, 8, 'Batch B', '2024-2025', '2026-10-13', NULL, NULL, '2026-10-13 11:07:43', '2026-10-13 11:07:43');

-- --------------------------------------------------------

--
-- Table structure for table `study_materials`
--

CREATE TABLE `study_materials` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `staff_id` int(10) UNSIGNED DEFAULT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `semester` tinyint(3) UNSIGNED NOT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `category` enum('notes','ppt','pdf','video','other') NOT NULL DEFAULT 'pdf',
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` int(10) UNSIGNED DEFAULT 0,
  `file_type` varchar(20) DEFAULT NULL,
  `uploaded_by` int(10) UNSIGNED NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `download_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('active','archived','draft') NOT NULL DEFAULT 'active',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `semester` tinyint(3) UNSIGNED NOT NULL,
  `credits` tinyint(3) UNSIGNED DEFAULT 3,
  `type` enum('theory','lab','elective') NOT NULL DEFAULT 'theory',
  `staff_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `code`, `name`, `department_id`, `semester`, `credits`, `type`, `staff_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'CS601', 'Distributed Cloud Architecture', 1, 6, 4, 'theory', 1, 'active', '2026-10-10 09:30:07', '2026-10-10 09:30:07'),
(2, 'CS602', 'Machine Learning & Neural Networks', 1, 6, 4, 'theory', 1, 'active', '2026-10-10 09:30:07', '2026-10-10 09:30:07'),
(3, 'CS603', 'Advanced Web Engineering Lab', 1, 6, 2, 'lab', 1, 'active', '2026-10-10 09:30:07', '2026-10-10 09:30:07'),
(4, 'A', 'Advanced Algorithms', 1, 6, 3, 'theory', 1, 'active', '2026-10-12 11:34:30', '2026-10-12 11:34:30'),
(5, 'B', 'Cloud Computing', 1, 6, 3, 'theory', 1, 'active', '2026-10-12 11:34:30', '2026-10-12 11:34:30'),
(6, 'C', 'Computer Networks', 1, 6, 3, 'theory', 1, 'active', '2026-10-12 11:34:30', '2026-10-12 11:34:30'),
(7, 'D', 'Database Systems', 1, 6, 3, 'theory', 1, 'active', '2026-10-12 11:34:30', '2026-10-12 11:34:30'),
(8, 'E', 'Software Engineering', 1, 6, 3, 'theory', 1, 'active', '2026-10-12 11:34:30', '2026-10-12 11:34:30'),
(9, 'G', 'General / Lab Practical', 1, 6, 3, 'theory', 1, 'active', '2026-10-12 11:34:30', '2026-10-12 11:34:30'),
(10, 'P&T', 'Placement & Training', 1, 6, 3, 'theory', 1, 'active', '2026-10-12 11:34:30', '2026-10-12 11:34:30');

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `semester` tinyint(3) UNSIGNED NOT NULL,
  `section` varchar(20) DEFAULT 'Batch A',
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `period_number` tinyint(3) UNSIGNED NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `staff_id` int(10) UNSIGNED DEFAULT NULL,
  `classroom_id` int(10) UNSIGNED DEFAULT NULL,
  `academic_year` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `timetable`
--

INSERT INTO `timetable` (`id`, `department_id`, `semester`, `section`, `day_of_week`, `period_number`, `start_time`, `end_time`, `subject_id`, `staff_id`, `classroom_id`, `academic_year`, `created_at`, `updated_at`) VALUES
(170, 1, 6, 'A', 'Monday', 2, '09:30:00', '10:30:00', 4, 1, 10, '2024-2025', '2026-10-15 08:47:12', '2026-10-15 08:47:12'),
(171, 1, 6, 'A', 'Monday', 3, '10:40:00', '11:40:00', 6, 1, 4, '2024-2025', '2026-10-15 08:47:12', '2026-10-15 08:47:12'),
(172, 1, 6, 'A', 'Monday', 4, '11:40:00', '12:40:00', 5, 1, 4, '2024-2025', '2026-10-15 08:47:12', '2026-10-15 08:47:12'),
(173, 1, 6, 'A', 'Monday', 5, '13:30:00', '14:30:00', 9, 1, NULL, '2024-2025', '2026-10-15 08:47:12', '2026-10-15 08:47:12'),
(174, 1, 6, 'A', 'Monday', 6, '14:30:00', '15:30:00', 9, 1, NULL, '2024-2025', '2026-10-15 08:47:12', '2026-10-15 08:47:12'),
(175, 1, 6, 'A', 'Monday', 7, '15:30:00', '16:30:00', 9, 1, NULL, '2024-2025', '2026-10-15 08:47:12', '2026-10-15 08:47:12'),
(176, 1, 6, 'A', 'Tuesday', 3, '10:40:00', '11:40:00', 5, 1, 11, '2024-2025', '2026-10-15 08:47:12', '2026-10-15 08:47:12'),
(177, 1, 6, 'A', 'Tuesday', 4, '11:40:00', '12:40:00', 6, 1, 7, '2024-2025', '2026-10-15 08:47:12', '2026-10-15 08:47:12'),
(178, 1, 6, 'A', 'Tuesday', 5, '13:30:00', '14:30:00', 4, 1, 8, '2024-2025', '2026-10-15 08:47:12', '2026-10-15 08:47:12'),
(179, 1, 6, 'A', 'Tuesday', 6, '14:30:00', '15:30:00', 7, 1, 8, '2024-2025', '2026-10-15 08:47:12', '2026-10-15 08:47:12'),
(180, 1, 6, 'A', 'Wednesday', 2, '09:30:00', '10:30:00', 6, 1, 8, '2024-2025', '2026-10-15 08:47:12', '2026-10-15 08:47:12'),
(181, 1, 6, 'A', 'Wednesday', 3, '10:40:00', '11:40:00', 8, 1, 12, '2024-2025', '2026-10-15 08:47:12', '2026-10-15 08:47:12'),
(182, 1, 6, 'A', 'Wednesday', 4, '11:40:00', '12:40:00', 5, 1, 12, '2024-2025', '2026-10-15 08:47:12', '2026-10-15 08:47:12'),
(183, 1, 6, 'A', 'Thursday', 2, '09:30:00', '10:30:00', 8, 1, 7, '2024-2025', '2026-10-15 08:47:12', '2026-10-15 08:47:12'),
(184, 1, 6, 'A', 'Thursday', 3, '10:40:00', '11:40:00', 7, 1, 7, '2024-2025', '2026-10-15 08:47:12', '2026-10-15 08:47:12'),
(185, 1, 6, 'A', 'Thursday', 4, '11:40:00', '12:40:00', 4, 1, 7, '2024-2025', '2026-10-15 08:47:12', '2026-10-15 08:47:12'),
(186, 1, 6, 'A', 'Thursday', 5, '13:30:00', '14:30:00', 10, 1, NULL, '2024-2025', '2026-10-15 08:47:12', '2026-10-15 08:47:12'),
(187, 1, 6, 'A', 'Thursday', 6, '14:30:00', '15:30:00', 10, 1, NULL, '2024-2025', '2026-10-15 08:47:12', '2026-10-15 08:47:12'),
(188, 1, 6, 'A', 'Thursday', 7, '15:30:00', '16:30:00', 10, 1, NULL, '2024-2025', '2026-10-15 08:47:12', '2026-10-15 08:47:12'),
(189, 1, 6, 'A', 'Friday', 3, '10:40:00', '11:40:00', 7, 1, 13, '2024-2025', '2026-10-15 08:47:12', '2026-10-15 08:47:12'),
(190, 1, 6, 'A', 'Friday', 4, '11:40:00', '12:40:00', 8, 1, 13, '2024-2025', '2026-10-15 08:47:12', '2026-10-15 08:47:12');

-- --------------------------------------------------------

--
-- Stand-in structure for view `timetables`
-- (See below for the actual view)
--
CREATE TABLE `timetables` (
`id` int(10) unsigned
,`department_id` int(10) unsigned
,`semester` tinyint(3) unsigned
,`section` varchar(20)
,`day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday')
,`period_number` tinyint(3) unsigned
,`start_time` time
,`end_time` time
,`subject_id` int(10) unsigned
,`staff_id` int(10) unsigned
,`classroom_id` int(10) unsigned
,`academic_year` varchar(20)
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','staff','admin') NOT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `profile_image` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `status`, `profile_image`, `last_login`, `password_reset_token`, `password_reset_expires`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@edms.edu', '$2y$10$oB4NNX5jTQjs8WA0cKfg/OIh3I7Nc2Vdwq3mkjp0PAP.8zWCjTH4G', 'admin', 'active', NULL, '2026-10-15 14:17:49', NULL, NULL, '2026-10-10 09:30:07', '2026-10-15 08:47:49'),
(2, 'rajesh.s', 'rajesh.s@edms.edu', '$2y$10$YHHSwMUj5xhwif1r0f/vIew.O.GN3vYFGyemX0Mn0V5MndZSCzHKi', 'staff', 'active', NULL, '2026-10-15 14:20:57', NULL, NULL, '2026-10-10 09:30:07', '2026-10-15 08:50:57'),
(3, 'john.d', 'john.d@edms.edu', '$2y$10$jRIy2ibYmtRz81c4jBGXyuyD6FPh0WqiUcHfLHherKbzqaVVQpNmG', 'student', 'active', NULL, '2026-10-15 14:22:36', NULL, NULL, '2026-10-13 09:26:50', '2026-10-15 08:52:36'),
(4, '2436090001', '2436090001@edms.edu', '$2y$10$NH4ue2xqosLy.b8EbmRvI.8iqKoINqjIKFDdcVSwnWJvtKKcrlRg6', 'student', 'active', NULL, NULL, NULL, NULL, '2026-10-12 09:41:42', '2026-10-12 09:41:42'),
(5, '2436090002', '2436090002@edms.edu', '$2y$10$Rb2taC/4GtytbnXpaYrSuuTX825QkKUzYmOjNffBeKSivDs6jyURS', 'student', 'active', NULL, NULL, NULL, NULL, '2026-10-12 09:41:42', '2026-10-12 09:41:42'),
(6, '2436090003', '2436090003@edms.edu', '$2y$10$G2ZW5PlzEBuz4.PARCB9QOryoLqqsUF1e.OkxCFPOxUr4zFG3/5nq', 'student', 'active', NULL, NULL, NULL, NULL, '2026-10-12 09:41:42', '2026-10-12 09:41:42'),
(7, '2436090004', '2436090004@edms.edu', '$2y$10$Yfys89oFUdJ3OVKHNoeJWOcWCUaBhTxs/LAACDjMMbA7gpW6cWyPq', 'student', 'active', NULL, NULL, NULL, NULL, '2026-10-12 09:41:42', '2026-10-12 09:41:42'),
(8, '2436090005', '2436090005@edms.edu', '$2y$10$7IR8iTnX/YhlNTtMRxO3wuq1ZNYq3y5Z.pwbFsKuXDmzu.a2zF91C', 'student', 'active', NULL, NULL, NULL, NULL, '2026-10-12 09:41:42', '2026-10-12 09:41:42'),
(9, '2436090006', '2436090006@edms.edu', '$2y$10$/HTFjnTeDJYJrhoKsd3aLukpcYS4Rv5G5UlkPCo3nGp1n.SFNt5d.', 'student', 'active', NULL, NULL, NULL, NULL, '2026-10-12 09:41:42', '2026-10-12 09:41:42'),
(10, '2436090007', '2436090007@edms.edu', '$2y$10$T1bzcGNfywoeun9Fp770zO.14DBWGQw.GprnFvNJTnHBMOZn2fOMu', 'student', 'active', NULL, NULL, NULL, NULL, '2026-10-12 09:41:42', '2026-10-12 09:41:42'),
(11, '2436090008', '2436090008@edms.edu', '$2y$10$cfTu6yjPmtGMXDkYAV.JKuyBrNLZ1C1WdkaQEXzmY4ccCGD/MatEi', 'student', 'active', NULL, NULL, NULL, NULL, '2026-10-12 09:41:42', '2026-10-12 09:41:42'),
(12, '2436090009', '2436090009@edms.edu', '$2y$10$0gng6FOx3liP1CLQApR7L./Gi9CViK/J/TVgvMsYCqcG3NTqJiemq', 'student', 'active', NULL, NULL, NULL, NULL, '2026-10-12 09:41:42', '2026-10-12 09:41:42'),
(13, '2436090010', '2436090010@edms.edu', '$2y$10$LQA2GAbMKZD89nvP8bar9.0kko/ULNogCa4FgEXkXvUAUMRKhwjwq', 'student', 'active', NULL, NULL, NULL, NULL, '2026-10-12 09:41:42', '2026-10-12 09:41:42'),
(15, 'mukesh', 'mukeshmv1750@gmail.com', '$2y$10$FwVKxMhyxeiBeDOKY91Z2uan5uJvYJiD3gLrdjAiEZsj5ILdLijxu', 'student', 'active', NULL, '2026-10-13 16:41:04', NULL, NULL, '2026-10-13 11:07:43', '2026-10-13 11:11:04');

-- --------------------------------------------------------

--
-- Structure for view `timetables`
--
DROP TABLE IF EXISTS `timetables`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `timetables`  AS SELECT `timetable`.`id` AS `id`, `timetable`.`department_id` AS `department_id`, `timetable`.`semester` AS `semester`, `timetable`.`section` AS `section`, `timetable`.`day_of_week` AS `day_of_week`, `timetable`.`period_number` AS `period_number`, `timetable`.`start_time` AS `start_time`, `timetable`.`end_time` AS `end_time`, `timetable`.`subject_id` AS `subject_id`, `timetable`.`staff_id` AS `staff_id`, `timetable`.`classroom_id` AS `classroom_id`, `timetable`.`academic_year` AS `academic_year`, `timetable`.`created_at` AS `created_at`, `timetable`.`updated_at` AS `updated_at` FROM `timetable` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_asgn_subject` (`subject_id`),
  ADD KEY `fk_asgn_staff` (`staff_id`),
  ADD KEY `fk_asgn_dept` (`department_id`);

--
-- Indexes for table `assignment_marks`
--
ALTER TABLE `assignment_marks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_asgn_marks` (`student_id`,`assignment_id`),
  ADD KEY `fk_am_assignment` (`assignment_id`),
  ADD KEY `fk_am_staff` (`staff_id`);

--
-- Indexes for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_assign_student` (`assignment_id`,`student_id`),
  ADD KEY `fk_sub_student` (`student_id`),
  ADD KEY `fk_sub_grader` (`graded_by`);

--
-- Indexes for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_session_student` (`session_id`,`student_id`),
  ADD KEY `fk_ar_student` (`student_id`),
  ADD KEY `fk_ar_corrector` (`corrected_by`);

--
-- Indexes for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_as_staff` (`staff_id`),
  ADD KEY `fk_as_subject` (`subject_id`),
  ADD KEY `fk_as_dept` (`department_id`),
  ADD KEY `fk_as_classroom` (`classroom_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_audit_user` (`user_id`);

--
-- Indexes for table `classrooms`
--
ALTER TABLE `classrooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`);

--
-- Indexes for table `classroom_bookings`
--
ALTER TABLE `classroom_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cb_classroom` (`classroom_id`),
  ADD KEY `fk_cb_staff` (`staff_id`),
  ADD KEY `fk_cb_approver` (`approved_by`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `internal_marks`
--
ALTER TABLE `internal_marks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_marks` (`student_id`,`subject_id`,`exam_type`,`academic_year`),
  ADD KEY `fk_im_subject` (`subject_id`),
  ADD KEY `fk_im_staff` (`staff_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notif_user` (`user_id`),
  ADD KEY `fk_notif_creator` (`created_by`);

--
-- Indexes for table `presentations`
--
ALTER TABLE `presentations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pres_subject` (`subject_id`),
  ADD KEY `fk_pres_staff` (`staff_id`),
  ADD KEY `fk_pres_dept` (`department_id`);

--
-- Indexes for table `presentation_participants`
--
ALTER TABLE `presentation_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_session_participant` (`session_id`,`student_id`),
  ADD KEY `fk_pp_student` (`student_id`);

--
-- Indexes for table `presentation_sessions`
--
ALTER TABLE `presentation_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_code` (`session_code`),
  ADD KEY `fk_ps_pres` (`presentation_id`),
  ADD KEY `fk_ps_staff` (`staff_id`);

--
-- Indexes for table `question_papers`
--
ALTER TABLE `question_papers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_qp_subject` (`subject_id`),
  ADD KEY `fk_qp_dept` (`department_id`),
  ADD KEY `fk_qp_uploader` (`uploaded_by`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `staff_code` (`staff_code`),
  ADD KEY `fk_staff_dept` (`department_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `register_number` (`register_number`),
  ADD KEY `fk_students_dept` (`department_id`);

--
-- Indexes for table `study_materials`
--
ALTER TABLE `study_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sm_subject` (`subject_id`),
  ADD KEY `fk_sm_dept` (`department_id`),
  ADD KEY `fk_sm_uploader` (`uploaded_by`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `fk_subjects_dept` (`department_id`),
  ADD KEY `fk_subjects_staff` (`staff_id`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tt_dept` (`department_id`),
  ADD KEY `fk_tt_subject` (`subject_id`),
  ADD KEY `fk_tt_staff` (`staff_id`),
  ADD KEY `fk_tt_classroom` (`classroom_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assignment_marks`
--
ALTER TABLE `assignment_marks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_records`
--
ALTER TABLE `attendance_records`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=154;

--
-- AUTO_INCREMENT for table `classrooms`
--
ALTER TABLE `classrooms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `classroom_bookings`
--
ALTER TABLE `classroom_bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `internal_marks`
--
ALTER TABLE `internal_marks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `presentations`
--
ALTER TABLE `presentations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `presentation_participants`
--
ALTER TABLE `presentation_participants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `presentation_sessions`
--
ALTER TABLE `presentation_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question_papers`
--
ALTER TABLE `question_papers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `study_materials`
--
ALTER TABLE `study_materials`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=191;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `fk_asgn_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_asgn_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_asgn_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assignment_marks`
--
ALTER TABLE `assignment_marks`
  ADD CONSTRAINT `fk_am_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_am_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_am_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assignment_submissions`
--
ALTER TABLE `assignment_submissions`
  ADD CONSTRAINT `fk_sub_assign` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sub_grader` FOREIGN KEY (`graded_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sub_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD CONSTRAINT `fk_ar_corrector` FOREIGN KEY (`corrected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ar_session` FOREIGN KEY (`session_id`) REFERENCES `attendance_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ar_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD CONSTRAINT `fk_as_classroom` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_as_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_as_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_as_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `classroom_bookings`
--
ALTER TABLE `classroom_bookings`
  ADD CONSTRAINT `fk_cb_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cb_classroom` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cb_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `internal_marks`
--
ALTER TABLE `internal_marks`
  ADD CONSTRAINT `fk_im_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_im_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_im_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `presentations`
--
ALTER TABLE `presentations`
  ADD CONSTRAINT `fk_pres_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pres_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pres_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `presentation_participants`
--
ALTER TABLE `presentation_participants`
  ADD CONSTRAINT `fk_pp_session` FOREIGN KEY (`session_id`) REFERENCES `presentation_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pp_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `presentation_sessions`
--
ALTER TABLE `presentation_sessions`
  ADD CONSTRAINT `fk_ps_pres` FOREIGN KEY (`presentation_id`) REFERENCES `presentations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ps_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `question_papers`
--
ALTER TABLE `question_papers`
  ADD CONSTRAINT `fk_qp_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_qp_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_qp_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `fk_staff_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `fk_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `study_materials`
--
ALTER TABLE `study_materials`
  ADD CONSTRAINT `fk_sm_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sm_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sm_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `fk_subjects_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `fk_subjects_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `timetable`
--
ALTER TABLE `timetable`
  ADD CONSTRAINT `fk_tt_classroom` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tt_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tt_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tt_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
