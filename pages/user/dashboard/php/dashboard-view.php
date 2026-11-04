<?php
require_once __DIR__ . '/dashboard-functions.php';
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/session.php';

// Simple session check for regular page load
if (!initSession() || !validateSession()) {
    header('Location: ' . BASE_URL . 'pages/auth/login.php');
    exit();
}

$dashboardFunctions = new UserDashboardFunctions();
$dashboardFunctions->checkAuthentication();

$desa_data = $dashboardFunctions->getDesaData($_SESSION['user_desa']);
$stats_penduduk = $dashboardFunctions->getStatistikPenduduk($_SESSION['user_desa']);
$keluarga_data = $dashboardFunctions->getKeluargaData($_SESSION['user_desa']);
$fasilitas_pendidikan = $dashboardFunctions->getFasilitasPendidikan($_SESSION['user_desa']);
$umkm_data = $dashboardFunctions->getUMKMData($_SESSION['user_desa']);
$jalan_data = $dashboardFunctions->getJalanData($_SESSION['user_desa']);
$jembatan_data = $dashboardFunctions->getJembatanData($_SESSION['user_desa']);

$total_penduduk = $stats_penduduk['total'] ?? 0;
$total_kk = count($keluarga_data);
$fasilitas_text = $dashboardFunctions->calculateFasilitasText($fasilitas_pendidikan);
$total_sektor = $dashboardFunctions->calculateSektorUMKM($umkm_data);
$persentase_jalan_baik = $dashboardFunctions->calculatePersentaseJalanBaik($jalan_data);
$anak_sekolah = $dashboardFunctions->calculateAnakSekolah($stats_penduduk);
$persentase_miskin = 4.7;
?>

<div class="dashboard-user-container">
    <div class="dashboard-user-header">
        <div class="dashboard-user-welcome-card">
            <div class="dashboard-user-welcome-content">
                <h2>Selamat Datang, <?php echo $_SESSION['user_name']; ?></h2>
                <p>Desa <?php echo $_SESSION['desa_name']; ?></p>
                <div class="dashboard-user-last-update">
                    <i class="fas fa-clock"></i>
                    Terakhir diperbarui: <?php echo date('d F Y, H:i'); ?>
                </div>
            </div>
            <div class="dashboard-user-welcome-icon">
                <i class="fas fa-home"></i>
            </div>
        </div>
    </div>
    
    <div class="dashboard-user-stats">
        <div class="card dashboard-user-stat-card">
            <div class="card-body">
                <div class="dashboard-user-stat-icon">
                    <i class="fas fa-school"></i>
                </div>
                <div class="dashboard-user-stat-content">
                    <div class="dashboard-user-stat-title">Fasilitas Pendidikan</div>
                    <div class="dashboard-user-stat-number"><?php echo $fasilitas_text; ?></div>
                    <div class="dashboard-user-stat-label">PAUD sampai SMP</div>
                </div>
            </div>
        </div>
        
        <div class="card dashboard-user-stat-card">
            <div class="card-body">
                <div class="dashboard-user-stat-icon">
                    <i class="fas fa-store"></i>
                </div>
                <div class="dashboard-user-stat-content">
                    <div class="dashboard-user-stat-title">UMKM Aktif</div>
                    <div class="dashboard-user-stat-number"><?php echo count($umkm_data); ?></div>
                    <div class="dashboard-user-stat-label"><?php echo $total_sektor; ?> sektor usaha</div>
                </div>
            </div>
        </div>
        
        <div class="card dashboard-user-stat-card">
            <div class="card-body">
                <div class="dashboard-user-stat-icon">
                    <i class="fas fa-road"></i>
                </div>
                <div class="dashboard-user-stat-content">
                    <div class="dashboard-user-stat-title">Infrastruktur</div>
                    <div class="dashboard-user-stat-number"><?php echo $persentase_jalan_baik; ?>%</div>
                    <div class="dashboard-user-stat-label">Kondisi baik</div>
                </div>
            </div>
        </div>
        
        <div class="card dashboard-user-stat-card">
            <div class="card-body">
                <div class="dashboard-user-stat-icon">
                    <i class="fas fa-home"></i>
                </div>
                <div class="dashboard-user-stat-content">
                    <div class="dashboard-user-stat-title">Kepala Keluarga</div>
                    <div class="dashboard-user-stat-number"><?php echo $total_kk; ?></div>
                    <div class="dashboard-user-stat-label">Total keluarga</div>
                </div>
            </div>
        </div>
        
        <div class="card dashboard-user-stat-card">
            <div class="card-body">
                <div class="dashboard-user-stat-icon">
                    <i class="fas fa-hand-holding-heart"></i>
                </div>
                <div class="dashboard-user-stat-content">
                    <div class="dashboard-user-stat-title">Warga Miskin</div>
                    <div class="dashboard-user-stat-number"><?php echo $persentase_miskin; ?>%</div>
                    <div class="dashboard-user-stat-label">dari total penduduk</div>
                </div>
            </div>
        </div>
        
        <div class="card dashboard-user-stat-card">
            <div class="card-body">
                <div class="dashboard-user-stat-icon">
                    <i class="fas fa-map"></i>
                </div>
                <div class="dashboard-user-stat-content">
                    <div class="dashboard-user-stat-title">Luas Wilayah</div>
                    <div class="dashboard-user-stat-number"><?php echo number_format($desa_data['luas_wilayah'] ?? 0); ?></div>
                    <div class="dashboard-user-stat-label">hektar</div>
                </div>
            </div>
        </div>
        
        <div class="card dashboard-user-stat-card">
            <div class="card-body">
                <div class="dashboard-user-stat-icon">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <div class="dashboard-user-stat-content">
                    <div class="dashboard-user-stat-title">Dusun/RT/RW</div>
                    <div class="dashboard-user-stat-number"><?php echo ($desa_data['jumlah_dusun'] ?? 0) . '/' . ($desa_data['jumlah_rt'] ?? 0) . '/' . ($desa_data['jumlah_rw'] ?? 0); ?></div>
                    <div class="dashboard-user-stat-label">wilayah administratif</div>
                </div>
            </div>
        </div>
        
        <div class="card dashboard-user-stat-card">
            <div class="card-body">
                <div class="dashboard-user-stat-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="dashboard-user-stat-content">
                    <div class="dashboard-user-stat-title">Usia Sekolah</div>
                    <div class="dashboard-user-stat-number"><?php echo $anak_sekolah; ?></div>
                    <div class="dashboard-user-stat-label">Anak usia 7–18 tahun</div>
                </div>
            </div>
        </div>
        
        <div class="card dashboard-user-stat-card">
            <div class="card-body">
                <div class="dashboard-user-stat-icon">
                    <i class="fas fa-bridge"></i>
                </div>
                <div class="dashboard-user-stat-content">
                    <div class="dashboard-user-stat-title">Jembatan</div>
                    <div class="dashboard-user-stat-number"><?php echo count($jembatan_data); ?></div>
                    <div class="dashboard-user-stat-label">Unit infrastruktur</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-user-content">
        <div class="card">
            <div class="card-header">
                <h3>Progress Data</h3>
            </div>
            <div class="card-body">
                <div class="dashboard-user-progress-item">
                    <div class="dashboard-user-progress-label">
                        <span>Data Kependudukan</span>
                        <span class="dashboard-user-progress-percent"><?php echo $total_penduduk > 0 ? min(100, round(($total_penduduk / 50) * 100)) : 0; ?>%</span>
                    </div>
                    <div class="dashboard-user-progress-bar">
                        <div class="dashboard-user-progress-fill" style="width: <?php echo $total_penduduk > 0 ? min(100, round(($total_penduduk / 50) * 100)) : 0; ?>%"></div>
                    </div>
                </div>
                
                <div class="dashboard-user-progress-item">
                    <div class="dashboard-user-progress-label">
                        <span>Data Ekonomi</span>
                        <span class="dashboard-user-progress-percent"><?php echo $total_sektor > 0 ? min(100, round(($total_sektor / 5) * 100)) : 0; ?>%</span>
                    </div>
                    <div class="dashboard-user-progress-bar">
                        <div class="dashboard-user-progress-fill" style="width: <?php echo $total_sektor > 0 ? min(100, round(($total_sektor / 5) * 100)) : 0; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>