<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STUDENT]);

$studentId = $_SESSION['role_id'] ?? 0;
$db = getDB();

// 1. Fetch Student Details
$stStmt = $db->prepare("
    SELECT s.*, d.name AS dept_name, d.code AS dept_code 
    FROM students s 
    LEFT JOIN departments d ON s.department_id = d.id 
    WHERE s.id = ?
");
$stStmt->execute([$studentId]);
$student = $stStmt->fetch();

if (!$student && !empty($_SESSION['user_id'])) {
    $stUserStmt = $db->prepare("
        SELECT s.*, d.name AS dept_name, d.code AS dept_code 
        FROM students s 
        LEFT JOIN departments d ON s.department_id = d.id 
        WHERE s.user_id = ?
    ");
    $stUserStmt->execute([$_SESSION['user_id']]);
    $student = $stUserStmt->fetch();
    if ($student) {
        $studentId = $student['id'];
        $_SESSION['role_id'] = $studentId;
    }
}

if (!$student) {
    $student = [
        'id'              => $studentId,
        'first_name'      => $_SESSION['user_name'] ?? 'Student',
        'last_name'       => '',
        'register_number' => $_SESSION['register_number'] ?? 'REG2024001',
        'department_id'   => 1,
        'dept_name'       => 'Computer Science and Engineering',
        'dept_code'       => 'CSE',
        'semester'        => 6,
        'section'         => 'A'
    ];
}

// 2. Attendance Summary
$attStmt = $db->prepare("
    SELECT 
        COUNT(*) AS total_classes,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present_count,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS late_count,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent_count
    FROM attendance_records
    WHERE student_id = ?
");
$attStmt->execute([$studentId]);
$attSummary = $attStmt->fetch();

$totalClasses = $attSummary['total_classes'] ?: 0;
$presentClasses = ($attSummary['present_count'] ?: 0) + ($attSummary['late_count'] ?: 0);
$attendancePct = $totalClasses > 0 ? round(($presentClasses / $totalClasses) * 100, 1) : 100;

// 3. Pending Assignments Count
$assStmt = $db->prepare("
    SELECT COUNT(*) AS pending_count
    FROM assignments a
    JOIN subjects sub ON a.subject_id = sub.id
    LEFT JOIN assignment_submissions submiss ON a.id = submiss.assignment_id AND submiss.student_id = ?
    WHERE sub.department_id = ? AND sub.semester = ? AND submiss.id IS NULL AND a.due_date >= NOW()
");
$assStmt->execute([$studentId, $student['department_id'], $student['semester']]);
$pendingAssignments = $assStmt->fetchColumn() ?: 0;

// 4. Live Presentations Active
$presStmt = $db->prepare("
    SELECT COUNT(*) AS active_count
    FROM presentation_sessions ps
    JOIN presentations p ON ps.presentation_id = p.id
    JOIN subjects sub ON p.subject_id = sub.id
    WHERE sub.department_id = ? AND sub.semester = ? AND ps.status = 'active'
");
$presStmt->execute([$student['department_id'], $student['semester']]);
$activePresentations = $presStmt->fetchColumn() ?: 0;

// 5. Recent Attendance Logs
$recAttStmt = $db->prepare("
    SELECT ar.*, COALESCE(ar.marked_via, 'manual') AS verification_method, sub.name AS subject_name, sub.code AS subject_code, ar.marked_at
    FROM attendance_records ar
    JOIN attendance_sessions ses ON ar.session_id = ses.id
    JOIN subjects sub ON ses.subject_id = sub.id
    WHERE ar.student_id = ?
    ORDER BY ar.marked_at DESC
    LIMIT 5
");
$recAttStmt->execute([$studentId]);
$recentAttendance = $recAttStmt->fetchAll();

// 6. Upcoming Assignment Deadlines
$upAssStmt = $db->prepare("
    SELECT a.*, sub.name AS subject_name, sub.code AS subject_code
    FROM assignments a
    JOIN subjects sub ON a.subject_id = sub.id
    WHERE sub.department_id = ? AND sub.semester = ? AND a.due_date >= NOW()
    ORDER BY a.due_date ASC
    LIMIT 4
");
$upAssStmt->execute([$student['department_id'], $student['semester']]);
$upcomingAssignments = $upAssStmt->fetchAll();

$pageTitle = "Student Dashboard";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div id="page-content-wrapper" class="w-100">
        <!-- Navbar -->
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <!-- Welcome Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card bg-primary text-white border-0 shadow-lg position-relative overflow-hidden" style="border-radius: 20px;">
                        <div class="card-body p-4 position-relative" style="z-index: 2;">
                            <div class="d-flex align-items-center justify-content-between flex-wrap">
                                <div>
                                    <span class="badge bg-white text-primary mb-2 font-weight-600 px-3 py-1">
                                        <?= htmlspecialchars($student['dept_code']) ?> • Semester <?= $student['semester'] ?> (Sec <?= $student['section'] ?>)
                                    </span>
                                    <h2 class="h3 font-weight-bold mb-1">Welcome back, <?= htmlspecialchars($student['first_name']) ?>! 👋</h2>
                                    <p class="mb-0 text-white-50">Register No: <strong><?= htmlspecialchars($student['register_number']) ?></strong> | Department: <?= htmlspecialchars($student['dept_name']) ?></p>
                                </div>
                                <div class="mt-3 mt-md-0 d-flex gap-2">
                                    <a href="<?= APP_URL ?>/student/attendance.php" class="btn btn-light text-primary font-weight-600 rounded-pill px-4 shadow-sm">
                                        <i class="fas fa-qrcode me-2"></i> Mark Attendance
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="row g-4 mb-4">
                
                <!-- Attendance Card -->
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                        <div class="card-body p-3.5 d-flex align-items-center">
                            <div class="rounded-circle bg-primary-lightest text-primary p-3 me-3" style="width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Overall Attendance</span>
                                <h3 class="h4 font-weight-bold mb-0 text-dark"><?= $attendancePct ?>%</h3>
                                <span class="badge <?= $attendancePct >= 75 ? 'bg-success-light text-success' : 'bg-danger-light text-danger' ?> small">
                                    <?= $attendancePct >= 75 ? 'Eligible' : 'Low Attendance' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Assignments -->
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                        <div class="card-body p-3.5 d-flex align-items-center">
                            <div class="rounded-circle bg-warning-light text-warning p-3 me-3" style="width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Pending Assignments</span>
                                <h3 class="h4 font-weight-bold mb-0 text-dark"><?= $pendingAssignments ?></h3>
                                <span class="text-muted small">Due soon</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Presentations -->
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                        <div class="card-body p-3.5 d-flex align-items-center">
                            <div class="rounded-circle bg-info-light text-info p-3 me-3" style="width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                                <i class="fas fa-desktop"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Live Class Sessions</span>
                                <h3 class="h4 font-weight-bold mb-0 text-dark"><?= $activePresentations ?></h3>
                                <span class="badge bg-success text-white small">Broadcast Active</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Action Card -->
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm h-100 bg-dark text-white" style="border-radius: 16px;">
                        <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                            <div>
                                <span class="text-muted small d-block text-light opacity-75">Quick Action</span>
                                <h4 class="h6 font-weight-bold text-white mb-1">Live Presentation</h4>
                            </div>
                            <a href="<?= APP_URL ?>/student/live_presentations.php" class="btn btn-accent btn-sm rounded-pill font-weight-600 mt-2">
                                Join Live Class <i class="fas fa-play ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Dashboard Main Content Grid -->
            <div class="row g-4">
                
                <!-- Left Column: Attendance History & Upcoming Deadlines -->
                <div class="col-lg-8">
                    
                    <!-- Recent Attendance Log -->
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                        <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                            <h5 class="h6 font-weight-bold mb-0 text-dark"><i class="fas fa-calendar-check text-primary me-2"></i> Recent Attendance Log</h5>
                            <a href="<?= APP_URL ?>/student/attendance_history.php" class="btn btn-link text-primary p-0 small font-weight-600">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Subject</th>
                                        <th>Date & Time</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentAttendance)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">No attendance recorded yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentAttendance as $log): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($log['subject_code']) ?></strong>
                                                    <span class="d-block text-muted small"><?= htmlspecialchars($log['subject_name']) ?></span>
                                                </td>
                                                <td class="small"><?= date('d M Y, h:i A', strtotime($log['marked_at'])) ?></td>
                                                <td>
                                                    <?php 
                                                        $method = $log['marked_via'] ?? ($log['verification_method'] ?? 'manual');
                                                        if (empty($method)) $method = 'manual';
                                                    ?>
                                                    <span class="badge bg-light text-dark text-uppercase small"><?= htmlspecialchars((string)$method) ?></span>
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

                    <!-- Upcoming Assignment Deadlines -->
                    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                        <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                            <h5 class="h6 font-weight-bold mb-0 text-dark"><i class="fas fa-clock text-warning me-2"></i> Upcoming Assignment Deadlines</h5>
                            <a href="<?= APP_URL ?>/student/assignments.php" class="btn btn-link text-primary p-0 small font-weight-600">View All Assignments</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php if (empty($upcomingAssignments)): ?>
                                    <div class="p-4 text-center text-muted">No upcoming assignments.</div>
                                <?php else: ?>
                                    <?php foreach ($upcomingAssignments as $ass): ?>
                                        <div class="list-group-item p-3 d-flex align-items-center justify-content-between border-bottom-0">
                                            <div>
                                                <span class="badge bg-primary-lightest text-primary mb-1"><?= htmlspecialchars($ass['subject_code']) ?></span>
                                                <h6 class="font-weight-bold text-dark mb-1"><?= htmlspecialchars($ass['title']) ?></h6>
                                                <span class="text-muted small">Max Marks: <?= $ass['max_marks'] ?> pts | Due: <?= date('d M Y, h:i A', strtotime($ass['due_date'])) ?></span>
                                            </div>
                                            <a href="<?= APP_URL ?>/student/submit_assignment.php?id=<?= $ass['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill font-weight-600">
                                                Submit <i class="fas fa-upload ms-1"></i>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column: Quick Shortcuts & Tools -->
                <div class="col-lg-4">
                    
                    <!-- Student Utilities Card -->
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="h6 font-weight-bold mb-0 text-dark"><i class="fas fa-th-large text-accent me-2"></i> Academic Portal Links</h5>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-grid gap-2">
                                <a href="<?= APP_URL ?>/student/internal_marks.php" class="btn btn-light text-start p-3 rounded-3 d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-chart-bar text-primary me-3 fs-5"></i>
                                        <div>
                                            <strong class="d-block text-dark">Internal Exam Marks</strong>
                                            <span class="text-muted small">View scores for IA1, IA2 & Model</span>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted small"></i>
                                </a>

                                <a href="<?= APP_URL ?>/student/study_materials.php" class="btn btn-light text-start p-3 rounded-3 d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-book-open text-success me-3 fs-5"></i>
                                        <div>
                                            <strong class="d-block text-dark">Study Materials & Notes</strong>
                                            <span class="text-muted small">Lectures, PDFs & Reference</span>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted small"></i>
                                </a>

                                <a href="<?= APP_URL ?>/student/timetable.php" class="btn btn-light text-start p-3 rounded-3 d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-calendar-alt text-warning me-3 fs-5"></i>
                                        <div>
                                            <strong class="d-block text-dark">Class Timetable</strong>
                                            <span class="text-muted small">Weekly class schedules</span>
                                        </div>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted small"></i>
                                </a>

                                <a href="<?= APP_URL ?>/student/download_report.php" class="btn btn-light text-start p-3 rounded-3 d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-file-pdf text-danger me-3 fs-5"></i>
                                        <div>
                                            <strong class="d-block text-dark">Academic Report Card</strong>
                                            <span class="text-muted small">Download personal PDF report</span>
                                        </div>
                                    </div>
                                    <i class="fas fa-download text-muted small"></i>
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
