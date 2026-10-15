<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STUDENT]);

$studentId = $_SESSION['role_id'] ?? 0;
$db = getDB();

$stStmt = $db->prepare("SELECT department_id, semester FROM students WHERE id = ?");
$stStmt->execute([$studentId]);
$student = $stStmt->fetch();

// Fetch Active Live Sessions
$presStmt = $db->prepare("
    SELECT 
        ps.*, 
        ps.current_page AS current_slide,
        p.title AS presentation_title,
        p.description AS presentation_desc,
        COALESCE(p.total_pages, 0) AS total_slides,
        p.total_pages,
        sub.name AS subject_name, 
        sub.code AS subject_code,
        st.first_name, st.last_name
    FROM presentation_sessions ps
    JOIN presentations p ON ps.presentation_id = p.id
    JOIN subjects sub ON p.subject_id = sub.id
    JOIN staff st ON p.staff_id = st.id
    WHERE sub.department_id = ? AND sub.semester = ? AND ps.status = 'active'
    ORDER BY ps.started_at DESC
");
$presStmt->execute([$student['department_id'], $student['semester']]);
$liveSessions = $presStmt->fetchAll();

$pageTitle = "Live Class Presentations";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Live Faculty Presentations</h1>
                    <p class="text-muted small">Join live slide broadcasts synchronized with faculty presentations in real-time</p>
                </div>
            </div>

            <?php displayAlerts(); ?>

            <!-- Manual Session Code Join Box -->
            <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 16px; background: linear-gradient(135deg, #0F172A 0%, #1E3A8A 100%); color: #fff;">
                <div class="row align-items-center">
                    <div class="col-md-7 mb-3 mb-md-0">
                        <h4 class="h5 font-weight-bold mb-1"><i class="fas fa-broadcast-tower text-accent me-2"></i> Join via Session PIN</h4>
                        <p class="text-white-50 small mb-0">Have a 6-digit session PIN from your professor? Enter it below to jump straight into the live session.</p>
                    </div>
                    <div class="col-md-5">
                        <form action="<?= APP_URL ?>/student/join_presentation.php" method="POST" class="d-flex gap-2">
                            <?= csrfField() ?>
                            <input type="text" name="pin" class="form-control form-control-lg text-center font-weight-bold letter-spacing-2" placeholder="e.g. 839201" maxlength="8" required>
                            <button type="submit" class="btn btn-accent font-weight-600 px-4">
                                Join <i class="fas fa-play ms-1"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <?php if (empty($liveSessions)): ?>
                    <div class="col-12 text-center py-5">
                        <div class="rounded-circle bg-light text-muted mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; font-size: 32px;">
                            <i class="fas fa-video-slash"></i>
                        </div>
                        <h5 class="text-muted">No live presentation sessions currently broadcasting.</h5>
                        <p class="text-muted small">Active sessions will appear here as soon as your professor starts presenting.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($liveSessions as $ls): ?>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                                <div class="card-body p-4 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1"><?= htmlspecialchars($ls['subject_code']) ?></span>
                                            <span class="badge bg-danger text-white px-2 py-1 rounded-pill animate-pulse">
                                                <i class="fas fa-circle fs-6 me-1"></i> LIVE NOW
                                            </span>
                                        </div>
                                        <h4 class="h5 font-weight-bold text-dark mb-1"><?= htmlspecialchars($ls['presentation_title']) ?></h4>
                                        <p class="text-muted small mb-3"><?= htmlspecialchars($ls['presentation_desc']) ?></p>
                                    </div>
                                    <div class="border-top pt-3 mt-3 d-flex align-items-center justify-content-between">
                                        <div class="small text-muted">
                                            <div>Prof. <?= htmlspecialchars($ls['first_name'] . ' ' . $ls['last_name']) ?></div>
                                            <div>Current Slide: <strong>#<?= $ls['current_slide'] ?></strong> / <?= $ls['total_slides'] ?></div>
                                        </div>
                                        <a href="<?= APP_URL ?>/student/presentation_viewer.php?session_id=<?= $ls['id'] ?>" class="btn btn-primary rounded-pill px-4 font-weight-600">
                                            Join Live Viewer <i class="fas fa-sign-in-alt ms-1"></i>
                                        </a>
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
