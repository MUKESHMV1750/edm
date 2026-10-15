<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';
checkRole([ROLE_STAFF]);

$staffId = $_SESSION['role_id'] ?? 0;
$db = getDB();

$assStmt = $db->prepare("
    SELECT a.*, sub.code AS subject_code, sub.name AS subject_name,
           (SELECT COUNT(*) FROM assignment_submissions submiss WHERE submiss.assignment_id = a.id) AS total_submissions,
           (SELECT COUNT(*) FROM assignment_submissions submiss WHERE submiss.assignment_id = a.id AND submiss.status = 'graded') AS graded_count
    FROM assignments a
    JOIN subjects sub ON a.subject_id = sub.id
    WHERE a.staff_id = ?
    ORDER BY a.due_date DESC
");
$assStmt->execute([$staffId]);
$assignments = $assStmt->fetchAll();

$pageTitle = "Assignment Analytics Report";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Assignment Performance & Submissions Report</h1>
                    <p class="text-muted small">Coursework evaluation progress and submission rates</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Title</th>
                                <th>Due Date</th>
                                <th>Max Marks</th>
                                <th>Submissions</th>
                                <th>Graded</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($assignments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No assignments data.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($assignments as $a): ?>
                                    <tr>
                                        <td><span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1"><?= htmlspecialchars($a['subject_code']) ?></span></td>
                                        <td><strong><?= htmlspecialchars($a['title']) ?></strong></td>
                                        <td class="small"><?= date('d M Y, h:i A', strtotime($a['due_date'])) ?></td>
                                        <td><?= $a['max_marks'] ?> pts</td>
                                        <td><span class="badge bg-info-light text-info font-weight-600"><?= $a['total_submissions'] ?></span></td>
                                        <td><span class="badge bg-success-light text-success font-weight-600"><?= $a['graded_count'] ?></span></td>
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
