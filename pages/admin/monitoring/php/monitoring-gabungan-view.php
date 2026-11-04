<?php
require_once __DIR__ . '/monitoring-functions.php';

$monitoringFunctions = new MonitoringFunctions();
$monitoringFunctions->checkAuthentication();

// Detail Gabungan Semua Desa View with Server-side Pagination
$statistics = $monitoringFunctions->calculateCombinedStatistics($monitoringFunctions->getAllCombinedData());

$is_ajax = $monitoringFunctions->isAjaxRequest();

if (!$is_ajax) {
    $page_title = 'Detail Gabungan Semua Desa';
    $current_page = 'detail-gabungan';
    $page_css = [BASE_URL . 'pages/admin/monitoring/monitoring.css'];
    $page_js = [BASE_URL . 'pages/admin/monitoring/js/monitoring-gabungan-pagination.js'];
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

<div class="detail-desa-container">
    <div class="detail-desa-header">
        <div class="detail-desa-breadcrumb">
            <a href="<?php echo BASE_URL; ?>pages/admin/monitoring/monitoring.php">← Kembali ke Monitoring</a>
        </div>
        <h2>Detail Gabungan Semua Desa</h2>
    </div>

    <!-- Data Umum Gabungan -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-map-marker-alt"></i> Data Umum Gabungan</h3>
        </div>
        <div class="section-content">
            <div class="info-grid">
                <?php echo renderInfoItem('Total Desa', $statistics['total_desa']); ?>
                <?php echo renderInfoItem('Total Luas Wilayah', number_format($statistics['total_luas']) . ' Ha'); ?>
                <?php echo renderInfoItem('Total Dusun', $statistics['total_dusun']); ?>
                <?php echo renderInfoItem('Total RT', $statistics['total_rt']); ?>
                <?php echo renderInfoItem('Total RW', $statistics['total_rw']); ?>
            </div>
        </div>
    </div>

    <!-- Data Kependudukan Gabungan -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-users"></i> Data Kependudukan Gabungan</h3>
        </div>
        <div class="section-content">
            <div class="stats-grid">
                <?php echo renderStatCard(number_format($statistics['total_penduduk']), 'Total Penduduk'); ?>
                <?php echo renderStatCard(number_format($statistics['total_kk']), 'Total KK'); ?>
                <?php echo renderStatCard($statistics['rata_per_kk'], 'Rata-rata per KK'); ?>
                <?php echo renderStatCard($statistics['kepadatan'], 'Kepadatan (jiwa/km²)'); ?>
            </div>

            <?php echo renderTableSectionPaginated(
                'Daftar Semua Penduduk',
                'searchPenduduk',
                'pendudukTable',
                'penduduk',
                ['NIK', 'Nama Lengkap', 'Desa', 'JK', 'Usia', 'Pendidikan', 'Pekerjaan']
            ); ?>
        </div>
    </div>

    <!-- Data Pendidikan Gabungan -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-school"></i> Data Pendidikan Gabungan</h3>
        </div>
        <div class="section-content">
            <?php echo renderTableSectionPaginated(
                'Semua Fasilitas Pendidikan',
                'searchPendidikan',
                'pendidikanTable',
                'fasilitas',
                ['Nama Fasilitas', 'Desa', 'Jenis', 'Alamat', 'Kapasitas', 'Kondisi']
            ); ?>
        </div>
    </div>

    <!-- Data Ekonomi Gabungan -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-chart-line"></i> Data Ekonomi Gabungan</h3>
        </div>
        <div class="section-content">
            <?php echo renderTableSectionPaginated(
                'Semua UMKM',
                'searchUMKM',
                'umkmTable',
                'umkm',
                ['Nama Usaha', 'Desa', 'Jenis', 'Modal', 'Omzet/Bulan', 'Karyawan']
            ); ?>

            <?php echo renderTableSectionPaginated(
                'Semua Pasar',
                'searchPasar',
                'pasarTable',
                'pasar',
                ['Nama Pasar', 'Desa', 'Jenis', 'Jumlah Pedagang', 'Hari Operasional', 'Kondisi']
            ); ?>
        </div>
    </div>

    <!-- Data Infrastruktur Gabungan -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-road"></i> Data Infrastruktur Gabungan</h3>
        </div>
        <div class="section-content">
            <?php echo renderTableSectionPaginated(
                'Semua Jalan Desa',
                'searchJalan',
                'jalanTable',
                'jalan',
                ['Nama Jalan', 'Desa', 'Panjang (m)', 'Lebar (m)', 'Kondisi', 'Jenis Permukaan']
            ); ?>

            <?php echo renderTableSectionPaginated(
                'Semua Jembatan',
                'searchJembatan',
                'jembatanTable',
                'jembatan',
                ['Nama Jembatan', 'Desa', 'Panjang (m)', 'Lebar (m)', 'Kondisi', 'Material']
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