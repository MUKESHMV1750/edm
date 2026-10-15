<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STAFF]);

$staffId = $_SESSION['role_id'] ?? 0;
$db = getDB();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $subject_id = (int)$_POST['subject_id'];
        $title = sanitizeInput($_POST['title']);
        $exam_type = sanitizeInput($_POST['exam_type']);
        $academic_year = sanitizeInput($_POST['academic_year']);

        if (isset($_FILES['question_file']) && $_FILES['question_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/questions/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = time() . '_' . basename($_FILES['question_file']['name']);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['question_file']['tmp_name'], $targetPath)) {
                $file_path = 'uploads/questions/' . $fileName;

                $subjInfo = $db->prepare("SELECT department_id, semester FROM subjects WHERE id = ?");
                $subjInfo->execute([$subject_id]);
                $sRow = $subjInfo->fetch();
                $deptId = $sRow['department_id'] ?? 1;
                $sem = $sRow['semester'] ?? 6;

                $ins = $db->prepare("
                    INSERT INTO question_papers (subject_id, staff_id, uploaded_by, department_id, semester, title, exam_type, academic_year, file_path, file_name)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $ins->execute([$subject_id, $staffId, $staffId, $deptId, $sem, $title, $exam_type, $academic_year, $file_path, $fileName]);

                setFlashMessage('success', 'Question paper added to archive successfully!');
                redirect('staff/manage_questions.php');
            }
        }
    }
}

// Fetch Staff Subjects
$subStmt = $db->prepare("SELECT * FROM subjects WHERE staff_id = ? ORDER BY code ASC");
$subStmt->execute([$staffId]);
$subjects = $subStmt->fetchAll();

// Fetch Question Papers uploaded by staff
$qStmt = $db->prepare("
    SELECT qp.*, sub.code AS subject_code, sub.name AS subject_name
    FROM question_papers qp
    JOIN subjects sub ON qp.subject_id = sub.id
    WHERE (qp.staff_id = ? OR qp.uploaded_by = ?)
    ORDER BY COALESCE(qp.uploaded_at, qp.created_at) DESC
");
$qStmt->execute([$staffId, $staffId]);
$questions = $qStmt->fetchAll();

$pageTitle = "Question Paper Repository";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Question Paper Bank</h1>
                    <p class="text-muted small">Upload midterm and semester end question papers for student reference</p>
                </div>
            </div>

            <?php displayAlerts(); ?>

            <div class="row g-4 mb-4">
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
                        <h5 class="h6 font-weight-bold text-dark mb-3"><i class="fas fa-file-upload text-primary me-2"></i> Add Question Paper</h5>
                        
                        <form action="" method="POST" enctype="multipart/form-data">
                            <?= csrfField() ?>

                            <div class="mb-3">
                                <label for="subject_id" class="form-label font-weight-500">Subject</label>
                                <select id="subject_id" name="subject_id" class="form-select" required>
                                    <option value="">-- Choose Subject --</option>
                                    <?php foreach ($subjects as $s): ?>
                                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['code']) ?> - <?= htmlspecialchars($s['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="title" class="form-label font-weight-500">Paper Title</label>
                                <input type="text" id="title" name="title" class="form-control" placeholder="e.g. End Semester Exam Paper 2024" required>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="exam_type" class="form-label font-weight-500">Exam Type</label>
                                    <select id="exam_type" name="exam_type" class="form-select" required>
                                        <option value="midterm">Midterm / Internal</option>
                                        <option value="semester">End Semester</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="academic_year" class="form-label font-weight-500">Academic Year</label>
                                    <input type="text" id="academic_year" name="academic_year" class="form-control" value="2024-2025" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="question_file" class="form-label font-weight-500">Upload PDF File</label>
                                <input type="file" id="question_file" name="question_file" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary rounded-pill px-4 font-weight-600 w-100">
                                Save to Bank <i class="fas fa-save ms-2"></i>
                            </button>
                        </form>

                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="h6 font-weight-bold text-dark mb-0"><i class="fas fa-database text-primary me-2"></i> Question Paper Archive</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Subject</th>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Year</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($questions)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No question papers uploaded.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($questions as $q): ?>
                                            <tr>
                                                <td><span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1"><?= htmlspecialchars($q['subject_code']) ?></span></td>
                                                <td><strong><?= htmlspecialchars($q['title']) ?></strong></td>
                                                <td><span class="badge bg-light text-dark text-uppercase small"><?= htmlspecialchars($q['exam_type']) ?></span></td>
                                                <td class="small"><?= htmlspecialchars($q['academic_year']) ?></td>
                                                <td>
                                                    <a href="<?= APP_URL . '/' . $q['file_path'] ?>" class="btn btn-sm btn-outline-primary rounded-pill font-weight-600" target="_blank">
                                                        Download <i class="fas fa-file-pdf ms-1 text-danger"></i>
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
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
