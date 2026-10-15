<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STAFF]);

$staffId = $_SESSION['role_id'] ?? 0;
$assignmentId = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
$db = getDB();

// Handle Grade Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $submission_id = (int)$_POST['submission_id'];
        $marks_obtained = (float)$_POST['marks_obtained'];
        $feedback = sanitizeInput($_POST['feedback']);

        $upd = $db->prepare("
            UPDATE assignment_submissions 
            SET marks_obtained = ?, feedback = ?, status = 'graded'
            WHERE id = ?
        ");
        $upd->execute([$marks_obtained, $feedback, $submission_id]);

        setFlashMessage('success', 'Marks and feedback saved successfully!');
        redirect("staff/review_submissions.php?assignment_id={$assignmentId}");
    }
}

// Fetch Assignment
$assStmt = $db->prepare("
    SELECT a.*, sub.code AS subject_code, sub.name AS subject_name
    FROM assignments a
    JOIN subjects sub ON a.subject_id = sub.id
    WHERE a.id = ? AND a.staff_id = ?
");
$assStmt->execute([$assignmentId, $staffId]);
$assignment = $assStmt->fetch();

if (!$assignment) {
    setFlashMessage('danger', 'Assignment not found.');
    redirect('staff/manage_assignments.php');
}

// Fetch Submissions
$subStmt = $db->prepare("
    SELECT 
        submiss.*,
        st.register_number, st.first_name, st.last_name
    FROM assignment_submissions submiss
    JOIN students st ON submiss.student_id = st.id
    WHERE submiss.assignment_id = ?
    ORDER BY submiss.submitted_at DESC
");
$subStmt->execute([$assignmentId]);
$submissions = $subStmt->fetchAll();

$pageTitle = "Review Submissions: " . $assignment['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="mb-4">
                <a href="<?= APP_URL ?>/staff/manage_assignments.php" class="text-muted small text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i> Back to Assignments List
                </a>
                <h1 class="h3 font-weight-bold text-dark mt-2 mb-0"><?= htmlspecialchars($assignment['title']) ?></h1>
                <span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1 mt-2">
                    <?= htmlspecialchars($assignment['subject_code']) ?> - <?= htmlspecialchars($assignment['subject_name']) ?> (Max Marks: <?= $assignment['max_marks'] ?>)
                </span>
            </div>

            <?php displayAlerts(); ?>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Register No</th>
                                <th>Student Name</th>
                                <th>Submitted Date</th>
                                <th>Attachment</th>
                                <th>Status</th>
                                <th>Grade & Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($submissions)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No student submissions received for this assignment.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($submissions as $s): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($s['register_number']) ?></strong></td>
                                        <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                                        <td class="small"><?= date('d M Y, h:i A', strtotime($s['submitted_at'])) ?></td>
                                        <td>
                                            <?php if (!empty($s['file_path'])): ?>
                                                <a href="<?= APP_URL . '/' . $s['file_path'] ?>" class="btn btn-sm btn-outline-primary rounded-pill" target="_blank">
                                                    <i class="fas fa-paperclip me-1"></i> Download
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">Text Response Only</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($s['status'] === 'graded'): ?>
                                                <span class="badge bg-success-light text-success font-weight-600">Graded (<?= $s['marks_obtained'] ?>/<?= $assignment['max_marks'] ?>)</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-light text-warning font-weight-600">Pending Review</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-success rounded-pill px-3 font-weight-600" data-bs-toggle="modal" data-bs-target="#gradeModal<?= $s['id'] ?>">
                                                <?= $s['status'] === 'graded' ? 'Update Marks' : 'Grade Now' ?>
                                            </button>
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

<!-- Grade Modals (outside wrapper and table) -->
<?php if (!empty($submissions)): ?>
    <?php foreach ($submissions as $s): ?>
        <div class="modal fade" id="gradeModal<?= $s['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content" style="border-radius: 20px;">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title font-weight-bold text-dark">Grade Student: <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="" method="POST">
                        <div class="modal-body p-4">
                            <?= csrfField() ?>
                            <input type="hidden" name="submission_id" value="<?= $s['id'] ?>">

                            <?php if (!empty($s['submission_text'])): ?>
                                <div class="mb-3 bg-light p-3 rounded-3">
                                    <label class="form-label text-muted small font-weight-600">Student Response Notes:</label>
                                    <p class="small text-dark mb-0"><?= nl2br(htmlspecialchars($s['submission_text'])) ?></p>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="marks_obtained_<?= $s['id'] ?>" class="form-label font-weight-500">Marks Obtained (Max <?= $assignment['max_marks'] ?>)</label>
                                <input type="number" step="0.5" id="marks_obtained_<?= $s['id'] ?>" name="marks_obtained" class="form-control" max="<?= $assignment['max_marks'] ?>" value="<?= $s['marks_obtained'] ?? '' ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="feedback_<?= $s['id'] ?>" class="form-label font-weight-500">Faculty Remarks / Feedback</label>
                                <textarea id="feedback_<?= $s['id'] ?>" name="feedback" class="form-control" rows="3" placeholder="Good work, well presented code..."><?= htmlspecialchars($s['feedback'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success rounded-pill px-4 font-weight-600">Save Grade</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
