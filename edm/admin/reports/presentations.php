<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';
checkRole([ROLE_ADMIN]);

$db = getDB();

$sessions = $db->query("
    SELECT ps.*, COALESCE(ps.pin_code, ps.session_code, '') AS session_pin, p.title, sub.code AS subject_code, st.first_name, st.last_name,
           (SELECT COUNT(*) FROM presentation_viewers pv WHERE pv.session_id = ps.id) AS viewer_count
    FROM presentation_sessions ps
    JOIN presentations p ON ps.presentation_id = p.id
    JOIN subjects sub ON p.subject_id = sub.id
    JOIN staff st ON p.staff_id = st.id
    ORDER BY ps.started_at DESC
")->fetchAll();

$pageTitle = "Admin Presentations Report";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Live Presentations Broadcast Log</h1>
                    <p class="text-muted small">Engagement rates and viewer turnouts for faculty presentation sessions</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Presentation Title</th>
                                <th>Faculty</th>
                                <th>PIN</th>
                                <th>Viewers</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $s): ?>
                                <tr>
                                    <td><span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1"><?= htmlspecialchars($s['subject_code']) ?></span></td>
                                    <td><strong><?= htmlspecialchars($s['title']) ?></strong></td>
                                    <td>Prof. <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                                    <td><strong class="text-accent letter-spacing-1"><?= htmlspecialchars($s['session_pin'] ?? $s['pin_code'] ?? '') ?></strong></td>
                                    <td><strong class="text-success"><?= $s['viewer_count'] ?> Students</strong></td>
                                    <td>
                                        <?php if ($s['status'] === 'active'): ?>
                                            <span class="badge bg-success text-white">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Ended</span>
                                        <?php endif; ?>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
