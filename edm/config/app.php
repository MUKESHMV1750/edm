<?php
/**
 * Application Configuration
 * Engineering Department Management System
 */

// ── App Identity ──────────────────────────────────────
define('APP_NAME', 'Engineering Department Management System');
define('APP_SHORT_NAME', 'EDMS');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/edm');

// ── User Roles ───────────────────────────────────────
if (!defined('ROLE_STUDENT')) define('ROLE_STUDENT', 'student');
if (!defined('ROLE_STAFF'))   define('ROLE_STAFF', 'staff');
if (!defined('ROLE_ADMIN'))   define('ROLE_ADMIN', 'admin');

// ── Paths ─────────────────────────────────────────────
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('VENDOR_PATH', BASE_PATH . '/vendor');

// ── Upload Directories ───────────────────────────────
define('UPLOAD_MATERIALS', UPLOAD_PATH . '/study_materials');
define('UPLOAD_QUESTIONS', UPLOAD_PATH . '/question_papers');
define('UPLOAD_ASSIGNMENTS', UPLOAD_PATH . '/assignments');
define('UPLOAD_SUBMISSIONS', UPLOAD_PATH . '/submissions');
define('UPLOAD_PRESENTATIONS', UPLOAD_PATH . '/presentations');

// ── File Upload Limits ───────────────────────────────
define('MAX_UPLOAD_SIZE', 20 * 1024 * 1024); // 20 MB
define('ALLOWED_DOC_TYPES', ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt']);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_SUBMISSION_TYPES', ['pdf', 'doc', 'docx', 'zip', 'rar', 'txt', 'c', 'cpp', 'java', 'py']);

// ── Session Settings ─────────────────────────────────
define('SESSION_LIFETIME', 3600);       // 1 hour
define('SESSION_NAME', 'EDMS_SESSION');

// ── Pagination ───────────────────────────────────────
define('ITEMS_PER_PAGE', 15);
define('MAX_PAGINATION_LINKS', 5);

// ── Attendance Settings ──────────────────────────────
define('QR_EXPIRY_MINUTES', 5);
define('PIN_LENGTH', 4);
define('GPS_RADIUS_METERS', 100);

// ── Presentation Settings ────────────────────────────
define('HEARTBEAT_INTERVAL', 10);       // seconds
define('HEARTBEAT_TIMEOUT', 30);        // seconds – mark as disconnected
define('SYNC_POLL_INTERVAL', 3);        // seconds

// ── Academic Settings ────────────────────────────────
define('MAX_SEMESTERS', 8);
define('CURRENT_ACADEMIC_YEAR', '2024-2025');
define('MIN_ATTENDANCE_PERCENT', 75);

// ── Timezone ─────────────────────────────────────────
date_default_timezone_set('Asia/Kolkata');

// ── Error Reporting (development) ────────────────────
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ── Start Session ────────────────────────────────────
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_name(SESSION_NAME);
    session_start();
}
