<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STUDENT]);

$studentId = $_SESSION['role_id'] ?? 0;
$db = getDB();

$stmt = $db->prepare("
    SELECT 
        submiss.*, 
        a.title AS assignment_title, 
        a.max_marks, 
        a.due_date,
        sub.code AS subject_code, 
        sub.name AS subject_name,
        st.first_name, st.last_name
    FROM assignment_submissions submiss
    JOIN assignments a ON submiss.assignment_id = a.id
    JOIN subjects sub ON a.subject_id = sub.id
    JOIN staff st ON a.staff_id = st.id
    WHERE submiss.student_id = ?
    ORDER BY submiss.submitted_at DESC
");
$stmt->execute([$studentId]);
$submissions = $stmt->fetchAll();

$pageTitle = "Assignment Marks & Feedback";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Assignment Marks & Feedback</h1>
                    <p class="text-muted small">Detailed breakdown of graded assignments and faculty remarks</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Assignment</th>
                                <th>Submitted On</th>
                                <th>Status</th>
                                <th>Score</th>
                                <th>Faculty Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($submissions)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No assignment submissions graded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($submissions as $s): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1"><?= htmlspecialchars($s['subject_code']) ?></span>
                                        </td>
                                        <td><strong><?= htmlspecialchars($s['assignment_title']) ?></strong></td>
                                        <td class="small"><?= date('d M Y, h:i A', strtotime($s['submitted_at'])) ?></td>
                                        <td>
                                            <?php if ($s['status'] === 'graded'): ?>
                                                <span class="badge bg-success-light text-success font-weight-600">Graded</span>
                                            <?php else: ?>
                                                <span class="badge bg-info-light text-info font-weight-600">Pending Review</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($s['status'] === 'graded'): ?>
                                                <strong><?= $s['marks_obtained'] ?> / <?= $s['max_marks'] ?></strong>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small">
                                            <?= !empty($s['feedback']) ? htmlspecialchars($s['feedback']) : '<span class="text-muted">No remarks</span>' ?>
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
