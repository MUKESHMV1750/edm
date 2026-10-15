<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== ROLE_STUDENT) {
    echo json_encode(['success' => false]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$sessionId = (int)($input['session_id'] ?? 0);
$slide = (int)($input['current_slide'] ?? 1);
$studentId = $_SESSION['role_id'];

if ($sessionId > 0 && $studentId > 0) {
    $db = getDB();
    $upd = $db->prepare("
        INSERT INTO presentation_participants (session_id, student_id, joined_at, last_heartbeat, connection_status)
        VALUES (?, ?, NOW(), NOW(), 'connected')
        ON DUPLICATE KEY UPDATE last_heartbeat = NOW(), connection_status = 'connected'
    ");
    $upd->execute([$sessionId, $studentId]);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false]);
