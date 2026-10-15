<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STAFF]);

$staffId = $_SESSION['role_id'] ?? 0;
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $classroom_id = (int)$_POST['classroom_id'];
        $booking_date = $_POST['booking_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $purpose = sanitizeInput($_POST['purpose']);

        // Check for conflicting approved bookings
        $chkStmt = $db->prepare("
            SELECT COUNT(*) FROM classroom_bookings 
            WHERE classroom_id = ? AND booking_date = ? AND status = 'approved'
              AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))
        ");
        $chkStmt->execute([$classroom_id, $booking_date, $start_time, $start_time, $end_time, $end_time]);
        $conflictCount = $chkStmt->fetchColumn();

        if ($conflictCount > 0) {
            setFlashMessage('danger', 'Selected classroom is already booked for this time slot.');
        } else {
            $ins = $db->prepare("
                INSERT INTO classroom_bookings (staff_id, classroom_id, booking_date, start_time, end_time, purpose, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            $ins->execute([$staffId, $classroom_id, $booking_date, $start_time, $end_time, $purpose]);

            setFlashMessage('success', 'Smart classroom booking request submitted to Admin for approval!');
            redirect('staff/booking_history.php');
        }
    }
}

// Fetch Classrooms
$roomStmt = $db->prepare("SELECT * FROM classrooms ORDER BY room_number ASC");
$roomStmt->execute();
$classrooms = $roomStmt->fetchAll();

$pageTitle = "Book Smart Classroom / Lab";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Book Smart Lab / Classroom</h1>
                    <p class="text-muted small">Reserve high-tech department laboratories and lecture halls</p>
                </div>
                <a href="<?= APP_URL ?>/staff/booking_history.php" class="btn btn-outline-primary rounded-pill btn-sm">
                    <i class="fas fa-history me-1"></i> My Bookings History
                </a>
            </div>

            <?php displayAlerts(); ?>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
                        
                        <form action="" method="POST">
                            <?= csrfField() ?>

                            <div class="mb-3">
                                <label for="classroom_id" class="form-label font-weight-500">Select Room / Lab</label>
                                <select id="classroom_id" name="classroom_id" class="form-select" required>
                                    <option value="">-- Select Classroom / Lab --</option>
                                    <?php foreach ($classrooms as $c): ?>
                                        <option value="<?= $c['id'] ?>">
                                            Room <?= htmlspecialchars($c['room_number']) ?> (<?= htmlspecialchars($c['building_name']) ?>) - <?= ucfirst($c['type']) ?> (Cap: <?= $c['capacity'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="booking_date" class="form-label font-weight-500">Booking Date</label>
                                    <input type="date" id="booking_date" name="booking_date" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="start_time" class="form-label font-weight-500">Start Time</label>
                                    <input type="time" id="start_time" name="start_time" class="form-control" value="09:00" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="end_time" class="form-label font-weight-500">End Time</label>
                                    <input type="time" id="end_time" name="end_time" class="form-control" value="10:30" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="purpose" class="form-label font-weight-500">Purpose / Special Event Details</label>
                                <textarea id="purpose" name="purpose" class="form-control" rows="3" placeholder="e.g. Special AI Hands-on Workshop / Placement Test" required></textarea>
                            </div>

                            <button type="submit" class="btn btn-warning text-dark rounded-pill px-4 py-2 font-weight-600 w-100">
                                Submit Booking Request <i class="fas fa-paper-plane ms-2"></i>
                            </button>
                        </form>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
