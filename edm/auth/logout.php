<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    $userId = $_SESSION['user_id'] ?? null;
    if ($userId) {
        logAudit($userId, 'LOGOUT', 'User logged out');
    }
}

// Unset all session variables
$_SESSION = array();

// Destroy session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect to index landing
session_start();
setFlashMessage('success', 'You have been logged out successfully.');
redirect('index.php');
