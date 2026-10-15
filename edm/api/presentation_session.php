<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = getDB();

// Handle GET start_session action
if (isset($_GET['action']) && $_GET['action'] === 'start_session') {
    $presentationId = (int)$_GET['presentation_id'];
    $pin = sprintf("%06d", rand(100000, 999999));
    $code = 'PS-' . substr(md5(uniqid()), 0, 8);
    $staffId = $_SESSION['role_id'] ?? 1;

    $insS = $db->prepare("
        INSERT INTO presentation_sessions (presentation_id, staff_id, current_page, session_code, pin_code, status)
        VALUES (?, ?, 1, ?, ?, 'active')
    ");
    $insS->execute([$presentationId, $staffId, $code, $pin]);
    $sessionId = $db->lastInsertId();

    setFlashMessage('success', "Live Presentation broadcast started! PIN: {$pin}");
    redirect("staff/live_presentation.php?session_id={$sessionId}");
}

// Handle POST actions (change slide, update notes, end session)
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$sessionId = (int)($input['session_id'] ?? 0);

if ($sessionId > 0) {
    if ($action === 'change_slide') {
        $slide = (int)$input['current_slide'];
        $upd = $db->prepare("UPDATE presentation_sessions SET current_page = ? WHERE id = ?");
        $upd->execute([$slide, $sessionId]);
        echo json_encode(['success' => true]);
        exit;
    } elseif ($action === 'update_notes') {
        echo json_encode(['success' => true]);
        exit;
    } elseif ($action === 'end_session') {
        $upd = $db->prepare("UPDATE presentation_sessions SET status = 'ended', ended_at = NOW() WHERE id = ?");
        $upd->execute([$sessionId]);
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
