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
        $subject_id = (int)$_POST['subject_id'];
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $total_slides = (int)$_POST['total_slides'];
        $file_path = null;

        if (isset($_FILES['presentation_file']) && $_FILES['presentation_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/presentations/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = time() . '_' . basename($_FILES['presentation_file']['name']);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['presentation_file']['tmp_name'], $targetPath)) {
                $file_path = 'uploads/presentations/' . $fileName;
            }
        }

        // Get subject metadata
        $subInfoStmt = $db->prepare("SELECT department_id, semester FROM subjects WHERE id = ?");
        $subInfoStmt->execute([$subject_id]);
        $subInfo = $subInfoStmt->fetch();
        $deptId = $subInfo['department_id'] ?? ($_SESSION['department_id'] ?? 1);
        $sem = $subInfo['semester'] ?? 1;
        $origFileName = !empty($_FILES['presentation_file']['name']) ? basename($_FILES['presentation_file']['name']) : ($fileName ?? 'presentation.pdf');

        // Insert presentation
        $insP = $db->prepare("
            INSERT INTO presentations (staff_id, subject_id, department_id, semester, title, description, file_path, file_name, total_pages)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insP->execute([$staffId, $subject_id, $deptId, $sem, $title, $description, $file_path, $origFileName, $total_slides]);
        $presentationId = $db->lastInsertId();

        // Check if start session immediately
        if (isset($_POST['start_immediately'])) {
            $pin = sprintf("%06d", rand(100000, 999999));
            $code = 'PS-' . substr(md5(uniqid()), 0, 8);
            $insS = $db->prepare("
                INSERT INTO presentation_sessions (presentation_id, staff_id, current_page, session_code, pin_code, status)
                VALUES (?, ?, 1, ?, ?, 'active')
            ");
            $insS->execute([$presentationId, $staffId, $code, $pin]);
            $sessionId = $db->lastInsertId();

            setFlashMessage('success', "Live Presentation session broadcasting! PIN: {$pin}");
            redirect("staff/live_presentation.php?session_id={$sessionId}");
        }

        setFlashMessage('success', 'Presentation uploaded to library successfully!');
        redirect('staff/presentations.php');
    }
}

// Fetch Staff Subjects
$subStmt = $db->prepare("SELECT * FROM subjects WHERE staff_id = ? ORDER BY code ASC");
$subStmt->execute([$staffId]);
$subjects = $subStmt->fetchAll();

$pageTitle = "New Faculty Presentation";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Upload & Start Live Presentation</h1>
                    <p class="text-muted small">Broadcast slide decks in real-time to student devices</p>
                </div>
            </div>

            <?php displayAlerts(); ?>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
                        
                        <form action="" method="POST" enctype="multipart/form-data">
                            <?= csrfField() ?>

                            <div class="mb-3">
                                <label for="subject_id" class="form-label font-weight-500">Subject</label>
                                <select id="subject_id" name="subject_id" class="form-select" required>
                                    <option value="">-- Choose Subject --</option>
                                    <?php foreach ($subjects as $s): ?>
                                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['code']) ?> - <?= htmlspecialchars($s['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="title" class="form-label font-weight-500">Presentation Title</label>
                                <input type="text" id="title" name="title" class="form-control" placeholder="e.g. Chapter 4 - Binary Search Trees" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label font-weight-500">Lecture Summary</label>
                                <textarea id="description" name="description" class="form-control" rows="3" placeholder="Topics covered in this presentation..."></textarea>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="total_slides" class="form-label font-weight-500">Total Number of Slides</label>
                                    <input type="number" id="total_slides" name="total_slides" class="form-control" value="15" min="1" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="presentation_file" class="form-label font-weight-500">Upload Presentation (PDF / PPTX)</label>
                                    <input type="file" id="presentation_file" name="presentation_file" class="form-control">
                                </div>
                            </div>

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="start_immediately" name="start_immediately" value="1" checked>
                                <label class="form-check-label font-weight-500 text-dark" for="start_immediately">
                                    Start Live Broadcast Immediately After Creation
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 font-weight-600 w-100">
                                Launch Live Broadcast Console <i class="fas fa-desktop ms-2"></i>
                            </button>
                        </form>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
