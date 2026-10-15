<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';
checkRole([ROLE_STAFF]);

$staffId = $_SESSION['role_id'] ?? 0;
$db = getDB();

$subStmt = $db->prepare("SELECT * FROM subjects WHERE staff_id = ? ORDER BY code ASC");
$subStmt->execute([$staffId]);
$subjects = $subStmt->fetchAll();

$selectedSubject = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : ($subjects[0]['id'] ?? 0);

$marks = [];
if ($selectedSubject > 0) {
    $mStmt = $db->prepare("
        SELECT st.register_number, st.first_name, st.last_name, im.exam_type, 
               COALESCE(im.marks_obtained, im.obtained_marks, 0) AS marks_obtained, im.max_marks
        FROM students st
        JOIN internal_marks im ON st.id = im.student_id
        WHERE im.subject_id = ?
        ORDER BY st.register_number ASC, im.exam_type ASC
    ");
    $mStmt->execute([$selectedSubject]);
    $marks = $mStmt->fetchAll();
}

$pageTitle = "Faculty Marks Report";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Internal Marks Report</h1>
                    <p class="text-muted small">Comprehensive statement of internal assessment scores for your subjects</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 16px;">
                <form action="" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label for="subject_id" class="form-label font-weight-500">Select Subject</label>
                        <select id="subject_id" name="subject_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($subjects as $sub): ?>
                                <option value="<?= $sub['id'] ?>" <?= $sub['id'] == $selectedSubject ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sub['code']) ?> - <?= htmlspecialchars($sub['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Register No</th>
                                <th>Student Name</th>
                                <th>Exam Type</th>
                                <th>Marks Obtained</th>
                                <th>Max Marks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($marks)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No marks recorded.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($marks as $m): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($m['register_number']) ?></strong></td>
                                        <td><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?></td>
                                        <td><span class="badge bg-light text-dark font-weight-600"><?= htmlspecialchars($m['exam_type']) ?></span></td>
                                        <td><strong><?= $m['marks_obtained'] ?></strong></td>
                                        <td><?= $m['max_marks'] ?></td>
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
