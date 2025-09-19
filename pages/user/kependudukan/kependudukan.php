<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../database/queries.php';
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'operator_desa') {
    header('Location: ' . BASE_URL . 'pages/auth/login.php');
    exit();
}

$queries = new Queries();
$id_desa = $_SESSION['user_desa'];

// Check if this is AJAX request
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    $page_title = 'Data Kependudukan';
    $current_page = 'kependudukan';
    $page_css = ['kependudukan.css'];
    $page_js = ['kependudukan.js'];
    
    ob_start();
}
?>

<div class="kependudukan-container">
    <div class="kependudukan-header">
        <h1>Data Kependudukan</h1>
        <div class="kependudukan-breadcrumb">
            <span>Dashboard</span> / <span>Data Kependudukan</span>
        </div>
    </div>

    <div class="kependudukan-tabs">
        <button class="kependudukan-tab active" data-tab="penduduk">
            <i class="fas fa-users"></i> Manajemen Penduduk
        </button>
        <button class="kependudukan-tab" data-tab="keluarga">
            <i class="fas fa-home"></i> Manajemen Keluarga
        </button>
    </div>

    <!-- Tab Penduduk -->
    <div id="tabPenduduk" class="kependudukan-tab-content active">
        <div class="kependudukan-actions">
            <div class="kependudukan-search">
                <input type="text" id="searchPenduduk" placeholder="Cari penduduk...">
                <i class="fas fa-search"></i>
            </div>
            <div class="kependudukan-filters">
                <select id="filterJenisKelamin">
                    <option value="">Semua Jenis Kelamin</option>
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                </select>
                <select id="filterUsia">
                    <option value="">Semua Usia</option>
                    <option value="0-5">0-5 tahun</option>
                    <option value="6-12">6-12 tahun</option>
                    <option value="13-17">13-17 tahun</option>
                    <option value="18-60">18-60 tahun</option>
                    <option value="60+">60+ tahun</option>
                </select>
            </div>
            <div class="kependudukan-buttons">
                <button class="kependudukan-btn-primary" onclick="openPendudukModal()">
                    <i class="fas fa-plus"></i> Tambah Penduduk
                </button>
                <button class="kependudukan-btn-secondary" onclick="importExcel()">
                    <i class="fas fa-file-excel"></i> Import Excel
                </button>
            </div>
        </div>

        <div class="kependudukan-table-container">
            <div class="table-controls">
                <div class="items-per-page">
                    <label>Tampilkan:</label>
                    <select id="pendudukPerPage" onchange="Kependudukan.changeItemsPerPage('penduduk', this.value)">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span>data per halaman</span>
                </div>
                <div class="table-info" id="pendudukInfo">Menampilkan 0 - 0 dari 0 data</div>
            </div>
            <table class="kependudukan-table" id="tablePenduduk">
                <thead>
                    <tr>
                        <th>NIK</th>
                        <th>Nama Lengkap</th>
                        <th>JK</th>
                        <th>Usia</th>
                        <th>Agama</th>
                        <th>Pendidikan</th>
                        <th>Pekerjaan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="pendudukTableBody">
                    <tr>
                        <td colspan="9" class="text-center">
                            <i class="fas fa-spinner fa-spin"></i> Memuat data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="kependudukan-pagination" id="pendudukPagination"></div>
    </div>

    <!-- Tab Keluarga -->
    <div id="tabKeluarga" class="kependudukan-tab-content">
        <div class="kependudukan-actions">
            <div class="kependudukan-search">
                <input type="text" id="searchKeluarga" placeholder="Cari keluarga...">
                <i class="fas fa-search"></i>
            </div>
            <div class="kependudukan-buttons">
                <button class="kependudukan-btn-primary" onclick="openKeluargaModal()">
                    <i class="fas fa-plus"></i> Tambah Keluarga
                </button>
            </div>
        </div>

        <div class="kependudukan-table-container">
            <div class="table-controls">
                <div class="items-per-page">
                    <label>Tampilkan:</label>
                    <select id="keluargaPerPage" onchange="Kependudukan.changeItemsPerPage('keluarga', this.value)">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span>data per halaman</span>
                </div>
                <div class="table-info" id="keluargaInfo">Menampilkan 0 - 0 dari 0 data</div>
            </div>
            <table class="kependudukan-table" id="tableKeluarga">
                <thead>
                    <tr>
                        <th>No. KK</th>
                        <th>Kepala Keluarga</th>
                        <th>Alamat</th>
                        <th>Jumlah Anggota</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="keluargaTableBody">
                    <tr>
                        <td colspan="5" class="text-center">
                            <i class="fas fa-spinner fa-spin"></i> Memuat data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="kependudukan-pagination" id="keluargaPagination"></div>
    </div>
</div>

<!-- Modal Penduduk -->
<div id="modalPenduduk" class="kependudukan-modal">
    <div class="kependudukan-modal-content">
        <div class="kependudukan-modal-header">
            <h3 id="modalPendudukTitle">Tambah Penduduk</h3>
            <button class="kependudukan-modal-close" onclick="closePendudukModal()">&times;</button>
        </div>
        <form id="formPenduduk" class="kependudukan-modal-body">
            <div class="kependudukan-form-grid">
                <div class="kependudukan-form-group">
                    <label>NIK *</label>
                    <input type="text" name="nik" id="nikPenduduk" maxlength="16" required>
                </div>
                <div class="kependudukan-form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" required>
                </div>
                <div class="kependudukan-form-group">
                    <label>Jenis Kelamin *</label>
                    <select name="jenis_kelamin" required>
                        <option value="">Pilih Jenis Kelamin</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div class="kependudukan-form-group">
                    <label>Tanggal Lahir *</label>
                    <input type="date" name="tanggal_lahir" required>
                </div>
                <div class="kependudukan-form-group">
                    <label>Agama *</label>
                    <select name="agama" required>
                        <option value="">Pilih Agama</option>
                        <option value="Islam">Islam</option>
                        <option value="Kristen">Kristen</option>
                        <option value="Katolik">Katolik</option>
                        <option value="Hindu">Hindu</option>
                        <option value="Buddha">Buddha</option>
                        <option value="Konghucu">Konghucu</option>
                    </select>
                </div>
                <div class="kependudukan-form-group">
                    <label>Pendidikan Terakhir</label>
                    <select name="pendidikan_terakhir">
                        <option value="Tidak Sekolah">Tidak Sekolah</option>
                        <option value="SD">SD</option>
                        <option value="SMP">SMP</option>
                        <option value="SMA">SMA</option>
                        <option value="D3">D3</option>
                        <option value="S1">S1</option>
                        <option value="S2">S2</option>
                        <option value="S3">S3</option>
                    </select>
                </div>
                <div class="kependudukan-form-group">
                    <label>Pekerjaan</label>
                    <input type="text" name="pekerjaan">
                </div>
                <div class="kependudukan-form-group">
                    <label>Status Pernikahan</label>
                    <select name="status_pernikahan">
                        <option value="Belum Menikah">Belum Menikah</option>
                        <option value="Menikah">Menikah</option>
                        <option value="Cerai Hidup">Cerai Hidup</option>
                        <option value="Cerai Mati">Cerai Mati</option>
                    </select>
                </div>
                <div class="kependudukan-form-group">
                    <label>Status Migrasi</label>
                    <select name="status_migrasi">
                        <option value="Menetap">Menetap</option>
                        <option value="Pendatang">Pendatang</option>
                        <option value="Pindah">Pindah</option>
                    </select>
                </div>
                <div class="kependudukan-form-group">
                    <label>Keluarga</label>
                    <select name="id_keluarga" id="selectKeluarga">
                        <option value="">Pilih Keluarga (Opsional)</option>
                    </select>
                </div>
            </div>
            <div class="kependudukan-form-group full-width">
                <label>Alamat</label>
                <textarea name="alamat" rows="3"></textarea>
            </div>
        </form>
        <div class="kependudukan-modal-footer">
            <button type="button" class="kependudukan-btn-secondary" onclick="closePendudukModal()">Batal</button>
            <button type="submit" form="formPenduduk" class="kependudukan-btn-primary">Simpan</button>
        </div>
    </div>
</div>

<!-- Modal Keluarga -->
<div id="modalKeluarga" class="kependudukan-modal">
    <div class="kependudukan-modal-content">
        <div class="kependudukan-modal-header">
            <h3 id="modalKeluargaTitle">Tambah Keluarga</h3>
            <button class="kependudukan-modal-close" onclick="closeKeluargaModal()">&times;</button>
        </div>
        <form id="formKeluarga" class="kependudukan-modal-body">
            <div class="kependudukan-form-grid">
                <div class="kependudukan-form-group">
                    <label>Nomor KK *</label>
                    <input type="text" name="nomor_kk" maxlength="20" required>
                </div>
                <div class="kependudukan-form-group">
                    <label>Nama Kepala Keluarga *</label>
                    <input type="text" name="nama_kepala_keluarga" required>
                </div>
            </div>
            <div class="kependudukan-form-group full-width">
                <label>Alamat Keluarga</label>
                <textarea name="alamat_keluarga" rows="3"></textarea>
            </div>
        </form>
        <div class="kependudukan-modal-footer">
            <button type="button" class="kependudukan-btn-secondary" onclick="closeKeluargaModal()">Batal</button>
            <button type="submit" form="formKeluarga" class="kependudukan-btn-primary">Simpan</button>
        </div>
    </div>
</div>

<!-- Modal Import Excel -->
<div id="modalImport" class="kependudukan-modal">
    <div class="kependudukan-modal-content">
        <div class="kependudukan-modal-header">
            <h3>Import Data Penduduk</h3>
            <button class="kependudukan-modal-close" onclick="closeImportModal()">&times;</button>
        </div>
        <div class="kependudukan-modal-body">
            <div class="import-section">
                <div class="import-info">
                    <h4>Panduan Import:</h4>
                    <ul>
                        <li>File harus berformat Excel (.xlsx)</li>
                        <li>Kolom harus sesuai urutan: NIK, Nama, JK, Tanggal Lahir, Agama, Pendidikan, Pekerjaan, Status Pernikahan, Status Migrasi, Alamat</li>
                        <li>Maksimal 1000 data per file</li>
                    </ul>
                    <button class="kependudukan-btn-secondary" onclick="downloadTemplate()">
                        <i class="fas fa-download"></i> Download Template
                    </button>
                </div>
                <div class="import-upload">
                    <div class="upload-area" id="importUploadArea">
                        <i class="fas fa-file-excel"></i>
                        <p>Pilih file Excel atau drag & drop di sini</p>
                    </div>
                    <input type="file" id="importFile" accept=".xlsx" style="display: none;">
                </div>
            </div>
        </div>
        <div class="kependudukan-modal-footer">
            <button type="button" class="kependudukan-btn-secondary" onclick="closeImportModal()">Batal</button>
            <button type="button" class="kependudukan-btn-primary" onclick="processImport()">Import Data</button>
        </div>
    </div>
</div>

<?php
if (!$is_ajax) {
    $content = ob_get_clean();
    require_once __DIR__ . '/../../../layout/main.php';
}
?>