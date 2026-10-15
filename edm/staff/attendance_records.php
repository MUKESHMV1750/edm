<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STAFF, ROLE_ADMIN]);

$currentRole = getCurrentUserRole();
$isAdmin = ($currentRole === ROLE_ADMIN);
$staffId = $_SESSION['role_id'] ?? 0;
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$db = getDB();

// Fetch Session info
if ($isAdmin) {
    $sesStmt = $db->prepare("
        SELECT s.*, s.pin_code AS session_pin, sub.code AS subject_code, sub.name AS subject_name, c.room_number
        FROM attendance_sessions s
        JOIN subjects sub ON s.subject_id = sub.id
        LEFT JOIN classrooms c ON s.classroom_id = c.id
        WHERE s.id = ?
    ");
    $sesStmt->execute([$sessionId]);
} else {
    $sesStmt = $db->prepare("
        SELECT s.*, s.pin_code AS session_pin, sub.code AS subject_code, sub.name AS subject_name, c.room_number
        FROM attendance_sessions s
        JOIN subjects sub ON s.subject_id = sub.id
        LEFT JOIN classrooms c ON s.classroom_id = c.id
        WHERE s.id = ? AND s.staff_id = ?
    ");
    $sesStmt->execute([$sessionId, $staffId]);
}
$session = $sesStmt->fetch();

if (!$session) {
    setFlashMessage('danger', 'Attendance session not found.');
    redirect($isAdmin ? 'staff/attendance_sessions.php' : 'staff/dashboard.php');
}

// Ensure session_pin is always set
if (empty($session['session_pin'])) {
    $session['session_pin'] = $session['pin_code'] ?? '1234';
}

// Fetch All Enrolled Students & their check-in status (including any students who checked into this session)
$sec = $session['section'];

// Check if any students match the specific section/batch filter
$secCheck = $db->prepare("
    SELECT COUNT(*) FROM students st
    WHERE st.department_id = ? AND st.semester = ? AND (
        st.section = ?
        OR (st.section = 'A' AND ? IN ('Batch A', 'A'))
        OR (st.section = 'B' AND ? IN ('Batch B', 'B'))
        OR (st.section = 'Batch A' AND ? IN ('Batch A', 'A'))
        OR (st.section = 'Batch B' AND ? IN ('Batch B', 'B'))
    )
");
$secCheck->execute([$session['department_id'], $session['semester'], $sec, $sec, $sec, $sec, $sec]);
$hasSectionMatches = (int)$secCheck->fetchColumn() > 0;

if ($hasSectionMatches) {
    $stStmt = $db->prepare("
        SELECT 
            st.id AS student_id, st.register_number, st.first_name, st.last_name,
            ar.id AS record_id, ar.status AS att_status, ar.marked_at, 
            ar.marked_via AS verification_method,
            ar.distance_meters, ar.latitude, ar.longitude
        FROM students st
        LEFT JOIN attendance_records ar ON st.id = ar.student_id AND ar.session_id = ?
        WHERE ar.session_id = ? OR (
            st.department_id = ? AND st.semester = ? AND (
                st.section = ?
                OR (st.section = 'A' AND ? IN ('Batch A', 'A'))
                OR (st.section = 'B' AND ? IN ('Batch B', 'B'))
                OR (st.section = 'Batch A' AND ? IN ('Batch A', 'A'))
                OR (st.section = 'Batch B' AND ? IN ('Batch B', 'B'))
            )
        )
        ORDER BY CASE WHEN ar.status = 'present' THEN 0 ELSE 1 END, st.register_number ASC
    ");
    $stStmt->execute([$sessionId, $sessionId, $session['department_id'], $session['semester'], $sec, $sec, $sec, $sec, $sec]);
    $students = $stStmt->fetchAll();
} else {
    // If no students match the specific section/batch, include all semester students + any who marked attendance
    $stStmt = $db->prepare("
        SELECT 
            st.id AS student_id, st.register_number, st.first_name, st.last_name,
            ar.id AS record_id, ar.status AS att_status, ar.marked_at, 
            ar.marked_via AS verification_method,
            ar.distance_meters, ar.latitude, ar.longitude
        FROM students st
        LEFT JOIN attendance_records ar ON st.id = ar.student_id AND ar.session_id = ?
        WHERE ar.session_id = ? OR (
            st.department_id = ? AND st.semester = ?
        )
        ORDER BY CASE WHEN ar.status = 'present' THEN 0 ELSE 1 END, st.register_number ASC
    ");
    $stStmt->execute([$sessionId, $sessionId, $session['department_id'], $session['semester']]);
    $students = $stStmt->fetchAll();
}

// Calculate counts
$totalEnrolled = count($students);
$presentCount = 0;
foreach ($students as $s) {
    if ($s['att_status'] === 'present' || $s['att_status'] === 'late') {
        $presentCount++;
    }
}
$absentCount = max(0, $totalEnrolled - $presentCount);
$attendancePct = $totalEnrolled > 0 ? round(($presentCount / $totalEnrolled) * 100, 1) : 0;

$pageTitle = "Live Attendance Monitor - " . $session['subject_code'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1 mb-1"><?= htmlspecialchars($session['subject_code']) ?></span>
                    <h1 class="h3 font-weight-bold text-dark mb-0"><?= htmlspecialchars($session['subject_name']) ?></h1>
                    <p class="text-muted small">Semester <?= $session['semester'] ?> (Sec <?= htmlspecialchars($session['section']) ?>) • Room <?= htmlspecialchars($session['room_number'] ?? 'Lab') ?></p>
                </div>
                <div class="d-flex gap-2">
                    <?php if ($session['status'] === 'active'): ?>
                        <a href="<?= APP_URL ?>/api/attendance.php?action=end_session&session_id=<?= $session['id'] ?>" class="btn btn-danger rounded-pill btn-sm px-3 font-weight-600">
                            <i class="fas fa-stop-circle me-1"></i> End Session Now
                        </a>
                    <?php endif; ?>
                    <a href="<?= APP_URL ?>/staff/attendance_sessions.php" class="btn btn-outline-secondary rounded-pill btn-sm">
                        Back to Sessions
                    </a>
                </div>
            </div>

            <?php displayAlerts(); ?>

            <!-- Live Session Header Banner -->
            <div class="row g-4 mb-4">
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm text-center p-4" style="border-radius: 20px; background: linear-gradient(135deg, #0F172A 0%, #1E3A8A 100%); color: #fff;">
                        <span class="badge bg-success-light text-success font-weight-600 px-3 py-1 mb-3 mx-auto">
                            <i class="fas fa-key me-1"></i> LIVE ATTENDANCE PIN
                        </span>
                        <div class="display-3 font-weight-extrabold letter-spacing-4 text-accent my-2">
                            <?= htmlspecialchars($session['session_pin']) ?>
                        </div>
                        <p class="text-white-50 small mb-0">Display this 4-Digit PIN to students or show QR Code</p>

                        <!-- Simulated Dynamic QR Code -->
                        <div class="bg-white p-3 rounded-3 mx-auto mt-3" style="width: 130px; height: 130px; display: flex; align-items: center; justify-content: center;">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=110x110&data=<?= urlencode(APP_URL . '/student/attendance.php?pin=' . $session['session_pin']) ?>" alt="QR Code" style="width: 100%; height: 100%;">
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px;">
                        <h5 class="h6 font-weight-bold text-dark mb-3"><i class="fas fa-chart-pie text-primary me-2"></i> Attendance Real-time Stats</h5>
                        
                        <div class="row text-center g-3 my-2">
                            <div class="col-4">
                                <div class="bg-light p-3 rounded-3">
                                    <span class="text-muted text-xs d-block">TOTAL ENROLLED</span>
                                    <h3 class="h4 font-weight-bold text-dark mb-0" id="statTotalEnrolled"><?= $totalEnrolled ?></h3>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="bg-success-light text-success p-3 rounded-3">
                                    <span class="text-xs d-block">PRESENT NOW</span>
                                    <h3 class="h4 font-weight-bold mb-0" id="statPresentCount"><?= $presentCount ?></h3>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="bg-danger-light text-danger p-3 rounded-3">
                                    <span class="text-xs d-block">ABSENT</span>
                                    <h3 class="h4 font-weight-bold mb-0" id="statAbsentCount"><?= $absentCount ?></h3>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="d-flex justify-content-between text-muted small mb-1">
                                <span>Turnout Percentage</span>
                                <strong id="statAttendancePctText"><?= $attendancePct ?>%</strong>
                            </div>
                            <div class="progress" style="height: 10px; border-radius: 5px;">
                                <div class="progress-bar bg-success" id="statProgressBar" style="width: <?= $attendancePct ?>%"></div>
                            </div>
                        </div>

                        <?php if (!empty($session['allowed_latitude'])): ?>
                            <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2 small">
                                <div>
                                    <i class="fas fa-location-dot text-danger me-1"></i>
                                    <strong>Geofence Verification:</strong> Active • Distance limit: 
                                    <span class="badge bg-danger text-white rounded-pill px-2 py-1"><?= $session['location_radius'] ?: 100 ?>m Radius</span>
                                </div>
                                <a href="https://www.openstreetmap.org/?mlat=<?= $session['allowed_latitude'] ?>&mlon=<?= $session['allowed_longitude'] ?>#map=18/<?= $session['allowed_latitude'] ?>/<?= $session['allowed_longitude'] ?>" target="_blank" class="text-primary text-decoration-none fw-semibold">
                                    <i class="fas fa-arrow-up-right-from-square me-1"></i> View Geofence Location
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Student Check-in Roster Table -->
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="h6 font-weight-bold text-dark mb-0"><i class="fas fa-users text-primary me-2"></i> Student Roster & Live Status</h5>
                    <div class="d-flex align-items-center gap-2">
                        <?php if (!empty($session['allowed_latitude'])): ?>
                            <span class="badge bg-danger-subtle text-danger font-weight-600 border border-danger-subtle px-3 py-1">
                                <i class="fas fa-shield-halved me-1"></i> Geofence ≤ <?= $session['location_radius'] ?: 100 ?>m
                            </span>
                        <?php endif; ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small" id="autoRefreshBadge">
                            <i class="fas fa-circle text-success me-1" style="font-size: 8px;"></i> Auto-refresh active
                        </span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Register No</th>
                                <th>Student Name</th>
                                <th>Check-in Time</th>
                                <th>Verification & Distance</th>
                                <th>Status</th>
                                <th>Manual Toggle</th>
                            </tr>
                        </thead>
                        <tbody id="rosterTableBody">
                            <?php foreach ($students as $st): 
                                $isPresent = ($st['att_status'] === 'present');
                                $isLate = ($st['att_status'] === 'late');
                                $isAttending = ($isPresent || $isLate);
                            ?>
                                <tr id="row_student_<?= $st['student_id'] ?>">
                                    <td><strong><?= htmlspecialchars($st['register_number']) ?></strong></td>
                                    <td><?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?></td>
                                    <td class="small">
                                        <?= !empty($st['marked_at']) ? date('h:i:s A', strtotime($st['marked_at'])) : '<span class="text-muted">-</span>' ?>
                                    </td>
                                    <td>
                                        <?php if ($isAttending): ?>
                                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                                <span class="badge bg-light text-dark text-uppercase small">
                                                    <?= htmlspecialchars(strtoupper($st['verification_method'] ?? 'PIN')) ?>
                                                </span>
                                                <?php if ($st['distance_meters'] !== null && $st['distance_meters'] !== ''): ?>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle small font-weight-600">
                                                        <i class="fas fa-location-dot me-1"></i> <?= $st['distance_meters'] ?>m away
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isPresent): ?>
                                            <span class="badge bg-success-light text-success font-weight-600">Present</span>
                                        <?php elseif ($isLate): ?>
                                            <span class="badge bg-warning-light text-warning font-weight-600">Late</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-light text-danger font-weight-600">Absent</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="toggleAttendance(<?= $session['id'] ?>, <?= $st['student_id'] ?>, '<?= $isPresent ? 'absent' : 'present' ?>')">
                                            Mark <?= $isPresent ? 'Absent' : 'Present' ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
const sessionId = <?= (int)$session['id'] ?>;
const isSessionActive = <?= $session['status'] === 'active' ? 'true' : 'false' ?>;

function toggleAttendance(sId, stId, targetStatus) {
    fetch('<?= APP_URL ?>/api/attendance.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'toggle_status',
            session_id: sId,
            student_id: stId,
            status: targetStatus
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (typeof showToast === 'function') {
                showToast("Attendance status updated!", "success");
            }
            pollLiveAttendance();
        } else {
            if (typeof showToast === 'function') {
                showToast(data.message || "Failed to update", "danger");
            }
        }
    })
    .catch(() => {
        setTimeout(() => location.reload(), 500);
    });
}

function pollLiveAttendance() {
    fetch('<?= APP_URL ?>/api/attendance.php?action=get_live_status&session_id=' + sessionId)
    .then(res => res.json())
    .then(data => {
        if (!data || !data.success) return;

        // Update Stats Counters
        const elTotal = document.getElementById('statTotalEnrolled');
        const elPres = document.getElementById('statPresentCount');
        const elAbs = document.getElementById('statAbsentCount');
        const elPct = document.getElementById('statAttendancePctText');
        const elBar = document.getElementById('statProgressBar');

        if (elTotal) elTotal.textContent = data.total_enrolled;
        if (elPres) elPres.textContent = data.present_count;
        if (elAbs) elAbs.textContent = data.absent_count;
        if (elPct) elPct.textContent = data.attendance_pct + '%';
        if (elBar) elBar.style.width = data.attendance_pct + '%';

        // Update Table Roster
        renderRoster(data.students);
    })
    .catch(err => console.debug('Live poll error:', err));
}

function renderRoster(students) {
    const tbody = document.getElementById('rosterTableBody');
    if (!tbody || !students) return;

    let html = '';
    students.forEach(st => {
        const isPresent = (st.att_status === 'present');
        const isLate = (st.att_status === 'late');
        const isAttending = isPresent || isLate;
        const targetStatus = isPresent ? 'absent' : 'present';
        const buttonText = isPresent ? 'Mark Absent' : 'Mark Present';

        let formattedTime = '<span class="text-muted">-</span>';
        if (st.marked_at) {
            try {
                const parts = st.marked_at.split(/[- :]/);
                if (parts.length >= 6) {
                    const dateObj = new Date(parts[0], parts[1] - 1, parts[2], parts[3], parts[4], parts[5]);
                    formattedTime = dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
                } else {
                    formattedTime = st.marked_at;
                }
            } catch (e) {
                formattedTime = st.marked_at;
            }
        }

        let verHtml = '<span class="text-muted">-</span>';
        if (isAttending) {
            const method = (st.verification_method || 'PIN').toUpperCase();
            let distBadge = '';
            if (st.distance_meters !== null && st.distance_meters !== undefined && st.distance_meters !== '') {
                distBadge = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle small font-weight-600">
                    <i class="fas fa-location-dot me-1"></i> ${st.distance_meters}m away
                </span>`;
            }
            verHtml = `<div class="d-flex align-items-center gap-1 flex-wrap">
                <span class="badge bg-light text-dark text-uppercase small">${method}</span>
                ${distBadge}
            </div>`;
        }

        let statusBadge = '<span class="badge bg-danger-light text-danger font-weight-600">Absent</span>';
        if (isPresent) {
            statusBadge = '<span class="badge bg-success-light text-success font-weight-600">Present</span>';
        } else if (isLate) {
            statusBadge = '<span class="badge bg-warning-light text-warning font-weight-600">Late</span>';
        }

        html += `<tr id="row_student_${st.student_id}">
            <td><strong>${escapeHtml(st.register_number)}</strong></td>
            <td>${escapeHtml((st.first_name || '') + ' ' + (st.last_name || ''))}</td>
            <td class="small">${formattedTime}</td>
            <td>${verHtml}</td>
            <td>${statusBadge}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="toggleAttendance(${sessionId}, ${st.student_id}, '${targetStatus}')">
                    ${buttonText}
                </button>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}

// Auto-poll every 3 seconds if session is active
if (isSessionActive) {
    setInterval(pollLiveAttendance, 3000);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
