<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_ADMIN]);

$db = getDB();

// Handle Create Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $staff_code = sanitizeInput($_POST['staff_code']);
        $first_name = sanitizeInput($_POST['first_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $department_id = (int)$_POST['department_id'];
        $designation = sanitizeInput($_POST['designation']);
        $qualification = sanitizeInput($_POST['qualification']);
        $specialization = sanitizeInput($_POST['specialization']);
        $phone = sanitizeInput($_POST['phone']);

        try {
            $db->beginTransaction();

            $insU = $db->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'staff', 'active')");
            $insU->execute([$username, $email, $password]);
            $userId = $db->lastInsertId();

            $insStf = $db->prepare("
                INSERT INTO staff (user_id, staff_code, first_name, last_name, phone, department_id, designation, qualification, specialization, join_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
            ");
            $insStf->execute([$userId, $staff_code, $first_name, $last_name, $phone, $department_id, $designation, $qualification, $specialization]);

            $db->commit();
            setFlashMessage('success', 'Faculty staff account created successfully!');
            redirect('admin/manage_staff.php');
        } catch (Exception $e) {
            $db->rollBack();
            setFlashMessage('danger', 'Error creating staff: ' . $e->getMessage());
        }
    }
}

// Handle Edit Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $stfId = (int)$_POST['staff_id'];
        $email = sanitizeInput($_POST['email']);
        $staff_code = sanitizeInput($_POST['staff_code']);
        $first_name = sanitizeInput($_POST['first_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $department_id = (int)$_POST['department_id'];
        $designation = sanitizeInput($_POST['designation']);
        $qualification = sanitizeInput($_POST['qualification']);
        $specialization = sanitizeInput($_POST['specialization']);
        $phone = sanitizeInput($_POST['phone']);

        try {
            $db->beginTransaction();

            $stfUsr = $db->prepare("SELECT user_id FROM staff WHERE id = ?");
            $stfUsr->execute([$stfId]);
            $uId = $stfUsr->fetchColumn();

            if ($uId) {
                $db->prepare("UPDATE users SET email = ? WHERE id = ?")->execute([$email, $uId]);
            }

            $updStf = $db->prepare("
                UPDATE staff 
                SET staff_code = ?, first_name = ?, last_name = ?, phone = ?, department_id = ?, designation = ?, qualification = ?, specialization = ?
                WHERE id = ?
            ");
            $updStf->execute([$staff_code, $first_name, $last_name, $phone, $department_id, $designation, $qualification, $specialization, $stfId]);

            $db->commit();
            setFlashMessage('success', 'Faculty details updated successfully!');
        } catch (Exception $e) {
            $db->rollBack();
            setFlashMessage('danger', 'Error updating faculty: ' . $e->getMessage());
        }
        redirect('admin/manage_staff.php');
    }
}

// Delete Staff
if (($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') || isset($_GET['delete'])) {
    $stfId = (int)($_POST['staff_id'] ?? $_GET['delete'] ?? 0);
    $stfUsr = $db->prepare("SELECT user_id FROM staff WHERE id = ?");
    $stfUsr->execute([$stfId]);
    $uId = $stfUsr->fetchColumn();

    if ($uId) {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$uId]);
        setFlashMessage('success', 'Faculty account removed successfully.');
    }
    redirect('admin/manage_staff.php');
}

$departments = $db->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();
$staffMembers = $db->query("
    SELECT st.*, u.username, u.email, d.name AS dept_name, d.code AS dept_code
    FROM staff st
    JOIN users u ON st.user_id = u.id
    JOIN departments d ON st.department_id = d.id
    ORDER BY st.id DESC
")->fetchAll();

$pageTitle = "Manage Faculty & Staff";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Faculty & Staff Management</h1>
                    <p class="text-muted small">Register professors, assistant professors, and departmental instructors</p>
                </div>
                <button type="button" class="btn btn-success rounded-pill btn-sm px-4 font-weight-600" data-bs-toggle="modal" data-bs-target="#newStaffModal">
                    <i class="fas fa-user-plus me-1"></i> Add Faculty Member
                </button>
            </div>

            <?php displayAlerts(); ?>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Staff Code</th>
                                <th>Faculty Name</th>
                                <th>Designation</th>
                                <th>Department</th>
                                <th>Specialization</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($staffMembers)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No faculty members found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($staffMembers as $stf): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($stf['staff_code']) ?></strong></td>
                                        <td>
                                            <strong>Prof. <?= htmlspecialchars($stf['first_name'] . ' ' . $stf['last_name']) ?></strong>
                                            <span class="d-block text-muted text-xs"><?= htmlspecialchars($stf['email']) ?></span>
                                        </td>
                                        <td><span class="badge bg-light text-dark font-weight-600"><?= htmlspecialchars($stf['designation']) ?></span></td>
                                        <td><span class="badge bg-primary-lightest text-primary font-weight-600"><?= htmlspecialchars($stf['dept_code']) ?></span></td>
                                        <td class="small"><?= htmlspecialchars($stf['specialization']) ?></td>
                                        <td class="text-end pe-4">
                                            <div class="d-inline-flex align-items-center gap-1">
                                                <!-- View Button (Icon only) -->
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-info rounded-circle" 
                                                        title="View Faculty Details" aria-label="View"
                                                        onclick='viewStaff(<?= json_encode($stf, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <!-- Edit Button (Icon only) -->
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-warning text-dark rounded-circle" 
                                                        title="Edit Faculty Member" aria-label="Edit"
                                                        onclick='editStaff(<?= json_encode($stf, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <!-- Delete Button (Icon only) -->
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger rounded-circle" 
                                                        title="Remove Faculty Member" aria-label="Delete"
                                                        onclick="deleteStaff(<?= $stf['id'] ?>, '<?= htmlspecialchars(addslashes($stf['first_name'] . ' ' . $stf['last_name'])) ?>', '<?= htmlspecialchars(addslashes($stf['staff_code'])) ?>')">
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

<!-- Modal: New Staff -->
<div class="modal fade" id="newStaffModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark"><i class="fas fa-user-plus text-success me-2"></i> Add Faculty Staff Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="create">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label font-weight-500">First Name</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label font-weight-500">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label font-weight-500">Username</label>
                            <input type="text" id="username" name="username" class="form-control" placeholder="e.g. rajesh.s" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label font-weight-500">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="rajesh@edms.edu" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="staff_code" class="form-label font-weight-500">Staff Code</label>
                            <input type="text" id="staff_code" name="staff_code" class="form-control" placeholder="STF009" required>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label font-weight-500">Initial Password</label>
                            <input type="password" id="password" name="password" class="form-control" value="staff123" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="department_id" class="form-label font-weight-500">Department</label>
                            <select id="department_id" name="department_id" class="form-select" required>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="designation" class="form-label font-weight-500">Designation</label>
                            <select id="designation" name="designation" class="form-select" required>
                                <option value="Assistant Professor">Assistant Professor</option>
                                <option value="Associate Professor">Associate Professor</option>
                                <option value="Professor">Professor</option>
                                <option value="Head of Department">Head of Department (HOD)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="qualification" class="form-label font-weight-500">Qualification</label>
                            <input type="text" id="qualification" name="qualification" class="form-control" placeholder="M.Tech, Ph.D" required>
                        </div>
                        <div class="col-md-6">
                            <label for="specialization" class="form-label font-weight-500">Specialization</label>
                            <input type="text" id="specialization" name="specialization" class="form-control" placeholder="Data Structures / ML" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label font-weight-500">Phone Number</label>
                        <input type="text" id="phone" name="phone" class="form-control" placeholder="9876543210" required>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 font-weight-600">Add Faculty Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: View Staff -->
<div class="modal fade" id="viewStaffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark">
                    <i class="fas fa-user-tie text-primary me-2"></i> Faculty Profile
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <span class="badge bg-primary-lightest text-primary font-weight-bold px-3 py-2 fs-6 me-3" id="viewStaffCode"></span>
                    <div>
                        <h5 class="h6 font-weight-bold text-dark mb-0" id="viewStaffName"></h5>
                        <small class="text-muted" id="viewStaffEmail"></small>
                    </div>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Designation:</span>
                        <strong class="text-dark" id="viewStaffDesignation"></strong>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Department:</span>
                        <span class="badge bg-warning-light text-dark" id="viewStaffDept"></span>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Qualification:</span>
                        <span class="text-dark font-weight-500" id="viewStaffQual"></span>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Specialization:</span>
                        <span class="text-dark" id="viewStaffSpec"></span>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Contact Phone:</span>
                        <span class="text-dark" id="viewStaffPhone"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Edit Staff -->
<div class="modal fade" id="editStaffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark">
                    <i class="fas fa-user-edit text-warning me-2"></i> Edit Faculty Member
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="staff_id" id="editStaffId">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="editStaffFirstName" class="form-label font-weight-500">First Name</label>
                            <input type="text" id="editStaffFirstName" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editStaffLastName" class="form-label font-weight-500">Last Name</label>
                            <input type="text" id="editStaffLastName" name="last_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="editStaffCode" class="form-label font-weight-500">Staff Code</label>
                            <input type="text" id="editStaffCode" name="staff_code" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editStaffEmail" class="form-label font-weight-500">Email Address</label>
                            <input type="email" id="editStaffEmail" name="email" class="form-control" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="editStaffDept" class="form-label font-weight-500">Department</label>
                            <select id="editStaffDept" name="department_id" class="form-select" required>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editStaffDesignation" class="form-label font-weight-500">Designation</label>
                            <select id="editStaffDesignation" name="designation" class="form-select" required>
                                <option value="Assistant Professor">Assistant Professor</option>
                                <option value="Associate Professor">Associate Professor</option>
                                <option value="Professor">Professor</option>
                                <option value="Head of Department">Head of Department (HOD)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="editStaffQualification" class="form-label font-weight-500">Qualification</label>
                            <input type="text" id="editStaffQualification" name="qualification" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editStaffSpec" class="form-label font-weight-500">Specialization</label>
                            <input type="text" id="editStaffSpec" name="specialization" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="editStaffPhone" class="form-label font-weight-500">Phone Number</label>
                        <input type="text" id="editStaffPhone" name="phone" class="form-control" required>
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

<!-- Modal: Delete Staff -->
<div class="modal fade" id="deleteStaffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i> Remove Faculty Member
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="staff_id" id="deleteStaffId">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <p class="mb-0">Are you sure you want to remove <strong id="deleteStaffName"></strong> (<strong id="deleteStaffCode"></strong>)?</p>
                    <small class="text-danger d-block mt-2">Warning: This action will permanently remove the faculty login account and department allocations.</small>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 font-weight-600">Remove Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewStaff(s) {
    document.getElementById('viewStaffCode').innerText = s.staff_code || '';
    document.getElementById('viewStaffName').innerText = 'Prof. ' + (s.first_name || '') + ' ' + (s.last_name || '');
    document.getElementById('viewStaffEmail').innerText = s.email || '';
    document.getElementById('viewStaffDesignation').innerText = s.designation || '';
    document.getElementById('viewStaffDept').innerText = (s.dept_name || '') + ' (' + (s.dept_code || '') + ')';
    document.getElementById('viewStaffQual').innerText = s.qualification || 'N/A';
    document.getElementById('viewStaffSpec').innerText = s.specialization || 'General';
    document.getElementById('viewStaffPhone').innerText = s.phone || 'N/A';
    new bootstrap.Modal(document.getElementById('viewStaffModal')).show();
}

function editStaff(s) {
    document.getElementById('editStaffId').value = s.id;
    document.getElementById('editStaffFirstName').value = s.first_name || '';
    document.getElementById('editStaffLastName').value = s.last_name || '';
    document.getElementById('editStaffCode').value = s.staff_code || '';
    document.getElementById('editStaffEmail').value = s.email || '';
    document.getElementById('editStaffDept').value = s.department_id || '';
    document.getElementById('editStaffDesignation').value = s.designation || 'Assistant Professor';
    document.getElementById('editStaffQualification').value = s.qualification || '';
    document.getElementById('editStaffSpec').value = s.specialization || '';
    document.getElementById('editStaffPhone').value = s.phone || '';
    new bootstrap.Modal(document.getElementById('editStaffModal')).show();
}

function deleteStaff(id, name, code) {
    document.getElementById('deleteStaffId').value = id;
    document.getElementById('deleteStaffName').innerText = name;
    document.getElementById('deleteStaffCode').innerText = code;
    new bootstrap.Modal(document.getElementById('deleteStaffModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
