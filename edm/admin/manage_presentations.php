<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_ADMIN]);

$db = getDB();

$presentations = $db->query("
    SELECT p.*, COALESCE(p.total_pages, 0) AS total_slides, sub.code AS subject_code, sub.name AS subject_name, st.first_name, st.last_name
    FROM presentations p
    JOIN subjects sub ON p.subject_id = sub.id
    JOIN staff st ON p.staff_id = st.id
    ORDER BY p.created_at DESC
")->fetchAll();

$pageTitle = "Presentations Oversight";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Presentation Decks Oversight</h1>
                    <p class="text-muted small">Global repository of faculty slide decks and live broadcast sessions</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Presentation Title</th>
                                <th>Faculty Staff</th>
                                <th>Slides</th>
                                <th>Created On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($presentations as $p): ?>
                                <tr>
                                    <td><span class="badge bg-primary-lightest text-primary font-weight-600 px-3 py-1"><?= htmlspecialchars($p['subject_code']) ?></span></td>
                                    <td><strong><?= htmlspecialchars($p['title']) ?></strong></td>
                                    <td>Prof. <?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></td>
                                    <td><span class="badge bg-light text-dark font-weight-600"><?= $p['total_slides'] ?> Slides</span></td>
                                    <td class="small"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
