<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';
checkRole([ROLE_STUDENT]);

$studentId = $_SESSION['role_id'] ?? 0;
$db = getDB();

$stStmt = $db->prepare("
    SELECT s.*, u.username, u.email, d.name AS dept_name, d.code AS dept_code
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.id = ?
");
$stStmt->execute([$studentId]);
$student = $stStmt->fetch();

if (!$student && !empty($_SESSION['user_id'])) {
    $stUserStmt = $db->prepare("
        SELECT s.*, u.username, u.email, d.name AS dept_name, d.code AS dept_code
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN departments d ON s.department_id = d.id
        WHERE s.user_id = ?
    ");
    $stUserStmt->execute([$_SESSION['user_id']]);
    $student = $stUserStmt->fetch();
    if ($student) {
        $studentId = (int)$student['id'];
        $_SESSION['role_id'] = $studentId;
    }
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $new_pass = $_POST['new_password'] ?? '';

        // Update phone & email
        $updSt = $db->prepare("UPDATE students SET phone = ? WHERE id = ?");
        $updSt->execute([$phone, $studentId]);

        $updUsr = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
        $updUsr->execute([$email, $student['user_id']]);

        // If changing password
        if (!empty($new_pass)) {
            if (strlen($new_pass) >= 6) {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $updP = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updP->execute([$hashed, $student['user_id']]);
            } else {
                setFlashMessage('danger', 'Password must be at least 6 characters.');
                redirect('student/profile.php');
            }
        }

        setFlashMessage('success', 'Profile details updated successfully.');
        redirect('student/profile.php');
    }
}

$pageTitle = "Student Profile";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex" id="wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="page-content-wrapper" class="w-100">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 font-weight-bold text-dark mb-0">My Student Profile</h1>
                    <p class="text-muted small">Manage your contact information & account security</p>
                </div>
            </div>

            <?php displayAlerts(); ?>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm text-center p-4" style="border-radius: 16px;">
                        <div class="rounded-circle bg-primary-lightest text-primary mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 90px; height: 90px; font-size: 36px;">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h4 class="h5 font-weight-bold text-dark mb-1"><?= htmlspecialchars(trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''))) ?></h4>
                        <p class="badge bg-primary-light text-primary font-weight-600 px-3 py-1 mb-3">
                            <?= htmlspecialchars($student['register_number'] ?? 'N/A') ?>
                        </p>
                        <hr class="my-3">
                        <div class="text-start small">
                            <div class="mb-2"><strong>Department:</strong> <?= htmlspecialchars($student['dept_name'] ?? 'General') ?> (<?= htmlspecialchars($student['dept_code'] ?? 'GEN') ?>)</div>
                            <div class="mb-2"><strong>Semester:</strong> <?= htmlspecialchars($student['semester'] ?? '1') ?> | Section <?= htmlspecialchars($student['section'] ?? 'A') ?></div>
                            <div class="mb-2"><strong>Academic Year:</strong> <?= htmlspecialchars($student['academic_year'] ?? 'Current') ?></div>
                            <div class="mb-2"><strong>Gender:</strong> <?= !empty($student['gender']) ? ucfirst(htmlspecialchars((string)$student['gender'])) : 'Not Specified' ?></div>
                            <div class="mb-2"><strong>Admission Date:</strong> <?= !empty($student['admission_date']) ? date('d M Y', strtotime($student['admission_date'])) : 'N/A' ?></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
                        <h5 class="h6 font-weight-bold text-dark mb-4"><i class="fas fa-edit text-primary me-2"></i> Update Contact Details & Password</h5>
                        
                        <form action="" method="POST">
                            <?= csrfField() ?>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label font-weight-500">First Name (Read-only)</label>
                                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($student['first_name']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label font-weight-500">Last Name (Read-only)</label>
                                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($student['last_name']) ?>" readonly>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label font-weight-500">Username (Read-only)</label>
                                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($student['username']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label font-weight-500">Register Number (Read-only)</label>
                                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($student['register_number']) ?>" readonly>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label font-weight-500">Email Address</label>
                                    <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars((string)($student['email'] ?? '')) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label font-weight-500">Phone Number</label>
                                    <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars((string)($student['phone'] ?? '')) ?>">
                                </div>
                            </div>

                            <hr class="my-4">

                            <h6 class="h6 font-weight-bold text-dark mb-3"><i class="fas fa-lock text-warning me-2"></i> Security (Optional)</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-12">
                                    <label for="new_password" class="form-label font-weight-500">New Password (leave blank to keep current)</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" placeholder="••••••••">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary rounded-pill px-4 font-weight-600">
                                Save Profile Changes <i class="fas fa-save ms-2"></i>
                            </button>
                        </form>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
