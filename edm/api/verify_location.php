<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== ROLE_STUDENT) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$sessionId = (int)($input['session_id'] ?? 0);
$lat = (float)($input['latitude'] ?? 0);
$lng = (float)($input['longitude'] ?? 0);
$studentId = $_SESSION['role_id'] ?? 0;
if (!$studentId && !empty($_SESSION['user_id'])) {
    $dbFallback = getDB();
    $stUserStmt = $dbFallback->prepare("SELECT id FROM students WHERE user_id = ?");
    $stUserStmt->execute([$_SESSION['user_id']]);
    $studentId = (int)$stUserStmt->fetchColumn();
    if ($studentId) {
        $_SESSION['role_id'] = $studentId;
    }
}

if ($sessionId > 0 && $lat != 0 && $lng != 0 && $studentId > 0) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM attendance_sessions WHERE id = ? AND status = 'active' AND expires_at > NOW()");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();

    if ($session) {
        $distanceMeters = null;
        $radius = (int)($session['location_radius'] ?: 100);

        // Distance & Geofence Verification if session specifies coordinates
        if (!empty($session['allowed_latitude']) && !empty($session['allowed_longitude'])) {
            $allowedLat = (float)$session['allowed_latitude'];
            $allowedLng = (float)$session['allowed_longitude'];
            $distanceMeters = calculateDistanceMeters($allowedLat, $allowedLng, $lat, $lng);

            if ($distanceMeters > $radius) {
                echo json_encode([
                    'success' => false,
                    'distance' => $distanceMeters,
                    'allowed_radius' => $radius,
                    'message' => "Location verification failed: You are {$distanceMeters}m away from the classroom (Allowed distance: {$radius}m). Please move inside the classroom."
                ]);
                exit;
            }
        }

        $ins = $db->prepare("
            INSERT INTO attendance_records (session_id, student_id, status, marked_via, latitude, longitude, distance_meters, marked_at)
            VALUES (?, ?, 'present', 'gps', ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE status = 'present', marked_via = 'gps', latitude = ?, longitude = ?, distance_meters = ?, marked_at = NOW()
        ");
        $ins->execute([$sessionId, $studentId, $lat, $lng, $distanceMeters, $lat, $lng, $distanceMeters]);

        $successMsg = 'Attendance verified via GPS successfully!';
        if ($distanceMeters !== null) {
            $successMsg = "Location verified! Distance: {$distanceMeters}m from classroom (Allowed: {$radius}m). Attendance recorded!";
        }

        echo json_encode([
            'success' => true,
            'distance' => $distanceMeters,
            'allowed_radius' => $radius,
            'message' => $successMsg
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid session or location coordinates.']);
