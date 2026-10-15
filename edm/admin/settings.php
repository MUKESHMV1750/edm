<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_ADMIN]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('success', 'System settings saved successfully!');
        redirect('admin/settings.php');
    }
}

$pageTitle = "System Settings";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">System Global Configuration</h1>
                    <p class="text-muted small">Manage portal settings, academic session thresholds, and security rules</p>
                </div>
            </div>

            <?php displayAlerts(); ?>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
                        
                        <form action="" method="POST">
                            <?= csrfField() ?>

                            <div class="mb-3">
                                <label class="form-label font-weight-500">Institution Name</label>
                                <input type="text" class="form-control" value="Engineering Department Management System (EDM)" required>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label font-weight-500">Current Academic Year</label>
                                    <input type="text" class="form-control" value="2024-2025" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label font-weight-500">Minimum Attendance Threshold (%)</label>
                                    <input type="number" class="form-control" value="75" required>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label font-weight-500">Default Attendance PIN Duration (Mins)</label>
                                    <input type="number" class="form-control" value="10" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label font-weight-500">Max Upload Attachment Size (MB)</label>
                                    <input type="number" class="form-control" value="10" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary rounded-pill px-4 font-weight-600">
                                Save System Configuration <i class="fas fa-save ms-2"></i>
                            </button>
                        </form>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
