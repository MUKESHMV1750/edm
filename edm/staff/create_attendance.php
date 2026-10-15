<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STAFF, ROLE_ADMIN]);

$userRole = getCurrentUserRole();
$isAdmin = ($userRole === ROLE_ADMIN);
$staffId = $_SESSION['role_id'] ?? 0;
$db = getDB();

// Fetch Staff Department if staff
$staff = null;
if (!$isAdmin && $staffId > 0) {
    $stfStmt = $db->prepare("SELECT department_id FROM staff WHERE id = ?");
    $stfStmt->execute([$staffId]);
    $staff = $stfStmt->fetch();
}

// Fetch Subjects: all subjects for admin, assigned subjects for staff
if ($isAdmin) {
    $subStmt = $db->query("SELECT * FROM subjects ORDER BY code ASC");
    $subjects = $subStmt->fetchAll();
} else {
    $subStmt = $db->prepare("SELECT * FROM subjects WHERE staff_id = ? ORDER BY code ASC");
    $subStmt->execute([$staffId]);
    $subjects = $subStmt->fetchAll();
}

// Handle POST actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $action = $_POST['action'] ?? 'create_session';

        // Admin-only: Add Classroom / Lab
        if ($action === 'add_room' && $isAdmin) {
            $room_number = sanitizeInput($_POST['room_number'] ?? '');
            $building_name = sanitizeInput($_POST['building_name'] ?? '');
            $capacity = (int)($_POST['capacity'] ?? 60);
            $type = sanitizeInput($_POST['type'] ?? 'lecture');
            $lat = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : 13.0827000;
            $lng = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : 80.2707000;

            if (!empty($room_number)) {
                $ins = $db->prepare("
                    INSERT INTO classrooms (room_number, building_name, capacity, type, latitude, longitude)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $ins->execute([$room_number, $building_name, $capacity, $type, $lat, $lng]);
                setFlashMessage('success', "Room '{$room_number}' added successfully with location coordinates!");
            } else {
                setFlashMessage('danger', 'Room number is required.');
            }
            redirect('staff/create_attendance.php');
        }

        // Admin-only: Delete Classroom / Lab
        if ($action === 'delete_room' && $isAdmin) {
            $roomId = (int)($_POST['delete_classroom_id'] ?? 0);
            if ($roomId > 0) {
                try {
                    $del = $db->prepare("DELETE FROM classrooms WHERE id = ?");
                    $del->execute([$roomId]);
                    setFlashMessage('success', 'Classroom / Lab removed successfully!');
                } catch (PDOException $e) {
                    setFlashMessage('danger', 'Cannot delete room: It is currently referenced by timetables, attendance sessions, or bookings.');
                }
            } else {
                setFlashMessage('warning', 'Please select a valid room to delete.');
            }
            redirect('staff/create_attendance.php');
        }

        // Create Attendance Session
        if ($action === 'create_session' || empty($action)) {
            $subject_id = (int)$_POST['subject_id'];
            $semester = (int)$_POST['semester'];
            $section = sanitizeInput($_POST['section']);
            $session_type = sanitizeInput($_POST['session_type']);
            $duration_minutes = (int)$_POST['duration_minutes'];
            $classroom_id = !empty($_POST['classroom_id']) ? (int)$_POST['classroom_id'] : null;

            // Location-Wise Attendance & Geofence Distance Radius
            $location_enabled = isset($_POST['location_enabled']) ? 1 : 0;
            $allowed_latitude = null;
            $allowed_longitude = null;
            $location_radius = 100;

            if ($location_enabled) {
                if (isset($_POST['allowed_latitude']) && $_POST['allowed_latitude'] !== '' && isset($_POST['allowed_longitude']) && $_POST['allowed_longitude'] !== '') {
                    $allowed_latitude = (float)$_POST['allowed_latitude'];
                    $allowed_longitude = (float)$_POST['allowed_longitude'];
                }
                if (!empty($_POST['location_radius'])) {
                    $location_radius = max(10, (int)$_POST['location_radius']);
                }
            }

            // Generate 4-digit random PIN & session code
            $pin = sprintf("%04d", rand(1000, 9999));
            $session_code = 'SES-' . strtoupper(substr(md5(uniqid()), 0, 8));
            $expires_at = date('Y-m-d H:i:s', strtotime("+{$duration_minutes} minutes"));

            $subStmt = $db->prepare("SELECT department_id, semester, staff_id FROM subjects WHERE id = ?");
            $subStmt->execute([$subject_id]);
            $subInfo = $subStmt->fetch();
            if ($subInfo) {
                $deptId = (int)$subInfo['department_id'];
                if (empty($semester)) {
                    $semester = (int)$subInfo['semester'];
                }
                $effectiveStaffId = $staffId ?: ($subInfo['staff_id'] ?: 1);
            } else {
                $deptId = !empty($staff['department_id']) ? (int)$staff['department_id'] : 1;
                $effectiveStaffId = $staffId ?: 1;
            }

            $ins = $db->prepare("
                INSERT INTO attendance_sessions 
                (staff_id, subject_id, department_id, semester, section, classroom_id, session_date, start_time, end_time, pin_code, qr_code, allowed_latitude, allowed_longitude, location_radius, status, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, CURDATE(), CURTIME(), ADDTIME(CURTIME(), SEC_TO_TIME(? * 60)), ?, ?, ?, ?, ?, 'active', DATE_ADD(NOW(), INTERVAL ? MINUTE))
            ");
            $ins->execute([
                $effectiveStaffId, $subject_id, $deptId, $semester, $section, $classroom_id,
                $duration_minutes, $pin, $session_code, $allowed_latitude, $allowed_longitude, $location_radius, $duration_minutes
            ]);

            $sessionId = $db->lastInsertId();

            $flashMsg = "Attendance session started! Live PIN: {$pin}";
            if ($location_enabled && $allowed_latitude !== null) {
                $flashMsg .= " • Location-wise verification active (Distance radius: {$location_radius}m).";
            }
            setFlashMessage('success', $flashMsg);
            redirect("staff/attendance_records.php?session_id={$sessionId}");
        }
    }
}

// Fetch Classrooms with coordinates
$roomStmt = $db->prepare("SELECT * FROM classrooms ORDER BY room_number ASC");
$roomStmt->execute();
$classrooms = $roomStmt->fetchAll();

$pageTitle = "Start Live Attendance Session";
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    /* Exact UI Styling matching Create Live Attendance Session design */
    .create-attendance-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0 !important;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        padding: 34px 30px !important;
    }

    .form-control-custom,
    .create-attendance-card .form-select,
    .create-attendance-card .form-control {
        border: 1px solid #CBD5E1;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 0.95rem;
        color: #1E293B;
        background-color: #FFFFFF;
        transition: all 0.2s ease;
    }

    .create-attendance-card .form-select:focus,
    .create-attendance-card .form-control:focus {
        border-color: #2563EB;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        outline: none;
    }

    .create-attendance-card .form-label {
        font-size: 0.88rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 6px;
    }

    .create-attendance-card .form-label .text-danger {
        color: #EF4444 !important;
        margin-left: 2px;
    }

    /* Location & Distance Geofence Card */
    .location-geofence-card {
        border: 1px solid #CBD5E1 !important;
        border-radius: 16px !important;
        background: #FFFFFF;
        margin-top: 24px;
        margin-bottom: 24px;
        overflow: hidden;
    }

    .location-geofence-header {
        background: #FFFFFF !important;
        border-bottom: 1px solid #F1F5F9;
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .location-icon-badge {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #EFF6FF;
        color: #2563EB;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    .location-header-title {
        font-size: 0.96rem;
        font-weight: 700;
        color: #1E293B;
        margin-bottom: 2px;
    }

    .location-header-subtitle {
        font-size: 0.8rem;
        color: #64748B;
    }

    .geofence-switch .form-check-input {
        width: 2.8rem;
        height: 1.45rem;
        cursor: pointer;
        background-color: #CBD5E1;
        border-color: #CBD5E1;
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }

    .geofence-switch .form-check-input:checked {
        background-color: #2563EB;
        border-color: #2563EB;
    }

    .geofence-switch .form-check-label {
        font-size: 0.9rem;
        font-weight: 600;
        color: #1E293B;
        margin-left: 8px;
        cursor: pointer;
    }

    /* Source Selector Pills */
    .source-pill-btn {
        border-radius: 50px;
        font-size: 0.82rem;
        font-weight: 600;
        padding: 7px 18px;
        transition: all 0.2s ease;
        border: 1px solid #CBD5E1;
        background: #FFFFFF;
        color: #334155;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
    }

    .source-pill-btn.active {
        background: #1D4ED8 !important;
        border-color: #1D4ED8 !important;
        color: #FFFFFF !important;
        box-shadow: 0 2px 8px rgba(29, 78, 216, 0.25);
    }

    .source-pill-btn:hover:not(.active) {
        background: #F8FAFC;
        color: #0F172A;
    }

    /* Coordinate Inputs */
    .coords-input-group {
        border-radius: 10px;
    }

    .coords-input-group .input-group-text {
        background: #FFFFFF;
        border: 1px solid #CBD5E1;
        border-right: none;
        border-radius: 10px 0 0 10px;
        color: #2563EB;
        padding: 0 14px;
        font-size: 1rem;
    }

    .coords-input-group .form-control {
        border: 1px solid #CBD5E1;
        border-radius: 0 10px 10px 0;
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        font-size: 0.95rem;
        font-weight: 700;
        color: #0F172A;
        padding: 10px 14px;
    }

    .gps-alert-box {
        background: #EFF6FF;
        border: 1px solid #DBEAFE;
        border-radius: 10px;
        padding: 9px 16px;
        font-size: 0.82rem;
        color: #1E40AF;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 16px 0;
        flex-wrap: wrap;
        gap: 8px;
    }

    .gps-alert-badge {
        background: #FFFFFF;
        border: 1px solid #CBD5E1;
        border-radius: 50px;
        padding: 4px 12px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #334155;
        white-space: nowrap;
    }

    /* Allowed Distance Radius Box */
    .radius-config-box {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 18px 20px;
        margin-bottom: 14px;
    }

    .radius-badge-pill {
        background: #1D4ED8;
        color: #FFFFFF;
        border-radius: 50px;
        padding: 4px 14px;
        font-size: 0.85rem;
        font-weight: 700;
        box-shadow: 0 2px 6px rgba(29, 78, 216, 0.25);
    }

    .radius-preset-pill {
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 6px 14px;
        border: 1px solid #CBD5E1;
        background: #FFFFFF;
        color: #334155;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .radius-preset-pill.active {
        background: #1D4ED8 !important;
        border-color: #1D4ED8 !important;
        color: #FFFFFF !important;
        box-shadow: 0 2px 6px rgba(29, 78, 216, 0.25);
    }

    .radius-preset-pill:hover:not(.active) {
        background: #F1F5F9;
        color: #0F172A;
    }

    /* Custom Geofence Range Slider */
    .custom-geofence-slider {
        -webkit-appearance: none;
        appearance: none;
        width: 100%;
        height: 6px;
        background: #E2E8F0;
        border-radius: 10px;
        outline: none;
        cursor: pointer;
    }

    .custom-geofence-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #1D4ED8;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(29, 78, 216, 0.35);
        transition: transform 0.15s ease;
    }

    .custom-geofence-slider::-webkit-slider-thumb:hover {
        transform: scale(1.15);
    }

    .custom-geofence-slider::-moz-range-thumb {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #1D4ED8;
        cursor: pointer;
        border: none;
        box-shadow: 0 2px 6px rgba(29, 78, 216, 0.35);
    }

    .radius-number-group .form-control {
        border: 1px solid #CBD5E1;
        border-radius: 8px 0 0 8px;
        text-align: center;
        font-weight: 700;
        color: #0F172A;
        font-size: 0.95rem;
    }

    .radius-number-group .input-group-text {
        background: #FFFFFF;
        border: 1px solid #CBD5E1;
        border-left: none;
        border-radius: 0 8px 8px 0;
        font-weight: 600;
        font-size: 0.82rem;
        color: #64748B;
        padding: 0 12px;
    }

    /* Submit Button matching image */
    .btn-launch-live-session {
        background: #059669;
        color: #FFFFFF;
        border: none;
        border-radius: 50px;
        padding: 15px 24px;
        font-size: 1.05rem;
        font-weight: 700;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
        transition: all 0.25s ease;
        cursor: pointer;
    }

    .btn-launch-live-session:hover {
        background: #047857;
        color: #FFFFFF;
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(5, 150, 105, 0.35);
    }

    /* Dark Mode Overrides */
    [data-theme="dark"] .create-attendance-card,
    body.dark-mode .create-attendance-card {
        background: #151F32 !important;
        border-color: #24324D !important;
    }

    [data-theme="dark"] .create-attendance-card .form-select,
    [data-theme="dark"] .create-attendance-card .form-control,
    body.dark-mode .create-attendance-card .form-select,
    body.dark-mode .create-attendance-card .form-control {
        background-color: #0B1120 !important;
        border-color: #24324D !important;
        color: #F8FAFC !important;
    }

    [data-theme="dark"] .create-attendance-card .form-label,
    body.dark-mode .create-attendance-card .form-label {
        color: #E2E8F0 !important;
    }

    [data-theme="dark"] .location-geofence-card,
    body.dark-mode .location-geofence-card {
        background: #0F172A !important;
        border-color: #24324D !important;
    }

    [data-theme="dark"] .location-geofence-header,
    body.dark-mode .location-geofence-header {
        background: #0F172A !important;
        border-color: #24324D !important;
    }

    [data-theme="dark"] .location-header-title,
    body.dark-mode .location-header-title {
        color: #F8FAFC !important;
    }

    [data-theme="dark"] .location-icon-badge,
    body.dark-mode .location-icon-badge {
        background: rgba(37, 99, 235, 0.2) !important;
        color: #60A5FA !important;
    }

    [data-theme="dark"] .source-pill-btn:not(.active),
    body.dark-mode .source-pill-btn:not(.active) {
        background: #1E293B !important;
        border-color: #334155 !important;
        color: #CBD5E1 !important;
    }

    [data-theme="dark"] .coords-input-group .input-group-text,
    body.dark-mode .coords-input-group .input-group-text {
        background: #1E293B !important;
        border-color: #334155 !important;
        color: #60A5FA !important;
    }

    [data-theme="dark"] .coords-input-group .form-control,
    body.dark-mode .coords-input-group .form-control {
        background: #0B1120 !important;
        border-color: #334155 !important;
        color: #F8FAFC !important;
    }

    [data-theme="dark"] .gps-alert-box,
    body.dark-mode .gps-alert-box {
        background: rgba(37, 99, 235, 0.15) !important;
        border-color: rgba(37, 99, 235, 0.3) !important;
        color: #93C5FD !important;
    }

    [data-theme="dark"] .gps-alert-badge,
    body.dark-mode .gps-alert-badge {
        background: #1E293B !important;
        border-color: #334155 !important;
        color: #E2E8F0 !important;
    }

    [data-theme="dark"] .radius-config-box,
    body.dark-mode .radius-config-box {
        background: #0B1120 !important;
        border-color: #24324D !important;
    }

    [data-theme="dark"] .radius-preset-pill:not(.active),
    body.dark-mode .radius-preset-pill:not(.active) {
        background: #1E293B !important;
        border-color: #334155 !important;
        color: #CBD5E1 !important;
    }

    [data-theme="dark"] .radius-number-group .form-control,
    body.dark-mode .radius-number-group .form-control {
        background: #151F32 !important;
        border-color: #334155 !important;
        color: #F8FAFC !important;
    }

    [data-theme="dark"] .radius-number-group .input-group-text,
    body.dark-mode .radius-number-group .input-group-text {
        background: #1E293B !important;
        border-color: #334155 !important;
        color: #94A3B8 !important;
    }

    [data-theme="dark"] .geofence-switch .form-check-label,
    body.dark-mode .geofence-switch .form-check-label {
        color: #F8FAFC !important;
    }
</style>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Create Live Attendance Session</h1>
                    <p class="text-muted small mb-0">Broadcast dynamic 4-Digit PIN, QR code, and location geofence radius for student check-in</p>
                </div>
                <a href="<?= APP_URL ?>/staff/attendance_sessions.php" class="btn btn-outline-secondary rounded-pill btn-sm px-3 font-weight-600">
                    <i class="fas fa-list me-1"></i> View Past Sessions
                </a>
            </div>

            <?php displayAlerts(); ?>

            <div class="row justify-content-center">
                <div class="col-lg-9 col-xl-8">
                    <div class="card create-attendance-card">
                        
                        <form action="" method="POST" id="createSessionForm">
                            <input type="hidden" name="action" value="create_session">
                            <?= csrfField() ?>

                            <!-- Select Subject -->
                            <div class="mb-3">
                                <label for="subject_id" class="form-label">
                                    Select Subject <span class="text-danger">*</span>
                                </label>
                                <select id="subject_id" name="subject_id" class="form-select" required>
                                    <option value="">-- Choose Subject --</option>
                                    <?php foreach ($subjects as $s): ?>
                                        <option value="<?= $s['id'] ?>" data-semester="<?= $s['semester'] ?>">
                                            <?= htmlspecialchars($s['code']) ?> - <?= htmlspecialchars($s['name']) ?> (Sem <?= $s['semester'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Semester & Section / Batch -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="semester" class="form-label">
                                        Semester <span class="text-danger">*</span>
                                    </label>
                                    <select id="semester" name="semester" class="form-select" required>
                                        <?php for ($sem = 1; $sem <= 8; $sem++): ?>
                                            <option value="<?= $sem ?>" <?= $sem == 6 ? 'selected' : '' ?>>Semester <?= $sem ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="section" class="form-label">
                                        Section / Batch <span class="text-danger">*</span>
                                    </label>
                                    <select id="section" name="section" class="form-select" required>
                                        <option value="Batch A" selected>Batch A</option>
                                        <option value="Batch B">Batch B</option>
                                        <option value="A">Section A</option>
                                        <option value="B">Section B</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Session Type & Active Duration -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="session_type" class="form-label">
                                        Session Type <span class="text-danger">*</span>
                                    </label>
                                    <select id="session_type" name="session_type" class="form-select" required>
                                        <option value="lecture" selected>Lecture / Theory</option>
                                        <option value="lab">Practical / Laboratory</option>
                                        <option value="tutorial">Tutorial / Discussion</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="duration_minutes" class="form-label">
                                        Active Duration <span class="text-danger">*</span>
                                    </label>
                                    <select id="duration_minutes" name="duration_minutes" class="form-select" required>
                                        <option value="5">5 Minutes (Quick Check-in)</option>
                                        <option value="10" selected>10 Minutes (Standard)</option>
                                        <option value="15">15 Minutes</option>
                                        <option value="30">30 Minutes</option>
                                        <option value="60">60 Minutes</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Classroom / Lab Location -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="classroom_id" class="form-label mb-0">
                                        Classroom / Lab Location
                                    </label>
                                    <?php if ($isAdmin): ?>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-link text-primary p-0 text-decoration-none fw-semibold" data-bs-toggle="modal" data-bs-target="#quickAddRoomModal">
                                                <i class="fas fa-plus-circle me-1"></i> Add Room
                                            </button>
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 text-decoration-none fw-semibold" onclick="confirmDeleteRoom()">
                                                <i class="fas fa-trash-alt me-1"></i> Delete
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <select id="classroom_id" name="classroom_id" class="form-select" onchange="onClassroomSelected(this)">
                                    <option value="" data-lat="" data-lng="">-- Standard Room (No Fixed GPS) --</option>
                                    <?php foreach ($classrooms as $c): 
                                        $bName = $c['building_name'] ?? $c['building'] ?? '';
                                        $rNum = $c['room_number'] ?? '';
                                        $displayName = "Room " . $rNum . ($bName ? " (" . $bName . ")" : "");
                                    ?>
                                        <option value="<?= $c['id'] ?>" 
                                                data-name="<?= htmlspecialchars($displayName) ?>"
                                                data-lat="<?= htmlspecialchars($c['latitude'] ?? '') ?>"
                                                data-lng="<?= htmlspecialchars($c['longitude'] ?? '') ?>">
                                            <?= htmlspecialchars($displayName) ?> - Cap: <?= (int)($c['capacity'] ?? 60) ?><?= (!empty($c['latitude']) ? ' • [GPS Ready]' : '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted d-block mt-1" style="font-size: 0.8rem; color: #94A3B8 !important;">
                                    Selecting a classroom with GPS coordinates will automatically configure the attendance geofence.
                                </small>
                            </div>

                            <!-- ============================================================ -->
                            <!-- LOCATION-WISE ATTENDANCE & DISTANCE RADIUS (GEOFENCING) CARD -->
                            <!-- ============================================================ -->
                            <div class="card location-geofence-card shadow-xs">
                                <div class="location-geofence-header">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="location-icon-badge">
                                            <i class="fas fa-map-location-dot"></i>
                                        </div>
                                        <div>
                                            <h6 class="location-header-title">Location-Wise Attendance &amp; Distance Verification</h6>
                                            <small class="location-header-subtitle">Geofenced attendance: Restrict student check-in within a specific distance radius</small>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch geofence-switch fs-5 mb-0 d-flex align-items-center">
                                        <input class="form-check-input" type="checkbox" id="location_enabled" name="location_enabled" value="1" checked onchange="toggleLocationControls(this.checked)">
                                        <label class="form-check-label" for="location_enabled">Enabled</label>
                                    </div>
                                </div>

                                <div class="card-body p-3 p-md-4 pt-3" id="locationControlsBody">
                                    
                                    <!-- Location Source Selector Tabs -->
                                    <div class="mb-3">
                                        <label class="form-label small font-weight-600 text-dark mb-2">Set Classroom / Attendance Coordinates From:</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button type="button" class="source-pill-btn active" id="btnSourceLiveGps" onclick="setLocationSource('live')">
                                                <i class="fas fa-crosshairs me-1"></i> Detect My Live GPS Location
                                            </button>
                                            <button type="button" class="source-pill-btn" id="btnSourceClassroom" onclick="setLocationSource('classroom')">
                                                <i class="fas fa-door-open me-1"></i> Use Selected Classroom Location
                                            </button>
                                            <button type="button" class="source-pill-btn" id="btnSourcePreset" onclick="setLocationSource('preset')">
                                                <i class="fas fa-building me-1"></i> Campus Landmark Presets
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Campus Preset Dropdown (Hidden until Preset tab clicked) -->
                                    <div class="mb-3 d-none p-3 bg-light rounded-3 border" id="campusPresetWrapper">
                                        <label for="campusPresetSelect" class="form-label small font-weight-600 text-dark mb-1">
                                            <i class="fas fa-landmark text-primary me-1"></i> Choose Campus Landmark Preset
                                        </label>
                                        <select class="form-select form-select-sm" id="campusPresetSelect" onchange="applyCampusPreset(this.value)">
                                            <option value="13.082700,80.270700">Main Academic Block (Lat: 13.082700, Lng: 80.270700)</option>
                                            <option value="13.083100,80.271200">Computer Science &amp; IT Labs Wing (Lat: 13.083100, Lng: 80.271200)</option>
                                            <option value="13.082200,80.270100">Electronics &amp; Mechanical Complex (Lat: 13.082200, Lng: 80.270100)</option>
                                            <option value="13.083500,80.270500">Central Library &amp; Seminar Auditorium (Lat: 13.083500, Lng: 80.270500)</option>
                                            <option value="13.081800,80.271500">Science &amp; Humanities Block (Lat: 13.081800, Lng: 80.271500)</option>
                                        </select>
                                    </div>

                                    <!-- Coordinate Inputs -->
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label for="allowed_latitude" class="form-label small font-weight-600 text-dark mb-1">
                                                Latitude Coordinates <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group coords-input-group">
                                                <span class="input-group-text"><i class="fas fa-compass text-primary"></i></span>
                                                <input type="number" step="0.0000001" id="allowed_latitude" name="allowed_latitude" class="form-control" placeholder="13.0827000" value="13.0827000" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="allowed_longitude" class="form-label small font-weight-600 text-dark mb-1">
                                                Longitude Coordinates <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group coords-input-group">
                                                <span class="input-group-text"><i class="fas fa-compass text-primary"></i></span>
                                                <input type="number" step="0.0000001" id="allowed_longitude" name="allowed_longitude" class="form-control" placeholder="80.2707000" value="80.2707000" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Live GPS Status Alert -->
                                    <div id="gpsStatusAlert" class="gps-alert-box">
                                        <div id="gpsStatusText">
                                            <i class="fas fa-info-circle me-1"></i> Click <strong>"Detect My Live GPS Location"</strong> to pinpoint faculty coordinates.
                                        </div>
                                        <div id="gpsAccuracyBadge" class="gps-alert-badge">
                                            Default: Campus Coordinate
                                        </div>
                                    </div>

                                    <!-- ====================================== -->
                                    <!-- DISTANCE RADIUS CONFIGURATION SECTION  -->
                                    <!-- ====================================== -->
                                    <div class="radius-config-box">
                                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                            <label for="location_radius" class="form-label font-weight-600 text-dark mb-0">
                                                <i class="fas fa-ruler-combined text-primary me-1"></i> Allowed Distance Radius (Geofence Limit)
                                            </label>
                                            <span class="radius-badge-pill" id="radiusBadge">
                                                100 Meters
                                            </span>
                                        </div>

                                        <p class="text-muted small mb-3" style="font-size: 0.8rem;">
                                            Students checking in farther than this distance radius from the classroom will be blocked with a distance violation message.
                                        </p>

                                        <!-- Quick distance preset pills matching screenshot -->
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            <button type="button" class="radius-preset-pill" id="pillRadius25" onclick="setRadius(25)">
                                                25m (Classroom Only)
                                            </button>
                                            <button type="button" class="radius-preset-pill" id="pillRadius50" onclick="setRadius(50)">
                                                50m (Classroom &amp; Corridor)
                                            </button>
                                            <button type="button" class="radius-preset-pill active" id="pillRadius100" onclick="setRadius(100)">
                                                100m (Standard Wing)
                                            </button>
                                            <button type="button" class="radius-preset-pill" id="pillRadius200" onclick="setRadius(200)">
                                                200m (Entire Building)
                                            </button>
                                            <button type="button" class="radius-preset-pill" id="pillRadius500" onclick="setRadius(500)">
                                                500m (Campus Zone)
                                            </button>
                                        </div>

                                        <!-- Range Slider & Direct Number Input -->
                                        <div class="row align-items-center g-3">
                                            <div class="col-7 col-sm-8">
                                                <input type="range" class="custom-geofence-slider" id="radiusRange" min="10" max="500" step="5" value="100" oninput="updateRadius(this.value)">
                                            </div>
                                            <div class="col-5 col-sm-4">
                                                <div class="input-group input-group-sm radius-number-group">
                                                    <input type="number" class="form-control" id="location_radius" name="location_radius" min="10" max="5000" value="100" oninput="updateRadius(this.value)">
                                                    <span class="input-group-text">Meters</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Visual Map Link & Rule Summary -->
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 text-muted small pt-1">
                                        <div>
                                            <i class="fas fa-shield-halved text-success me-1"></i>
                                            Anti-Proxy Distance Rule: Student GPS distance must be <strong class="text-dark" id="ruleDistanceText">within 100 meters</strong>.
                                        </div>
                                        <a href="https://www.openstreetmap.org/?mlat=13.082700&mlon=80.270700#map=18/13.082700/80.270700" id="previewMapLink" target="_blank" class="text-primary text-decoration-none fw-semibold">
                                            <i class="fas fa-arrow-up-right-from-square me-1"></i> Preview Geofence on Map
                                        </a>
                                    </div>

                                </div>
                            </div>

                            <!-- Launch Button matching screenshot -->
                            <button type="submit" class="btn-launch-live-session" id="btnLaunchLiveSession">
                                <span>Launch Live Session &amp; Generate PIN</span>
                                <i class="fas fa-qrcode"></i>
                            </button>
                        </form>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Modal: Quick Add Room (Admin Only) -->
<div class="modal fade" id="quickAddRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark">
                    <i class="fas fa-door-open text-primary me-2"></i> Add Classroom / Lab with Location
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="add_room">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="room_number" class="form-label font-weight-500">Room Number <span class="text-danger">*</span></label>
                        <input type="text" id="room_number" name="room_number" class="form-control" placeholder="e.g. 101, Lab-3, Seminar-1" required>
                    </div>
                    <div class="mb-3">
                        <label for="building_name" class="form-label font-weight-500">Building / Block <span class="text-danger">*</span></label>
                        <input type="text" id="building_name" name="building_name" class="form-control" placeholder="e.g. Main Block, IT Block, Tech Park" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="capacity" class="form-label font-weight-500">Capacity</label>
                            <input type="number" id="capacity" name="capacity" class="form-control" value="60" min="1" max="500" required>
                        </div>
                        <div class="col-6">
                            <label for="room_type" class="form-label font-weight-500">Room Type</label>
                            <select id="room_type" name="type" class="form-select" required>
                                <option value="lecture">Lecture Hall</option>
                                <option value="lab">Computer / Hardware Lab</option>
                                <option value="seminar">Seminar Hall</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label for="new_room_lat" class="form-label font-weight-500">Latitude</label>
                            <input type="number" step="0.0000001" id="new_room_lat" name="latitude" class="form-control" placeholder="13.0827000" value="13.0827000">
                        </div>
                        <div class="col-6">
                            <label for="new_room_lng" class="form-label font-weight-500">Longitude</label>
                            <input type="number" step="0.0000001" id="new_room_lng" name="longitude" class="form-control" placeholder="80.2707000" value="80.2707000">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 font-weight-600">Save Room with Location</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Confirm Delete Room (Admin Only) -->
<div class="modal fade" id="confirmDeleteRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i> Delete Classroom / Lab
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="delete_room">
                <input type="hidden" name="delete_classroom_id" id="deleteClassroomId">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <p class="mb-2">Are you sure you want to permanently delete <strong id="deleteRoomName"></strong>?</p>
                    <small class="text-danger d-block">Warning: Rooms currently referenced by timetables, attendance sessions, or bookings cannot be deleted.</small>
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
function confirmDeleteRoom() {
    const select = document.getElementById('classroom_id');
    const selectedVal = select.value;
    if (!selectedVal) {
        alert("Please select a specific room from the dropdown to delete. '-- Standard Room --' cannot be deleted.");
        return;
    }
    const selectedOption = select.options[select.selectedIndex];
    const selectedText = selectedOption.getAttribute('data-name') || selectedOption.text;
    document.getElementById('deleteClassroomId').value = selectedVal;
    document.getElementById('deleteRoomName').innerText = selectedText;
    new bootstrap.Modal(document.getElementById('confirmDeleteRoomModal')).show();
}
</script>
<?php endif; ?>

<script>
// Auto-fill Semester based on Subject
document.getElementById('subject_id').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt && opt.dataset.semester) {
        document.getElementById('semester').value = opt.dataset.semester;
    }
});

// Toggle Location Controls Panel
function toggleLocationControls(isEnabled) {
    const body = document.getElementById('locationControlsBody');
    const lat = document.getElementById('allowed_latitude');
    const lng = document.getElementById('allowed_longitude');
    if (isEnabled) {
        body.style.display = 'block';
        lat.required = true;
        lng.required = true;
    } else {
        body.style.display = 'none';
        lat.required = false;
        lng.required = false;
    }
}

// Distance Radius Management
function setRadius(meters) {
    document.getElementById('radiusRange').value = meters;
    document.getElementById('location_radius').value = meters;
    updateRadius(meters);
}

function updateRadius(meters) {
    meters = parseInt(meters) || 100;
    document.getElementById('radiusRange').value = meters;
    document.getElementById('location_radius').value = meters;
    document.getElementById('radiusBadge').innerText = meters + ' Meters';
    document.getElementById('ruleDistanceText').innerText = 'within ' + meters + ' meters';

    // Highlight matching pill
    const pills = [25, 50, 100, 200, 500];
    pills.forEach(p => {
        const btn = document.getElementById('pillRadius' + p);
        if (btn) {
            btn.classList.toggle('active', p === meters);
        }
    });
}

// Location Source Switching
function setLocationSource(source) {
    const btnLive = document.getElementById('btnSourceLiveGps');
    const btnClassroom = document.getElementById('btnSourceClassroom');
    const btnPreset = document.getElementById('btnSourcePreset');
    const presetWrapper = document.getElementById('campusPresetWrapper');

    btnLive.classList.remove('active');
    btnClassroom.classList.remove('active');
    btnPreset.classList.remove('active');
    presetWrapper.classList.add('d-none');

    if (source === 'live') {
        btnLive.classList.add('active');
        detectLiveGps();
    } else if (source === 'classroom') {
        btnClassroom.classList.add('active');
        applyClassroomCoordinates();
    } else if (source === 'preset') {
        btnPreset.classList.add('active');
        presetWrapper.classList.remove('d-none');
        applyCampusPreset(document.getElementById('campusPresetSelect').value);
    }
}

// Live GPS detection via navigator.geolocation
function detectLiveGps() {
    const statusText = document.getElementById('gpsStatusText');
    const badge = document.getElementById('gpsAccuracyBadge');

    if (!navigator.geolocation) {
        statusText.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Geolocation is not supported by this browser.</span>';
        return;
    }

    statusText.innerHTML = '<span class="text-primary"><i class="fas fa-spinner fa-spin me-1"></i> Acquiring satellite GPS coordinates...</span>';

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude.toFixed(7);
            const lng = position.coords.longitude.toFixed(7);
            const accuracy = Math.round(position.coords.accuracy);

            document.getElementById('allowed_latitude').value = lat;
            document.getElementById('allowed_longitude').value = lng;

            statusText.innerHTML = '<span class="text-success fw-semibold"><i class="fas fa-check-circle me-1"></i> Live faculty coordinates detected successfully!</span>';
            badge.className = 'badge bg-success-subtle text-success border border-success-subtle font-weight-600';
            badge.innerText = 'GPS Accuracy: ±' + accuracy + 'm';

            updateMapPreview(lat, lng);
        },
        function(error) {
            let msg = 'Unable to acquire location: ';
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    msg += 'Location permission denied by user. Please allow location in browser.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    msg += 'Location information unavailable.';
                    break;
                case error.TIMEOUT:
                    msg += 'Location request timed out.';
                    break;
                default:
                    msg += error.message;
            }
            statusText.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> ' + msg + '</span>';
            badge.className = 'badge bg-danger-subtle text-danger border';
            badge.innerText = 'Detection Failed';
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}

// Apply Selected Classroom Coordinates
function onClassroomSelected(select) {
    const opt = select.options[select.selectedIndex];
    if (opt && opt.dataset.lat && opt.dataset.lng) {
        document.getElementById('allowed_latitude').value = parseFloat(opt.dataset.lat).toFixed(7);
        document.getElementById('allowed_longitude').value = parseFloat(opt.dataset.lng).toFixed(7);
        document.getElementById('gpsStatusText').innerHTML = '<span class="text-success"><i class="fas fa-door-open me-1"></i> Coordinates loaded from ' + opt.text + '</span>';
        document.getElementById('gpsAccuracyBadge').innerText = 'Room Preset';
        updateMapPreview(opt.dataset.lat, opt.dataset.lng);
    }
}

function applyClassroomCoordinates() {
    const select = document.getElementById('classroom_id');
    const opt = select.options[select.selectedIndex];
    if (opt && opt.dataset.lat && opt.dataset.lng) {
        onClassroomSelected(select);
    } else {
        document.getElementById('gpsStatusText').innerHTML = '<span class="text-warning"><i class="fas fa-info-circle me-1"></i> Please choose a classroom from the dropdown above that has GPS configured.</span>';
    }
}

// Apply Campus Preset
function applyCampusPreset(val) {
    if (!val) return;
    const parts = val.split(',');
    if (parts.length === 2) {
        const lat = parseFloat(parts[0]).toFixed(7);
        const lng = parseFloat(parts[1]).toFixed(7);
        document.getElementById('allowed_latitude').value = lat;
        document.getElementById('allowed_longitude').value = lng;
        document.getElementById('gpsStatusText').innerHTML = '<span class="text-primary"><i class="fas fa-landmark me-1"></i> Campus Landmark preset applied.</span>';
        document.getElementById('gpsAccuracyBadge').innerText = 'Campus Preset';
        updateMapPreview(lat, lng);
    }
}

// Update OpenStreetMap link
function updateMapPreview(lat, lng) {
    const link = document.getElementById('previewMapLink');
    if (link && lat && lng) {
        link.href = 'https://www.openstreetmap.org/?mlat=' + lat + '&mlon=' + lng + '#map=18/' + lat + '/' + lng;
    }
}

// Listen for manual coordinate changes
document.getElementById('allowed_latitude').addEventListener('input', function() {
    updateMapPreview(this.value, document.getElementById('allowed_longitude').value);
});
document.getElementById('allowed_longitude').addEventListener('input', function() {
    updateMapPreview(document.getElementById('allowed_latitude').value, this.value);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

