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

// Handle End Session GET request
if (isset($_GET['action']) && $_GET['action'] === 'end_session') {
    $sessionId = (int)$_GET['session_id'];
    $db->prepare("UPDATE attendance_sessions SET status = 'completed' WHERE id = ?")->execute([$sessionId]);
    setFlashMessage('success', 'Attendance session ended.');
    redirect("staff/attendance_records.php?session_id={$sessionId}");
}

// Handle GET live status polling
if (isset($_GET['action']) && $_GET['action'] === 'get_live_status') {
    $sessionId = (int)($_GET['session_id'] ?? 0);
    if ($sessionId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
        exit;
    }

    $sesStmt = $db->prepare("SELECT * FROM attendance_sessions WHERE id = ?");
    $sesStmt->execute([$sessionId]);
    $ses = $sesStmt->fetch();
    if (!$ses) {
        echo json_encode(['success' => false, 'message' => 'Session not found']);
        exit;
    }

    $sec = $ses['section'];
    $secCheck = $db->prepare("
        SELECT COUNT(*) FROM students st
        WHERE st.department_id = ? AND st.semester = ? AND (
            st.section = ?
            OR (st.section = 'A' AND ? IN ('Batch A', 'A'))
            OR (st.section = 'B' AND ? IN ('Batch B', 'B'))
            OR (st.section = 'Batch A' AND ? IN ('Batch A', 'A'))
            OR (st.section = 'Batch B' AND ? IN ('Batch B', 'B'))
        )
    ");
    $secCheck->execute([$ses['department_id'], $ses['semester'], $sec, $sec, $sec, $sec, $sec]);
    $hasSectionMatches = (int)$secCheck->fetchColumn() > 0;

    if ($hasSectionMatches) {
        $stStmt = $db->prepare("
            SELECT 
                st.id AS student_id, st.register_number, st.first_name, st.last_name,
                ar.id AS record_id, ar.status AS att_status, ar.marked_at, 
                ar.marked_via AS verification_method,
                ar.distance_meters, ar.latitude, ar.longitude
            FROM students st
            LEFT JOIN attendance_records ar ON st.id = ar.student_id AND ar.session_id = ?
            WHERE ar.session_id = ? OR (
                st.department_id = ? AND st.semester = ? AND (
                    st.section = ?
                    OR (st.section = 'A' AND ? IN ('Batch A', 'A'))
                    OR (st.section = 'B' AND ? IN ('Batch B', 'B'))
                    OR (st.section = 'Batch A' AND ? IN ('Batch A', 'A'))
                    OR (st.section = 'Batch B' AND ? IN ('Batch B', 'B'))
                )
            )
            ORDER BY CASE WHEN ar.status = 'present' THEN 0 ELSE 1 END, st.register_number ASC
        ");
        $stStmt->execute([$sessionId, $sessionId, $ses['department_id'], $ses['semester'], $sec, $sec, $sec, $sec, $sec]);
    } else {
        $stStmt = $db->prepare("
            SELECT 
                st.id AS student_id, st.register_number, st.first_name, st.last_name,
                ar.id AS record_id, ar.status AS att_status, ar.marked_at, 
                ar.marked_via AS verification_method,
                ar.distance_meters, ar.latitude, ar.longitude
            FROM students st
            LEFT JOIN attendance_records ar ON st.id = ar.student_id AND ar.session_id = ?
            WHERE ar.session_id = ? OR (
                st.department_id = ? AND st.semester = ?
            )
            ORDER BY CASE WHEN ar.status = 'present' THEN 0 ELSE 1 END, st.register_number ASC
        ");
        $stStmt->execute([$sessionId, $sessionId, $ses['department_id'], $ses['semester']]);
    }
    $studentsList = $stStmt->fetchAll();

    $tot = count($studentsList);
    $pres = 0;
    foreach ($studentsList as $s) {
        if ($s['att_status'] === 'present' || $s['att_status'] === 'late') {
            $pres++;
        }
    }
    $abs = max(0, $tot - $pres);
    $pct = $tot > 0 ? round(($pres / $tot) * 100, 1) : 0;

    echo json_encode([
        'success' => true,
        'session_status' => $ses['status'],
        'total_enrolled' => $tot,
        'present_count' => $pres,
        'absent_count' => $abs,
        'attendance_pct' => $pct,
        'students' => $studentsList
    ]);
    exit;
}

// Handle POST JSON requests
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'toggle_status') {
    $sessionId = (int)($input['session_id'] ?? 0);
    $studentId = (int)($input['student_id'] ?? 0);
    $status = sanitizeInput($input['status'] ?? 'present');

    $ins = $db->prepare("
        INSERT INTO attendance_records (session_id, student_id, status, marked_via, marked_at)
        VALUES (?, ?, ?, 'manual', NOW())
        ON DUPLICATE KEY UPDATE status = ?, marked_via = 'manual', marked_at = NOW()
    ");
    $ins->execute([$sessionId, $studentId, $status, $status]);

    echo json_encode(['success' => true, 'message' => 'Status updated']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
