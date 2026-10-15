<?php
/**
 * Utility Functions & Helper Framework
 * Engineering Department Management System (EDM)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/permissions.php';

// ── User Role Constants ──────────────────────────────
if (!defined('ROLE_STUDENT')) define('ROLE_STUDENT', 'student');
if (!defined('ROLE_STAFF'))   define('ROLE_STAFF', 'staff');
if (!defined('ROLE_ADMIN'))   define('ROLE_ADMIN', 'admin');

// ══════════════════════════════════════════════════════
// CSRF Protection
// ══════════════════════════════════════════════════════

/**
 * Generate CSRF token and store in session
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden CSRF input field
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCSRFToken()) . '">';
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token = null) {
    $checkToken = $token ?? ($_POST['csrf_token'] ?? '');
    if (empty($checkToken) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $checkToken);
}

function verifyCSRFToken($token = null) {
    return validateCSRFToken($token);
}

// ══════════════════════════════════════════════════════
// Sanitization & Input Processing
// ══════════════════════════════════════════════════════

function sanitize($str) {
    return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}

function cleanInput($data) {
    return htmlspecialchars(trim(stripslashes($data ?? '')), ENT_QUOTES, 'UTF-8');
}

function sanitizeInput($data) {
    return cleanInput($data);
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// ══════════════════════════════════════════════════════
// Session & Authentication Helpers
// ══════════════════════════════════════════════════════

function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function getCurrentUserName() {
    return $_SESSION['user_name'] ?? ($_SESSION['username'] ?? 'User');
}

function getProfileId() {
    return $_SESSION['role_id'] ?? ($_SESSION['profile_id'] ?? null);
}

/**
 * Enforce role access control on dashboard pages
 *
 * @param array|string $allowedRoles Single role or array of allowed roles
 */
function checkRole($allowedRoles) {
    if (!isLoggedIn()) {
        setFlashMessage('danger', 'Please log in to continue.');
        redirect('auth/login.php');
    }
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    $currentRole = getCurrentUserRole();
    if (!in_array($currentRole, $allowedRoles)) {
        setFlashMessage('warning', 'Access restricted. Redirected to your authorized workspace.');
        switch ($currentRole) {
            case ROLE_STUDENT:
                redirect('student/dashboard.php');
                break;
            case ROLE_STAFF:
                redirect('staff/dashboard.php');
                break;
            case ROLE_ADMIN:
                redirect('admin/dashboard.php');
                break;
            default:
                redirect('index.php');
        }
    }
}

// ══════════════════════════════════════════════════════
// Flash Alerts System
// ══════════════════════════════════════════════════════

function setFlashMessage($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_' . $type] = $message;
}

function getFlashMessage($type) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

function renderFlashMessages() {
    $types = ['success', 'danger', 'warning', 'info', 'error'];
    $iconMap = [
        'success' => 'check-circle',
        'danger'  => 'exclamation-circle',
        'error'   => 'exclamation-circle',
        'warning' => 'exclamation-triangle',
        'info'    => 'info-circle',
    ];
    foreach ($types as $type) {
        $msg = getFlashMessage($type);
        if ($msg) {
            $cssClass = ($type === 'error') ? 'danger' : $type;
            $icon = $iconMap[$type] ?? 'info-circle';
            echo '<div class="alert alert-' . $cssClass . ' alert-dismissible fade show shadow-sm mb-4" role="alert" style="border-radius: 12px;">';
            echo '<i class="fas fa-' . $icon . ' me-2"></i>';
            echo '<span>' . $msg . '</span>';
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
        }
    }
}

function displayAlerts() {
    renderFlashMessages();
}

// ══════════════════════════════════════════════════════
// Navigation & Redirect Helpers
// ══════════════════════════════════════════════════════

function redirect($path) {
    $url = (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) 
        ? $path 
        : APP_URL . '/' . ltrim($path, '/');
    header('Location: ' . $url);
    exit;
}

// ══════════════════════════════════════════════════════
// Date & Time Helpers
// ══════════════════════════════════════════════════════

function formatDate($date, $format = 'd M Y') {
    return empty($date) ? '-' : date($format, strtotime($date));
}

function formatDateTime($datetime, $format = 'd M Y, h:i A') {
    return empty($datetime) ? '-' : date($format, strtotime($datetime));
}

function formatTime($time, $format = 'h:i A') {
    return empty($time) ? '-' : date($format, strtotime($time));
}

function timeAgo($datetime) {
    if (empty($datetime)) return '-';
    $now  = new DateTime();
    $ago  = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' min' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

// ══════════════════════════════════════════════════════
// Audit Logging
// ══════════════════════════════════════════════════════

function logAudit($param1, $param2 = null, $param3 = null, $param4 = null, $param5 = null, $param6 = null) {
    try {
        $pdo = getDBConnection();
        $userId = getCurrentUserId();
        $action = 'LOG';
        $details = '';

        if (is_numeric($param1)) {
            $userId = (int)$param1;
            $action = (string)$param2;
            $details = (string)$param3;
        } else {
            $action = (string)$param1;
            $details = is_string($param2) ? $param2 : (is_string($param3) ? $param3 : ($param6 ?? ''));
        }

        $stmt = $pdo->prepare(
            "INSERT INTO audit_logs (user_id, action, description, ip_address, created_at)
             VALUES (:uid, :action, :description, :ip, NOW())"
        );
        $stmt->execute([
            ':uid'         => $userId,
            ':action'      => substr($action, 0, 100),
            ':description' => $details,
            ':ip'          => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ]);
    } catch (Exception $e) {
        error_log("Audit Log Exception: " . $e->getMessage());
    }
}

/**
 * Calculate distance in meters between two GPS coordinates using Haversine formula
 */
function calculateDistanceMeters($lat1, $lon1, $lat2, $lon2) {
    if (empty($lat1) || empty($lon1) || empty($lat2) || empty($lon2)) {
        return null;
    }
    $earthRadius = 6371000; // in meters
    $dLat = deg2rad((float)$lat2 - (float)$lat1);
    $dLon = deg2rad((float)$lon2 - (float)$lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad((float)$lat1)) * cos(deg2rad((float)$lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return (int)round($earthRadius * $c);
}
