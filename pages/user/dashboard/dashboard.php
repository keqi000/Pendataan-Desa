<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../database/queries.php';
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'operator_desa') {
    header('Location: ' . BASE_URL . 'pages/auth/login.php');
    exit();
}

$queries = new Queries();
$desa_data = $queries->getDesaById($_SESSION['user_desa']);
$stats_penduduk = $queries->getStatistikPenduduk($_SESSION['user_desa']);
$keluarga_data = $queries->getKeluargaByDesa($_SESSION['user_desa']);
$data_ekonomi = $queries->getDataEkonomi($_SESSION['user_desa']);
$fasilitas_pendidikan = $queries->getFasilitasPendidikan($_SESSION['user_desa']);
$umkm_data = $queries->getUMKM($_SESSION['user_desa']);
$jalan_data = $queries->getInfrastrukturJalan($_SESSION['user_desa']);
$jembatan_data = $queries->getInfrastrukturJembatan($_SESSION['user_desa']);

// Debug logging
error_log('🏠 [DASHBOARD-PHP] User Desa ID: ' . $_SESSION['user_desa']);
error_log('🏠 [DASHBOARD-PHP] Keluarga Count: ' . count($keluarga_data));
error_log('🏠 [DASHBOARD-PHP] Keluarga Data: ' . json_encode($keluarga_data));

// Calculate dashboard statistics
$total_penduduk = $stats_penduduk['total'] ?? 0;
$total_kk = count($keluarga_data);
$rata_anggota_kk = $total_kk > 0 ? round($total_penduduk / $total_kk, 1) : 0;

// Count facilities by type
$fasilitas_count = [];
foreach ($fasilitas_pendidikan as $fasilitas) {
    $jenis = $fasilitas['jenis_pendidikan'];
    $fasilitas_count[$jenis] = ($fasilitas_count[$jenis] ?? 0) + 1;
}
$fasilitas_text = implode(', ', array_map(function($k, $v) { return "$k($v)"; }, array_keys($fasilitas_count), $fasilitas_count)) ?: 'Belum ada data';

// Count UMKM sectors
$sektor_umkm = [];
foreach ($umkm_data as $umkm) {
    $jenis = $umkm['jenis_usaha'];
    $sektor_umkm[$jenis] = ($sektor_umkm[$jenis] ?? 0) + 1;
}
$total_sektor = count($sektor_umkm);

// Calculate road condition
$jalan_baik = 0;
$total_panjang = 0;
foreach ($jalan_data as $jalan) {
    $total_panjang += $jalan['panjang_jalan'];
    if ($jalan['kondisi_jalan'] === 'baik') {
        $jalan_baik += $jalan['panjang_jalan'];
    }
}
$persentase_jalan_baik = $total_panjang > 0 ? round(($jalan_baik / $total_panjang) * 100) : 0;

// Count children of school age (7-18 years)
$anak_sekolah = 0;
foreach ($stats_penduduk['kelompok_usia'] ?? [] as $kelompok) {
    if (in_array($kelompok['kelompok_usia'], ['Anak', 'Remaja'])) {
        $anak_sekolah += $kelompok['jumlah'];
    }
}

// Calculate poor population percentage (placeholder - needs warga_miskin table)
$persentase_miskin = 4.7; // Default value as per requirement

// Check if this is AJAX request
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    // Full page load
    $page_title = 'Dashboard Desa';
    $current_page = 'dashboard';
    $page_css = ['dashboard.css'];
    $page_js = ['dashboard.js'];
    
    ob_start();
}
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
                    <div class="dashboard-user-stat-number"><?php echo $total_sektor; ?></div>
                    <div class="dashboard-user-stat-label">sektor usaha</div>
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
                    <i class="fas fa-road"></i>
                </div>
                <div class="dashboard-user-stat-content">
                    <div class="dashboard-user-stat-title">Jalan Desa</div>
                    <div class="dashboard-user-stat-number"><?php echo $persentase_jalan_baik; ?>%</div>
                    <div class="dashboard-user-stat-label">kondisi baik</div>
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
                    <i class="fas fa-briefcase"></i>
                </div>
                <div class="dashboard-user-stat-content">
                    <div class="dashboard-user-stat-title">Sektor Usaha</div>
                    <div class="dashboard-user-stat-number"><?php echo $total_sektor; ?></div>
                    <div class="dashboard-user-stat-label">Jenis bidang usaha</div>
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
        <div class="dashboard-user-left">
            <div class="card">
                <div class="card-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="dashboard-user-quick-actions">
                        <a href="#" class="dashboard-user-action-btn" onclick="loadPage('kependudukan')">
                            <i class="fas fa-user-plus"></i>
                            <span>Tambah Penduduk</span>
                        </a>
                        <a href="#" class="dashboard-user-action-btn" onclick="loadPage('ekonomi')">
                            <i class="fas fa-chart-line"></i>
                            <span>Data Ekonomi</span>
                        </a>

                    </div>
                </div>
            </div>
        </div>
        
        <div class="dashboard-user-right">
            <div class="card">
                <div class="card-header">
                    <h3>Progress Data</h3>
                </div>
                <div class="card-body">
                    <div class="dashboard-user-progress-item">
                        <div class="dashboard-user-progress-label">
                            <span>Data Kependudukan</span>
                            <span class="dashboard-user-progress-percent"><?php echo min(100, ($total_penduduk / 50) * 100); ?>%</span>
                        </div>
                        <div class="dashboard-user-progress-bar">
                            <div class="dashboard-user-progress-fill" style="width: <?php echo min(100, ($total_penduduk / 50) * 100); ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="dashboard-user-progress-item">
                        <div class="dashboard-user-progress-label">
                            <span>Data Ekonomi</span>
                            <span class="dashboard-user-progress-percent"><?php echo min(100, ($total_sektor / 5) * 100); ?>%</span>
                        </div>
                        <div class="dashboard-user-progress-bar">
                            <div class="dashboard-user-progress-fill" style="width: <?php echo min(100, ($total_sektor / 5) * 100); ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_ajax) {
    $content = ob_get_clean();
    require_once __DIR__ . '/../../../layout/main.php';
}
?>