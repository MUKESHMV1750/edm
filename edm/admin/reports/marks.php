<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';
checkRole([ROLE_ADMIN]);

$db = getDB();

$departments = $db->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();
$selectedDept = isset($_GET['department_id']) ? (int)$_GET['department_id'] : ($departments[0]['id'] ?? 0);

$marks = [];
if ($selectedDept > 0) {
    $mStmt = $db->prepare("
        SELECT st.register_number, st.first_name, st.last_name, sub.code AS subject_code, im.exam_type, im.marks_obtained, im.max_marks
        FROM students st
        JOIN internal_marks im ON st.id = im.student_id
        JOIN subjects sub ON im.subject_id = sub.id
        WHERE st.department_id = ?
        ORDER BY st.register_number ASC, sub.code ASC
    ");
    $mStmt->execute([$selectedDept]);
    $marks = $mStmt->fetchAll();
}

$pageTitle = "Admin Marks Report";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Department Internal Marks Report</h1>
                    <p class="text-muted small">System-wide internal assessment scores</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 16px;">
                <form action="" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label for="department_id" class="form-label font-weight-500">Select Department</label>
                        <select id="department_id" name="department_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= $d['id'] == $selectedDept ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
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
                                <th>Subject Code</th>
                                <th>Exam Type</th>
                                <th>Marks Obtained</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($marks)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No internal marks found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($marks as $m): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($m['register_number']) ?></strong></td>
                                        <td><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?></td>
                                        <td><span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1"><?= htmlspecialchars($m['subject_code']) ?></span></td>
                                        <td><span class="badge bg-light text-dark font-weight-600"><?= htmlspecialchars($m['exam_type']) ?></span></td>
                                        <td><strong><?= $m['marks_obtained'] ?> / <?= $m['max_marks'] ?></strong></td>
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
