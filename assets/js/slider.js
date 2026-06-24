/**
 * PMS - Performance Management System
 * Slider/Sidebar JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    initDropdowns();
    initThemeToggle();
    initMobileMenu();
    setActiveMenuFromURL();
});

/**
 * Initialize dropdown menus
 */
function initDropdowns() {
    const dropdowns = document.querySelectorAll('.menu-item-wrapper.dropdown');

    dropdowns.forEach(wrapper => {
        const trigger = wrapper.querySelector('.menu-item');

        if (trigger) {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();

                // Toggle current dropdown
                wrapper.classList.toggle('active');

                // Accordion behavior - close other dropdowns
                dropdowns.forEach(other => {
                    if (other !== wrapper) {
                        other.classList.remove('active');
                    }
                });
            });
        }
    });
}

/**
 * Initialize dark/light theme toggle
 */
function initThemeToggle() {
    const themeToggler = document.getElementById('dark-mode-toggle');

    if (!themeToggler) return;

    const spans = themeToggler.querySelectorAll('span');

    // Check for saved theme preference
    const savedTheme = localStorage.getItem('pms-theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        updateThemeToggleUI(spans, true);
    }

    themeToggler.addEventListener('click', () => {
        const isDark = document.body.classList.toggle('dark-mode');
        localStorage.setItem('pms-theme', isDark ? 'dark' : 'light');
        updateThemeToggleUI(spans, isDark);
    });
}

function updateThemeToggleUI(spans, isDark) {
    if (spans.length >= 2) {
        spans[0].classList.toggle('active', !isDark);
        spans[1].classList.toggle('active', isDark);
    }
}

/**
 * Initialize mobile menu functionality
 */
function initMobileMenu() {
    const closeBtn = document.getElementById('close-btn');
    const sidebar = document.querySelector('.sidebar-container');
    const overlay = document.querySelector('.sidebar-overlay');

    // Create menu toggle button if not exists
    let menuToggle = document.querySelector('.menu-toggle');
    if (!menuToggle) {
        menuToggle = document.createElement('button');
        menuToggle.className = 'menu-toggle';
        menuToggle.innerHTML = '<span class="material-symbols-sharp">menu</span>';
        document.body.appendChild(menuToggle);
    }

    // Create overlay if not exists
    let sidebarOverlay = overlay;
    if (!sidebarOverlay) {
        sidebarOverlay = document.createElement('div');
        sidebarOverlay.className = 'sidebar-overlay';
        document.body.appendChild(sidebarOverlay);
    }

    // Toggle sidebar on menu button click
    menuToggle.addEventListener('click', () => {
        sidebar.classList.add('open');
        sidebarOverlay.classList.add('active');
    });

    // Close sidebar
    const closeSidebar = () => {
        sidebar.classList.remove('open');
        sidebarOverlay.classList.remove('active');
    };

    if (closeBtn) {
        closeBtn.addEventListener('click', closeSidebar);
    }

    sidebarOverlay.addEventListener('click', closeSidebar);
}

/**
 * Set active menu item based on current URL
 */
function setActiveMenuFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    const currentPage = urlParams.get('page') || 'homepage';

    // Remove all active classes first
    document.querySelectorAll('.menu-item.active').forEach(item => {
        item.classList.remove('active');
    });

    document.querySelectorAll('.sub-menu a.active').forEach(item => {
        item.classList.remove('active');
    });

    // Find and activate the correct menu item
    const menuLinks = document.querySelectorAll('.sidebar-menu a');
    menuLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(`page=${currentPage}`)) {
            link.classList.add('active');

            // If it's a submenu item, also open the parent dropdown
            const parentDropdown = link.closest('.menu-item-wrapper.dropdown');
            if (parentDropdown) {
                parentDropdown.classList.add('active');
            }
        }
    });
}