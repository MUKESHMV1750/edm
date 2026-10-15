<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STUDENT]);

$studentId = $_SESSION['role_id'] ?? 0;
$assignmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = getDB();

// Fetch Assignment
$stmt = $db->prepare("
    SELECT a.*, sub.name AS subject_name, sub.code AS subject_code, st.first_name, st.last_name
    FROM assignments a
    JOIN subjects sub ON a.subject_id = sub.id
    JOIN staff st ON a.staff_id = st.id
    WHERE a.id = ?
");
$stmt->execute([$assignmentId]);
$assignment = $stmt->fetch();

if (!$assignment) {
    setFlashMessage('danger', 'Assignment not found.');
    redirect('student/assignments.php');
}

// Fetch Existing Submission
$subStmt = $db->prepare("SELECT * FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
$subStmt->execute([$assignmentId, $studentId]);
$submission = $subStmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $submission_text = sanitizeInput($_POST['submission_text'] ?? '');
        $file_path = $submission['file_path'] ?? null;

        // Handle File Upload
        if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/assignments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = time() . '_' . basename($_FILES['submission_file']['name']);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $targetPath)) {
                $file_path = 'uploads/assignments/' . $fileName;
            }
        }

        if ($submission) {
            // Update
            $upd = $db->prepare("
                UPDATE assignment_submissions 
                SET submission_text = ?, file_path = ?, submitted_at = NOW(), status = 'submitted'
                WHERE id = ?
            ");
            $upd->execute([$submission_text, $file_path, $submission['id']]);
        } else {
            // Insert
            $ins = $db->prepare("
                INSERT INTO assignment_submissions (assignment_id, student_id, submission_text, file_path, status)
                VALUES (?, ?, ?, ?, 'submitted')
            ");
            $ins->execute([$assignmentId, $studentId, $submission_text, $file_path]);
        }

        setFlashMessage('success', 'Assignment submitted successfully!');
        redirect("student/submit_assignment.php?id={$assignmentId}");
    }
}

$pageTitle = "Submit Assignment";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="mb-4">
                <a href="<?= APP_URL ?>/student/assignments.php" class="text-muted small text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i> Back to Assignments
                </a>
                <h1 class="h3 font-weight-bold text-dark mt-2 mb-0"><?= htmlspecialchars($assignment['title']) ?></h1>
                <span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1 mt-2">
                    <?= htmlspecialchars($assignment['subject_code']) ?> - <?= htmlspecialchars($assignment['subject_name']) ?>
                </span>
            </div>

            <?php displayAlerts(); ?>

            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
                        <h5 class="h6 font-weight-bold text-dark mb-3"><i class="fas fa-info-circle text-primary me-2"></i> Instructions</h5>
                        <p class="text-muted small"><?= nl2br(htmlspecialchars($assignment['description'])) ?></p>
                        
                        <hr class="my-3">

                        <div class="small">
                            <div class="mb-2"><strong>Faculty:</strong> Prof. <?= htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']) ?></div>
                            <div class="mb-2"><strong>Max Marks:</strong> <?= $assignment['max_marks'] ?> points</div>
                            <div class="mb-2"><strong>Due Date:</strong> <?= date('d M Y, h:i A', strtotime($assignment['due_date'])) ?></div>
                            <?php if (!empty($assignment['file_path'])): ?>
                                <div class="mt-3">
                                    <a href="<?= APP_URL . '/' . $assignment['file_path'] ?>" class="btn btn-outline-primary btn-sm rounded-pill" target="_blank">
                                        <i class="fas fa-download me-1"></i> Download Reference Attachment
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
                        <h5 class="h6 font-weight-bold text-dark mb-4"><i class="fas fa-upload text-success me-2"></i> Submission Form</h5>

                        <?php if ($submission && $submission['status'] === 'graded'): ?>
                            <div class="alert alert-success mb-4" style="border-radius: 12px;">
                                <h6 class="font-weight-bold mb-1"><i class="fas fa-award me-1"></i> Submission Graded</h6>
                                <p class="mb-1">Score: <strong><?= $submission['marks_obtained'] ?> / <?= $assignment['max_marks'] ?></strong></p>
                                <?php if (!empty($submission['feedback'])): ?>
                                    <p class="mb-0 small text-dark">Faculty Feedback: <em>"<?= htmlspecialchars($submission['feedback']) ?>"</em></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST" enctype="multipart/form-data">
                            <?= csrfField() ?>

                            <div class="mb-3">
                                <label for="submission_text" class="form-label font-weight-500">Submission Notes / Code / Text Response</label>
                                <textarea id="submission_text" name="submission_text" class="form-control" rows="6" placeholder="Enter your response, solution summary, or code links here..."><?= htmlspecialchars($submission['submission_text'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label for="submission_file" class="form-label font-weight-500">Upload Attachment (PDF, ZIP, DOCX)</label>
                                <input type="file" id="submission_file" name="submission_file" class="form-control">
                                <?php if (!empty($submission['file_path'])): ?>
                                    <div class="form-text text-success small mt-2">
                                        <i class="fas fa-paperclip me-1"></i> Current file: <a href="<?= APP_URL . '/' . $submission['file_path'] ?>" target="_blank">Download Submitted File</a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <button type="submit" class="btn btn-primary rounded-pill px-4 font-weight-600" <?= ($submission && $submission['status'] === 'graded') ? 'disabled' : '' ?>>
                                <?= $submission ? 'Update Submission' : 'Submit Assignment' ?> <i class="fas fa-paper-plane ms-2"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
