<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';
checkRole([ROLE_ADMIN]);

$db = getDB();

$bookings = $db->query("
    SELECT cb.*, c.room_number, c.building_name, st.first_name, st.last_name
    FROM classroom_bookings cb
    JOIN classrooms c ON cb.classroom_id = c.id
    JOIN staff st ON cb.staff_id = st.id
    ORDER BY cb.booking_date DESC, cb.start_time DESC
")->fetchAll();

$pageTitle = "Admin Classroom Bookings Report";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Classroom & Lab Reservations Log</h1>
                    <p class="text-muted small">Comprehensive report of smart room allocations and approval states</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Room Number</th>
                                <th>Faculty Staff</th>
                                <th>Booking Date</th>
                                <th>Slot Time</th>
                                <th>Purpose</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td><strong>Room <?= htmlspecialchars($b['room_number']) ?> (<?= htmlspecialchars($b['building_name']) ?>)</strong></td>
                                    <td>Prof. <?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></td>
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
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
