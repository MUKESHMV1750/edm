<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STAFF]);

$staffId = $_SESSION['role_id'] ?? 0;
$db = getDB();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $subject_id = (int)$_POST['subject_id'];
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $file_type = sanitizeInput($_POST['file_type'] ?? 'pdf');

        if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/materials/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = time() . '_' . basename($_FILES['material_file']['name']);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['material_file']['tmp_name'], $targetPath)) {
                $file_path = 'uploads/materials/' . $fileName;

                $subjInfo = $db->prepare("SELECT department_id, semester FROM subjects WHERE id = ?");
                $subjInfo->execute([$subject_id]);
                $sRow = $subjInfo->fetch();
                $deptId = $sRow['department_id'] ?? 1;
                $sem = $sRow['semester'] ?? 6;

                $ins = $db->prepare("
                    INSERT INTO study_materials (subject_id, staff_id, uploaded_by, department_id, semester, academic_year, title, description, file_path, file_name, file_type)
                    VALUES (?, ?, ?, ?, ?, '2024-2025', ?, ?, ?, ?, ?)
                ");
                $ins->execute([$subject_id, $staffId, $staffId, $deptId, $sem, $title, $description, $file_path, $fileName, $file_type]);

                setFlashMessage('success', 'Study material uploaded successfully!');
                redirect('staff/upload_materials.php');
            }
        } else {
            setFlashMessage('danger', 'Please select a valid file to upload.');
        }
    }
}

// Fetch Staff Subjects
$subStmt = $db->prepare("SELECT * FROM subjects WHERE staff_id = ? ORDER BY code ASC");
$subStmt->execute([$staffId]);
$subjects = $subStmt->fetchAll();

// Fetch Uploaded Materials
$matStmt = $db->prepare("
    SELECT sm.*, sub.code AS subject_code, sub.name AS subject_name
    FROM study_materials sm
    JOIN subjects sub ON sm.subject_id = sub.id
    WHERE (sm.staff_id = ? OR sm.uploaded_by = ?)
    ORDER BY COALESCE(sm.uploaded_at, sm.created_at) DESC
");
$matStmt->execute([$staffId, $staffId]);
$materials = $matStmt->fetchAll();

$pageTitle = "Upload Study Materials";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Study Materials & Notes</h1>
                    <p class="text-muted small">Upload syllabus documents, lecture slide decks, and reference PDFs for students</p>
                </div>
            </div>

            <?php displayAlerts(); ?>

            <div class="row g-4 mb-4">
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
                        <h5 class="h6 font-weight-bold text-dark mb-3"><i class="fas fa-upload text-success me-2"></i> Upload New Resource</h5>
                        
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
                                <label for="title" class="form-label font-weight-500">Document Title</label>
                                <input type="text" id="title" name="title" class="form-control" placeholder="e.g. Unit 1 Data Structures Notes" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label font-weight-500">Short Description</label>
                                <textarea id="description" name="description" class="form-control" rows="2" placeholder="Brief summary..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="file_type" class="form-label font-weight-500">Material Type</label>
                                <select id="file_type" name="file_type" class="form-select">
                                    <option value="pdf">PDF Document</option>
                                    <option value="ppt">PowerPoint Presentation (PPT/PPTX)</option>
                                    <option value="doc">Word / Text Document</option>
                                    <option value="zip">ZIP / Archive Source Code</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="material_file" class="form-label font-weight-500">Select File</label>
                                <input type="file" id="material_file" name="material_file" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-success rounded-pill px-4 font-weight-600 w-100">
                                Upload Material <i class="fas fa-paper-plane ms-2"></i>
                            </button>
                        </form>

                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="h6 font-weight-bold text-dark mb-0"><i class="fas fa-folder text-primary me-2"></i> Uploaded Materials Library</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Subject</th>
                                        <th>Title</th>
                                        <th>Uploaded On</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($materials)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">No materials uploaded yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($materials as $m): ?>
                                            <tr>
                                                <td><span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1"><?= htmlspecialchars($m['subject_code']) ?></span></td>
                                                <td><strong><?= htmlspecialchars($m['title']) ?></strong></td>
                                                <td class="small"><?= date('d M Y', strtotime($m['uploaded_at'] ?? $m['created_at'])) ?></td>
                                                <td>
                                                    <a href="<?= APP_URL . '/' . $m['file_path'] ?>" class="btn btn-sm btn-outline-primary rounded-pill font-weight-600" target="_blank">
                                                        Download <i class="fas fa-download ms-1"></i>
                                                    </a>
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
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
