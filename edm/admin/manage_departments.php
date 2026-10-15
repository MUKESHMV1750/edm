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
            $name = sanitizeInput($_POST['name'] ?? '');
            $code = strtoupper(sanitizeInput($_POST['code'] ?? ''));
            $hod_name = sanitizeInput($_POST['hod_name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');

            $ins = $db->prepare("INSERT INTO departments (name, code, hod_name, description) VALUES (?, ?, ?, ?)");
            $ins->execute([$name, $code, $hod_name, $description]);

            setFlashMessage('success', "Department '{$code}' added successfully!");
        } elseif ($action === 'edit') {
            $id = (int)($_POST['department_id'] ?? 0);
            $name = sanitizeInput($_POST['name'] ?? '');
            $code = strtoupper(sanitizeInput($_POST['code'] ?? ''));
            $hod_name = sanitizeInput($_POST['hod_name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');

            $upd = $db->prepare("UPDATE departments SET name = ?, code = ?, hod_name = ?, description = ? WHERE id = ?");
            $upd->execute([$name, $code, $hod_name, $description, $id]);

            setFlashMessage('success', "Department '{$code}' updated successfully!");
        } elseif ($action === 'delete') {
            $id = (int)($_POST['department_id'] ?? 0);
            try {
                $del = $db->prepare("DELETE FROM departments WHERE id = ?");
                $del->execute([$id]);
                setFlashMessage('success', 'Department deleted successfully!');
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Cannot delete department: It is currently linked to enrolled students, faculty, or subjects.');
            }
        }
        redirect('admin/manage_departments.php');
    }
}

// Fetch departments with counts
$departments = $db->query("
    SELECT d.*, 
           (SELECT COUNT(*) FROM subjects sub WHERE sub.department_id = d.id) AS subject_count,
           (SELECT COUNT(*) FROM staff st WHERE st.department_id = d.id) AS staff_count,
           (SELECT COUNT(*) FROM students stu WHERE stu.department_id = d.id) AS student_count
    FROM departments d 
    ORDER BY d.name ASC
")->fetchAll();

$pageTitle = "Manage Departments";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Engineering Departments</h1>
                    <p class="text-muted small mb-0">Configure academic departments, codes, and Head of Department (HOD) leaders</p>
                </div>
                <button type="button" class="btn btn-warning text-dark rounded-pill btn-sm px-4 font-weight-600 shadow-sm" data-bs-toggle="modal" data-bs-target="#newDeptModal">
                    <i class="fas fa-plus me-1"></i> Add Department
                </button>
            </div>

            <?php displayAlerts(); ?>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Department Name</th>
                                <th>HOD Name</th>
                                <th>Faculty</th>
                                <th>Subjects</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($departments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No departments configured yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($departments as $d): ?>
                                    <tr>
                                        <td><span class="badge bg-warning-light text-dark font-weight-600 px-3 py-1"><?= htmlspecialchars($d['code']) ?></span></td>
                                        <td>
                                            <strong><?= htmlspecialchars($d['name']) ?></strong>
                                            <?php if (!empty($d['description'])): ?>
                                                <span class="d-block text-muted text-xs text-truncate" style="max-width: 280px;"><?= htmlspecialchars($d['description']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($d['hod_name'] ?? 'Not Assigned') ?></td>
                                        <td><span class="badge bg-primary-subtle text-primary font-weight-600"><?= $d['staff_count'] ?> Faculty</span></td>
                                        <td><span class="badge bg-info-subtle text-info font-weight-600"><?= $d['subject_count'] ?> Courses</span></td>
                                        <td class="text-end pe-4">
                                            <div class="d-inline-flex align-items-center gap-1">
                                                <!-- View Button (Icon only) -->
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-info rounded-circle" 
                                                        title="View Department" aria-label="View Department"
                                                        onclick='viewDept(<?= json_encode($d, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <!-- Edit Button (Icon only) -->
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-warning text-dark rounded-circle" 
                                                        title="Edit Department" aria-label="Edit Department"
                                                        onclick='editDept(<?= json_encode($d, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <!-- Delete Button (Icon only) -->
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger rounded-circle" 
                                                        title="Delete Department" aria-label="Delete Department"
                                                        onclick="deleteDept(<?= $d['id'] ?>, '<?= htmlspecialchars(addslashes($d['name'])) ?>')">
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

<!-- Modal: New Department -->
<div class="modal fade" id="newDeptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark">Add New Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="add">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="name" class="form-label font-weight-500">Department Name</label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Information Technology" required>
                    </div>

                    <div class="mb-3">
                        <label for="code" class="form-label font-weight-500">Department Code</label>
                        <input type="text" id="code" name="code" class="form-control text-uppercase" placeholder="e.g. IT" required>
                    </div>

                    <div class="mb-3">
                        <label for="hod_name" class="form-label font-weight-500">HOD Name</label>
                        <input type="text" id="hod_name" name="hod_name" class="form-control" placeholder="Dr. S. K. Sharma">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label font-weight-500">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="2" placeholder="Brief outline of departmental specializations"></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark rounded-pill px-4 font-weight-600">Save Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: View Department -->
<div class="modal fade" id="viewDeptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark">
                    <i class="fas fa-university text-primary me-2"></i> Department Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <span class="badge bg-warning-light text-dark font-weight-bold px-3 py-2 fs-6 me-3" id="viewDeptCode"></span>
                    <h5 class="h6 font-weight-bold text-dark mb-0" id="viewDeptName"></h5>
                </div>
                <div class="list-group list-group-flush mb-3">
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Head of Department (HOD):</span>
                        <strong class="text-dark" id="viewDeptHod"></strong>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Assigned Faculty Members:</span>
                        <span class="badge bg-primary text-white" id="viewDeptStaffCount"></span>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Total Active Courses:</span>
                        <span class="badge bg-info text-white" id="viewDeptSubjectCount"></span>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Enrolled Students:</span>
                        <span class="badge bg-success text-white" id="viewDeptStudentCount"></span>
                    </div>
                </div>
                <div>
                    <label class="text-muted small d-block mb-1 font-weight-500">Department Description:</label>
                    <div class="p-3 bg-light rounded-3 text-dark small" id="viewDeptDesc"></div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Edit Department -->
<div class="modal fade" id="editDeptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark">
                    <i class="fas fa-edit text-warning me-2"></i> Edit Department
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="department_id" id="editDeptId">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="editDeptNameInput" class="form-label font-weight-500">Department Name</label>
                        <input type="text" id="editDeptNameInput" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="editDeptCodeInput" class="form-label font-weight-500">Department Code</label>
                        <input type="text" id="editDeptCodeInput" name="code" class="form-control text-uppercase" required>
                    </div>

                    <div class="mb-3">
                        <label for="editDeptHodInput" class="form-label font-weight-500">HOD Name</label>
                        <input type="text" id="editDeptHodInput" name="hod_name" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="editDeptDescInput" class="form-label font-weight-500">Description</label>
                        <textarea id="editDeptDescInput" name="description" class="form-control" rows="2"></textarea>
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

<!-- Modal: Delete Department -->
<div class="modal fade" id="deleteDeptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i> Confirm Deletion
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="department_id" id="deleteDeptId">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <p class="mb-0">Are you sure you want to permanently delete department <strong id="deleteDeptName"></strong>?</p>
                    <small class="text-danger d-block mt-2">Note: Departments linked to courses or faculty members cannot be deleted.</small>
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
function viewDept(d) {
    document.getElementById('viewDeptCode').innerText = d.code || '';
    document.getElementById('viewDeptName').innerText = d.name || '';
    document.getElementById('viewDeptHod').innerText = d.hod_name || 'Not Assigned';
    document.getElementById('viewDeptStaffCount').innerText = (d.staff_count || 0) + ' Faculty';
    document.getElementById('viewDeptSubjectCount').innerText = (d.subject_count || 0) + ' Courses';
    document.getElementById('viewDeptStudentCount').innerText = (d.student_count || 0) + ' Students';
    document.getElementById('viewDeptDesc').innerText = d.description || 'No description provided.';
    new bootstrap.Modal(document.getElementById('viewDeptModal')).show();
}

function editDept(d) {
    document.getElementById('editDeptId').value = d.id;
    document.getElementById('editDeptNameInput').value = d.name || '';
    document.getElementById('editDeptCodeInput').value = d.code || '';
    document.getElementById('editDeptHodInput').value = d.hod_name || '';
    document.getElementById('editDeptDescInput').value = d.description || '';
    new bootstrap.Modal(document.getElementById('editDeptModal')).show();
}

function deleteDept(id, name) {
    document.getElementById('deleteDeptId').value = id;
    document.getElementById('deleteDeptName').innerText = name;
    new bootstrap.Modal(document.getElementById('deleteDeptModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
