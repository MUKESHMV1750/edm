<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';
checkRole([ROLE_STAFF]);

$pageTitle = "Faculty Reports Hub";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">Faculty Analytics & Reports Hub</h1>
                    <p class="text-muted small">Generate exportable PDF and CSV reports across attendance, internal marks, and course analytics</p>
                </div>
            </div>

            <div class="row g-4">
                
                <!-- Attendance Report Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm text-center p-4 h-100" style="border-radius: 20px;">
                        <div class="rounded-circle bg-primary-lightest text-primary mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; font-size: 28px;">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h4 class="h5 font-weight-bold text-dark mb-2">Attendance Reports</h4>
                        <p class="text-muted small mb-4">Detailed student attendance percentages & turnouts by subject and date range.</p>
                        <a href="<?= APP_URL ?>/staff/reports/attendance.php" class="btn btn-primary rounded-pill font-weight-600 px-4">
                            Generate Report <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Internal Marks Report Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm text-center p-4 h-100" style="border-radius: 20px;">
                        <div class="rounded-circle bg-success-light text-success mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; font-size: 28px;">
                            <i class="fas fa-award"></i>
                        </div>
                        <h4 class="h5 font-weight-bold text-dark mb-2">Marks & Grades</h4>
                        <p class="text-muted small mb-4">Internal assessment 1, 2 and model exam grade sheets and class averages.</p>
                        <a href="<?= APP_URL ?>/staff/reports/marks.php" class="btn btn-success rounded-pill font-weight-600 px-4">
                            Generate Report <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Assignment Report Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm text-center p-4 h-100" style="border-radius: 20px;">
                        <div class="rounded-circle bg-warning-light text-warning mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; font-size: 28px;">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h4 class="h5 font-weight-bold text-dark mb-2">Assignment Analytics</h4>
                        <p class="text-muted small mb-4">Coursework submission rates, evaluation delays, and score distribution.</p>
                        <a href="<?= APP_URL ?>/staff/reports/assignments.php" class="btn btn-warning text-dark rounded-pill font-weight-600 px-4">
                            Generate Report <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Presentation Report Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm text-center p-4 h-100" style="border-radius: 20px;">
                        <div class="rounded-circle bg-info-light text-info mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; font-size: 28px;">
                            <i class="fas fa-desktop"></i>
                        </div>
                        <h4 class="h5 font-weight-bold text-dark mb-2">Live Presentations</h4>
                        <p class="text-muted small mb-4">Broadcast session engagement logs, viewer retention and slide counts.</p>
                        <a href="<?= APP_URL ?>/staff/reports/presentations.php" class="btn btn-info text-white rounded-pill font-weight-600 px-4">
                            Generate Report <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
