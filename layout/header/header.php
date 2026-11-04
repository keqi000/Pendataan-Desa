<?php
require_once __DIR__ . '/../../config/config.php';
?>
<header class="header-main-header">
    <div class="header-left">
        <button class="header-sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="header-page-title"><?php echo $page_title ?? 'Dashboard'; ?></h1>
    </div>
    
    <div class="header-right">
        <button class="header-theme-toggle" title="Toggle Theme" onclick="if(window.toggleTheme) window.toggleTheme()">
            <i class="fas fa-moon"></i>
        </button>
        
        <div class="header-user-menu">
            <button class="header-user-menu-toggle" onclick="toggleUserMenu()">
                <i class="fas fa-user-circle"></i>
            </button>
            <div class="header-user-menu-dropdown" id="userMenuDropdown">
                <a href="<?php echo BASE_URL; ?>pages/<?php echo $_SESSION['user_role'] === 'admin_kecamatan' ? 'admin' : 'user'; ?>/pengaturan/pengaturan.php" class="header-dropdown-item">
                    <i class="fas fa-user"></i>
                    Profil Pengguna
                </a>
                <a href="#" class="header-dropdown-item" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </div>
</header>