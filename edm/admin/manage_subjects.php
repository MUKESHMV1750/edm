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

        if ($action === 'add') {
            $code = strtoupper(sanitizeInput($_POST['code'] ?? ''));
            $name = sanitizeInput($_POST['name'] ?? '');
            $department_id = (int)($_POST['department_id'] ?? 0);
            $semester = (int)($_POST['semester'] ?? 1);
            $credits = (int)($_POST['credits'] ?? 3);
            $type = sanitizeInput($_POST['type'] ?? 'theory');
            $staff_id = !empty($_POST['staff_id']) ? (int)$_POST['staff_id'] : null;

            $ins = $db->prepare("
                INSERT INTO subjects (code, name, department_id, semester, credits, type, staff_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([$code, $name, $department_id, $semester, $credits, $type, $staff_id]);

            setFlashMessage('success', "Subject '{$code}' created and assigned successfully!");
        } elseif ($action === 'edit') {
            $id = (int)($_POST['subject_id'] ?? 0);
            $code = strtoupper(sanitizeInput($_POST['code'] ?? ''));
            $name = sanitizeInput($_POST['name'] ?? '');
            $department_id = (int)($_POST['department_id'] ?? 0);
            $semester = (int)($_POST['semester'] ?? 1);
            $credits = (int)($_POST['credits'] ?? 3);
            $type = sanitizeInput($_POST['type'] ?? 'theory');
            $staff_id = !empty($_POST['staff_id']) ? (int)$_POST['staff_id'] : null;

            $upd = $db->prepare("
                UPDATE subjects 
                SET code = ?, name = ?, department_id = ?, semester = ?, credits = ?, type = ?, staff_id = ?
                WHERE id = ?
            ");
            $upd->execute([$code, $name, $department_id, $semester, $credits, $type, $staff_id, $id]);

            setFlashMessage('success', "Subject '{$code}' updated successfully!");
        } elseif ($action === 'delete') {
            $id = (int)($_POST['subject_id'] ?? 0);
            try {
                $del = $db->prepare("DELETE FROM subjects WHERE id = ?");
                $del->execute([$id]);
                setFlashMessage('success', 'Subject deleted successfully!');
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Cannot delete subject: It has recorded attendance, assignments, or marks.');
            }
        }
        redirect('admin/manage_subjects.php');
    }
}

$departments = $db->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();
$staffMembers = $db->query("SELECT id, first_name, last_name, staff_code FROM staff ORDER BY first_name ASC")->fetchAll();

$subjects = $db->query("
    SELECT sub.*, d.code AS dept_code, d.name AS dept_name, st.first_name, st.last_name, st.staff_code,
           (SELECT COUNT(*) FROM assignments a WHERE a.subject_id = sub.id) AS assignment_count,
           (SELECT COUNT(*) FROM attendance_sessions ses WHERE ses.subject_id = sub.id) AS session_count
    FROM subjects sub
    JOIN departments d ON sub.department_id = d.id
    LEFT JOIN staff st ON sub.staff_id = st.id
    ORDER BY d.code ASC, sub.semester ASC, sub.code ASC
")->fetchAll();

$pageTitle = "Manage Subjects & Course Loads";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Course Subjects & Faculty Allocation</h1>
                    <p class="text-muted small mb-0">Manage syllabus courses, credits, and assigned faculty instructors</p>
                </div>
                <button type="button" class="btn btn-warning text-dark rounded-pill btn-sm px-4 font-weight-600 shadow-sm" data-bs-toggle="modal" data-bs-target="#newSubjectModal">
                    <i class="fas fa-plus me-1"></i> Add Subject
                </button>
            </div>

            <?php displayAlerts(); ?>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Subject Name</th>
                                <th>Dept</th>
                                <th>Sem</th>
                                <th>Credits</th>
                                <th>Type</th>
                                <th>Assigned Faculty</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($subjects)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No course subjects found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($subjects as $s): ?>
                                    <tr>
                                        <td><span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1"><?= htmlspecialchars($s['code']) ?></span></td>
                                        <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                                        <td><span class="badge bg-warning-light text-dark font-weight-600"><?= htmlspecialchars($s['dept_code']) ?></span></td>
                                        <td>Sem <?= $s['semester'] ?></td>
                                        <td><?= $s['credits'] ?> Credits</td>
                                        <td><span class="badge bg-light text-dark text-uppercase small"><?= htmlspecialchars($s['type']) ?></span></td>
                                        <td>
                                            <?php if (!empty($s['first_name'])): ?>
                                                <strong>Prof. <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></strong>
                                            <?php else: ?>
                                                <span class="text-muted small">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="d-inline-flex align-items-center gap-1">
                                                <!-- View Button (Icon only) -->
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-info rounded-circle" 
                                                        title="View Subject Overview" aria-label="View Subject"
                                                        onclick='viewSubject(<?= json_encode($s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <!-- Edit Button (Icon only) -->
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-warning text-dark rounded-circle" 
                                                        title="Edit Course Subject" aria-label="Edit Subject"
                                                        onclick='editSubject(<?= json_encode($s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <!-- Delete Button (Icon only) -->
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger rounded-circle" 
                                                        title="Delete Subject" aria-label="Delete Subject"
                                                        onclick="deleteSubject(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['name'])) ?>', '<?= htmlspecialchars(addslashes($s['code'])) ?>')">
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

<!-- Modal: New Subject -->
<div class="modal fade" id="newSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark">Add New Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="add">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="code" class="form-label font-weight-500">Subject Code</label>
                            <input type="text" id="code" name="code" class="form-control text-uppercase" placeholder="e.g. CS601" required>
                        </div>
                        <div class="col-md-8">
                            <label for="name" class="form-label font-weight-500">Subject Title</label>
                            <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Cloud Computing & DevOps" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="department_id" class="form-label font-weight-500">Department</label>
                            <select id="department_id" name="department_id" class="form-select" required>
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> (<?= htmlspecialchars($d['code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="semester" class="form-label font-weight-500">Semester</label>
                            <select id="semester" name="semester" class="form-select" required>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?= $i ?>">Semester <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="credits" class="form-label font-weight-500">Credits</label>
                            <input type="number" id="credits" name="credits" class="form-control" value="3" min="1" max="6" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="type" class="form-label font-weight-500">Course Type</label>
                            <select id="type" name="type" class="form-select" required>
                                <option value="theory">Theory / Lecture</option>
                                <option value="lab">Practical / Laboratory</option>
                                <option value="elective">Professional Elective</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="staff_id" class="form-label font-weight-500">Assign Faculty Instructor</label>
                            <select id="staff_id" name="staff_id" class="form-select">
                                <option value="">-- Leave Unassigned --</option>
                                <?php foreach ($staffMembers as $stf): ?>
                                    <option value="<?= $stf['id'] ?>">Prof. <?= htmlspecialchars($stf['first_name'] . ' ' . $stf['last_name']) ?> (<?= htmlspecialchars($stf['staff_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark rounded-pill px-4 font-weight-600">Save Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: View Subject -->
<div class="modal fade" id="viewSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark">
                    <i class="fas fa-book-open text-primary me-2"></i> Subject Overview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <span class="badge bg-primary-lightest text-primary font-weight-bold px-3 py-2 fs-6 me-3" id="viewSubCode"></span>
                    <h5 class="h6 font-weight-bold text-dark mb-0" id="viewSubName"></h5>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Department:</span>
                        <strong class="text-dark" id="viewSubDept"></strong>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Academic Term:</span>
                        <span class="badge bg-light text-dark" id="viewSubSem"></span>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Credits & Type:</span>
                        <span class="badge bg-info-subtle text-info text-uppercase" id="viewSubCreditsType"></span>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Assigned Faculty:</span>
                        <strong class="text-primary" id="viewSubFaculty"></strong>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Recorded Sessions:</span>
                        <span class="badge bg-success-subtle text-success" id="viewSubSessions"></span>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Course Assignments:</span>
                        <span class="badge bg-warning-subtle text-warning" id="viewSubAssignments"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Edit Subject -->
<div class="modal fade" id="editSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark">
                    <i class="fas fa-edit text-warning me-2"></i> Edit Course Subject
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="subject_id" id="editSubjectId">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="editSubCode" class="form-label font-weight-500">Subject Code</label>
                            <input type="text" id="editSubCode" name="code" class="form-control text-uppercase" required>
                        </div>
                        <div class="col-md-8">
                            <label for="editSubName" class="form-label font-weight-500">Subject Title</label>
                            <input type="text" id="editSubName" name="name" class="form-control" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="editSubDept" class="form-label font-weight-500">Department</label>
                            <select id="editSubDept" name="department_id" class="form-select" required>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> (<?= htmlspecialchars($d['code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="editSubSem" class="form-label font-weight-500">Semester</label>
                            <select id="editSubSem" name="semester" class="form-select" required>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?= $i ?>">Semester <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="editSubCredits" class="form-label font-weight-500">Credits</label>
                            <input type="number" id="editSubCredits" name="credits" class="form-control" min="1" max="6" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="editSubType" class="form-label font-weight-500">Course Type</label>
                            <select id="editSubType" name="type" class="form-select" required>
                                <option value="theory">Theory / Lecture</option>
                                <option value="lab">Practical / Laboratory</option>
                                <option value="elective">Professional Elective</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editSubStaff" class="form-label font-weight-500">Assign Faculty Instructor</label>
                            <select id="editSubStaff" name="staff_id" class="form-select">
                                <option value="">-- Leave Unassigned --</option>
                                <?php foreach ($staffMembers as $stf): ?>
                                    <option value="<?= $stf['id'] ?>">Prof. <?= htmlspecialchars($stf['first_name'] . ' ' . $stf['last_name']) ?> (<?= htmlspecialchars($stf['staff_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
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

<!-- Modal: Delete Subject -->
<div class="modal fade" id="deleteSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i> Confirm Subject Deletion
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="subject_id" id="deleteSubjectId">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <p class="mb-0">Are you sure you want to permanently remove <strong id="deleteSubjectCode"></strong> - <strong id="deleteSubjectTitle"></strong>?</p>
                    <small class="text-danger d-block mt-2">Warning: Courses with logged attendance or submitted marks cannot be deleted.</small>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 font-weight-600">Delete Permanently</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewSubject(s) {
    document.getElementById('viewSubCode').innerText = s.code || '';
    document.getElementById('viewSubName').innerText = s.name || '';
    document.getElementById('viewSubDept').innerText = (s.dept_name || '') + ' (' + (s.dept_code || '') + ')';
    document.getElementById('viewSubSem').innerText = 'Semester ' + (s.semester || '1');
    document.getElementById('viewSubCreditsType').innerText = (s.credits || '3') + ' Credits • ' + (s.type || 'Theory');
    document.getElementById('viewSubFaculty').innerText = s.first_name ? ('Prof. ' + s.first_name + ' ' + (s.last_name || '')) : 'Unassigned';
    document.getElementById('viewSubSessions').innerText = (s.session_count || 0) + ' Sessions';
    document.getElementById('viewSubAssignments').innerText = (s.assignment_count || 0) + ' Tasks';
    new bootstrap.Modal(document.getElementById('viewSubjectModal')).show();
}

function editSubject(s) {
    document.getElementById('editSubjectId').value = s.id;
    document.getElementById('editSubCode').value = s.code || '';
    document.getElementById('editSubName').value = s.name || '';
    document.getElementById('editSubDept').value = s.department_id || '';
    document.getElementById('editSubSem').value = s.semester || '1';
    document.getElementById('editSubCredits').value = s.credits || '3';
    document.getElementById('editSubType').value = s.type || 'theory';
    document.getElementById('editSubStaff').value = s.staff_id || '';
    new bootstrap.Modal(document.getElementById('editSubjectModal')).show();
}

function deleteSubject(id, name, code) {
    document.getElementById('deleteSubjectId').value = id;
    document.getElementById('deleteSubjectCode').innerText = code;
    document.getElementById('deleteSubjectTitle').innerText = name;
    new bootstrap.Modal(document.getElementById('deleteSubjectModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
