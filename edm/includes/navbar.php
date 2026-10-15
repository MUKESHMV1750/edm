<?php
/**
 * Top Navigation Bar
 * Engineering Department Management System
 */

$currentRole = getCurrentUserRole();
$userName = getCurrentUserName();
$notifCount = isLoggedIn() ? 0 : 0;
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-xs px-4 py-2" id="main-navbar">
    <div class="container-fluid p-0">
        
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-secondary btn-sm border-0" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="fas fa-bars fs-5"></i>
            </button>
            <a href="<?= APP_URL ?>/<?= $currentRole ?>/dashboard.php" class="navbar-brand font-weight-bold text-dark d-flex align-items-center gap-2 mb-0">
                <i class="fas fa-graduation-cap text-primary fs-4"></i>
                <span>EDM Portal</span>
            </a>
        </div>

        <div class="d-flex align-items-center gap-3">
            
            <!-- Dark Mode / Light Mode Toggle Button -->
            <button class="btn btn-light rounded-circle p-2 theme-toggle-btn shadow-xs" id="themeToggleBtn" title="Toggle Theme" aria-label="Toggle dark/light mode" style="width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center;">
                <i class="fas fa-moon"></i>
            </button>

            <!-- Notifications Link -->
            <a href="<?= APP_URL ?>/<?= $currentRole ?>/notifications.php" class="btn btn-light rounded-circle p-2 position-relative" style="width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center;">
                <i class="fas fa-bell"></i>
            </a>

            <!-- User Profile Dropdown -->
            <div class="dropdown position-relative">
                <button class="btn btn-light rounded-pill px-3 py-1.5 d-flex align-items-center gap-2 border" type="button" id="userDropdownBtn" onclick="toggleUserDropdown(event)" aria-expanded="false" style="cursor: pointer; position: relative;">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center font-weight-600 shadow-xs" style="width: 32px; height: 32px; font-size: 14px;">
                        <?= strtoupper(substr($userName, 0, 1)) ?>
                    </div>
                    <div class="text-start d-none d-md-block">
                        <span class="d-block font-weight-600 text-dark small leading-tight"><?= sanitize($userName) ?></span>
                        <span class="d-block text-muted text-xs text-capitalize leading-tight"><?= $currentRole ?></span>
                    </div>
                    <i class="fas fa-chevron-down text-muted small ms-1" id="userDropdownChevron"></i>
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2" id="userDropdownMenu" style="border-radius: 12px; min-width: 220px; position: absolute; top: calc(100% + 8px); right: 0; left: auto; z-index: 99999; display: none;">
                    <li class="px-3 py-2 border-bottom mb-1 bg-light rounded-top">
                        <span class="d-block font-weight-bold text-dark small leading-tight"><?= sanitize($userName) ?></span>
                        <span class="d-block text-muted text-xs text-capitalize"><?= $currentRole ?> Portal</span>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="<?= APP_URL ?>/<?= $currentRole ?>/dashboard.php">
                            <i class="fas fa-tachometer-alt me-2 text-primary"></i> Dashboard
                        </a>
                    </li>
                    <?php if ($currentRole === 'student'): ?>
                        <li>
                            <a class="dropdown-item py-2" href="<?= APP_URL ?>/student/profile.php">
                                <i class="fas fa-user-circle me-2 text-info"></i> My Profile
                            </a>
                        </li>
                    <?php elseif ($currentRole === 'admin'): ?>
                        <li>
                            <a class="dropdown-item py-2" href="<?= APP_URL ?>/admin/settings.php">
                                <i class="fas fa-cog me-2 text-info"></i> System Settings
                            </a>
                        </li>
                    <?php endif; ?>
                    <li>
                        <button class="dropdown-item py-2 d-flex align-items-center justify-content-between theme-toggle-btn w-100 border-0 bg-transparent" type="button">
                            <span><i class="fas fa-moon me-2 text-secondary theme-dropdown-icon"></i> Theme</span>
                            <span class="badge bg-light text-muted small theme-dropdown-label">Dark / Light</span>
                        </button>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item py-2 text-danger" href="<?= APP_URL ?>/auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

        </div>

    </div>
</nav>

<script>
function toggleUserDropdown(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    var menu = document.getElementById('userDropdownMenu');
    var btn = document.getElementById('userDropdownBtn');
    if (!menu) return;

    var isOpen = menu.classList.contains('show') || menu.style.display === 'block';
    if (isOpen) {
        menu.classList.remove('show');
        if (btn) {
            btn.classList.remove('show');
            btn.setAttribute('aria-expanded', 'false');
        }
        menu.style.display = 'none';
        menu.style.opacity = '0';
        menu.style.visibility = 'hidden';
        menu.style.pointerEvents = 'none';
    } else {
        menu.classList.add('show');
        if (btn) {
            btn.classList.add('show');
            btn.setAttribute('aria-expanded', 'true');
        }
        menu.style.display = 'block';
        menu.style.opacity = '1';
        menu.style.visibility = 'visible';
        menu.style.pointerEvents = 'auto';
        menu.style.position = 'absolute';
        menu.style.top = 'calc(100% + 8px)';
        menu.style.right = '0';
        menu.style.left = 'auto';
        menu.style.zIndex = '99999';
    }
}

document.addEventListener('click', function(e) {
    var menu = document.getElementById('userDropdownMenu');
    var btn = document.getElementById('userDropdownBtn');
    if (!menu || !btn) return;
    if (!btn.contains(e.target) && !menu.contains(e.target)) {
        menu.classList.remove('show');
        btn.classList.remove('show');
        btn.setAttribute('aria-expanded', 'false');
        menu.style.display = 'none';
        menu.style.opacity = '0';
        menu.style.visibility = 'hidden';
        menu.style.pointerEvents = 'none';
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        var menu = document.getElementById('userDropdownMenu');
        var btn = document.getElementById('userDropdownBtn');
        if (!menu) return;
        menu.classList.remove('show');
        if (btn) {
            btn.classList.remove('show');
            btn.setAttribute('aria-expanded', 'false');
        }
        menu.style.display = 'none';
        menu.style.opacity = '0';
        menu.style.visibility = 'hidden';
        menu.style.pointerEvents = 'none';
    }
});
</script>
