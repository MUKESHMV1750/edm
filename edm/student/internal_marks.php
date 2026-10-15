<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STUDENT]);

$studentId = $_SESSION['role_id'] ?? 0;
$db = getDB();

// Fetch Internal Marks
$mStmt = $db->prepare("
    SELECT im.*, sub.code AS subject_code, sub.name AS subject_name, sub.credits
    FROM internal_marks im
    JOIN subjects sub ON im.subject_id = sub.id
    WHERE im.student_id = ?
    ORDER BY sub.code ASC, im.exam_type ASC
");
$mStmt->execute([$studentId]);
$marks = $mStmt->fetchAll();

// Group by subject
$grouped = [];
foreach ($marks as $m) {
    $grouped[$m['subject_code']]['name'] = $m['subject_name'];
    $grouped[$m['subject_code']]['credits'] = $m['credits'];
    $grouped[$m['subject_code']]['exams'][$m['exam_type']] = [
        'marks' => $m['marks_obtained'],
        'max' => $m['max_marks'],
        'remarks' => $m['remarks']
    ];
}

$pageTitle = "Internal Assessment Marks";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Internal Marks Statement</h1>
                    <p class="text-muted small">Scores for Internal Assessment 1, Internal Assessment 2, and Model Examinations</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="h6 font-weight-bold text-dark mb-0"><i class="fas fa-award text-primary me-2"></i> Performance Grade Sheet</h5>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th>Credits</th>
                                <th>IA-1 (50)</th>
                                <th>IA-2 (50)</th>
                                <th>Model Exam (100)</th>
                                <th>Calculated Avg</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($grouped)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No internal marks posted yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($grouped as $code => $data): ?>
                                    <?php
                                        $ia1 = $data['exams']['IA1']['marks'] ?? '-';
                                        $ia2 = $data['exams']['IA2']['marks'] ?? '-';
                                        $model = $data['exams']['Model']['marks'] ?? '-';

                                        $sum = 0; $count = 0;
                                        if (is_numeric($ia1)) { $sum += ($ia1 / 50) * 100; $count++; }
                                        if (is_numeric($ia2)) { $sum += ($ia2 / 50) * 100; $count++; }
                                        if (is_numeric($model)) { $sum += $model; $count++; }

                                        $avgPct = $count > 0 ? round($sum / $count, 1) : '-';
                                    ?>
                                    <tr>
                                        <td><span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1"><?= htmlspecialchars($code) ?></span></td>
                                        <td><strong><?= htmlspecialchars($data['name']) ?></strong></td>
                                        <td><?= htmlspecialchars($data['credits']) ?></td>
                                        <td><strong><?= $ia1 ?></strong></td>
                                        <td><strong><?= $ia2 ?></strong></td>
                                        <td><strong><?= $model ?></strong></td>
                                        <td>
                                            <?php if ($avgPct !== '-'): ?>
                                                <span class="badge <?= $avgPct >= 50 ? 'bg-success-light text-success' : 'bg-danger-light text-danger' ?> px-3 py-1 font-weight-600">
                                                    <?= $avgPct ?>%
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
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
