<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_ADMIN]);

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $action = sanitizeInput($_POST['action'] ?? 'add');
        $department_id = (int)$_POST['department_id'];
        $semester = (int)$_POST['semester'];
        $section = sanitizeInput($_POST['section']);

        if ($action === 'delete') {
            $slotId = (int)$_POST['slot_id'];
            $db->prepare("DELETE FROM timetable WHERE id = ?")->execute([$slotId]);
            setFlashMessage('success', 'Timetable slot removed successfully.');
        } elseif ($action === 'edit') {
            $slotId = (int)$_POST['slot_id'];
            $day_of_week = sanitizeInput($_POST['day_of_week']);
            $period_number = (int)$_POST['period_number'];
            $subject_id = (int)$_POST['subject_id'];
            $staff_id = (int)$_POST['staff_id'];
            $classroom_id = !empty($_POST['classroom_id']) ? (int)$_POST['classroom_id'] : null;

            $upd = $db->prepare("
                UPDATE timetable 
                SET day_of_week = ?, period_number = ?, subject_id = ?, staff_id = ?, classroom_id = ?
                WHERE id = ?
            ");
            $upd->execute([$day_of_week, $period_number, $subject_id, $staff_id, $classroom_id, $slotId]);
            setFlashMessage('success', 'Timetable slot updated successfully.');
        } else {
            $day_of_week = sanitizeInput($_POST['day_of_week']);
            $period_number = (int)$_POST['period_number'];
            $subject_id = (int)$_POST['subject_id'];
            $staff_id = (int)$_POST['staff_id'];
            $classroom_id = !empty($_POST['classroom_id']) ? (int)$_POST['classroom_id'] : null;

            $ins = $db->prepare("
                INSERT INTO timetable (department_id, semester, section, day_of_week, period_number, subject_id, staff_id, classroom_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE subject_id = ?, staff_id = ?, classroom_id = ?
            ");
            $ins->execute([
                $department_id, $semester, $section, $day_of_week, $period_number, $subject_id, $staff_id, $classroom_id,
                $subject_id, $staff_id, $classroom_id
            ]);

            setFlashMessage('success', 'Timetable slot allocated successfully!');
        }
        redirect("admin/manage_timetable.php?department_id={$department_id}&semester={$semester}&section={$section}");
    }
}

$departments = $db->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();
$subjects = $db->query("SELECT * FROM subjects ORDER BY code ASC")->fetchAll();
$staffMembers = $db->query("SELECT id, first_name, last_name FROM staff ORDER BY first_name ASC")->fetchAll();
$classrooms = $db->query("SELECT id, room_number FROM classrooms ORDER BY room_number ASC")->fetchAll();

$selectedDept = isset($_GET['department_id']) ? (int)$_GET['department_id'] : ($departments[0]['id'] ?? 0);
$selectedSem = isset($_GET['semester']) ? (int)$_GET['semester'] : 4;
$selectedSec = sanitizeInput($_GET['section'] ?? 'A');

$timetable = [];
if ($selectedDept > 0) {
    $ttStmt = $db->prepare("
        SELECT tt.*, sub.code AS subject_code, sub.name AS subject_name, st.first_name, st.last_name, c.room_number
        FROM timetable tt
        JOIN subjects sub ON tt.subject_id = sub.id
        JOIN staff st ON tt.staff_id = st.id
        LEFT JOIN classrooms c ON tt.classroom_id = c.id
        WHERE tt.department_id = ? AND tt.semester = ? AND tt.section = ?
        ORDER BY FIELD(tt.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), tt.period_number ASC
    ");
    $ttStmt->execute([$selectedDept, $selectedSem, $selectedSec]);
    $timetable = $ttStmt->fetchAll();
}

$pageTitle = "Timetable Schedule Editor";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Master Timetable Editor</h1>
                    <p class="text-muted small">Construct weekly period schedules across departments and sections</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="../staff/timetable.php" class="btn btn-outline-primary rounded-pill btn-sm px-3 font-weight-600">
                        <i class="fas fa-file-excel me-1 text-success"></i> Faculty Timetable & Excel Import
                    </a>
                    <button type="button" class="btn btn-warning text-dark rounded-pill btn-sm px-4 font-weight-600" data-bs-toggle="modal" data-bs-target="#newSlotModal">
                        <i class="fas fa-plus me-1"></i> Add Timetable Slot
                    </button>
                </div>
            </div>

            <?php displayAlerts(); ?>

            <!-- Filter Controls -->
            <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 16px;">
                <form action="" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="department_id" class="form-label font-weight-500">Department</label>
                        <select id="department_id" name="department_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= $d['id'] == $selectedDept ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="semester" class="form-label font-weight-500">Semester</label>
                        <select id="semester" name="semester" class="form-select" onchange="this.form.submit()">
                            <?php for ($s = 1; $s <= 8; $s++): ?>
                                <option value="<?= $s ?>" <?= $s == $selectedSem ? 'selected' : '' ?>>Sem <?= $s ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="section" class="form-label font-weight-500">Section</label>
                        <select id="section" name="section" class="form-select" onchange="this.form.submit()">
                            <option value="A" <?= $selectedSec === 'A' ? 'selected' : '' ?>>Section A</option>
                            <option value="B" <?= $selectedSec === 'B' ? 'selected' : '' ?>>Section B</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary rounded-pill w-100 font-weight-600">Filter</button>
                    </div>
                </form>
            </div>

            <!-- Timetable List -->
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Day</th>
                                <th>Period</th>
                                <th>Subject</th>
                                <th>Faculty</th>
                                <th>Classroom</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($timetable)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No timetable slots configured for this selection.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($timetable as $t): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($t['day_of_week']) ?></strong></td>
                                        <td><span class="badge bg-light text-dark font-weight-600">Period <?= $t['period_number'] ?></span></td>
                                        <td>
                                            <span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1"><?= htmlspecialchars($t['subject_code']) ?></span>
                                            <span class="ms-2 font-weight-500"><?= htmlspecialchars($t['subject_name']) ?></span>
                                        </td>
                                        <td>Prof. <?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></td>
                                        <td>Room <?= htmlspecialchars($t['room_number'] ?? 'Standard') ?></td>
                                        <td class="text-end pe-4">
                                            <div class="d-inline-flex align-items-center gap-1">
                                                <!-- View Button (Icon only) -->
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-info rounded-circle" 
                                                        title="View Slot Details" aria-label="View"
                                                        onclick='viewSlot(<?= json_encode($t, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <!-- Edit Button (Icon only) -->
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-warning text-dark rounded-circle" 
                                                        title="Edit Timetable Slot" aria-label="Edit"
                                                        onclick='editSlot(<?= json_encode($t, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <!-- Delete Button (Icon only) -->
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger rounded-circle" 
                                                        title="Remove Timetable Slot" aria-label="Delete"
                                                        onclick="deleteSlot(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['day_of_week'])) ?>', <?= $t['period_number'] ?>, '<?= htmlspecialchars(addslashes($t['subject_code'])) ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
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

<!-- Modal: New Slot -->
<div class="modal fade" id="newSlotModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark">Allocate Timetable Slot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4">
                    <?= csrfField() ?>
                    <input type="hidden" name="department_id" value="<?= $selectedDept ?>">
                    <input type="hidden" name="semester" value="<?= $selectedSem ?>">
                    <input type="hidden" name="section" value="<?= htmlspecialchars($selectedSec) ?>">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="day_of_week" class="form-label font-weight-500">Day of Week</label>
                            <select id="day_of_week" name="day_of_week" class="form-select" required>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="period_number" class="form-label font-weight-500">Period Number</label>
                            <select id="period_number" name="period_number" class="form-select" required>
                                <?php for ($p = 1; $p <= 7; $p++): ?>
                                    <option value="<?= $p ?>">Period <?= $p ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="subject_id" class="form-label font-weight-500">Subject</label>
                        <select id="subject_id" name="subject_id" class="form-select" required>
                            <?php foreach ($subjects as $sub): ?>
                                <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['code']) ?> - <?= htmlspecialchars($sub['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="staff_id" class="form-label font-weight-500">Faculty Professor</label>
                        <select id="staff_id" name="staff_id" class="form-select" required>
                            <?php foreach ($staffMembers as $stf): ?>
                                <option value="<?= $stf['id'] ?>">Prof. <?= htmlspecialchars($stf['first_name'] . ' ' . $stf['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="classroom_id" class="form-label font-weight-500">Classroom</label>
                        <select id="classroom_id" name="classroom_id" class="form-select">
                            <?php foreach ($classrooms as $c): ?>
                                <option value="<?= $c['id'] ?>">Room <?= htmlspecialchars($c['room_number']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark rounded-pill px-4 font-weight-600">Save Slot</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: View Slot -->
<div class="modal fade" id="viewSlotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark">
                    <i class="fas fa-calendar-alt text-primary me-2"></i> Timetable Slot Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <span class="badge bg-primary-lightest text-primary font-weight-bold px-3 py-2 fs-6 me-3" id="viewSlotDayPeriod"></span>
                    <div>
                        <h5 class="h6 font-weight-bold text-dark mb-0" id="viewSlotSubCode"></h5>
                        <small class="text-muted" id="viewSlotSubName"></small>
                    </div>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Assigned Faculty:</span>
                        <strong class="text-dark" id="viewSlotFaculty"></strong>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Allocated Room:</span>
                        <span class="badge bg-warning-light text-dark" id="viewSlotRoom"></span>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Academic Term:</span>
                        <span class="badge bg-light text-dark font-weight-600">Semester <?= $selectedSem ?> (Section <?= htmlspecialchars($selectedSec) ?>)</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Edit Slot -->
<div class="modal fade" id="editSlotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark">
                    <i class="fas fa-edit text-warning me-2"></i> Edit Timetable Slot
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="slot_id" id="editSlotId">
                <input type="hidden" name="department_id" value="<?= $selectedDept ?>">
                <input type="hidden" name="semester" value="<?= $selectedSem ?>">
                <input type="hidden" name="section" value="<?= htmlspecialchars($selectedSec) ?>">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="edit_day_of_week" class="form-label font-weight-500">Day of Week</label>
                            <select id="edit_day_of_week" name="day_of_week" class="form-select" required>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_period_number" class="form-label font-weight-500">Period Number</label>
                            <select id="edit_period_number" name="period_number" class="form-select" required>
                                <?php for ($p = 1; $p <= 7; $p++): ?>
                                    <option value="<?= $p ?>">Period <?= $p ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_subject_id" class="form-label font-weight-500">Subject</label>
                        <select id="edit_subject_id" name="subject_id" class="form-select" required>
                            <?php foreach ($subjects as $sub): ?>
                                <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['code']) ?> - <?= htmlspecialchars($sub['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_staff_id" class="form-label font-weight-500">Faculty Professor</label>
                        <select id="edit_staff_id" name="staff_id" class="form-select" required>
                            <?php foreach ($staffMembers as $stf): ?>
                                <option value="<?= $stf['id'] ?>">Prof. <?= htmlspecialchars($stf['first_name'] . ' ' . $stf['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_classroom_id" class="form-label font-weight-500">Classroom</label>
                        <select id="edit_classroom_id" name="classroom_id" class="form-select">
                            <?php foreach ($classrooms as $c): ?>
                                <option value="<?= $c['id'] ?>">Room <?= htmlspecialchars($c['room_number']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark rounded-pill px-4 font-weight-600">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Delete Slot -->
<div class="modal fade" id="deleteSlotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i> Remove Slot
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="slot_id" id="deleteSlotId">
                <input type="hidden" name="department_id" value="<?= $selectedDept ?>">
                <input type="hidden" name="semester" value="<?= $selectedSem ?>">
                <input type="hidden" name="section" value="<?= htmlspecialchars($selectedSec) ?>">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <p class="mb-0">Are you sure you want to remove <strong id="deleteSlotSubject"></strong> slot on <strong id="deleteSlotDay"></strong> (Period <span id="deleteSlotPeriod"></span>)?</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 font-weight-600">Remove Slot</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewSlot(t) {
    document.getElementById('viewSlotDayPeriod').innerText = (t.day_of_week || '') + ' • Period ' + (t.period_number || '1');
    document.getElementById('viewSlotSubCode').innerText = t.subject_code || '';
    document.getElementById('viewSlotSubName').innerText = t.subject_name || '';
    document.getElementById('viewSlotFaculty').innerText = 'Prof. ' + (t.first_name || '') + ' ' + (t.last_name || '');
    document.getElementById('viewSlotRoom').innerText = 'Room ' + (t.room_number || 'Standard');
    new bootstrap.Modal(document.getElementById('viewSlotModal')).show();
}

function editSlot(t) {
    document.getElementById('editSlotId').value = t.id;
    document.getElementById('edit_day_of_week').value = t.day_of_week || 'Monday';
    document.getElementById('edit_period_number').value = t.period_number || 1;
    document.getElementById('edit_subject_id').value = t.subject_id || '';
    document.getElementById('edit_staff_id').value = t.staff_id || '';
    document.getElementById('edit_classroom_id').value = t.classroom_id || '';
    new bootstrap.Modal(document.getElementById('editSlotModal')).show();
}

function deleteSlot(id, day, period, code) {
    document.getElementById('deleteSlotId').value = id;
    document.getElementById('deleteSlotDay').innerText = day;
    document.getElementById('deleteSlotPeriod').innerText = period;
    document.getElementById('deleteSlotSubject').innerText = code;
    new bootstrap.Modal(document.getElementById('deleteSlotModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
