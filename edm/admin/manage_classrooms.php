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
            $room_number = sanitizeInput($_POST['room_number'] ?? '');
            $building_name = sanitizeInput($_POST['building_name'] ?? '');
            $capacity = (int)($_POST['capacity'] ?? 60);
            $type = sanitizeInput($_POST['type'] ?? 'lecture');
            $has_projector = isset($_POST['has_projector']) ? 1 : 0;
            $has_ac = isset($_POST['has_ac']) ? 1 : 0;

            $ins = $db->prepare("
                INSERT INTO classrooms (room_number, building_name, capacity, type, has_projector, has_ac)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([$room_number, $building_name, $capacity, $type, $has_projector, $has_ac]);

            setFlashMessage('success', "Room '{$room_number}' added successfully!");
        } elseif ($action === 'edit') {
            $id = (int)($_POST['classroom_id'] ?? 0);
            $room_number = sanitizeInput($_POST['room_number'] ?? '');
            $building_name = sanitizeInput($_POST['building_name'] ?? '');
            $capacity = (int)($_POST['capacity'] ?? 60);
            $type = sanitizeInput($_POST['type'] ?? 'lecture');
            $has_projector = isset($_POST['has_projector']) ? 1 : 0;
            $has_ac = isset($_POST['has_ac']) ? 1 : 0;

            $upd = $db->prepare("
                UPDATE classrooms 
                SET room_number = ?, building_name = ?, capacity = ?, type = ?, has_projector = ?, has_ac = ?
                WHERE id = ?
            ");
            $upd->execute([$room_number, $building_name, $capacity, $type, $has_projector, $has_ac, $id]);

            setFlashMessage('success', "Room '{$room_number}' updated successfully!");
        } elseif ($action === 'delete') {
            $id = (int)($_POST['classroom_id'] ?? 0);
            try {
                $del = $db->prepare("DELETE FROM classrooms WHERE id = ?");
                $del->execute([$id]);
                setFlashMessage('success', 'Classroom / Lab removed successfully!');
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Cannot delete room: It is currently referenced by timetables, attendance sessions, or reservations.');
            }
        }
        redirect('admin/manage_classrooms.php');
    }
}

$classrooms = $db->query("
    SELECT c.*,
           (SELECT COUNT(*) FROM classroom_bookings cb WHERE cb.classroom_id = c.id) AS booking_count,
           (SELECT COUNT(*) FROM attendance_sessions ses WHERE ses.classroom_id = c.id) AS session_count
    FROM classrooms c 
    ORDER BY c.room_number ASC
")->fetchAll();

$pageTitle = "Manage Classrooms & Labs";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Classrooms & Laboratories</h1>
                    <p class="text-muted small mb-0">Manage physical rooms, capacities, and AV equipment specifications</p>
                </div>
                <button type="button" class="btn btn-warning text-dark rounded-pill btn-sm px-4 font-weight-600 shadow-sm" data-bs-toggle="modal" data-bs-target="#newRoomModal">
                    <i class="fas fa-plus me-1"></i> Add Room / Lab
                </button>
            </div>

            <?php displayAlerts(); ?>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Room Number</th>
                                <th>Building</th>
                                <th>Capacity</th>
                                <th>Type</th>
                                <th>Equipment / Amenities</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($classrooms)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No classrooms or laboratories configured yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($classrooms as $c): ?>
                                    <tr>
                                        <td><strong>Room <?= htmlspecialchars($c['room_number']) ?></strong></td>
                                        <td><?= htmlspecialchars($c['building_name'] ?? 'Main Campus') ?></td>
                                        <td><strong><?= $c['capacity'] ?> Seats</strong></td>
                                        <td><span class="badge bg-light text-dark text-uppercase small"><?= htmlspecialchars($c['type']) ?></span></td>
                                        <td>
                                            <?php if ($c['has_projector']): ?>
                                                <span class="badge bg-info-light text-info me-1"><i class="fas fa-video me-1"></i> Projector</span>
                                            <?php endif; ?>
                                            <?php if ($c['has_ac']): ?>
                                                <span class="badge bg-success-light text-success me-1"><i class="fas fa-snowflake me-1"></i> AC</span>
                                            <?php endif; ?>
                                            <?php if (!$c['has_projector'] && !$c['has_ac']): ?>
                                                <span class="text-muted small">Standard Board</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="d-inline-flex align-items-center gap-1">
                                                <!-- View Button (Icon only) -->
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-info rounded-circle" 
                                                        title="View Room Specifications" aria-label="View Room"
                                                        onclick='viewRoom(<?= json_encode($c, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <!-- Edit Button (Icon only) -->
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-warning text-dark rounded-circle" 
                                                        title="Edit Classroom / Lab" aria-label="Edit Room"
                                                        onclick='editRoom(<?= json_encode($c, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <!-- Delete Button (Icon only) -->
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger rounded-circle" 
                                                        title="Delete Room" aria-label="Delete Room"
                                                        onclick="deleteRoom(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['room_number'])) ?>')">
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

<!-- Modal: New Room -->
<div class="modal fade" id="newRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark">Add New Classroom / Lab</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="add">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="room_number" class="form-label font-weight-500">Room Number</label>
                            <input type="text" id="room_number" name="room_number" class="form-control" placeholder="e.g. 301 or LAB-A" required>
                        </div>
                        <div class="col-md-6">
                            <label for="building_name" class="form-label font-weight-500">Building / Block</label>
                            <input type="text" id="building_name" name="building_name" class="form-control" placeholder="e.g. Turing Block" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="capacity" class="form-label font-weight-500">Seating Capacity</label>
                            <input type="number" id="capacity" name="capacity" class="form-control" value="60" min="5" max="300" required>
                        </div>
                        <div class="col-md-6">
                            <label for="type" class="form-label font-weight-500">Space Type</label>
                            <select id="type" name="type" class="form-select" required>
                                <option value="lecture">Lecture Classroom</option>
                                <option value="lab">Computer / Science Lab</option>
                                <option value="seminar">Seminar / Auditorium</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-500 d-block">Amenities & Hardware</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="has_projector" name="has_projector" value="1" checked>
                            <label class="form-check-label" for="has_projector">Digital AV Projector</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="has_ac" name="has_ac" value="1">
                            <label class="form-check-label" for="has_ac">Air Conditioning</label>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark rounded-pill px-4 font-weight-600">Save Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: View Room -->
<div class="modal fade" id="viewRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark">
                    <i class="fas fa-door-open text-primary me-2"></i> Room Specifications
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <span class="badge bg-warning-light text-dark font-weight-bold px-3 py-2 fs-6 me-3" id="viewRoomNum"></span>
                    <h5 class="h6 font-weight-bold text-dark mb-0" id="viewRoomBuilding"></h5>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Seating Capacity:</span>
                        <strong class="text-dark" id="viewRoomCapacity"></strong>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Space Categorization:</span>
                        <span class="badge bg-light text-dark text-uppercase" id="viewRoomType"></span>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Installed Amenities:</span>
                        <div id="viewRoomAmenities"></div>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Recorded Sessions Held:</span>
                        <span class="badge bg-info-subtle text-info" id="viewRoomSessions"></span>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted">Faculty Booking Requests:</span>
                        <span class="badge bg-success-subtle text-success" id="viewRoomBookings"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Edit Room -->
<div class="modal fade" id="editRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark">
                    <i class="fas fa-edit text-warning me-2"></i> Edit Classroom / Lab
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="classroom_id" id="editClassroomId">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="editRoomNumber" class="form-label font-weight-500">Room Number</label>
                            <input type="text" id="editRoomNumber" name="room_number" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editBuildingName" class="form-label font-weight-500">Building / Block</label>
                            <input type="text" id="editBuildingName" name="building_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="editCapacity" class="form-label font-weight-500">Seating Capacity</label>
                            <input type="number" id="editCapacity" name="capacity" class="form-control" min="5" max="300" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editType" class="form-label font-weight-500">Space Type</label>
                            <select id="editType" name="type" class="form-select" required>
                                <option value="lecture">Lecture Classroom</option>
                                <option value="lab">Computer / Science Lab</option>
                                <option value="seminar">Seminar / Auditorium</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-500 d-block">Amenities & Hardware</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="editHasProjector" name="has_projector" value="1">
                            <label class="form-check-label" for="editHasProjector">Digital AV Projector</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="editHasAc" name="has_ac" value="1">
                            <label class="form-check-label" for="editHasAc">Air Conditioning</label>
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

<!-- Modal: Delete Room -->
<div class="modal fade" id="deleteRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i> Confirm Room Deletion
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="classroom_id" id="deleteRoomId">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <p class="mb-0">Are you sure you want to permanently delete room <strong id="deleteRoomTitle"></strong>?</p>
                    <small class="text-danger d-block mt-2">Warning: Rooms linked to active schedules or bookings cannot be deleted.</small>
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
function viewRoom(c) {
    document.getElementById('viewRoomNum').innerText = 'Room ' + (c.room_number || '');
    document.getElementById('viewRoomBuilding').innerText = c.building_name || 'Main Campus';
    document.getElementById('viewRoomCapacity').innerText = (c.capacity || '0') + ' Seats';
    document.getElementById('viewRoomType').innerText = c.type || 'Lecture';
    
    let amenitiesHtml = '';
    if (parseInt(c.has_projector) === 1) {
        amenitiesHtml += '<span class="badge bg-info-light text-info me-1"><i class="fas fa-video me-1"></i> Projector</span>';
    }
    if (parseInt(c.has_ac) === 1) {
        amenitiesHtml += '<span class="badge bg-success-light text-success me-1"><i class="fas fa-snowflake me-1"></i> AC</span>';
    }
    if (!amenitiesHtml) {
        amenitiesHtml = '<span class="text-muted small">Standard Board</span>';
    }
    document.getElementById('viewRoomAmenities').innerHTML = amenitiesHtml;
    document.getElementById('viewRoomSessions').innerText = (c.session_count || 0) + ' Sessions';
    document.getElementById('viewRoomBookings').innerText = (c.booking_count || 0) + ' Bookings';

    new bootstrap.Modal(document.getElementById('viewRoomModal')).show();
}

function editRoom(c) {
    document.getElementById('editClassroomId').value = c.id;
    document.getElementById('editRoomNumber').value = c.room_number || '';
    document.getElementById('editBuildingName').value = c.building_name || '';
    document.getElementById('editCapacity').value = c.capacity || '60';
    document.getElementById('editType').value = c.type || 'lecture';
    document.getElementById('editHasProjector').checked = parseInt(c.has_projector) === 1;
    document.getElementById('editHasAc').checked = parseInt(c.has_ac) === 1;

    new bootstrap.Modal(document.getElementById('editRoomModal')).show();
}

function deleteRoom(id, roomNumber) {
    document.getElementById('deleteRoomId').value = id;
    document.getElementById('deleteRoomTitle').innerText = 'Room ' + roomNumber;
    new bootstrap.Modal(document.getElementById('deleteRoomModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
