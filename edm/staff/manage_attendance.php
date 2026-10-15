<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STAFF]);

$staffId = $_SESSION['role_id'] ?? 0;
$db = getDB();

// Fetch Staff Subjects
$subStmt = $db->prepare("SELECT * FROM subjects WHERE staff_id = ? ORDER BY code ASC");
$subStmt->execute([$staffId]);
$subjects = $subStmt->fetchAll();

$selectedSubject = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : ($subjects[0]['id'] ?? 0);
$selectedDate = sanitizeInput($_GET['date'] ?? date('Y-m-d'));

// Handle Manual Attendance Submission / Edit Batch
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $subject_id = (int)$_POST['subject_id'];
        $date = $_POST['date'];

        // Find or create session for this subject & date
        $sesStmt = $db->prepare("SELECT id FROM attendance_sessions WHERE staff_id = ? AND subject_id = ? AND session_date = ? LIMIT 1");
        $sesStmt->execute([$staffId, $subject_id, $date]);
        $session = $sesStmt->fetch();

        if (!$session) {
            // Get subject dept & sem
            $sd = $db->prepare("SELECT department_id, semester FROM subjects WHERE id = ?")->execute([$subject_id]);
            $subj = $db->prepare("SELECT department_id, semester FROM subjects WHERE id = ?");
            $subj->execute([$subject_id]);
            $sInfo = $subj->fetch();

            $pin = sprintf("%04d", rand(1000, 9999));
            $code = 'MAN-' . strtoupper(substr(md5(uniqid()), 0, 6));

            $insSes = $db->prepare("
                INSERT INTO attendance_sessions (staff_id, subject_id, department_id, semester, section, session_date, start_time, end_time, pin_code, qr_code, status, expires_at)
                VALUES (?, ?, ?, ?, 'A', ?, '09:00:00', '10:00:00', ?, ?, 'completed', NOW())
            ");
            $insSes->execute([$staffId, $subject_id, $sInfo['department_id'], $sInfo['semester'], $date, $pin, $code]);
            $sessionId = $db->lastInsertId();
        } else {
            $sessionId = $session['id'];
        }

        if (isset($_POST['attendance']) && is_array($_POST['attendance'])) {
            foreach ($_POST['attendance'] as $stId => $status) {
                $statusVal = sanitizeInput($status);
                $insRec = $db->prepare("
                    INSERT INTO attendance_records (session_id, student_id, status, marked_via, marked_at)
                    VALUES (?, ?, ?, 'manual', NOW())
                    ON DUPLICATE KEY UPDATE status = ?, marked_via = 'manual', marked_at = NOW()
                ");
                $insRec->execute([$sessionId, $stId, $statusVal, $statusVal]);
            }
            setFlashMessage('success', 'Manual attendance records updated successfully!');
            redirect("staff/manage_attendance.php?subject_id={$subject_id}&date={$date}");
        }
    }
}

// Fetch Students & their records for selected subject & date
$students = [];
if ($selectedSubject > 0) {
    $subjDetails = $db->prepare("SELECT department_id, semester FROM subjects WHERE id = ?");
    $subjDetails->execute([$selectedSubject]);
    $sd = $subjDetails->fetch();

    if ($sd) {
        $stStmt = $db->prepare("
            SELECT st.id AS student_id, st.register_number, st.first_name, st.last_name,
                   ar.status AS att_status
            FROM students st
            LEFT JOIN attendance_sessions ses ON ses.subject_id = ? AND ses.session_date = ?
            LEFT JOIN attendance_records ar ON st.id = ar.student_id AND ar.session_id = ses.id
            WHERE st.department_id = ? AND st.semester = ?
            ORDER BY st.register_number ASC
        ");
        $stStmt->execute([$selectedSubject, $selectedDate, $sd['department_id'], $sd['semester']]);
        $students = $stStmt->fetchAll();
    }
}

$pageTitle = "Manage Manual Attendance";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Manual Attendance Management</h1>
                    <p class="text-muted small">Update, correct, or manually record attendance for past or active classes</p>
                </div>
            </div>

            <?php displayAlerts(); ?>

            <!-- Filter Controls -->
            <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 16px;">
                <form action="" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label for="subject_id" class="form-label font-weight-500">Select Subject</label>
                        <select id="subject_id" name="subject_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($subjects as $sub): ?>
                                <option value="<?= $sub['id'] ?>" <?= $sub['id'] == $selectedSubject ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sub['code']) ?> - <?= htmlspecialchars($sub['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="date" class="form-label font-weight-500">Attendance Date</label>
                        <input type="date" id="date" name="date" class="form-control" value="<?= htmlspecialchars($selectedDate) ?>" onchange="this.form.submit()">
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary rounded-pill w-100 font-weight-600">
                            Load Sheet
                        </button>
                    </div>
                </form>
            </div>

            <!-- Attendance Sheet -->
            <?php if (!empty($students)): ?>
                <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                    <form action="" method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="subject_id" value="<?= $selectedSubject ?>">
                        <input type="hidden" name="date" value="<?= htmlspecialchars($selectedDate) ?>">

                        <div class="table-responsive">
                            <table class="table align-middle table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Register No</th>
                                        <th>Student Name</th>
                                        <th>Attendance Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $st): ?>
                                        <?php $stStatus = $st['att_status'] ?? 'absent'; ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($st['register_number']) ?></strong></td>
                                            <td><?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <input type="radio" class="btn-check" name="attendance[<?= $st['student_id'] ?>]" id="pres_<?= $st['student_id'] ?>" value="present" <?= $stStatus === 'present' ? 'checked' : '' ?>>
                                                    <label class="btn btn-outline-success btn-sm font-weight-600" for="pres_<?= $st['student_id'] ?>">Present</label>

                                                    <input type="radio" class="btn-check" name="attendance[<?= $st['student_id'] ?>]" id="late_<?= $st['student_id'] ?>" value="late" <?= $stStatus === 'late' ? 'checked' : '' ?>>
                                                    <label class="btn btn-outline-warning btn-sm font-weight-600" for="late_<?= $st['student_id'] ?>">Late</label>

                                                    <input type="radio" class="btn-check" name="attendance[<?= $st['student_id'] ?>]" id="abs_<?= $st['student_id'] ?>" value="absent" <?= ($stStatus === 'absent' || empty($st['att_status'])) ? 'checked' : '' ?>>
                                                    <label class="btn btn-outline-danger btn-sm font-weight-600" for="abs_<?= $st['student_id'] ?>">Absent</label>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer bg-white border-0 py-3 text-end">
                            <button type="submit" class="btn btn-success rounded-pill px-4 font-weight-600">
                                Save Attendance Sheet <i class="fas fa-save ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
