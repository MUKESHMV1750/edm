<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/report_helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STUDENT]);

$studentId = $_SESSION['role_id'] ?? 0;
$format = sanitizeInput($_GET['format'] ?? 'print');

$db = getDB();

// Fetch student details
$stStmt = $db->prepare("
    SELECT s.*, d.name AS dept_name, d.code AS dept_code
    FROM students s
    JOIN departments d ON s.department_id = d.id
    WHERE s.id = ?
");
$stStmt->execute([$studentId]);
$student = $stStmt->fetch();

// Fetch attendance summary
$attStmt = $db->prepare("
    SELECT 
        sub.code AS subject_code,
        sub.name AS subject_name,
        COUNT(ar.id) AS total_sessions,
        SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) AS present_count,
        SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END) AS late_count,
        SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) AS absent_count
    FROM attendance_records ar
    JOIN attendance_sessions ses ON ar.session_id = ses.id
    JOIN subjects sub ON ses.subject_id = sub.id
    WHERE ar.student_id = ?
    GROUP BY sub.id, sub.code, sub.name
");
$attStmt->execute([$studentId]);
$attendance = $attStmt->fetchAll();

// CSV Export Mode
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Student_Academic_Report_' . $student['register_number'] . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ENGINEERING DEPARTMENT MANAGEMENT SYSTEM - STUDENT REPORT']);
    fputcsv($output, ['Name:', $student['first_name'] . ' ' . $student['last_name']]);
    fputcsv($output, ['Register No:', $student['register_number']]);
    fputcsv($output, ['Department:', $student['dept_name']]);
    fputcsv($output, ['Semester:', $student['semester']]);
    fputcsv($output, []);
    fputcsv($output, ['Subject Code', 'Subject Name', 'Total Classes', 'Present', 'Absent', 'Attendance %']);
    
    foreach ($attendance as $row) {
        $tot = $row['total_sessions'] ?: 1;
        $pres = $row['present_count'] + $row['late_count'];
        $pct = round(($pres / $tot) * 100, 1);
        fputcsv($output, [
            $row['subject_code'],
            $row['subject_name'],
            $row['total_sessions'],
            $pres,
            $row['absent_count'],
            $pct . '%'
        ]);
    }
    fclose($output);
    exit;
}

// Print / HTML View Mode
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Academic Report - <?= htmlspecialchars($student['register_number']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; padding: 40px; color: #1E293B; background: #fff; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #1D4ED8; padding-bottom: 20px; }
        .meta-box { display: flex; justify-content: space-between; margin-bottom: 30px; background: #F8FAFC; padding: 20px; border-radius: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #CBD5E1; padding: 12px 16px; text-align: left; }
        th { background: #0F172A; color: #fff; }
        .btn-print { background: #1D4ED8; color: #fff; border: none; padding: 10px 20px; border-radius: 50px; font-weight: 600; cursor: pointer; text-decoration: none; }
        @media print { .btn-print { display: none; } }
    </style>
</head>
<body>

    <div class="text-end mb-4">
        <button onclick="window.print()" class="btn-print"><i class="fas fa-print me-1"></i> Print / Save PDF</button>
        <a href="?format=csv" class="btn-print" style="background: #10B981;"><i class="fas fa-file-csv me-1"></i> Export CSV</a>
    </div>

    <div class="header">
        <h2 style="margin: 0; color: #1D4ED8; font-size: 24px;">ENGINEERING DEPARTMENT MANAGEMENT SYSTEM</h2>
        <h4 style="margin: 5px 0 0 0; font-weight: 500; color: #475569;">Official Student Academic & Attendance Performance Card</h4>
    </div>

    <div class="meta-box">
        <div>
            <div><strong>Student Name:</strong> <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></div>
            <div><strong>Register Number:</strong> <?= htmlspecialchars($student['register_number']) ?></div>
            <div><strong>Department:</strong> <?= htmlspecialchars($student['dept_name']) ?></div>
        </div>
        <div>
            <div><strong>Semester:</strong> <?= htmlspecialchars($student['semester']) ?> (Section <?= htmlspecialchars($student['section']) ?>)</div>
            <div><strong>Academic Year:</strong> <?= htmlspecialchars($student['academic_year']) ?></div>
            <div><strong>Generated Date:</strong> <?= date('d M Y') ?></div>
        </div>
    </div>

    <h3>Subject-Wise Attendance Summary</h3>
    <table>
        <thead>
            <tr>
                <th>Subject Code</th>
                <th>Subject Name</th>
                <th>Total Classes</th>
                <th>Attended</th>
                <th>Absent</th>
                <th>Percentage</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($attendance as $row): ?>
                <?php
                    $tot = $row['total_sessions'] ?: 1;
                    $pres = $row['present_count'] + $row['late_count'];
                    $pct = round(($pres / $tot) * 100, 1);
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['subject_code']) ?></strong></td>
                    <td><?= htmlspecialchars($row['subject_name']) ?></td>
                    <td><?= $row['total_sessions'] ?></td>
                    <td style="color: #10B981; font-weight: 600;"><?= $pres ?></td>
                    <td style="color: #EF4444; font-weight: 600;"><?= $row['absent_count'] ?></td>
                    <td style="font-weight: 700; color: <?= $pct >= 75 ? '#10B981' : '#EF4444' ?>;"><?= $pct ?>%</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 60px; display: flex; justify-content: space-between; text-align: center;">
        <div>
            <hr style="width: 200px; border-top: 1px solid #000;">
            <span style="font-size: 14px; color: #475569;">Student Signature</span>
        </div>
        <div>
            <hr style="width: 200px; border-top: 1px solid #000;">
            <span style="font-size: 14px; color: #475569;">Head of Department (HOD)</span>
        </div>
    </div>

</body>
</html>
