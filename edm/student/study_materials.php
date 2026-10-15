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

// Fetch Study Materials
$matStmt = $db->prepare("
    SELECT sm.*, sub.code AS subject_code, sub.name AS subject_name, st.first_name, st.last_name
    FROM study_materials sm
    JOIN subjects sub ON sm.subject_id = sub.id
    JOIN staff st ON sm.staff_id = st.id
    WHERE sub.department_id = ? AND sub.semester = ?
    ORDER BY sm.uploaded_at DESC
");
$matStmt->execute([$student['department_id'], $student['semester']]);
$materials = $matStmt->fetchAll();

$pageTitle = "Study Materials & Notes";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Study Materials & Notes</h1>
                    <p class="text-muted small">Access lecture slide decks, syllabus PDFs & code samples uploaded by faculty</p>
                </div>
            </div>

            <div class="row g-4">
                <?php if (empty($materials)): ?>
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-folder-open text-muted display-4 mb-3 d-block"></i>
                        <h5 class="text-muted">No study materials uploaded for this semester yet.</h5>
                    </div>
                <?php else: ?>
                    <?php foreach ($materials as $m): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                                <div class="card-body p-4 d-flex flex-column justify-content-between">
                                    <div>
                                        <span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1 mb-2">
                                            <?= htmlspecialchars($m['subject_code']) ?>
                                        </span>
                                        <h5 class="h6 font-weight-bold text-dark mb-1"><?= htmlspecialchars($m['title']) ?></h5>
                                        <p class="text-muted small mb-3"><?= htmlspecialchars($m['description']) ?></p>
                                    </div>
                                    <div class="border-top pt-3 mt-3 d-flex align-items-center justify-content-between">
                                        <div class="small text-muted">
                                            <div>Prof. <?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?></div>
                                            <div class="text-xs"><?= date('d M Y', strtotime($m['uploaded_at'])) ?></div>
                                        </div>
                                        <a href="<?= APP_URL . '/' . $m['file_path'] ?>" class="btn btn-outline-primary btn-sm rounded-pill font-weight-600" target="_blank">
                                            Download <i class="fas fa-download ms-1"></i>
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
