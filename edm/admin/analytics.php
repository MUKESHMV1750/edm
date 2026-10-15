<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_ADMIN]);

$db = getDB();

// Department-wise Student Distribution
$deptDist = $db->query("
    SELECT d.code, d.name, COUNT(s.id) AS student_count
    FROM departments d
    LEFT JOIN students s ON d.id = s.department_id
    GROUP BY d.id, d.code, d.name
")->fetchAll();

// Overall Attendance Rates
$attStats = $db->query("
    SELECT 
        COUNT(*) AS total_records,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present_count,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS late_count,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent_count
    FROM attendance_records
")->fetch();

$tot = $attStats['total_records'] ?: 1;
$presPct = round((($attStats['present_count'] + $attStats['late_count']) / $tot) * 100, 1);

$pageTitle = "System Analytics";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Department Analytics & Insights</h1>
                    <p class="text-muted small">Visual summaries of attendance performance, enrollment metrics & course loads</p>
                </div>
            </div>

            <!-- Top Key Indicators -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
                        <h5 class="h6 font-weight-bold text-dark mb-3"><i class="fas fa-chart-pie text-primary me-2"></i> Overall Department Attendance Rate</h5>
                        <div class="display-4 font-weight-extrabold text-success mb-2"><?= $presPct ?>%</div>
                        <p class="text-muted small mb-0">Based on <?= $tot ?> total recorded class attendance instances across all departments.</p>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
                        <h5 class="h6 font-weight-bold text-dark mb-3"><i class="fas fa-users text-warning me-2"></i> Attendance Status Breakdown</h5>
                        <div class="d-flex justify-content-between text-center mt-3">
                            <div>
                                <span class="text-muted text-xs d-block">PRESENT</span>
                                <strong class="text-success fs-4"><?= $attStats['present_count'] ?></strong>
                            </div>
                            <div>
                                <span class="text-muted text-xs d-block">LATE</span>
                                <strong class="text-warning fs-4"><?= $attStats['late_count'] ?></strong>
                            </div>
                            <div>
                                <span class="text-muted text-xs d-block">ABSENT</span>
                                <strong class="text-danger fs-4"><?= $attStats['absent_count'] ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Department Distribution Table -->
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="h6 font-weight-bold text-dark mb-0"><i class="fas fa-building text-warning me-2"></i> Department Enrollment Stats</h5>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Department Code</th>
                                <th>Department Title</th>
                                <th>Enrolled Students</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deptDist as $dd): ?>
                                <tr>
                                    <td><span class="badge bg-warning-light text-dark font-weight-600 px-3 py-1"><?= htmlspecialchars($dd['code']) ?></span></td>
                                    <td><strong><?= htmlspecialchars($dd['name']) ?></strong></td>
                                    <td><strong class="text-primary"><?= $dd['student_count'] ?> Students</strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
