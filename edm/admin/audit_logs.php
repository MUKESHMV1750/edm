<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_ADMIN]);

$db = getDB();

$logs = $db->query("
    SELECT al.*, COALESCE(al.description, '') AS details, u.username, u.email, u.role
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 100
")->fetchAll();

$pageTitle = "System Audit Logs";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">System Audit Trail & Security Logs</h1>
                    <p class="text-muted small">Immutable log of user logins, role modifications, and system configuration updates</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $l): ?>
                                <tr>
                                    <td class="small"><?= date('d M Y, h:i:s A', strtotime($l['created_at'])) ?></td>
                                    <td><strong><?= htmlspecialchars($l['username'] ?? 'System') ?></strong></td>
                                    <td><span class="badge bg-light text-dark font-weight-600 text-uppercase small"><?= htmlspecialchars($l['role'] ?? 'System') ?></span></td>
                                    <td><span class="badge bg-primary-lightest text-primary font-weight-600"><?= htmlspecialchars($l['action'] ?? '') ?></span></td>
                                    <td class="small"><?= htmlspecialchars($l['description'] ?? ($l['details'] ?? '—')) ?></td>
                                    <td class="text-muted text-xs"><?= htmlspecialchars($l['ip_address'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
