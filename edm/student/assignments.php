<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STUDENT]);

$studentId = $_SESSION['role_id'] ?? 0;
$db = getDB();

// Get Student details
$stStmt = $db->prepare("SELECT department_id, semester FROM students WHERE id = ?");
$stStmt->execute([$studentId]);
$student = $stStmt->fetch();

// Fetch Assignments list with submission status
$assStmt = $db->prepare("
    SELECT 
        a.*, 
        sub.name AS subject_name, 
        sub.code AS subject_code,
        st.first_name AS staff_first, 
        st.last_name AS staff_last,
        submiss.id AS submission_id,
        submiss.submitted_at,
        submiss.marks_obtained,
        submiss.status AS submission_status,
        submiss.feedback
    FROM assignments a
    JOIN subjects sub ON a.subject_id = sub.id
    JOIN staff st ON a.staff_id = st.id
    LEFT JOIN assignment_submissions submiss ON a.id = submiss.assignment_id AND submiss.student_id = ?
    WHERE sub.department_id = ? AND sub.semester = ?
    ORDER BY a.due_date DESC
");
$assStmt->execute([$studentId, $student['department_id'], $student['semester']]);
$assignments = $assStmt->fetchAll();

$pageTitle = "Course Assignments";
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
                    <p class="text-muted small">View coursework tasks, submission deadlines & faculty grades</p>
                </div>
                <a href="<?= APP_URL ?>/student/assignment_marks.php" class="btn btn-outline-primary rounded-pill btn-sm">
                    <i class="fas fa-award me-1"></i> Assignment Marks
                </a>
            </div>

            <?php displayAlerts(); ?>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Assignment Title</th>
                                <th>Faculty</th>
                                <th>Due Date</th>
                                <th>Max Marks</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($assignments)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No assignments issued for this semester.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($assignments as $a): ?>
                                    <?php 
                                        $isOverdue = strtotime($a['due_date']) < time();
                                        $hasSubmitted = !empty($a['submission_id']);
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1"><?= htmlspecialchars($a['subject_code']) ?></span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($a['title']) ?></strong>
                                            <span class="d-block text-muted small text-truncate" style="max-width: 250px;"><?= htmlspecialchars($a['description']) ?></span>
                                        </td>
                                        <td class="small">Prof. <?= htmlspecialchars($a['staff_first'] . ' ' . $a['staff_last']) ?></td>
                                        <td class="small">
                                            <span class="<?= $isOverdue && !$hasSubmitted ? 'text-danger font-weight-bold' : '' ?>">
                                                <?= date('d M Y, h:i A', strtotime($a['due_date'])) ?>
                                            </span>
                                        </td>
                                        <td><strong><?= $a['max_marks'] ?> pts</strong></td>
                                        <td>
                                            <?php if ($hasSubmitted): ?>
                                                <?php if ($a['submission_status'] === 'graded'): ?>
                                                    <span class="badge bg-success text-white font-weight-600">Graded: <?= $a['marks_obtained'] ?> / <?= $a['max_marks'] ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-info-light text-info font-weight-600">Submitted</span>
                                                <?php endif; ?>
                                            <?php elseif ($isOverdue): ?>
                                                <span class="badge bg-danger-light text-danger font-weight-600">Overdue</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-light text-warning font-weight-600">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?= APP_URL ?>/student/submit_assignment.php?id=<?= $a['id'] ?>" class="btn btn-sm <?= $hasSubmitted ? 'btn-outline-secondary' : 'btn-primary' ?> rounded-pill px-3 font-weight-600">
                                                <?= $hasSubmitted ? 'View Submission' : 'Submit Now' ?>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
