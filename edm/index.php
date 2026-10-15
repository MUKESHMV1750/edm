<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/functions.php';

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
    }
}
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
    <title>Engineering Department Management System | EDM Portal</title>
    <!-- Google Fonts & FontAwesome -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Main Style -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0F172A 0%, #1E3A8A 50%, #2563EB 100%);
            --accent-glow: rgba(59, 130, 246, 0.4);
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: #1E293B;
            background-color: #F8FAFC;
        }

        /* Dark Mode Overrides for Landing Page */
        [data-theme="dark"] body,
        body.dark-mode {
            background-color: #0B1120 !important;
            color: #F8FAFC !important;
        }

        [data-theme="dark"] .role-card,
        body.dark-mode .role-card,
        [data-theme="dark"] .intro-card,
        body.dark-mode .intro-card,
        [data-theme="dark"] .demo-cred-card,
        body.dark-mode .demo-cred-card {
            background-color: #151F32 !important;
            border-color: #24324D !important;
            color: #F8FAFC !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.45) !important;
        }

        [data-theme="dark"] .section-heading,
        body.dark-mode .section-heading,
        [data-theme="dark"] .text-dark,
        body.dark-mode .text-dark {
            color: #F8FAFC !important;
        }

        [data-theme="dark"] .intro-card:hover,
        body.dark-mode .intro-card:hover {
            background-color: #1E293B !important;
            border-color: #3B82F6 !important;
        }

        /* Top Navigation */
        .landing-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 40px;
            background: #0F172A;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ffffff;
            font-weight: 700;
            font-size: 1.35rem;
            text-decoration: none;
            letter-spacing: -0.5px;
        }

        .brand-logo i {
            font-size: 1.8rem;
            color: #60A5FA;
        }

        .brand-sub {
            font-size: 0.72rem;
            color: #94A3B8;
            font-weight: 400;
            display: block;
            letter-spacing: 0.5px;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .nav-link-custom {
            color: #CBD5E1;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .nav-link-custom:hover {
            color: #60A5FA;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-nav-login {
            background: #2563EB;
            color: #ffffff;
            padding: 9px 22px;
            border-radius: 50px;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
            transition: all 0.25s ease;
        }

        .btn-nav-login:hover {
            background: #1D4ED8;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.45);
        }

        /* Hero / Introduction Section */
        .landing-hero {
            background: var(--primary-gradient);
            color: #ffffff;
            padding: 85px 20px 110px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .landing-hero::before {
            content: '';
            position: absolute;
            top: -40%;
            left: -40%;
            width: 180%;
            height: 180%;
            background: radial-gradient(circle, rgba(96, 165, 250, 0.12) 0%, transparent 60%);
            animation: pulse 8s infinite alternate ease-in-out;
        }

        @keyframes pulse {
            0% { transform: scale(1) translate(0, 0); }
            100% { transform: scale(1.1) translate(15px, -15px); }
        }

        .hero-badge-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .feature-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 18px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #E2E8F0;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .hero-title {
            font-size: 2.85rem;
            font-weight: 800;
            line-height: 1.25;
            letter-spacing: -1px;
            margin-bottom: 20px;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-title span {
            background: linear-gradient(135deg, #93C5FD 0%, #60A5FA 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-description {
            font-size: 1.15rem;
            font-weight: 400;
            line-height: 1.7;
            color: #E2E8F0;
            opacity: 0.95;
            max-width: 780px;
            margin: 0 auto 35px;
        }

        /* Hero Primary Action CTA Buttons */
        .hero-cta-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .btn-hero-primary {
            background: #2563EB;
            color: #ffffff;
            font-size: 1.05rem;
            font-weight: 700;
            padding: 15px 36px;
            border-radius: 50px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.45);
            transition: all 0.3s ease;
            position: relative;
        }

        .btn-hero-primary:hover {
            background: #1D4ED8;
            color: #ffffff;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 35px rgba(37, 99, 235, 0.6);
        }

        .btn-hero-secondary {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #ffffff;
            font-size: 1.05rem;
            font-weight: 600;
            padding: 15px 32px;
            border-radius: 50px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .btn-hero-secondary:hover {
            background: rgba(255, 255, 255, 0.22);
            color: #ffffff;
            transform: translateY(-3px);
        }

        /* Quick Demo Shortcut Chips in Hero */
        .hero-demo-strip {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 18px;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px);
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            font-size: 0.85rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .demo-chip {
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.78rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: transform 0.2s ease;
        }

        .demo-chip:hover {
            transform: translateY(-2px);
        }

        .chip-student { background: #DBEAFE; color: #1E40AF; }
        .chip-staff { background: #D1FAE5; color: #065F46; }
        .chip-admin { background: #FEF3C7; color: #92400E; }

        /* Role Cards Section */
        .portals-section {
            margin-top: -65px;
            position: relative;
            z-index: 10;
        }

        .role-card {
            background: #ffffff;
            border-radius: 22px;
            padding: 38px 28px;
            text-align: center;
            transition: all 0.35s cubic-bezier(0.165, 0.84, 0.44, 1);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            border: 1px solid #E2E8F0;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .role-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: #CBD5E1;
            transition: background 0.3s ease;
        }

        .role-card.card-student::after { background: #2563EB; }
        .role-card.card-staff::after { background: #059669; }
        .role-card.card-admin::after { background: #D97706; }

        .role-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(15, 23, 42, 0.15);
        }

        .role-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 20px;
            transition: transform 0.3s ease;
        }

        .role-card:hover .role-icon {
            transform: scale(1.1) rotate(4deg);
        }

        .icon-student { background: #EFF6FF; color: #2563EB; }
        .icon-staff { background: #ECFDF5; color: #059669; }
        .icon-admin { background: #FFFBEB; color: #D97706; }

        .role-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 8px;
        }

        .role-desc {
            font-size: 0.88rem;
            color: #64748B;
            line-height: 1.6;
            margin-bottom: 22px;
        }

        .role-features-list {
            list-style: none;
            padding: 0;
            margin: 0 0 25px 0;
            text-align: left;
            font-size: 0.82rem;
            color: #475569;
        }

        .role-features-list li {
            padding: 5px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .role-features-list li i {
            font-size: 0.8rem;
            color: #10B981;
        }

        .btn-card-login {
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.25s ease;
            width: 100%;
        }

        .btn-student {
            background: #2563EB;
            color: #fff;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.25);
        }
        .btn-student:hover {
            background: #1D4ED8;
            color: #fff;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.35);
        }

        .btn-staff {
            background: #059669;
            color: #fff;
            box-shadow: 0 4px 15px rgba(5, 150, 105, 0.25);
        }
        .btn-staff:hover {
            background: #047857;
            color: #fff;
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.35);
        }

        .btn-admin {
            background: #D97706;
            color: #fff;
            box-shadow: 0 4px 15px rgba(217, 119, 6, 0.25);
        }
        .btn-admin:hover {
            background: #B45309;
            color: #fff;
            box-shadow: 0 8px 20px rgba(217, 119, 6, 0.35);
        }

        /* Overview / Introduction Section */
        .section-intro {
            padding: 85px 0;
            background: #ffffff;
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-sub {
            color: #2563EB;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: block;
            margin-bottom: 8px;
        }

        .section-heading {
            font-size: 2.2rem;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: -0.5px;
        }

        .intro-card {
            background: #F8FAFC;
            border-radius: 18px;
            padding: 30px 25px;
            border: 1px solid #E2E8F0;
            height: 100%;
            transition: transform 0.3s ease;
        }

        .intro-card:hover {
            transform: translateY(-5px);
            background: #ffffff;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.06);
            border-color: #CBD5E1;
        }

        .intro-card-icon {
            width: 55px;
            height: 55px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 18px;
        }

        /* Demo Credentials Table Section */
        .demo-table-section {
            background: #0F172A;
            color: #ffffff;
            padding: 70px 0;
        }

        .table-dark-custom {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .table-dark-custom th {
            background: rgba(255, 255, 255, 0.08);
            color: #94A3B8;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.78rem;
            letter-spacing: 0.5px;
            padding: 16px 20px;
            border: none;
        }

        .table-dark-custom td {
            padding: 16px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.07);
            color: #E2E8F0;
            vertical-align: middle;
        }

        /* Footer */
        .landing-footer {
            background: #090D16;
            color: #94A3B8;
            padding: 40px 0 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.88rem;
        }

        @media (max-width: 768px) {
            .landing-nav { padding: 15px 20px; }
            .nav-menu { display: none; }
            .hero-title { font-size: 2rem; }
            .hero-description { font-size: 0.98rem; }
            .portals-section { margin-top: -30px; }
        }
    </style>
</head>
<body>

    <!-- Top Navigation Bar with Direct Go To Login Button -->
    <nav class="landing-nav">
        <a href="<?= APP_URL ?>" class="brand-logo">
            <i class="fas fa-graduation-cap"></i>
            <div>
                <span>EDM Portal</span>
                <span class="brand-sub">Engineering Department Management</span>
            </div>
        </a>

        <!-- Desktop Navigation Menu -->
        <div class="nav-menu">
            <a href="#overview" class="nav-link-custom">Overview</a>
            <a href="#portals" class="nav-link-custom">3 Portals</a>
            <a href="#features" class="nav-link-custom">Key Modules</a>
            <a href="#demo-access" class="nav-link-custom">Demo Credentials</a>
        </div>

        <!-- Navigation Action Buttons -->
        <div class="nav-actions">
            <!-- Go to Unified Login Page -->
            <a href="<?= APP_URL ?>/auth/login.php" class="btn-nav-login" id="navLoginBtn">
                <i class="fas fa-sign-in-alt"></i>
                <span>Sign In / Login</span>
            </a>
            <!-- Theme Toggle -->
            <button id="themeToggleBtn" class="btn btn-outline-light btn-sm rounded-circle p-2" aria-label="Toggle theme">
                <i class="fas fa-moon"></i>
            </button>
        </div>
    </nav>

    <!-- Hero / Introduction Section -->
    <section class="landing-hero">
        <div class="container position-relative" style="z-index: 2;">
            
            <!-- Badges -->
            <div class="hero-badge-wrap">
                <span class="feature-badge">
                    <i class="fas fa-microchip text-warning"></i> Department of Computer Science & Engineering
                </span>
                <span class="feature-badge">
                    <i class="fas fa-shield-alt text-info"></i> Integrated Academic Platform
                </span>
                <span class="feature-badge">
                    <i class="fas fa-satellite-dish text-success"></i> Real-time Operations Active
                </span>
            </div>

            <!-- Engaging Introduction Headline -->
            <h1 class="hero-title">
                Smart Operations & Digital Learning for <span>Engineering Departments</span>
            </h1>

            <!-- Detailed Introduction Content -->
            <p class="hero-description">
                A centralized departmental platform bringing together <strong>Students</strong>, <strong>Faculty</strong>, and <strong>Administrators</strong>. Experience real-time smart attendance (QR, Dynamic PIN & GPS), synchronized classroom presentations, coursework grading, classroom reservations, and automated departmental analytics in one unified portal.
            </p>

            <!-- Prominent "Go to Login" and Action Buttons (Requested by user) -->
            <div class="hero-cta-group">
                <!-- PRIMARY CTA: Go to Login Page -->
                <a href="<?= APP_URL ?>/auth/login.php" class="btn-hero-primary" id="heroGoToLoginBtn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Go to Login Page</span>
                    <i class="fas fa-arrow-right"></i>
                </a>

                <!-- SECONDARY CTA: Explore the 3 Portals -->
                <a href="#portals" class="btn-hero-secondary">
                    <i class="fas fa-th-large"></i>
                    <span>Explore 3 Portals</span>
                </a>
            </div>

            <!-- Instant Demo Quick-Access Strip -->
            <div class="hero-demo-strip">
                <span class="text-white-50"><i class="fas fa-bolt text-warning me-1"></i> Instant Demo Login:</span>
                <a href="<?= APP_URL ?>/auth/login.php?demo=student" class="demo-chip chip-student" title="1-Click Demo Student Login">
                    <i class="fas fa-user-graduate"></i> Student (john.d)
                </a>
                <a href="<?= APP_URL ?>/auth/login.php?demo=staff" class="demo-chip chip-staff" title="1-Click Demo Faculty Login">
                    <i class="fas fa-chalkboard-teacher"></i> Faculty (rajesh.s)
                </a>
                <a href="<?= APP_URL ?>/auth/login.php?demo=admin" class="demo-chip chip-admin" title="1-Click Demo Admin Login">
                    <i class="fas fa-user-shield"></i> Admin (admin)
                </a>
            </div>

        </div>
    </section>

    <!-- The 3 Role Portals (Cards with Login Options) -->
    <section class="portals-section pb-5" id="portals">
        <div class="container">
            <div class="row g-4 justify-content-center">
                
                <!-- 1. Student Portal Card -->
                <div class="col-md-4">
                    <div class="role-card card-student">
                        <div>
                            <div class="role-icon icon-student">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <h2 class="role-title">Student Portal</h2>
                            <p class="role-desc">
                                Check real-time attendance percentages, submit assignments, view internal marks, download study materials, and tune into live slide sync during lectures.
                            </p>
                            <ul class="role-features-list">
                                <li><i class="fas fa-check-circle"></i> Mark Attendance via QR, PIN & GPS</li>
                                <li><i class="fas fa-check-circle"></i> Live Slide Sync & Participant Tracking</li>
                                <li><i class="fas fa-check-circle"></i> Coursework & Assignment Submissions</li>
                                <li><i class="fas fa-check-circle"></i> Semester Question Papers & Study Notes</li>
                            </ul>
                        </div>
                        <div>
                            <!-- Go to Login with Student Preselected -->
                            <a href="<?= APP_URL ?>/auth/login.php?role=student" class="btn-card-login btn-student mb-2">
                                <span>Student Login</span>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                            <a href="<?= APP_URL ?>/auth/login.php?demo=student" class="text-primary small font-weight-500 d-inline-block">
                                <i class="fas fa-bolt me-1"></i> Quick Demo Access (john.d)
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 2. Faculty & Staff Portal Card -->
                <div class="col-md-4">
                    <div class="role-card card-staff">
                        <div>
                            <div class="role-icon icon-staff">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <h2 class="role-title">Faculty Portal</h2>
                            <p class="role-desc">
                                Create instant attendance sessions with dynamic QR/PINs, broadcast live presentations to student screens, grade assignments, and reserve smart labs.
                            </p>
                            <ul class="role-features-list">
                                <li><i class="fas fa-check-circle"></i> Dynamic PIN & QR Code Attendance</li>
                                <li><i class="fas fa-check-circle"></i> Live PPT/PDF Slide Sync Broadcast</li>
                                <li><i class="fas fa-check-circle"></i> Assignment Grading & Feedback</li>
                                <li><i class="fas fa-check-circle"></i> Conflict-Free Classroom Booking</li>
                            </ul>
                        </div>
                        <div>
                            <!-- Go to Login with Staff Preselected -->
                            <a href="<?= APP_URL ?>/auth/login.php?role=staff" class="btn-card-login btn-staff mb-2">
                                <span>Faculty Login</span>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                            <a href="<?= APP_URL ?>/auth/login.php?demo=staff" class="text-success small font-weight-500 d-inline-block">
                                <i class="fas fa-bolt me-1"></i> Quick Demo Access (rajesh.s)
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 3. Admin Portal Card -->
                <div class="col-md-4">
                    <div class="role-card card-admin">
                        <div>
                            <div class="role-icon icon-admin">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <h2 class="role-title">Admin Console</h2>
                            <p class="role-desc">
                                Oversee students, staff, subjects, academic timetables, approve classroom bookings, inspect audit trails, and export comprehensive departmental analytics.
                            </p>
                            <ul class="role-features-list">
                                <li><i class="fas fa-check-circle"></i> Complete Student & Faculty Directory</li>
                                <li><i class="fas fa-check-circle"></i> Timetable & Subject Management</li>
                                <li><i class="fas fa-check-circle"></i> Classroom Booking Approvals</li>
                                <li><i class="fas fa-check-circle"></i> System Security & Audit Logging</li>
                            </ul>
                        </div>
                        <div>
                            <!-- Go to Login with Admin Preselected -->
                            <a href="<?= APP_URL ?>/auth/login.php?role=admin" class="btn-card-login btn-admin mb-2">
                                <span>Admin Login</span>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                            <a href="<?= APP_URL ?>/auth/login.php?demo=admin" class="text-warning small font-weight-600 d-inline-block">
                                <i class="fas fa-bolt me-1"></i> Quick Demo Access (admin)
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Detailed Department Introduction & Core Pillars (#overview) -->
    <section class="section-intro" id="overview">
        <div class="container">
            <div class="section-header">
                <span class="section-sub">System Overview & Architecture</span>
                <h2 class="section-heading">Why Engineering Department Management (EDM)?</h2>
                <p class="text-muted max-width-700 mx-auto mt-2">
                    Engineered to eliminate paper-based workflows, attendance discrepancies, and lecture disconnections through a cohesive digital campus platform.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="intro-card">
                        <div class="intro-card-icon bg-primary-subtle text-primary">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <h3 class="h5 font-weight-bold mb-2">Smart Multi-Mode Attendance</h3>
                        <p class="text-muted small mb-0">
                            Faculty launch 5-minute dynamic QR codes, expiring 4-digit PINs, or GPS-bounded sessions. Students verify presence with single-click authentication, preventing proxy attendance.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="intro-card">
                        <div class="intro-card-icon bg-success-subtle text-success">
                            <i class="fas fa-broadcast-tower"></i>
                        </div>
                        <h3 class="h5 font-weight-bold mb-2">Synchronized Live Lectures</h3>
                        <p class="text-muted small mb-0">
                            Faculty broadcast PDF/PPT slide presentations in real-time. When faculty change slides, all connected students' screens advance automatically with participant tracking and idle detection.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="intro-card">
                        <div class="intro-card-icon bg-warning-subtle text-warning">
                            <i class="fas fa-door-open"></i>
                        </div>
                        <h3 class="h5 font-weight-bold mb-2">Smart Facility Reservations</h3>
                        <p class="text-muted small mb-0">
                            Prevent scheduling conflicts with real-time room availability for smart classrooms, seminar halls, and computer laboratories with automated admin approval workflows.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Middle CTA Banner: Go To Login -->
            <div class="mt-5 p-4 rounded-4 text-white text-center" style="background: linear-gradient(135deg, #1E3A8A 0%, #0F172A 100%);">
                <div class="row align-items-center">
                    <div class="col-lg-8 text-lg-start mb-3 mb-lg-0">
                        <h3 class="h4 font-weight-bold mb-1">Ready to access your Department Portal?</h3>
                        <p class="text-light opacity-90 small mb-0">Single sign-on form supports Students, Faculty, and Administrators with instant demo credentials.</p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-primary rounded-pill px-4 py-2.5 font-weight-600 shadow">
                            <i class="fas fa-sign-in-alt me-2"></i> Open Login Page
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Key Modules / Features Section (#features) -->
    <section class="py-5 bg-light border-top border-bottom" id="features">
        <div class="container">
            <div class="section-header mb-4">
                <span class="section-sub">Enterprise Features</span>
                <h2 class="section-heading">Engineered for Academic Excellence</h2>
            </div>

            <div class="row text-center g-4">
                <div class="col-6 col-md-3">
                    <div class="bg-white p-4 rounded-3 shadow-sm h-100 border">
                        <i class="fas fa-fingerprint text-primary display-6 mb-3"></i>
                        <h4 class="h6 font-weight-bold mb-1">Proxy-Proof Tracking</h4>
                        <span class="text-muted small">GPS Radius + Dynamic PIN safeguards</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bg-white p-4 rounded-3 shadow-sm h-100 border">
                        <i class="fas fa-file-upload text-success display-6 mb-3"></i>
                        <h4 class="h6 font-weight-bold mb-1">Assignments & Marks</h4>
                        <span class="text-muted small">Online submissions, grading & internal calculation</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bg-white p-4 rounded-3 shadow-sm h-100 border">
                        <i class="fas fa-bell text-warning display-6 mb-3"></i>
                        <h4 class="h6 font-weight-bold mb-1">Instant Notifications</h4>
                        <span class="text-muted small">Alerts for lectures, deadlines & bookings</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bg-white p-4 rounded-3 shadow-sm h-100 border">
                        <i class="fas fa-file-excel text-info display-6 mb-3"></i>
                        <h4 class="h6 font-weight-bold mb-1">Exportable Analytics</h4>
                        <span class="text-muted small">Automated CSV / PDF departmental reports</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Demo Credentials Reference Section (#demo-access) -->
    <section class="demo-table-section" id="demo-access">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <span class="badge bg-warning text-dark font-weight-600 mb-2 px-3 py-1.5 rounded-pill">
                        <i class="fas fa-key me-1"></i> Pre-Configured Demo Accounts
                    </span>
                    <h3 class="h2 font-weight-bold text-white mb-3">Test Any Role in 1-Click</h3>
                    <p class="text-light opacity-80 small mb-4">
                        Experience all capabilities immediately without registration. Select any demo persona to automatically log into their tailored dashboard.
                    </p>
                    <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-outline-light rounded-pill px-4 py-2 font-weight-600">
                        <i class="fas fa-sign-in-alt me-2"></i> Go to Single Login Form
                    </a>
                </div>

                <div class="col-lg-7">
                    <div class="table-responsive table-dark-custom">
                        <table class="table table-dark table-borderless mb-0">
                            <thead>
                                <tr>
                                    <th>Role</th>
                                    <th>Username</th>
                                    <th>Password</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary px-2.5 py-1.5 rounded-pill">
                                            <i class="fas fa-user-graduate me-1"></i> Student
                                        </span>
                                    </td>
                                    <td><code>john.d</code></td>
                                    <td><code>student123</code></td>
                                    <td class="text-end">
                                        <a href="<?= APP_URL ?>/auth/login.php?demo=student" class="btn btn-sm btn-primary rounded-pill px-3">
                                            <i class="fas fa-bolt me-1"></i> Demo Login
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge bg-success px-2.5 py-1.5 rounded-pill">
                                            <i class="fas fa-chalkboard-teacher me-1"></i> Faculty
                                        </span>
                                    </td>
                                    <td><code>rajesh.s</code></td>
                                    <td><code>staff123</code></td>
                                    <td class="text-end">
                                        <a href="<?= APP_URL ?>/auth/login.php?demo=staff" class="btn btn-sm btn-success rounded-pill px-3">
                                            <i class="fas fa-bolt me-1"></i> Demo Login
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge bg-warning text-dark px-2.5 py-1.5 rounded-pill">
                                            <i class="fas fa-user-shield me-1"></i> Admin
                                        </span>
                                    </td>
                                    <td><code>admin</code></td>
                                    <td><code>admin123</code></td>
                                    <td class="text-end">
                                        <a href="<?= APP_URL ?>/auth/login.php?demo=admin" class="btn btn-sm btn-warning text-dark rounded-pill px-3">
                                            <i class="fas fa-bolt me-1"></i> Demo Login
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <p class="mb-1">&copy; <?= date('Y') ?> Engineering Department Management System (EDM Portal).</p>
                    <small class="text-muted">Designed for Computer Science & Engineering Departments.</small>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="<?= APP_URL ?>/auth/login.php" class="text-light me-3 text-decoration-none small">
                        <i class="fas fa-sign-in-alt me-1"></i> Unified Login
                    </a>
                    <span class="badge bg-success-subtle text-success border border-success rounded-pill px-2.5 py-1 small">
                        <i class="fas fa-circle text-success me-1" style="font-size: 8px;"></i> System Online
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Main JavaScript -->
    <script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
