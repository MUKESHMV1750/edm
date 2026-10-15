<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STUDENT]);

$studentId = $_SESSION['role_id'] ?? 0;
$db = getDB();

$stStmt = $db->prepare("SELECT department_id, semester, section FROM students WHERE id = ?");
$stStmt->execute([$studentId]);
$student = $stStmt->fetch();

// Fetch Timetable Entries
$ttStmt = $db->prepare("
    SELECT tt.*, sub.code AS subject_code, sub.name AS subject_name, st.first_name, st.last_name, c.room_number
    FROM timetable tt
    JOIN subjects sub ON tt.subject_id = sub.id
    JOIN staff st ON tt.staff_id = st.id
    LEFT JOIN classrooms c ON tt.classroom_id = c.id
    WHERE tt.department_id = ? AND tt.semester = ? AND tt.section = ?
    ORDER BY FIELD(tt.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), tt.period_number ASC
");
$ttStmt->execute([$student['department_id'], $student['semester'], $student['section']]);
$timetable = $ttStmt->fetchAll();

// Structure grid by day & period
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$periods = [1, 2, 3, 4, 5, 6, 7];

$grid = [];
foreach ($timetable as $t) {
    $grid[$t['day_of_week']][$t['period_number']] = $t;
}

$pageTitle = "Class Timetable";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Class Timetable</h1>
                    <p class="text-muted small">Semester <?= htmlspecialchars($student['semester']) ?> - Section <?= htmlspecialchars($student['section']) ?> Schedule Grid</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 120px;">Day / Period</th>
                                <th>P1 (09:00 - 09:50)</th>
                                <th>P2 (09:50 - 10:40)</th>
                                <th>P3 (10:50 - 11:40)</th>
                                <th>P4 (11:40 - 12:30)</th>
                                <th class="table-secondary text-dark" style="width: 80px;">LUNCH</th>
                                <th>P5 (01:30 - 02:20)</th>
                                <th>P6 (02:20 - 03:10)</th>
                                <th>P7 (03:15 - 04:05)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($days as $day): ?>
                                <tr>
                                    <td class="font-weight-bold table-light text-start ps-3"><?= $day ?></td>
                                    <?php for ($p = 1; $p <= 7; $p++): ?>
                                        <?php if ($p == 5): ?>
                                            <!-- Lunch break column insert before P5 -->
                                            <td class="table-secondary text-muted small align-middle" style="writing-mode: vertical-lr; transform: rotate(180deg); font-weight: 600;">RECESS</td>
                                        <?php endif; ?>

                                        <?php if (isset($grid[$day][$p])): ?>
                                            <?php $slot = $grid[$day][$p]; ?>
                                            <td class="p-2 bg-light">
                                                <span class="badge bg-primary text-white font-weight-600 d-block mb-1"><?= htmlspecialchars($slot['subject_code']) ?></span>
                                                <small class="d-block text-dark font-weight-500 text-truncate" style="max-width: 110px;"><?= htmlspecialchars($slot['subject_name']) ?></small>
                                                <span class="text-muted text-xs d-block">Room <?= htmlspecialchars($slot['room_number'] ?? 'Lab') ?></span>
                                            </td>
                                        <?php else: ?>
                                            <td class="text-muted text-xs align-middle bg-white">- Free -</td>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
