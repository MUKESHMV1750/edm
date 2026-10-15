<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/report_helpers.php';
require_once __DIR__ . '/../../includes/auth_check.php';
checkRole([ROLE_STAFF]);

$staffId = $_SESSION['role_id'] ?? 0;
$db = getDB();

// Fetch Staff Subjects
$subStmt = $db->prepare("SELECT * FROM subjects WHERE staff_id = ? ORDER BY code ASC");
$subStmt->execute([$staffId]);
$subjects = $subStmt->fetchAll();

$selectedSubject = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : ($subjects[0]['id'] ?? 0);

$records = [];
if ($selectedSubject > 0) {
    $subjDetails = $db->prepare("SELECT department_id, semester FROM subjects WHERE id = ?");
    $subjDetails->execute([$selectedSubject]);
    $sd = $subjDetails->fetch();

    if ($sd) {
        $stStmt = $db->prepare("
            SELECT st.register_number, st.first_name, st.last_name,
                   COUNT(ar.id) AS total_sessions,
                   SUM(CASE WHEN ar.status = 'present' OR ar.status = 'late' THEN 1 ELSE 0 END) AS attended,
                   SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) AS absent
            FROM students st
            LEFT JOIN attendance_records ar ON st.id = ar.student_id
            LEFT JOIN attendance_sessions ses ON ar.session_id = ses.id AND ses.subject_id = ?
            WHERE st.department_id = ? AND st.semester = ?
            GROUP BY st.id, st.register_number, st.first_name, st.last_name
            ORDER BY st.register_number ASC
        ");
        $stStmt->execute([$selectedSubject, $sd['department_id'], $sd['semester']]);
        $records = $stStmt->fetchAll();
    }
}

$pageTitle = "Staff Attendance Report";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Course Attendance Report</h1>
                    <p class="text-muted small">Comprehensive student-wise attendance records for your subjects</p>
                </div>
                <div>
                    <a href="<?= APP_URL ?>/staff/reports/export.php?type=attendance&subject_id=<?= $selectedSubject ?>&format=csv" class="btn btn-outline-success rounded-pill btn-sm me-2">
                        <i class="fas fa-file-csv me-1"></i> Export CSV
                    </a>
                    <button onclick="window.print()" class="btn btn-primary rounded-pill btn-sm">
                        <i class="fas fa-print me-1"></i> Print PDF
                    </button>
                </div>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 16px;">
                <form action="" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label for="subject_id" class="form-label font-weight-500">Select Subject</label>
                        <select id="subject_id" name="subject_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($subjects as $sub): ?>
                                <option value="<?= $sub['id'] ?>" <?= $sub['id'] == $selectedSubject ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sub['code']) ?> - <?= htmlspecialchars($sub['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Register No</th>
                                <th>Student Name</th>
                                <th>Total Sessions</th>
                                <th>Attended</th>
                                <th>Absent</th>
                                <th>Attendance %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($records)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No attendance data found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($records as $r): ?>
                                    <?php
                                        $tot = $r['total_sessions'] ?: 1;
                                        $att = $r['attended'];
                                        $pct = round(($att / $tot) * 100, 1);
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($r['register_number']) ?></strong></td>
                                        <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                                        <td><?= $r['total_sessions'] ?></td>
                                        <td class="text-success font-weight-600"><?= $att ?></td>
                                        <td class="text-danger font-weight-600"><?= $r['absent'] ?></td>
                                        <td>
                                            <span class="badge <?= $pct >= 75 ? 'bg-success-light text-success' : 'bg-danger-light text-danger' ?> font-weight-600 px-3 py-1">
                                                <?= $pct ?>%
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
