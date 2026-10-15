<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STUDENT]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = sanitizeInput($_POST['pin'] ?? '');
    if (!empty($pin)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM presentation_sessions WHERE (pin_code = ? OR session_code = ?) AND status = 'active' LIMIT 1");
        $stmt->execute([$pin, $pin]);
        $session = $stmt->fetch();

        if ($session) {
            redirect("student/presentation_viewer.php?session_id={$session['id']}");
        } else {
            setFlashMessage('danger', 'Invalid or inactive session PIN. Please verify with your professor.');
        }
    } else {
        setFlashMessage('warning', 'Please enter a valid session PIN.');
    }
}

redirect('student/live_presentations.php');
