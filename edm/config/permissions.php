<?php
/**
 * Role-Based Permissions
 * Engineering Department Management System
 *
 * Defines which pages and actions each role can access.
 */

$permissions = [

    // ── Student Permissions ──────────────────────────
    'student' => [
        'dashboard'           => true,
        'profile'             => true,
        'attendance_mark'     => true,
        'attendance_view'     => true,
        'internal_marks_view' => true,
        'assignment_view'     => true,
        'assignment_submit'   => true,
        'assignment_marks_view' => true,
        'study_materials_view'=> true,
        'question_papers_view'=> true,
        'presentation_join'   => true,
        'timetable_view'      => true,
        'notifications_view'  => true,
        'report_personal'     => true,
    ],

    // ── Staff Permissions ────────────────────────────
    'staff' => [
        'dashboard'               => true,
        'profile'                 => true,
        'students_view'           => true,
        'attendance_create'       => true,
        'attendance_manage'       => true,
        'attendance_correct'      => true,
        'attendance_report'       => true,
        'assignment_create'       => true,
        'assignment_manage'       => true,
        'submission_review'       => true,
        'marks_entry'             => true,
        'marks_report'            => true,
        'materials_upload'        => true,
        'materials_manage'        => true,
        'questions_upload'        => true,
        'questions_manage'        => true,
        'presentation_create'     => true,
        'presentation_manage'     => true,
        'presentation_live'       => true,
        'classroom_book'          => true,
        'classroom_booking_view'  => true,
        'timetable_view'          => true,
        'notifications_view'      => true,
        'report_class'            => true,
    ],

    // ── Admin Permissions ────────────────────────────
    'admin' => [
        'dashboard'               => true,
        'profile'                 => true,
        'students_manage'         => true,
        'staff_manage'            => true,
        'departments_manage'      => true,
        'subjects_manage'         => true,
        'classrooms_manage'       => true,
        'timetable_manage'        => true,
        'bookings_approve'        => true,
        'attendance_monitor'      => true,
        'attendance_report'       => true,
        'marks_monitor'           => true,
        'marks_report'            => true,
        'assignments_monitor'     => true,
        'materials_manage'        => true,
        'presentations_monitor'   => true,
        'accounts_manage'         => true,
        'analytics_view'          => true,
        'audit_logs_view'         => true,
        'settings_manage'         => true,
        'notifications_manage'    => true,
        'report_department'       => true,
        'report_all'              => true,
    ],
];

/**
 * Check if a role has a specific permission
 *
 * @param string $role   User role (student, staff, admin)
 * @param string $perm   Permission key
 * @return bool
 */
function hasPermission($role, $perm) {
    global $permissions;
    return isset($permissions[$role][$perm]) && $permissions[$role][$perm] === true;
}

/**
 * Require a specific permission – redirect with error if denied
 *
 * @param string $perm Permission key
 */
function requirePermission($perm) {
    if (!isset($_SESSION['user_role'])) {
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }
    if (!hasPermission($_SESSION['user_role'], $perm)) {
        $_SESSION['flash_error'] = 'You do not have permission to access this page.';
        header('Location: ' . APP_URL . '/' . $_SESSION['user_role'] . '/dashboard.php');
        exit;
    }
}
