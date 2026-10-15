<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STAFF]);

$staffId = $_SESSION['role_id'] ?? 0;
$db = getDB();

$stfStmt = $db->prepare("SELECT department_id FROM staff WHERE id = ?");
$stfStmt->execute([$staffId]);
$staff = $stfStmt->fetch();

// Fetch Students in department
$stStmt = $db->prepare("
    SELECT s.*, u.email, d.code AS dept_code
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN departments d ON s.department_id = d.id
    WHERE s.department_id = ?
    ORDER BY s.semester ASC, s.section ASC, s.register_number ASC
");
$stStmt->execute([$staff['department_id']]);
$students = $stStmt->fetchAll();

$pageTitle = "Assigned Students";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Department Students Roster</h1>
                    <p class="text-muted small">View students enrolled in your department and courses</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Register No</th>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Semester</th>
                                <th>Section</th>
                                <th>Academic Year</th>
                                <th>Phone</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No students enrolled.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $s): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($s['register_number'] ?? '') ?></strong></td>
                                        <td><?= htmlspecialchars(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars($s['email'] ?? '') ?></td>
                                        <td><span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1">Sem <?= $s['semester'] ?? '1' ?></span></td>
                                        <td><span class="badge bg-light text-dark font-weight-600">Sec <?= htmlspecialchars($s['section'] ?? 'A') ?></span></td>
                                        <td><?= htmlspecialchars($s['academic_year'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($s['phone'] ?? 'N/A') ?></td>
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
