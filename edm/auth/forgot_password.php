<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $email = sanitizeInput($_POST['email'] ?? '');
        if (!empty($email)) {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, username FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $upd = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
                $upd->execute([$token, $expires, $user['id']]);

                // Simulation link for dev/testing environment
                $resetLink = APP_URL . "/auth/reset_password.php?token=" . $token;
                $message = "Password reset instructions have been generated. Click here to reset: <a href='{$resetLink}' class='alert-link'>Reset Password Now</a>";
                $messageType = "success";
            } else {
                $message = "If an account with that email exists, password reset instructions have been sent.";
                $messageType = "info";
            }
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
    <title>Forgot Password | EDM System</title>
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
            <div class="rounded-circle bg-primary-lightest text-primary d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; font-size: 28px;">
                <i class="fas fa-key"></i>
            </div>
            <h2 class="h4 font-weight-bold text-dark">Forgot Password?</h2>
            <p class="text-muted small">Enter your registered email address to reset your password.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> small">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <?= csrfField() ?>
            <div class="form-group mb-4">
                <label for="email" class="form-label text-dark font-weight-500">Registered Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                    <input type="email" id="email" name="email" class="form-control" placeholder="user@edms.edu" required autofocus>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block rounded-pill py-2.5 font-weight-600 w-100 mb-3">
                Send Reset Request <i class="fas fa-paper-plane ms-2"></i>
            </button>
        </form>

        <div class="text-center pt-3 border-top">
            <a href="<?= APP_URL ?>/index.php" class="text-muted small">
                <i class="fas fa-arrow-left me-1"></i> Back to Main Portal
            </a>
        </div>
    </div>

    <script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
