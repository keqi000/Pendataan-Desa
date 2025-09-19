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
                <a href="#" class="sidebar-nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" 
                   onclick="loadPage('dashboard')">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <?php if ($_SESSION['user_role'] === 'operator_desa'): ?>
            <!-- Menu Operator Desa -->
            <li class="sidebar-nav-item">
                <a href="#" class="sidebar-nav-link <?php echo $current_page === 'data-umum' ? 'active' : ''; ?>"
                   onclick="loadPage('data-umum')">
                    <i class="fas fa-info-circle"></i>
                    <span>Data Umum Desa</span>
                </a>
            </li>
            
            <li class="sidebar-nav-item">
                <a href="#" class="sidebar-nav-link <?php echo $current_page === 'kependudukan' ? 'active' : ''; ?>"
                   onclick="loadPage('kependudukan')">
                    <i class="fas fa-users"></i>
                    <span>Kependudukan</span>
                </a>
            </li>
            
            <li class="sidebar-nav-item">
                <a href="#" class="sidebar-nav-link <?php echo $current_page === 'ekonomi' ? 'active' : ''; ?>"
                   onclick="loadPage('ekonomi')">
                    <i class="fas fa-chart-line"></i>
                    <span>Ekonomi</span>
                </a>
            </li>
            
            <li class="sidebar-nav-item">
                <a href="#" class="sidebar-nav-link <?php echo $current_page === 'pendidikan' ? 'active' : ''; ?>"
                   onclick="loadPage('pendidikan')">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Pendidikan</span>
                </a>
            </li>
            
            <li class="sidebar-nav-item">
                <a href="#" class="sidebar-nav-link <?php echo $current_page === 'infrastruktur' ? 'active' : ''; ?>"
                   onclick="loadPage('infrastruktur')">
                    <i class="fas fa-road"></i>
                    <span>Infrastruktur</span>
                </a>
            </li>
            

            
            <?php else: ?>
            <!-- Menu Admin Kecamatan -->
            <li class="sidebar-nav-item">
                <a href="#" class="sidebar-nav-link <?php echo $current_page === 'monitoring' ? 'active' : ''; ?>"
                   onclick="loadPage('monitoring')">
                    <i class="fas fa-desktop"></i>
                    <span>Monitoring Desa</span>
                </a>
            </li>
            
            <li class="sidebar-nav-item">
                <a href="#" class="sidebar-nav-link <?php echo $current_page === 'analisis' ? 'active' : ''; ?>"
                   onclick="loadPage('analisis')">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analisis Data</span>
                </a>
            </li>
            
            <li class="sidebar-nav-item">
                <a href="#" class="sidebar-nav-link <?php echo $current_page === 'laporan' ? 'active' : ''; ?>"
                   onclick="loadPage('laporan')">
                    <i class="fas fa-file-alt"></i>
                    <span>Laporan</span>
                </a>
            </li>
            
            <li class="sidebar-nav-item">
                <a href="#" class="sidebar-nav-link <?php echo $current_page === 'peta' ? 'active' : ''; ?>"
                   onclick="loadPage('peta')">
                    <i class="fas fa-map"></i>
                    <span>Peta & Geografis</span>
                </a>
            </li>
            
            <li class="sidebar-nav-item">
                <a href="#" class="sidebar-nav-link <?php echo $current_page === 'pengaturan' ? 'active' : ''; ?>"
                   onclick="loadPage('pengaturan')">
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