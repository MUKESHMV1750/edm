<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STUDENT]);

$studentId = $_SESSION['role_id'] ?? 0;
$db = getDB();

// Subject-wise Attendance Breakdown
$subAttStmt = $db->prepare("
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
$subAttStmt->execute([$studentId]);
$subjectBreakdown = $subAttStmt->fetchAll();

// All Attendance Log Entries
$allLogStmt = $db->prepare("
    SELECT ar.*, 
           COALESCE(ar.marked_via, 'manual') AS verification_method, 
           sub.code AS subject_code, 
           sub.name AS subject_name, 
           COALESCE(sub.type, 'Theory') AS session_type, 
           ses.session_date
    FROM attendance_records ar
    JOIN attendance_sessions ses ON ar.session_id = ses.id
    JOIN subjects sub ON ses.subject_id = sub.id
    WHERE ar.student_id = ?
    ORDER BY ar.marked_at DESC
");
$allLogStmt->execute([$studentId]);
$allLogs = $allLogStmt->fetchAll();

$pageTitle = "Attendance History";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Attendance History & Stats</h1>
                    <p class="text-muted small">Subject-wise percentage breakdowns and chronological session logs</p>
                </div>
            </div>

            <!-- Subject Breakdown Grid -->
            <div class="row g-4 mb-4">
                <?php if (empty($subjectBreakdown)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">No attendance records found yet.</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($subjectBreakdown as $sb): ?>
                        <?php 
                            $tot = $sb['total_sessions'] ?: 1;
                            $pres = $sb['present_count'] + $sb['late_count'];
                            $pct = round(($pres / $tot) * 100, 1);
                        ?>
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1"><?= htmlspecialchars($sb['subject_code']) ?></span>
                                    <span class="font-weight-bold <?= $pct >= 75 ? 'text-success' : 'text-danger' ?>"><?= $pct ?>%</span>
                                </div>
                                <h5 class="h6 font-weight-bold text-dark mb-3"><?= htmlspecialchars($sb['subject_name']) ?></h5>
                                
                                <div class="progress mb-3" style="height: 8px; border-radius: 4px;">
                                    <div class="progress-bar <?= $pct >= 75 ? 'bg-success' : 'bg-danger' ?>" style="width: <?= $pct ?>%"></div>
                                </div>

                                <div class="d-flex justify-content-between text-muted small">
                                    <span>Total: <strong><?= $sb['total_sessions'] ?></strong></span>
                                    <span>Attended: <strong class="text-success"><?= $pres ?></strong></span>
                                    <span>Absent: <strong class="text-danger"><?= $sb['absent_count'] ?></strong></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Detailed Logs Table -->
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="h6 font-weight-bold text-dark mb-0"><i class="fas fa-list text-primary me-2"></i> Session Logs</h5>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Subject</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Verification Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allLogs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No entries recorded.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allLogs as $idx => $log): ?>
                                    <tr>
                                        <td><?= $idx + 1 ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($log['subject_code']) ?></strong>
                                            <span class="d-block text-muted small"><?= htmlspecialchars($log['subject_name']) ?></span>
                                        </td>
                                        <td class="small"><?= date('d M Y, h:i A', strtotime($log['marked_at'])) ?></td>
                                        <td><span class="badge bg-light text-dark text-uppercase small"><?= htmlspecialchars($log['session_type']) ?></span></td>
                                        <td>
                                            <?php 
                                                $vMethod = $log['marked_via'] ?? ($log['verification_method'] ?? 'manual');
                                                if (empty($vMethod)) $vMethod = 'manual';
                                            ?>
                                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                                <span class="badge bg-secondary small text-uppercase"><?= htmlspecialchars((string)$vMethod) ?></span>
                                                <?php if (!empty($log['distance_meters'])): ?>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle small font-weight-600">
                                                        <i class="fas fa-location-dot me-1"></i> <?= $log['distance_meters'] ?>m
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($log['status'] === 'present'): ?>
                                                <span class="badge bg-success-light text-success">Present</span>
                                            <?php elseif ($log['status'] === 'late'): ?>
                                                <span class="badge bg-warning-light text-warning">Late</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-light text-danger">Absent</span>
                                            <?php endif; ?>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
