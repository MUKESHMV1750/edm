<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STAFF]);

$staffId = $_SESSION['role_id'] ?? 0;
$db = getDB();

// Handle Create Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $subject_id = (int)$_POST['subject_id'];
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $max_marks = (int)$_POST['max_marks'];
        $due_date = $_POST['due_date'];
        $file_path = null;

        // Attachment handling
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/assignments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = time() . '_' . basename($_FILES['attachment']['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
                $file_path = 'uploads/assignments/' . $fileName;
            }
        }

        $ins = $db->prepare("
            INSERT INTO assignments (staff_id, subject_id, title, description, max_marks, due_date, file_path)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([$staffId, $subject_id, $title, $description, $max_marks, $due_date, $file_path]);

        setFlashMessage('success', 'Assignment published successfully!');
        redirect('staff/manage_assignments.php');
    }
}

// Fetch Staff Subjects
$subStmt = $db->prepare("SELECT * FROM subjects WHERE staff_id = ? ORDER BY code ASC");
$subStmt->execute([$staffId]);
$subjects = $subStmt->fetchAll();

// Fetch Assignments created by staff
$assStmt = $db->prepare("
    SELECT a.*, sub.code AS subject_code, sub.name AS subject_name,
           (SELECT COUNT(*) FROM assignment_submissions submiss WHERE submiss.assignment_id = a.id) AS submission_count
    FROM assignments a
    JOIN subjects sub ON a.subject_id = sub.id
    WHERE a.staff_id = ?
    ORDER BY a.created_at DESC
");
$assStmt->execute([$staffId]);
$assignments = $assStmt->fetchAll();

$pageTitle = "Manage Course Assignments";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Course Assignments</h1>
                    <p class="text-muted small">Create new assignments, attach reference files, and manage student submissions</p>
                </div>
                <button type="button" class="btn btn-success rounded-pill btn-sm px-4 font-weight-600" data-bs-toggle="modal" data-bs-target="#newAssignmentModal">
                    <i class="fas fa-plus me-1"></i> New Assignment
                </button>
            </div>

            <?php displayAlerts(); ?>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Assignment Title</th>
                                <th>Max Marks</th>
                                <th>Due Date</th>
                                <th>Submissions</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($assignments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No assignments published yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($assignments as $a): ?>
                                    <tr>
                                        <td><span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1"><?= htmlspecialchars($a['subject_code']) ?></span></td>
                                        <td><strong><?= htmlspecialchars($a['title']) ?></strong></td>
                                        <td><strong><?= $a['max_marks'] ?> pts</strong></td>
                                        <td class="small"><?= date('d M Y, h:i A', strtotime($a['due_date'])) ?></td>
                                        <td>
                                            <span class="badge bg-success-light text-success font-weight-600 px-3 py-1">
                                                <?= $a['submission_count'] ?> Submissions
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= APP_URL ?>/staff/review_submissions.php?assignment_id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-success rounded-pill font-weight-600">
                                                Review & Grade <i class="fas fa-check-circle ms-1"></i>
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

<!-- Modal: New Assignment -->
<div class="modal fade" id="newAssignmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark"><i class="fas fa-plus-circle text-success me-2"></i> Create New Assignment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label for="subject_id" class="form-label font-weight-500">Subject</label>
                        <select id="subject_id" name="subject_id" class="form-select" required>
                            <option value="">-- Select Subject --</option>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['code']) ?> - <?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label font-weight-500">Assignment Title</label>
                        <input type="text" id="title" name="title" class="form-control" placeholder="e.g. Data Structures Lab Assignment 1" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label font-weight-500">Instructions & Problem Statement</label>
                        <textarea id="description" name="description" class="form-control" rows="4" placeholder="Detail the requirements, constraints, and submission instructions..."></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="max_marks" class="form-label font-weight-500">Maximum Marks</label>
                            <input type="number" id="max_marks" name="max_marks" class="form-control" value="100" required>
                        </div>
                        <div class="col-md-6">
                            <label for="due_date" class="form-label font-weight-500">Due Date & Time</label>
                            <input type="datetime-local" id="due_date" name="due_date" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="attachment" class="form-label font-weight-500">Reference File Attachment (Optional)</label>
                        <input type="file" id="attachment" name="attachment" class="form-control">
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 font-weight-600">Publish Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
