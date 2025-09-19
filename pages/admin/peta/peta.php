<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../database/queries.php';
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin_kecamatan') {
    header('Location: ' . BASE_URL . 'pages/auth/login.php');
    exit();
}

$queries = new Queries();

// Get all facilities data
$all_fasilitas = [];
$all_jalan = [];
$all_jembatan = [];

// Update desa query to include boundary data
$query = "SELECT * FROM desa ORDER BY nama_desa";
$stmt = $queries->db->prepare($query);
$stmt->execute();
$all_desa = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($all_desa as $desa) {
    $id_desa = $desa['id_desa'];
    
    // Get facilities with coordinates
    $query = "SELECT f.*, d.nama_desa FROM fasilitas_pendidikan f 
              JOIN desa d ON f.id_desa = d.id_desa 
              WHERE f.id_desa = :id_desa";
    $stmt = $queries->db->prepare($query);
    $stmt->bindParam(':id_desa', $id_desa);
    $stmt->execute();
    $fasilitas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($fasilitas as $f) {
        $all_fasilitas[] = $f;
    }
    
    // Get roads with coordinates
    $query = "SELECT j.*, d.nama_desa FROM infrastruktur_jalan j 
              JOIN desa d ON j.id_desa = d.id_desa 
              WHERE j.id_desa = :id_desa";
    $stmt = $queries->db->prepare($query);
    $stmt->bindParam(':id_desa', $id_desa);
    $stmt->execute();
    $jalan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($jalan as $j) {
        $all_jalan[] = $j;
    }
    
    // Get bridges with coordinates
    $query = "SELECT j.*, d.nama_desa FROM infrastruktur_jembatan j 
              JOIN desa d ON j.id_desa = d.id_desa 
              WHERE j.id_desa = :id_desa";
    $stmt = $queries->db->prepare($query);
    $stmt->bindParam(':id_desa', $id_desa);
    $stmt->execute();
    $jembatan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($jembatan as $jmb) {
        $all_jembatan[] = $jmb;
    }
}

// Check if this is AJAX request
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    // Full page load
    $page_title = 'Peta & Geografis Kecamatan';
    $current_page = 'peta';
    $page_css = [BASE_URL . 'pages/admin/peta/peta.css'];
    $page_js = [BASE_URL . 'pages/admin/peta/peta.js'];

    ob_start();
}
?>

<div class="peta-admin-container">
    <div class="peta-admin-header">
        <div class="peta-admin-title">
            <h2><i class="fas fa-map"></i> Peta & Geografis Kecamatan Tibawa</h2>
            <p>Visualisasi geografis fasilitas dan infrastruktur desa</p>
        </div>
        <div class="peta-admin-controls">
            <button class="btn btn-warning" onclick="uploadAdministrativeBoundaries()">
                <i class="fas fa-upload"></i> Upload Batas Administratif
            </button>
            <button class="btn btn-secondary" onclick="resetMapView()">
                <i class="fas fa-home"></i> Reset View
            </button>
        </div>
    </div>

    <div class="peta-admin-content">
        <!-- Map Container -->
        <div class="peta-admin-map-section">
            <div class="card">
                <div class="card-header">
                    <h3>Peta Kecamatan Tibawa</h3>
                    <div class="map-legend">
                        <div class="legend-item" onclick="toggleLayer('fasilitas')" data-layer="fasilitas">
                            <span class="legend-color" style="background: #e74c3c;"></span>
                            <span>Fasilitas Pendidikan</span>
                        </div>
                        <div class="legend-item" onclick="toggleLayer('jalan')" data-layer="jalan">
                            <span class="legend-color" style="background: #3498db;"></span>
                            <span>Jalan</span>
                        </div>
                        <div class="legend-item" onclick="toggleLayer('jembatan')" data-layer="jembatan">
                            <span class="legend-color" style="background: #f39c12;"></span>
                            <span>Jembatan</span>
                        </div>
                        <div class="legend-item" onclick="toggleLayer('desa')" data-layer="desa">
                            <span class="legend-color" style="background: #1abc9c;"></span>
                            <span>Desa</span>
                        </div>
                        <div class="legend-item" onclick="toggleLayer('kecamatan')" data-layer="kecamatan">
                            <span class="legend-color" style="background: #2980b9;"></span>
                            <span>Kecamatan</span>
                        </div>
                        <div class="legend-item" onclick="toggleLayer('kabupaten')" data-layer="kabupaten">
                            <span class="legend-color" style="background: #e67e22;"></span>
                            <span>Kabupaten</span>
                        </div>
                        <div class="legend-item" onclick="toggleLayer('provinsi')" data-layer="provinsi">
                            <span class="legend-color" style="background: #8e44ad;"></span>
                            <span>Provinsi</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="mapContainer" class="map-container">
                        <div class="map-loading">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Memuat peta...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="peta-admin-stats">
            <div class="card peta-stat-card">
                <div class="card-body">
                    <div class="peta-stat-icon">
                        <i class="fas fa-school"></i>
                    </div>
                    <div class="peta-stat-content">
                        <div class="peta-stat-number"><?php echo count($all_fasilitas); ?></div>
                        <div class="peta-stat-label">Fasilitas Pendidikan</div>
                        <div class="peta-stat-detail">
                            <?php
                            $jenis_count = [];
                            foreach ($all_fasilitas as $f) {
                                $jenis_count[$f['jenis_pendidikan']] = ($jenis_count[$f['jenis_pendidikan']] ?? 0) + 1;
                            }
                            echo implode(', ', array_map(function($k, $v) { return "$k: $v"; }, array_keys($jenis_count), $jenis_count));
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card peta-stat-card">
                <div class="card-body">
                    <div class="peta-stat-icon">
                        <i class="fas fa-road"></i>
                    </div>
                    <div class="peta-stat-content">
                        <div class="peta-stat-number"><?php echo count($all_jalan); ?></div>
                        <div class="peta-stat-label">Jalan Desa</div>
                        <div class="peta-stat-detail">
                            <?php
                            $total_panjang = array_sum(array_column($all_jalan, 'panjang_jalan'));
                            echo number_format($total_panjang, 1) . ' km total';
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card peta-stat-card">
                <div class="card-body">
                    <div class="peta-stat-icon">
                        <i class="fas fa-bridge"></i>
                    </div>
                    <div class="peta-stat-content">
                        <div class="peta-stat-number"><?php echo count($all_jembatan); ?></div>
                        <div class="peta-stat-label">Jembatan</div>
                        <div class="peta-stat-detail">
                            <?php
                            $kondisi_baik = count(array_filter($all_jembatan, function($j) { return $j['kondisi_jembatan'] === 'baik'; }));
                            echo "$kondisi_baik dalam kondisi baik";
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card peta-stat-card">
                <div class="card-body">
                    <div class="peta-stat-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <div class="peta-stat-content">
                        <div class="peta-stat-number"><?php echo count($all_desa); ?></div>
                        <div class="peta-stat-label">Desa</div>
                        <div class="peta-stat-detail">
                            <?php
                            $total_luas = array_sum(array_column($all_desa, 'luas_wilayah'));
                            echo number_format($total_luas) . ' ha total';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Tables -->
        <div class="peta-admin-tables">
            <!-- Desa -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-map-marked-alt"></i> Desa</h3>
                    <input type="text" id="searchDesa" class="search-input" placeholder="Cari desa...">
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="desaTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Desa</th>
                                    <th>Luas Wilayah</th>
                                    <th>Jumlah Dusun</th>
                                    <th>RT/RW</th>

                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_desa as $index => $d): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $d['nama_desa']; ?></td>
                                    <td><?php echo $d['luas_wilayah']; ?> ha</td>
                                    <td><?php echo $d['jumlah_dusun']; ?> dusun</td>
                                    <td><?php echo $d['jumlah_rt']; ?>/<?php echo $d['jumlah_rw']; ?></td>

                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="showOnMap('desa', <?php echo $d['id_desa']; ?>)">
                                            <i class="fas fa-map-marker-alt"></i> Lihat di Peta
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-controls">
                        <div class="pagination-info">
                            <select class="items-per-page" id="desaPerPage" onchange="changeItemsPerPage('desa', this.value)">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span class="pagination-text">Menampilkan <span id="desaStart">1</span>-<span id="desaEnd">10</span> dari <span id="desaTotal"><?php echo count($all_desa); ?></span> data</span>
                        </div>
                        <div class="pagination-buttons">
                            <button class="pagination-btn" onclick="changePage('desa', 'prev')" id="desaPrev">‹ Sebelumnya</button>
                            <span class="page-info" id="desaPageInfo">Halaman 1</span>
                            <button class="pagination-btn" onclick="changePage('desa', 'next')" id="desaNext">Selanjutnya ›</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Fasilitas Pendidikan -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-school"></i> Fasilitas Pendidikan</h3>
                    <input type="text" id="searchFasilitas" class="search-input" placeholder="Cari fasilitas...">
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="fasilitasTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Fasilitas</th>
                                    <th>Jenis</th>
                                    <th>Desa</th>
                                    <th>Kondisi</th>
                                    <th>Kapasitas</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_fasilitas as $index => $f): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $f['nama_fasilitas']; ?></td>
                                    <td><span class="badge badge-info"><?php echo $f['jenis_pendidikan']; ?></span></td>
                                    <td><?php echo $f['nama_desa']; ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $f['kondisi_bangunan'] === 'Baik' ? 'success' : ($f['kondisi_bangunan'] === 'Sedang' ? 'warning' : 'danger'); ?>">
                                            <?php echo $f['kondisi_bangunan']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $f['kapasitas_siswa']; ?> siswa</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="showOnMap('fasilitas', <?php echo $f['id_fasilitas']; ?>)">
                                            <i class="fas fa-map-marker-alt"></i> Lihat di Peta
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-controls">
                        <div class="pagination-info">
                            <select class="items-per-page" id="fasilitasPerPage" onchange="changeItemsPerPage('fasilitas', this.value)">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span class="pagination-text">Menampilkan <span id="fasilitasStart">1</span>-<span id="fasilitasEnd">10</span> dari <span id="fasilitasTotal"><?php echo count($all_fasilitas); ?></span> data</span>
                        </div>
                        <div class="pagination-buttons">
                            <button class="pagination-btn" onclick="changePage('fasilitas', 'prev')" id="fasilitasPrev">‹ Sebelumnya</button>
                            <span class="page-info" id="fasilitasPageInfo">Halaman 1</span>
                            <button class="pagination-btn" onclick="changePage('fasilitas', 'next')" id="fasilitasNext">Selanjutnya ›</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Infrastruktur Jalan -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-road"></i> Infrastruktur Jalan</h3>
                    <input type="text" id="searchJalan" class="search-input" placeholder="Cari jalan...">
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="jalanTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Jalan</th>
                                    <th>Desa</th>
                                    <th>Panjang</th>
                                    <th>Kondisi</th>
                                    <th>Jenis Permukaan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_jalan as $index => $j): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $j['nama_jalan']; ?></td>
                                    <td><?php echo $j['nama_desa']; ?></td>
                                    <td><?php echo $j['panjang_jalan']; ?> km</td>
                                    <td>
                                        <span class="badge badge-<?php echo $j['kondisi_jalan'] === 'baik' ? 'success' : ($j['kondisi_jalan'] === 'sedang' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($j['kondisi_jalan']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo ucfirst($j['jenis_permukaan']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="showOnMap('jalan', <?php echo $j['id_jalan']; ?>)">
                                            <i class="fas fa-map-marker-alt"></i> Lihat di Peta
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-controls">
                        <div class="pagination-info">
                            <select class="items-per-page" id="jalanPerPage" onchange="changeItemsPerPage('jalan', this.value)">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span class="pagination-text">Menampilkan <span id="jalanStart">1</span>-<span id="jalanEnd">10</span> dari <span id="jalanTotal"><?php echo count($all_jalan); ?></span> data</span>
                        </div>
                        <div class="pagination-buttons">
                            <button class="pagination-btn" onclick="changePage('jalan', 'prev')" id="jalanPrev">‹ Sebelumnya</button>
                            <span class="page-info" id="jalanPageInfo">Halaman 1</span>
                            <button class="pagination-btn" onclick="changePage('jalan', 'next')" id="jalanNext">Selanjutnya ›</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Infrastruktur Jembatan -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-bridge"></i> Infrastruktur Jembatan</h3>
                    <input type="text" id="searchJembatan" class="search-input" placeholder="Cari jembatan...">
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="jembatanTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Jembatan</th>
                                    <th>Desa</th>
                                    <th>Panjang</th>
                                    <th>Kondisi</th>
                                    <th>Material</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_jembatan as $index => $jmb): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $jmb['nama_jembatan']; ?></td>
                                    <td><?php echo $jmb['nama_desa']; ?></td>
                                    <td><?php echo $jmb['panjang_jembatan']; ?> m</td>
                                    <td>
                                        <span class="badge badge-<?php echo $jmb['kondisi_jembatan'] === 'baik' ? 'success' : ($jmb['kondisi_jembatan'] === 'sedang' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($jmb['kondisi_jembatan']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $jmb['material_jembatan']; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="showOnMap('jembatan', <?php echo $jmb['id_jembatan']; ?>)">
                                            <i class="fas fa-map-marker-alt"></i> Lihat di Peta
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-controls">
                        <div class="pagination-info">
                            <select class="items-per-page" id="jembatanPerPage" onchange="changeItemsPerPage('jembatan', this.value)">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span class="pagination-text">Menampilkan <span id="jembatanStart">1</span>-<span id="jembatanEnd">10</span> dari <span id="jembatanTotal"><?php echo count($all_jembatan); ?></span> data</span>
                        </div>
                        <div class="pagination-buttons">
                            <button class="pagination-btn" onclick="changePage('jembatan', 'prev')" id="jembatanPrev">‹ Sebelumnya</button>
                            <span class="page-info" id="jembatanPageInfo">Halaman 1</span>
                            <button class="pagination-btn" onclick="changePage('jembatan', 'next')" id="jembatanNext">Selanjutnya ›</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Administrative Boundaries Upload Modal -->
<div class="modal" id="uploadAdministrativeBoundariesModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="modal-dialog" style="background: white; padding: 20px; border-radius: 8px; max-width: 600px; width: 90%;">
        <div class="modal-header" style="border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px;">
            <h4 class="modal-title">Upload Batas Administratif GeoJSON</h4>
            <button type="button" class="close" onclick="closeAdministrativeBoundariesModal()" style="float: right; border: none; background: none; font-size: 24px;">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group" style="margin-bottom: 15px;">
                <label>File GeoJSON Batas Administratif:</label>
                <input type="file" id="administrativeBoundariesFile" accept=".geojson,.json" class="form-control" style="margin-top: 5px;">
                <small style="color: #666; font-size: 12px;">File GeoJSON dengan FeatureCollection berisi batas provinsi, kabupaten, kecamatan, dan desa</small>
            </div>
            <div id="boundariesPreviewInfo" style="display: none; margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                <strong>Preview:</strong>
                <div id="boundariesPreviewContent"></div>
            </div>
            <div class="alert" id="administrativeBoundariesUploadStatus" style="display: none; padding: 10px; border-radius: 4px; margin-top: 10px;"></div>
        </div>
        <div class="modal-footer" style="border-top: 1px solid #eee; padding-top: 15px; text-align: right;">
            <button type="button" class="btn btn-secondary" onclick="closeAdministrativeBoundariesModal()">Batal</button>
            <button type="button" class="btn btn-primary" onclick="processAdministrativeBoundaries()">Upload Batas Administratif</button>
        </div>
    </div>
</div>

<script>
// Pass data to JavaScript - ensure data is available immediately
window.petaData = {
    desa: <?php echo json_encode($all_desa); ?>,
    fasilitas: <?php echo json_encode($all_fasilitas); ?>,
    jalan: <?php echo json_encode($all_jalan); ?>,
    jembatan: <?php echo json_encode($all_jembatan); ?>
};

// Pass BASE_URL to JavaScript
window.BASE_URL = '<?php echo BASE_URL; ?>';

// Mark data as ready
window.petaDataReady = true;
console.log('🗺️ [PHP] Peta data loaded:', {
    desa: window.petaData.desa.length,
    fasilitas: window.petaData.fasilitas.length, 
    jalan: window.petaData.jalan.length,
    jembatan: window.petaData.jembatan.length
});
console.log('🗺️ [PHP] BASE_URL set to:', window.BASE_URL);
console.log('🗺️ [PHP] Peta data ready flag set to:', window.petaDataReady);
</script>

<?php
if (!$is_ajax) {
    $content = ob_get_clean();
    require_once __DIR__ . '/../../../layout/main.php';
}
?>