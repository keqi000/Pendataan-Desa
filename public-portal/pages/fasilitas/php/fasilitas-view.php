<?php
// Fasilitas View - Sistem Informasi Publik
// File: public-portal/pages/fasilitas/php/fasilitas-view.php

require_once __DIR__ . '/fasilitas-functions.php';

$fn        = new FasilitasFunctions();
$jenisSel  = $_GET['jenis']   ?? '';
$id_desa   = isset($_GET['id_desa']) ? (int)$_GET['id_desa'] : null;
$fasilitas = $fn->getAll($jenisSel ?: null, $id_desa);
$jenisList = $fn->getJenisList();
$desaList  = $fn->getDesaList();
$summary   = $fn->getSummaryByJenis($id_desa);

$kondisiIcon = ['Baik' => 'success', 'Sedang' => 'warning', 'Rusak' => 'danger'];
$jenisIcon   = ['SD' => 'fa-school', 'SMP' => 'fa-school', 'SMA' => 'fa-university', 'SMK' => 'fa-university', 'TK' => 'fa-child', 'PAUD' => 'fa-child'];
?>

<!-- Page Header -->
<section class="page-hero">
    <div class="container">
        <h1><i class="fas fa-building"></i> Fasilitas Publik</h1>
        <p>Data fasilitas pendidikan dan kesehatan di Kecamatan Tibawa</p>
    </div>
</section>

<!-- Summary Cards -->
<section class="summary-section">
    <div class="container">
        <div class="summary-grid">
            <?php foreach ($summary as $s): ?>
            <div class="summary-card">
                <i class="fas <?php echo $jenisIcon[$s['jenis']] ?? 'fa-building'; ?>"></i>
                <div class="summary-value"><?php echo $s['jumlah']; ?></div>
                <div class="summary-label"><?php echo $s['jenis']; ?></div>
                <div class="summary-sub">Kapasitas: <?php echo number_format($s['kapasitas']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Filter & List -->
<section class="fasilitas-section">
    <div class="container">
        <!-- Filter -->
        <div class="filter-bar">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label>Jenis Fasilitas</label>
                    <select name="jenis" class="form-control" onchange="this.form.submit()">
                        <option value="">Semua Jenis</option>
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
                <?php if ($jenisSel || $id_desa): ?>
                <a href="?" class="btn btn-secondary">Reset Filter</a>
                <?php endif; ?>
            </form>
            <div class="filter-result">
                Menampilkan <strong><?php echo count($fasilitas); ?></strong> fasilitas
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Fasilitas</th>
                                <th>Jenis</th>
                                <th>Desa</th>
                                <th>Kapasitas</th>
                                <th>Kondisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fasilitas as $f): ?>
                            <tr>
                                <td>
                                    <i class="fas <?php echo $jenisIcon[$f['jenis_pendidikan']] ?? 'fa-building'; ?> text-primary"></i>
                                    <?php echo htmlspecialchars($f['nama_fasilitas']); ?>
                                </td>
                                <td><?php echo $f['jenis_pendidikan']; ?></td>
                                <td><?php echo $f['nama_desa']; ?></td>
                                <td><?php echo number_format($f['kapasitas_siswa']); ?></td>
                                <td><span class="badge badge-<?php echo $kondisiIcon[$f['kondisi_bangunan']] ?? 'info'; ?>"><?php echo $f['kondisi_bangunan']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($fasilitas)): ?>
                            <tr><td colspan="5" class="text-center text-muted">Tidak ada data fasilitas</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
