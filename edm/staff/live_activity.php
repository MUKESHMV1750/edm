<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STAFF]);

$staffId = $_SESSION['role_id'] ?? 0;
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$db = getDB();

// Fetch session info
$sesStmt = $db->prepare("
    SELECT ps.*, p.title, sub.code AS subject_code
    FROM presentation_sessions ps
    JOIN presentations p ON ps.presentation_id = p.id
    JOIN subjects sub ON p.subject_id = sub.id
    WHERE ps.id = ? AND p.staff_id = ?
");
$sesStmt->execute([$sessionId, $staffId]);
$session = $sesStmt->fetch();

if (!$session) {
    setFlashMessage('danger', 'Session not found.');
    redirect('staff/presentations.php');
}

// Fetch connected viewers
$viewersStmt = $db->prepare("
    SELECT pv.*, st.register_number, st.first_name, st.last_name
    FROM presentation_viewers pv
    JOIN students st ON pv.student_id = st.id
    WHERE pv.session_id = ?
    ORDER BY pv.last_active DESC
");
$viewersStmt->execute([$sessionId]);
$viewers = $viewersStmt->fetchAll();

$pageTitle = "Viewer Activity Log";
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
                    <h1 class="h3 font-weight-bold text-dark mb-0">Viewer Activity Log: <?= htmlspecialchars($session['title']) ?></h1>
                    <p class="text-muted small">Real-time student participation, current active slide, and connection status</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Register No</th>
                                <th>Student Name</th>
                                <th>Joined Time</th>
                                <th>Last Active</th>
                                <th>Viewing Slide #</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($viewers)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No students currently connected to this live broadcast.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($viewers as $v): ?>
                                    <?php 
                                        $isActive = (time() - strtotime($v['last_active'])) < 30;
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($v['register_number']) ?></strong></td>
                                        <td><?= htmlspecialchars($v['first_name'] . ' ' . $v['last_name']) ?></td>
                                        <td class="small"><?= date('h:i:s A', strtotime($v['joined_at'])) ?></td>
                                        <td class="small"><?= date('h:i:s A', strtotime($v['last_active'])) ?></td>
                                        <td><strong>#<?= $v['current_slide'] ?></strong></td>
                                        <td>
                                            <?php if ($isActive): ?>
                                                <span class="badge bg-success-light text-success font-weight-600"><i class="fas fa-circle me-1 fs-6"></i> Online</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary font-weight-600">Idle / Left</span>
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
