<?php
/**
 * Authentication Check
 * Include at the top of every protected page.
 */

require_once __DIR__ . '/functions.php';

if (!isLoggedIn()) {
    setFlashMessage('error', 'Please log in to continue.');
    redirect('/index.php');
}

// Check session timeout
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        session_start();
        setFlashMessage('error', 'Your session has expired. Please log in again.');
        redirect('/index.php');
    }
}
$_SESSION['last_activity'] = time();

// Auto-restore role_id and user details if missing in current session
if (empty($_SESSION['role_id']) && !empty($_SESSION['user_id']) && !empty($_SESSION['user_role'])) {
    $db = getDB();
    if ($_SESSION['user_role'] === ROLE_STUDENT) {
        $st = $db->prepare("SELECT id, first_name, last_name, register_number, department_id, semester, section FROM students WHERE user_id = ?");
        $st->execute([$_SESSION['user_id']]);
        $student = $st->fetch();
        if ($student) {
            $_SESSION['role_id'] = $student['id'];
            $_SESSION['user_name'] = $student['first_name'] . ' ' . $student['last_name'];
            $_SESSION['register_number'] = $student['register_number'];
            $_SESSION['department_id'] = $student['department_id'];
            $_SESSION['semester'] = $student['semester'];
            $_SESSION['section'] = $student['section'];
        }
    } elseif ($_SESSION['user_role'] === ROLE_STAFF) {
        $sf = $db->prepare("SELECT id, first_name, last_name, staff_code, department_id, designation FROM staff WHERE user_id = ?");
        $sf->execute([$_SESSION['user_id']]);
        $staff = $sf->fetch();
        if ($staff) {
            $_SESSION['role_id'] = $staff['id'];
            $_SESSION['user_name'] = $staff['first_name'] . ' ' . $staff['last_name'];
            $_SESSION['staff_code'] = $staff['staff_code'];
            $_SESSION['department_id'] = $staff['department_id'];
            $_SESSION['designation'] = $staff['designation'];
        }
    }
}
