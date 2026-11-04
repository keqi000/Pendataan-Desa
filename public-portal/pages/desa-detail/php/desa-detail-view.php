<?php
// Desa Detail View - Sistem Informasi Publik
// File: public-portal/pages/desa-detail/php/desa-detail-view.php

require_once __DIR__ . '/desa-detail-functions.php';

$fn       = new DesaDetailFunctions();
$id_desa  = isset($_GET['id']) ? (int)$_GET['id'] : null;
$desa_slug = $_GET['desa'] ?? ($desa_slug ?? '');

// Prioritas: id langsung, fallback ke slug
if ($id_desa) {
    $desa = $fn->getDesaById($id_desa);
} elseif ($desa_slug) {
    $desa = $fn->getDesaBySlug($desa_slug);
    $id_desa = $desa['id_desa'] ?? null;
} else {
    // Default: desa pertama
    $list    = $fn->getAllDesaList();
    $first   = $list[0] ?? [];
    $id_desa = $first['id_desa'] ?? null;
    $desa    = $id_desa ? $fn->getDesaById($id_desa) : [];
}

$fasilitas = $id_desa ? $fn->getFasilitasDesa($id_desa) : [];
$umkm      = $id_desa ? $fn->getUmkmDesa($id_desa)     : [];
$infra     = $id_desa ? $fn->getInfrastrukturDesa($id_desa) : [];
$infra     = array_replace([
    'jalan_baik' => 0,
    'jalan_sedang' => 0,
    'jalan_rusak' => 0,
    'jembatan' => 0
], $infra);
$usia      = $id_desa ? $fn->getStatistikUsia($id_desa) : [];
$desaList  = $fn->getAllDesaList();

$nama_desa    = $desa['nama_desa']     ?? '-';
$total_penduduk = $desa['total_penduduk'] ?? 0;
$total_kk     = $desa['total_kk']      ?? 0;
$total_laki   = $desa['total_laki']    ?? 0;
$total_perempuan = $desa['total_perempuan'] ?? 0;
$luas         = $desa['luas_wilayah']  ?? 0;
$jumlah_dusun = $desa['jumlah_dusun']  ?? 0;
$jumlah_rt    = $desa['jumlah_rt']     ?? 0;
$jumlah_rw    = $desa['jumlah_rw']     ?? 0;

$jenisIcon = ['SD' => 'fa-school', 'SMP' => 'fa-school', 'SMA' => 'fa-university', 'SMK' => 'fa-university', 'TK' => 'fa-child', 'PAUD' => 'fa-child'];
?>

<!-- Page Header -->
<section class="desa-hero">
    <div class="container">
        <div class="desa-hero-content">
            <div class="breadcrumb">
                <a href="<?php echo BASE_URL_PUBLIC; ?>">Beranda</a>
                <i class="fas fa-chevron-right"></i>
                <span>Profil Desa</span>
                <i class="fas fa-chevron-right"></i>
                <span><?php echo htmlspecialchars($nama_desa); ?></span>
            </div>
            <h1><i class="fas fa-map-marker-alt"></i> Desa <?php echo htmlspecialchars($nama_desa); ?></h1>
            <p>Kecamatan Tibawa, Kabupaten Gorontalo Utara</p>
        </div>
    </div>
</section>

<!-- Quick Stats -->
<section class="desa-stats-section">
    <div class="container">
        <div class="desa-stats-grid">
            <div class="desa-stat-card">
                <i class="fas fa-users"></i>
                <div class="desa-stat-value"><?php echo number_format($total_penduduk); ?></div>
                <div class="desa-stat-label">Total Penduduk</div>
            </div>
            <div class="desa-stat-card">
                <i class="fas fa-home"></i>
                <div class="desa-stat-value"><?php echo number_format($total_kk); ?></div>
                <div class="desa-stat-label">Kepala Keluarga</div>
            </div>
            <div class="desa-stat-card">
                <i class="fas fa-male"></i>
                <div class="desa-stat-value"><?php echo number_format($total_laki); ?></div>
                <div class="desa-stat-label">Laki-laki</div>
            </div>
            <div class="desa-stat-card">
                <i class="fas fa-female"></i>
                <div class="desa-stat-value"><?php echo number_format($total_perempuan); ?></div>
                <div class="desa-stat-label">Perempuan</div>
            </div>
            <div class="desa-stat-card">
                <i class="fas fa-map"></i>
                <div class="desa-stat-value"><?php echo $luas; ?> km²</div>
                <div class="desa-stat-label">Luas Wilayah</div>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<section class="desa-content">
    <div class="container">
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-8">

                <!-- Grafik Distribusi Usia -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-pie"></i> Distribusi Usia</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="usiaChart" height="250"
                            data-labels='<?php echo json_encode($usia["labels"] ?? []); ?>'
                            data-values='<?php echo json_encode($usia["data"]   ?? []); ?>'>
                        </canvas>
                    </div>
                </div>

                <!-- Fasilitas -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-building"></i> Fasilitas Desa</h3>
                    </div>
                    <div class="card-body">
                        <div class="fasilitas-grid">
                            <?php foreach ($fasilitas as $f): ?>
                                <?php if ((int)$f['jumlah'] > 0): ?>
                                <div class="fasilitas-item">
                                    <div class="fasilitas-icon">
                                        <i class="fas <?php echo $jenisIcon[$f['jenis']] ?? 'fa-school'; ?>"></i>
                                    </div>
                                    <div class="fasilitas-info">
                                        <span class="fasilitas-jenis"><?php echo $f['jenis']; ?></span>
                                        <span class="fasilitas-jumlah"><?php echo $f['jumlah']; ?> unit</span>
                                        <?php if (!empty($f['kapasitas'])): ?>
                                        <span class="fasilitas-kapasitas">Kapasitas: <?php echo number_format($f['kapasitas']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if (empty($fasilitas)): ?>
                            <p class="text-muted">Tidak ada data fasilitas</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- UMKM -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-store"></i> UMKM Desa</h3>
                    </div>
                    <div class="card-body">
                        <div class="umkm-list">
                            <?php foreach ($umkm as $u): ?>
                            <div class="umkm-item">
                                <div class="umkm-icon"><i class="fas fa-store"></i></div>
                                <div class="umkm-info">
                                    <span class="umkm-nama"><?php echo htmlspecialchars($u['nama_usaha']); ?></span>
                                    <span class="umkm-jenis"><?php echo $u['jenis_usaha']; ?></span>
                                </div>
                                <span class="umkm-karyawan"><i class="fas fa-users"></i> <?php echo $u['jumlah_karyawan']; ?> orang</span>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($umkm)): ?>
                            <p class="text-muted">Tidak ada data UMKM</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-4">

                <!-- Info Wilayah -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle"></i> Info Wilayah</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-list">
                            <div class="info-row">
                                <span class="info-label">Jumlah Dusun</span>
                                <span class="info-value"><?php echo $jumlah_dusun; ?> dusun</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Jumlah RT</span>
                                <span class="info-value"><?php echo $jumlah_rt; ?> RT</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Jumlah RW</span>
                                <span class="info-value"><?php echo $jumlah_rw; ?> RW</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Kepadatan</span>
                                <span class="info-value"><?php echo $luas > 0 ? number_format($total_penduduk / $luas, 0) : '-'; ?> jiwa/km²</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Infrastruktur -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-road"></i> Infrastruktur Jalan</h3>
                    </div>
                    <div class="card-body">
                        <div class="infra-bar">
                            <?php
                            $total_jalan = $infra['jalan_baik'] + $infra['jalan_sedang'] + $infra['jalan_rusak'];
                            $pct = fn($v) => $total_jalan > 0 ? round($v / $total_jalan * 100) : 0;
                            ?>
                            <div class="infra-bar-item">
                                <span>Baik (<?php echo $infra['jalan_baik']; ?>)</span>
                                <div class="bar-track"><div class="bar-fill bar-baik" style="width:<?php echo $pct($infra['jalan_baik']); ?>%"></div></div>
                                <span><?php echo $pct($infra['jalan_baik']); ?>%</span>
                            </div>
                            <div class="infra-bar-item">
                                <span>Sedang (<?php echo $infra['jalan_sedang']; ?>)</span>
                                <div class="bar-track"><div class="bar-fill bar-sedang" style="width:<?php echo $pct($infra['jalan_sedang']); ?>%"></div></div>
                                <span><?php echo $pct($infra['jalan_sedang']); ?>%</span>
                            </div>
                            <div class="infra-bar-item">
                                <span>Rusak (<?php echo $infra['jalan_rusak']; ?>)</span>
                                <div class="bar-track"><div class="bar-fill bar-rusak" style="width:<?php echo $pct($infra['jalan_rusak']); ?>%"></div></div>
                                <span><?php echo $pct($infra['jalan_rusak']); ?>%</span>
                            </div>
                            <?php if ($total_jalan === 0): ?>
                            <p class="text-muted">Tidak ada data jalan</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Navigasi Desa Lain -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-map-marked-alt"></i> Desa Lainnya</h3>
                    </div>
                    <div class="card-body">
                        <div class="desa-nav-list">
                            <?php foreach ($desaList as $d): ?>
                                <?php if ((int)$d['id_desa'] !== (int)$id_desa): ?>
                                <a href="?id=<?php echo $d['id_desa']; ?>" class="desa-nav-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($d['nama']); ?>
                                </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
