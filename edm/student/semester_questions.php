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

// Fetch Semester Question Papers
$qStmt = $db->prepare("
    SELECT qp.*, sub.code AS subject_code, sub.name AS subject_name
    FROM question_papers qp
    JOIN subjects sub ON qp.subject_id = sub.id
    WHERE sub.department_id = ? AND sub.semester = ? AND qp.exam_type = 'semester'
    ORDER BY qp.academic_year DESC, sub.code ASC
");
$qStmt->execute([$student['department_id'], $student['semester']]);
$questions = $qStmt->fetchAll();

$pageTitle = "Semester End Question Papers";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">End-Semester Exam Question Papers</h1>
                    <p class="text-muted small">Comprehensive repository of university end-semester examination question papers</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th>Academic Year</th>
                                <th>Title / Description</th>
                                <th>Download Paper</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($questions)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No semester question papers uploaded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($questions as $q): ?>
                                    <tr>
                                        <td><span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1"><?= htmlspecialchars($q['subject_code']) ?></span></td>
                                        <td><strong><?= htmlspecialchars($q['subject_name']) ?></strong></td>
                                        <td><?= htmlspecialchars($q['academic_year']) ?></td>
                                        <td><?= htmlspecialchars($q['title']) ?></td>
                                        <td>
                                            <a href="<?= APP_URL . '/' . $q['file_path'] ?>" class="btn btn-sm btn-outline-primary rounded-pill font-weight-600" target="_blank">
                                                <i class="fas fa-file-pdf me-1 text-danger"></i> Download Paper
                                            </a>
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
