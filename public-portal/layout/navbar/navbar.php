<?php
// Navbar Component untuk Sistem Informasi Publik
// File: public-portal/layout/navbar/navbar.php

$current_page = $current_page ?? '';
?>

<nav class="public-navbar">
    <div class="container">
        <div class="navbar-content">
            <div class="navbar-brand">
                <i class="fas fa-chart-bar"></i>
                <span>Data Terbuka</span>
            </div>
            
            <div class="navbar-toggle" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
            
            <ul class="navbar-menu" id="navbarMenu">
                <li class="nav-item">
                    <a href="<?php echo BASE_URL_PUBLIC; ?>" class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i>
                        <span>Beranda</span>
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle <?php echo in_array($current_page, ['desa-detail']) ? 'active' : ''; ?>">
                        <i class="fas fa-map-marked-alt"></i>
                        <span>Profil Desa</span>
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo BASE_URL_PUBLIC; ?>pages/desa-detail/desa-detail.php?desa=ilomata">Ilomata</a></li>
                        <li><a href="<?php echo BASE_URL_PUBLIC; ?>pages/desa-detail/desa-detail.php?desa=molowahu">Molowahu</a></li>
                        <li><a href="<?php echo BASE_URL_PUBLIC; ?>pages/desa-detail/desa-detail.php?desa=dunggala">Dunggala</a></li>
                        <li><a href="<?php echo BASE_URL_PUBLIC; ?>pages/desa-detail/desa-detail.php?desa=reksonegoro">Reksonegoro</a></li>
                        <li><a href="<?php echo BASE_URL_PUBLIC; ?>pages/desa-detail/desa-detail.php?desa=tolotio">Tolotio</a></li>
                        <li><a href="<?php echo BASE_URL_PUBLIC; ?>pages/desa-detail/desa-detail.php?desa=isimu_selatan">Isimu Selatan</a></li>
                        <li><a href="<?php echo BASE_URL_PUBLIC; ?>pages/desa-detail/desa-detail.php?desa=datahu">Datahu</a></li>
                        <li><a href="<?php echo BASE_URL_PUBLIC; ?>pages/desa-detail/desa-detail.php?desa=iloponu">Iloponu</a></li>
                        <li><a href="<?php echo BASE_URL_PUBLIC; ?>pages/desa-detail/desa-detail.php?desa=buhu">Buhu</a></li>
                        <li><a href="<?php echo BASE_URL_PUBLIC; ?>pages/desa-detail/desa-detail.php?desa=isimu_utara">Isimu Utara</a></li>
                        <li><a href="<?php echo BASE_URL_PUBLIC; ?>pages/desa-detail/desa-detail.php?desa=labanu">Labanu</a></li>
                        <li><a href="<?php echo BASE_URL_PUBLIC; ?>pages/desa-detail/desa-detail.php?desa=motilango">Motilango</a></li>
                        <li><a href="<?php echo BASE_URL_PUBLIC; ?>pages/desa-detail/desa-detail.php?desa=balahu">Balahu</a></li>
                        <li><a href="<?php echo BASE_URL_PUBLIC; ?>pages/desa-detail/desa-detail.php?desa=botumoputi">Botumoputi</a></li>
                        <li><a href="<?php echo BASE_URL_PUBLIC; ?>pages/desa-detail/desa-detail.php?desa=isimu_raya">Isimu Raya</a></li>
                        <li><a href="<?php echo BASE_URL_PUBLIC; ?>pages/desa-detail/desa-detail.php?desa=ulobua">Ulobua</a></li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo BASE_URL_PUBLIC; ?>pages/fasilitas/fasilitas.php" class="nav-link <?php echo $current_page === 'fasilitas' ? 'active' : ''; ?>">
                        <i class="fas fa-building"></i>
                        <span>Fasilitas</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo BASE_URL_PUBLIC; ?>pages/umkm/umkm.php" class="nav-link <?php echo $current_page === 'umkm' ? 'active' : ''; ?>">
                        <i class="fas fa-store"></i>
                        <span>UMKM</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo BASE_URL_PUBLIC; ?>api/docs.php" class="nav-link" target="_blank">
                        <i class="fas fa-code"></i>
                        <span>API</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo PENDATAAN_LOGIN_URL; ?>" class="nav-link nav-link-login">
                        <span>Login Admin</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>