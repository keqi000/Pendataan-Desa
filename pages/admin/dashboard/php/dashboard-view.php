<?php
require_once __DIR__ . '/dashboard-functions.php';
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/session.php';

// Simple session check for regular page load
if (!initSession() || !validateSession()) {
    header('Location: ' . BASE_URL . 'pages/auth/login.php');
    exit();
}

$dashboardFunctions = new DashboardFunctions();
$dashboardFunctions->checkAuthentication();

$all_desa = $dashboardFunctions->getAllDesa();
$aggregated_stats = $dashboardFunctions->calculateAggregatedStats($all_desa);
$derived_stats = $dashboardFunctions->calculateDerivedStats($aggregated_stats);
$available_years = $dashboardFunctions->getAvailableYears();

// Extract variables for easier access
$total_desa = $aggregated_stats['total_desa'];
$total_penduduk = $aggregated_stats['total_penduduk'];
$total_kk = $aggregated_stats['total_kk'];
$total_fasilitas_pendidikan = $aggregated_stats['total_fasilitas_pendidikan'];
$total_umkm = $aggregated_stats['total_umkm'];
$total_jalan_baik = $aggregated_stats['total_jalan_baik'];
$total_panjang_jalan = $aggregated_stats['total_panjang_jalan'];
$total_jembatan = $aggregated_stats['total_jembatan'];
$total_luas_wilayah = $aggregated_stats['total_luas_wilayah'];
$total_dusun = $aggregated_stats['total_dusun'];
$total_rt = $aggregated_stats['total_rt'];
$total_rw = $aggregated_stats['total_rw'];
$total_anak_sekolah = $aggregated_stats['total_anak_sekolah'];
$total_warga_miskin = $aggregated_stats['total_warga_miskin'];

$rata_anggota_kk = $derived_stats['rata_anggota_kk'];
$persentase_jalan_baik = $derived_stats['persentase_jalan_baik'];
$jumlah_sektor_usaha = $derived_stats['jumlah_sektor_usaha'];
$persentase_miskin = $derived_stats['persentase_miskin'];

// Calculate trend data
$current_year = date('Y');
$selected_year = $current_year;
$selected_period = 'jan-jun';

if ($selected_period == 'jan-jun') {
    $start_month = 1;
    $end_month = 6;
} else {
    $start_month = 7;
    $end_month = 12;
}

$trend_data = $dashboardFunctions->getTrendData($selected_year, $start_month, $end_month);
$monthly_data = $dashboardFunctions->getMonthlyData($selected_year, $start_month, $end_month);
?>

<div class="dashboard-admin-container">
    <div class="dashboard-admin-header">
        <div class="dashboard-admin-welcome-card">
            <div class="dashboard-admin-welcome-content">
                <h2>Selamat Datang, <?php echo $_SESSION['user_name']; ?></h2>
                <p>Admin Kecamatan Tibawa</p>
                <div class="dashboard-admin-last-update">
                    <i class="fas fa-clock"></i>
                    Terakhir diperbarui: <?php echo date('d F Y, H:i'); ?>
                </div>
            </div>
            <div class="dashboard-admin-welcome-icon">
                <i class="fas fa-building"></i>
            </div>
        </div>
    </div>

    <div class="dashboard-admin-stats">
        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-school"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">Fasilitas Pendidikan</div>
                    <div class="dashboard-admin-stat-number"><?php echo $total_fasilitas_pendidikan; ?></div>
                    <div class="dashboard-admin-stat-label">PAUD sampai SMP</div>
                </div>
            </div>
        </div>

        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-store"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">UMKM Aktif</div>
                    <div class="dashboard-admin-stat-number"><?php echo $jumlah_sektor_usaha; ?></div>
                    <div class="dashboard-admin-stat-label">sektor usaha</div>
                </div>
            </div>
        </div>

        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-road"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">Infrastruktur</div>
                    <div class="dashboard-admin-stat-number"><?php echo $persentase_jalan_baik; ?>%</div>
                    <div class="dashboard-admin-stat-label">Kondisi baik</div>
                </div>
            </div>
        </div>

        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-home"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">Kepala Keluarga</div>
                    <div class="dashboard-admin-stat-number"><?php echo $rata_anggota_kk; ?></div>
                    <div class="dashboard-admin-stat-label">Rata-rata anggota/KK</div>
                </div>
            </div>
        </div>

        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-hand-holding-heart"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">Warga Miskin</div>
                    <div class="dashboard-admin-stat-number"><?php echo $persentase_miskin; ?>%</div>
                    <div class="dashboard-admin-stat-label">dari total penduduk</div>
                </div>
            </div>
        </div>

        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-road"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">Jalan Desa</div>
                    <div class="dashboard-admin-stat-number"><?php echo $persentase_jalan_baik; ?>%</div>
                    <div class="dashboard-admin-stat-label">kondisi baik</div>
                </div>
            </div>
        </div>

        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-map"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">Luas Wilayah</div>
                    <div class="dashboard-admin-stat-number"><?php echo number_format($total_luas_wilayah); ?></div>
                    <div class="dashboard-admin-stat-label">hektar</div>
                </div>
            </div>
        </div>

        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">Dusun/RT/RW</div>
                    <div class="dashboard-admin-stat-number">
                        <?php echo $total_dusun . '/' . $total_rt . '/' . $total_rw; ?>
                    </div>
                    <div class="dashboard-admin-stat-label">wilayah administratif</div>
                </div>
            </div>
        </div>

        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">Usia Sekolah</div>
                    <div class="dashboard-admin-stat-number"><?php echo $total_anak_sekolah; ?></div>
                    <div class="dashboard-admin-stat-label">Anak usia 7–18 tahun</div>
                </div>
            </div>
        </div>

        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">Sektor Usaha</div>
                    <div class="dashboard-admin-stat-number"><?php echo $jumlah_sektor_usaha; ?></div>
                    <div class="dashboard-admin-stat-label">Jenis bidang usaha</div>
                </div>
            </div>
        </div>

        <div class="card dashboard-admin-stat-card">
            <div class="card-body">
                <div class="dashboard-admin-stat-icon">
                    <i class="fas fa-bridge"></i>
                </div>
                <div class="dashboard-admin-stat-content">
                    <div class="dashboard-admin-stat-title">Jembatan</div>
                    <div class="dashboard-admin-stat-number"><?php echo $total_jembatan; ?></div>
                    <div class="dashboard-admin-stat-label">Unit infrastruktur</div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-admin-content">
        <div class="card">
            <div class="card-header">
                <h3>Tren Data 6 Bulan Terakhir</h3>
                <p class="card-subtitle">Tren Pertumbuhan Data Komprehensif</p>
                <div class="trend-filters">
                    <select class="form-control" id="yearFilter" onchange="updateTrendData()">
                        <?php foreach ($available_years as $year): ?>
                            <option value="<?php echo $year['tahun']; ?>" <?php echo $year['tahun'] == date('Y') ? 'selected' : ''; ?>>
                                <?php echo $year['tahun']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select class="form-control" id="periodFilter" onchange="updateTrendData()">
                        <option value="jan-jun">Jan - Jun</option>
                        <option value="jul-des">Jul - Des</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <p class="trend-description">Menampilkan tren semua kategori data</p>
                <div class="dashboard-admin-trends">
                    <div class="trend-charts">
                        <div class="trend-chart-item">
                            <h5 style="text-align: center; margin-bottom: 15px;">Tren 6 Bulan (%)</h5>
                            <canvas id="trendBarChart" width="400" height="200"></canvas>
                        </div>
                        <div class="trend-chart-item">
                            <h5 style="text-align: center; margin-bottom: 15px;">Progres Bulanan</h5>
                            <canvas id="trendLineChart" width="400" height="200"></canvas>
                        </div>
                    </div>

                    <script>
                        window.trendData = {
                            desa: <?php echo $trend_data['desa']; ?>,
                            penduduk: <?php echo $trend_data['penduduk']; ?>,
                            pendidikan: <?php echo $trend_data['pendidikan']; ?>,
                            umkm: <?php echo $trend_data['umkm']; ?>,
                            jalan: <?php echo $trend_data['jalan']; ?>
                        };

                        window.monthlyData = <?php echo json_encode($monthly_data); ?>;
                        window.selectedYear = <?php echo $selected_year; ?>;
                        window.selectedPeriod = '<?php echo $selected_period; ?>';
                    </script>

                    <div class="trend-summary">
                        <div class="trend-item">
                            <div class="trend-value <?php echo $trend_data['desa'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo ($trend_data['desa'] >= 0 ? '+' : '') . $trend_data['desa']; ?>%
                            </div>
                            <div class="trend-label">Desa</div>
                        </div>
                        <div class="trend-item">
                            <div class="trend-value <?php echo $trend_data['penduduk'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo ($trend_data['penduduk'] >= 0 ? '+' : '') . $trend_data['penduduk']; ?>%
                            </div>
                            <div class="trend-label">Penduduk</div>
                        </div>
                        <div class="trend-item">
                            <div class="trend-value <?php echo $trend_data['pendidikan'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo ($trend_data['pendidikan'] >= 0 ? '+' : '') . $trend_data['pendidikan']; ?>%
                            </div>
                            <div class="trend-label">Pendidikan</div>
                        </div>
                        <div class="trend-item">
                            <div class="trend-value <?php echo $trend_data['umkm'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo ($trend_data['umkm'] >= 0 ? '+' : '') . $trend_data['umkm']; ?>%
                            </div>
                            <div class="trend-label">UMKM</div>
                        </div>
                        <div class="trend-item">
                            <div class="trend-value <?php echo $trend_data['jalan'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo ($trend_data['jalan'] >= 0 ? '+' : '') . $trend_data['jalan']; ?>%
                            </div>
                            <div class="trend-label">Jalan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Overview Desa</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Desa</th>
                                <th>Status Data</th>
                                <th>Last Update</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_desa)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <div class="no-data">
                                            <i class="fas fa-map-marked-alt fa-3x mb-3 text-muted"></i>
                                            <h5 class="text-muted">Tidak Ada Data</h5>
                                            <p class="text-muted">Belum ada data desa yang tersedia</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($all_desa as $desa): ?>
                                    <?php
                                    $completeness = $dashboardFunctions->getDesaDataCompleteness($desa['id_desa']);
                                    ?>
                                    <tr>
                                        <td><?php echo $desa['nama_desa']; ?></td>
                                        <td><span class="badge badge-<?php echo $completeness['badge_class']; ?>"><?php echo $completeness['status']; ?></span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($desa['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary"
                                                onclick="showDesaOverview(<?php echo $desa['id_desa']; ?>, '<?php echo $desa['nama_desa']; ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Overview Desa -->
<div class="modal-overview" id="desaOverviewModal" style="display: none;">
    <div class="modal-overview-backdrop" onclick="closeDesaOverview()"></div>
    <div class="modal-overview-dialog">
        <div class="modal-overview-content">
            <div class="modal-overview-header">
                <h3 class="modal-overview-title">Overview Desa</h3>
                <button class="modal-overview-close" onclick="closeDesaOverview()">&times;</button>
            </div>
            <div class="modal-overview-body" id="desaOverviewContent">
                <div class="loading-text">Loading...</div>
            </div>
        </div>
    </div>
</div>