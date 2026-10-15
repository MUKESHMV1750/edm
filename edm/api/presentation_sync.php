<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';

$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;

if ($sessionId > 0) {
    $db = getDB();
    $stmt = $db->prepare("SELECT current_page AS current_slide, status FROM presentation_sessions WHERE id = ?");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();

    if ($session) {
        // Count online viewers (active in last 30s)
        $cntStmt = $db->prepare("SELECT COUNT(*) FROM presentation_participants WHERE session_id = ? AND last_heartbeat >= NOW() - INTERVAL 30 SECOND");
        $cntStmt->execute([$sessionId]);
        $activeCount = $cntStmt->fetchColumn() ?: 0;

        echo json_encode([
            'success' => true,
            'current_slide' => (int)$session['current_slide'],
            'slide_notes' => 'Faculty live broadcast',
            'status' => $session['status'],
            'participants_count' => (int)$activeCount
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Session not found']);
