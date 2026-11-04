<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../database/queries.php';
require_once __DIR__ . '/pendidikan-functions.php';
session_start();

$pendidikan = new PendidikanFunctions();
$pendidikan->checkAuthentication();

$is_ajax = $pendidikan->isAjaxRequest();

// Handle AJAX requests
if ($is_ajax) {
    $pendidikan->handleAjaxRequest();
}

if (!$is_ajax) {
    $config = $pendidikan->getPageConfig();
    extract($config);
    ob_start();
}
?>

<div class="pendidikan-container">
    <div class="pendidikan-header">
        <h1>Data Pendidikan</h1>
        <div class="pendidikan-breadcrumb">
            <span>Dashboard</span> / <span>Data Pendidikan</span>
        </div>
    </div>

    <div class="pendidikan-actions">
        <div class="pendidikan-search">
            <input type="text" id="searchFasilitas" placeholder="Cari fasilitas pendidikan...">
            <i class="fas fa-search"></i>
        </div>
        <div class="pendidikan-filters">
            <select id="filterJenis">
                <option value="">Semua Jenis</option>
                <option value="PAUD">PAUD</option>
                <option value="TK">TK</option>
                <option value="SD">SD</option>
                <option value="SMP">SMP</option>
                <option value="SMA">SMA</option>
                <option value="SMK">SMK</option>
            </select>
            <select id="filterKondisi">
                <option value="">Semua Kondisi</option>
                <option value="Baik">Baik</option>
                <option value="Sedang">Sedang</option>
                <option value="Rusak">Rusak</option>
            </select>
        </div>
        <button class="pendidikan-btn-primary" onclick="openFasilitasModal()">
            <i class="fas fa-plus"></i> Tambah Fasilitas
        </button>
    </div>

    <div class="pendidikan-stats">
        <div class="stat-card">
            <div class="stat-icon paud">
                <i class="fas fa-baby"></i>
            </div>
            <div class="stat-content">
                <h4 id="totalPAUD">0</h4>
                <p>PAUD</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon tk">
                <i class="fas fa-child"></i>
            </div>
            <div class="stat-content">
                <h4 id="totalTK">0</h4>
                <p>TK</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon sd">
                <i class="fas fa-school"></i>
            </div>
            <div class="stat-content">
                <h4 id="totalSD">0</h4>
                <p>SD</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon smp">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="stat-content">
                <h4 id="totalSMP">0</h4>
                <p>SMP</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon sma">
                <i class="fas fa-university"></i>
            </div>
            <div class="stat-content">
                <h4 id="totalSMA">0</h4>
                <p>SMA</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon smk">
                <i class="fas fa-tools"></i>
            </div>
            <div class="stat-content">
                <h4 id="totalSMK">0</h4>
                <p>SMK</p>
            </div>
        </div>
    </div>

    <div class="pendidikan-cards" id="fasilitasCards">
        <div class="pendidikan-card-loading">
            <i class="fas fa-spinner fa-spin"></i> Memuat data fasilitas...
        </div>
    </div>
</div>

<!-- Modal Fasilitas -->
<div id="modalFasilitas" class="pendidikan-modal">
    <div class="pendidikan-modal-content">
        <div class="pendidikan-modal-header">
            <h3 id="modalFasilitasTitle">Tambah Fasilitas Pendidikan</h3>
            <button class="pendidikan-modal-close" onclick="closeFasilitasModal()">&times;</button>
        </div>
        <form id="formFasilitas" class="pendidikan-modal-body">
            <div class="pendidikan-form-grid">
                <div class="pendidikan-form-group">
                    <label>Nama Fasilitas *</label>
                    <input type="text" name="nama_fasilitas" required>
                </div>
                <div class="pendidikan-form-group">
                    <label>Jenis Pendidikan *</label>
                    <select name="jenis_pendidikan" required>
                        <option value="">Pilih Jenis</option>
                        <option value="PAUD">PAUD</option>
                        <option value="TK">TK</option>
                        <option value="SD">SD</option>
                        <option value="SMP">SMP</option>
                        <option value="SMA">SMA</option>
                        <option value="SMK">SMK</option>
                    </select>
                </div>
                <div class="pendidikan-form-group">
                    <label>Kapasitas Siswa</label>
                    <input type="number" name="kapasitas_siswa" min="0">
                </div>
                <div class="pendidikan-form-group">
                    <label>Jumlah Guru</label>
                    <input type="number" name="jumlah_guru" min="0">
                </div>
                <div class="pendidikan-form-group">
                    <label>Kondisi Bangunan</label>
                    <select name="kondisi_bangunan">
                        <option value="Baik">Baik</option>
                        <option value="Sedang">Sedang</option>
                        <option value="Rusak">Rusak</option>
                    </select>
                </div>
            </div>
            <div class="pendidikan-form-group koordinat-group">
                <label>Koordinat</label>
                <div class="koordinat-input">
                    <input type="text" name="koordinat_latitude" placeholder="Latitude">
                    <input type="text" name="koordinat_longitude" placeholder="Longitude">
                </div>
            </div>
            <div class="pendidikan-form-group full-width">
                <label>Alamat Fasilitas</label>
                <textarea name="alamat_fasilitas" rows="3"></textarea>
            </div>
        </form>
        <div class="pendidikan-modal-footer">
            <button type="button" class="pendidikan-btn-secondary" onclick="closeFasilitasModal()">Batal</button>
            <button type="submit" form="formFasilitas" class="pendidikan-btn-primary">Simpan</button>
        </div>
    </div>
</div>
<?php
if (!$is_ajax) {
    $content = ob_get_clean();
    require_once __DIR__ . '/../../../../layout/main.php';
}
?>