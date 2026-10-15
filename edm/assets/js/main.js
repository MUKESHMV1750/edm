/**
 * ENGINEERING DEPARTMENT MANAGEMENT SYSTEM
 * Core JavaScript Framework
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Sidebar Toggle
    const sidebar = document.getElementById('sidebar');
    const sidebarToggleBtn = document.getElementById('sidebarToggle');
    const mobileSidebarToggleBtn = document.getElementById('mobileSidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggleBtn && sidebar) {
        sidebarToggleBtn.addEventListener('click', () => {
            if (window.innerWidth < 992) {
                sidebar.classList.toggle('show');
                if (sidebarOverlay) sidebarOverlay.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                document.body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('edm_sidebar_collapsed', sidebar.classList.contains('collapsed'));
            }
        });

        // Restore collapsed state on desktop
        if (window.innerWidth >= 992 && localStorage.getItem('edm_sidebar_collapsed') === 'true') {
            sidebar.classList.add('collapsed');
            document.body.classList.add('sidebar-collapsed');
        }
    }

    if (mobileSidebarToggleBtn && sidebar) {
        mobileSidebarToggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            if (sidebarOverlay) sidebarOverlay.classList.toggle('show');
        });
    }

    if (sidebarOverlay && sidebar) {
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });
    }

    // 2. Dark Mode Toggle
    function applyAppTheme(theme) {
        const isDark = (theme === 'dark');
        if (isDark) {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.documentElement.classList.add('dark-mode');
            document.body.classList.add('dark-mode');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            document.documentElement.classList.remove('dark-mode');
            document.body.classList.remove('dark-mode');
        }
        const buttons = document.querySelectorAll('#themeToggleBtn, .theme-toggle-btn');
        buttons.forEach(btn => {
            btn.innerHTML = isDark ? '<i class="fas fa-sun text-warning"></i>' : '<i class="fas fa-moon"></i>';
            btn.setAttribute('title', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
            btn.setAttribute('aria-label', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
        });
        window.dispatchEvent(new CustomEvent('edmThemeChanged', { detail: { theme: isDark ? 'dark' : 'light' } }));
    }

    // Initialize Theme
    const savedTheme = localStorage.getItem('edm_theme');
    const systemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const initialTheme = savedTheme ? savedTheme : (systemDark ? 'dark' : 'light');
    applyAppTheme(initialTheme);

    // Event listener for toggle button(s)
    document.addEventListener('click', (e) => {
        const toggleBtn = e.target.closest('#themeToggleBtn, .theme-toggle-btn');
        if (toggleBtn) {
            const currentTheme = document.documentElement.getAttribute('data-theme') || (document.body.classList.contains('dark-mode') ? 'dark' : 'light');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('edm_theme', newTheme);
            applyAppTheme(newTheme);
        }
    });

    // 3. User Dropdown Menu
    if (typeof window.toggleUserDropdown !== 'function') {
        window.toggleUserDropdown = function(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            const menu = document.getElementById('userDropdownMenu');
            const btn = document.getElementById('userDropdownBtn');
            if (!menu) return;

            const isShown = menu.classList.contains('show') || menu.style.display === 'block';
            if (isShown) {
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
        };
    }

    // Close on clicking outside
    document.addEventListener('click', (e) => {
        const userDropdownBtn = document.getElementById('userDropdownBtn');
        const userDropdownMenu = document.getElementById('userDropdownMenu');
        if (!userDropdownBtn || !userDropdownMenu) return;
        if (!userDropdownBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
            userDropdownMenu.classList.remove('show');
            userDropdownBtn.classList.remove('show');
            userDropdownBtn.setAttribute('aria-expanded', 'false');
            userDropdownMenu.style.display = 'none';
            userDropdownMenu.style.opacity = '0';
            userDropdownMenu.style.visibility = 'hidden';
            userDropdownMenu.style.pointerEvents = 'none';
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdownMenu = document.getElementById('userDropdownMenu');
            if (!userDropdownMenu) return;
            userDropdownMenu.classList.remove('show');
            if (userDropdownBtn) {
                userDropdownBtn.classList.remove('show');
                userDropdownBtn.setAttribute('aria-expanded', 'false');
            }
            userDropdownMenu.style.display = 'none';
            userDropdownMenu.style.opacity = '0';
            userDropdownMenu.style.visibility = 'hidden';
            userDropdownMenu.style.pointerEvents = 'none';
        }
    });

    // 4. Global Modal Handlers & Automatic Body Relocation
    // Ensures modals are never trapped inside container stacking contexts or table structures
    function relocateModalsToBody() {
        document.querySelectorAll('.modal').forEach(modal => {
            if (modal.parentElement && modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }
        });
    }
    relocateModalsToBody();

    // Re-verify modal placement on show event
    document.addEventListener('show.bs.modal', (e) => {
        if (e.target && e.target.parentElement !== document.body) {
            document.body.appendChild(e.target);
        }
    });

    document.querySelectorAll('[data-bs-dismiss="modal"], [data-dismiss="modal"]').forEach(button => {
        button.addEventListener('click', () => {
            const modalEl = button.closest('.modal');
            if (modalEl) {
                const bsModal = bootstrap.Modal.getInstance(modalEl);
                if (bsModal) {
                    bsModal.hide();
                } else {
                    modalEl.classList.remove('show');
                    modalEl.style.display = 'none';
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) backdrop.remove();
                    document.body.classList.remove('modal-open');
                }
            }
        });
    });

    // 5. Alert Auto-dismiss after 5s
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // 6. Active Sidebar Link Marking
    const currentUrl = window.location.pathname;
    document.querySelectorAll('.sidebar-nav a').forEach(link => {
        if (link.getAttribute('href') && currentUrl.endsWith(link.getAttribute('href'))) {
            link.classList.add('active');
            const parentCollapse = link.closest('.collapse');
            if (parentCollapse) {
                parentCollapse.classList.add('show');
            }
        }
    });
});

/**
 * Toast Notification Utility
 * @param {string} message - Message text
 * @param {string} type - 'success', 'danger', 'warning', 'info'
 */
function showToast(message, type = 'info') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `alert alert-${type} shadow-lg align-items-center show fade-in`;
    toast.style.minWidth = '280px';
    toast.style.marginBottom = '10px';
    toast.innerHTML = `
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'danger' ? 'fa-exclamation-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close ms-3" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;

    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.4s ease';
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

/**
 * Global API Fetch Helper
 * @param {string} url 
 * @param {object} options 
 */
async function fetchAPI(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            ...options
        });
        const data = await response.json();
        return data;
    } catch (err) {
        console.error('API Error:', err);
        showToast('Network or server error encountered', 'danger');
        return { success: false, message: 'Server error' };
    }
}
