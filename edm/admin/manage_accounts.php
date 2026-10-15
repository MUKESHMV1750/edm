<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_ADMIN]);

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $userId = (int)$_POST['user_id'];
        $new_status = sanitizeInput($_POST['status']);

        $upd = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $upd->execute([$new_status, $userId]);

        logAudit($_SESSION['user_id'], 'USER_STATUS_CHANGE', "User #{$userId} status changed to {$new_status}");
        setFlashMessage('success', 'User account status updated.');
        redirect('admin/manage_accounts.php');
    }
}

$users = $db->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();

$pageTitle = "User Account Management";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">User Account Oversight</h1>
                    <p class="text-muted small">Enable, disable, or suspend user access across the system</p>
                </div>
            </div>

            <?php displayAlerts(); ?>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Last Login</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td>#<?= $u['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td><span class="badge bg-light text-dark text-uppercase small"><?= htmlspecialchars($u['role']) ?></span></td>
                                    <td class="small"><?= !empty($u['last_login']) ? date('d M Y, h:i A', strtotime($u['last_login'])) : 'Never' ?></td>
                                    <td>
                                        <?php if ($u['status'] === 'active'): ?>
                                            <span class="badge bg-success text-white font-weight-600">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger font-weight-600">Inactive / Suspended</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                            <form action="" method="POST" class="d-inline">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <input type="hidden" name="status" value="<?= $u['status'] === 'active' ? 'suspended' : 'active' ?>">
                                                <button type="submit" class="btn btn-sm <?= $u['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?> rounded-pill px-3 font-weight-600">
                                                    <?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">Current Admin</span>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
