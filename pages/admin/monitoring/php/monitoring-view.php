<?php
require_once __DIR__ . '/monitoring-functions.php';

$monitoringFunctions = new MonitoringFunctions();
$monitoringFunctions->checkAuthentication();

// Check if this is a detail-desa request
$desa_id = $_GET['desa'] ?? null;
$gabungan = $_GET['gabungan'] ?? null;

if ($gabungan === 'semua') {
    // Detail Gabungan Semua Desa View with Server-side Pagination
    require_once __DIR__ . '/monitoring-gabungan-view.php';
    return;
}

if ($desa_id) {
    // Detail Desa View with Server-side Pagination
    require_once __DIR__ . '/monitoring-detail-view.php';
    return;
}

// Regular Monitoring View
$all_desa = $monitoringFunctions->getAllDesa();
$is_ajax = $monitoringFunctions->isAjaxRequest();

if (!$is_ajax) {
    $page_title = 'Monitoring Desa';
    $current_page = 'monitoring';
    $page_css = [BASE_URL . 'pages/admin/monitoring/monitoring.css'];
    $page_js = [
        BASE_URL . 'pages/admin/monitoring/js/monitoring-core.js',
        BASE_URL . 'pages/admin/monitoring/js/monitoring-status.js',
        BASE_URL . 'pages/admin/monitoring/js/monitoring-comparison.js',
        BASE_URL . 'pages/admin/monitoring/monitoring.js'
    ];

    ob_start();
}

$monitoring_stats = $monitoringFunctions->calculateMonitoringStats($all_desa);
?>

<div class="monitoring-admin-container">
    <div class="monitoring-admin-header">
        <div class="monitoring-admin-title">
            <h2>Monitoring Data Desa</h2>
            <p>Pantau status kelengkapan dan kualitas data dari semua desa</p>
        </div>
        <div class="monitoring-admin-actions">
            <button class="btn btn-primary" onclick="refreshAllData()">
                <i class="fas fa-sync-alt"></i>
                Refresh Data
            </button>
        </div>
    </div>

    <div class="monitoring-admin-stats">
        <div class="monitoring-admin-stat-card">
            <div class="monitoring-admin-stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="monitoring-admin-stat-content">
                <div class="monitoring-admin-stat-number"><?php echo $monitoring_stats['data_lengkap']; ?></div>
                <div class="monitoring-admin-stat-label">Data Lengkap</div>
            </div>
        </div>

        <div class="monitoring-admin-stat-card">
            <div class="monitoring-admin-stat-icon warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="monitoring-admin-stat-content">
                <div class="monitoring-admin-stat-number"><?php echo $monitoring_stats['perlu_update']; ?></div>
                <div class="monitoring-admin-stat-label">Perlu Update</div>
            </div>
        </div>

        <div class="monitoring-admin-stat-card">
            <div class="monitoring-admin-stat-icon danger">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="monitoring-admin-stat-content">
                <div class="monitoring-admin-stat-number"><?php echo $monitoring_stats['data_kosong']; ?></div>
                <div class="monitoring-admin-stat-label">Data Kosong</div>
            </div>
        </div>
    </div>

    <div class="monitoring-admin-tabs">
        <div class="monitoring-admin-tab-nav">
            <button class="monitoring-admin-tab-btn active" onclick="switchMonitoringTab('status')">
                Status Data Per Desa
            </button>
            <button class="monitoring-admin-tab-btn" onclick="switchMonitoringTab('perbandingan')">
                Perbandingan Desa
            </button>
            <button class="monitoring-admin-tab-btn" onclick="switchMonitoringTab('quality')">
                Quality Check
            </button>
        </div>
    </div>

    <div class="monitoring-admin-content">
        <!-- Status Data Tab -->
        <div class="monitoring-admin-tab-content active" id="status">
            <div class="card">
                <div class="card-header">
                    <h3>Status Data Per Desa</h3>
                    <div class="monitoring-admin-filters">
                        <button class="btn btn-info" onclick="navigateToGabunganDesa()" style="margin-right: 10px;">
                            <i class="fas fa-layer-group"></i> Detail Gabungan Semua Desa
                        </button>
                        <select class="form-control" id="statusFilter" onchange="filterByStatus()">
                            <option value="">Semua Status</option>
                            <option value="complete">Lengkap</option>
                            <option value="incomplete">Tidak Lengkap</option>
                            <option value="outdated">Perlu Update</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="monitoringTable">
                            <thead>
                                <tr>
                                    <th>Nama Desa</th>
                                    <th>Data Kependudukan</th>
                                    <th>Data Ekonomi</th>
                                    <th>Data Pendidikan</th>
                                    <th>Data Infrastruktur</th>
                                    <th>Last Update</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_desa)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="no-data">
                                                <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                                                <h5 class="text-muted">Tidak Ada Data</h5>
                                                <p class="text-muted">Belum ada data desa yang tersedia</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($all_desa as $desa):
                                        $desa_data = $monitoringFunctions->getDesaDataForTable($desa);
                                        ?>
                                        <tr data-desa="<?php echo $desa['id_desa']; ?>">
                                            <td><strong><?php echo htmlspecialchars($desa['nama_desa']); ?></strong></td>
                                            <td><?php echo $desa_data['total_penduduk']; ?> orang</td>
                                            <td><?php echo $desa_data['total_ekonomi']; ?> data</td>
                                            <td><?php echo $desa_data['total_fasilitas']; ?> fasilitas</td>
                                            <td><?php echo $desa_data['total_jalan']; ?> jalan</td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($desa['created_at'])); ?></td>
                                            <td><span
                                                    class="badge <?php echo $desa_data['status_class']; ?>"><?php echo $desa_data['status_text']; ?></span>
                                            </td>
                                            <td>
                                                <button class="monitoring-btn-view"
                                                    onclick="navigateToDetailDesa(<?php echo $desa['id_desa']; ?>, '<?php echo htmlspecialchars($desa['nama_desa']); ?>')">
                                                    <i class="fas fa-eye"></i> Detail
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

        <!-- Perbandingan Tab -->
        <div class="monitoring-admin-tab-content" id="perbandingan">
            <div class="tab-loading" style="text-align: center; padding: 50px;">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p>Memuat data perbandingan...</p>
            </div>
        </div>

        <!-- Quality Check Tab -->
        <div class="monitoring-admin-tab-content" id="quality">
            <div class="tab-loading" style="text-align: center; padding: 50px;">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p>Memuat data quality check...</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Desa Detail -->
<div id="desaDetailModal" class="modal" style="display: none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Data Desa</h5>
                <button type="button" class="close" onclick="closeDesaModal()">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="desaDetailContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>Memuat data...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_ajax) {
    $content = ob_get_clean();
    require_once __DIR__ . '/../../../../layout/main.php';
}
?>