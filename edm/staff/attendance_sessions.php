<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STAFF, ROLE_ADMIN]);

$currentRole = getCurrentUserRole();
$isAdmin = ($currentRole === ROLE_ADMIN);
$staffId = $_SESSION['role_id'] ?? 0;
$db = getDB();

// Handle Delete Session POST action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('danger', 'Invalid security token. Please try again.');
            redirect('staff/attendance_sessions.php');
        }

        $sessionId = (int)($_POST['session_id'] ?? 0);
        if ($sessionId <= 0) {
            setFlashMessage('danger', 'Invalid attendance session ID.');
            redirect('staff/attendance_sessions.php');
        }

        // Verify session existence and ownership
        if ($isAdmin) {
            $chk = $db->prepare("SELECT id, subject_id FROM attendance_sessions WHERE id = ?");
            $chk->execute([$sessionId]);
        } else {
            $chk = $db->prepare("SELECT id, subject_id FROM attendance_sessions WHERE id = ? AND staff_id = ?");
            $chk->execute([$sessionId, $staffId]);
        }
        $existing = $chk->fetch();

        if (!$existing) {
            setFlashMessage('danger', 'Session not found or you do not have permission to delete it.');
            redirect('staff/attendance_sessions.php');
        }

        try {
            $db->beginTransaction();
            // Delete child attendance records
            $delRecords = $db->prepare("DELETE FROM attendance_records WHERE session_id = ?");
            $delRecords->execute([$sessionId]);

            // Delete session
            $delSession = $db->prepare("DELETE FROM attendance_sessions WHERE id = ?");
            $delSession->execute([$sessionId]);
            $db->commit();

            setFlashMessage('success', 'Attendance session and all associated attendance records were permanently deleted.');
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            setFlashMessage('danger', 'Database error while deleting session: ' . $e->getMessage());
        }

        redirect('staff/attendance_sessions.php');
    }
}

// Fetch sessions
if ($isAdmin) {
    $sesStmt = $db->prepare("
        SELECT s.*, 
               COALESCE(s.pin_code, 'PIN') AS session_pin,
               CASE 
                   WHEN s.qr_code IS NOT NULL AND s.qr_code != '' THEN 'QR'
                   WHEN s.pin_code IS NOT NULL AND s.pin_code != '' THEN 'PIN'
                   ELSE 'General'
               END AS session_type,
               sub.code AS subject_code, sub.name AS subject_name,
               CONCAT(st.first_name, ' ', COALESCE(st.last_name, '')) AS faculty_name,
               (SELECT COUNT(*) FROM attendance_records ar WHERE ar.session_id = s.id AND ar.status IN ('present', 'late')) AS present_count
        FROM attendance_sessions s
        JOIN subjects sub ON s.subject_id = sub.id
        LEFT JOIN staff st ON s.staff_id = st.id
        ORDER BY s.created_at DESC
    ");
    $sesStmt->execute();
} else {
    $sesStmt = $db->prepare("
        SELECT s.*, 
               COALESCE(s.pin_code, 'PIN') AS session_pin,
               CASE 
                   WHEN s.qr_code IS NOT NULL AND s.qr_code != '' THEN 'QR'
                   WHEN s.pin_code IS NOT NULL AND s.pin_code != '' THEN 'PIN'
                   ELSE 'General'
               END AS session_type,
               sub.code AS subject_code, sub.name AS subject_name,
               NULL AS faculty_name,
               (SELECT COUNT(*) FROM attendance_records ar WHERE ar.session_id = s.id AND ar.status IN ('present', 'late')) AS present_count
        FROM attendance_sessions s
        JOIN subjects sub ON s.subject_id = sub.id
        WHERE s.staff_id = ?
        ORDER BY s.created_at DESC
    ");
    $sesStmt->execute([$staffId]);
}
$sessions = $sesStmt->fetchAll();

$pageTitle = "Attendance Sessions History";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Attendance Sessions Log</h1>
                    <p class="text-muted small"><?= $isAdmin ? 'Review and manage attendance sessions across all departments and faculty' : 'Manage live attendance sessions and review historical turnout' ?></p>
                </div>
                <?php if (!$isAdmin): ?>
                    <a href="<?= APP_URL ?>/staff/create_attendance.php" class="btn btn-success rounded-pill btn-sm px-4 font-weight-600">
                        <i class="fas fa-plus me-1"></i> New Session
                    </a>
                <?php endif; ?>
            </div>

            <?php displayAlerts(); ?>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <?php if ($isAdmin): ?>
                                    <th>Faculty</th>
                                <?php endif; ?>
                                <th>Date & Time</th>
                                <th>Sem / Sec</th>
                                <th>Session PIN</th>
                                <th>Type</th>
                                <th>Turnout</th>
                                <th>Status</th>
                                <th class="text-center" style="width: 110px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sessions)): ?>
                                <tr>
                                    <td colspan="<?= $isAdmin ? 9 : 8 ?>" class="text-center py-4 text-muted">No attendance sessions found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sessions as $s): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1"><?= htmlspecialchars($s['subject_code']) ?></span>
                                            <span class="d-block small text-dark font-weight-500 mt-1"><?= htmlspecialchars($s['subject_name']) ?></span>
                                        </td>
                                        <?php if ($isAdmin): ?>
                                            <td>
                                                <span class="small font-weight-600 text-dark"><?= htmlspecialchars(trim($s['faculty_name'] ?? '') ?: ('Staff #' . $s['staff_id'])) ?></span>
                                            </td>
                                        <?php endif; ?>
                                        <td class="small"><?= date('d M Y, h:i A', strtotime($s['created_at'])) ?></td>
                                        <td class="small">Sem <?= $s['semester'] ?> (<?= htmlspecialchars($s['section']) ?>)</td>
                                        <td><strong class="text-accent letter-spacing-1"><?= htmlspecialchars($s['session_pin'] ?? '-') ?></strong></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                                <span class="badge bg-light text-dark text-uppercase small"><?= htmlspecialchars($s['session_type'] ?? 'PIN') ?></span>
                                                <?php if (!empty($s['allowed_latitude'])): ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle small font-weight-600" title="Geofence Distance Radius">
                                                        <i class="fas fa-location-dot me-1"></i> ≤ <?= $s['location_radius'] ?: 100 ?>m
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><strong class="text-success"><?= $s['present_count'] ?> Students</strong></td>
                                        <td>
                                            <?php if ($s['status'] === 'active' && strtotime($s['expires_at']) > time()): ?>
                                                <span class="badge bg-success text-white">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Closed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="<?= APP_URL ?>/staff/attendance_records.php?session_id=<?= $s['id'] ?>" class="btn btn-sm btn-icon btn-outline-info rounded-circle" title="View Live Attendance Monitor" aria-label="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger rounded-circle" title="Delete Attendance Session" aria-label="Delete" onclick="deleteSession(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['subject_code'])) ?>', '<?= date('d M Y, h:i A', strtotime($s['created_at'])) ?>', <?= (int)$s['present_count'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
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

<!-- Delete Attendance Session Confirmation Modal -->
<div class="modal fade" id="deleteSessionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i> Delete Attendance Session
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="session_id" id="deleteSessionId">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <p class="mb-2">Are you sure you want to permanently delete the attendance session for <strong id="deleteSessionSubject"></strong> created on <strong id="deleteSessionDate"></strong>?</p>
                    <div class="alert alert-warning py-2 px-3 small mb-0 rounded-3 border-0 bg-warning-light text-dark">
                        <i class="fas fa-info-circle text-warning me-1"></i>
                        This will permanently remove the session and all student check-ins (<span id="deleteSessionRecords" class="font-weight-bold">0</span> records). This action cannot be undone.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 font-weight-600">
                        <i class="fas fa-trash me-1"></i> Delete Permanently
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteSession(id, subjectCode, dateStr, count) {
    document.getElementById('deleteSessionId').value = id;
    document.getElementById('deleteSessionSubject').innerText = subjectCode;
    document.getElementById('deleteSessionDate').innerText = dateStr;
    document.getElementById('deleteSessionRecords').innerText = count || 0;
    new bootstrap.Modal(document.getElementById('deleteSessionModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
