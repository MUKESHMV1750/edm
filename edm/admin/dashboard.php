<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_ADMIN]);

$db = getDB();

// Stat Counts
$totDept = $db->query("SELECT COUNT(*) FROM departments")->fetchColumn() ?: 0;
$totStaff = $db->query("SELECT COUNT(*) FROM staff")->fetchColumn() ?: 0;
$totStudent = $db->query("SELECT COUNT(*) FROM students")->fetchColumn() ?: 0;
$totSubject = $db->query("SELECT COUNT(*) FROM subjects")->fetchColumn() ?: 0;
$totBookingsPending = $db->query("SELECT COUNT(*) FROM classroom_bookings WHERE status = 'pending'")->fetchColumn() ?: 0;

// Department breakdown
$deptsStmt = $db->query("
    SELECT d.*, 
           (SELECT COUNT(*) FROM staff st WHERE st.department_id = d.id) AS staff_count,
           (SELECT COUNT(*) FROM students s WHERE s.department_id = d.id) AS student_count
    FROM departments d
    ORDER BY d.code ASC
");
$departments = $deptsStmt->fetchAll();

// Recent Audit Logs
$auditStmt = $db->query("
    SELECT al.*, COALESCE(al.description, '') AS details, u.username, u.role
    FROM audit_logs al
    JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 6
");
$recentAudits = $auditStmt->fetchAll();

$pageTitle = "Administrator Console";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <!-- Welcome Banner -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card bg-warning text-dark border-0 shadow-lg position-relative overflow-hidden" style="border-radius: 20px;">
                        <div class="card-body p-4 position-relative" style="z-index: 2;">
                            <div class="d-flex align-items-center justify-content-between flex-wrap">
                                <div>
                                    <span class="badge bg-dark text-warning mb-2 font-weight-600 px-3 py-1">
                                        <i class="fas fa-shield-alt me-1"></i> System Administration
                                    </span>
                                    <h2 class="h3 font-weight-bold mb-1">Engineering Department Management Hub 👋</h2>
                                    <p class="mb-0 text-dark opacity-75">Control center for users, academic courses, classroom allocations & departmental reports</p>
                                </div>
                                <div class="mt-3 mt-md-0 d-flex gap-2">
                                    <a href="<?= APP_URL ?>/admin/approve_bookings.php" class="btn btn-dark text-warning font-weight-600 rounded-pill px-4 shadow-sm position-relative">
                                        <i class="fas fa-door-open me-2"></i> Room Approvals
                                        <?php if ($totBookingsPending > 0): ?>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                                <?= $totBookingsPending ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="row g-4 mb-4">
                
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                        <div class="card-body p-3.5 d-flex align-items-center">
                            <div class="rounded-circle bg-warning-light text-warning p-3 me-3" style="width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                                <i class="fas fa-building font-weight-bold"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Departments</span>
                                <h3 class="h4 font-weight-bold mb-0 text-dark"><?= $totDept ?></h3>
                                <span class="text-muted small">Active wings</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                        <div class="card-body p-3.5 d-flex align-items-center">
                            <div class="rounded-circle bg-success-light text-success p-3 me-3" style="width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Faculty & Staff</span>
                                <h3 class="h4 font-weight-bold mb-0 text-dark"><?= $totStaff ?></h3>
                                <span class="text-muted small">Professors</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                        <div class="card-body p-3.5 d-flex align-items-center">
                            <div class="rounded-circle bg-primary-lightest text-primary p-3 me-3" style="width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Enrolled Students</span>
                                <h3 class="h4 font-weight-bold mb-0 text-dark"><?= $totStudent ?></h3>
                                <span class="text-muted small">Active students</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                        <div class="card-body p-3.5 d-flex align-items-center">
                            <div class="rounded-circle bg-info-light text-info p-3 me-3" style="width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                                <i class="fas fa-book"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Total Subjects</span>
                                <h3 class="h4 font-weight-bold mb-0 text-dark"><?= $totSubject ?></h3>
                                <span class="text-muted small">Courses</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Dashboard Grid -->
            <div class="row g-4">
                
                <!-- Left: Department Summary Roster -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h5 class="h6 font-weight-bold text-dark mb-0"><i class="fas fa-building text-warning me-2"></i> Engineering Departments</h5>
                            <a href="<?= APP_URL ?>/admin/manage_departments.php" class="btn btn-link text-warning text-dark p-0 small font-weight-600">Manage Departments</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Code</th>
                                        <th>Department Name</th>
                                        <th>Head of Dept (HOD)</th>
                                        <th>Faculty</th>
                                        <th>Students</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($departments as $d): ?>
                                        <tr>
                                            <td><span class="badge bg-warning-light text-dark font-weight-600 px-3 py-1"><?= htmlspecialchars($d['code']) ?></span></td>
                                            <td><strong><?= htmlspecialchars($d['name']) ?></strong></td>
                                            <td><?= htmlspecialchars($d['hod_name'] ?? 'Not Assigned') ?></td>
                                            <td><span class="badge bg-success-light text-success font-weight-600"><?= $d['staff_count'] ?> Staff</span></td>
                                            <td><span class="badge bg-primary-lightest text-primary font-weight-600"><?= $d['student_count'] ?> Students</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right: Recent Security Audit Logs -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h5 class="h6 font-weight-bold text-dark mb-0"><i class="fas fa-history text-primary me-2"></i> Audit Activity</h5>
                            <a href="<?= APP_URL ?>/admin/audit_logs.php" class="btn btn-link text-primary p-0 small font-weight-600">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentAudits as $log): ?>
                                    <div class="list-group-item p-3 border-bottom-0">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong class="text-dark small"><?= htmlspecialchars($log['username']) ?> (<?= htmlspecialchars($log['role']) ?>)</strong>
                                            <span class="text-muted text-xs"><?= date('h:i A', strtotime($log['created_at'])) ?></span>
                                        </div>
                                        <span class="badge bg-light text-dark text-uppercase small mb-1"><?= htmlspecialchars($log['action']) ?></span>
                                        <p class="text-muted text-xs mb-0"><?= htmlspecialchars($log['description'] ?? ($log['details'] ?? '—')) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
