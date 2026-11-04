<?php
require_once __DIR__ . '/laporan-functions.php';

$laporanFunctions = new LaporanFunctions();
$laporanFunctions->checkAuthentication();

$all_desa = $laporanFunctions->getAllDesa();
$is_ajax = $laporanFunctions->isAjaxRequest();

// Handle AJAX requests
$laporanFunctions->handleAjaxRequests();

// Get templates and riwayat
$templates = $laporanFunctions->getTemplates();
$riwayat = $laporanFunctions->getRiwayat();

if (!$is_ajax) {
    $page_title = 'Laporan';
    $current_page = 'laporan';
    $page_css = ['laporan.css'];
    $page_js = ['js/laporan-core.js', 'js/laporan-template.js', 'js/laporan-generate.js', 'laporan.js'];
    
    ob_start();
}
?>

<div class="laporan-admin-container">
    <div class="laporan-admin-header">
        <div class="laporan-admin-title">
            <h2>Manajemen Laporan</h2>
            <p>Generate dan kelola laporan berdasarkan data analisis</p>
        </div>
    </div>

    <div class="laporan-admin-tabs">
        <div class="laporan-admin-tab-nav">
            <button class="laporan-admin-tab-btn active" onclick="switchLaporanTab('template')">
                Template Laporan
            </button>
            <button class="laporan-admin-tab-btn" onclick="switchLaporanTab('generate')">
                Generate Laporan Custom
            </button>
            <button class="laporan-admin-tab-btn" onclick="switchLaporanTab('riwayat')">
                Riwayat Laporan
            </button>
        </div>
    </div>

    <div class="laporan-admin-content">
        <!-- Template Tab -->
        <div class="laporan-admin-tab-content active" id="template">
            <div class="card">
                <div class="card-header">
                    <h3>Template Laporan Tersimpan</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($templates)): ?>
                        <div class="no-data">
                            <i class="fas fa-file-alt fa-3x mb-3 text-muted"></i>
                            <h5 class="text-muted">Belum Ada Template</h5>
                            <p class="text-muted">Buat template laporan di tab "Generate Laporan Custom"</p>
                        </div>
                    <?php else: ?>
                        <div class="template-grid">
                            <?php foreach ($templates as $template): ?>
                                <div class="template-card">
                                    <div class="template-header">
                                        <h4><?php echo htmlspecialchars($template['nama_template']); ?></h4>
                                        <div class="template-actions">
                                            <button class="btn-use" onclick="useTemplate(<?php echo $template['id_template']; ?>)">
                                                <i class="fas fa-play"></i> Gunakan
                                            </button>
                                            <button class="btn-delete" onclick="deleteTemplate(<?php echo $template['id_template']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="template-info">
                                        <p><strong>Dibuat:</strong> <?php echo date('d/m/Y H:i', strtotime($template['created_at'])); ?></p>
                                        <div class="template-sections">
                                            <?php 
                                            $config = json_decode($template['konfigurasi'], true);
                                            foreach ($config['sections'] ?? [] as $section):
                                            ?>
                                                <span class="section-tag"><?php echo ucwords(str_replace('_', ' ', $section)); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Generate Tab -->
        <div class="laporan-admin-tab-content" id="generate">
            <div class="card">
                <div class="card-header">
                    <h3>Generate Laporan Custom</h3>
                </div>
                <div class="card-body">
                    <form id="reportForm">
                        <div class="form-section">
                            <h4>Informasi Laporan</h4>
                            <div class="form-group">
                                <label>Nama Laporan</label>
                                <input type="text" id="reportName" class="form-control" placeholder="Masukkan nama laporan" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Pilih Desa</label>
                                    <select id="reportDesa" class="form-control">
                                        <option value="all">Semua Desa</option>
                                        <?php foreach ($all_desa as $desa): ?>
                                            <option value="<?php echo $desa['id_desa']; ?>"><?php echo $desa['nama_desa']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Tahun Data</label>
                                    <select id="reportYear" class="form-control">
                                        <?php for ($year = date('Y'); $year >= 2020; $year--): ?>
                                            <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h4>Pilih Data yang Akan Disertakan</h4>
                            <div class="analysis-levels">
                                <!-- Analisis Tingkat 1 -->
                                <div class="analysis-level">
                                    <div class="level-header">
                                        <label class="checkbox-item level-main">
                                            <input type="checkbox" id="tingkat1" onchange="toggleLevel('tingkat1')">
                                            <span>Analisis Tingkat 1 - Data Dasar</span>
                                        </label>
                                    </div>
                                    <div class="level-content" id="tingkat1-content">
                                        <!-- Kependudukan -->
                                        <div class="section-category">
                                            <label class="checkbox-item category-header">
                                                <input type="checkbox" id="kependudukan-t1" onchange="toggleCategory('kependudukan-t1')">
                                                <span>Statistik Kependudukan</span>
                                            </label>
                                            <div class="category-content" id="kependudukan-t1-content">
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat1" value="kependudukan_data_umum">
                                                    <span>Data Umum Penduduk</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat1" value="kependudukan_kelompok_usia">
                                                    <span>Kelompok Usia</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat1" value="kependudukan_agama">
                                                    <span>Agama</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat1" value="kependudukan_tingkat_pendidikan">
                                                    <span>Tingkat Pendidikan</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat1" value="kependudukan_status_pernikahan">
                                                    <span>Status Pernikahan</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Ekonomi -->
                                        <div class="section-category">
                                            <label class="checkbox-item category-header">
                                                <input type="checkbox" id="ekonomi-t1" onchange="toggleCategory('ekonomi-t1')">
                                                <span>Distribusi Ekonomi</span>
                                            </label>
                                            <div class="category-content" id="ekonomi-t1-content">
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat1" value="ekonomi_data_umum">
                                                    <span>Data Umum Ekonomi</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat1" value="ekonomi_mata_pencaharian">
                                                    <span>Mata Pencaharian</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat1" value="ekonomi_umkm_jenis_usaha">
                                                    <span>UMKM Berdasarkan Jenis Usaha</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat1" value="ekonomi_warga_miskin_bantuan">
                                                    <span>Warga Miskin & Bantuan Sosial</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Pendidikan -->
                                        <div class="section-category">
                                            <label class="checkbox-item category-header">
                                                <input type="checkbox" id="pendidikan-t1" onchange="toggleCategory('pendidikan-t1')">
                                                <span>Fasilitas Pendidikan</span>
                                            </label>
                                            <div class="category-content" id="pendidikan-t1-content">
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat1" value="pendidikan_data_umum">
                                                    <span>Data Umum Pendidikan</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat1" value="pendidikan_jenis_fasilitas">
                                                    <span>Jenis Fasilitas</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat1" value="pendidikan_kapasitas_per_jenjang">
                                                    <span>Kapasitas Per Jenjang</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Infrastruktur -->
                                        <div class="section-category">
                                            <label class="checkbox-item category-header">
                                                <input type="checkbox" id="infrastruktur-t1" onchange="toggleCategory('infrastruktur-t1')">
                                                <span>Status Infrastruktur</span>
                                            </label>
                                            <div class="category-content" id="infrastruktur-t1-content">
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat1" value="infrastruktur_data_umum">
                                                    <span>Data Umum Infrastruktur</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat1" value="infrastruktur_kondisi_jalan">
                                                    <span>Kondisi Jalan</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat1" value="infrastruktur_kondisi_jembatan">
                                                    <span>Kondisi Jembatan</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat1" value="infrastruktur_analisis_spasial">
                                                    <span>Analisis Spasial & Geografis</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Analisis Tingkat 2 -->
                                <div class="analysis-level">
                                    <div class="level-header">
                                        <label class="checkbox-item level-main">
                                            <input type="checkbox" id="tingkat2" onchange="toggleLevel('tingkat2')">
                                            <span>Analisis Tingkat 2 - Data Lanjutan</span>
                                        </label>
                                    </div>
                                    <div class="level-content" id="tingkat2-content">
                                        <!-- Kependudukan Lanjutan -->
                                        <div class="section-category">
                                            <label class="checkbox-item category-header">
                                                <input type="checkbox" id="kependudukan-t2" onchange="toggleCategory('kependudukan-t2')">
                                                <span>Analisis Kependudukan Lanjutan</span>
                                            </label>
                                            <div class="category-content" id="kependudukan-t2-content">
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat2" value="kependudukan_kk_perempuan_anak_sekolah">
                                                    <span>KK Perempuan + Anak Sekolah</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat2" value="kependudukan_produktif_tanpa_kerja">
                                                    <span>Produktif Tanpa Kerja Tetap</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat2" value="kependudukan_kk_lansia_tanggungan">
                                                    <span>KK Lansia + Tanggungan</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat2" value="kependudukan_pendidikan_tinggi_tidak_sesuai">
                                                    <span>Pendidikan Tinggi Tidak Sesuai Bidang</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Ekonomi Lanjutan -->
                                        <div class="section-category">
                                            <label class="checkbox-item category-header">
                                                <input type="checkbox" id="ekonomi-t2" onchange="toggleCategory('ekonomi-t2')">
                                                <span>Analisis Ekonomi Lanjutan</span>
                                            </label>
                                            <div class="category-content" id="ekonomi-t2-content">
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat2" value="ekonomi_petani_lahan_kecil">
                                                    <span>Petani Lahan < 0.5 Ha</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat2" value="ekonomi_korelasi_pendidikan_pekerjaan">
                                                    <span>Korelasi Pendidikan-Pekerjaan</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat2" value="ekonomi_kesenjangan_antar_desa">
                                                    <span>Kesenjangan Ekonomi Antar Desa</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat2" value="ekonomi_distribusi_pendapatan">
                                                    <span>Distribusi Pendapatan Desa per bulan</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat2" value="ekonomi_rincian_non_pertanian">
                                                    <span>Rincian Non-Pertanian</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Integrasi Data -->
                                        <div class="section-category">
                                            <label class="checkbox-item category-header">
                                                <input type="checkbox" id="integrasi-t2" onchange="toggleCategory('integrasi-t2')">
                                                <span>Analisis Integrasi Data</span>
                                            </label>
                                            <div class="category-content" id="integrasi-t2-content">
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat2" value="integrasi_kk_miskin_anak_sekolah">
                                                    <span>KK Miskin + Anak Sekolah</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat2" value="integrasi_pendidikan_tinggi_miskin">
                                                    <span>Pendidikan Tinggi Tapi Miskin</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat2" value="integrasi_rasio_ketergantungan_ekonomi">
                                                    <span>Rasio Ketergantungan Ekonomi</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat2" value="integrasi_anak_tidak_sekolah_ekonomi">
                                                    <span>Anak Tidak Sekolah karena Ekonomi</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat2" value="integrasi_anak_petani_pendidikan_tinggi">
                                                    <span>Anak Petani → Pendidikan Tinggi</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat2" value="integrasi_bidang_studi_populer">
                                                    <span>Bidang Studi Populer</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat2" value="integrasi_status_setelah_lulus">
                                                    <span>Status Setelah Lulus</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Spasial & Prediktif -->
                                        <div class="section-category">
                                            <label class="checkbox-item category-header">
                                                <input type="checkbox" id="spasial-t2" onchange="toggleCategory('spasial-t2')">
                                                <span>Analisis Spasial & Prediktif</span>
                                            </label>
                                            <div class="category-content" id="spasial-t2-content">
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat2" value="spasial_data_umum">
                                                    <span>Data Umum Spasial</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat2" value="spasial_ranking_potensi_pengembangan">
                                                    <span>Ranking Potensi Pengembangan Desa</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Analisis Tingkat 3 -->
                                <div class="analysis-level">
                                    <div class="level-header">
                                        <label class="checkbox-item level-main">
                                            <input type="checkbox" id="tingkat3" onchange="toggleLevel('tingkat3')">
                                            <span>Prediksi & Proyeksi</span>
                                        </label>
                                    </div>
                                    <div class="level-content" id="tingkat3-content">
                                        <!-- Prediksi Struktur Penduduk -->
                                        <div class="section-category">
                                            <label class="checkbox-item category-header">
                                                <input type="checkbox" id="prediksi-t3" onchange="toggleCategory('prediksi-t3')">
                                                <span>Prediksi Struktur Penduduk</span>
                                            </label>
                                            <div class="category-content" id="prediksi-t3-content">
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat3" value="prediksi_penduduk_5_tahun">
                                                    <span>Prediksi Penduduk 5 Tahun</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat3" value="prediksi_data_umum">
                                                    <span>Data Umum Prediksi</span>
                                                </label>
                                                <label class="checkbox-item sub-category">
                                                    <input type="checkbox" name="tingkat3" value="proyeksi_kebutuhan_pembangunan">
                                                    <span>Proyeksi Kebutuhan Pembangunan</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <div class="cache-info-box">
                                <div class="info-icon">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="info-content">
                                    <strong>Tips untuk Performa Optimal:</strong>
                                    <p>Untuk generate laporan yang lebih cepat, pastikan Anda sudah melakukan "Generate Analisis" di halaman Analisis terlebih dahulu. Sistem akan menggunakan cache data yang sudah dikalkulasi.</p>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary" onclick="saveAsTemplate()">
                                <i class="fas fa-save"></i> Simpan sebagai Template
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="clearFormAndState()" title="Bersihkan form dan hapus data tersimpan">
                                <i class="fas fa-eraser"></i> Reset Form
                            </button>
                            <button type="button" class="btn btn-success" onclick="generateReport()">
                                <i class="fas fa-file-pdf"></i> Generate Laporan PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Riwayat Tab -->
        <div class="laporan-admin-tab-content" id="riwayat">
            <div class="card">
                <div class="card-header">
                    <h3>Riwayat Laporan</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($riwayat)): ?>
                        <div class="no-data">
                            <i class="fas fa-history fa-3x mb-3 text-muted"></i>
                            <h5 class="text-muted">Belum Ada Riwayat</h5>
                            <p class="text-muted">Riwayat laporan yang telah dibuat akan muncul di sini</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Nama Laporan</th>
                                        <th>Nama File</th>
                                        <th>Ukuran File</th>
                                        <th>Tanggal Dibuat</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($riwayat as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['nama_laporan']); ?></td>
                                            <td>
                                                <i class="fas fa-file-alt text-primary"></i>
                                                <?php echo $item['file_pdf'] ? pathinfo(basename($item['file_pdf']), PATHINFO_FILENAME) : 'Tidak ada file'; ?>
                                            </td>
                                            <td><?php echo $item['file_size'] ? number_format($item['file_size'] / 1024, 2) . ' KB' : '-'; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?></td>
                                            <td><span class="badge badge-success"><?php echo ucfirst($item['status'] ?? 'completed'); ?></span></td>
                                            <td>
                                                <?php if ($item['file_pdf']): ?>
                                                    <a href="<?php echo BASE_URL . $item['file_pdf']; ?>" class="btn-download" download>
                                                        <i class="fas fa-download"></i> Download
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Tidak ada file</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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