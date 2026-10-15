<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STAFF]);

$staffId = $_SESSION['role_id'] ?? 0;
$db = getDB();

// Fetch Booking History
$bStmt = $db->prepare("
    SELECT cb.*, c.room_number, COALESCE(c.building_name, c.building, 'Academic Block') AS building_name, c.type AS room_type
    FROM classroom_bookings cb
    JOIN classrooms c ON cb.classroom_id = c.id
    WHERE cb.staff_id = ?
    ORDER BY cb.created_at DESC
");
$bStmt->execute([$staffId]);
$bookings = $bStmt->fetchAll();

$pageTitle = "Classroom Booking History";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Classroom Booking Requests</h1>
                    <p class="text-muted small">Status tracking for lab and hall reservations</p>
                </div>
                <a href="<?= APP_URL ?>/staff/book_classroom.php" class="btn btn-warning text-dark rounded-pill btn-sm px-4 font-weight-600">
                    <i class="fas fa-plus me-1"></i> New Request
                </a>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Classroom</th>
                                <th>Date</th>
                                <th>Time Slot</th>
                                <th>Purpose</th>
                                <th>Requested On</th>
                                <th>Approval Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No booking requests submitted yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $b): ?>
                                    <tr>
                                        <td><strong>Room <?= htmlspecialchars($b['room_number']) ?> (<?= htmlspecialchars($b['building_name']) ?>)</strong></td>
                                        <td><?= date('d M Y', strtotime($b['booking_date'])) ?></td>
                                        <td class="small"><?= date('h:i A', strtotime($b['start_time'])) ?> - <?= date('h:i A', strtotime($b['end_time'])) ?></td>
                                        <td class="small" style="max-width: 250px;"><?= htmlspecialchars($b['purpose']) ?></td>
                                        <td class="text-muted text-xs"><?= date('d M Y', strtotime($b['created_at'])) ?></td>
                                        <td>
                                            <?php if ($b['status'] === 'approved'): ?>
                                                <span class="badge bg-success text-white font-weight-600 px-3 py-1">Approved</span>
                                            <?php elseif ($b['status'] === 'rejected'): ?>
                                                <span class="badge bg-danger-light text-danger font-weight-600 px-3 py-1">Rejected</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-light text-warning font-weight-600 px-3 py-1">Pending Approval</span>
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
