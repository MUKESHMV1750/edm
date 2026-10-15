<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';
checkRole([ROLE_STAFF]);

$staffId = $_SESSION['role_id'] ?? 0;
$type = sanitizeInput($_GET['type'] ?? 'attendance');
$subject_id = (int)($_GET['subject_id'] ?? 0);
$db = getDB();

if ($type === 'attendance') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Faculty_Attendance_Report.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Register Number', 'Student Name', 'Total Sessions', 'Attended', 'Absent', 'Attendance Percentage']);

    $stStmt = $db->prepare("
        SELECT st.register_number, st.first_name, st.last_name,
               COUNT(ar.id) AS total_sessions,
               SUM(CASE WHEN ar.status = 'present' OR ar.status = 'late' THEN 1 ELSE 0 END) AS attended,
               SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) AS absent
        FROM students st
        LEFT JOIN attendance_records ar ON st.id = ar.student_id
        LEFT JOIN attendance_sessions ses ON ar.session_id = ses.id AND ses.subject_id = ?
        GROUP BY st.id, st.register_number, st.first_name, st.last_name
        ORDER BY st.register_number ASC
    ");
    $stStmt->execute([$subject_id]);
    $records = $stStmt->fetchAll();

    foreach ($records as $r) {
        $tot = $r['total_sessions'] ?: 1;
        $att = $r['attended'];
        $pct = round(($att / $tot) * 100, 1);
        fputcsv($output, [
            $r['register_number'],
            $r['first_name'] . ' ' . $r['last_name'],
            $r['total_sessions'],
            $att,
            $r['absent'],
            $pct . '%'
        ]);
    }
    fclose($output);
    exit;
}

redirect('staff/reports/index.php');
