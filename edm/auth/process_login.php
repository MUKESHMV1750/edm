<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';

// Safe role constants fallback
if (!defined('ROLE_STUDENT')) define('ROLE_STUDENT', 'student');
if (!defined('ROLE_STAFF'))   define('ROLE_STAFF', 'staff');
if (!defined('ROLE_ADMIN'))   define('ROLE_ADMIN', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

// Verify CSRF
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('danger', 'Invalid session security token. Please try again.');
    redirect('index.php');
}

$username = sanitizeInput($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$expected_role = sanitizeInput($_POST['expected_role'] ?? '');
$source = sanitizeInput($_POST['source'] ?? 'unified');

// Determine appropriate fail redirect URL
$failRedirect = ($source === 'unified' || empty($expected_role) || !in_array($expected_role, ['student', 'staff', 'admin']))
    ? ("auth/login.php" . (!empty($expected_role) ? "?role={$expected_role}" : ""))
    : "auth/{$expected_role}_login.php";

if (empty($username) || empty($password)) {
    setFlashMessage('danger', 'Please enter both username/email and password.');
    redirect($failRedirect);
}

$db = getDB();

// Find user by username or email
$stmt = $db->prepare("SELECT * FROM users WHERE (username = :u1 OR email = :u2) AND status = 'active' LIMIT 1");
$stmt->execute(['u1' => $username, 'u2' => $username]);
$user = $stmt->fetch();

// Also check student register_number if not found
if (!$user) {
    $stLookup = $db->prepare("SELECT u.* FROM users u JOIN students s ON u.id = s.user_id WHERE s.register_number = ? AND u.status = 'active' LIMIT 1");
    $stLookup->execute([$username]);
    $user = $stLookup->fetch();
}

// Also check staff staff_code if not found
if (!$user) {
    $sfLookup = $db->prepare("SELECT u.* FROM users u JOIN staff st ON u.id = st.user_id WHERE st.staff_code = ? AND u.status = 'active' LIMIT 1");
    $sfLookup->execute([$username]);
    $user = $sfLookup->fetch();
}

$loginSuccess = false;

if ($user) {
    // Check password (supports hashed password and demo fallback)
    if (password_verify($password, $user['password'])) {
        $loginSuccess = true;
    } else {
        // Demo fallback check
        $demoPasswords = [
            'admin' => 'admin123',
            'staff' => 'staff123',
            'student' => 'student123'
        ];
        if (isset($demoPasswords[$user['role']]) && $password === $demoPasswords[$user['role']]) {
            $loginSuccess = true;
        }
    }
}

if (!$loginSuccess) {
    setFlashMessage('danger', 'Invalid username or password.');
    redirect($failRedirect);
}

// Role mismatch check
if (!empty($expected_role) && $user['role'] !== $expected_role) {
    setFlashMessage('warning', "Access denied. Your account is registered as '" . ucfirst($user['role']) . "', not '" . ucfirst($expected_role) . "'.");
    $mismatchRedirect = ($source === 'unified') 
        ? "auth/login.php?role={$user['role']}" 
        : "auth/{$user['role']}_login.php";
    redirect($mismatchRedirect);
}

// Initialize Session
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];

// Retrieve role-specific details
if ($user['role'] === ROLE_STUDENT) {
    $stStmt = $db->prepare("SELECT id, first_name, last_name, register_number, department_id, semester, section FROM students WHERE user_id = ?");
    $stStmt->execute([$user['id']]);
    $student = $stStmt->fetch();
    if ($student) {
        $_SESSION['role_id'] = $student['id'];
        $_SESSION['user_name'] = $student['first_name'] . ' ' . $student['last_name'];
        $_SESSION['register_number'] = $student['register_number'];
        $_SESSION['department_id'] = $student['department_id'];
        $_SESSION['semester'] = $student['semester'];
        $_SESSION['section'] = $student['section'];
    } else {
        $_SESSION['user_name'] = $user['username'];
    }
} elseif ($user['role'] === ROLE_STAFF) {
    $sfStmt = $db->prepare("SELECT id, first_name, last_name, staff_code, department_id, designation FROM staff WHERE user_id = ?");
    $sfStmt->execute([$user['id']]);
    $staff = $sfStmt->fetch();
    if ($staff) {
        $_SESSION['role_id'] = $staff['id'];
        $_SESSION['user_name'] = $staff['first_name'] . ' ' . $staff['last_name'];
        $_SESSION['staff_code'] = $staff['staff_code'];
        $_SESSION['department_id'] = $staff['department_id'];
        $_SESSION['designation'] = $staff['designation'];
    } else {
        $_SESSION['user_name'] = $user['username'];
    }
} else { // Admin
    $_SESSION['user_name'] = 'Administrator';
}

// Update last login
$upd = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
$upd->execute([$user['id']]);

// Log Audit
logAudit($user['id'], 'LOGIN', "User logged in successfully as {$user['role']}");

// Redirect to dashboard
switch ($user['role']) {
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
