<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Login | Engineering Department Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #78350F 0%, #0F172A 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: #ffffff;
            border-radius: 20px;
            width: 100%;
            max-width: 440px;
            padding: 40px 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
            position: relative;
        }
        .role-badge-admin {
            background: #FEF3C7;
            color: #92400E;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="text-center mb-4">
            <span class="role-badge-admin mb-3">
                <i class="fas fa-user-shield"></i> System Administration
            </span>
            <h2 class="h3 font-weight-bold text-dark">Admin Sign In</h2>
            <p class="text-muted small">System configuration & department oversight</p>
        </div>

        <?php displayAlerts(); ?>

        <!-- Demo credentials helper box with 1-click button -->
        <div class="alert alert-warning text-dark py-2 px-3 small mb-4 d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-info-circle me-1"></i> Demo: <strong>admin</strong> / <strong>admin123</strong>
            </div>
            <button type="button" class="btn btn-sm btn-outline-dark py-1 px-2.5 rounded-pill" onclick="fillAdminDemo()">
                <i class="fas fa-bolt me-1"></i> 1-Click Demo
            </button>
        </div>

        <form action="<?= APP_URL ?>/auth/process_login.php" method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="expected_role" value="admin">

            <div class="form-group mb-3">
                <label for="username" class="form-label text-dark font-weight-500">Admin Username or Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user-shield text-muted"></i></span>
                    <input type="text" id="username" name="username" class="form-control" placeholder="admin or admin@edms.edu" required autofocus>
                </div>
            </div>

            <div class="form-group mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label text-dark font-weight-500 mb-0">Password</label>
                    <a href="<?= APP_URL ?>/auth/forgot_password.php?role=admin" class="text-warning small text-dark font-weight-600">Forgot?</a>
                </div>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                <label class="form-check-label text-muted small" for="remember">Keep me logged in</label>
            </div>

            <button type="submit" class="btn btn-warning text-dark btn-block rounded-pill py-2.5 font-weight-600 shadow-sm w-100">
                Log In to Admin Console <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </form>

        <div class="mt-4 pt-3 text-center border-top">
            <a href="<?= APP_URL ?>/auth/login.php?role=admin" class="text-warning text-dark small d-block mb-2 font-weight-600">
                <i class="fas fa-layer-group me-1"></i> Switch to Unified 3-in-1 Login Portal
            </a>
            <a href="<?= APP_URL ?>/index.php" class="text-muted small">
                <i class="fas fa-home me-1"></i> Back to Department Introduction
            </a>
        </div>
    </div>

    <script>
        function fillAdminDemo() {
            document.getElementById('username').value = 'admin';
            document.getElementById('password').value = 'admin123';
            document.querySelector('form').submit();
        }
    </script>
    <script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
