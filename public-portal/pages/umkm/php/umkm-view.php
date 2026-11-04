<?php
// UMKM View - Sistem Informasi Publik
// File: public-portal/pages/umkm/php/umkm-view.php

require_once __DIR__ . '/umkm-functions.php';

$fn        = new UmkmFunctions();
$jenisSel  = $_GET['jenis']   ?? '';
$id_desa   = isset($_GET['id_desa']) ? (int)$_GET['id_desa'] : null;
$search    = $_GET['search']  ?? '';
$umkm      = $fn->getAll($jenisSel ?: null, $id_desa, $search ?: null);
$jenisList = $fn->getJenisList();
$desaList  = $fn->getDesaList();
$kategori  = $fn->getKategoriSummary();

$jenisIcon  = ['Kuliner' => 'fa-utensils', 'Pertanian' => 'fa-seedling', 'Kerajinan' => 'fa-paint-brush', 'Perdagangan' => 'fa-shopping-cart', 'Jasa' => 'fa-tools'];
$jenisColor = ['Kuliner' => 'primary', 'Pertanian' => 'success', 'Kerajinan' => 'info', 'Perdagangan' => 'warning', 'Jasa' => 'danger'];
?>

<!-- Page Header -->
<section class="page-hero">
    <div class="container">
        <h1><i class="fas fa-store"></i> Direktori UMKM</h1>
        <p>Data usaha mikro, kecil, dan menengah di Kecamatan Tibawa</p>
    </div>
</section>

<!-- Kategori Summary -->
<section class="summary-section">
    <div class="container">
        <div class="summary-grid">
            <?php foreach ($kategori as $jenis => $jumlah): ?>
            <div class="summary-card" data-jenis="<?php echo $jenis; ?>">
                <i class="fas <?php echo $jenisIcon[$jenis] ?? 'fa-store'; ?>"></i>
                <div class="summary-value"><?php echo $jumlah; ?></div>
                <div class="summary-label"><?php echo $jenis; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Filter & List -->
<section class="umkm-section">
    <div class="container">
        <!-- Filter -->
        <div class="filter-bar">
            <form method="GET" class="filter-form" id="umkmFilterForm">
                <div class="filter-group">
                    <label>Cari UMKM</label>
                    <input type="text" name="search" class="form-control" placeholder="Nama usaha..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <label>Kategori</label>
                    <select name="jenis" class="form-control" onchange="this.form.submit()">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($jenisList as $j): ?>
                        <option value="<?php echo $j; ?>" <?php echo $jenisSel === $j ? 'selected' : ''; ?>><?php echo $j; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Desa</label>
                    <select name="id_desa" class="form-control" onchange="this.form.submit()">
                        <option value="">Semua Desa</option>
                        <?php foreach ($desaList as $d): ?>
                        <option value="<?php echo htmlspecialchars($d); ?>" <?php echo (string)$id_desa === (string)$d ? 'selected' : ''; ?>><?php echo $d; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group" style="justify-content:flex-end">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary">Cari</button>
                </div>
                <?php if ($jenisSel || $id_desa || $search): ?>
                <div class="filter-group" style="justify-content:flex-end">
                    <label>&nbsp;</label>
                    <a href="?" class="btn btn-secondary">Reset</a>
                </div>
                <?php endif; ?>
            </form>
            <div class="filter-result">
                Menampilkan <strong><?php echo count($umkm); ?></strong> UMKM
            </div>
        </div>

        <!-- Cards Grid -->
        <div class="umkm-grid">
            <?php foreach ($umkm as $u): ?>
            <div class="umkm-card">
                <div class="umkm-card-icon umkm-icon-<?php echo $jenisColor[$u['jenis_usaha']] ?? 'primary'; ?>">
                    <i class="fas <?php echo $jenisIcon[$u['jenis_usaha']] ?? 'fa-store'; ?>"></i>
                </div>
                <div class="umkm-card-body">
                    <div class="umkm-card-heading">
                        <h4><?php echo htmlspecialchars($u['nama_usaha']); ?></h4>
                        <span class="badge badge-<?php echo $u['status_izin'] === 'Ada' ? 'success' : 'secondary'; ?>">
                            Izin: <?php echo $u['status_izin']; ?>
                        </span>
                    </div>
                    <div class="umkm-meta">
                        <span><i class="fas fa-tag"></i> <?php echo $u['jenis_usaha']; ?></span>
                        <span><i class="fas fa-map-marker-alt"></i> <?php echo $u['nama_desa']; ?></span>
                        <span><i class="fas fa-users"></i> <?php echo $u['jumlah_karyawan']; ?> karyawan</span>
                    </div>
                    <?php if (!empty($u['alamat_usaha'])): ?>
                    <div class="umkm-alamat"><i class="fas fa-map-pin"></i> <?php echo htmlspecialchars($u['alamat_usaha']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($umkm)): ?>
            <div class="umkm-empty">
                <i class="fas fa-search"></i>
                <p>Tidak ada UMKM yang ditemukan</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
