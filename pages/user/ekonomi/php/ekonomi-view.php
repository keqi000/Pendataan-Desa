<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../database/queries.php';
require_once __DIR__ . '/ekonomi-functions.php';
session_start();

$ekonomi = new EkonomiFunctions();
$ekonomi->checkAuthentication();

$is_ajax = $ekonomi->isAjaxRequest();

// Handle AJAX requests
if ($is_ajax) {
    $ekonomi->handleAjaxRequest();
}

if (!$is_ajax) {
    $config = $ekonomi->getPageConfig();
    extract($config);
    ob_start();
}
?>

<div class="ekonomi-container">
    <div class="ekonomi-header">
        <h1>Data Ekonomi</h1>
        <div class="ekonomi-breadcrumb">
            <span>Dashboard</span> / <span>Data Ekonomi</span>
        </div>
    </div>

    <div class="ekonomi-tabs">
        <button class="ekonomi-tab active" data-tab="mata-pencaharian">
            <i class="fas fa-briefcase"></i> Mata Pencaharian
        </button>
        <button class="ekonomi-tab" data-tab="umkm">
            <i class="fas fa-store"></i> UMKM
        </button>
        <button class="ekonomi-tab" data-tab="pertanian">
            <i class="fas fa-seedling"></i> Pertanian
        </button>
        <button class="ekonomi-tab" data-tab="pasar">
            <i class="fas fa-shopping-cart"></i> Pasar
        </button>
        <button class="ekonomi-tab" data-tab="bantuan-sosial">
            <i class="fas fa-hand-holding-heart"></i> Bantuan Sosial
        </button>
    </div>

    <!-- Tab Mata Pencaharian -->
    <div id="tabMataPencaharian" class="ekonomi-tab-content active">
        <div class="ekonomi-actions">
            <div class="ekonomi-search">
                <input type="text" id="searchMataPencaharian" placeholder="Cari NIK, nama, atau pekerjaan...">
                <i class="fas fa-search"></i>
            </div>
            <div class="ekonomi-filters">
                <select id="filterSektor">
                    <option value="">Semua Sektor</option>
                    <option value="pertanian">Pertanian</option>
                    <option value="industri">Industri</option>
                    <option value="jasa">Jasa</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            </div>
            <button class="ekonomi-btn-primary" onclick="openImportMataPencaharianModal()">
                <i class="fas fa-upload"></i> Import Data
            </button>
        </div>

        <div class="ekonomi-table-container">
            <div class="table-controls">
                <div class="items-per-page">
                    <label>Tampilkan:</label>
                    <select id="mataPencaharianPerPage" onchange="Ekonomi.changeItemsPerPage('mata-pencaharian', this.value)">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span>data per halaman</span>
                </div>
                <div class="table-info" id="mataPencaharianInfo">Menampilkan 0 - 0 dari 0 data</div>
            </div>
            <table class="ekonomi-table">
                <thead>
                    <tr>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Jenis Pekerjaan</th>
                        <th>Sektor</th>
                        <th>Penghasilan/Bulan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="mataPencaharianTableBody">
                    <tr>
                        <td colspan="7" class="text-center">
                            <i class="fas fa-spinner fa-spin"></i> Memuat data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="ekonomi-pagination" id="mataPencaharianPagination"></div>
    </div>

    <!-- Tab UMKM -->
    <div id="tabUmkm" class="ekonomi-tab-content">
        <div class="ekonomi-actions">
            <div class="ekonomi-search">
                <input type="text" id="searchUmkm" placeholder="Cari nama UMKM atau pemilik...">
                <i class="fas fa-search"></i>
            </div>
            <button class="ekonomi-btn-primary" onclick="openUmkmModal()">
                <i class="fas fa-plus"></i> Tambah UMKM
            </button>
        </div>

        <div class="table-controls">
            <div class="items-per-page">
                <label>Tampilkan:</label>
                <select id="umkmPerPage" onchange="Ekonomi.changeItemsPerPage('umkm', this.value)">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>data per halaman</span>
            </div>
            <div class="table-info" id="umkmInfo">Menampilkan 0 - 0 dari 0 data</div>
        </div>

        <div class="ekonomi-cards" id="umkmCards">
            <div class="ekonomi-card-loading">
                <i class="fas fa-spinner fa-spin"></i> Memuat data UMKM...
            </div>
        </div>
        
        <div class="ekonomi-pagination" id="umkmPagination"></div>
    </div>

    <!-- Tab Pertanian -->
    <div id="tabPertanian" class="ekonomi-tab-content">
        <div class="ekonomi-actions">
            <div class="ekonomi-search">
                <input type="text" id="searchPertanian" placeholder="Cari NIK, nama, atau komoditas...">
                <i class="fas fa-search"></i>
            </div>
            <button class="ekonomi-btn-primary" onclick="openImportPertanianModal()">
                <i class="fas fa-upload"></i> Import Data
            </button>
        </div>

        <div class="ekonomi-table-container">
            <div class="table-controls">
                <div class="items-per-page">
                    <label>Tampilkan:</label>
                    <select id="pertanianPerPage" onchange="Ekonomi.changeItemsPerPage('pertanian', this.value)">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span>data per halaman</span>
                </div>
                <div class="table-info" id="pertanianInfo">Menampilkan 0 - 0 dari 0 data</div>
            </div>
            <table class="ekonomi-table">
                <thead>
                    <tr>
                        <th>NIK</th>
                        <th>Nama Petani</th>
                        <th>Komoditas</th>
                        <th>Luas Lahan</th>
                        <th>Jenis Lahan</th>
                        <th>Pendapatan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="pertanianTableBody">
                    <tr>
                        <td colspan="7" class="text-center">
                            <i class="fas fa-spinner fa-spin"></i> Memuat data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="ekonomi-pagination" id="pertanianPagination"></div>
    </div>

    <!-- Tab Pasar -->
    <div id="tabPasar" class="ekonomi-tab-content">
        <div class="ekonomi-actions">
            <div class="ekonomi-search">
                <input type="text" id="searchPasar" placeholder="Cari nama pasar...">
                <i class="fas fa-search"></i>
            </div>
            <button class="ekonomi-btn-primary" onclick="openPasarModal()">
                <i class="fas fa-plus"></i> Tambah Pasar
            </button>
        </div>

        <div class="table-controls">
            <div class="items-per-page">
                <label>Tampilkan:</label>
                <select id="pasarPerPage" onchange="Ekonomi.changeItemsPerPage('pasar', this.value)">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>data per halaman</span>
            </div>
            <div class="table-info" id="pasarInfo">Menampilkan 0 - 0 dari 0 data</div>
        </div>

        <div class="ekonomi-cards" id="pasarCards">
            <div class="ekonomi-card-loading">
                <i class="fas fa-spinner fa-spin"></i> Memuat data pasar...
            </div>
        </div>
        
        <div class="ekonomi-pagination" id="pasarPagination"></div>
    </div>

    <!-- Tab Bantuan Sosial -->
    <div id="tabBantuanSosial" class="ekonomi-tab-content">
        <div class="ekonomi-actions">
            <div class="ekonomi-search">
                <input type="text" id="searchBantuan" placeholder="Cari NIK, nama, atau jenis bantuan...">
                <i class="fas fa-search"></i>
            </div>
            <button class="ekonomi-btn-primary" onclick="openBantuanModal()">
                <i class="fas fa-plus"></i> Tambah Penerima Bantuan
            </button>
        </div>

        <div class="ekonomi-table-container">
            <table class="ekonomi-table">
                <thead>
                    <tr>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Jenis Bantuan</th>
                        <th>Nominal</th>
                        <th>Periode</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="bantuanTableBody">
                    <tr>
                        <td colspan="7" class="text-center">
                            <i class="fas fa-spinner fa-spin"></i> Memuat data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Pertanian -->
<div id="modalPertanian" class="ekonomi-modal">
    <div class="ekonomi-modal-content">
        <div class="ekonomi-modal-header">
            <h3 id="modalPertanianTitle">Tambah Data Pertanian</h3>
            <button class="ekonomi-modal-close" onclick="closePertanianModal()">&times;</button>
        </div>
        <form id="formPertanian" class="ekonomi-modal-body">
            <div class="ekonomi-form-grid">
                <div class="ekonomi-form-group">
                    <label>Cari Petani *</label>
                    <div class="search-input-container">
                        <input type="text" id="searchPendudukPertanian" placeholder="Ketik nama atau NIK..." autocomplete="off">
                        <input type="hidden" name="nik_petani" id="selectedNikPertanian" required>
                        <div class="search-results" id="searchResultsPertanian"></div>
                    </div>
                </div>
                <div class="ekonomi-form-group">
                    <label>Jenis Komoditas</label>
                    <input type="text" name="jenis_komoditas">
                </div>
                <div class="ekonomi-form-group">
                    <label>Luas Lahan (Ha)</label>
                    <input type="number" name="luas_lahan" step="0.01">
                </div>
                <div class="ekonomi-form-group">
                    <label>Jenis Lahan</label>
                    <select name="jenis_lahan">
                        <option value="">Pilih Jenis Lahan</option>
                        <option value="sawah">Sawah</option>
                        <option value="ladang">Ladang</option>
                        <option value="kebun">Kebun</option>
                        <option value="tegalan">Tegalan</option>
                    </select>
                </div>
                <div class="ekonomi-form-group">
                    <label>Status Kepemilikan</label>
                    <select name="status_kepemilikan">
                        <option value="">Pilih Status</option>
                        <option value="milik_sendiri">Milik Sendiri</option>
                        <option value="sewa">Sewa</option>
                        <option value="bagi_hasil">Bagi Hasil</option>
                    </select>
                </div>
                <div class="ekonomi-form-group">
                    <label>Produksi per Musim</label>
                    <input type="number" name="produksi_per_musim" step="0.01">
                </div>
                <div class="ekonomi-form-group">
                    <label>Satuan Produksi</label>
                    <input type="text" name="satuan_produksi" placeholder="Contoh: Ton, Kg">
                </div>
                <div class="ekonomi-form-group">
                    <label>Pendapatan per Musim</label>
                    <input type="number" name="pendapatan_per_musim" step="0.01">
                </div>
                <div class="ekonomi-form-group">
                    <label>Musim Tanam</label>
                    <select name="musim_tanam">
                        <option value="">Pilih Musim</option>
                        <option value="musim_hujan">Musim Hujan</option>
                        <option value="musim_kemarau">Musim Kemarau</option>
                        <option value="sepanjang_tahun">Sepanjang Tahun</option>
                    </select>
                </div>
                <div class="ekonomi-form-group">
                    <label>Bantuan Pertanian</label>
                    <select name="bantuan_pertanian">
                        <option value="Tidak Ada">Tidak Ada</option>
                        <option value="Ada">Ada</option>
                    </select>
                </div>
                <div class="ekonomi-form-group">
                    <label>Jenis Bantuan</label>
                    <input type="text" name="jenis_bantuan">
                </div>
                <div class="ekonomi-form-group">
                    <label>Teknologi Pertanian</label>
                    <select name="teknologi_pertanian">
                        <option value="">Pilih Teknologi</option>
                        <option value="tradisional">Tradisional</option>
                        <option value="semi_modern">Semi Modern</option>
                        <option value="modern">Modern</option>
                    </select>
                </div>
                <div class="ekonomi-form-group">
                    <label>Irigasi</label>
                    <select name="irigasi">
                        <option value="">Pilih Irigasi</option>
                        <option value="teknis">Teknis</option>
                        <option value="semi_teknis">Semi Teknis</option>
                        <option value="sederhana">Sederhana</option>
                        <option value="tadah_hujan">Tadah Hujan</option>
                    </select>
                </div>
                <div class="ekonomi-form-group">
                    <label>Jumlah Pelaku</label>
                    <input type="number" name="jumlah_pelaku" min="1">
                </div>
                <div class="ekonomi-form-group">
                    <label>Nilai Ekonomi</label>
                    <input type="number" name="nilai_ekonomi" step="0.01">
                </div>
                <div class="ekonomi-form-group">
                    <label>Lokasi</label>
                    <input type="text" name="lokasi">
                </div>
            </div>
            <div class="ekonomi-form-group full-width">
                <label>Deskripsi</label>
                <textarea name="deskripsi" rows="3"></textarea>
            </div>
        </form>
        <div class="ekonomi-modal-footer">
            <button type="button" class="ekonomi-btn-secondary" onclick="closePertanianModal()">Batal</button>
            <button type="submit" form="formPertanian" class="ekonomi-btn-primary">Simpan</button>
        </div>
    </div>
</div>

<!-- Modal Detail Pertanian -->
<div id="modalDetailPertanian" class="ekonomi-modal">
    <div class="ekonomi-modal-content">
        <div class="ekonomi-modal-header">
            <h3>Detail Data Pertanian</h3>
            <button class="ekonomi-modal-close" onclick="closeDetailPertanianModal()">&times;</button>
        </div>
        <div class="ekonomi-modal-body">
            <div class="detail-grid">
                <div class="detail-item">
                    <label>NIK Petani:</label>
                    <span id="detailNikPetani">-</span>
                </div>
                <div class="detail-item">
                    <label>Nama Petani:</label>
                    <span id="detailNamaPetani">-</span>
                </div>
                <div class="detail-item">
                    <label>Jenis Komoditas:</label>
                    <span id="detailJenisKomoditas">-</span>
                </div>
                <div class="detail-item">
                    <label>Luas Lahan:</label>
                    <span id="detailLuasLahan">-</span>
                </div>
                <div class="detail-item">
                    <label>Jenis Lahan:</label>
                    <span id="detailJenisLahan">-</span>
                </div>
                <div class="detail-item">
                    <label>Status Kepemilikan:</label>
                    <span id="detailStatusKepemilikan">-</span>
                </div>
                <div class="detail-item">
                    <label>Produksi per Musim:</label>
                    <span id="detailProduksiPerMusim">-</span>
                </div>
                <div class="detail-item">
                    <label>Satuan Produksi:</label>
                    <span id="detailSatuanProduksi">-</span>
                </div>
                <div class="detail-item">
                    <label>Pendapatan per Musim:</label>
                    <span id="detailPendapatanPerMusim">-</span>
                </div>
                <div class="detail-item">
                    <label>Musim Tanam:</label>
                    <span id="detailMusimTanam">-</span>
                </div>
                <div class="detail-item">
                    <label>Bantuan Pertanian:</label>
                    <span id="detailBantuanPertanian">-</span>
                </div>
                <div class="detail-item">
                    <label>Jenis Bantuan:</label>
                    <span id="detailJenisBantuan">-</span>
                </div>
                <div class="detail-item">
                    <label>Teknologi Pertanian:</label>
                    <span id="detailTeknologiPertanian">-</span>
                </div>
                <div class="detail-item">
                    <label>Irigasi:</label>
                    <span id="detailIrigasi">-</span>
                </div>
                <div class="detail-item">
                    <label>Jumlah Pelaku:</label>
                    <span id="detailJumlahPelaku">-</span>
                </div>
                <div class="detail-item">
                    <label>Nilai Ekonomi:</label>
                    <span id="detailNilaiEkonomi">-</span>
                </div>
                <div class="detail-item">
                    <label>Lokasi:</label>
                    <span id="detailLokasi">-</span>
                </div>
                <div class="detail-item full-width">
                    <label>Deskripsi:</label>
                    <span id="detailDeskripsi">-</span>
                </div>
            </div>
        </div>
        <div class="ekonomi-modal-footer">
            <button type="button" class="ekonomi-btn-secondary" onclick="closeDetailPertanianModal()">Tutup</button>
        </div>
    </div>
</div>

<!-- All modals from original file -->
<!-- Modal Edit Mata Pencaharian -->
<div id="modalMataPencaharian" class="ekonomi-modal">
    <div class="ekonomi-modal-content">
        <div class="ekonomi-modal-header">
            <h3 id="modalMataPencaharianTitle">Edit Mata Pencaharian</h3>
            <button class="ekonomi-modal-close" onclick="closeMataPencaharianModal()">&times;</button>
        </div>
        <form id="formMataPencaharian" class="ekonomi-modal-body">
            <div class="ekonomi-form-grid">
                <div class="ekonomi-form-group">
                    <label>NIK</label>
                    <input type="text" name="nik" readonly>
                </div>
                <div class="ekonomi-form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" readonly>
                </div>
                <div class="ekonomi-form-group">
                    <label>Jenis Pekerjaan</label>
                    <input type="text" name="jenis_pekerjaan" readonly>
                </div>
                <div class="ekonomi-form-group">
                    <label>Sektor Pekerjaan</label>
                    <select name="sektor_pekerjaan">
                        <option value="">Pilih Sektor</option>
                        <option value="pertanian">Pertanian</option>
                        <option value="industri">Industri</option>
                        <option value="jasa">Jasa</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="ekonomi-form-group">
                    <label>Penghasilan per Bulan</label>
                    <input type="text" name="penghasilan_perbulan" placeholder="Contoh: 3.500.000" oninput="formatCurrencyInput(this)">
                </div>
                <div class="ekonomi-form-group">
                    <label>Status Pekerjaan</label>
                    <select name="status_pekerjaan">
                        <option value="">Pilih Status</option>
                        <option value="tetap">Tetap</option>
                        <option value="tidak_tetap">Tidak Tetap</option>
                    </select>
                </div>
            </div>
        </form>
        <div class="ekonomi-modal-footer">
            <button type="button" class="ekonomi-btn-secondary" onclick="closeMataPencaharianModal()">Batal</button>
            <button type="submit" form="formMataPencaharian" class="ekonomi-btn-primary">Simpan</button>
        </div>
    </div>
</div>

<!-- Modal UMKM -->
<div id="modalUmkm" class="ekonomi-modal">
    <div class="ekonomi-modal-content">
        <div class="ekonomi-modal-header">
            <h3 id="modalUmkmTitle">Tambah UMKM</h3>
            <button class="ekonomi-modal-close" onclick="closeUmkmModal()">&times;</button>
        </div>
        <form id="formUmkm" class="ekonomi-modal-body">
            <div class="ekonomi-form-grid">
                <div class="ekonomi-form-group">
                    <label>Cari Pemilik *</label>
                    <div class="search-input-container">
                        <input type="text" id="searchPendudukUmkm" placeholder="Ketik nama atau NIK..." autocomplete="off">
                        <input type="hidden" name="nik_pemilik" id="selectedNikUmkm" required>
                        <div class="search-results" id="searchResultsUmkm"></div>
                    </div>
                </div>
                <div class="ekonomi-form-group">
                    <label>Nama Usaha *</label>
                    <input type="text" name="nama_usaha" required>
                </div>
                <div class="ekonomi-form-group">
                    <label>Jenis Usaha</label>
                    <input type="text" name="jenis_usaha">
                </div>
                <div class="ekonomi-form-group">
                    <label>Modal Usaha</label>
                    <input type="number" name="modal_usaha" step="0.01">
                </div>
                <div class="ekonomi-form-group">
                    <label>Omzet per Bulan</label>
                    <input type="number" name="omzet_perbulan" step="0.01">
                </div>
                <div class="ekonomi-form-group">
                    <label>Jumlah Karyawan</label>
                    <input type="number" name="jumlah_karyawan" min="0">
                </div>
                <div class="ekonomi-form-group">
                    <label>Status Izin</label>
                    <select name="status_izin">
                        <option value="Tidak Ada">Tidak Ada</option>
                        <option value="Ada">Ada</option>
                    </select>
                </div>
            </div>
            <div class="ekonomi-form-group full-width">
                <label>Alamat Usaha</label>
                <textarea name="alamat_usaha" rows="3"></textarea>
            </div>
        </form>
        <div class="ekonomi-modal-footer">
            <button type="button" class="ekonomi-btn-secondary" onclick="closeUmkmModal()">Batal</button>
            <button type="submit" form="formUmkm" class="ekonomi-btn-primary">Simpan</button>
        </div>
    </div>
</div>

<!-- Modal Pasar -->
<div id="modalPasar" class="ekonomi-modal">
    <div class="ekonomi-modal-content">
        <div class="ekonomi-modal-header">
            <h3 id="modalPasarTitle">Tambah Pasar</h3>
            <button class="ekonomi-modal-close" onclick="closePasarModal()">&times;</button>
        </div>
        <form id="formPasar" class="ekonomi-modal-body">
            <div class="ekonomi-form-grid">
                <div class="ekonomi-form-group">
                    <label>Nama Pasar *</label>
                    <input type="text" name="nama_pasar" required>
                </div>
                <div class="ekonomi-form-group">
                    <label>Jenis Pasar</label>
                    <select name="jenis_pasar">
                        <option value="tradisional">Tradisional</option>
                        <option value="modern">Modern</option>
                    </select>
                </div>
                <div class="ekonomi-form-group">
                    <label>Jumlah Pedagang</label>
                    <input type="number" name="jumlah_pedagang" min="0">
                </div>
                <div class="ekonomi-form-group">
                    <label>Hari Operasional</label>
                    <input type="text" name="hari_operasional" placeholder="Contoh: Senin - Minggu">
                </div>
                <div class="ekonomi-form-group">
                    <label>Hasil Produksi</label>
                    <input type="text" name="hasil_produksi" placeholder="Contoh: Sayuran, Buah-buahan">
                </div>
                <div class="ekonomi-form-group">
                    <label>Nilai Ekonomi</label>
                    <input type="number" name="nilai_ekonomi" step="0.01">
                </div>
                <div class="ekonomi-form-group">
                    <label>Kondisi Fasilitas</label>
                    <select name="kondisi_fasilitas">
                        <option value="Baik">Baik</option>
                        <option value="Sedang">Sedang</option>
                        <option value="Rusak">Rusak</option>
                    </select>
                </div>
            </div>
            <div class="ekonomi-form-group full-width">
                <label>Alamat Pasar</label>
                <textarea name="alamat_pasar" rows="3"></textarea>
            </div>
            <div class="ekonomi-form-group full-width">
                <label>Deskripsi</label>
                <textarea name="deskripsi" rows="3" placeholder="Deskripsi pasar..."></textarea>
            </div>
        </form>
        <div class="ekonomi-modal-footer">
            <button type="button" class="ekonomi-btn-secondary" onclick="closePasarModal()">Batal</button>
            <button type="submit" form="formPasar" class="ekonomi-btn-primary">Simpan</button>
        </div>
    </div>
</div>

<!-- Modal Bantuan Sosial -->
<div id="modalBantuan" class="ekonomi-modal">
    <div class="ekonomi-modal-content">
        <div class="ekonomi-modal-header">
            <h3 id="modalBantuanTitle">Tambah Penerima Bantuan</h3>
            <button class="ekonomi-modal-close" onclick="closeBantuanModal()">&times;</button>
        </div>
        <form id="formBantuan" class="ekonomi-modal-body">
            <div class="ekonomi-form-grid">
                <div class="ekonomi-form-group">
                    <label>Cari Penduduk *</label>
                    <div class="search-input-container">
                        <input type="text" id="searchPendudukBantuan" placeholder="Ketik nama atau NIK..." autocomplete="off" required>
                        <input type="hidden" name="nik" id="selectedNikBantuan" required>
                        <div class="search-results" id="searchResultsBantuan"></div>
                    </div>
                </div>
                <div class="ekonomi-form-group">
                    <label>Jenis Bantuan *</label>
                    <select name="jenis_bantuan" required>
                        <option value="">Pilih Jenis Bantuan</option>
                        <option value="PKH">PKH</option>
                        <option value="BPNT">BPNT</option>
                        <option value="BST">BST</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="ekonomi-form-group">
                    <label>Nominal Bantuan</label>
                    <input type="number" name="nominal_bantuan" step="0.01">
                </div>
                <div class="ekonomi-form-group">
                    <label>Periode Bantuan</label>
                    <input type="text" name="periode_bantuan" placeholder="Contoh: Januari 2024">
                </div>
            </div>
        </form>
        <div class="ekonomi-modal-footer">
            <button type="button" class="ekonomi-btn-secondary" onclick="closeBantuanModal()">Batal</button>
            <button type="submit" form="formBantuan" class="ekonomi-btn-primary">Simpan</button>
        </div>
    </div>
</div>

<!-- Modal Import Mata Pencaharian -->
<div id="modalImportMataPencaharian" class="ekonomi-modal">
    <div class="ekonomi-modal-content">
        <div class="ekonomi-modal-header">
            <h3>Import Data Mata Pencaharian</h3>
            <button class="ekonomi-modal-close" onclick="closeImportMataPencaharianModal()">&times;</button>
        </div>
        <div class="ekonomi-modal-body">
            <div class="import-section">
                <div class="import-info">
                    <h4>Panduan Import:</h4>
                    <ul>
                        <li>Download data mata pencaharian saat ini terlebih dahulu</li>
                        <li>Lengkapi kolom: Sektor Pekerjaan, Penghasilan Per Bulan, Status Pekerjaan</li>
                        <li>File harus berformat Excel (.xlsx)</li>
                        <li>Jangan mengubah kolom NIK, Nama, dan Jenis Pekerjaan</li>
                    </ul>
                    <button class="ekonomi-btn-secondary" onclick="downloadMataPencaharianData()">
                        <i class="fas fa-download"></i> Download Data
                    </button>
                </div>
                <div class="import-upload">
                    <div class="upload-area" id="importMataPencaharianUploadArea">
                        <i class="fas fa-file-excel"></i>
                        <p>Pilih file Excel atau drag & drop di sini</p>
                    </div>
                    <input type="file" id="importMataPencaharianFile" accept=".xlsx" style="display: none;">
                </div>
            </div>
        </div>
        <div class="ekonomi-modal-footer">
            <button type="button" class="ekonomi-btn-secondary" onclick="closeImportMataPencaharianModal()">Batal</button>
            <button type="button" class="ekonomi-btn-primary" onclick="processMataPencaharianImport()">Import Data</button>
        </div>
    </div>
</div>

<!-- Modal Import Pertanian -->
<div id="modalImportPertanian" class="ekonomi-modal">
    <div class="ekonomi-modal-content">
        <div class="ekonomi-modal-header">
            <h3>Import Data Pertanian</h3>
            <button class="ekonomi-modal-close" onclick="closeImportPertanianModal()">&times;</button>
        </div>
        <div class="ekonomi-modal-body">
            <div class="import-section">
                <div class="import-info">
                    <h4>Panduan Import:</h4>
                    <ul>
                        <li>Download data pertanian saat ini terlebih dahulu</li>
                        <li>Lengkapi kolom pertanian yang kosong</li>
                        <li>File harus berformat Excel (.xlsx)</li>
                        <li>Jangan mengubah kolom NIK dan Nama Petani</li>
                    </ul>
                    <button class="ekonomi-btn-secondary" onclick="downloadPertanianData()">
                        <i class="fas fa-download"></i> Download Data
                    </button>
                </div>
                <div class="import-upload">
                    <div class="upload-area" id="importPertanianUploadArea">
                        <i class="fas fa-file-excel"></i>
                        <p>Pilih file Excel atau drag & drop di sini</p>
                    </div>
                    <input type="file" id="importPertanianFile" accept=".xlsx" style="display: none;">
                </div>
            </div>
        </div>
        <div class="ekonomi-modal-footer">
            <button type="button" class="ekonomi-btn-secondary" onclick="closeImportPertanianModal()">Batal</button>
            <button type="button" class="ekonomi-btn-primary" onclick="processPertanianImport()">Import Data</button>
        </div>
    </div>
</div>

<?php
if (!$is_ajax) {
    $content = ob_get_clean();
    require_once __DIR__ . '/../../../../layout/main.php';
}
?>