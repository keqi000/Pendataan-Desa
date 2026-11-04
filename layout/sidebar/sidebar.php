<?php
require_once __DIR__ . '/../../config/config.php';
?>
<aside class="sidebar-main-sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="fas fa-map-marked-alt"></i>
            <span>Pendataan Desa</span>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="sidebar-nav-list">
            <li class="sidebar-nav-item">
                <a href="<?php echo BASE_URL; ?>pages/<?php echo $_SESSION['user_role'] === 'admin_kecamatan' ? 'admin' : 'user'; ?>/dashboard/dashboard.php" 
                   class="sidebar-nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <?php if ($_SESSION['user_role'] === 'operator_desa'): ?>
            <!-- Menu Operator Desa -->
            <li class="sidebar-nav-item">
                <a href="<?php echo BASE_URL; ?>pages/user/data-umum/data-umum.php" 
                   class="sidebar-nav-link <?php echo $current_page === 'data-umum' ? 'active' : ''; ?>">
                    <i class="fas fa-info-circle"></i>
                    <span>Data Umum Desa</span>
                </a>
            </li>
            
            <li class="sidebar-nav-item">
                <a href="<?php echo BASE_URL; ?>pages/user/kependudukan/kependudukan.php" 
                   class="sidebar-nav-link <?php echo $current_page === 'kependudukan' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Kependudukan</span>
                </a>
            </li>
            
            <li class="sidebar-nav-item">
                <a href="<?php echo BASE_URL; ?>pages/user/ekonomi/ekonomi.php" 
                   class="sidebar-nav-link <?php echo $current_page === 'ekonomi' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Ekonomi</span>
                </a>
            </li>
            
            <li class="sidebar-nav-item">
                <a href="<?php echo BASE_URL; ?>pages/user/pendidikan/pendidikan.php" 
                   class="sidebar-nav-link <?php echo $current_page === 'pendidikan' ? 'active' : ''; ?>">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Pendidikan</span>
                </a>
            </li>
            
            <li class="sidebar-nav-item">
                <a href="<?php echo BASE_URL; ?>pages/user/infrastruktur/infrastruktur.php" 
                   class="sidebar-nav-link <?php echo $current_page === 'infrastruktur' ? 'active' : ''; ?>">
                    <i class="fas fa-road"></i>
                    <span>Infrastruktur</span>
                </a>
            </li>
            
            <li class="sidebar-nav-item">
                <a href="<?php echo BASE_URL; ?>pages/user/pengaturan/pengaturan.php" 
                   class="sidebar-nav-link <?php echo $current_page === 'pengaturan' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Pengaturan</span>
                </a>
            </li>
            
            <?php else: ?>
            <!-- Menu Admin Kecamatan -->
            <li class="sidebar-nav-item">
                <a href="<?php echo BASE_URL; ?>pages/admin/monitoring/monitoring.php" 
                   class="sidebar-nav-link <?php echo $current_page === 'monitoring' ? 'active' : ''; ?>">
                    <i class="fas fa-desktop"></i>
                    <span>Monitoring Desa</span>
                </a>
            </li>
            
            <li class="sidebar-nav-item">
                <a href="<?php echo BASE_URL; ?>pages/admin/analisis/analisis.php" 
                   class="sidebar-nav-link <?php echo $current_page === 'analisis' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analisis Data</span>
                </a>
            </li>
            
            <li class="sidebar-nav-item">
                <a href="<?php echo BASE_URL; ?>pages/admin/laporan/laporan.php" 
                   class="sidebar-nav-link <?php echo $current_page === 'laporan' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Laporan</span>
                </a>
            </li>
            
            <li class="sidebar-nav-item">
                <a href="<?php echo BASE_URL; ?>pages/admin/peta/peta.php" 
                   class="sidebar-nav-link <?php echo $current_page === 'peta' ? 'active' : ''; ?>">
                    <i class="fas fa-map"></i>
                    <span>Peta & Geografis</span>
                </a>
            </li>
            
            <li class="sidebar-nav-item">
                <a href="<?php echo BASE_URL; ?>pages/admin/pengaturan/pengaturan.php" 
                   class="sidebar-nav-link <?php echo $current_page === 'pengaturan' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Pengaturan</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <div class="sidebar-user-info">
            <div class="sidebar-user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="sidebar-user-details">
                <div class="sidebar-user-name"><?php echo $_SESSION['user_name']; ?></div>
                <div class="sidebar-user-role"><?php echo ucfirst(str_replace('_', ' ', $_SESSION['user_role'])); ?></div>
            </div>
        </div>
    </div>
</aside>