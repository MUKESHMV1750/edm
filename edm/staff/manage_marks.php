<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STAFF]);

$staffId = $_SESSION['role_id'] ?? 0;
$db = getDB();

// Fetch Staff Subjects
$subStmt = $db->prepare("SELECT * FROM subjects WHERE staff_id = ? ORDER BY code ASC");
$subStmt->execute([$staffId]);
$subjects = $subStmt->fetchAll();

$selectedSubject = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : ($subjects[0]['id'] ?? 0);
$selectedExam = sanitizeInput($_GET['exam_type'] ?? 'IA1');

// Save Internal Marks Batch
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $subject_id = (int)$_POST['subject_id'];
        $exam_type = sanitizeInput($_POST['exam_type']);
        $max_marks = $exam_type === 'Model' ? 100 : 50;

        if (isset($_POST['marks']) && is_array($_POST['marks'])) {
            foreach ($_POST['marks'] as $stId => $markVal) {
                if ($markVal !== '' && $markVal !== null) {
                    $mVal = (float)$markVal;
                    $remarks = sanitizeInput($_POST['remarks'][$stId] ?? '');

                    $ins = $db->prepare("
                        INSERT INTO internal_marks (student_id, subject_id, exam_type, marks_obtained, max_marks, remarks)
                        VALUES (?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE marks_obtained = ?, max_marks = ?, remarks = ?
                    ");
                    $ins->execute([$stId, $subject_id, $exam_type, $mVal, $max_marks, $remarks, $mVal, $max_marks, $remarks]);
                }
            }
            setFlashMessage('success', "Internal marks for {$exam_type} saved successfully!");
            redirect("staff/manage_marks.php?subject_id={$subject_id}&exam_type={$exam_type}");
        }
    }
}

// Fetch Students for selected subject department & semester
$students = [];
if ($selectedSubject > 0) {
    $subjDetails = $db->prepare("SELECT department_id, semester FROM subjects WHERE id = ?");
    $subjDetails->execute([$selectedSubject]);
    $sd = $subjDetails->fetch();

    if ($sd) {
        $stStmt = $db->prepare("
            SELECT st.id AS student_id, st.register_number, st.first_name, st.last_name,
                   im.marks_obtained, im.remarks
            FROM students st
            LEFT JOIN internal_marks im ON st.id = im.student_id AND im.subject_id = ? AND im.exam_type = ?
            WHERE st.department_id = ? AND st.semester = ?
            ORDER BY st.register_number ASC
        ");
        $stStmt->execute([$selectedSubject, $selectedExam, $sd['department_id'], $sd['semester']]);
        $students = $stStmt->fetchAll();
    }
}

$pageTitle = "Enter Internal Marks";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Internal Examination Marks Entry</h1>
                    <p class="text-muted small">Input scores for Internal Assessment 1, 2, and Model Exams</p>
                </div>
            </div>

            <?php displayAlerts(); ?>

            <!-- Filter Controls -->
            <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 16px;">
                <form action="" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label for="subject_id" class="form-label font-weight-500">Select Course / Subject</label>
                        <select id="subject_id" name="subject_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($subjects as $sub): ?>
                                <option value="<?= $sub['id'] ?>" <?= $sub['id'] == $selectedSubject ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sub['code']) ?> - <?= htmlspecialchars($sub['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="exam_type" class="form-label font-weight-500">Exam Type</label>
                        <select id="exam_type" name="exam_type" class="form-select" onchange="this.form.submit()">
                            <option value="IA1" <?= $selectedExam === 'IA1' ? 'selected' : '' ?>>Internal Assessment 1 (Max 50)</option>
                            <option value="IA2" <?= $selectedExam === 'IA2' ? 'selected' : '' ?>>Internal Assessment 2 (Max 50)</option>
                            <option value="Model" <?= $selectedExam === 'Model' ? 'selected' : '' ?>>Model Examination (Max 100)</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary rounded-pill w-100 font-weight-600">
                            Load Roster
                        </button>
                    </div>
                </form>
            </div>

            <!-- Marks Entry Table -->
            <?php if (!empty($students)): ?>
                <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                    <form action="" method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="subject_id" value="<?= $selectedSubject ?>">
                        <input type="hidden" name="exam_type" value="<?= htmlspecialchars($selectedExam) ?>">

                        <div class="table-responsive">
                            <table class="table align-middle table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Register No</th>
                                        <th>Student Name</th>
                                        <th>Marks Obtained (Max <?= $selectedExam === 'Model' ? 100 : 50 ?>)</th>
                                        <th>Faculty Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $st): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($st['register_number']) ?></strong></td>
                                            <td><?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?></td>
                                            <td style="max-width: 150px;">
                                                <input type="number" step="0.5" name="marks[<?= $st['student_id'] ?>]" class="form-control form-control-sm font-weight-bold" value="<?= $st['marks_obtained'] ?? '' ?>" placeholder="0.0">
                                            </td>
                                            <td>
                                                <input type="text" name="remarks[<?= $st['student_id'] ?>]" class="form-control form-control-sm" value="<?= htmlspecialchars($st['remarks'] ?? '') ?>" placeholder="Good / Needs Improvement">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer bg-white border-0 py-3 text-end">
                            <button type="submit" class="btn btn-success rounded-pill px-4 font-weight-600">
                                Save All Marks <i class="fas fa-save ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
