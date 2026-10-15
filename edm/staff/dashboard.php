<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STAFF]);

$staffId = $_SESSION['role_id'] ?? 0;
$db = getDB();

// 1. Fetch Staff info
$stfStmt = $db->prepare("
    SELECT st.*, d.name AS dept_name, d.code AS dept_code
    FROM staff st
    LEFT JOIN departments d ON st.department_id = d.id
    WHERE st.id = ?
");
$stfStmt->execute([$staffId]);
$staff = $stfStmt->fetch();

// Fallback lookup by user_id if not found by staff.id
if (!$staff && !empty($_SESSION['user_id'])) {
    $stfUserStmt = $db->prepare("
        SELECT st.*, d.name AS dept_name, d.code AS dept_code
        FROM staff st
        LEFT JOIN departments d ON st.department_id = d.id
        WHERE st.user_id = ?
    ");
    $stfUserStmt->execute([$_SESSION['user_id']]);
    $staff = $stfUserStmt->fetch();
    if ($staff) {
        $staffId = $staff['id'];
        $_SESSION['role_id'] = $staffId;
    }
}

// Fallback safe array to prevent any null/boolean warnings
if (!$staff) {
    $staff = [
        'id'          => $staffId,
        'first_name'  => $_SESSION['user_name'] ?? 'Faculty',
        'last_name'   => '',
        'staff_code'  => $_SESSION['staff_code'] ?? 'STF001',
        'dept_name'   => 'Computer Science and Engineering',
        'dept_code'   => 'CSE',
        'designation' => 'Faculty Member'
    ];
}

// 2. Active Attendance Session
$actAttStmt = $db->prepare("
    SELECT s.*, s.pin_code AS session_pin, sub.code AS subject_code, sub.name AS subject_name
    FROM attendance_sessions s
    JOIN subjects sub ON s.subject_id = sub.id
    WHERE s.staff_id = ? AND s.status = 'active' AND s.expires_at > NOW()
    ORDER BY s.created_at DESC LIMIT 1
");
$actAttStmt->execute([$staffId]);
$activeAttendance = $actAttStmt->fetch();

// 3. Active Presentation Broadcast
$actPresStmt = $db->prepare("
    SELECT ps.*, p.title AS pres_title, sub.code AS subject_code
    FROM presentation_sessions ps
    JOIN presentations p ON ps.presentation_id = p.id
    JOIN subjects sub ON p.subject_id = sub.id
    WHERE p.staff_id = ? AND ps.status = 'active'
    ORDER BY ps.started_at DESC LIMIT 1
");
$actPresStmt->execute([$staffId]);
$activePresentation = $actPresStmt->fetch();

// 4. Summary Quick Stats
$totSubjStmt = $db->prepare("SELECT COUNT(*) FROM subjects WHERE staff_id = ?");
$totSubjStmt->execute([$staffId]);
$totalSubjects = $totSubjStmt->fetchColumn() ?: 0;

$totAssStmt = $db->prepare("SELECT COUNT(*) FROM assignments WHERE staff_id = ?");
$totAssStmt->execute([$staffId]);
$totalAssignments = $totAssStmt->fetchColumn() ?: 0;

$pendingSubmissionsStmt = $db->prepare("
    SELECT COUNT(*) 
    FROM assignment_submissions submiss
    JOIN assignments a ON submiss.assignment_id = a.id
    WHERE a.staff_id = ? AND submiss.status = 'submitted'
");
$pendingSubmissionsStmt->execute([$staffId]);
$pendingSubmissions = $pendingSubmissionsStmt->fetchColumn() ?: 0;

// 5. Recent Assignments
$recAssStmt = $db->prepare("
    SELECT a.*, sub.code AS subject_code
    FROM assignments a
    JOIN subjects sub ON a.subject_id = sub.id
    WHERE a.staff_id = ?
    ORDER BY a.created_at DESC LIMIT 4
");
$recAssStmt->execute([$staffId]);
$recentAssignments = $recAssStmt->fetchAll();

$pageTitle = "Faculty Dashboard";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <!-- Welcome Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card bg-success text-white border-0 shadow-lg position-relative overflow-hidden" style="border-radius: 20px;">
                        <div class="card-body p-4 position-relative" style="z-index: 2;">
                            <div class="d-flex align-items-center justify-content-between flex-wrap">
                                <div>
                                    <span class="badge bg-white text-success mb-2 font-weight-600 px-3 py-1">
                                        <?= htmlspecialchars($staff['dept_code'] ?? 'CSE') ?> • <?= htmlspecialchars($staff['designation'] ?? 'Faculty') ?>
                                    </span>
                                    <h2 class="h3 font-weight-bold mb-1">Welcome, Prof. <?= htmlspecialchars(trim(($staff['first_name'] ?? '') . ' ' . ($staff['last_name'] ?? ''))) ?> 👋</h2>
                                    <p class="mb-0 text-white-50">Staff ID: <strong><?= htmlspecialchars($staff['staff_code'] ?? 'STF001') ?></strong> | Department: <?= htmlspecialchars($staff['dept_name'] ?? 'Computer Science & Engineering') ?></p>
                                </div>
                                <div class="mt-3 mt-md-0 d-flex gap-2">
                                    <a href="<?= APP_URL ?>/staff/create_attendance.php" class="btn btn-light text-success font-weight-600 rounded-pill px-4 shadow-sm">
                                        <i class="fas fa-qrcode me-2"></i> Take Attendance
                                    </a>
                                    <a href="<?= APP_URL ?>/staff/create_presentation.php" class="btn btn-outline-light font-weight-600 rounded-pill px-4 shadow-sm">
                                        <i class="fas fa-desktop me-2"></i> Start Presentation
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Live Widgets Banner -->
            <?php if ($activeAttendance || $activePresentation): ?>
                <div class="row g-3 mb-4">
                    <?php if ($activeAttendance): ?>
                        <div class="col-md-6">
                            <div class="alert alert-primary d-flex align-items-center justify-content-between mb-0" style="border-radius: 16px;">
                                <div>
                                    <span class="badge bg-primary text-white mb-1">ATTENDANCE SESSION ACTIVE</span>
                                    <h5 class="h6 font-weight-bold text-dark mb-0"><?= htmlspecialchars($activeAttendance['subject_code'] ?? '') ?> - PIN: <strong><?= htmlspecialchars($activeAttendance['session_pin'] ?? $activeAttendance['pin_code'] ?? 'N/A') ?></strong></h5>
                                </div>
                                <a href="<?= APP_URL ?>/staff/attendance_records.php?session_id=<?= $activeAttendance['id'] ?>" class="btn btn-primary btn-sm rounded-pill font-weight-600">
                                    View Live PIN & QR <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($activePresentation): ?>
                        <div class="col-md-6">
                            <div class="alert alert-danger d-flex align-items-center justify-content-between mb-0" style="border-radius: 16px;">
                                <div>
                                    <span class="badge bg-danger text-white mb-1">PRESENTATION BROADCAST LIVE</span>
                                    <h5 class="h6 font-weight-bold text-dark mb-0"><?= htmlspecialchars($activePresentation['pres_title']) ?></h5>
                                </div>
                                <a href="<?= APP_URL ?>/staff/live_presentation.php?session_id=<?= $activePresentation['id'] ?>" class="btn btn-danger btn-sm rounded-pill font-weight-600">
                                    Open Control Console <i class="fas fa-play ms-1"></i>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Faculty Stat Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                        <div class="card-body p-3.5 d-flex align-items-center">
                            <div class="rounded-circle bg-success-light text-success p-3 me-3" style="width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                                <i class="fas fa-book font-weight-bold"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Assigned Subjects</span>
                                <h3 class="h4 font-weight-bold mb-0 text-dark"><?= $totalSubjects ?></h3>
                                <span class="text-muted small">Course loads</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                        <div class="card-body p-3.5 d-flex align-items-center">
                            <div class="rounded-circle bg-warning-light text-warning p-3 me-3" style="width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Total Assignments</span>
                                <h3 class="h4 font-weight-bold mb-0 text-dark"><?= $totalAssignments ?></h3>
                                <span class="text-muted small">Published</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                        <div class="card-body p-3.5 d-flex align-items-center">
                            <div class="rounded-circle bg-danger-light text-danger p-3 me-3" style="width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                                <i class="fas fa-edit"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Pending Submissions</span>
                                <h3 class="h4 font-weight-bold mb-0 text-dark"><?= $pendingSubmissions ?></h3>
                                <span class="text-muted small">Requires grading</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm h-100 bg-dark text-white" style="border-radius: 16px;">
                        <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                            <div>
                                <span class="text-muted small d-block text-light opacity-75">Lab & Classroom</span>
                                <h4 class="h6 font-weight-bold text-white mb-1">Smart Reservation</h4>
                            </div>
                            <a href="<?= APP_URL ?>/staff/book_classroom.php" class="btn btn-warning text-dark btn-sm rounded-pill font-weight-600 mt-2">
                                Book Room <i class="fas fa-plus ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="row g-4">
                <div class="col-lg-8">
                    
                    <!-- Recent Assignments Created -->
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                        <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                            <h5 class="h6 font-weight-bold mb-0 text-dark"><i class="fas fa-tasks text-success me-2"></i> Recent Assignments</h5>
                            <a href="<?= APP_URL ?>/staff/manage_assignments.php" class="btn btn-link text-success p-0 small font-weight-600">Manage All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Subject</th>
                                        <th>Title</th>
                                        <th>Due Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentAssignments)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">No assignments created yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentAssignments as $ra): ?>
                                            <tr>
                                                <td><span class="badge bg-success-light text-success font-weight-600 px-3 py-1"><?= htmlspecialchars($ra['subject_code']) ?></span></td>
                                                <td><strong><?= htmlspecialchars($ra['title']) ?></strong></td>
                                                <td class="small"><?= date('d M Y, h:i A', strtotime($ra['due_date'])) ?></td>
                                                <td>
                                                    <a href="<?= APP_URL ?>/staff/review_submissions.php?assignment_id=<?= $ra['id'] ?>" class="btn btn-sm btn-outline-success rounded-pill font-weight-600">
                                                        Grade Submissions <i class="fas fa-check-circle ms-1"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="h6 font-weight-bold mb-0 text-dark"><i class="fas fa-bolt text-warning me-2"></i> Faculty Quick Tools</h5>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-grid gap-2">
                                <a href="<?= APP_URL ?>/staff/manage_marks.php" class="btn btn-light text-start p-3 rounded-3 d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-edit text-primary me-3 fs-5"></i>
                                        <div>
                                            <strong class="d-block text-dark">Enter Internal Marks</strong>
                                            <span class="text-muted small">Input IA1, IA2 & Model exam scores</span>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted small"></i>
                                </a>

                                <a href="<?= APP_URL ?>/staff/upload_materials.php" class="btn btn-light text-start p-3 rounded-3 d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-file-upload text-success me-3 fs-5"></i>
                                        <div>
                                            <strong class="d-block text-dark">Upload Study Materials</strong>
                                            <span class="text-muted small">Post lecture slides & notes</span>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted small"></i>
                                </a>

                                <a href="<?= APP_URL ?>/staff/manage_questions.php" class="btn btn-light text-start p-3 rounded-3 d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-file-pdf text-danger me-3 fs-5"></i>
                                        <div>
                                            <strong class="d-block text-dark">Question Paper Bank</strong>
                                            <span class="text-muted small">Upload midterm & semester papers</span>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted small"></i>
                                </a>

                                <a href="<?= APP_URL ?>/staff/reports/index.php" class="btn btn-light text-start p-3 rounded-3 d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-chart-pie text-info me-3 fs-5"></i>
                                        <div>
                                            <strong class="d-block text-dark">Faculty Reports Hub</strong>
                                            <span class="text-muted small">Generate CSV & PDF reports</span>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted small"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
