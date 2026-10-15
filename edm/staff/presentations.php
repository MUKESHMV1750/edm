<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STAFF]);

$staffId = $_SESSION['role_id'] ?? 0;
$db = getDB();

// Fetch Presentations
$pStmt = $db->prepare("
    SELECT p.*, COALESCE(p.total_pages, 0) AS total_slides, sub.code AS subject_code, sub.name AS subject_name,
           (SELECT id FROM presentation_sessions ps WHERE ps.presentation_id = p.id AND ps.status = 'active' LIMIT 1) AS active_session_id
    FROM presentations p
    JOIN subjects sub ON p.subject_id = sub.id
    WHERE p.staff_id = ?
    ORDER BY p.created_at DESC
");
$pStmt->execute([$staffId]);
$presentations = $pStmt->fetchAll();

$pageTitle = "Faculty Presentation Decks";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Faculty Presentation Decks</h1>
                    <p class="text-muted small">Manage slide decks, launch live sessions, and view participant logs</p>
                </div>
                <a href="<?= APP_URL ?>/staff/create_presentation.php" class="btn btn-primary rounded-pill btn-sm px-4 font-weight-600">
                    <i class="fas fa-upload me-1"></i> Upload Deck
                </a>
            </div>

            <?php displayAlerts(); ?>

            <div class="row g-4">
                <?php if (empty($presentations)): ?>
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-desktop text-muted display-4 mb-3 d-block"></i>
                        <h5 class="text-muted">No presentation decks created yet.</h5>
                    </div>
                <?php else: ?>
                    <?php foreach ($presentations as $p): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                                <div class="card-body p-4 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1"><?= htmlspecialchars($p['subject_code']) ?></span>
                                            <span class="badge bg-light text-dark font-weight-600"><?= $p['total_slides'] ?> Slides</span>
                                        </div>
                                        <h5 class="h6 font-weight-bold text-dark mb-1"><?= htmlspecialchars($p['title']) ?></h5>
                                        <p class="text-muted small mb-3"><?= htmlspecialchars($p['description']) ?></p>
                                    </div>
                                    <div class="border-top pt-3 mt-3 d-flex align-items-center justify-content-between">
                                        <?php if (!empty($p['active_session_id'])): ?>
                                            <a href="<?= APP_URL ?>/staff/live_presentation.php?session_id=<?= $p['active_session_id'] ?>" class="btn btn-danger btn-sm rounded-pill font-weight-600 w-100">
                                                Resume Live Broadcast <i class="fas fa-play ms-1"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= APP_URL ?>/api/presentation_session.php?action=start_session&presentation_id=<?= $p['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill font-weight-600 w-100">
                                                Start Live Session <i class="fas fa-broadcast-tower ms-1"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
