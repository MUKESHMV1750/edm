<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';
checkRole([ROLE_ADMIN]);

$db = getDB();

$assignments = $db->query("
    SELECT a.*, sub.code AS subject_code, st.first_name, st.last_name,
           (SELECT COUNT(*) FROM assignment_submissions submiss WHERE submiss.assignment_id = a.id) AS submission_count
    FROM assignments a
    JOIN subjects sub ON a.subject_id = sub.id
    JOIN staff st ON a.staff_id = st.id
    ORDER BY a.due_date DESC
")->fetchAll();

$pageTitle = "Admin Assignments Report";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Assignments & Submissions Summary</h1>
                    <p class="text-muted small">Overview of coursework assignments and evaluation progress</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Assignment Title</th>
                                <th>Faculty</th>
                                <th>Max Marks</th>
                                <th>Due Date</th>
                                <th>Submissions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $a): ?>
                                <tr>
                                    <td><span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1"><?= htmlspecialchars($a['subject_code']) ?></span></td>
                                    <td><strong><?= htmlspecialchars($a['title']) ?></strong></td>
                                    <td>Prof. <?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></td>
                                    <td><?= $a['max_marks'] ?> pts</td>
                                    <td class="small"><?= date('d M Y, h:i A', strtotime($a['due_date'])) ?></td>
                                    <td><span class="badge bg-success-light text-success font-weight-600"><?= $a['submission_count'] ?> Submissions</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
