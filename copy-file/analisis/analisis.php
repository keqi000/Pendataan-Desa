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
$desa_info = $queries->getDesaById($id_desa);

// Check if this is AJAX request
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    $page_title = 'Analisis Data';
    $current_page = 'analisis';
    $page_css = ['analisis.css'];
    $page_js = ['analisis.js'];
    
    ob_start();
}
?>

<div class="analisis-user-container">
    <div class="analisis-user-header">
        <div class="analisis-user-title">
            <h2>Analisis Data Desa <?php echo $desa_info['nama_desa']; ?></h2>
            <p>Analisis tingkat 1 dan 2 untuk data desa</p>
        </div>
    </div>

    <div class="analisis-user-tabs">
        <div class="analisis-user-tab-nav">
            <button class="analisis-user-tab-btn active" onclick="switchTab('tingkat1')">
                Analisis Tingkat 1
            </button>
            <button class="analisis-user-tab-btn" onclick="switchTab('tingkat2')">
                Analisis Tingkat 2
            </button>
        </div>
    </div>

    <div class="analisis-user-content">
        <!-- Tingkat 1 Tab -->
        <div class="analisis-user-tab-content active" id="tingkat1">
            <div class="analisis-user-grid">
                <div class="card">
                    <div class="card-header">
                        <h3>Statistik Kependudukan</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $penduduk = $queries->getPendudukByDesa($id_desa);
                        $keluarga = $queries->getKeluargaByDesa($id_desa);
                        $total_penduduk = count($penduduk);
                        $total_kk = count($keluarga);
                        $total_laki = 0;
                        $total_perempuan = 0;
                        $total_balita = 0;
                        $total_anak = 0;
                        $total_remaja = 0;
                        $total_dewasa = 0;
                        $total_lansia = 0;
                        $agama_stats = [];
                        $pendidikan_stats = [];
                        $pernikahan_stats = [];
                        
                        foreach ($penduduk as $p) {
                            // Jenis kelamin
                            if ($p['jenis_kelamin'] === 'L') $total_laki++;
                            else $total_perempuan++;
                            
                            // Kelompok usia
                            if ($p['usia'] >= 0 && $p['usia'] <= 5) $total_balita++;
                            elseif ($p['usia'] >= 6 && $p['usia'] <= 12) $total_anak++;
                            elseif ($p['usia'] >= 13 && $p['usia'] <= 17) $total_remaja++;
                            elseif ($p['usia'] >= 18 && $p['usia'] <= 64) $total_dewasa++;
                            else $total_lansia++;
                            
                            // Agama
                            $agama_stats[$p['agama']] = ($agama_stats[$p['agama']] ?? 0) + 1;
                            
                            // Pendidikan
                            $pendidikan_stats[$p['pendidikan_terakhir']] = ($pendidikan_stats[$p['pendidikan_terakhir']] ?? 0) + 1;
                            
                            // Status pernikahan
                            $pernikahan_stats[$p['status_pernikahan']] = ($pernikahan_stats[$p['status_pernikahan']] ?? 0) + 1;
                        }
                        ?>
                        
                        <div class="analisis-stats-grid">
                            <div class="stat-item">
                                <h4>Total Penduduk</h4>
                                <div class="stat-value"><?php echo number_format($total_penduduk); ?> jiwa</div>
                            </div>
                            <div class="stat-item">
                                <h4>Laki-laki</h4>
                                <div class="stat-value"><?php echo number_format($total_laki); ?> (<?php echo $total_penduduk > 0 ? round(($total_laki/$total_penduduk)*100, 1) : 0; ?>%)</div>
                            </div>
                            <div class="stat-item">
                                <h4>Perempuan</h4>
                                <div class="stat-value"><?php echo number_format($total_perempuan); ?> (<?php echo $total_penduduk > 0 ? round(($total_perempuan/$total_penduduk)*100, 1) : 0; ?>%)</div>
                            </div>
                            <div class="stat-item">
                                <h4>Total KK</h4>
                                <div class="stat-value"><?php echo number_format($total_kk); ?> KK</div>
                            </div>
                        </div>
                        
                        <div class="analisis-breakdown">
                            <h5>Kelompok Usia</h5>
                            <div class="breakdown-item">
                                <span>Balita (0-5 tahun)</span>
                                <span><?php echo number_format($total_balita); ?> (<?php echo $total_penduduk > 0 ? round(($total_balita/$total_penduduk)*100, 1) : 0; ?>%)</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Anak (6-12 tahun)</span>
                                <span><?php echo number_format($total_anak); ?> (<?php echo $total_penduduk > 0 ? round(($total_anak/$total_penduduk)*100, 1) : 0; ?>%)</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Remaja (13-17 tahun)</span>
                                <span><?php echo number_format($total_remaja); ?> (<?php echo $total_penduduk > 0 ? round(($total_remaja/$total_penduduk)*100, 1) : 0; ?>%)</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Dewasa (18-64 tahun)</span>
                                <span><?php echo number_format($total_dewasa); ?> (<?php echo $total_penduduk > 0 ? round(($total_dewasa/$total_penduduk)*100, 1) : 0; ?>%)</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Lansia (65+ tahun)</span>
                                <span><?php echo number_format($total_lansia); ?> (<?php echo $total_penduduk > 0 ? round(($total_lansia/$total_penduduk)*100, 1) : 0; ?>%)</span>
                            </div>
                        </div>
                        
                        <div class="analisis-breakdown">
                            <h5>Agama</h5>
                            <?php foreach ($agama_stats as $agama => $jumlah): ?>
                            <div class="breakdown-item">
                                <span><?php echo $agama; ?></span>
                                <span><?php echo number_format($jumlah); ?> (<?php echo $total_penduduk > 0 ? round(($jumlah/$total_penduduk)*100, 1) : 0; ?>%)</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="analisis-breakdown">
                            <h5>Tingkat Pendidikan</h5>
                            <?php 
                            arsort($pendidikan_stats);
                            foreach ($pendidikan_stats as $pendidikan => $jumlah): 
                            ?>
                            <div class="breakdown-item">
                                <span><?php echo $pendidikan; ?></span>
                                <span><?php echo number_format($jumlah); ?> (<?php echo $total_penduduk > 0 ? round(($jumlah/$total_penduduk)*100, 1) : 0; ?>%)</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="analisis-breakdown">
                            <h5>Status Pernikahan</h5>
                            <?php foreach ($pernikahan_stats as $status => $jumlah): ?>
                            <div class="breakdown-item">
                                <span><?php echo $status; ?></span>
                                <span><?php echo number_format($jumlah); ?> (<?php echo $total_penduduk > 0 ? round(($jumlah/$total_penduduk)*100, 1) : 0; ?>%)</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Distribusi Ekonomi</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $mata_pencaharian = $queries->getMataPencaharianByDesa($id_desa);
                        $mata_pencaharian_stats = [];
                        $ekonomi = $queries->getDataEkonomi($id_desa);
                        $total_pertanian = 0;
                        $total_umkm = 0;
                        $total_pasar = 0;
                        
                        foreach ($mata_pencaharian as $mp) {
                            $mata_pencaharian_stats[$mp['jenis_pekerjaan']] = ($mata_pencaharian_stats[$mp['jenis_pekerjaan']] ?? 0) + 1;
                        }
                        
                        foreach ($ekonomi as $e) {
                            if ($e['jenis_data'] === 'pertanian') $total_pertanian++;
                            elseif ($e['jenis_data'] === 'umkm') $total_umkm++;
                            elseif ($e['jenis_data'] === 'pasar') $total_pasar++;
                        }
                        
                        // Count total unique jenis_pekerjaan
                        $total_jenis_mata_pencaharian = count($mata_pencaharian_stats);
                        ?>
                        
                        <div class="analisis-stats-grid">
                            <div class="stat-item">
                                <h4>Total Mata Pencaharian</h4>
                                <div class="stat-value"><?php echo number_format($total_jenis_mata_pencaharian); ?> jenis</div>
                            </div>
                            <div class="stat-item">
                                <h4>UMKM</h4>
                                <div class="stat-value"><?php echo number_format($total_umkm); ?> unit</div>
                            </div>
                            <div class="stat-item">
                                <h4>Pertanian</h4>
                                <div class="stat-value"><?php echo number_format($total_pertanian); ?> unit</div>
                            </div>
                            <div class="stat-item">
                                <h4>Pasar</h4>
                                <div class="stat-value"><?php echo number_format($total_pasar); ?> unit</div>
                            </div>
                        </div>
                        
                        <div class="analisis-breakdown">
                            <h5>Mata Pencaharian</h5>
                            <?php 
                            arsort($mata_pencaharian_stats);
                            $total_mp = array_sum($mata_pencaharian_stats);
                            foreach ($mata_pencaharian_stats as $pekerjaan => $jumlah): 
                            ?>
                            <div class="breakdown-item">
                                <span><?php echo $pekerjaan; ?></span>
                                <span><?php echo number_format($jumlah); ?> (<?php echo $total_mp > 0 ? round(($jumlah/$total_mp)*100, 1) : 0; ?>%)</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="analisis-breakdown">
                            <h5>UMKM Berdasarkan Jenis Usaha</h5>
                            <?php
                            $umkm = $queries->getUMKM($id_desa);
                            $umkm_jenis_stats = [];
                            foreach ($umkm as $u) {
                                $umkm_jenis_stats[$u['jenis_usaha']] = ($umkm_jenis_stats[$u['jenis_usaha']] ?? 0) + 1;
                            }
                            arsort($umkm_jenis_stats);
                            foreach ($umkm_jenis_stats as $jenis => $jumlah): 
                            ?>
                            <div class="breakdown-item">
                                <span><?php echo $jenis; ?></span>
                                <span><?php echo number_format($jumlah); ?> unit</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="analisis-breakdown">
                            <h5>Warga Miskin & Bantuan Sosial</h5>
                            <?php
                            $query = "SELECT COUNT(*) as total FROM warga_miskin WHERE id_desa = :id_desa";
                            $stmt = $queries->db->prepare($query);
                            $stmt->bindParam(':id_desa', $id_desa);
                            $stmt->execute();
                            $total_warga_miskin = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                            
                            $query = "SELECT jenis_bantuan, COUNT(*) as jumlah FROM warga_miskin WHERE id_desa = :id_desa GROUP BY jenis_bantuan";
                            $stmt = $queries->db->prepare($query);
                            $stmt->bindParam(':id_desa', $id_desa);
                            $stmt->execute();
                            $bantuan_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <div class="breakdown-item">
                                <span>Total Warga Miskin</span>
                                <span><?php echo number_format($total_warga_miskin); ?> orang</span>
                            </div>
                            <?php foreach ($bantuan_stats as $bantuan): ?>
                            <div class="breakdown-item">
                                <span><?php echo $bantuan['jenis_bantuan']; ?></span>
                                <span><?php echo number_format($bantuan['jumlah']); ?> penerima</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Fasilitas Pendidikan</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $fasilitas = $queries->getFasilitasPendidikan($id_desa);
                        $fasilitas_stats = [];
                        $total_kapasitas = 0;
                        $total_guru = 0;
                        
                        foreach ($fasilitas as $f) {
                            $fasilitas_stats[$f['jenis_pendidikan']] = ($fasilitas_stats[$f['jenis_pendidikan']] ?? 0) + 1;
                            $total_kapasitas += $f['kapasitas_siswa'] ?? 0;
                            $total_guru += $f['jumlah_guru'] ?? 0;
                        }
                        ?>
                        
                        <div class="analisis-stats-grid">
                            <div class="stat-item">
                                <h4>Total Fasilitas</h4>
                                <div class="stat-value"><?php echo number_format(array_sum($fasilitas_stats)); ?> unit</div>
                            </div>
                            <div class="stat-item">
                                <h4>Total Kapasitas</h4>
                                <div class="stat-value"><?php echo number_format($total_kapasitas); ?> siswa</div>
                            </div>
                            <div class="stat-item">
                                <h4>Total Guru</h4>
                                <div class="stat-value"><?php echo number_format($total_guru); ?> orang</div>
                            </div>
                            <div class="stat-item">
                                <h4>Rasio Guru:Siswa</h4>
                                <div class="stat-value">1:<?php echo $total_guru > 0 ? round($total_kapasitas/$total_guru) : 0; ?></div>
                            </div>
                        </div>
                        
                        <div class="analisis-breakdown">
                            <h5>Jenis Fasilitas</h5>
                            <?php foreach ($fasilitas_stats as $jenis => $jumlah): ?>
                            <div class="breakdown-item">
                                <span><?php echo $jenis; ?></span>
                                <span><?php echo number_format($jumlah); ?> unit</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Status Infrastruktur</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $jalan = $queries->getInfrastrukturJalan($id_desa);
                        $jembatan = $queries->getInfrastrukturJembatan($id_desa);
                        $jalan_baik = 0;
                        $jalan_sedang = 0;
                        $jalan_rusak = 0;
                        $total_panjang_jalan = 0;
                        $jembatan_baik = 0;
                        $jembatan_sedang = 0;
                        $jembatan_rusak = 0;
                        $total_jembatan = count($jembatan);
                        
                        foreach ($jalan as $j) {
                            $total_panjang_jalan += $j['panjang_jalan'];
                            if ($j['kondisi_jalan'] === 'baik') $jalan_baik++;
                            elseif ($j['kondisi_jalan'] === 'sedang') $jalan_sedang++;
                            else $jalan_rusak++;
                        }
                        
                        foreach ($jembatan as $jmb) {
                            if ($jmb['kondisi_jembatan'] === 'baik') $jembatan_baik++;
                            elseif ($jmb['kondisi_jembatan'] === 'sedang') $jembatan_sedang++;
                            else $jembatan_rusak++;
                        }
                        
                        $total_jalan_unit = $jalan_baik + $jalan_sedang + $jalan_rusak;
                        ?>
                        
                        <div class="analisis-stats-grid">
                            <div class="stat-item">
                                <h4>Total Jalan</h4>
                                <div class="stat-value"><?php echo number_format($total_jalan_unit); ?> unit</div>
                            </div>
                            <div class="stat-item">
                                <h4>Panjang Jalan</h4>
                                <div class="stat-value"><?php echo number_format($total_panjang_jalan, 1); ?> km</div>
                            </div>
                            <div class="stat-item">
                                <h4>Total Jembatan</h4>
                                <div class="stat-value"><?php echo number_format($total_jembatan); ?> unit</div>
                            </div>
                            <div class="stat-item">
                                <h4>Kondisi Baik</h4>
                                <div class="stat-value"><?php echo ($total_jalan_unit + $total_jembatan) > 0 ? round((($jalan_baik + $jembatan_baik) / ($total_jalan_unit + $total_jembatan)) * 100, 1) : 0; ?>%</div>
                            </div>
                        </div>
                        
                        <div class="analisis-breakdown">
                            <h5>Kondisi Jalan</h5>
                            <div class="breakdown-item">
                                <span>Baik</span>
                                <span><?php echo number_format($jalan_baik); ?> (<?php echo $total_jalan_unit > 0 ? round(($jalan_baik/$total_jalan_unit)*100, 1) : 0; ?>%)</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Sedang</span>
                                <span><?php echo number_format($jalan_sedang); ?> (<?php echo $total_jalan_unit > 0 ? round(($jalan_sedang/$total_jalan_unit)*100, 1) : 0; ?>%)</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Rusak</span>
                                <span><?php echo number_format($jalan_rusak); ?> (<?php echo $total_jalan_unit > 0 ? round(($jalan_rusak/$total_jalan_unit)*100, 1) : 0; ?>%)</span>
                            </div>
                        </div>
                        
                        <div class="analisis-breakdown">
                            <h5>Kondisi Jembatan</h5>
                            <div class="breakdown-item">
                                <span>Baik</span>
                                <span><?php echo number_format($jembatan_baik); ?> (<?php echo $total_jembatan > 0 ? round(($jembatan_baik/$total_jembatan)*100, 1) : 0; ?>%)</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Sedang</span>
                                <span><?php echo number_format($jembatan_sedang); ?> (<?php echo $total_jembatan > 0 ? round(($jembatan_sedang/$total_jembatan)*100, 1) : 0; ?>%)</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Rusak</span>
                                <span><?php echo number_format($jembatan_rusak); ?> (<?php echo $total_jembatan > 0 ? round(($jembatan_rusak/$total_jembatan)*100, 1) : 0; ?>%)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tingkat 2 Tab -->
        <div class="analisis-user-tab-content" id="tingkat2">
            <div class="analisis-user-advanced">
                <div class="card">
                    <div class="card-header">
                        <h3>Analisis Korelasi</h3>
                    </div>
                    <div class="card-body">
                        <div class="no-data">
                            <i class="fas fa-project-diagram fa-2x mb-3 text-muted"></i>
                            <h6 class="text-muted">Tidak Ada Data Korelasi</h6>
                            <p class="text-muted">Data belum cukup untuk analisis korelasi</p>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Prediksi & Proyeksi</h3>
                    </div>
                    <div class="card-body">
                        <div class="no-data">
                            <i class="fas fa-chart-area fa-2x mb-3 text-muted"></i>
                            <h6 class="text-muted">Tidak Ada Data untuk Prediksi</h6>
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