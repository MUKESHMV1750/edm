<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STAFF]);

$staffId = $_SESSION['role_id'] ?? 0;
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$db = getDB();

// Fetch Session & Presentation
$stmt = $db->prepare("
    SELECT ps.*, COALESCE(ps.pin_code, ps.session_code, '') AS session_pin, ps.current_page AS current_slide, p.title, p.description, COALESCE(p.total_pages, 0) AS total_slides, p.total_pages, sub.code AS subject_code, sub.name AS subject_name
    FROM presentation_sessions ps
    JOIN presentations p ON ps.presentation_id = p.id
    JOIN subjects sub ON p.subject_id = sub.id
    WHERE ps.id = ? AND p.staff_id = ?
");
$stmt->execute([$sessionId, $staffId]);
$session = $stmt->fetch();

if (!$session) {
    setFlashMessage('danger', 'Presentation session not found.');
    redirect('staff/dashboard.php');
}

$pageTitle = "Faculty Live Console: " . $session['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100 bg-dark text-white" style="min-height: 100vh;">
        
        <!-- Header Bar -->
        <div class="bg-gray-900 border-bottom border-secondary px-4 py-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-danger rounded-pill px-3 py-2 animate-pulse">
                    <i class="fas fa-circle fs-6 me-1"></i> BROADCASTING LIVE
                </span>
                <div>
                    <span class="badge bg-primary me-2"><?= htmlspecialchars($session['subject_code']) ?></span>
                    <strong class="h6 mb-0 text-white"><?= htmlspecialchars($session['title']) ?></strong>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-info-light text-info px-3 py-2 font-weight-600">
                    PIN: <strong class="letter-spacing-2 text-white"><?= htmlspecialchars($session['session_pin'] ?? $session['pin_code'] ?? $session['session_code'] ?? '') ?></strong>
                </span>
                <a href="<?= APP_URL ?>/staff/live_activity.php?session_id=<?= $sessionId ?>" class="btn btn-outline-info btn-sm rounded-pill" target="_blank">
                    <i class="fas fa-users me-1"></i> Viewers Log
                </a>
                <button class="btn btn-danger btn-sm rounded-pill font-weight-600 px-3" onclick="endSession()">
                    <i class="fas fa-stop me-1"></i> End Session
                </button>
            </div>
        </div>

        <div class="container-fluid p-4">
            
            <div class="row g-4">
                
                <!-- Slide Projector Box -->
                <div class="col-lg-8">
                    <div class="card bg-black border-secondary text-center p-4 position-relative d-flex flex-column align-items-center justify-content-center shadow-2xl" style="min-height: 480px; border-radius: 20px;">
                        
                        <span class="badge bg-primary text-white mb-3 px-3 py-1 font-weight-600">
                            SLIDE BROADCAST CONTROL
                        </span>

                        <div class="display-1 text-accent font-weight-extrabold my-3">
                            #<span id="currentSlideDisplay"><?= $session['current_slide'] ?></span>
                        </div>
                        <p class="text-muted">Slide <span id="slideCurrentNum"><?= $session['current_slide'] ?></span> of <?= $session['total_slides'] ?></p>

                        <!-- Controls Bar -->
                        <div class="d-flex align-items-center gap-3 mt-4">
                            <button class="btn btn-outline-light btn-lg rounded-circle" onclick="changeSlide(-1)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="btn btn-accent btn-lg rounded-pill px-5 font-weight-700" onclick="changeSlide(1)">
                                Next Slide <i class="fas fa-chevron-right ms-2"></i>
                            </button>
                            <button class="btn btn-outline-light btn-lg rounded-circle" onclick="changeSlide(1)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Jump to Slide & Broadcast Notes -->
                <div class="col-lg-4">
                    <div class="card bg-gray-900 border-secondary text-white p-4 h-100 d-flex flex-column justify-content-between" style="border-radius: 20px;">
                        <div>
                            <h5 class="h6 font-weight-bold text-white mb-3 border-bottom border-secondary pb-2">
                                <i class="fas fa-bullhorn text-accent me-2"></i> Faculty Broadcast Notes
                            </h5>
                            
                            <!-- Jump to Slide form -->
                            <div class="mb-4">
                                <label class="form-label text-muted small">Jump Directly to Slide #</label>
                                <div class="input-group">
                                    <input type="number" id="jumpSlideInput" class="form-control bg-dark text-white border-secondary" min="1" max="<?= $session['total_slides'] ?>" value="<?= $session['current_slide'] ?>">
                                    <button class="btn btn-primary" onclick="jumpToSlide()">Jump</button>
                                </div>
                            </div>

                            <!-- Live Slide Notes -->
                            <div class="mb-3">
                                <label class="form-label text-muted small">Broadcast Notes / Annotations to Students</label>
                                <textarea id="slideNotesInput" class="form-control bg-dark text-white border-secondary" rows="5" placeholder="Type key formulas, code snippet hints, or lecture notes here..."><?= htmlspecialchars($session['slide_notes'] ?? '') ?></textarea>
                            </div>

                            <button class="btn btn-success btn-sm w-100 rounded-pill font-weight-600" onclick="updateNotes()">
                                Send Notes to Student Screen <i class="fas fa-paper-plane ms-1"></i>
                            </button>
                        </div>

                        <div class="pt-3 border-top border-secondary text-center text-muted small">
                            Active Session Code: <strong><?= htmlspecialchars($session['session_pin'] ?? $session['pin_code'] ?? $session['session_code'] ?? '') ?></strong>
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

function updateSlideUI() {
    document.getElementById('currentSlideDisplay').innerText = currentSlide;
    document.getElementById('slideCurrentNum').innerText = currentSlide;
    document.getElementById('jumpSlideInput').value = currentSlide;
}

function changeSlide(delta) {
    let newSlide = currentSlide + delta;
    if (newSlide >= 1 && newSlide <= totalSlides) {
        currentSlide = newSlide;
        sendSync('change_slide', { current_slide: currentSlide });
    }
}

function jumpToSlide() {
    let target = parseInt(document.getElementById('jumpSlideInput').value);
    if (target >= 1 && target <= totalSlides) {
        currentSlide = target;
        sendSync('change_slide', { current_slide: currentSlide });
    }
}

function updateNotes() {
    let notes = document.getElementById('slideNotesInput').value;
    sendSync('update_notes', { slide_notes: notes });
    showToast("Notes broadcasted to viewers!", "success");
}

function endSession() {
    if (confirm("Are you sure you want to end this live presentation broadcast?")) {
        sendSync('end_session', {});
        setTimeout(() => window.location.href = '<?= APP_URL ?>/staff/presentations.php', 1000);
    }
}

function sendSync(action, extraData) {
    updateSlideUI();
    fetch('<?= APP_URL ?>/api/presentation_session.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: action,
            session_id: sessionId,
            ...extraData
        })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            showToast(data.message || "Failed to update broadcast", "danger");
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
