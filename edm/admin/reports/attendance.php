<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';
checkRole([ROLE_ADMIN]);

$db = getDB();

$departments = $db->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();
$selectedDept = isset($_GET['department_id']) ? (int)$_GET['department_id'] : ($departments[0]['id'] ?? 0);

$records = [];
if ($selectedDept > 0) {
    $stStmt = $db->prepare("
        SELECT st.register_number, st.first_name, st.last_name, d.code AS dept_code,
               COUNT(ar.id) AS total_sessions,
               SUM(CASE WHEN ar.status = 'present' OR ar.status = 'late' THEN 1 ELSE 0 END) AS attended,
               SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) AS absent
        FROM students st
        JOIN departments d ON st.department_id = d.id
        LEFT JOIN attendance_records ar ON st.id = ar.student_id
        WHERE st.department_id = ?
        GROUP BY st.id, st.register_number, st.first_name, st.last_name, d.code
        ORDER BY st.register_number ASC
    ");
    $stStmt->execute([$selectedDept]);
    $records = $stStmt->fetchAll();
}

$pageTitle = "Admin Attendance Report";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Department Attendance Report</h1>
                    <p class="text-muted small">System-wide attendance statistics by department</p>
                </div>
                <div>
                    <button onclick="window.print()" class="btn btn-primary rounded-pill btn-sm">
                        <i class="fas fa-print me-1"></i> Print / Save PDF
                    </button>
                </div>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 16px;">
                <form action="" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label for="department_id" class="form-label font-weight-500">Select Department</label>
                        <select id="department_id" name="department_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= $d['id'] == $selectedDept ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
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
                                <th>Dept Code</th>
                                <th>Total Sessions</th>
                                <th>Attended</th>
                                <th>Absent</th>
                                <th>Attendance %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($records)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No records found.</td>
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
                                        <td><span class="badge bg-warning-light text-dark font-weight-600"><?= htmlspecialchars($r['dept_code']) ?></span></td>
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
