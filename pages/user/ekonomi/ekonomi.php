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
    $page_title = 'Data Ekonomi';
    $current_page = 'ekonomi';
    $page_css = ['ekonomi.css'];
    $page_js = ['ekonomi.js'];
    
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
                <input type="text" id="searchMataPencaharian" placeholder="Cari mata pencaharian...">
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
            <button class="ekonomi-btn-primary" onclick="openMataPencaharianModal()">
                <i class="fas fa-plus"></i> Tambah Data
            </button>
        </div>

        <div class="ekonomi-table-container">
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
    </div>

    <!-- Tab UMKM -->
    <div id="tabUmkm" class="ekonomi-tab-content">
        <div class="ekonomi-actions">
            <div class="ekonomi-search">
                <input type="text" id="searchUmkm" placeholder="Cari UMKM...">
                <i class="fas fa-search"></i>
            </div>
            <button class="ekonomi-btn-primary" onclick="openUmkmModal()">
                <i class="fas fa-plus"></i> Tambah UMKM
            </button>
        </div>

        <div class="ekonomi-cards" id="umkmCards">
            <div class="ekonomi-card-loading">
                <i class="fas fa-spinner fa-spin"></i> Memuat data UMKM...
            </div>
        </div>
    </div>

    <!-- Tab Pertanian -->
    <div id="tabPertanian" class="ekonomi-tab-content">
        <div class="ekonomi-actions">
            <div class="ekonomi-search">
                <input type="text" id="searchPertanian" placeholder="Cari data pertanian...">
                <i class="fas fa-search"></i>
            </div>
            <button class="ekonomi-btn-primary" onclick="openPertanianModal()">
                <i class="fas fa-plus"></i> Tambah Data Pertanian
            </button>
        </div>

        <div class="ekonomi-table-container">
            <table class="ekonomi-table">
                <thead>
                    <tr>
                        <th>Nama Petani</th>
                        <th>Komoditas</th>
                        <th>Luas Lahan</th>
                        <th>Jenis Lahan</th>
                        <th>Produksi/Musim</th>
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
    </div>

    <!-- Tab Pasar -->
    <div id="tabPasar" class="ekonomi-tab-content">
        <div class="ekonomi-actions">
            <button class="ekonomi-btn-primary" onclick="openPasarModal()">
                <i class="fas fa-plus"></i> Tambah Pasar
            </button>
        </div>

        <div class="ekonomi-cards" id="pasarCards">
            <div class="ekonomi-card-loading">
                <i class="fas fa-spinner fa-spin"></i> Memuat data pasar...
            </div>
        </div>
    </div>

    <!-- Tab Bantuan Sosial -->
    <div id="tabBantuanSosial" class="ekonomi-tab-content">
        <div class="ekonomi-actions">
            <div class="ekonomi-search">
                <input type="text" id="searchBantuan" placeholder="Cari penerima bantuan...">
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

<!-- Modal Mata Pencaharian -->
<div id="modalMataPencaharian" class="ekonomi-modal">
    <div class="ekonomi-modal-content">
        <div class="ekonomi-modal-header">
            <h3 id="modalMataPencaharianTitle">Tambah Mata Pencaharian</h3>
            <button class="ekonomi-modal-close" onclick="closeMataPencaharianModal()">&times;</button>
        </div>
        <form id="formMataPencaharian" class="ekonomi-modal-body">
            <div class="ekonomi-form-grid">
                <div class="ekonomi-form-group">
                    <label>Cari Penduduk *</label>
                    <div class="search-input-container">
                        <input type="text" id="searchPendudukMataPencaharian" placeholder="Ketik nama atau NIK..." autocomplete="off">
                        <input type="hidden" name="nik" id="selectedNikMataPencaharian" required>
                        <div class="search-results" id="searchResultsMataPencaharian"></div>
                    </div>
                </div>
                <div class="ekonomi-form-group">
                    <label>Jenis Pekerjaan *</label>
                    <input type="text" name="jenis_pekerjaan" required>
                </div>
                <div class="ekonomi-form-group">
                    <label>Sektor Pekerjaan</label>
                    <select name="sektor_pekerjaan">
                        <option value="pertanian">Pertanian</option>
                        <option value="industri">Industri</option>
                        <option value="jasa">Jasa</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="ekonomi-form-group">
                    <label>Penghasilan per Bulan</label>
                    <input type="number" name="penghasilan_perbulan" step="0.01">
                </div>
                <div class="ekonomi-form-group">
                    <label>Status Pekerjaan</label>
                    <select name="status_pekerjaan">
                        <option value="tetap">Tetap</option>
                        <option value="kontrak">Kontrak</option>
                        <option value="harian">Harian</option>
                        <option value="musiman">Musiman</option>
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
                    <label>Jenis Komoditas *</label>
                    <input type="text" name="jenis_komoditas" required>
                </div>
                <div class="ekonomi-form-group">
                    <label>Luas Lahan (Ha)</label>
                    <input type="number" name="luas_lahan" step="0.01">
                </div>
                <div class="ekonomi-form-group">
                    <label>Jenis Lahan</label>
                    <select name="jenis_lahan">
                        <option value="sawah">Sawah</option>
                        <option value="ladang">Ladang</option>
                        <option value="kebun">Kebun</option>
                    </select>
                </div>
            </div>
        </form>
        <div class="ekonomi-modal-footer">
            <button type="button" class="ekonomi-btn-secondary" onclick="closePertanianModal()">Batal</button>
            <button type="submit" form="formPertanian" class="ekonomi-btn-primary">Simpan</button>
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
            </div>
            <div class="ekonomi-form-group full-width">
                <label>Alamat Pasar</label>
                <textarea name="alamat_pasar" rows="3"></textarea>
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
                        <input type="text" id="searchPendudukBantuan" placeholder="Ketik nama atau NIK..." autocomplete="off">
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
<?php
if (!$is_ajax) {
    $content = ob_get_clean();
    require_once __DIR__ . '/../../../layout/main.php';
}
?>