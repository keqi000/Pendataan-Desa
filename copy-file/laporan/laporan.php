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
    $page_title = 'Laporan Desa';
    $current_page = 'laporan';
    $page_css = ['laporan.css'];
    $page_js = ['laporan.js'];
    
    ob_start();
}
?>

<div class="laporan-container">
    <div class="laporan-header">
        <h1>Laporan Desa</h1>
        <div class="laporan-breadcrumb">
            <span>Dashboard</span> / <span>Laporan Desa</span>
        </div>
    </div>

    <div class="laporan-tabs">
        <button class="laporan-tab active" data-tab="buat-laporan">
            <i class="fas fa-plus-circle"></i> Buat Laporan
        </button>
        <button class="laporan-tab" data-tab="riwayat-laporan">
            <i class="fas fa-history"></i> Riwayat Laporan
        </button>
        <button class="laporan-tab" data-tab="template">
            <i class="fas fa-file-alt"></i> Template
        </button>
        <button class="laporan-tab" data-tab="export-data">
            <i class="fas fa-download"></i> Export Data
        </button>
    </div>

    <!-- Tab Buat Laporan -->
    <div id="tabBuatLaporan" class="laporan-tab-content active">
        <div class="laporan-quick-reports">
            <h3>Laporan Cepat</h3>
            <div class="quick-report-cards">
                <div class="quick-report-card" onclick="generateQuickReport('kependudukan')">
                    <div class="quick-report-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="quick-report-content">
                        <h4>Laporan Kependudukan</h4>
                        <p>Data penduduk dan keluarga</p>
                    </div>
                </div>
                <div class="quick-report-card" onclick="generateQuickReport('ekonomi')">
                    <div class="quick-report-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="quick-report-content">
                        <h4>Laporan Ekonomi</h4>
                        <p>UMKM, mata pencaharian, pertanian</p>
                    </div>
                </div>
                <div class="quick-report-card" onclick="generateQuickReport('pendidikan')">
                    <div class="quick-report-icon">
                        <i class="fas fa-school"></i>
                    </div>
                    <div class="quick-report-content">
                        <h4>Laporan Pendidikan</h4>
                        <p>Fasilitas dan data pendidikan</p>
                    </div>
                </div>
                <div class="quick-report-card" onclick="generateQuickReport('infrastruktur')">
                    <div class="quick-report-icon">
                        <i class="fas fa-road"></i>
                    </div>
                    <div class="quick-report-content">
                        <h4>Laporan Infrastruktur</h4>
                        <p>Jalan, jembatan, fasilitas</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="laporan-custom">
            <h3>Laporan Custom</h3>
            <form id="formCustomReport" class="custom-report-form">
                <div class="laporan-form-grid">
                    <div class="laporan-form-group">
                        <label>Judul Laporan *</label>
                        <input type="text" name="judul_laporan" required>
                    </div>
                    <div class="laporan-form-group">
                        <label>Jenis Laporan</label>
                        <select name="jenis_laporan">
                            <option value="bulanan">Bulanan</option>
                            <option value="triwulan">Triwulan</option>
                            <option value="tahunan">Tahunan</option>
                            <option value="khusus">Khusus</option>
                        </select>
                    </div>
                    <div class="laporan-form-group">
                        <label>Periode Laporan</label>
                        <input type="text" name="periode_laporan" placeholder="Contoh: Januari 2024">
                    </div>
                </div>
                <div class="laporan-form-group">
                    <label>Kategori Data</label>
                    <div class="checkbox-group">
                        <label class="checkbox-item">
                            <input type="checkbox" name="kategori[]" value="kependudukan">
                            <span>Kependudukan</span>
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" name="kategori[]" value="ekonomi">
                            <span>Ekonomi</span>
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" name="kategori[]" value="pendidikan">
                            <span>Pendidikan</span>
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" name="kategori[]" value="infrastruktur">
                            <span>Infrastruktur</span>
                        </label>
                    </div>
                </div>
                <div class="laporan-form-actions">
                    <button type="submit" class="laporan-btn-primary">
                        <i class="fas fa-file-pdf"></i> Generate Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tab Riwayat Laporan -->
    <div id="tabRiwayatLaporan" class="laporan-tab-content">
        <div class="laporan-actions">
            <div class="laporan-search">
                <input type="text" id="searchLaporan" placeholder="Cari laporan...">
                <i class="fas fa-search"></i>
            </div>
            <div class="laporan-filters">
                <select id="filterJenis">
                    <option value="">Semua Jenis</option>
                    <option value="bulanan">Bulanan</option>
                    <option value="triwulan">Triwulan</option>
                    <option value="tahunan">Tahunan</option>
                    <option value="khusus">Khusus</option>
                </select>
                <select id="filterStatus">
                    <option value="">Semua Status</option>
                    <option value="draft">Draft</option>
                    <option value="final">Final</option>
                    <option value="terkirim">Terkirim</option>
                </select>
            </div>
        </div>

        <div class="laporan-table-container">
            <table class="laporan-table">
                <thead>
                    <tr>
                        <th>Judul Laporan</th>
                        <th>Jenis</th>
                        <th>Periode</th>
                        <th>Status</th>
                        <th>Tanggal Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="laporanTableBody">
                    <tr>
                        <td colspan="6" class="text-center">
                            <i class="fas fa-spinner fa-spin"></i> Memuat data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab Template -->
    <div id="tabTemplate" class="laporan-tab-content">
        <div class="template-actions">
            <button class="laporan-btn-primary" onclick="openTemplateModal()">
                <i class="fas fa-plus"></i> Buat Template
            </button>
        </div>

        <div class="template-cards" id="templateCards">
            <div class="template-card-loading">
                <i class="fas fa-spinner fa-spin"></i> Memuat template...
            </div>
        </div>
    </div>

    <!-- Tab Export Data -->
    <div id="tabExportData" class="laporan-tab-content">
        <div class="export-section">
            <h3>Export Data Desa</h3>
            <div class="export-cards">
                <div class="export-card">
                    <div class="export-icon">
                        <i class="fas fa-file-excel"></i>
                    </div>
                    <div class="export-content">
                        <h4>Export ke Excel</h4>
                        <p>Download semua data dalam format Excel</p>
                        <button class="laporan-btn-secondary" onclick="exportData('excel')">
                            <i class="fas fa-download"></i> Download Excel
                        </button>
                    </div>
                </div>
                <div class="export-card">
                    <div class="export-icon">
                        <i class="fas fa-file-csv"></i>
                    </div>
                    <div class="export-content">
                        <h4>Export ke CSV</h4>
                        <p>Download data dalam format CSV</p>
                        <button class="laporan-btn-secondary" onclick="exportData('csv')">
                            <i class="fas fa-download"></i> Download CSV
                        </button>
                    </div>
                </div>
                <div class="export-card">
                    <div class="export-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="export-content">
                        <h4>Backup Database</h4>
                        <p>Backup lengkap database desa</p>
                        <button class="laporan-btn-secondary" onclick="backupDatabase()">
                            <i class="fas fa-download"></i> Backup DB
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Template -->
<div id="modalTemplate" class="laporan-modal">
    <div class="laporan-modal-content">
        <div class="laporan-modal-header">
            <h3>Buat Template Laporan</h3>
            <button class="laporan-modal-close" onclick="closeTemplateModal()">&times;</button>
        </div>
        <form id="formTemplate" class="laporan-modal-body">
            <div class="laporan-form-group">
                <label>Nama Template *</label>
                <input type="text" name="nama_template" required>
            </div>
            <div class="laporan-form-group">
                <label>Konfigurasi Template</label>
                <textarea name="konfigurasi" rows="10" placeholder="Masukkan konfigurasi template dalam format JSON"></textarea>
            </div>
        </form>
        <div class="laporan-modal-footer">
            <button type="button" class="laporan-btn-secondary" onclick="closeTemplateModal()">Batal</button>
            <button type="submit" form="formTemplate" class="laporan-btn-primary">Simpan Template</button>
        </div>
    </div>
</div>
<?php
if (!$is_ajax) {
    $content = ob_get_clean();
    require_once __DIR__ . '/../../../layout/main.php';
}
?>