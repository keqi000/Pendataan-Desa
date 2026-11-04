<?php
require_once __DIR__ . '/detail-analisis-functions.php';

$detailAnalisisFunctions = new DetailAnalisisFunctions();
$detailAnalisisFunctions->checkAuthentication();

// Check if this is a direct AJAX call or included from view
$is_direct_ajax = $detailAnalisisFunctions->isAjaxRequest() && !isset($included_from_view);

if ($is_direct_ajax) {
    // Handle GET requests for pagination
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
        $detailAnalisisFunctions->handleAjaxRequest();
        exit();
    }
    
    // Handle POST requests for tab switching
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $analysis_type = $_POST['type'] ?? '';
        $selected_desa = $_POST['desa'] ?? 'all';
        $page = max(1, (int)($_POST['page'] ?? 1));
        $limit = max(5, min(50, (int)($_POST['limit'] ?? 10)));
        $search = trim($_POST['search'] ?? '');
        
        if (empty($analysis_type)) {
            http_response_code(400);
            echo json_encode(['error' => 'Analysis type required']);
            exit();
        }
        
        $all_desa = $detailAnalisisFunctions->getAllDesa();
        $filtered_desa = $detailAnalisisFunctions->getFilteredDesa($selected_desa, $all_desa);
        
        // Continue with tab content generation below
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request method']);
        exit();
    }
}

// When included from view, these variables should already be set
if (!$is_direct_ajax && (!isset($analysis_type) || !isset($filtered_desa))) {
    echo '<div class="error-message">Configuration error: Required variables not set</div>';
    return;
}

// Set default pagination for initial load when included from view
if (!$is_direct_ajax) {
    $page = 1;
    $limit = 10;
    $search = '';
}

if ($analysis_type === 'kependudukan') {
    ?>
    <!-- KK Perempuan + Anak Sekolah -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-users"></i> Detail KK Perempuan + Anak Sekolah</h3>
        </div>
        <div class="section-content">
            <?php $kk_perempuan_result = $detailAnalisisFunctions->getKKPerempuanDataPaginated($filtered_desa, $page, $limit, $search); ?>
            
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>KK Perempuan + Anak Sekolah</h4>
                    <div class="stat-value"><?php echo $kk_perempuan_result['total']; ?> KK</div>
                </div>
            </div>
            
            <div class="data-table-section">
                <div class="table-header">
                    <h4>Daftar KK Perempuan + Anak Sekolah</h4>
                    <input type="text" id="searchKKPerempuan" class="search-input" placeholder="Cari nama kepala keluarga..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="kkPerempuanTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kepala Keluarga</th>
                            <th>Desa</th>
                            <th>Jumlah Anak Sekolah</th>
                            <th>Usia Anak</th>
                            <th>Status Ekonomi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $start_number = ($page - 1) * $limit + 1;
                        foreach ($kk_perempuan_result['data'] as $index => $data): 
                        ?>
                        <tr>
                            <td><?php echo $start_number + $index; ?></td>
                            <td><?php echo htmlspecialchars($data['kepala_keluarga']['nama_lengkap'] ?? 'Data Tidak Tersedia'); ?></td>
                            <td><?php echo htmlspecialchars($data['desa']); ?></td>
                            <td><?php echo count($data['anak_sekolah'] ?? []); ?> anak</td>
                            <td>
                                <?php 
                                if (!empty($data['anak_sekolah']) && is_array($data['anak_sekolah'])) {
                                    $usia_list = array_map(function($anak) { 
                                        return $anak['usia'] ?? 'N/A';
                                    }, $data['anak_sekolah']);
                                    echo implode(', ', $usia_list) . ' tahun';
                                } else {
                                    echo 'Data Tidak Tersedia';
                                }
                                ?>
                            </td>
                            <td>
                                <span class="status-badge status-miskin">Miskin</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($kk_perempuan_result['data'])): ?>
                        <tr class="no-results-row">
                            <td colspan="6" style="text-align: center; padding: 32px; color: #666;">
                                <i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                                <?php echo $search ? 'Tidak ada data yang sesuai dengan pencarian "' . htmlspecialchars($search) . '"' : 'Tidak ada data tersedia'; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
                <!-- Pagination will be added by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Produktif Tanpa Kerja Tetap -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-briefcase"></i> Detail Produktif Tanpa Kerja Tetap</h3>
        </div>
        <div class="section-content">
            <?php $produktif_result = $detailAnalisisFunctions->getProduktifDataPaginated($filtered_desa, $page, $limit, $search); ?>
            
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>Produktif Tanpa Kerja Tetap</h4>
                    <div class="stat-value"><?php echo $produktif_result['total']; ?> orang</div>
                </div>
            </div>
            
            <div class="data-table-section">
                <div class="table-header">
                    <h4>Daftar Produktif Tanpa Kerja Tetap</h4>
                    <input type="text" id="searchProduktif" class="search-input" placeholder="Cari nama penduduk..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="produktifTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Desa</th>
                            <th>Pendidikan</th>
                            <th>Jenis Kelamin</th>
                            <th>Usia</th>
                            <th>Status Pekerjaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produktif_result['data'] as $index => $data): ?>
                        <tr>
                            <td><?php echo $start_number + $index; ?></td>
                            <td><?php echo htmlspecialchars($data['penduduk']['nama_lengkap']); ?></td>
                            <td><?php echo htmlspecialchars($data['desa']); ?></td>
                            <td><?php echo htmlspecialchars($data['penduduk']['pendidikan_terakhir']); ?></td>
                            <td><?php echo $data['penduduk']['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></td>
                            <td><?php echo $data['penduduk']['usia']; ?> tahun</td>
                            <td>
                                <span class="status-badge status-tidak-tetap">Tidak Tetap</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($produktif_result['data'])): ?>
                        <tr class="no-results-row">
                            <td colspan="7" style="text-align: center; padding: 32px; color: #666;">
                                <i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                                <?php echo $search ? 'Tidak ada data yang sesuai dengan pencarian "' . htmlspecialchars($search) . '"' : 'Tidak ada data tersedia'; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
                <!-- Pagination will be added by JavaScript -->
            </div>
            
            <div class="analisis-breakdown">
                <h5>Berdasarkan Pendidikan:</h5>
                <div class="breakdown-item"><span>SD</span><span><?php echo $produktif_result['detail']['sd']; ?> orang</span></div>
                <div class="breakdown-item"><span>SMP</span><span><?php echo $produktif_result['detail']['smp']; ?> orang</span></div>
                <div class="breakdown-item"><span>SMA</span><span><?php echo $produktif_result['detail']['sma']; ?> orang</span></div>
                <div class="breakdown-item"><span>Diploma</span><span><?php echo $produktif_result['detail']['diploma']; ?> orang</span></div>
                <div class="breakdown-item"><span>Sarjana</span><span><?php echo $produktif_result['detail']['sarjana']; ?> orang</span></div>
                
                <h5>Berdasarkan Jenis Kelamin:</h5>
                <div class="breakdown-item"><span>Laki-laki</span><span><?php echo $produktif_result['detail']['laki']; ?> orang</span></div>
                <div class="breakdown-item"><span>Perempuan</span><span><?php echo $produktif_result['detail']['perempuan']; ?> orang</span></div>
            </div>
        </div>
    </div>

    <!-- KK Lansia + Tanggungan -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-user-clock"></i> Detail KK Lansia + Tanggungan</h3>
        </div>
        <div class="section-content">
            <?php $lansia_result = $detailAnalisisFunctions->getLansiaDataPaginated($filtered_desa, $page, $limit, $search); ?>
            
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>KK Lansia + Tanggungan</h4>
                    <div class="stat-value"><?php echo $lansia_result['total']; ?> KK</div>
                </div>
            </div>
            
            <div class="data-table-section">
                <div class="table-header">
                    <h4>Daftar KK Lansia + Tanggungan</h4>
                    <input type="text" id="searchLansia" class="search-input" placeholder="Cari nama kepala keluarga..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="lansiaTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kepala Keluarga</th>
                            <th>Desa</th>
                            <th>Usia</th>
                            <th>Jumlah Tanggungan</th>
                            <th>Usia Tanggungan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lansia_result['data'] as $index => $data): ?>
                        <tr>
                            <td><?php echo $start_number + $index; ?></td>
                            <td><?php echo htmlspecialchars($data['kepala_keluarga']['nama_lengkap'] ?? 'Data Tidak Tersedia'); ?></td>
                            <td><?php echo htmlspecialchars($data['desa']); ?></td>
                            <td><?php echo $data['kepala_keluarga']['usia'] ?? 'N/A'; ?> tahun</td>
                            <td><?php echo count($data['tanggungan'] ?? []); ?> orang</td>
                            <td>
                                <?php 
                                if (!empty($data['tanggungan']) && is_array($data['tanggungan'])) {
                                    $usia_tanggungan = array_map(function($tanggungan) { 
                                        return $tanggungan['usia'] ?? 'N/A';
                                    }, $data['tanggungan']);
                                    echo implode(', ', $usia_tanggungan) . ' tahun';
                                } else {
                                    echo 'Tidak ada tanggungan';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($lansia_result['data'])): ?>
                        <tr class="no-results-row">
                            <td colspan="6" style="text-align: center; padding: 32px; color: #666;">
                                <i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                                <?php echo $search ? 'Tidak ada data yang sesuai dengan pencarian "' . htmlspecialchars($search) . '"' : 'Tidak ada data tersedia'; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
                <!-- Pagination will be added by JavaScript -->
            </div>
        </div>
    </div>
    <?php
} elseif ($analysis_type === 'ekonomi') {
    ?>
    <!-- Petani Lahan < 0.5 Ha -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-seedling"></i> Detail Petani Lahan &lt; 1 Ha Tanpa Bantuan</h3>
        </div>
        <div class="section-content">
            <?php $petani_result = $detailAnalisisFunctions->getPetaniDataPaginated($filtered_desa, $page, $limit, $search); ?>
            
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>Petani Lahan &lt; 1 Ha</h4>
                    <div class="stat-value"><?php echo $petani_result['total']; ?> orang</div>
                </div>
            </div>
            
            <div class="data-table-section">
                <div class="table-header">
                    <h4>Daftar Petani Lahan Kecil</h4>
                    <input type="text" id="searchPetani" class="search-input" placeholder="Cari nama petani..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="petaniTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Petani</th>
                            <th>Desa</th>
                            <th>Luas Lahan (Ha)</th>
                            <th>Jenis Komoditas</th>
                            <th>Pendapatan/Musim</th>
                            <th>Kategori Lahan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $start_number = ($page - 1) * $limit + 1;
                        foreach ($petani_result['data'] as $index => $data): 
                        ?>
                        <tr>
                            <td><?php echo $start_number + $index; ?></td>
                            <td><?php echo htmlspecialchars($data['nama_petani']); ?></td>
                            <td><?php echo htmlspecialchars($data['desa']); ?></td>
                            <td><?php echo number_format($data['luas_lahan'], 2); ?> Ha</td>
                            <td><?php echo htmlspecialchars($data['jenis_komoditas']); ?></td>
                            <td>Rp <?php echo number_format($data['pendapatan_per_musim']); ?></td>
                            <td>
                                <?php 
                                $luas = $data['luas_lahan'];
                                if ($luas >= 0.1 && $luas <= 0.5) {
                                    echo '<span class="kategori-badge kategori-kecil">0.1-0.5 Ha</span>';
                                } elseif ($luas > 0.5 && $luas < 1) {
                                    echo '<span class="kategori-badge kategori-sedang">0.5-1 Ha</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($petani_result['data'])): ?>
                        <tr class="no-results-row">
                            <td colspan="7" style="text-align: center; padding: 32px; color: #666;">
                                <i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                                <?php echo $search ? 'Tidak ada data yang sesuai dengan pencarian "' . htmlspecialchars($search) . '"' : 'Tidak ada data tersedia'; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
                <!-- Pagination will be added by JavaScript -->
            </div>
            
            <div class="analisis-breakdown">
                <h5>Berdasarkan Luas Lahan:</h5>
                <div class="breakdown-item"><span>0.1-0.5 Ha</span><span><?php echo $petani_result['breakdown']['0.1-0.5']; ?> petani</span></div>
                <div class="breakdown-item"><span>0.5-1 Ha</span><span><?php echo $petani_result['breakdown']['0.5-1']; ?> petani</span></div>
                
                <h5>Berdasarkan Jenis Komoditas:</h5>
                <?php if (!empty($petani_result['komoditas'])): ?>
                    <?php foreach ($petani_result['komoditas'] as $komoditas => $jumlah): ?>
                        <div class="breakdown-item"><span><?php echo $komoditas; ?></span><span><?php echo $jumlah; ?> petani</span></div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="breakdown-item"><span>Tidak ada data</span><span>-</span></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Detail Korelasi Pendidikan-Pekerjaan -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-graduation-cap"></i> Detail Korelasi Pendidikan-Pekerjaan</h3>
        </div>
        <div class="section-content">
            <?php $korelasi_result = $detailAnalisisFunctions->getKorelasiPendidikanDataPaginated($filtered_desa, $page, $limit, $search); ?>
            
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>Korelasi Pendidikan-Pekerjaan</h4>
                    <div class="stat-value"><?php echo $korelasi_result['total']; ?> orang</div>
                </div>
            </div>
            
            <div class="data-table-section">
                <div class="table-header">
                    <h4>Daftar Korelasi Pendidikan-Pekerjaan</h4>
                    <input type="text" id="searchKorelasi" class="search-input" placeholder="Cari nama penduduk..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="korelasiTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Desa</th>
                            <th>Pendidikan</th>
                            <th>Sektor Pekerjaan</th>
                            <th>Penghasilan/Bulan</th>
                            <th>Kategori</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $start_number = ($page - 1) * $limit + 1;
                        foreach ($korelasi_result['data'] as $index => $data): 
                        ?>
                        <tr>
                            <td><?php echo $start_number + $index; ?></td>
                            <td><?php echo htmlspecialchars($data['penduduk']['nama_lengkap']); ?></td>
                            <td><?php echo htmlspecialchars($data['desa']); ?></td>
                            <td><?php echo htmlspecialchars($data['penduduk']['pendidikan_terakhir']); ?></td>
                            <td><?php echo htmlspecialchars($data['pekerjaan']['sektor_pekerjaan']); ?></td>
                            <td>Rp <?php echo number_format($data['pekerjaan']['penghasilan_perbulan'] ?? 0); ?></td>
                            <td>
                                <?php 
                                $sektor = $data['pekerjaan']['sektor_pekerjaan'];
                                if ($sektor === 'pertanian') {
                                    echo '<span class="sektor-badge sektor-pertanian">Pertanian</span>';
                                } else {
                                    echo '<span class="sektor-badge sektor-non-pertanian">Non-Pertanian</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($korelasi_result['data'])): ?>
                        <tr class="no-results-row">
                            <td colspan="7" style="text-align: center; padding: 32px; color: #666;">
                                <i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                                <?php echo $search ? 'Tidak ada data yang sesuai dengan pencarian "' . htmlspecialchars($search) . '"' : 'Tidak ada data tersedia'; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
                <!-- Pagination will be added by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Detail Kesenjangan Ekonomi Antar Desa -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-chart-bar"></i> Detail Kesenjangan Ekonomi Antar Desa</h3>
        </div>
        <div class="section-content">
            <?php $kesenjangan_result = $detailAnalisisFunctions->getKesenjanganEkonomiDataPaginated($detailAnalisisFunctions->getAllDesa(), $page, $limit, $search); ?>
            
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>Kesenjangan Ekonomi Antar Desa</h4>
                    <div class="stat-value"><?php echo $kesenjangan_result['total']; ?> desa</div>
                </div>
            </div>
            
            <div class="data-table-section">
                <div class="table-header">
                    <h4>Daftar Pendapatan Per Desa</h4>
                    <input type="text" id="searchKesenjangan" placeholder="Cari nama desa..." class="search-input" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="kesenjanganTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Desa</th>
                                <th>Total Pendapatan</th>
                                <th>Jumlah Pekerja</th>
                                <th>Rata-rata Pendapatan</th>
                                <th>Kategori</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $start_number = ($page - 1) * $limit + 1;
                            foreach ($kesenjangan_result['data'] as $index => $data): 
                            ?>
                            <tr>
                                <td><?php echo $start_number + $index; ?></td>
                                <td><?php echo htmlspecialchars($data['nama_desa']); ?></td>
                                <td>Rp <?php echo number_format($data['total_pendapatan']); ?></td>
                                <td><?php echo $data['jumlah_pekerja']; ?> orang</td>
                                <td>Rp <?php echo number_format($data['rata_pendapatan']); ?></td>
                                <td>
                                    <?php 
                                    $total_data = count($kesenjangan_result['data']);
                                    if ($index < $total_data / 3) {
                                        echo '<span class="kategori-badge kategori-tinggi">Tinggi</span>';
                                    } elseif ($index < ($total_data * 2) / 3) {
                                        echo '<span class="kategori-badge kategori-sedang">Sedang</span>';
                                    } else {
                                        echo '<span class="kategori-badge kategori-rendah">Rendah</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($kesenjangan_result['data'])): ?>
                            <tr class="no-results-row">
                                <td colspan="6" style="text-align: center; padding: 32px; color: #666;">
                                    <i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                                    <?php echo $search ? 'Tidak ada data yang sesuai dengan pencarian "' . htmlspecialchars($search) . '"' : 'Tidak ada data tersedia'; ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination will be added by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Detail Distribusi Pendapatan Desa -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-pie-chart"></i> Detail Distribusi Pendapatan Desa</h3>
        </div>
        <div class="section-content">
            <?php $distribusi_result = $detailAnalisisFunctions->getDistribusiPendapatanDataPaginated($filtered_desa, $page, $limit, $search); ?>
            
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>Distribusi Pendapatan Desa</h4>
                    <div class="stat-value"><?php echo $distribusi_result['total']; ?> desa</div>
                </div>
            </div>
            
            <div class="data-table-section">
                <div class="table-header">
                    <h4>Distribusi Pendapatan Per Desa</h4>
                    <input type="text" id="searchDistribusi" placeholder="Cari nama desa..." class="search-input" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="distribusiTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Desa</th>
                                <th>Pendapatan Pertanian</th>
                                <th>Pendapatan Non-Pertanian</th>
                                <th>Total Pendapatan</th>
                                <th>Dominasi Sektor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $start_number = ($page - 1) * $limit + 1;
                            foreach ($distribusi_result['data'] as $index => $data): 
                            ?>
                            <tr>
                                <td><?php echo $start_number + $index; ?></td>
                                <td><?php echo htmlspecialchars($data['nama_desa']); ?></td>
                                <td>Rp <?php echo number_format($data['pendapatan_pertanian']); ?></td>
                                <td>Rp <?php echo number_format($data['pendapatan_non_pertanian']); ?></td>
                                <td>Rp <?php echo number_format($data['total_pendapatan']); ?></td>
                                <td>
                                    <?php 
                                    if ($data['pendapatan_pertanian'] > $data['pendapatan_non_pertanian']) {
                                        echo '<span class="sektor-badge sektor-pertanian">Pertanian</span>';
                                    } else {
                                        echo '<span class="sektor-badge sektor-non-pertanian">Non-Pertanian</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($distribusi_result['data'])): ?>
                            <tr class="no-results-row">
                                <td colspan="6" style="text-align: center; padding: 32px; color: #666;">
                                    <i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                                    <?php echo $search ? 'Tidak ada data yang sesuai dengan pencarian "' . htmlspecialchars($search) . '"' : 'Tidak ada data tersedia'; ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination will be added by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Detail Rincian Non-Pertanian -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-industry"></i> Detail Rincian Non-Pertanian</h3>
        </div>
        <div class="section-content">
            <?php $non_pertanian_result = $detailAnalisisFunctions->getNonPertanianDataPaginated($filtered_desa, $page, $limit, $search); ?>
            
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>Rincian Non-Pertanian</h4>
                    <div class="stat-value"><?php echo $non_pertanian_result['total']; ?> orang</div>
                </div>
            </div>
            
            <div class="data-table-section">
                <div class="table-header">
                    <h4>Daftar Pekerja Non-Pertanian</h4>
                    <input type="text" id="searchNonPertanian" placeholder="Cari nama pekerja..." class="search-input" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="nonPertanianTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Desa</th>
                                <th>Sektor Pekerjaan</th>
                                <th>Jenis Pekerjaan</th>
                                <th>Penghasilan/Bulan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $start_number = ($page - 1) * $limit + 1;
                            foreach ($non_pertanian_result['data'] as $index => $data): 
                            ?>
                            <tr>
                                <td><?php echo $start_number + $index; ?></td>
                                <td><?php echo htmlspecialchars($data['penduduk']['nama_lengkap']); ?></td>
                                <td><?php echo htmlspecialchars($data['desa']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($data['pekerjaan']['sektor_pekerjaan'])); ?></td>
                                <td><?php echo htmlspecialchars($data['pekerjaan']['jenis_pekerjaan']); ?></td>
                                <td>Rp <?php echo number_format($data['pekerjaan']['penghasilan_perbulan'] ?? 0); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $data['pekerjaan']['status_pekerjaan'] == 'tetap' ? 'status-tetap' : 'status-tidak-tetap'; ?>">
                                        <?php echo ucfirst($data['pekerjaan']['status_pekerjaan']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($non_pertanian_result['data'])): ?>
                            <tr class="no-results-row">
                                <td colspan="7" style="text-align: center; padding: 32px; color: #666;">
                                    <i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                                    <?php echo $search ? 'Tidak ada data yang sesuai dengan pencarian "' . htmlspecialchars($search) . '"' : 'Tidak ada data tersedia'; ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination will be added by JavaScript -->
            </div>
        </div>
    </div>
    <?php
} elseif ($analysis_type === 'integrasi') {
    ?>
    <!-- KK Miskin + Anak Sekolah -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-users"></i> Detail KK Miskin + Anak Sekolah</h3>
        </div>
        <div class="section-content">
            <?php $kk_miskin_result = $detailAnalisisFunctions->getKKMiskinDataPaginated($filtered_desa, $page, $limit, $search); ?>
            
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>KK Miskin + Anak Sekolah</h4>
                    <div class="stat-value"><?php echo $kk_miskin_result['total']; ?> KK</div>
                </div>
            </div>
            
            <div class="data-table-section">
                <div class="table-header">
                    <h4>Daftar KK Miskin + Anak Sekolah</h4>
                    <input type="text" id="searchKKMiskin" class="search-input" placeholder="Cari nama kepala keluarga..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="kkMiskinTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kepala Keluarga</th>
                            <th>Desa</th>
                            <th>Jumlah Anak Sekolah</th>
                            <th>Anak Sekolah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $start_number = ($page - 1) * $limit + 1;
                        foreach ($kk_miskin_result['data'] as $index => $data): 
                        ?>
                        <tr>
                            <td><?php echo $start_number + $index; ?></td>
                            <td><?php echo htmlspecialchars($data['kepala_keluarga']['nama_lengkap'] ?? 'Data Tidak Tersedia'); ?></td>
                            <td><?php echo htmlspecialchars($data['desa']); ?></td>
                            <td><?php echo count($data['anak_sekolah'] ?? []); ?> anak</td>
                            <td>
                                <?php 
                                if (!empty($data['anak_sekolah']) && is_array($data['anak_sekolah'])) {
                                    $anak_list = array_map(function($anak) { 
                                        return ($anak['nama_lengkap'] ?? 'Nama Tidak Tersedia') . ' (' . ($anak['usia'] ?? 'Usia Tidak Diketahui') . ' tahun)';
                                    }, $data['anak_sekolah']);
                                    echo implode(', ', $anak_list);
                                } else {
                                    echo 'Data Anak Sekolah Tidak Tersedia';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($kk_miskin_result['data'])): ?>
                        <tr class="no-results-row">
                            <td colspan="5" style="text-align: center; padding: 32px; color: #666;">
                                <i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                                <?php echo $search ? 'Tidak ada data yang sesuai dengan pencarian "' . htmlspecialchars($search) . '"' : 'Tidak ada data tersedia'; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
                <!-- Pagination will be added by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Detail Rasio Ketergantungan Ekonomi -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-balance-scale"></i> Detail Rasio Ketergantungan Ekonomi</h3>
        </div>
        <div class="section-content">
            <?php $rasio_result = $detailAnalisisFunctions->getRasioKetergantunganDataPaginated($filtered_desa, $page, $limit, $search); ?>
            
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>Rasio Ketergantungan Ekonomi</h4>
                    <div class="stat-value"><?php echo $rasio_result['total']; ?> KK</div>
                </div>
            </div>
            
            <div class="data-table-section">
                <div class="table-header">
                    <h4>Daftar Rasio Ketergantungan per Keluarga</h4>
                    <input type="text" id="searchRasioEkonomi" class="search-input" placeholder="Cari nama kepala keluarga..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="rasioEkonomiTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kepala Keluarga</th>
                            <th>Desa</th>
                            <th>Total Anggota</th>
                            <th>Anggota Bekerja</th>
                            <th>Tanggungan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $start_number = ($page - 1) * $limit + 1;
                        foreach ($rasio_result['data'] as $index => $data): 
                        ?>
                        <tr>
                            <td><?php echo $start_number + $index; ?></td>
                            <td><?php echo htmlspecialchars($data['kepala_keluarga']); ?></td>
                            <td><?php echo htmlspecialchars($data['desa']); ?></td>
                            <td><?php echo $data['total_anggota']; ?> orang</td>
                            <td><?php echo $data['anggota_bekerja']; ?> orang</td>
                            <td><?php echo $data['tanggungan']; ?> orang</td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($rasio_result['data'])): ?>
                        <tr class="no-results-row">
                            <td colspan="6" style="text-align: center; padding: 32px; color: #666;">
                                <i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                                <?php echo $search ? 'Tidak ada data yang sesuai dengan pencarian "' . htmlspecialchars($search) . '"' : 'Tidak ada data tersedia'; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
                <!-- Pagination will be added by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Detail Pendidikan Tinggi Tapi Miskin -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-user-graduate"></i> Detail Pendidikan Tinggi Tapi Miskin</h3>
        </div>
        <div class="section-content">
            <?php $pendidikan_tinggi_result = $detailAnalisisFunctions->getPendidikanTinggiMiskinDataPaginated($filtered_desa, $page, $limit, $search); ?>
            
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>Pendidikan Tinggi Tapi Miskin</h4>
                    <div class="stat-value"><?php echo $pendidikan_tinggi_result['total']; ?> orang</div>
                </div>
            </div>
            
            <div class="data-table-section">
                <div class="table-header">
                    <h4>Daftar Kepala Keluarga Berpendidikan Tinggi Tapi Miskin</h4>
                    <input type="text" id="searchPendidikanTinggiMiskin" class="search-input" placeholder="Cari nama penduduk..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="pendidikanTinggiMiskinTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Desa</th>
                            <th>Pendidikan</th>
                            <th>Pekerjaan</th>
                            <th>Faktor Penyebab</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $start_number = ($page - 1) * $limit + 1;
                        foreach ($pendidikan_tinggi_result['data'] as $index => $data): 
                        ?>
                        <tr>
                            <td><?php echo $start_number + $index; ?></td>
                            <td><?php echo htmlspecialchars($data['penduduk']['nama_lengkap']); ?></td>
                            <td><?php echo htmlspecialchars($data['desa']); ?></td>
                            <td><?php echo htmlspecialchars($data['penduduk']['pendidikan_terakhir']); ?></td>
                            <td><?php echo htmlspecialchars($data['penduduk']['pekerjaan'] ?? 'Belum Bekerja'); ?></td>
                            <td><span class="status-badge status-miskin"><?php echo ucfirst($data['faktor_penyebab']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($pendidikan_tinggi_result['data'])): ?>
                        <tr class="no-results-row">
                            <td colspan="6" style="text-align: center; padding: 32px; color: #666;">
                                <i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                                <?php echo $search ? 'Tidak ada data yang sesuai dengan pencarian "' . htmlspecialchars($search) . '"' : 'Tidak ada data tersedia'; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
                <!-- Pagination will be added by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Detail Anak Tidak Sekolah karena Ekonomi Keluarga -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-child"></i> Detail Anak Tidak Sekolah karena Ekonomi Keluarga</h3>
        </div>
        <div class="section-content">
            <?php $anak_tidak_sekolah_result = $detailAnalisisFunctions->getAnakTidakSekolahDataPaginated($filtered_desa, $page, $limit, $search); ?>
            
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>Anak Tidak Sekolah</h4>
                    <div class="stat-value"><?php echo $anak_tidak_sekolah_result['total']; ?> anak</div>
                </div>
            </div>
            
            <div class="data-table-section">
                <div class="table-header">
                    <h4>Daftar Anak Tidak Sekolah dari Keluarga Miskin</h4>
                    <input type="text" id="searchAnakTidakSekolah" class="search-input" placeholder="Cari nama anak..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="anakTidakSekolahTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Desa</th>
                            <th>Usia</th>
                            <th>Jenis Kelamin</th>
                            <th>Status Keluarga</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $start_number = ($page - 1) * $limit + 1;
                        foreach ($anak_tidak_sekolah_result['data'] as $index => $data): 
                        ?>
                        <tr>
                            <td><?php echo $start_number + $index; ?></td>
                            <td><?php echo htmlspecialchars($data['penduduk']['nama_lengkap']); ?></td>
                            <td><?php echo htmlspecialchars($data['desa']); ?></td>
                            <td><?php echo $data['penduduk']['usia']; ?> tahun</td>
                            <td><?php echo $data['penduduk']['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></td>
                            <td><span class="status-badge status-miskin">Miskin</span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($anak_tidak_sekolah_result['data'])): ?>
                        <tr class="no-results-row">
                            <td colspan="6" style="text-align: center; padding: 32px; color: #666;">
                                <i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                                <?php echo $search ? 'Tidak ada data yang sesuai dengan pencarian "' . htmlspecialchars($search) . '"' : 'Tidak ada data tersedia'; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
                <!-- Pagination will be added by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Detail Anak Petani → Pendidikan Tinggi -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-graduation-cap"></i> Detail Anak Petani → Pendidikan Tinggi</h3>
        </div>
        <div class="section-content">
            <?php $anak_petani_result = $detailAnalisisFunctions->getAnakPetaniDataPaginated($filtered_desa, $page, $limit, $search); ?>
            
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>Anak Petani Berpendidikan Tinggi</h4>
                    <div class="stat-value"><?php echo $anak_petani_result['total']; ?> orang</div>
                </div>
            </div>
            
            <div class="data-table-section">
                <div class="table-header">
                    <h4>Daftar Anak Petani dengan Pendidikan Tinggi</h4>
                    <input type="text" id="searchAnakPetani" class="search-input" placeholder="Cari nama anak..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="anakPetaniTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Anak</th>
                            <th>Desa</th>
                            <th>Pendidikan</th>
                            <th>Nama Orang Tua</th>
                            <th>Pekerjaan Orang Tua</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $start_number = ($page - 1) * $limit + 1;
                        foreach ($anak_petani_result['data'] as $index => $data): 
                        ?>
                        <tr>
                            <td><?php echo $start_number + $index; ?></td>
                            <td><?php echo htmlspecialchars($data['penduduk']['nama_lengkap']); ?></td>
                            <td><?php echo htmlspecialchars($data['desa']); ?></td>
                            <td><?php echo htmlspecialchars($data['penduduk']['pendidikan_terakhir']); ?></td>
                            <td><?php echo htmlspecialchars($data['parent']['nama_lengkap']); ?></td>
                            <td><span class="status-badge status-tetap"><?php echo htmlspecialchars($data['parent']['pekerjaan']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($anak_petani_result['data'])): ?>
                        <tr class="no-results-row">
                            <td colspan="6" style="text-align: center; padding: 32px; color: #666;">
                                <i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                                <?php echo $search ? 'Tidak ada data yang sesuai dengan pencarian "' . htmlspecialchars($search) . '"' : 'Tidak ada data tersedia'; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
                <!-- Pagination will be added by JavaScript -->
            </div>
        </div>
    </div>
    <?php
} elseif ($analysis_type === 'spasial') {
    ?>
    <!-- Detail Desa Tanpa Fasilitas Pendidikan -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-school"></i> Detail Desa Tanpa Fasilitas Pendidikan</h3>
        </div>
        <div class="section-content">
            <?php $spasial_result = $detailAnalisisFunctions->getSpasialDataPaginated($filtered_desa, $page, $limit, $search); ?>
            
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>Desa Tanpa Fasilitas Pendidikan</h4>
                    <div class="stat-value"><?php echo $spasial_result['total']; ?> desa</div>
                </div>
            </div>
            
            <div class="data-table-section">
                <div class="table-header">
                    <h4>Daftar Desa Tanpa Fasilitas Pendidikan</h4>
                    <input type="text" id="searchSpasial" class="search-input" placeholder="Cari nama desa..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="spasialTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Desa</th>
                            <th>Total Penduduk</th>
                            <th>Fasilitas Pendidikan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $start_number = ($page - 1) * $limit + 1;
                        foreach ($spasial_result['data'] as $index => $data): 
                        ?>
                        <tr>
                            <td><?php echo $start_number + $index; ?></td>
                            <td><?php echo htmlspecialchars($data['nama_desa']); ?></td>
                            <td><?php echo number_format($data['total_penduduk']); ?> jiwa</td>
                            <td><span class="status-badge status-miskin">0 unit</span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($spasial_result['data'])): ?>
                        <tr class="no-results-row">
                            <td colspan="4" style="text-align: center; padding: 32px; color: #666;">
                                <i class="fas fa-check-circle" style="font-size: 24px; margin-bottom: 8px; display: block; color: green;"></i>
                                Semua desa sudah memiliki fasilitas pendidikan
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Persentase Jalan Baik -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-road"></i> Detail Persentase Jalan Baik per Desa</h3>
        </div>
        <div class="section-content">
            <?php 
            // Ambil semua data tanpa limit untuk hitung total yang akurat
            $aksesibilitas_all = $detailAnalisisFunctions->getAksesibilitasDataPaginated($filtered_desa, 1, 9999, $search);
            $aksesibilitas_result = $detailAnalisisFunctions->getAksesibilitasDataPaginated($filtered_desa, $page, $limit, $search);
            $total_jalan_all = array_sum(array_column($aksesibilitas_all['data'], 'total_jalan'));
            $total_jalan_baik_all = array_sum(array_column($aksesibilitas_all['data'], 'jalan_baik'));
            $persen_total = $total_jalan_all > 0 ? round(($total_jalan_baik_all / $total_jalan_all) * 100, 1) : 0;
            ?>
            
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>Total Persentase Jalan Baik</h4>
                    <div class="stat-value"><?php echo $persen_total; ?>%</div>
                </div>
                <div class="stat-item">
                    <h4>Total Jalan Baik</h4>
                    <div class="stat-value"><?php echo number_format($total_jalan_baik_all); ?> unit</div>
                </div>
                <div class="stat-item">
                    <h4>Total Seluruh Jalan</h4>
                    <div class="stat-value"><?php echo number_format($total_jalan_all); ?> unit</div>
                </div>
            </div>
            
            <div class="data-table-section">
                <div class="table-header">
                    <h4>Kondisi Jalan per Desa</h4>
                    <input type="text" id="searchAksesibilitas" class="search-input" placeholder="Cari nama desa..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="aksesibilitasTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Desa</th>
                            <th>Jalan Baik</th>
                            <th>Total Jalan</th>
                            <th>Persentase Jalan Baik</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $start_number = ($page - 1) * $limit + 1;
                        foreach ($aksesibilitas_result['data'] as $index => $data): 
                        ?>
                        <tr>
                            <td><?php echo $start_number + $index; ?></td>
                            <td><?php echo htmlspecialchars($data['nama_desa']); ?></td>
                            <td><?php echo $data['jalan_baik']; ?> unit</td>
                            <td><?php echo $data['total_jalan']; ?> unit</td>
                            <td><?php echo $data['aksesibilitas']; ?>%</td>
                            <td>
                                <?php 
                                if ($data['aksesibilitas'] >= 75) {
                                    echo '<span class="status-badge status-mampu">Baik</span>';
                                } elseif ($data['aksesibilitas'] >= 50) {
                                    echo '<span class="kategori-badge kategori-sedang">Sedang</span>';
                                } else {
                                    echo '<span class="status-badge status-miskin">Buruk</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($aksesibilitas_result['data'])): ?>
                        <tr class="no-results-row">
                            <td colspan="6" style="text-align: center; padding: 32px; color: #666;">
                                Tidak ada data tersedia
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail 5 Desa Penduduk Terbanyak -->
    <div class="detail-section">
        <div class="section-header">
            <h3><i class="fas fa-sort-amount-down"></i> Detail 5 Desa dengan Penduduk Terbanyak</h3>
        </div>
        <div class="section-content">
            <?php $potensi_result = $detailAnalisisFunctions->getPotensiPengembanganDataPaginated($filtered_desa, $page, $limit, $search); ?>
            
            <div class="analisis-stats-grid">
                <div class="stat-item">
                    <h4>Total Desa</h4>
                    <div class="stat-value"><?php echo $potensi_result['total']; ?> desa</div>
                </div>
            </div>
            
            <div class="data-table-section">
                <div class="table-header">
                    <h4>Daftar Desa Diurutkan Penduduk Terbanyak</h4>
                    <input type="text" id="searchPotensi" class="search-input" placeholder="Cari nama desa..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="potensiTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Desa</th>
                            <th>Total Penduduk</th>
                            <th>Laki-laki</th>
                            <th>Perempuan</th>
                            <th>Total KK</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $start_number = ($page - 1) * $limit + 1;
                        foreach ($potensi_result['data'] as $index => $data): 
                        ?>
                        <tr>
                            <td><?php echo $start_number + $index; ?></td>
                            <td><?php echo htmlspecialchars($data['nama_desa']); ?></td>
                            <td><?php echo number_format($data['total_penduduk']); ?> jiwa</td>
                            <td><?php echo number_format($data['total_laki']); ?> orang</td>
                            <td><?php echo number_format($data['total_perempuan']); ?> orang</td>
                            <td><?php echo number_format($data['total_kk']); ?> KK</td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($potensi_result['data'])): ?>
                        <tr class="no-results-row">
                            <td colspan="6" style="text-align: center; padding: 32px; color: #666;">
                                Tidak ada data tersedia
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
