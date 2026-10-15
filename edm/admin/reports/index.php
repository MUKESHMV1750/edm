<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';
checkRole([ROLE_ADMIN]);

$pageTitle = "Admin Reports Hub";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">System-wide Departmental Reports</h1>
                    <p class="text-muted small">Generate full institution PDF summaries, CSV data sheets & academic performance cards</p>
                </div>
            </div>

            <div class="row g-4">
                
                <!-- Department Summary -->
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm text-center p-4 h-100" style="border-radius: 20px;">
                        <div class="rounded-circle bg-warning-light text-dark mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; font-size: 28px;">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <h4 class="h5 font-weight-bold text-dark mb-2">Department Summary Card</h4>
                        <p class="text-muted small mb-4">Complete overview of student turnout, faculty ratios, subject counts, and lab reservations.</p>
                        <a href="<?= APP_URL ?>/admin/reports/department_summary.php" class="btn btn-warning text-dark rounded-pill font-weight-600 px-4">
                            View Summary Card <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Attendance Analytics -->
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm text-center p-4 h-100" style="border-radius: 20px;">
                        <div class="rounded-circle bg-primary-lightest text-primary mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; font-size: 28px;">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h4 class="h5 font-weight-bold text-dark mb-2">Department Attendance</h4>
                        <p class="text-muted small mb-4">Classroom turnout records across all engineering departments.</p>
                        <a href="<?= APP_URL ?>/admin/reports/attendance.php" class="btn btn-primary rounded-pill font-weight-600 px-4">
                            View Attendance Report <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Marks Overview -->
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm text-center p-4 h-100" style="border-radius: 20px;">
                        <div class="rounded-circle bg-success-light text-success mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; font-size: 28px;">
                            <i class="fas fa-award"></i>
                        </div>
                        <h4 class="h5 font-weight-bold text-dark mb-2">Marks & Performance</h4>
                        <p class="text-muted small mb-4">Internal assessment scores, model exam ranks and grade sheets.</p>
                        <a href="<?= APP_URL ?>/admin/reports/marks.php" class="btn btn-success rounded-pill font-weight-600 px-4">
                            View Marks Report <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
