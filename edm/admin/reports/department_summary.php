<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';
checkRole([ROLE_ADMIN]);

$db = getDB();

// Department Stats Summary
$depts = $db->query("
    SELECT d.*,
           (SELECT COUNT(*) FROM staff st WHERE st.department_id = d.id) AS total_staff,
           (SELECT COUNT(*) FROM students s WHERE s.department_id = d.id) AS total_students,
           (SELECT COUNT(*) FROM subjects sub WHERE sub.department_id = d.id) AS total_subjects
    FROM departments d
    ORDER BY d.code ASC
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Department Summary Report</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; padding: 40px; color: #0F172A; background: #fff; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #F59E0B; padding-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #CBD5E1; padding: 14px; text-align: left; }
        th { background: #0F172A; color: #fff; }
        .btn-print { background: #F59E0B; color: #000; border: none; padding: 10px 24px; border-radius: 50px; font-weight: 700; cursor: pointer; text-decoration: none; }
        @media print { .btn-print { display: none; } }
    </style>
</head>
<body>

    <div class="text-end mb-4">
        <button onclick="window.print()" class="btn-print"><i class="fas fa-print me-1"></i> Print / Save PDF</button>
    </div>

    <div class="header">
        <h1 style="margin: 0; color: #D97706; font-size: 26px;">ENGINEERING DEPARTMENT MANAGEMENT SYSTEM</h1>
        <h3 style="margin: 5px 0 0 0; font-weight: 500; color: #475569;">Institutional Departmental Summary & Ratios</h3>
        <p style="margin: 5px 0 0 0; font-size: 13px; color: #64748B;">Report Generated On: <?= date('d M Y, h:i A') ?></p>
    </div>

    <h3>Departmental Breakdown</h3>
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Department Name</th>
                <th>Head of Dept (HOD)</th>
                <th>Faculty Staff</th>
                <th>Enrolled Students</th>
                <th>Offered Subjects</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($depts as $d): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($d['code']) ?></strong></td>
                    <td><?= htmlspecialchars($d['name']) ?></td>
                    <td><?= htmlspecialchars($d['hod_name'] ?? 'N/A') ?></td>
                    <td><strong><?= $d['total_staff'] ?></strong></td>
                    <td><strong><?= $d['total_students'] ?></strong></td>
                    <td><strong><?= $d['total_subjects'] ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 80px; display: flex; justify-content: space-between; text-align: center;">
        <div>
            <hr style="width: 200px; border-top: 1px solid #000;">
            <span style="font-size: 14px; color: #475569;">Dean of Academics</span>
        </div>
        <div>
            <hr style="width: 200px; border-top: 1px solid #000;">
            <span style="font-size: 14px; color: #475569;">System Administrator</span>
        </div>
    </div>

</body>
</html>
