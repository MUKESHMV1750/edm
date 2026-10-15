<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';
checkRole([ROLE_STAFF]);

$staffId = $_SESSION['role_id'] ?? 0;
$db = getDB();

$presStmt = $db->prepare("
    SELECT ps.*, COALESCE(ps.pin_code, ps.session_code, '') AS session_pin, p.title, COALESCE(p.total_pages, 0) AS total_slides, p.total_pages, sub.code AS subject_code,
           (SELECT COUNT(*) FROM presentation_participants pv WHERE pv.session_id = ps.id) AS total_viewers
    FROM presentation_sessions ps
    JOIN presentations p ON ps.presentation_id = p.id
    JOIN subjects sub ON p.subject_id = sub.id
    WHERE p.staff_id = ?
    ORDER BY ps.started_at DESC
");
$presStmt->execute([$staffId]);
$sessions = $presStmt->fetchAll();

$pageTitle = "Presentation Engagement Report";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Presentation Session Engagement Report</h1>
                    <p class="text-muted small">Broadcast session history, connected student counts, and status overview</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Presentation Title</th>
                                <th>Started At</th>
                                <th>Session PIN</th>
                                <th>Total Viewers</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sessions)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No broadcast sessions recorded.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sessions as $s): ?>
                                    <tr>
                                        <td><span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1"><?= htmlspecialchars($s['subject_code']) ?></span></td>
                                        <td><strong><?= htmlspecialchars($s['title']) ?></strong></td>
                                        <td class="small"><?= date('d M Y, h:i A', strtotime($s['started_at'])) ?></td>
                                        <td><strong class="text-accent letter-spacing-1"><?= htmlspecialchars($s['session_pin'] ?? $s['pin_code'] ?? '') ?></strong></td>
                                        <td><strong class="text-success"><?= $s['total_viewers'] ?> Students</strong></td>
                                        <td>
                                            <?php if ($s['status'] === 'active'): ?>
                                                <span class="badge bg-success text-white">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Ended</span>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
