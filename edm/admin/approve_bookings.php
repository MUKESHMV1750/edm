<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_ADMIN]);

$db = getDB();

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $booking_id = (int)$_POST['booking_id'];
        $new_status = sanitizeInput($_POST['status']);

        $upd = $db->prepare("UPDATE classroom_bookings SET status = ? WHERE id = ?");
        $upd->execute([$new_status, $booking_id]);

        logAudit($_SESSION['user_id'], 'BOOKING_APPROVAL', "Booking #{$booking_id} status changed to {$new_status}");

        setFlashMessage('success', "Booking request updated to '{$new_status}'.");
        redirect('admin/approve_bookings.php');
    }
}

// Fetch All Bookings
$bookings = $db->query("
    SELECT cb.*, c.room_number, c.building_name, c.type AS room_type,
           st.first_name, st.last_name, d.code AS dept_code
    FROM classroom_bookings cb
    JOIN classrooms c ON cb.classroom_id = c.id
    JOIN staff st ON cb.staff_id = st.id
    JOIN departments d ON st.department_id = d.id
    ORDER BY cb.created_at DESC
")->fetchAll();

$pageTitle = "Classroom Booking Approvals";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Smart Lab & Room Reservations</h1>
                    <p class="text-muted small">Review and approve faculty requests for high-tech laboratories and halls</p>
                </div>
            </div>

            <?php displayAlerts(); ?>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Requested By</th>
                                <th>Room / Lab</th>
                                <th>Booking Date</th>
                                <th>Time Slot</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No booking requests found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $b): ?>
                                    <tr>
                                        <td>
                                            <strong>Prof. <?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></strong>
                                            <span class="d-block text-muted text-xs"><?= htmlspecialchars($b['dept_code']) ?></span>
                                        </td>
                                        <td>
                                            <strong>Room <?= htmlspecialchars($b['room_number']) ?></strong>
                                            <span class="d-block text-muted text-xs"><?= htmlspecialchars($b['building_name']) ?></span>
                                        </td>
                                        <td><?= date('d M Y', strtotime($b['booking_date'])) ?></td>
                                        <td class="small"><?= date('h:i A', strtotime($b['start_time'])) ?> - <?= date('h:i A', strtotime($b['end_time'])) ?></td>
                                        <td class="small" style="max-width: 200px;"><?= htmlspecialchars($b['purpose']) ?></td>
                                        <td>
                                            <?php if ($b['status'] === 'approved'): ?>
                                                <span class="badge bg-success text-white font-weight-600 px-3 py-1">Approved</span>
                                            <?php elseif ($b['status'] === 'rejected'): ?>
                                                <span class="badge bg-danger-light text-danger font-weight-600 px-3 py-1">Rejected</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-light text-warning font-weight-600 px-3 py-1">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($b['status'] === 'pending'): ?>
                                                <div class="d-flex gap-1">
                                                    <form action="" method="POST" class="d-inline">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                        <input type="hidden" name="status" value="approved">
                                                        <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 font-weight-600">Approve</button>
                                                    </form>
                                                    <form action="" method="POST" class="d-inline">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                        <input type="hidden" name="status" value="rejected">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3 font-weight-600">Reject</button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">Resolved</span>
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
