<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../database/queries.php';
require_once __DIR__ . '/infrastruktur-functions.php';
session_start();

$infrastruktur = new InfrastrukturFunctions();
$infrastruktur->checkAuthentication();

$is_ajax = $infrastruktur->isAjaxRequest();

// Handle AJAX requests
if ($is_ajax) {
    $infrastruktur->handleAjaxRequest();
}

if (!$is_ajax) {
    $config = $infrastruktur->getPageConfig();
    $config['page_js'] = [
        'infrastruktur.js',
        'js/infrastruktur-core.js',
        'js/infrastruktur-jalan.js', 
        'js/infrastruktur-jembatan.js'
    ];
    extract($config);
    ob_start();
}
?>

<div class="infrastruktur-container">
    <div class="infrastruktur-header">
        <h1>Data Infrastruktur</h1>
        <div class="infrastruktur-breadcrumb">
            <span>Dashboard</span> / <span>Data Infrastruktur</span>
        </div>
    </div>

    <div class="infrastruktur-tabs">
        <button class="infrastruktur-tab active" data-tab="jalan">
            <i class="fas fa-road"></i> Jalan Desa
        </button>
        <button class="infrastruktur-tab" data-tab="jembatan">
            <i class="fas fa-bridge-water"></i> Jembatan
        </button>
    </div>

    <!-- Tab Jalan -->
    <div id="tabJalan" class="infrastruktur-tab-content active">
        <div class="infrastruktur-actions">
            <div class="infrastruktur-search">
                <input type="text" id="searchJalan" placeholder="Cari jalan...">
                <i class="fas fa-search"></i>
            </div>
            <div class="infrastruktur-filters">
                <select id="filterKondisiJalan">
                    <option value="">Semua Kondisi</option>
                    <option value="baik">Baik</option>
                    <option value="sedang">Sedang</option>
                    <option value="rusak">Rusak</option>
                </select>
                <select id="filterPermukaan">
                    <option value="">Semua Permukaan</option>
                    <option value="aspal">Aspal</option>
                    <option value="beton">Beton</option>
                    <option value="tanah">Tanah</option>
                    <option value="kerikil">Kerikil</option>
                </select>
            </div>
            <button class="infrastruktur-btn-primary" onclick="openJalanModal()">
                <i class="fas fa-plus"></i> Tambah Jalan
            </button>
        </div>

        <div class="infrastruktur-table-container">
            <table class="infrastruktur-table">
                <thead>
                    <tr>
                        <th>Nama Jalan</th>
                        <th>Panjang (m)</th>
                        <th>Lebar (m)</th>
                        <th>Kondisi</th>
                        <th>Permukaan</th>
                        <th>Foto</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="jalanTableBody">
                    <tr>
                        <td colspan="7" class="text-center">
                            <i class="fas fa-spinner fa-spin"></i> Memuat data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab Jembatan -->
    <div id="tabJembatan" class="infrastruktur-tab-content">
        <div class="infrastruktur-actions">
            <div class="infrastruktur-search">
                <input type="text" id="searchJembatan" placeholder="Cari jembatan...">
                <i class="fas fa-search"></i>
            </div>
            <div class="infrastruktur-filters">
                <select id="filterKondisiJembatan">
                    <option value="">Semua Kondisi</option>
                    <option value="baik">Baik</option>
                    <option value="sedang">Sedang</option>
                    <option value="rusak">Rusak</option>
                </select>
            </div>
            <button class="infrastruktur-btn-primary" onclick="openJembatanModal()">
                <i class="fas fa-plus"></i> Tambah Jembatan
            </button>
        </div>

        <div class="infrastruktur-cards" id="jembatanCards">
            <div class="infrastruktur-card-loading">
                <i class="fas fa-spinner fa-spin"></i> Memuat data jembatan...
            </div>
        </div>
    </div>
</div>

<!-- Modal Jalan -->
<div id="modalJalan" class="infrastruktur-modal">
    <div class="infrastruktur-modal-content">
        <div class="infrastruktur-modal-header">
            <h3 id="modalJalanTitle">Tambah Jalan</h3>
            <button class="infrastruktur-modal-close" onclick="closeJalanModal()">&times;</button>
        </div>
        <form id="formJalan" class="infrastruktur-modal-body">
            <div class="infrastruktur-form-grid">
                <div class="infrastruktur-form-group">
                    <label>Nama Jalan *</label>
                    <input type="text" name="nama_jalan" required>
                </div>
                <div class="infrastruktur-form-group">
                    <label>Panjang Jalan (m) *</label>
                    <input type="number" name="panjang_jalan" step="0.01" required>
                </div>
                <div class="infrastruktur-form-group">
                    <label>Lebar Jalan (m)</label>
                    <input type="number" name="lebar_jalan" step="0.01">
                </div>
                <div class="infrastruktur-form-group">
                    <label>Kondisi Jalan</label>
                    <select name="kondisi_jalan">
                        <option value="baik">Baik</option>
                        <option value="sedang">Sedang</option>
                        <option value="rusak">Rusak</option>
                    </select>
                </div>
                <div class="infrastruktur-form-group">
                    <label>Jenis Permukaan</label>
                    <select name="jenis_permukaan">
                        <option value="tanah">Tanah</option>
                        <option value="aspal">Aspal</option>
                        <option value="beton">Beton</option>
                        <option value="kerikil">Kerikil</option>
                    </select>
                </div>
                <div class="infrastruktur-form-group">
                    <label>Foto Jalan</label>
                    <input type="file" name="foto_jalan" accept="image/*">
                </div>
            </div>

        </form>
        <div class="infrastruktur-modal-footer">
            <button type="button" class="infrastruktur-btn-secondary" onclick="closeJalanModal()">Batal</button>
            <button type="submit" form="formJalan" class="infrastruktur-btn-primary">Simpan</button>
        </div>
    </div>
</div>

<!-- Modal Jembatan -->
<div id="modalJembatan" class="infrastruktur-modal">
    <div class="infrastruktur-modal-content">
        <div class="infrastruktur-modal-header">
            <h3 id="modalJembatanTitle">Tambah Jembatan</h3>
            <button class="infrastruktur-modal-close" onclick="closeJembatanModal()">&times;</button>
        </div>
        <form id="formJembatan" class="infrastruktur-modal-body">
            <div class="infrastruktur-form-grid">
                <div class="infrastruktur-form-group">
                    <label>Nama Jembatan *</label>
                    <input type="text" name="nama_jembatan" required>
                </div>
                <div class="infrastruktur-form-group">
                    <label>Panjang Jembatan (m) *</label>
                    <input type="number" name="panjang_jembatan" step="0.01" required>
                </div>
                <div class="infrastruktur-form-group">
                    <label>Lebar Jembatan (m)</label>
                    <input type="number" name="lebar_jembatan" step="0.01">
                </div>
                <div class="infrastruktur-form-group">
                    <label>Kondisi Jembatan</label>
                    <select name="kondisi_jembatan">
                        <option value="baik">Baik</option>
                        <option value="sedang">Sedang</option>
                        <option value="rusak">Rusak</option>
                    </select>
                </div>
                <div class="infrastruktur-form-group">
                    <label>Material Jembatan</label>
                    <input type="text" name="material_jembatan" placeholder="Contoh: Beton, Kayu, Besi">
                </div>

                <div class="infrastruktur-form-group">
                    <label>Foto Jembatan</label>
                    <input type="file" name="foto_jembatan" accept="image/*">
                </div>
                <div class="infrastruktur-form-group">
                    <label>Koordinat</label>
                    <div class="koordinat-input">
                        <input type="text" name="koordinat_latitude" placeholder="Latitude">
                        <input type="text" name="koordinat_longitude" placeholder="Longitude">
                    </div>
                </div>

            </div>
        </form>
        <div class="infrastruktur-modal-footer">
            <button type="button" class="infrastruktur-btn-secondary" onclick="closeJembatanModal()">Batal</button>
            <button type="submit" form="formJembatan" class="infrastruktur-btn-primary">Simpan</button>
        </div>
    </div>
</div>
<?php
if (!$is_ajax) {
    $content = ob_get_clean();
    require_once __DIR__ . '/../../../../layout/main.php';
}
?>