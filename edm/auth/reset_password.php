<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';

$token = sanitizeInput($_GET['token'] ?? '');
$validToken = false;
$user = null;
$message = '';
$messageType = '';

$db = getDB();

if (!empty($token)) {
    $stmt = $db->prepare("SELECT id, username, role FROM users WHERE reset_token = ? AND reset_expires > NOW() AND status = 'active'");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if ($user) {
        $validToken = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (strlen($new_password) < 6) {
            $message = "Password must be at least 6 characters long.";
            $messageType = "danger";
        } elseif ($new_password !== $confirm_password) {
            $message = "Passwords do not match.";
            $messageType = "danger";
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $upd = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            $upd->execute([$hashed, $user['id']]);

            logAudit($user['id'], 'PASSWORD_RESET', 'User reset password via token');

            setFlashMessage('success', 'Your password has been reset successfully! You can now log in.');
            redirect("auth/{$user['role']}_login.php");
        }
    } else {
        $message = "Invalid security token.";
        $messageType = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password | EDM System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card-reset {
            background: #ffffff;
            border-radius: 20px;
            width: 100%;
            max-width: 440px;
            padding: 40px 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>

    <div class="card-reset">
        <div class="text-center mb-4">
            <h2 class="h4 font-weight-bold text-dark">Set New Password</h2>
            <p class="text-muted small">Choose a strong new password for your account.</p>
        </div>

        <?php if (!$validToken): ?>
            <div class="alert alert-danger text-center mb-4">
                <i class="fas fa-exclamation-triangle display-6 mb-2 d-block"></i>
                Invalid or expired password reset link.
            </div>
            <div class="text-center">
                <a href="<?= APP_URL ?>/auth/forgot_password.php" class="btn btn-primary rounded-pill px-4">Request New Token</a>
            </div>
        <?php else: ?>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> small">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <?= csrfField() ?>
                <div class="form-group mb-3">
                    <label for="new_password" class="form-label text-dark font-weight-500">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Minimum 6 characters" required>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <label for="confirm_password" class="form-label text-dark font-weight-500">Confirm New Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-check text-muted"></i></span>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-success btn-block rounded-pill py-2.5 font-weight-600 w-100 mb-3">
                    Update Password <i class="fas fa-save ms-2"></i>
                </button>
            </form>
        <?php endif; ?>

        <div class="text-center pt-3 border-top mt-3">
            <a href="<?= APP_URL ?>/index.php" class="text-muted small">
                <i class="fas fa-home me-1"></i> Back to Main Portal
            </a>
        </div>
    </div>

    <script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
