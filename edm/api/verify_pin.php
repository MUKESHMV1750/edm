<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn() || $_SESSION['user_role'] !== ROLE_STUDENT) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
           || (!empty($_POST['is_ajax'])) 
           || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $sessionId = (int)$_POST['session_id'];
        $pin = sanitizeInput($_POST['pin']);
        $method = sanitizeInput($_POST['method'] ?? 'pin');
        if (!in_array($method, ['pin', 'qr', 'manual', 'gps'])) {
            $method = 'pin';
        }

        $studentId = $_SESSION['role_id'] ?? 0;
        $db = getDB();

        if (!$studentId && !empty($_SESSION['user_id'])) {
            $stStmt = $db->prepare("SELECT id FROM students WHERE user_id = ?");
            $stStmt->execute([$_SESSION['user_id']]);
            $studentId = (int)$stStmt->fetchColumn();
            if ($studentId) {
                $_SESSION['role_id'] = $studentId;
            }
        }

        // Verify Session & PIN
        $stmt = $db->prepare("
            SELECT * FROM attendance_sessions 
            WHERE id = ? AND status = 'active' AND expires_at > NOW()
        ");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();

        if ($session) {
            $sessionPin = $session['pin_code'] ?? ($session['session_pin'] ?? '');
            if ($sessionPin === $pin) {
                $studentLat = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
                $studentLng = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
                $distanceMeters = null;

                // Geofence & Distance Validation
                if (!empty($session['allowed_latitude']) && !empty($session['allowed_longitude'])) {
                    $allowedLat = (float)$session['allowed_latitude'];
                    $allowedLng = (float)$session['allowed_longitude'];
                    $radius = (int)($session['location_radius'] ?: 100);

                    if ($studentLat !== null && $studentLng !== null && $studentLat != 0 && $studentLng != 0) {
                        $distanceMeters = calculateDistanceMeters($allowedLat, $allowedLng, $studentLat, $studentLng);
                        if ($distanceMeters > $radius) {
                            $errMsg = "Location distance check failed: You are {$distanceMeters}m away from the classroom (Allowed distance: {$radius}m). Please check in from the classroom.";
                            if ($isAjax) {
                                header('Content-Type: application/json');
                                echo json_encode(['success' => false, 'message' => $errMsg, 'distance' => $distanceMeters, 'allowed_radius' => $radius]);
                                exit;
                            }
                            setFlashMessage('danger', $errMsg);
                            redirect('student/attendance.php');
                        }
                    } else {
                        $errMsg = "Location coordinates required: This attendance session requires location verification within {$radius}m. Please allow GPS location in your browser.";
                        if ($isAjax) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'message' => $errMsg, 'allowed_radius' => $radius]);
                            exit;
                        }
                        setFlashMessage('danger', $errMsg);
                        redirect('student/attendance.php');
                    }
                }

                // Record Attendance
                $ins = $db->prepare("
                    INSERT INTO attendance_records (session_id, student_id, status, marked_via, latitude, longitude, distance_meters, marked_at)
                    VALUES (?, ?, 'present', ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE status = 'present', marked_via = ?, latitude = ?, longitude = ?, distance_meters = ?, marked_at = NOW()
                ");
                $ins->execute([$sessionId, $studentId, $method, $studentLat, $studentLng, $distanceMeters, $method, $studentLat, $studentLng, $distanceMeters]);

                $successMsg = 'Attendance marked successfully via ' . strtoupper($method) . '!';
                if ($distanceMeters !== null) {
                    $successMsg .= " (Distance: {$distanceMeters}m)";
                }

                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => $successMsg]);
                    exit;
                }

                setFlashMessage('success', $successMsg);
            } else {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Invalid 4-Digit PIN. Please try again.']);
                    exit;
                }
                setFlashMessage('danger', 'Invalid 4-Digit PIN. Please try again.');
            }
        } else {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'This attendance session has expired or is inactive.']);
                exit;
            }
            setFlashMessage('danger', 'This attendance session has expired or is inactive.');
        }
    } else {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid session security token. Please refresh.']);
            exit;
        }
        setFlashMessage('danger', 'Invalid session security token. Please refresh.');
    }
}

redirect('student/attendance.php');

