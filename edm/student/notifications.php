<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STUDENT]);

$userId = $_SESSION['user_id'] ?? 0;
$studentId = $_SESSION['role_id'] ?? 0;
$db = getDB();

$stStmt = $db->prepare("SELECT id, department_id, semester FROM students WHERE id = ?");
$stStmt->execute([$studentId]);
$student = $stStmt->fetch();

if (!$student && !empty($_SESSION['user_id'])) {
    $stUserStmt = $db->prepare("SELECT id, department_id, semester FROM students WHERE user_id = ?");
    $stUserStmt->execute([$_SESSION['user_id']]);
    $student = $stUserStmt->fetch();
    if ($student) {
        $studentId = (int)$student['id'];
        $_SESSION['role_id'] = $studentId;
    }
}

if (!$userId && $studentId) {
    $uStmt = $db->prepare("SELECT user_id FROM students WHERE id = ?");
    $uStmt->execute([$studentId]);
    $userId = (int)$uStmt->fetchColumn();
}

// Fetch Notifications for this user
$notifStmt = $db->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? OR user_id = 0
    ORDER BY created_at DESC
");
$notifStmt->execute([$userId]);
$notifications = $notifStmt->fetchAll();

// Mark as read
if ($userId > 0) {
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$userId]);
}

$pageTitle = "Department Notifications";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Notifications & Announcements</h1>
                    <p class="text-muted small mb-0">Department notices, circulars, exam updates & timetable announcements</p>
                </div>
                <button type="button" class="btn btn-outline-secondary rounded-pill btn-sm" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>

            <?php displayAlerts(); ?>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" style="border-radius: 16px;">
                        <?php if (empty($notifications)): ?>
                            <div class="p-5 text-center text-muted">
                                <i class="fas fa-bell-slash display-4 mb-3 d-block text-muted opacity-50"></i>
                                <h5 class="h6 font-weight-bold text-dark mb-1">No notifications yet</h5>
                                <p class="text-muted small mb-0">You're all caught up! New circulars and alerts will appear here.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $n): 
                                $type = $n['type'] ?? 'info';
                                $icon = 'fa-bullhorn';
                                $bgClass = 'bg-primary-subtle text-primary';
                                
                                switch ($type) {
                                    case 'attendance':
                                        $icon = 'fa-user-check';
                                        $bgClass = 'bg-success-subtle text-success';
                                        break;
                                    case 'presentation':
                                        $icon = 'fa-desktop';
                                        $bgClass = 'bg-danger-subtle text-danger';
                                        break;
                                    case 'assignment':
                                        $icon = 'fa-tasks';
                                        $bgClass = 'bg-warning-subtle text-warning';
                                        break;
                                    case 'material':
                                        $icon = 'fa-file-alt';
                                        $bgClass = 'bg-info-subtle text-info';
                                        break;
                                    case 'warning':
                                        $icon = 'fa-exclamation-triangle';
                                        $bgClass = 'bg-warning-subtle text-warning';
                                        break;
                                    case 'danger':
                                        $icon = 'fa-bell';
                                        $bgClass = 'bg-danger-subtle text-danger';
                                        break;
                                    case 'success':
                                        $icon = 'fa-check-circle';
                                        $bgClass = 'bg-success-subtle text-success';
                                        break;
                                    default:
                                        $icon = 'fa-bullhorn';
                                        $bgClass = 'bg-primary-subtle text-primary';
                                        break;
                                }
                            ?>
                                <div class="list-group-item p-4 border-bottom">
                                    <div class="d-flex align-items-start">
                                        <div class="rounded-circle <?= $bgClass ?> p-3 me-3 flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 20px;">
                                            <i class="fas <?= $icon ?>"></i>
                                        </div>
                                        <div class="w-100">
                                            <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge <?= $bgClass ?> text-uppercase px-2 py-1" style="font-size: 11px;">
                                                        <?= htmlspecialchars($type) ?>
                                                    </span>
                                                    <h5 class="h6 font-weight-bold text-dark mb-0"><?= htmlspecialchars($n['title']) ?></h5>
                                                </div>
                                                <span class="text-muted small">
                                                    <i class="far fa-clock me-1"></i><?= date('d M Y, h:i A', strtotime($n['created_at'])) ?>
                                                </span>
                                            </div>
                                            <p class="text-muted small mb-2"><?= nl2br(htmlspecialchars($n['message'])) ?></p>
                                            <?php if (!empty($n['link'])): ?>
                                                <a href="<?= htmlspecialchars($n['link']) ?>" class="btn btn-sm btn-outline-primary rounded-pill font-weight-600 px-3 py-1">
                                                    View Details <i class="fas fa-arrow-right ms-1"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
