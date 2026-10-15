<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STUDENT]);

$studentId = $_SESSION['role_id'] ?? 0;
$db = getDB();

// 1. Fetch Student details with fallback to user_id
$stStmt = $db->prepare("SELECT id, department_id, semester, section FROM students WHERE id = ?");
$stStmt->execute([$studentId]);
$student = $stStmt->fetch();

if (!$student && !empty($_SESSION['user_id'])) {
    $stUserStmt = $db->prepare("SELECT id, department_id, semester, section FROM students WHERE user_id = ?");
    $stUserStmt->execute([$_SESSION['user_id']]);
    $student = $stUserStmt->fetch();
    if ($student) {
        $studentId = (int)$student['id'];
        $_SESSION['role_id'] = $studentId;
    }
}

$deptId = (int)($student['department_id'] ?? 1);
$sem = (int)($student['semester'] ?? 6);
$sec = $student['section'] ?? 'A';

// Capture URL PIN parameter (from QR code scan or direct link)
$urlPin = sanitizeInput($_GET['pin'] ?? '');
$urlSessionId = (int)($_GET['session_id'] ?? 0);

// 2. Fetch Active Attendance Sessions matching student's department & semester/subject
$sesStmt = $db->prepare("
    SELECT 
        s.*, 
        COALESCE(s.pin_code, '') AS session_pin,
        sub.name AS subject_name, 
        sub.code AS subject_code,
        sub.semester AS subject_semester,
        st.first_name AS staff_first, 
        st.last_name AS staff_last,
        c.room_number, COALESCE(c.building_name, c.building, 'Academic Block') AS building_name,
        (SELECT COUNT(*) FROM attendance_records ar WHERE ar.session_id = s.id AND ar.student_id = ?) AS already_marked,
        (SELECT ar.marked_at FROM attendance_records ar WHERE ar.session_id = s.id AND ar.student_id = ? LIMIT 1) AS marked_at_time,
        (SELECT ar.marked_via FROM attendance_records ar WHERE ar.session_id = s.id AND ar.student_id = ? LIMIT 1) AS marked_via_type
    FROM attendance_sessions s
    JOIN subjects sub ON s.subject_id = sub.id
    JOIN staff st ON s.staff_id = st.id
    LEFT JOIN classrooms c ON s.classroom_id = c.id
    WHERE s.status = 'active' 
      AND s.expires_at > NOW()
      AND (
          (s.department_id = ? AND (s.semester = ? OR sub.semester = ?))
          OR s.department_id = ?
      )
    ORDER BY s.created_at DESC
");
$sesStmt->execute([$studentId, $studentId, $studentId, $deptId, $sem, $sem, $deptId]);
$activeSessions = $sesStmt->fetchAll();

$pageTitle = "Mark Live Attendance";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Mark Live Attendance</h1>
                    <p class="text-muted small mb-0">Verify active session via 4-Digit PIN or QR code scanner</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill btn-sm" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-1"></i> Refresh
                    </button>
                    <a href="<?= APP_URL ?>/student/attendance_history.php" class="btn btn-outline-primary rounded-pill btn-sm">
                        <i class="fas fa-history me-1"></i> Attendance History
                    </a>
                </div>
            </div>

            <?php displayAlerts(); ?>

            <?php if (empty($activeSessions)): ?>
                <!-- Empty State (No Active Session) -->
                <div class="card border-0 shadow-sm text-center p-5" style="border-radius: 16px;">
                    <div class="rounded-circle bg-light text-muted mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; font-size: 32px;">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <h4 class="h5 font-weight-bold text-dark mb-1">No Active Attendance Session</h4>
                    <p class="text-muted small max-width-500 mx-auto mb-3">
                        Your faculty has not started any live attendance session for your semester right now. Please wait for your professor to start a session.
                    </p>
                    <div>
                        <button type="button" class="btn btn-primary rounded-pill btn-sm px-4" onclick="location.reload()">
                            <i class="fas fa-redo me-1"></i> Check Again
                        </button>
                    </div>
                </div>
            <?php else: ?>
                
                <div class="row g-4">
                    <?php foreach ($activeSessions as $ses): 
                        $isMarked = (int)$ses['already_marked'] > 0;
                        $sessionPin = $ses['session_pin'] ?? '';
                        $qrPayload = APP_URL . '/student/attendance.php?pin=' . urlencode($sessionPin) . '&session_id=' . $ses['id'];
                    ?>
                        <div class="col-12 col-xl-10 mx-auto">
                            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 20px;">
                                
                                <!-- Card Header -->
                                <div class="card-header bg-primary text-white p-4 d-flex justify-content-between align-items-center flex-wrap gap-2" style="background: linear-gradient(135deg, #1E3A8A 0%, #2563EB 100%);">
                                    <div>
                                        <span class="badge bg-white text-primary font-weight-600 px-3 py-1 mb-2">
                                            <?= htmlspecialchars($ses['subject_code']) ?> • SEMESTER <?= htmlspecialchars($ses['subject_semester'] ?? $ses['semester']) ?>
                                        </span>
                                        <h3 class="h5 font-weight-bold mb-0 text-white"><?= htmlspecialchars($ses['subject_name']) ?></h3>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <?php if (!empty($ses['allowed_latitude'])): ?>
                                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill font-weight-600 shadow-xs">
                                                <i class="fas fa-location-crosshairs me-1 text-danger"></i> Geofence: ≤ <?= (int)($ses['location_radius'] ?: 100) ?>m Radius
                                            </span>
                                        <?php endif; ?>
                                        <span class="badge bg-danger text-white px-3 py-2 rounded-pill font-weight-600">
                                            <i class="fas fa-circle me-1 small animate-pulse"></i> LIVE SESSION
                                        </span>
                                    </div>
                                </div>

                                <div class="card-body p-4">
                                    
                                    <!-- Meta Badges Bar -->
                                    <div class="row g-2 mb-3 p-3 bg-light rounded-3 text-muted small align-items-center">
                                        <div class="col-md-4 col-sm-6">
                                            <i class="fas fa-user-tie me-2 text-primary"></i> Faculty: <strong class="text-dark">Prof. <?= htmlspecialchars($ses['staff_first'] . ' ' . $ses['staff_last']) ?></strong>
                                        </div>
                                        <div class="col-md-4 col-sm-6">
                                            <i class="fas fa-door-open me-2 text-warning"></i> Room: <strong class="text-dark"><?= htmlspecialchars($ses['room_number'] ?? 'Standard Hall') ?> (<?= htmlspecialchars($ses['building_name']) ?>)</strong>
                                        </div>
                                        <div class="col-md-4 col-sm-12 text-md-end">
                                            <i class="fas fa-hourglass-half me-2 text-danger"></i> Closes at: <strong class="text-danger"><?= date('h:i A', strtotime($ses['expires_at'])) ?></strong>
                                        </div>
                                    </div>

                                    <?php if (!empty($ses['allowed_latitude'])): ?>
                                        <!-- Location-Wise Geofence & Real-Time Distance Radar Card -->
                                        <div class="card border mb-4 shadow-xs" style="border-radius: 16px; border-color: #e2e8f0 !important; background: #f8fafc;">
                                            <div class="card-body p-3 p-md-4">
                                                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="rounded-circle bg-danger-subtle text-danger p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                                            <i class="fas fa-satellite-dish"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="font-weight-bold text-dark mb-0">Location-Wise Attendance Active</h6>
                                                            <small class="text-muted">Classroom geofencing enforced: You must be physically present inside the radius</small>
                                                        </div>
                                                    </div>
                                                    <span class="badge bg-danger text-white rounded-pill px-3 py-1 fs-6 font-weight-600">
                                                        Distance Limit: <?= (int)($ses['location_radius'] ?: 100) ?> Meters
                                                    </span>
                                                </div>

                                                <div class="p-3 bg-white rounded-3 border mb-3">
                                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                        <div>
                                                            <div class="text-muted text-xs text-uppercase font-weight-bold">Live Student GPS Distance Check</div>
                                                            <div class="fw-semibold text-dark fs-6" id="distText_<?= $ses['id'] ?>">
                                                                <i class="fas fa-spinner fa-spin text-primary me-1"></i> Detecting your current distance from classroom...
                                                            </div>
                                                        </div>
                                                        <div id="distBadge_<?= $ses['id'] ?>">
                                                            <span class="badge bg-secondary rounded-pill px-3 py-2">Acquiring GPS...</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill font-weight-600 px-3" onclick="refreshStudentDistance(<?= $ses['id'] ?>, <?= (float)$ses['allowed_latitude'] ?>, <?= (float)$ses['allowed_longitude'] ?>, <?= (int)($ses['location_radius'] ?: 100) ?>)">
                                                        <i class="fas fa-crosshairs me-1"></i> Recalculate Distance
                                                    </button>
                                                    <button type="button" class="btn btn-success btn-sm rounded-pill font-weight-600 px-4 shadow-xs" onclick="getLocationAndSubmit(<?= $ses['id'] ?>)">
                                                        <i class="fas fa-location-dot me-1"></i> 1-Click GPS Check-In
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($isMarked): ?>
                                        <!-- Already Recorded Banner -->
                                        <div class="alert alert-success d-flex align-items-center p-4 mb-0" style="border-radius: 16px; background-color: #ECFDF5; border-color: #A7F3D0;">
                                            <div class="rounded-circle bg-success text-white p-3 me-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; font-size: 24px;">
                                                <i class="fas fa-check"></i>
                                            </div>
                                            <div>
                                                <h5 class="h6 font-weight-bold text-success mb-1">Attendance Successfully Recorded!</h5>
                                                <div class="text-dark small">
                                                    Status: <span class="badge bg-success text-white">PRESENT</span> • 
                                                    Marked via: <strong><?= strtoupper(htmlspecialchars($ses['marked_via_type'] ?? 'PIN')) ?></strong>
                                                    <?php if (!empty($ses['marked_at_time'])): ?>
                                                        at <strong><?= date('h:i A', strtotime($ses['marked_at_time'])) ?></strong>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                    <?php else: ?>

                                        <!-- Live QR Code and Number PIN Dual Interface -->
                                        <div class="row g-4 align-items-stretch">
                                            
                                            <!-- Panel 1: Live QR Code -->
                                            <div class="col-md-5">
                                                <div class="card border-0 h-100 p-3 text-center" style="background: #F8FAFC; border-radius: 16px; border: 1px dashed #CBD5E1 !important;">
                                                    <span class="badge bg-primary-subtle text-primary font-weight-600 px-3 py-1 mb-2 mx-auto">
                                                        <i class="fas fa-qrcode me-1"></i> LIVE SESSION QR CODE
                                                    </span>
                                                    
                                                    <!-- Dynamic QR image -->
                                                    <div class="bg-white p-3 rounded-3 shadow-sm mx-auto my-2" style="width: 170px; height: 170px; display: flex; align-items: center; justify-content: center;">
                                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($qrPayload) ?>" alt="Attendance QR Code" class="img-fluid" style="width: 100%; height: 100%;">
                                                    </div>

                                                    <p class="text-muted small mb-3">
                                                        Scan this QR with your mobile camera or click below to launch the camera scanner
                                                    </p>

                                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill font-weight-600 mt-auto" onclick="openCameraScanner(<?= $ses['id'] ?>)">
                                                        <i class="fas fa-camera me-1"></i> Scan with Device Camera
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Panel 2: 4-Digit Number PIN -->
                                            <div class="col-md-7">
                                                <div class="card border-0 h-100 p-4" style="background: #F8FAFC; border-radius: 16px;">
                                                    
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <h6 class="font-weight-bold text-dark mb-0">
                                                            <i class="fas fa-key text-primary me-2"></i> Submit 4-Digit PIN
                                                        </h6>
                                                        <span class="badge bg-success-subtle text-success font-weight-600 px-3 py-1">
                                                            PIN: <strong class="letter-spacing-1 font-monospace"><?= htmlspecialchars($sessionPin) ?></strong>
                                                        </span>
                                                    </div>

                                                    <p class="text-muted small mb-3">
                                                        Enter the 4-digit PIN announced by your professor or click the quick-fill button below:
                                                    </p>

                                                    <!-- Quick Fill Button -->
                                                    <div class="mb-3">
                                                        <button type="button" class="btn btn-sm btn-light border text-primary rounded-pill font-weight-600 w-100" onclick="autoFillPin(<?= $ses['id'] ?>, '<?= htmlspecialchars($sessionPin) ?>')">
                                                            <i class="fas fa-magic me-1 text-warning"></i> Auto-fill Session PIN: <strong><?= htmlspecialchars($sessionPin) ?></strong>
                                                        </button>
                                                    </div>

                                                    <!-- Verification Form -->
                                                    <form action="<?= APP_URL ?>/api/verify_pin.php" method="POST" id="pinForm_<?= $ses['id'] ?>" onsubmit="return submitPinAttendance(event, <?= $ses['id'] ?>, <?= !empty($ses['allowed_latitude']) ? 'true' : 'false' ?>, <?= (float)($ses['allowed_latitude'] ?? 0) ?>, <?= (float)($ses['allowed_longitude'] ?? 0) ?>, <?= (int)($ses['location_radius'] ?: 100) ?>)">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="session_id" value="<?= $ses['id'] ?>">
                                                        <input type="hidden" name="method" value="pin" id="method_<?= $ses['id'] ?>">
                                                        <input type="hidden" name="latitude" id="lat_<?= $ses['id'] ?>" value="">
                                                        <input type="hidden" name="longitude" id="lng_<?= $ses['id'] ?>" value="">

                                                        <div class="mb-3">
                                                            <div class="input-group input-group-lg">
                                                                <span class="input-group-text bg-white text-muted border-end-0">
                                                                    <i class="fas fa-lock"></i>
                                                                </span>
                                                                <input type="text" 
                                                                       name="pin" 
                                                                       id="pinInput_<?= $ses['id'] ?>" 
                                                                       class="form-control text-center font-weight-bold font-monospace fs-3 border-start-0" 
                                                                       placeholder="••••" 
                                                                       maxlength="6" 
                                                                       value="<?= htmlspecialchars(($urlPin && ($urlSessionId === 0 || $urlSessionId === (int)$ses['id'])) ? $urlPin : '') ?>" 
                                                                       required 
                                                                       autocomplete="off">
                                                            </div>
                                                        </div>

                                                        <button type="submit" id="btnSubmitPin_<?= $ses['id'] ?>" class="btn btn-primary btn-lg rounded-pill font-weight-600 w-100 shadow-sm">
                                                            Verify & Mark Present <i class="fas fa-arrow-right ms-2"></i>
                                                        </button>
                                                    </form>

                                                    <?php if (!empty($ses['allowed_latitude'])): ?>
                                                        <div class="text-center pt-3 mt-3 border-top">
                                                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" onclick="getLocationAndSubmit(<?= $ses['id'] ?>)">
                                                                <i class="fas fa-map-marker-alt text-danger me-1"></i> Verify via GPS Location
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>

                                                </div>
                                            </div>

                                        </div>

                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Camera QR Scanner Modal -->
<div class="modal fade" id="qrScannerModal" tabindex="-1" aria-labelledby="qrScannerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold text-dark" id="qrScannerModalLabel">
                    <i class="fas fa-camera text-primary me-2"></i> Camera QR Scanner
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="stopCameraScanner()"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="text-muted small mb-3">Position the Live Attendance QR code within the camera frame below:</p>
                <div id="qrReader" style="width: 100%; min-height: 250px; background: #000; border-radius: 12px; overflow: hidden;" class="mx-auto mb-3"></div>
                <div id="qrScanFeedback" class="text-muted small">Initializing camera...</div>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal" onclick="stopCameraScanner()">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- HTML5 QR Code Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
let activeSessionForScan = null;
let html5QrScannerInstance = null;

// Quick Auto-fill PIN
function autoFillPin(sessionId, pin) {
    const input = document.getElementById('pinInput_' + sessionId);
    if (input) {
        input.value = pin;
        input.focus();
        showToast('PIN ' + pin + ' auto-filled! Click "Verify & Mark Present" to submit.', 'info');
    }
}

// Camera Scanner Launcher
function openCameraScanner(sessionId) {
    activeSessionForScan = sessionId;
    const modalEl = document.getElementById('qrScannerModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    setTimeout(() => {
        startScanner();
    }, 400);
}

function startScanner() {
    const feedback = document.getElementById('qrScanFeedback');
    feedback.innerText = 'Searching for QR code...';

    if (typeof Html5Qrcode !== 'undefined') {
        html5QrScannerInstance = new Html5Qrcode("qrReader");
        html5QrScannerInstance.start(
            { facingMode: "environment" },
            {
                fps: 10,
                qrbox: { width: 220, height: 220 }
            },
            (decodedText, decodedResult) => {
                handleScannedQr(decodedText);
            },
            (errorMessage) => {
                // scanning frame ignored
            }
        ).catch(err => {
            feedback.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Camera access error: ' + err + '</span>';
        });
    } else {
        feedback.innerText = 'Scanner library loading failed. Please use PIN verification.';
    }
}

function stopCameraScanner() {
    if (html5QrScannerInstance) {
        html5QrScannerInstance.stop().then(() => {
            html5QrScannerInstance.clear();
            html5QrScannerInstance = null;
        }).catch(err => {
            console.warn(err);
        });
    }
}

function handleScannedQr(decodedText) {
    stopCameraScanner();
    const modalEl = document.getElementById('qrScannerModal');
    const modalInstance = bootstrap.Modal.getInstance(modalEl);
    if (modalInstance) {
        modalInstance.hide();
    }

    let extractedPin = '';
    try {
        if (decodedText.includes('pin=')) {
            const urlObj = new URL(decodedText);
            extractedPin = urlObj.searchParams.get('pin') || '';
        } else if (/^\d{4,6}$/.test(decodedText.trim())) {
            extractedPin = decodedText.trim();
        }
    } catch (e) {
        if (/^\d{4,6}$/.test(decodedText.trim())) {
            extractedPin = decodedText.trim();
        }
    }

    if (!extractedPin && decodedText.includes('pin=')) {
        const match = decodedText.match(/[?&]pin=([0-9a-zA-Z]+)/);
        if (match) extractedPin = match[1];
    }

    if (activeSessionForScan && extractedPin) {
        const input = document.getElementById('pinInput_' + activeSessionForScan);
        const methodInput = document.getElementById('method_' + activeSessionForScan);
        const form = document.getElementById('pinForm_' + activeSessionForScan);

        if (input && form) {
            input.value = extractedPin;
            if (methodInput) methodInput.value = 'qr';
            showToast('QR Code Scanned! Submitting attendance...', 'success');
            setTimeout(() => {
                form.submit();
            }, 600);
            return;
        }
    }

    showToast('QR code scanned: ' + decodedText, 'info');
}

// Calculate distance between two coordinates in meters (Haversine formula in JS)
function calculateDistanceMetersJS(lat1, lon1, lat2, lon2) {
    if (!lat1 || !lon1 || !lat2 || !lon2) return null;
    const R = 6371000; // Earth radius in meters
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return Math.round(R * c);
}

// Live Distance Detector & Feedback for Student
function refreshStudentDistance(sessionId, allowedLat, allowedLng, allowedRadius) {
    const textEl = document.getElementById('distText_' + sessionId);
    const badgeEl = document.getElementById('distBadge_' + sessionId);
    const latInput = document.getElementById('lat_' + sessionId);
    const lngInput = document.getElementById('lng_' + sessionId);

    if (!navigator.geolocation) {
        if (textEl) textEl.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Geolocation not supported by your browser.</span>';
        return;
    }

    if (textEl) textEl.innerHTML = '<span class="text-primary"><i class="fas fa-spinner fa-spin me-1"></i> Reading your current GPS position...</span>';

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            const studentLat = pos.coords.latitude;
            const studentLng = pos.coords.longitude;
            const accuracy = Math.round(pos.coords.accuracy);

            if (latInput) latInput.value = studentLat;
            if (lngInput) lngInput.value = studentLng;

            const distance = calculateDistanceMetersJS(allowedLat, allowedLng, studentLat, studentLng);

            if (distance !== null) {
                if (distance <= allowedRadius) {
                    if (textEl) {
                        textEl.innerHTML = '<span class="text-success fw-bold"><i class="fas fa-circle-check me-1"></i> In Classroom Range: ' + distance + 'm away (Allowed: ' + allowedRadius + 'm)</span>' +
                            '<span class="text-muted small ms-2">• GPS Accuracy: ±' + accuracy + 'm</span>';
                    }
                    if (badgeEl) {
                        badgeEl.innerHTML = '<span class="badge bg-success rounded-pill px-3 py-2"><i class="fas fa-check me-1"></i> Eligible (' + distance + 'm)</span>';
                    }
                } else {
                    if (textEl) {
                        textEl.innerHTML = '<span class="text-danger fw-bold"><i class="fas fa-triangle-exclamation me-1"></i> Outside Range: ' + distance + 'm away (Must be within ' + allowedRadius + 'm)</span>' +
                            '<div class="text-muted small mt-1">Please move inside the classroom to record attendance.</div>';
                    }
                    if (badgeEl) {
                        badgeEl.innerHTML = '<span class="badge bg-danger rounded-pill px-3 py-2"><i class="fas fa-xmark me-1"></i> Too Far (' + distance + 'm)</span>';
                    }
                }
            }
        },
        function(err) {
            let errMsg = 'Location detection failed: ';
            if (err.code === err.PERMISSION_DENIED) {
                errMsg = 'GPS permission denied. Please enable location permissions in browser.';
            } else {
                errMsg += err.message;
            }
            if (textEl) textEl.innerHTML = '<span class="text-danger small"><i class="fas fa-exclamation-circle me-1"></i> ' + errMsg + '</span>';
            if (badgeEl) badgeEl.innerHTML = '<span class="badge bg-warning text-dark rounded-pill px-3 py-2">Location Required</span>';
        },
        { enableHighAccuracy: true, timeout: 8000, maximumAge: 10000 }
    );
}

// Intercept PIN Attendance Submit if Geofence Required
function submitPinAttendance(event, sessionId, isGeoRequired, allowedLat, allowedLng, allowedRadius) {
    if (!isGeoRequired) {
        return true;
    }

    const latInput = document.getElementById('lat_' + sessionId);
    const lngInput = document.getElementById('lng_' + sessionId);
    const pinInput = document.getElementById('pinInput_' + sessionId);
    const btn = document.getElementById('btnSubmitPin_' + sessionId);

    if (!pinInput || !pinInput.value.trim()) {
        showToast('Please enter the 4-digit PIN first.', 'warning');
        return false;
    }

    // If coordinates already cached and available
    if (latInput && lngInput && latInput.value && lngInput.value) {
        const studentLat = parseFloat(latInput.value);
        const studentLng = parseFloat(lngInput.value);
        const dist = calculateDistanceMetersJS(allowedLat, allowedLng, studentLat, studentLng);

        if (dist !== null && dist > allowedRadius) {
            event.preventDefault();
            showToast('Distance Check Blocked: You are ' + dist + 'm away from the classroom (Allowed limit: ' + allowedRadius + 'm). Move inside the classroom.', 'danger');
            return false;
        }
        return true;
    }

    // Otherwise, acquire position before submitting
    event.preventDefault();
    if (!navigator.geolocation) {
        showToast('Geolocation is not supported by your browser. Location verification is required.', 'danger');
        return false;
    }

    const origBtnHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Verifying GPS Distance...';
    }

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            const studentLat = pos.coords.latitude;
            const studentLng = pos.coords.longitude;
            latInput.value = studentLat;
            lngInput.value = studentLng;

            const dist = calculateDistanceMetersJS(allowedLat, allowedLng, studentLat, studentLng);
            if (dist !== null && dist > allowedRadius) {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = origBtnHtml;
                }
                showToast('Distance check failed: You are ' + dist + 'm away (Allowed radius: ' + allowedRadius + 'm). Attendance cannot be marked.', 'danger');
                refreshStudentDistance(sessionId, allowedLat, allowedLng, allowedRadius);
                return;
            }

            // In range: submit form
            if (btn) btn.innerHTML = '<i class="fas fa-check me-2"></i> In Range (' + dist + 'm)! Submitting...';
            document.getElementById('pinForm_' + sessionId).submit();
        },
        function(err) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origBtnHtml;
            }
            showToast('GPS permission required: Please allow location access to mark attendance for this session.', 'danger');
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );

    return false;
}

// GPS Location submit
function getLocationAndSubmit(sessionId) {
    if (navigator.geolocation) {
        showToast('Acquiring your GPS location...', 'info');
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            fetch('<?= APP_URL ?>/api/verify_location.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    session_id: sessionId,
                    latitude: lat,
                    longitude: lng
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message || 'Location verification failed', 'danger');
                }
            })
            .catch(err => {
                showToast('Network error while verifying location: ' + err, 'danger');
            });
        }, function(error) {
            showToast('Unable to fetch location: ' + error.message, 'danger');
        }, { enableHighAccuracy: true, timeout: 10000 });
    } else {
        showToast('Geolocation is not supported by this browser.', 'warning');
    }
}

// Auto-check distance on load for active geofenced sessions
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const pin = urlParams.get('pin');
    if (pin) {
        const inputs = document.querySelectorAll('input[name="pin"]');
        if (inputs.length === 1 && !inputs[0].value) {
            inputs[0].value = pin;
        }
    }

    // Auto-detect distance for all geofenced sessions
    <?php foreach ($activeSessions as $ses): ?>
        <?php if (!empty($ses['allowed_latitude'])): ?>
            refreshStudentDistance(<?= $ses['id'] ?>, <?= (float)$ses['allowed_latitude'] ?>, <?= (float)$ses['allowed_longitude'] ?>, <?= (int)($ses['location_radius'] ?: 100) ?>);
        <?php endif; ?>
    <?php endforeach; ?>
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
