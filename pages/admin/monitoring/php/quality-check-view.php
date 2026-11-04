<?php
// Quality Check View dengan BPS Integration
// File: pages/admin/monitoring/php/quality-check-view.php

require_once __DIR__ . '/bps-integration-service.php';

$bpsService        = new BPSIntegrationService();
$monitoringFunctions = new MonitoringFunctions();

// Ambil data real dari BPS API
$bps_data = $bpsService->getAllDesaComparison();

// Hitung total internal dari semua desa
$all_desa = $monitoringFunctions->getAllDesa();
$total_penduduk_internal = 0;
$total_kk_internal       = 0;
$total_laki_internal     = 0;
$total_perempuan_internal = 0;

$desa_rows = [];
foreach ($all_desa as $desa) {
    $d = $monitoringFunctions->getDesaDataForTable($desa);
    $total_penduduk_internal  += (int)$d['total_penduduk'];
    $total_kk_internal        += (int)$d['total_kk'];

    // Hitung laki/perempuan per desa dari statistik penduduk
    $stats = $monitoringFunctions->getStatistikPenduduk($desa['id_desa']);
    $laki = 0; $perempuan = 0;
    foreach (($stats['jenis_kelamin'] ?? []) as $jk) {
        $val    = (int)$jk['jumlah'];
        $gender = strtoupper(trim($jk['jenis_kelamin'] ?? ''));
        if ($gender === 'L') $laki = $val;
        else                 $perempuan = $val;
    }
    $total_laki_internal      += $laki;
    $total_perempuan_internal += $perempuan;

    $desa_rows[] = [
        'nama_desa'  => $desa['nama_desa'],
        'penduduk'   => (int)$d['total_penduduk'],
        'kk'         => (int)$d['total_kk'],
        'laki'       => $laki,
        'perempuan'  => $perempuan,
    ];
}

// Data BPS level kecamatan
$bps_ok          = $bps_data['success'] ?? false;
$bps_penduduk    = $bps_ok ? (int)$bps_data['bps_penduduk'] : 0;
$bps_kk          = $bps_ok ? (int)$bps_data['bps_kk']       : 0;
$bps_laki        = $bps_ok ? (int)$bps_data['bps_laki']      : 0;
$bps_perempuan   = $bps_ok ? (int)$bps_data['bps_perempuan'] : 0;
$bps_tahun       = $bps_ok ? $bps_data['tahun']              : '-';
$bps_sumber      = $bps_ok ? $bps_data['sumber']             : 'BPS';
$bps_fetched     = $bps_ok ? $bps_data['fetched_at']         : '-';

// Selisih agregat
$selisih_penduduk  = $total_penduduk_internal - $bps_penduduk;
$selisih_kk        = $total_kk_internal - $bps_kk;
$selisih_laki      = $total_laki_internal - $bps_laki;
$selisih_perempuan = $total_perempuan_internal - $bps_perempuan;

$persen_selisih = $bps_penduduk > 0
    ? round((abs($selisih_penduduk) / $bps_penduduk) * 100, 1)
    : 0;
$akurasi = max(0, 100 - $persen_selisih);
?>

<div class="quality-check-container">
    <!-- Header Section -->
    <div class="quality-check-header">
        <div class="quality-check-title">
            <h3><i class="fas fa-shield-alt"></i> Quality Check - Validasi Data BPS</h3>
            <p>Perbandingan data internal desa dengan data resmi BPS Kabupaten Gorontalo tahun <?php echo $bps_tahun; ?></p>
        </div>
    </div>

    <?php if (!$bps_ok): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> Gagal mengambil data dari BPS API. Pastikan koneksi internet tersedia.
    </div>
    <?php endif; ?>

    <!-- Dashboard Cards -->
    <div class="quality-check-dashboard">
        <div class="quality-check-card info">
            <div class="quality-check-card-icon"><i class="fas fa-users"></i></div>
            <div class="quality-check-card-content">
                <div class="quality-check-card-number"><?php echo number_format($total_penduduk_internal); ?></div>
                <div class="quality-check-card-label">Total Penduduk Internal</div>
                <div class="quality-check-card-desc">Dari <?php echo count($all_desa); ?> desa</div>
            </div>
        </div>
        <div class="quality-check-card <?php echo $bps_ok ? 'success' : 'danger'; ?>">
            <div class="quality-check-card-icon"><i class="fas fa-database"></i></div>
            <div class="quality-check-card-content">
                <div class="quality-check-card-number"><?php echo number_format($bps_penduduk); ?></div>
                <div class="quality-check-card-label">Total Penduduk BPS</div>
                <div class="quality-check-card-desc">Kec. Tibawa <?php echo $bps_tahun; ?></div>
            </div>
        </div>
        <div class="quality-check-card <?php echo abs($selisih_penduduk) > 1000 ? 'warning' : 'success'; ?>">
            <div class="quality-check-card-icon"><i class="fas fa-exchange-alt"></i></div>
            <div class="quality-check-card-content">
                <div class="quality-check-card-number"><?php echo ($selisih_penduduk >= 0 ? '+' : '') . number_format($selisih_penduduk); ?></div>
                <div class="quality-check-card-label">Selisih Penduduk</div>
                <div class="quality-check-card-desc">Internal vs BPS</div>
            </div>
        </div>
        <div class="quality-check-card info">
            <div class="quality-check-card-icon"><i class="fas fa-percentage"></i></div>
            <div class="quality-check-card-content">
                <div class="quality-check-card-number"><?php echo $akurasi; ?>%</div>
                <div class="quality-check-card-label">Akurasi Keseluruhan</div>
                <div class="quality-check-card-desc">vs Data BPS</div>
            </div>
        </div>
    </div>

    <!-- Summary Section -->
    <div class="quality-check-summary">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-chart-bar"></i> Ringkasan Perbandingan — Kecamatan Tibawa</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="summary-item">
                            <label>Total Penduduk:</label>
                            <span class="summary-value">
                                Internal: <strong><?php echo number_format($total_penduduk_internal); ?></strong> &nbsp;|&nbsp;
                                BPS <?php echo $bps_tahun; ?>: <strong><?php echo number_format($bps_penduduk); ?></strong>
                            </span>
                        </div>
                        <div class="summary-item">
                            <label>Laki-laki:</label>
                            <span class="summary-value">
                                Internal: <strong><?php echo number_format($total_laki_internal); ?></strong> &nbsp;|&nbsp;
                                BPS: <strong><?php echo number_format($bps_laki); ?></strong>
                            </span>
                        </div>
                        <div class="summary-item">
                            <label>Perempuan:</label>
                            <span class="summary-value">
                                Internal: <strong><?php echo number_format($total_perempuan_internal); ?></strong> &nbsp;|&nbsp;
                                BPS: <strong><?php echo number_format($bps_perempuan); ?></strong>
                            </span>
                        </div>
                        <div class="summary-item">
                            <label>Total KK:</label>
                            <span class="summary-value">
                                Internal: <strong><?php echo number_format($total_kk_internal); ?></strong> &nbsp;|&nbsp;
                                BPS: <strong><?php echo number_format($bps_kk); ?></strong>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="summary-item">
                            <label>Selisih Penduduk:</label>
                            <span class="summary-value <?php echo abs($selisih_penduduk) > 1000 ? 'text-danger' : 'text-success'; ?>">
                                <?php echo ($selisih_penduduk >= 0 ? '+' : '') . number_format($selisih_penduduk); ?> orang
                            </span>
                        </div>
                        <div class="summary-item">
                            <label>Selisih Laki-laki:</label>
                            <span class="summary-value <?php echo abs($selisih_laki) > 500 ? 'text-danger' : 'text-success'; ?>">
                                <?php echo ($selisih_laki >= 0 ? '+' : '') . number_format($selisih_laki); ?> orang
                            </span>
                        </div>
                        <div class="summary-item">
                            <label>Selisih Perempuan:</label>
                            <span class="summary-value <?php echo abs($selisih_perempuan) > 500 ? 'text-danger' : 'text-success'; ?>">
                                <?php echo ($selisih_perempuan >= 0 ? '+' : '') . number_format($selisih_perempuan); ?> orang
                            </span>
                        </div>
                        <div class="summary-item">
                            <label>Selisih KK:</label>
                            <span class="summary-value <?php echo abs($selisih_kk) > 500 ? 'text-danger' : 'text-success'; ?>">
                                <?php echo ($selisih_kk >= 0 ? '+' : '') . number_format($selisih_kk); ?> KK
                            </span>
                        </div>
                        <div class="summary-item">
                            <label>Sumber BPS:</label>
                            <span class="summary-value text-muted"><?php echo htmlspecialchars($bps_sumber); ?></span>
                        </div>
                        <div class="summary-item">
                            <label>Diambil pada:</label>
                            <span class="summary-value text-muted"><?php echo $bps_fetched; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Comparison Table dihapus: BPS tidak punya data breakdown per desa -->

    <!-- BPS Data Source Info -->
    <div class="quality-check-footer">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-12 text-center">
                        <h6><i class="fas fa-info-circle"></i> Sumber Data</h6>
                        <p class="mb-0">
                            Data pembanding bersumber dari <strong>Badan Pusat Statistik (BPS) Kabupaten Gorontalo</strong>
                            tahun <?php echo $bps_tahun; ?> melalui integrasi API real-time.
                            <br><small class="text-muted">Sumber: <?php echo htmlspecialchars($bps_sumber); ?> &mdash; Diambil: <?php echo $bps_fetched; ?></small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
