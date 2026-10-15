<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STUDENT]);

$studentId = $_SESSION['role_id'] ?? 0;
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$db = getDB();

// Fetch Session & Presentation details
$stmt = $db->prepare("
    SELECT 
        ps.*, 
        ps.current_page AS current_slide,
        p.title, p.description, 
        COALESCE(p.total_pages, 0) AS total_slides, 
        p.total_pages, 
        p.file_path,
        sub.code AS subject_code, sub.name AS subject_name,
        st.first_name, st.last_name
    FROM presentation_sessions ps
    JOIN presentations p ON ps.presentation_id = p.id
    JOIN subjects sub ON p.subject_id = sub.id
    JOIN staff st ON p.staff_id = st.id
    WHERE ps.id = ?
");
$stmt->execute([$sessionId]);
$session = $stmt->fetch();

if (!$session) {
    setFlashMessage('danger', 'Presentation session not found.');
    redirect('student/live_presentations.php');
}

// Log initial join in presentation_participants
$logStmt = $db->prepare("
    INSERT INTO presentation_participants (session_id, student_id, joined_at, last_heartbeat, connection_status)
    VALUES (?, ?, NOW(), NOW(), 'connected')
    ON DUPLICATE KEY UPDATE last_heartbeat = NOW(), connection_status = 'connected'
");
$logStmt->execute([$sessionId, $studentId]);

$pageTitle = "Live Presentation: " . $session['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100 bg-dark text-white" style="min-height: 100vh;">
        <!-- Top Session Header Bar -->
        <div class="bg-gray-900 border-bottom border-secondary px-4 py-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <a href="<?= APP_URL ?>/student/live_presentations.php" class="btn btn-outline-light btn-sm rounded-circle p-2">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <span class="badge bg-primary me-2"><?= htmlspecialchars($session['subject_code']) ?></span>
                    <strong class="h6 mb-0 text-white"><?= htmlspecialchars($session['title']) ?></strong>
                    <span class="text-muted small ms-2 d-none d-md-inline">Prof. <?= htmlspecialchars($session['first_name'] . ' ' . $session['last_name']) ?></span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-danger rounded-pill px-3 py-2 animate-pulse">
                    <i class="fas fa-circle fs-6 me-1"></i> LIVE SYNC
                </span>
                <span class="text-muted small" id="participantCount">
                    <i class="fas fa-users me-1 text-info"></i> Connecting...
                </span>
            </div>
        </div>

        <div class="container-fluid p-4">
            
            <div class="row g-4">
                
                <!-- Main Slide Projection Stage -->
                <div class="col-lg-9">
                    <div class="card bg-black border-secondary text-center p-4 position-relative d-flex align-items-center justify-content-center shadow-2xl" style="min-height: 520px; border-radius: 20px;">
                        
                        <!-- Slide Content Display Box -->
                        <div id="slideStage" class="w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                            <div class="display-1 text-primary mb-3">
                                <i class="fas fa-desktop"></i>
                            </div>
                            <h2 class="h3 text-white font-weight-bold mb-2">Slide #<span id="currentSlideNum"><?= $session['current_slide'] ?></span></h2>
                            <p class="text-muted" id="slideStatusMsg">Synchronized with Faculty Controls</p>
                            
                            <div class="bg-dark p-4 rounded-3 text-start w-100 max-width-700 mx-auto mt-4 border border-secondary" id="slideContentBox">
                                <h5 class="h6 text-accent font-weight-600 mb-2">Live Broadcast Details</h5>
                                <p class="text-light small mb-0" id="slideNotesText">
                                    Faculty is currently presenting slide <?= $session['current_slide'] ?> of <?= $session['total_slides'] ?>.
                                </p>
                            </div>
                        </div>

                        <!-- Fullscreen Toggle -->
                        <button class="btn btn-sm btn-dark position-absolute bottom-0 end-0 m-3 opacity-75" onclick="toggleFullscreen()">
                            <i class="fas fa-expand me-1"></i> Fullscreen
                        </button>
                    </div>
                </div>

                <!-- Right Live Info Panel -->
                <div class="col-lg-3">
                    <div class="card bg-gray-900 border-secondary text-white h-100 p-4 d-flex flex-column justify-content-between" style="border-radius: 20px;">
                        <div>
                            <h5 class="h6 font-weight-bold text-white mb-3 border-bottom border-secondary pb-2">
                                <i class="fas fa-sliders-h text-primary me-2"></i> Session Controls
                            </h5>
                            
                            <div class="mb-4">
                                <span class="text-muted small d-block">Presentation Progress</span>
                                <div class="progress bg-dark my-2" style="height: 10px; border-radius: 5px;">
                                    <div id="progressBar" class="progress-bar bg-accent" style="width: <?= round(($session['current_slide'] / $session['total_slides']) * 100) ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>Slide <strong class="text-white" id="slideCurrentTxt"><?= $session['current_slide'] ?></strong></span>
                                    <span>Total <strong class="text-white"><?= $session['total_slides'] ?></strong></span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <span class="text-muted small d-block">Session Status</span>
                                <span id="sessionStatusBadge" class="badge bg-success mt-1">BROADCASTING</span>
                            </div>

                            <div>
                                <span class="text-muted small d-block mb-1">Subject</span>
                                <strong class="d-block text-light"><?= htmlspecialchars($session['subject_name']) ?></strong>
                            </div>
                        </div>

                        <div class="pt-4 border-top border-secondary text-center">
                            <button class="btn btn-outline-danger btn-sm w-100 rounded-pill" onclick="window.location.href='<?= APP_URL ?>/student/live_presentations.php'">
                                Leave Session
                            </button>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<script>
const sessionId = <?= $sessionId ?>;
let currentSlide = <?= $session['current_slide'] ?>;
const totalSlides = <?= $session['total_slides'] ?>;

// Poll for Live Slide Updates & Heartbeat every 2s
function syncPresentation() {
    fetch('<?= APP_URL ?>/api/presentation_sync.php?session_id=' + sessionId)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.status === 'ended') {
                    document.getElementById('slideStatusMsg').innerText = "Faculty has ended this session.";
                    document.getElementById('sessionStatusBadge').innerText = "ENDED";
                    document.getElementById('sessionStatusBadge').className = "badge bg-secondary mt-1";
                    showToast("Faculty ended the presentation session.", "info");
                    setTimeout(() => window.location.href = "<?= APP_URL ?>/student/live_presentations.php", 3000);
                    return;
                }

                if (data.current_slide !== currentSlide) {
                    currentSlide = data.current_slide;
                    document.getElementById('currentSlideNum').innerText = currentSlide;
                    document.getElementById('slideCurrentTxt').innerText = currentSlide;
                    const pct = Math.round((currentSlide / totalSlides) * 100);
                    document.getElementById('progressBar').style.width = pct + '%';
                    showToast("Slide updated to #" + currentSlide, "info");
                }

                if (data.participants_count !== undefined) {
                    document.getElementById('participantCount').innerHTML = `<i class="fas fa-users me-1 text-info"></i> ${data.participants_count} Viewers`;
                }

                if (data.slide_notes) {
                    document.getElementById('slideNotesText').innerText = data.slide_notes;
                }
            }
        })
        .catch(err => console.error("Sync error:", err));
}

// Send Heartbeat every 10s
function sendHeartbeat() {
    fetch('<?= APP_URL ?>/api/presentation_heartbeat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ session_id: sessionId, current_slide: currentSlide })
    });
}

setInterval(syncPresentation, 2000);
setInterval(sendHeartbeat, 10000);

function toggleFullscreen() {
    const elem = document.getElementById('slideStage');
    if (!document.fullscreenElement) {
        elem.requestFullscreen().catch(err => alert(`Error entering fullscreen: ${err.message}`));
    } else {
        document.exitFullscreen();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
