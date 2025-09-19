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
    $page_title = 'Data Umum Desa';
    $current_page = 'data-umum';
    $page_css = ['data-umum.css'];
    $page_js = ['data-umum.js'];
    
    ob_start();
}
?>

<div class="data-umum-container">
    <div class="data-umum-header">
        <h1>Data Umum Desa</h1>
        <div class="data-umum-breadcrumb">
            <span>Dashboard</span> / <span>Data Umum Desa</span>
        </div>
    </div>

    <div class="data-umum-content">
        <!-- Informasi Dasar -->
        <div class="data-umum-card">
            <div class="data-umum-card-header">
                <h3>Informasi Dasar Desa</h3>
                <button class="data-umum-btn-primary" onclick="editInformasiDasar()">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
            <div class="data-umum-card-content">
                <form id="formInformasiDasar" class="data-umum-form">
                    <div class="data-umum-form-grid">
                        <div class="data-umum-form-group">
                            <label>Nama Desa</label>
                            <input type="text" id="namaDesa" name="nama_desa" readonly>
                        </div>
                        <div class="data-umum-form-group">
                            <label>Nama Kecamatan</label>
                            <input type="text" id="namaKecamatan" name="nama_kecamatan" readonly>
                        </div>
                        <div class="data-umum-form-group">
                            <label>Nama Kabupaten</label>
                            <input type="text" id="namaKabupaten" name="nama_kabupaten" readonly>
                        </div>
                        <div class="data-umum-form-group">
                            <label>Nama Provinsi</label>
                            <input type="text" id="namaProvinsi" name="nama_provinsi" readonly>
                        </div>
                        <div class="data-umum-form-group">
                            <label>Luas Wilayah (km²)</label>
                            <input type="number" id="luasWilayah" name="luas_wilayah" step="0.01" readonly>
                        </div>
                        <div class="data-umum-form-group">
                            <label>Jumlah Dusun</label>
                            <input type="number" id="jumlahDusun" name="jumlah_dusun" readonly>
                        </div>
                        <div class="data-umum-form-group">
                            <label>Jumlah RT</label>
                            <input type="number" id="jumlahRt" name="jumlah_rt" readonly>
                        </div>
                        <div class="data-umum-form-group">
                            <label>Jumlah RW</label>
                            <input type="number" id="jumlahRw" name="jumlah_rw" readonly>
                        </div>
                    </div>
                    <div class="data-umum-form-actions" id="formActions" style="display: none;">
                        <button type="button" class="data-umum-btn-secondary" onclick="cancelEdit()">Batal</button>
                        <button type="submit" class="data-umum-btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Peta Desa -->
        <div class="data-umum-card">
            <div class="data-umum-card-header">
                <h3>Peta Desa</h3>
            </div>
            <div class="data-umum-card-content">
                <div class="peta-desa-container">
                    <div class="peta-desa-wrapper">
                        <div id="petaDesaMap" class="peta-desa-map"></div>
                        <div class="peta-desa-legend">
                            <h4>Legend</h4>
                            <div class="legend-item" data-layer="boundary">
                                <div class="legend-color" style="background: #1abc9c;"></div>
                                <span>Batas Desa</span>
                            </div>
                            <div class="legend-item" data-layer="fasilitas">
                                <div class="legend-color" style="background: #e74c3c;"></div>
                                <span>Fasilitas Pendidikan</span>
                            </div>
                            <div class="legend-item" data-layer="jalan">
                                <div class="legend-color" style="background: #3498db;"></div>
                                <span>Jalan</span>
                            </div>
                            <div class="legend-item" data-layer="jembatan">
                                <div class="legend-color" style="background: #f39c12;"></div>
                                <span>Jembatan</span>
                            </div>
                        </div>
                        <div class="peta-desa-controls">
                            <button class="peta-control-btn" onclick="toggleDesaFullscreen()" title="Fullscreen">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>
</div>

<?php
if (!$is_ajax) {
    $content = ob_get_clean();
    require_once __DIR__ . '/../../../layout/main.php';
}
?>

