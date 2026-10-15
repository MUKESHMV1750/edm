<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';
checkRole([ROLE_ADMIN]);

$db = getDB();

$materials = $db->query("
    SELECT sm.*, sub.code AS subject_code, st.first_name, st.last_name
    FROM study_materials sm
    JOIN subjects sub ON sm.subject_id = sub.id
    JOIN staff st ON sm.staff_id = st.id
    ORDER BY sm.uploaded_at DESC
")->fetchAll();

$pageTitle = "Admin Materials Report";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Study Resources Upload Log</h1>
                    <p class="text-muted small">Global repository log of study notes and lecture slides</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Title</th>
                                <th>Faculty</th>
                                <th>Uploaded Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materials as $m): ?>
                                <tr>
                                    <td><span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1"><?= htmlspecialchars($m['subject_code']) ?></span></td>
                                    <td><strong><?= htmlspecialchars($m['title']) ?></strong></td>
                                    <td>Prof. <?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?></td>
                                    <td class="small"><?= date('d M Y', strtotime($m['uploaded_at'])) ?></td>
                                    <td>
                                        <a href="<?= APP_URL . '/' . $m['file_path'] ?>" class="btn btn-sm btn-outline-primary rounded-pill font-weight-600" target="_blank">
                                            Download <i class="fas fa-download ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
