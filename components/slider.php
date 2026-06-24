<?php
// Get current page for active state
$currentPage = $_GET['page'] ?? 'homepage';
?>
<aside class="sidebar-container">
    <div class="top">
        <div class="logo">
            <img src="assets/images/logo.png" alt="PMS Logo">
        </div>
        <div class="close" id="close-btn">
            <span class="material-symbols-sharp">close</span>
        </div>
    </div>

    <nav class="sidebar-menu">
        <!-- Dashboard -->
        <a href="index.php?page=pms-dashboard" class="menu-item <?php echo $currentPage === 'pms-dashboard' || $currentPage === 'homepage' ? 'active' : ''; ?>">
            <span class="material-symbols-sharp">dashboard</span>
            <h3>Dashboard</h3>
        </a>

        <!-- Admin Dropdown -->
        <div class="menu-item-wrapper dropdown <?php echo strpos($currentPage, 'admin') !== false ? 'active' : ''; ?>">
            <a href="#" class="menu-item">
                <span class="material-symbols-sharp">admin_panel_settings</span>
                <h3>Admin</h3>
                <span class="material-symbols-sharp arrow">chevron_right</span>
            </a>
            <div class="sub-menu">
                <a href="index.php?page=admin-dashboard" class="<?php echo $currentPage === 'admin-dashboard' || $currentPage === 'admin' ? 'active' : ''; ?>">
                    <span class="material-symbols-sharp">grid_view</span>
                    Dashboard
                </a>
                <a href="index.php?page=admin-line-manager" class="<?php echo $currentPage === 'admin-line-manager' ? 'active' : ''; ?>">
                    <span class="material-symbols-sharp">supervisor_account</span>
                    Line Manager
                </a>
                <a href="index.php?page=admin-users" class="<?php echo $currentPage === 'admin-users' ? 'active' : ''; ?>">
                    <span class="material-symbols-sharp">group</span>
                    Users
                </a>
            </div>
        </div>

        <!-- PMS Dropdown -->
        <div class="menu-item-wrapper dropdown <?php echo strpos($currentPage, 'pms') !== false ? 'active' : ''; ?>">
            <a href="#" class="menu-item">
                <span class="material-symbols-sharp">hub</span>
                <h3>PMS Hub</h3>
                <span class="material-symbols-sharp arrow">chevron_right</span>
            </a>
            <div class="sub-menu">
    
                <a href="index.php?page=pms-appraisal" class="<?php echo $currentPage === 'pms-appraisal' ? 'active' : ''; ?>">
                    <span class="material-symbols-sharp">rate_review</span>
                    Yearly Appraisal
                </a>
            </div>
        </div>

        <!-- HR Dropdown -->
        <div class="menu-item-wrapper dropdown <?php echo strpos($currentPage, 'hr') !== false ? 'active' : ''; ?>">
            <a href="#" class="menu-item">
                <span class="material-symbols-sharp">group</span>
                <h3>HR</h3>
                <span class="material-symbols-sharp arrow">chevron_right</span>
            </a>
            <div class="sub-menu">
                <a href="index.php?page=hr-submissions" class="<?php echo $currentPage === 'hr-submissions' ? 'active' : ''; ?>">
                    <span class="material-symbols-sharp">assignment</span>
                    Submissions
                </a>
                <a href="index.php?page=hr-reports" class="<?php echo $currentPage === 'hr-reports' ? 'active' : ''; ?>">
                    <span class="material-symbols-sharp">summarize</span>
                    Reports
                </a>
            </div>
        </div>

        <!-- Goals -->
        <a href="index.php?page=goals" class="menu-item <?php echo $currentPage === 'goals' ? 'active' : ''; ?>">
            <span class="material-symbols-sharp">flag</span>
            <h3>Goals</h3>
        </a>

        <!-- Reviews -->
        <a href="index.php?page=reviews" class="menu-item <?php echo $currentPage === 'reviews' ? 'active' : ''; ?>">
            <span class="material-symbols-sharp">reviews</span>
            <h3>Reviews</h3>
        </a>

        <!-- Check-ins -->
        <a href="index.php?page=check-ins" class="menu-item <?php echo $currentPage === 'check-ins' ? 'active' : ''; ?>">
            <span class="material-symbols-sharp">check_circle</span>
            <h3>Check-ins</h3>
        </a>

        <!-- Settings -->
        <a href="index.php?page=settings" class="menu-item <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
            <span class="material-symbols-sharp">settings</span>
            <h3>Settings</h3>
        </a>

        <!-- Logout -->
        <a href="index.php?page=logout" class="menu-item logout-link">
            <span class="material-symbols-sharp">logout</span>
            <h3>Log Out</h3>
        </a>
    </nav>

    <div class="user-profile-section">
        <div class="user-info">
            <div class="profile-photo">
                <img src="assets/images/user-avatar.png" alt="User" onerror="this.src='https://ui-avatars.com/api/?name=User&background=7c3aed&color=fff'">
            </div>
            <div class="user-details">
                <p><b>John Doe</b></p>
                <small class="text-muted">Admin</small>
            </div>
        </div>
        <!-- Theme Toggler -->
        <div class="theme-toggler" id="dark-mode-toggle">
            <span class="material-symbols-sharp active">light_mode</span>
            <span class="material-symbols-sharp">dark_mode</span>
        </div>
    </div>
</aside>