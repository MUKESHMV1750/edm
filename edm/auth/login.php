<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';

// If already logged in, redirect to respective dashboard
if (isLoggedIn()) {
    switch ($_SESSION['user_role']) {
        case ROLE_STUDENT:
            redirect('student/dashboard.php');
            break;
        case ROLE_STAFF:
            redirect('staff/dashboard.php');
            break;
        case ROLE_ADMIN:
            redirect('admin/dashboard.php');
            break;
        default:
            redirect('index.php');
    }
}

// Get initial role from query parameter if present, default to student
$initialRole = sanitizeInput($_GET['role'] ?? 'student');
if (!in_array($initialRole, ['student', 'staff', 'admin'])) {
    $initialRole = 'student';
}

// Check if demo auto-fill or auto-login requested via URL
$demoParam = sanitizeInput($_GET['demo'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        (function() {
            try {
                const savedTheme = localStorage.getItem('edm_theme');
                const systemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (savedTheme === 'dark' || (!savedTheme && systemDark)) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    document.documentElement.classList.add('dark-mode');
                } else {
                    document.documentElement.setAttribute('data-theme', 'light');
                    document.documentElement.classList.remove('dark-mode');
                }
            } catch (e) {}
        })();
    </script>
    <title>Unified Login Portal | Engineering Department Management System</title>
    <!-- Google Fonts & FontAwesome -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Main Style -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <style>
        :root {
            --student-color: #2563EB;
            --student-bg: #EFF6FF;
            --staff-color: #059669;
            --staff-bg: #ECFDF5;
            --admin-color: #D97706;
            --admin-bg: #FFFBEB;
        }

        body {
            background: linear-gradient(135deg, #0F172A 0%, #1E3A8A 50%, #0F172A 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
            position: relative;
            font-family: 'Poppins', sans-serif;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 20%, rgba(37, 99, 235, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(16, 185, 129, 0.12) 0%, transparent 50%);
            pointer-events: none;
        }

        .auth-container {
            width: 100%;
            max-width: 520px;
            position: relative;
            z-index: 2;
        }

        .auth-brand {
            text-align: center;
            margin-bottom: 25px;
        }

        .auth-brand-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: #ffffff;
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
            letter-spacing: -0.5px;
        }

        .auth-brand-logo i {
            font-size: 2rem;
            color: #60A5FA;
        }

        .auth-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 38px 34px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        /* Top Theme Indicator Bar */
        .card-top-bar {
            height: 6px;
            border-radius: 10px;
            margin: -38px -34px 28px -34px;
            background: var(--student-color);
            transition: background 0.3s ease;
        }

        /* Role Switcher Tabs (3 Login Options) */
        .role-switch-container {
            background: #F1F5F9;
            padding: 6px;
            border-radius: 16px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            margin-bottom: 24px;
        }

        .role-tab-btn {
            border: none;
            background: transparent;
            padding: 10px 6px;
            border-radius: 12px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #64748B;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            text-align: center;
        }

        .role-tab-btn i {
            font-size: 1.15rem;
            transition: transform 0.2s ease;
        }

        .role-tab-btn:hover {
            color: #0F172A;
            background: rgba(255, 255, 255, 0.6);
        }

        /* Active Tab States */
        .role-tab-btn.active[data-role="student"] {
            background: #ffffff;
            color: var(--student-color);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.18);
        }

        .role-tab-btn.active[data-role="staff"] {
            background: #ffffff;
            color: var(--staff-color);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.18);
        }

        .role-tab-btn.active[data-role="admin"] {
            background: #ffffff;
            color: var(--admin-color);
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.18);
        }

        /* Role Badge Indicator */
        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 600;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }

        .role-badge.role-student {
            background: var(--student-bg);
            color: var(--student-color);
        }

        .role-badge.role-staff {
            background: var(--staff-bg);
            color: var(--staff-color);
        }

        .role-badge.role-admin {
            background: var(--admin-bg);
            color: var(--admin-color);
        }

        /* Form Inputs */
        .form-label {
            font-size: 0.88rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: stretch;
            width: 100%;
        }

        .input-group-text {
            background: #F8FAFC;
            border: 1px solid #CBD5E1;
            border-right: none;
            border-radius: 12px 0 0 12px;
            color: #64748B;
            padding: 0 16px;
            display: flex;
            align-items: center;
        }

        .form-control {
            border: 1px solid #CBD5E1;
            border-radius: 0 12px 12px 0;
            padding: 12px 16px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            width: 100%;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--student-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .password-toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #94A3B8;
            cursor: pointer;
            padding: 4px 8px;
            z-index: 5;
            transition: color 0.2s ease;
        }

        .password-toggle-btn:hover {
            color: #334155;
        }

        /* Dynamic Submit Button */
        .btn-submit-login {
            background: var(--student-color);
            color: #ffffff;
            border: none;
            border-radius: 50px;
            padding: 14px 24px;
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
        }

        .btn-submit-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(37, 99, 235, 0.35);
            color: #ffffff;
        }

        .btn-submit-login.btn-staff {
            background: var(--staff-color);
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.25);
        }
        .btn-submit-login.btn-staff:hover {
            box-shadow: 0 12px 25px rgba(5, 150, 105, 0.35);
        }

        .btn-submit-login.btn-admin {
            background: var(--admin-color);
            box-shadow: 0 8px 20px rgba(217, 119, 6, 0.25);
        }
        .btn-submit-login.btn-admin:hover {
            box-shadow: 0 12px 25px rgba(217, 119, 6, 0.35);
        }

        /* Demo Login Section */
        .demo-section {
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px dashed #E2E8F0;
        }

        .demo-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .demo-title span {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748B;
        }

        .demo-buttons-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .demo-btn {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 10px 6px;
            font-size: 0.78rem;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            text-align: center;
            width: 100%;
        }

        .demo-btn i {
            font-size: 1.1rem;
        }

        .demo-btn-student {
            border-color: #BFDBFE;
            color: #1E40AF;
        }
        .demo-btn-student:hover {
            background: #DBEAFE;
            transform: translateY(-2px);
        }

        .demo-btn-staff {
            border-color: #A7F3D0;
            color: #065F46;
        }
        .demo-btn-staff:hover {
            background: #D1FAE5;
            transform: translateY(-2px);
        }

        .demo-btn-admin {
            border-color: #FDE68A;
            color: #92400E;
        }
        .demo-btn-admin:hover {
            background: #FEF3C7;
            transform: translateY(-2px);
        }

        .demo-help-sub {
            font-size: 0.72rem;
            color: #64748B;
            margin-top: 8px;
            text-align: center;
        }

        /* Navigation back to Index */
        .auth-footer {
            margin-top: 20px;
            text-align: center;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #94A3B8;
            font-size: 0.88rem;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: #ffffff;
        }

        /* Pulse animation for 1-click demo actions */
        @keyframes subtlePulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        .filling-active {
            animation: subtlePulse 0.4s ease-in-out;
        }

        /* Top Demo Banner above Form */
        .demo-card-banner {
            border-radius: 14px;
            padding: 12px 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .demo-card-banner.banner-student {
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
        }

        .demo-card-banner.banner-staff {
            background: #ECFDF5;
            border: 1px solid #A7F3D0;
        }

        .demo-card-banner.banner-admin {
            background: #FFFBEB;
            border: 1px solid #FDE68A;
        }

        .demo-banner-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }

        .demo-banner-title {
            font-size: 0.82rem;
            font-weight: 700;
            color: #1E293B;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .demo-banner-creds {
            font-size: 0.74rem;
            color: #64748B;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .demo-banner-creds code {
            background: rgba(0, 0, 0, 0.06);
            padding: 1px 6px;
            border-radius: 4px;
            font-weight: 600;
            color: #0F172A;
            font-size: 0.76rem;
        }

        .demo-banner-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        .btn-banner-login {
            background: var(--student-color);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 7px 12px;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s ease;
            white-space: nowrap;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.2);
        }

        .btn-banner-login:hover {
            opacity: 0.93;
            transform: translateY(-1px);
            color: #ffffff;
        }

        .btn-banner-fill {
            background: transparent;
            border: 1px solid #CBD5E1;
            color: #475569;
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-banner-fill:hover {
            background: rgba(0, 0, 0, 0.05);
            color: #0F172A;
        }

        /* Divider between regular submit and demo button */
        .demo-or-divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 16px 0 12px 0;
            color: #94A3B8;
            font-size: 0.74rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .demo-or-divider::before,
        .demo-or-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #E2E8F0;
        }

        .demo-or-divider span {
            padding: 0 10px;
        }

        /* Secondary Prominent Demo Button under Primary Submit */
        .btn-demo-prominent {
            width: 100%;
            background: #F8FAFC;
            border: 1.5px dashed #2563EB;
            color: #1E40AF;
            border-radius: 50px;
            padding: 11px 20px;
            font-size: 0.92rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-demo-prominent:hover {
            background: #EFF6FF;
            border-style: solid;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.16);
            color: #1D4ED8;
        }

        .btn-demo-prominent.prominent-staff {
            border-color: #059669;
            color: #065F46;
        }
        .btn-demo-prominent.prominent-staff:hover {
            background: #ECFDF5;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.16);
        }

        .btn-demo-prominent.prominent-admin {
            border-color: #D97706;
            color: #92400E;
        }
        .btn-demo-prominent.prominent-admin:hover {
            background: #FFFBEB;
            box-shadow: 0 4px 14px rgba(217, 119, 6, 0.16);
        }

        .btn-demo-prominent .demo-pill-tag {
            background: rgba(37, 99, 235, 0.12);
            color: #1E40AF;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 10px;
            border-radius: 20px;
            margin-left: auto;
        }

        .btn-demo-prominent.prominent-staff .demo-pill-tag {
            background: rgba(5, 150, 105, 0.12);
            color: #065F46;
        }

        .btn-demo-prominent.prominent-admin .demo-pill-tag {
            background: rgba(217, 119, 6, 0.12);
            color: #92400E;
        }

        /* Active highlight for bottom grid buttons */
        .demo-btn.active-role-card {
            border-width: 2px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            position: relative;
        }
        .demo-btn-student.active-role-card {
            border-color: #2563EB !important;
            background: #EFF6FF;
        }
        .demo-btn-staff.active-role-card {
            border-color: #059669 !important;
            background: #ECFDF5;
        }
        .demo-btn-admin.active-role-card {
            border-color: #D97706 !important;
            background: #FFFBEB;
        }

        /* Toast notification */
        .demo-toast {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #0F172A;
            color: #FFFFFF;
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            z-index: 9999;
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            pointer-events: none;
        }

        .demo-toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        /* Dark mode overrides for Login Portal */
        [data-theme="dark"] .auth-card,
        body.dark-mode .auth-card {
            background: #151F32 !important;
            border: 1px solid #24324D !important;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6) !important;
        }
        [data-theme="dark"] .role-switch-container,
        body.dark-mode .role-switch-container {
            background: #0B1120 !important;
        }
        [data-theme="dark"] .role-tab-btn,
        body.dark-mode .role-tab-btn {
            color: #94A3B8 !important;
        }
        [data-theme="dark"] .role-tab-btn:hover,
        body.dark-mode .role-tab-btn:hover {
            color: #F8FAFC !important;
            background: rgba(255, 255, 255, 0.08) !important;
        }
        [data-theme="dark"] .role-tab-btn.active[data-role="student"],
        body.dark-mode .role-tab-btn.active[data-role="student"] {
            background: #1E293B !important;
            color: #60A5FA !important;
        }
        [data-theme="dark"] .role-tab-btn.active[data-role="staff"],
        body.dark-mode .role-tab-btn.active[data-role="staff"] {
            background: #1E293B !important;
            color: #34D399 !important;
        }
        [data-theme="dark"] .role-tab-btn.active[data-role="admin"],
        body.dark-mode .role-tab-btn.active[data-role="admin"] {
            background: #1E293B !important;
            color: #FBBF24 !important;
        }
        [data-theme="dark"] .form-control,
        body.dark-mode .form-control {
            background: #0B1120 !important;
            border-color: #24324D !important;
            color: #F8FAFC !important;
        }
        [data-theme="dark"] .input-group-text,
        body.dark-mode .input-group-text {
            background: #1E293B !important;
            border-color: #24324D !important;
            color: #94A3B8 !important;
        }
        [data-theme="dark"] .demo-btn,
        body.dark-mode .demo-btn {
            background: #0B1120 !important;
            border-color: #24324D !important;
            color: #CBD5E1 !important;
        }
        [data-theme="dark"] .demo-section,
        body.dark-mode .demo-section {
            border-top-color: #24324D !important;
        }
        [data-theme="dark"] .text-dark,
        body.dark-mode .text-dark {
            color: #F8FAFC !important;
        }
        [data-theme="dark"] .demo-card-banner.banner-student,
        body.dark-mode .demo-card-banner.banner-student {
            background: rgba(37, 99, 235, 0.15) !important;
            border-color: rgba(37, 99, 235, 0.35) !important;
        }
        [data-theme="dark"] .demo-card-banner.banner-staff,
        body.dark-mode .demo-card-banner.banner-staff {
            background: rgba(5, 150, 105, 0.15) !important;
            border-color: rgba(5, 150, 105, 0.35) !important;
        }
        [data-theme="dark"] .demo-card-banner.banner-admin,
        body.dark-mode .demo-card-banner.banner-admin {
            background: rgba(217, 119, 6, 0.15) !important;
            border-color: rgba(217, 119, 6, 0.35) !important;
        }
        [data-theme="dark"] .demo-banner-title,
        body.dark-mode .demo-banner-title {
            color: #F8FAFC !important;
        }
        [data-theme="dark"] .demo-banner-creds,
        body.dark-mode .demo-banner-creds {
            color: #94A3B8 !important;
        }
        [data-theme="dark"] .demo-banner-creds code,
        body.dark-mode .demo-banner-creds code {
            background: rgba(255, 255, 255, 0.12) !important;
            color: #93C5FD !important;
        }
        [data-theme="dark"] .btn-banner-fill,
        body.dark-mode .btn-banner-fill {
            border-color: #334155 !important;
            color: #CBD5E1 !important;
        }
        [data-theme="dark"] .btn-banner-fill:hover,
        body.dark-mode .btn-banner-fill:hover {
            background: rgba(255, 255, 255, 0.1) !important;
            color: #FFFFFF !important;
        }
        [data-theme="dark"] .demo-or-divider,
        body.dark-mode .demo-or-divider {
            color: #64748B !important;
        }
        [data-theme="dark"] .demo-or-divider::before,
        [data-theme="dark"] .demo-or-divider::after,
        body.dark-mode .demo-or-divider::before,
        body.dark-mode .demo-or-divider::after {
            border-bottom-color: #24324D !important;
        }
        [data-theme="dark"] .btn-demo-prominent,
        body.dark-mode .btn-demo-prominent {
            background: #0B1120 !important;
            color: #60A5FA !important;
        }
        [data-theme="dark"] .btn-demo-prominent:hover,
        body.dark-mode .btn-demo-prominent:hover {
            background: #172554 !important;
        }
        [data-theme="dark"] .btn-demo-prominent.prominent-staff,
        body.dark-mode .btn-demo-prominent.prominent-staff {
            color: #34D399 !important;
        }
        [data-theme="dark"] .btn-demo-prominent.prominent-staff:hover,
        body.dark-mode .btn-demo-prominent.prominent-staff:hover {
            background: #064E3B !important;
        }
        [data-theme="dark"] .btn-demo-prominent.prominent-admin,
        body.dark-mode .btn-demo-prominent.prominent-admin {
            color: #FBBF24 !important;
        }
        [data-theme="dark"] .btn-demo-prominent.prominent-admin:hover,
        body.dark-mode .btn-demo-prominent.prominent-admin:hover {
            background: #78350F !important;
        }
        [data-theme="dark"] .btn-demo-prominent .demo-pill-tag,
        body.dark-mode .btn-demo-prominent .demo-pill-tag {
            background: rgba(96, 165, 250, 0.2) !important;
            color: #93C5FD !important;
        }
        [data-theme="dark"] .btn-demo-prominent.prominent-staff .demo-pill-tag,
        body.dark-mode .btn-demo-prominent.prominent-staff .demo-pill-tag {
            background: rgba(52, 211, 153, 0.2) !important;
            color: #6EE7B7 !important;
        }
        [data-theme="dark"] .btn-demo-prominent.prominent-admin .demo-pill-tag,
        body.dark-mode .btn-demo-prominent.prominent-admin .demo-pill-tag {
            background: rgba(251, 191, 36, 0.2) !important;
            color: #FDE68A !important;
        }
        [data-theme="dark"] .demo-btn-student.active-role-card,
        body.dark-mode .demo-btn-student.active-role-card {
            background: #172554 !important;
            border-color: #3B82F6 !important;
        }
        [data-theme="dark"] .demo-btn-staff.active-role-card,
        body.dark-mode .demo-btn-staff.active-role-card {
            background: #064E3B !important;
            border-color: #10B981 !important;
        }
        [data-theme="dark"] .demo-btn-admin.active-role-card,
        body.dark-mode .demo-btn-admin.active-role-card {
            background: #78350F !important;
            border-color: #F59E0B !important;
        }
    </style>
</head>
<body>

    <!-- Dark / Light Mode Floating Toggle -->
    <div style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
        <button class="btn btn-light rounded-circle shadow theme-toggle-btn" id="themeToggleBtn" title="Toggle Theme" aria-label="Toggle dark/light mode" style="width: 44px; height: 44px; display: inline-flex; align-items: center; justify-content: center; backdrop-filter: blur(8px);">
            <i class="fas fa-moon"></i>
        </button>
    </div>

    <div class="auth-container">
        <!-- Brand Header -->
        <div class="auth-brand">
            <a href="<?= APP_URL ?>/index.php" class="auth-brand-logo">
                <i class="fas fa-graduation-cap"></i>
                <span>EDM Portal</span>
            </a>
            <p class="text-light opacity-75 small mb-0 mt-1">Engineering Department Management System</p>
        </div>

        <!-- Main Auth Card -->
        <div class="auth-card">
            <!-- Dynamic Top Color Accent Bar -->
            <div class="card-top-bar" id="cardTopBar"></div>

            <!-- Header with Dynamic Badge -->
            <div class="text-center mb-3">
                <span class="role-badge role-student" id="roleBadge">
                    <i class="fas fa-user-graduate" id="roleBadgeIcon"></i>
                    <span id="roleBadgeText">Student Portal Access</span>
                </span>
                <h1 class="h4 font-weight-bold text-dark mb-1" id="formHeading">Welcome Back</h1>
                <p class="text-muted small" id="formSubheading">Choose your role or select an instant demo account below</p>
            </div>

            <!-- Role Selector: 3 Login Options (Single Form Switcher) -->
            <div class="role-switch-container" role="tablist" aria-label="Select Login Role">
                <button type="button" 
                        class="role-tab-btn <?= $initialRole === 'student' ? 'active' : '' ?>" 
                        data-role="student" 
                        id="tabStudent"
                        onclick="switchLoginRole('student')">
                    <i class="fas fa-user-graduate"></i>
                    <span>Student</span>
                </button>
                <button type="button" 
                        class="role-tab-btn <?= $initialRole === 'staff' ? 'active' : '' ?>" 
                        data-role="staff" 
                        id="tabStaff"
                        onclick="switchLoginRole('staff')">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Faculty</span>
                </button>
                <button type="button" 
                        class="role-tab-btn <?= $initialRole === 'admin' ? 'active' : '' ?>" 
                        data-role="admin" 
                        id="tabAdmin"
                        onclick="switchLoginRole('admin')">
                    <i class="fas fa-user-shield"></i>
                    <span>Admin</span>
                </button>
            </div>

            <!-- Flash Error/Success Messages -->
            <?php displayAlerts(); ?>

            <!-- ⚡ Quick 1-Click Demo Banner for Selected Role -->
            <div class="demo-card-banner banner-student" id="demoCardBanner">
                <div class="demo-banner-info">
                    <div class="demo-banner-title" id="demoBannerTitle">
                        <i class="fas fa-bolt text-warning"></i>
                        <span id="demoBannerTitleText">Demo Student Account</span>
                        <span class="badge bg-primary text-white ms-1" id="demoBannerBadge">Instant</span>
                    </div>
                    <div class="demo-banner-creds" id="demoBannerCreds">
                        Username: <code>john.d</code> (or <code>REG2024001</code>) &bull; Pass: <code>student123</code>
                    </div>
                </div>
                <div class="demo-banner-actions">
                    <button type="button" 
                            class="btn-banner-fill" 
                            id="demoBannerFillBtn" 
                            onclick="fillCurrentDemoCredentials()"
                            title="Auto-fill credentials into form fields without auto-login">
                        <i class="fas fa-pen-fancy"></i>
                        <span>Auto-Fill</span>
                    </button>
                    <button type="button" 
                            class="btn-banner-login" 
                            id="demoBannerLoginBtn" 
                            onclick="quickRoleDemoLogin()"
                            title="Instant 1-click sign in">
                        <i class="fas fa-bolt"></i>
                        <span>1-Click Log In</span>
                    </button>
                </div>
            </div>

            <!-- The Single Unified Login Form -->
            <form id="unifiedLoginForm" action="<?= APP_URL ?>/auth/process_login.php" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="expected_role" id="expectedRoleInput" value="<?= htmlspecialchars($initialRole) ?>">
                <input type="hidden" name="source" value="unified">

                <!-- Dynamic Username Input Field -->
                <div class="form-group mb-3">
                    <label for="usernameInput" class="form-label" id="usernameLabel">
                        Username or Register No.
                    </label>
                    <div class="input-group">
                        <span class="input-group-text" id="usernameIconSpan">
                            <i class="fas fa-id-card" id="usernameFieldIcon"></i>
                        </span>
                        <input type="text" 
                               id="usernameInput" 
                               name="username" 
                               class="form-control" 
                               placeholder="john.d or REG2024001" 
                               required 
                               autocomplete="username"
                               autofocus>
                    </div>
                </div>

                <!-- Password Field with Show/Hide Toggle -->
                <div class="form-group mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="passwordInput" class="form-label mb-0">Password</label>
                        <a href="<?= APP_URL ?>/auth/forgot_password.php" class="text-primary small" id="forgotPassLink">
                            Forgot?
                        </a>
                    </div>
                    <div class="input-group position-relative">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" 
                               id="passwordInput" 
                               name="password" 
                               class="form-control pe-5" 
                               placeholder="••••••••" 
                               required 
                               autocomplete="current-password">
                        <button type="button" 
                                class="password-toggle-btn" 
                                id="togglePasswordBtn" 
                                onclick="togglePasswordVisibility()" 
                                title="Show/Hide Password"
                                aria-label="Toggle password visibility">
                            <i class="fas fa-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me Checkbox -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
                        <label class="form-check-label text-muted small" for="rememberMe">
                            Remember this device
                        </label>
                    </div>
                    <span class="badge bg-light text-muted border small py-1 px-2">
                        <i class="fas fa-shield-alt text-success me-1"></i> SSL 256-Bit
                    </span>
                </div>

                <!-- Unified Submit Button -->
                <button type="submit" class="btn-submit-login" id="submitLoginBtn">
                    <span id="submitBtnText">Sign In to Student Portal</span>
                    <i class="fas fa-arrow-right"></i>
                </button>

                <!-- Divider -->
                <div class="demo-or-divider">
                    <span>or 1-click demo access</span>
                </div>

                <!-- Instant Demo Button directly under Sign In -->
                <button type="button" 
                        class="btn-demo-prominent prominent-student" 
                        id="demoProminentBtn" 
                        onclick="quickRoleDemoLogin()"
                        title="Click to instantly sign in as demo user">
                    <i class="fas fa-bolt text-warning"></i>
                    <span id="demoProminentBtnText">Log In as Demo Student</span>
                    <span class="demo-pill-tag" id="demoProminentBadge">john.d</span>
                </button>
            </form>

            <!-- ⚡ Demo Log In Section (Added as requested) -->
            <div class="demo-section">
                <div class="demo-title">
                    <span><i class="fas fa-bolt text-warning me-1"></i> Quick 1-Click Demo Login</span>
                    <span class="badge bg-primary-subtle text-primary small">Instant Sign-In</span>
                </div>

                <div class="demo-buttons-grid">
                    <!-- Demo Student Button -->
                    <button type="button" 
                            class="demo-btn demo-btn-student <?= $initialRole === 'student' ? 'active-role-card' : '' ?>" 
                            id="demoStudentBtn"
                            onclick="loginAsDemo('student', 'john.d', 'student123')"
                            title="Auto-fill and log in as Demo Student">
                        <i class="fas fa-user-graduate"></i>
                        <span>Demo Student</span>
                        <small class="text-muted" style="font-size: 0.68rem;">john.d</small>
                    </button>

                    <!-- Demo Faculty Button -->
                    <button type="button" 
                            class="demo-btn demo-btn-staff <?= $initialRole === 'staff' ? 'active-role-card' : '' ?>" 
                            id="demoStaffBtn"
                            onclick="loginAsDemo('staff', 'rajesh.s', 'staff123')"
                            title="Auto-fill and log in as Demo Faculty">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Demo Faculty</span>
                        <small class="text-muted" style="font-size: 0.68rem;">rajesh.s</small>
                    </button>

                    <!-- Demo Admin Button -->
                    <button type="button" 
                            class="demo-btn demo-btn-admin <?= $initialRole === 'admin' ? 'active-role-card' : '' ?>" 
                            id="demoAdminBtn"
                            onclick="loginAsDemo('admin', 'admin', 'admin123')"
                            title="Auto-fill and log in as Demo Admin">
                        <i class="fas fa-user-shield"></i>
                        <span>Demo Admin</span>
                        <small class="text-muted" style="font-size: 0.68rem;">admin</small>
                    </button>
                </div>

                <div class="demo-help-sub">
                    <i class="fas fa-check-circle text-success me-1"></i> Pre-configured demo accounts ready to explore all portal features with 1-click.
                </div>
            </div>

        </div>

        <!-- Back to Index / Introduction Page -->
        <div class="auth-footer">
            <a href="<?= APP_URL ?>/index.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Department Introduction</span>
            </a>
        </div>
    </div>

    <!-- Floating Toast Notification for Auto-Fill -->
    <div id="demoToast" class="demo-toast">
        <i class="fas fa-check-circle text-success"></i>
        <span id="demoToastText">Demo credentials filled!</span>
    </div>

    <!-- Interactive Role & Demo JavaScript -->
    <script>
        // Configuration for the 3 roles
        const roleConfig = {
            student: {
                role: 'student',
                title: 'Student Portal Access',
                icon: 'fas fa-user-graduate',
                color: '#2563EB',
                bgClass: 'role-student',
                btnClass: '',
                label: 'Student Username or Register No.',
                placeholder: 'john.d or REG2024001',
                fieldIcon: 'fas fa-id-card',
                btnText: 'Sign In to Student Portal',
                forgotUrl: '<?= APP_URL ?>/auth/forgot_password.php?role=student'
            },
            staff: {
                role: 'staff',
                title: 'Faculty & Staff Portal',
                icon: 'fas fa-chalkboard-teacher',
                color: '#059669',
                bgClass: 'role-staff',
                btnClass: 'btn-staff',
                label: 'Staff Code or Username',
                placeholder: 'rajesh.s or STF001',
                fieldIcon: 'fas fa-id-badge',
                btnText: 'Sign In to Faculty Portal',
                forgotUrl: '<?= APP_URL ?>/auth/forgot_password.php?role=staff'
            },
            admin: {
                role: 'admin',
                title: 'Administrator Console',
                icon: 'fas fa-user-shield',
                color: '#D97706',
                bgClass: 'role-admin',
                btnClass: 'btn-admin',
                label: 'Admin Username or Email',
                placeholder: 'admin or admin@edms.edu',
                fieldIcon: 'fas fa-user-shield',
                btnText: 'Sign In to Admin Console',
                forgotUrl: '<?= APP_URL ?>/auth/forgot_password.php?role=admin'
            }
        };

        // Demo accounts configuration
        const demoRoleData = {
            student: {
                role: 'student',
                title: 'Demo Student Account',
                username: 'john.d',
                altUsername: 'REG2024001',
                password: 'student123',
                bannerClass: 'banner-student',
                prominentClass: 'prominent-student',
                btnText: 'Log In as Demo Student',
                badgeText: 'john.d (Student)',
                btnClass: 'demo-btn-student'
            },
            staff: {
                role: 'staff',
                title: 'Demo Faculty Account',
                username: 'rajesh.s',
                altUsername: 'STF001',
                password: 'staff123',
                bannerClass: 'banner-staff',
                prominentClass: 'prominent-staff',
                btnText: 'Log In as Demo Faculty',
                badgeText: 'rajesh.s (Faculty)',
                btnClass: 'demo-btn-staff'
            },
            admin: {
                role: 'admin',
                title: 'Demo Admin Console',
                username: 'admin',
                altUsername: 'admin@edms.edu',
                password: 'admin123',
                bannerClass: 'banner-admin',
                prominentClass: 'prominent-admin',
                btnText: 'Log In as Demo Admin',
                badgeText: 'admin (Admin)',
                btnClass: 'demo-btn-admin'
            }
        };

        let currentRole = '<?= $initialRole ?>';
        let toastTimeout = null;

        // Switch between the 3 Login Options
        function switchLoginRole(role) {
            if (!roleConfig[role]) role = 'student';
            currentRole = role;
            const config = roleConfig[role];
            const demo = demoRoleData[role];

            // 1. Update Hidden Role Input
            document.getElementById('expectedRoleInput').value = role;

            // 2. Update Tabs State
            document.querySelectorAll('.role-tab-btn').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-role') === role);
            });

            // 3. Update Top Bar & Role Badge
            document.getElementById('cardTopBar').style.background = config.color;
            const badge = document.getElementById('roleBadge');
            badge.className = 'role-badge ' + config.bgClass;
            document.getElementById('roleBadgeIcon').className = config.icon;
            document.getElementById('roleBadgeText').textContent = config.title;

            // 4. Update Form Inputs Label & Placeholder
            document.getElementById('usernameLabel').textContent = config.label;
            document.getElementById('usernameInput').placeholder = config.placeholder;
            document.getElementById('usernameFieldIcon').className = config.fieldIcon;

            // 5. Update Submit Button
            const submitBtn = document.getElementById('submitLoginBtn');
            submitBtn.className = 'btn-submit-login ' + config.btnClass;
            document.getElementById('submitBtnText').textContent = config.btnText;

            // 6. Update Demo Top Banner
            const banner = document.getElementById('demoCardBanner');
            if (banner && demo) {
                banner.className = 'demo-card-banner ' + demo.bannerClass;
                document.getElementById('demoBannerTitleText').textContent = demo.title;
                const altText = demo.altUsername ? ` (or <code>${demo.altUsername}</code>)` : '';
                document.getElementById('demoBannerCreds').innerHTML = 
                    `Username: <code>${demo.username}</code>${altText} &bull; Pass: <code>${demo.password}</code>`;
            }

            // 7. Update Secondary Prominent Demo Button
            const promBtn = document.getElementById('demoProminentBtn');
            if (promBtn && demo) {
                promBtn.className = 'btn-demo-prominent ' + demo.prominentClass;
                document.getElementById('demoProminentBtnText').textContent = demo.btnText;
                document.getElementById('demoProminentBadge').textContent = demo.badgeText;
            }

            // 8. Update Active Highlight on Bottom Grid Buttons
            const bStudent = document.getElementById('demoStudentBtn');
            const bStaff = document.getElementById('demoStaffBtn');
            const bAdmin = document.getElementById('demoAdminBtn');
            if (bStudent) bStudent.classList.toggle('active-role-card', role === 'student');
            if (bStaff) bStaff.classList.toggle('active-role-card', role === 'staff');
            if (bAdmin) bAdmin.classList.toggle('active-role-card', role === 'admin');

            // 9. Update Forgot Password Link
            document.getElementById('forgotPassLink').href = config.forgotUrl;

            // 10. Update URL without refreshing page
            const newUrl = new URL(window.location);
            newUrl.searchParams.set('role', role);
            window.history.replaceState({}, '', newUrl);
        }

        // Auto-fill Demo credentials for currently active role
        function fillCurrentDemoCredentials() {
            const demo = demoRoleData[currentRole] || demoRoleData.student;
            fillDemoCredentials(demo.role, demo.username, demo.password);
        }

        // Fill credentials into inputs with animation and toast notice
        function fillDemoCredentials(role, username, password) {
            if (currentRole !== role) {
                switchLoginRole(role);
            }

            const userInput = document.getElementById('usernameInput');
            const passInput = document.getElementById('passwordInput');
            userInput.value = username;
            passInput.value = password;

            userInput.classList.add('filling-active');
            passInput.classList.add('filling-active');
            setTimeout(() => {
                userInput.classList.remove('filling-active');
                passInput.classList.remove('filling-active');
            }, 500);

            showToastNotification(`Demo ${demoRoleData[role].title} credentials filled! Click 'Sign In' or submit.`);
        }

        // 1-Click Quick Demo Login for currently active role
        function quickRoleDemoLogin() {
            const demo = demoRoleData[currentRole] || demoRoleData.student;
            loginAsDemo(demo.role, demo.username, demo.password);
        }

        // 1-Click Demo Login Function (fills and submits)
        function loginAsDemo(role, username, password) {
            // 1. Switch to corresponding role tab
            if (currentRole !== role) {
                switchLoginRole(role);
            }

            // 2. Populate credentials
            const userInput = document.getElementById('usernameInput');
            const passInput = document.getElementById('passwordInput');
            userInput.value = username;
            passInput.value = password;

            // 3. Visual animation feedback
            const form = document.getElementById('unifiedLoginForm');
            form.classList.add('filling-active');

            const submitBtn = document.getElementById('submitLoginBtn');
            const promBtn = document.getElementById('demoProminentBtn');
            const roleName = role.charAt(0).toUpperCase() + role.slice(1);
            
            submitBtn.disabled = true;
            if (promBtn) promBtn.disabled = true;

            document.getElementById('submitBtnText').innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i> Logging in as Demo ${roleName}...`;
            if (promBtn) {
                document.getElementById('demoProminentBtnText').innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i> Signing In...`;
            }

            // 4. Submit form automatically
            setTimeout(() => {
                form.submit();
            }, 300);
        }

        // Toast feedback notice
        function showToastNotification(message) {
            const toast = document.getElementById('demoToast');
            const text = document.getElementById('demoToastText');
            if (!toast || !text) return;

            text.textContent = message;
            toast.classList.add('show');

            if (toastTimeout) clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => {
                toast.classList.remove('show');
            }, 3200);
        }

        // Toggle password show / hide
        function togglePasswordVisibility() {
            const passInput = document.getElementById('passwordInput');
            const icon = document.getElementById('togglePasswordIcon');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                passInput.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        // On Page Load: initialize initial role or demo trigger
        document.addEventListener('DOMContentLoaded', () => {
            switchLoginRole(currentRole);

            // Handle ?demo=student | ?demo=1 | ?demo=staff | ?demo=admin query parameter
            const urlParams = new URLSearchParams(window.location.search);
            const demoTrigger = (urlParams.get('demo') || '').toLowerCase();
            
            if (demoTrigger === 'student' || (currentRole === 'student' && (demoTrigger === '1' || demoTrigger === 'true' || demoTrigger === 'yes'))) {
                loginAsDemo('student', 'john.d', 'student123');
            } else if (demoTrigger === 'staff' || (currentRole === 'staff' && (demoTrigger === '1' || demoTrigger === 'true' || demoTrigger === 'yes'))) {
                loginAsDemo('staff', 'rajesh.s', 'staff123');
            } else if (demoTrigger === 'admin' || (currentRole === 'admin' && (demoTrigger === '1' || demoTrigger === 'true' || demoTrigger === 'yes'))) {
                loginAsDemo('admin', 'admin', 'admin123');
            } else if (demoTrigger === 'fill' || demoTrigger === 'autofill') {
                fillCurrentDemoCredentials();
            }
        });
    </script>
    <script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
