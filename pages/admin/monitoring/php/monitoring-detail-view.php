<?php
require_once __DIR__ . '/monitoring-functions.php';

$monitoringFunctions = new MonitoringFunctions();
$monitoringFunctions->checkAuthentication();

// Detail Desa View with Server-side Pagination
$desa_id = $monitoringFunctions->validateDesaId($_GET['desa'] ?? null);
$desa_data = $monitoringFunctions->getDesaById($desa_id);
$statistics = $monitoringFunctions->calculateDetailStatistics($desa_id, $monitoringFunctions->getStatistikPenduduk($desa_id));

$is_ajax = $monitoringFunctions->isAjaxRequest();

if (!$is_ajax) {
    $page_title = 'Detail Desa ' . $desa_data['nama_desa'];
    $current_page = 'detail-desa';
    $page_css = [BASE_URL . 'pages/admin/monitoring/monitoring.css'];
    $page_js = [BASE_URL . 'pages/admin/monitoring/js/monitoring-detail-pagination.js'];
    ob_start();
}

// Helper functions
function renderInfoItem($label, $value)
{
    return '<div class="info-item"><label>' . $label . ':</label><span>' . $value . '</span></div>';
}

function renderStatCard($number, $label)
{
    return '<div class="stat-card"><div class="stat-number">' . $number . '</div><div class="stat-label">' . $label . '</div></div>';
}

function renderTableSectionPaginated($title, $searchId, $tableId, $dataType, $headers)
{
    $html = '<div class="data-table-section" data-type="' . $dataType . '">';
    $html .= '<div class="table-header">';
    $html .= '<h4>' . $title . '</h4>';
    $html .= '<input type="text" id="' . $searchId . '" placeholder="Cari ' . strtolower($title) . '..." class="search-input">';
    $html .= '</div>';
    $html .= '<div class="table-responsive">';
    $html .= '<table class="data-table" id="' . $tableId . '">';
    $html .= '<thead><tr>';
    foreach ($headers as $header) {
        $html .= '<th>' . $header . '</th>';
    }
    $html .= '</tr></thead>';
    $html .= '<tbody class="table-loading">';
    $html .= '<tr><td colspan="' . count($headers) . '" style="text-align: center; padding: 32px;"><i class="fas fa-spinner fa-spin"></i> Memuat data...</td></tr>';
    $html .= '</tbody>';
    $html .= '</table></div>';
    
    // Pagination controls
    $html .= '<div class="pagination-controls" id="' . $tableId . 'Pagination">';
    $html .= '<div class="pagination-info">';
    $html .= '<select class="items-per-page" data-table="' . $tableId . '">';
    $html .= '<option value="5">5 per halaman</option>';
    $html .= '<option value="10" selected>10 per halaman</option>';
    $html .= '<option value="25">25 per halaman</option>';
    $html .= '<option value="50">50 per halaman</option>';
    $html .= '</select>';
    $html .= '<span class="pagination-text">Menampilkan <span class="start-item">0</span>-<span class="end-item">0</span> dari <span class="total-items">0</span> data</span>';
    $html .= '</div>';
    $html .= '<div class="pagination-buttons">';
    $html .= '<button class="pagination-btn prev-btn" data-table="' . $tableId . '" disabled><i class="fas fa-chevron-left"></i></button>';
    $html .= '<span class="page-info">Halaman <span class="current-page">1</span> dari <span class="total-pages">1</span></span>';
    $html .= '<button class="pagination-btn next-btn" data-table="' . $tableId . '" disabled><i class="fas fa-chevron-right"></i></button>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    return $html;
}
?>

<div class="detail-desa-container" data-desa-id="<?php echo $desa_id; ?>">
    <div class="detail-desa-header">
        <div class="detail-desa-breadcrumb">
            <a href="<?php echo BASE_URL; ?>pages/admin/monitoring/monitoring.php">← Kembali ke Monitoring</a>
        </div>
        <h2>Detail Data <?php echo $desa_data['nama_desa']; ?></h2>
    </div>

    <!-- Data Umum Desa -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-map-marker-alt"></i> Data Umum Desa</h3>
        </div>
        <div class="section-content">
            <div class="info-grid">
                <?php echo renderInfoItem('Nama Desa', $desa_data['nama_desa']); ?>
                <?php echo renderInfoItem('Kecamatan', $desa_data['nama_kecamatan']); ?>
                <?php echo renderInfoItem('Kabupaten', $desa_data['nama_kabupaten']); ?>
                <?php echo renderInfoItem('Provinsi', $desa_data['nama_provinsi']); ?>
                <?php echo renderInfoItem('Luas Wilayah', $desa_data['luas_wilayah'] . ' Ha'); ?>
                <?php echo renderInfoItem('Jumlah Dusun', $desa_data['jumlah_dusun']); ?>
                <?php echo renderInfoItem('Jumlah RT', $desa_data['jumlah_rt']); ?>
                <?php echo renderInfoItem('Jumlah RW', $desa_data['jumlah_rw']); ?>
            </div>
        </div>
    </div>

    <!-- Data Kependudukan -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-users"></i> Data Kependudukan</h3>
        </div>
        <div class="section-content">
            <div class="stats-grid">
                <?php echo renderStatCard(number_format($statistics['total_penduduk']), 'Total Penduduk'); ?>
                <?php echo renderStatCard(number_format($statistics['total_kk']), 'Total KK'); ?>
                <?php echo renderStatCard($statistics['rata_per_kk'], 'Rata-rata per KK'); ?>
                <?php echo renderStatCard($statistics['kepadatan'], 'Kepadatan (jiwa/km²)'); ?>
            </div>

            <?php echo renderTableSectionPaginated(
                'Daftar Penduduk',
                'searchPenduduk',
                'pendudukTable',
                'penduduk',
                ['NIK', 'Nama Lengkap', 'JK', 'Usia', 'Pendidikan', 'Pekerjaan']
            ); ?>
        </div>
    </div>

    <!-- Data Pendidikan -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-school"></i> Data Pendidikan</h3>
        </div>
        <div class="section-content">
            <?php echo renderTableSectionPaginated(
                'Fasilitas Pendidikan',
                'searchPendidikan',
                'pendidikanTable',
                'fasilitas',
                ['Nama Fasilitas', 'Jenis', 'Alamat', 'Kapasitas', 'Kondisi']
            ); ?>
        </div>
    </div>

    <!-- Data Ekonomi -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-chart-line"></i> Data Ekonomi</h3>
        </div>
        <div class="section-content">
            <?php echo renderTableSectionPaginated(
                'UMKM',
                'searchUMKM',
                'umkmTable',
                'umkm',
                ['Nama Usaha', 'Jenis', 'Modal', 'Omzet/Bulan', 'Karyawan']
            ); ?>

            <?php echo renderTableSectionPaginated(
                'Pasar',
                'searchPasar',
                'pasarTable',
                'pasar',
                ['Nama Pasar', 'Jenis', 'Jumlah Pedagang', 'Hari Operasional', 'Kondisi']
            ); ?>
        </div>
    </div>

    <!-- Data Infrastruktur -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-road"></i> Data Infrastruktur</h3>
        </div>
        <div class="section-content">
            <?php echo renderTableSectionPaginated(
                'Jalan Desa',
                'searchJalan',
                'jalanTable',
                'jalan',
                ['Nama Jalan', 'Panjang (m)', 'Lebar (m)', 'Kondisi', 'Jenis Permukaan']
            ); ?>

            <?php echo renderTableSectionPaginated(
                'Jembatan',
                'searchJembatan',
                'jembatanTable',
                'jembatan',
                ['Nama Jembatan', 'Panjang (m)', 'Lebar (m)', 'Kondisi', 'Material']
            ); ?>
        </div>
    </div>
</div>

<?php
if (!$is_ajax) {
    $content = ob_get_clean();
    require_once __DIR__ . '/../../../../layout/main.php';
}
?>