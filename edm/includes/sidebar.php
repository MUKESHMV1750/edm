<?php
/**
 * Sidebar Navigation
 * Engineering Department Management System
 */

$currentRole = getCurrentUserRole();
$currentPage = basename($_SERVER['PHP_SELF']);

$menus = [

    // Student Menu
    'student' => [
        [
            'label' => 'Dashboard',
            'icon'  => 'fas fa-tachometer-alt',
            'url'   => '/student/dashboard.php',
            'file'  => 'dashboard.php',
        ],
        [
            'label' => 'My Profile',
            'icon'  => 'fas fa-user-circle',
            'url'   => '/student/profile.php',
            'file'  => 'profile.php',
        ],
        [
            'label'    => 'Attendance',
            'icon'     => 'fas fa-user-check',
            'children' => [
                ['label' => 'Mark Live Attendance', 'url' => '/student/attendance.php', 'file' => 'attendance.php'],
                ['label' => 'Attendance History', 'url' => '/student/attendance_history.php', 'file' => 'attendance_history.php'],
            ],
        ],
        [
            'label'    => 'Academics & Marks',
            'icon'     => 'fas fa-chart-bar',
            'children' => [
                ['label' => 'Internal Marks', 'url' => '/student/internal_marks.php', 'file' => 'internal_marks.php'],
                ['label' => 'Assignment Marks', 'url' => '/student/assignment_marks.php', 'file' => 'assignment_marks.php'],
            ],
        ],
        [
            'label'    => 'Course Assignments',
            'icon'     => 'fas fa-tasks',
            'children' => [
                ['label' => 'View Assignments', 'url' => '/student/assignments.php', 'file' => 'assignments.php'],
            ],
        ],
        [
            'label'    => 'Study Resources',
            'icon'     => 'fas fa-book-open',
            'children' => [
                ['label' => 'Study Materials', 'url' => '/student/study_materials.php', 'file' => 'study_materials.php'],
                ['label' => 'Midterm Questions', 'url' => '/student/midterm_questions.php', 'file' => 'midterm_questions.php'],
                ['label' => 'Semester Questions', 'url' => '/student/semester_questions.php', 'file' => 'semester_questions.php'],
            ],
        ],
        [
            'label'    => 'Live Presentations',
            'icon'     => 'fas fa-desktop',
            'children' => [
                ['label' => 'Active Broadcasts', 'url' => '/student/live_presentations.php', 'file' => 'live_presentations.php'],
            ],
        ],
        [
            'label' => 'Class Timetable',
            'icon'  => 'fas fa-calendar-alt',
            'url'   => '/student/timetable.php',
            'file'  => 'timetable.php',
        ],
        [
            'label' => 'Notifications',
            'icon'  => 'fas fa-bell',
            'url'   => '/student/notifications.php',
            'file'  => 'notifications.php',
        ],
        [
            'label' => 'Academic Report',
            'icon'  => 'fas fa-file-pdf',
            'url'   => '/student/download_report.php',
            'file'  => 'download_report.php',
        ],
    ],

    // Staff Menu
    'staff' => [
        [
            'label' => 'Faculty Dashboard',
            'icon'  => 'fas fa-tachometer-alt',
            'url'   => '/staff/dashboard.php',
            'file'  => 'dashboard.php',
        ],
        [
            'label' => 'Assigned Students',
            'icon'  => 'fas fa-users',
            'url'   => '/staff/manage_students.php',
            'file'  => 'manage_students.php',
        ],
        [
            'label'    => 'Live Attendance',
            'icon'     => 'fas fa-qrcode',
            'children' => [
                ['label' => 'Sessions Log', 'url' => '/staff/attendance_sessions.php', 'file' => 'attendance_sessions.php'],
                ['label' => 'Create Live Session', 'url' => '/staff/create_attendance.php', 'file' => 'create_attendance.php'],
                ['label' => 'Live Monitor', 'url' => '/staff/attendance_records.php', 'file' => 'attendance_records.php'],
            ],
        ],
        [
            'label'    => 'Assignments',
            'icon'     => 'fas fa-tasks',
            'children' => [
                ['label' => 'Manage Assignments', 'url' => '/staff/manage_assignments.php', 'file' => 'manage_assignments.php'],
            ],
        ],
        [
            'label' => 'Enter Internal Marks',
            'icon'  => 'fas fa-award',
            'url'   => '/staff/manage_marks.php',
            'file'  => 'manage_marks.php',
        ],
        [
            'label'    => 'Upload Resources',
            'icon'     => 'fas fa-file-upload',
            'children' => [
                ['label' => 'Study Materials', 'url' => '/staff/upload_materials.php', 'file' => 'upload_materials.php'],
                ['label' => 'Question Papers', 'url' => '/staff/manage_questions.php', 'file' => 'manage_questions.php'],
            ],
        ],
        [
            'label'    => 'Live Presentations',
            'icon'     => 'fas fa-broadcast-tower',
            'children' => [
                ['label' => 'My Presentations', 'url' => '/staff/presentations.php', 'file' => 'presentations.php'],
                ['label' => 'New Live Session', 'url' => '/staff/create_presentation.php', 'file' => 'create_presentation.php'],
            ],
        ],
        [
            'label'    => 'Classroom Booking',
            'icon'     => 'fas fa-door-open',
            'children' => [
                ['label' => 'Book Room / Lab', 'url' => '/staff/book_classroom.php', 'file' => 'book_classroom.php'],
                ['label' => 'Booking History', 'url' => '/staff/booking_history.php', 'file' => 'booking_history.php'],
            ],
        ],
        [
            'label' => 'Teaching Schedule',
            'icon'  => 'fas fa-calendar-alt',
            'url'   => '/staff/timetable.php',
            'file'  => 'timetable.php',
        ],
        [
            'label' => 'Faculty Reports Hub',
            'icon'  => 'fas fa-chart-pie',
            'url'   => '/staff/reports/index.php',
            'file'  => 'index.php',
        ],
    ],

    // Admin Menu
    'admin' => [
        [
            'label' => 'Admin Console',
            'icon'  => 'fas fa-tachometer-alt',
            'url'   => '/admin/dashboard.php',
            'file'  => 'dashboard.php',
        ],
        [
            'label'    => 'User Management',
            'icon'     => 'fas fa-user-shield',
            'children' => [
                ['label' => 'Manage Students', 'url' => '/admin/manage_students.php', 'file' => 'manage_students.php'],
                ['label' => 'Manage Faculty', 'url' => '/admin/manage_staff.php', 'file' => 'manage_staff.php'],
            ],
        ],
        [
            'label'    => 'Academic Setup',
            'icon'     => 'fas fa-university',
            'children' => [
                ['label' => 'Departments', 'url' => '/admin/manage_departments.php', 'file' => 'manage_departments.php'],
                ['label' => 'Subjects', 'url' => '/admin/manage_subjects.php', 'file' => 'manage_subjects.php'],
                ['label' => 'Classrooms & Labs', 'url' => '/admin/manage_classrooms.php', 'file' => 'manage_classrooms.php'],
                ['label' => 'Timetable Builder', 'url' => '/admin/manage_timetable.php', 'file' => 'manage_timetable.php'],
                ['label' => 'Faculty Timetable', 'url' => '/staff/timetable.php', 'file' => 'timetable.php'],
                ['label' => 'Attendance Sessions', 'url' => '/staff/attendance_sessions.php', 'file' => 'attendance_sessions.php'],
            ],
        ],
        [
            'label' => 'Room Approvals',
            'icon'  => 'fas fa-check-double',
            'url'   => '/admin/approve_bookings.php',
            'file'  => 'approve_bookings.php',
        ],
        [
            'label' => 'System Analytics',
            'icon'  => 'fas fa-chart-line',
            'url'   => '/admin/analytics.php',
            'file'  => 'analytics.php',
        ],
        [
            'label' => 'Security Audit Logs',
            'icon'  => 'fas fa-history',
            'url'   => '/admin/audit_logs.php',
            'file'  => 'audit_logs.php',
        ],
        [
            'label' => 'Department Reports',
            'icon'  => 'fas fa-file-invoice',
            'url'   => '/admin/reports/index.php',
            'file'  => 'index.php',
        ],
    ],
];

$menuItems = $menus[$currentRole] ?? [];

function isMenuActive($item) {
    global $currentPage;
    if (isset($item['file']) && $item['file'] === $currentPage) return true;
    if (isset($item['children'])) {
        foreach ($item['children'] as $child) {
            if ($child['file'] === $currentPage) return true;
        }
    }
    return false;
}
?>

<aside class="sidebar bg-dark text-white p-3" id="sidebar">
    <div class="sidebar-header d-flex align-items-center justify-content-between pb-3 border-bottom border-secondary mb-3">
        <a href="<?= APP_URL ?>/<?= $currentRole ?>/dashboard.php" class="text-white text-decoration-none d-flex align-items-center gap-2">
            <i class="fas fa-graduation-cap text-accent fs-3"></i>
            <div>
                <strong class="d-block leading-tight fs-5">EDM Portal</strong>
                <span class="text-muted text-xs text-uppercase font-weight-600"><?= $currentRole ?> Panel</span>
            </div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav nav-pills flex-column mb-auto">
            <?php foreach ($menuItems as $item): ?>
                <?php $active = isMenuActive($item); ?>
                <?php if (isset($item['children'])): ?>
                    <li class="nav-item mb-1">
                        <a class="nav-link text-white d-flex align-items-center justify-content-between <?= $active ? 'active bg-primary font-weight-600' : 'opacity-75' ?>" data-bs-toggle="collapse" href="#collapse<?= md5($item['label']) ?>" role="button" aria-expanded="<?= $active ? 'true' : 'false' ?>">
                            <div>
                                <i class="<?= $item['icon'] ?> me-2" style="width: 20px;"></i>
                                <span><?= $item['label'] ?></span>
                            </div>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </a>
                        <div class="collapse <?= $active ? 'show' : '' ?> ms-3 mt-1" id="collapse<?= md5($item['label']) ?>">
                            <ul class="nav nav-pills flex-column">
                                <?php foreach ($item['children'] as $child): ?>
                                    <li class="nav-item">
                                        <a href="<?= APP_URL . $child['url'] ?>" class="nav-link text-white-50 py-1 small <?= ($child['file'] === $currentPage) ? 'text-accent font-weight-bold' : '' ?>">
                                            <i class="fas fa-circle me-2 text-xs opacity-50"></i>
                                            <?= $child['label'] ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </li>
                <?php else: ?>
                    <li class="nav-item mb-1">
                        <a href="<?= APP_URL . $item['url'] ?>" class="nav-link text-white <?= $active ? 'active bg-primary font-weight-600' : 'opacity-75' ?>">
                            <i class="<?= $item['icon'] ?> me-2" style="width: 20px;"></i>
                            <span><?= $item['label'] ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
