<?php if (!isset($is_ajax_tab_load)): ?>
<div class="analisis-admin-container">
    <!-- Loading Indicator -->
    <div id="analisis-loading-indicator">
        <div class="loader-content">
            <div class="spinner"></div>
            <p>Memuat data analisis...</p>
        </div>
    </div>
    
    <div class="analisis-admin-header">
        <div class="analisis-admin-title">
            <h2>Analisis Data Lintas Desa</h2>
            <p>Analisis tingkat 1 dan 2 untuk semua desa di Kecamatan Tibawa</p>
        </div>
        <div class="analisis-admin-actions">
            <div class="desa-search-container">
                <input type="text" class="form-control" id="desaSearch" placeholder="Cari atau ketik nama desa..."
                    autocomplete="off" oninput="searchDesa()" onfocus="showAllDesa()">
                <input type="hidden" id="selectedDesaId" value="<?php echo $selected_desa; ?>">
                <div class="autocomplete-dropdown" id="autocompleteDropdown"></div>
            </div>
            <button class="btn btn-primary" onclick="generateAnalysis()">
                <i class="fas fa-chart-bar"></i>
                Generate Analisis
            </button>
            <div class="filter-status">
                <span class="filter-label">Data Aktif:</span>
                <span class="filter-value"><?php
                if ($selected_desa === 'all') {
                    echo 'Semua Desa';
                } elseif (!empty($selected_desa)) {
                    foreach ($all_desa as $desa) {
                        if ($desa['id_desa'] == $selected_desa) {
                            echo $desa['nama_desa'];
                            break;
                        }
                    }
                } else {
                    echo 'Semua Desa';
                }
                ?></span>
            </div>
        </div>
    </div>

    <div class="analisis-admin-tabs">
        <div class="analisis-admin-tab-nav">
            <button class="analisis-admin-tab-btn <?php echo ($_POST['tab'] ?? $_GET['tab'] ?? '') === 'prediksi' ? '' : 'active'; ?>" onclick="switchTab('tingkat1')">
                Statistik Tingkat 1
            </button>
            <button class="analisis-admin-tab-btn <?php echo ($_POST['tab'] ?? '') === 'prediksi' ? '' : ''; ?>" onclick="switchTab('tingkat2')">
                Statistik Tingkat 2
            </button>
            <button class="analisis-admin-tab-btn" onclick="switchTab('indeks_ekonomi')">
                Indeks Ketahanan Ekonomi
            </button>
            <button class="analisis-admin-tab-btn <?php echo ($_POST['tab'] ?? $_GET['tab'] ?? '') === 'prediksi' ? 'active' : ''; ?>" onclick="switchTab('prediksi')">
                Prediksi & Proyeksi
            </button>
        </div>
    </div>

    <div class="analisis-admin-content">
        <!-- Last Update Display -->
        <?php 
        // Get cache info for display
        $cache_info = null;
        if (isset($using_cache_initial) && $using_cache_initial && isset($cache_status)) {
            $cache_info = $cache_status;
        } else {
            // Try to get cache info for current selection
            $cached_tingkat1 = $cacheManager->getAnalisisData('tingkat1', $selected_desa, false);
            if ($cached_tingkat1['from_cache']) {
                $cache_info = $cached_tingkat1;
            }
        }
        ?>
        <?php if ($cache_info): ?>
            <div class="last-update-display">
                <div class="update-info">
                    <i class="fas fa-clock"></i>
                    <span>Data analisis terakhir diperbarui: <strong><?php echo date('d/m/Y H:i', strtotime($cache_info['last_update'])); ?></strong></span>
                    <small>(<?php echo $cacheManager->timeAgo($cache_info['last_update']); ?>)</small>
                </div>
            </div>
        <?php endif; ?>
<?php endif; ?>
        <!-- Tingkat 1 Tab -->
        <?php if (!isset($is_ajax_tab_load) || (isset($_POST['tab']) && $_POST['tab'] === 'tingkat1')): ?>
        <div class="analisis-admin-tab-content <?php echo ($_POST['tab'] ?? $_GET['tab'] ?? '') === 'prediksi' ? '' : 'active'; ?>" id="tingkat1" data-loaded="true">
            <div class="analisis-admin-grid">
                <div class="card">
                    <div class="card-header">
                        <h3>Statistik Kependudukan</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container-small">
                            <canvas id="chartKependudukanTingkat1" width="400" height="200"></canvas>
                        </div>
                        <div id="kependudukanTingkat1">
                            <div class="analisis-stats-grid">
                                <div class="stat-item">
                                    <h4>Total Penduduk</h4>
                                    <div class="stat-value"><?php echo number_format($demographic_stats['total_penduduk_all']); ?> jiwa</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Laki-laki</h4>
                                    <div class="stat-value"><?php echo number_format($demographic_stats['total_laki']); ?>
                                        (<?php echo $demographic_stats['total_penduduk_all'] > 0 ? round(($demographic_stats['total_laki'] / $demographic_stats['total_penduduk_all']) * 100, 1) : 0; ?>%)
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <h4>Perempuan</h4>
                                    <div class="stat-value"><?php echo number_format($demographic_stats['total_perempuan']); ?>
                                        (<?php echo $demographic_stats['total_penduduk_all'] > 0 ? round(($demographic_stats['total_perempuan'] / $demographic_stats['total_penduduk_all']) * 100, 1) : 0; ?>%)
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <h4>Total KK</h4>
                                    <div class="stat-value"><?php echo number_format($demographic_stats['total_kk']); ?> KK</div>
                                </div>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Kelompok Usia</h5>
                                <div class="breakdown-item">
                                    <span>Balita (0-5 tahun)</span>
                                    <span><?php echo number_format($demographic_stats['total_balita']); ?>
                                        (<?php echo $demographic_stats['total_penduduk_all'] > 0 ? round(($demographic_stats['total_balita'] / $demographic_stats['total_penduduk_all']) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Anak (6-12 tahun)</span>
                                    <span><?php echo number_format($demographic_stats['total_anak']); ?>
                                        (<?php echo $demographic_stats['total_penduduk_all'] > 0 ? round(($demographic_stats['total_anak'] / $demographic_stats['total_penduduk_all']) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Remaja (13-17 tahun)</span>
                                    <span><?php echo number_format($demographic_stats['total_remaja']); ?>
                                        (<?php echo $demographic_stats['total_penduduk_all'] > 0 ? round(($demographic_stats['total_remaja'] / $demographic_stats['total_penduduk_all']) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Dewasa (18-64 tahun)</span>
                                    <span><?php echo number_format($demographic_stats['total_dewasa']); ?>
                                        (<?php echo $demographic_stats['total_penduduk_all'] > 0 ? round(($demographic_stats['total_dewasa'] / $demographic_stats['total_penduduk_all']) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Lansia (65+ tahun)</span>
                                    <span><?php echo number_format($demographic_stats['total_lansia']); ?>
                                        (<?php echo $demographic_stats['total_penduduk_all'] > 0 ? round(($demographic_stats['total_lansia'] / $demographic_stats['total_penduduk_all']) * 100, 1) : 0; ?>%)</span>
                                </div>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Agama</h5>
                                <?php foreach ($demographic_stats['agama_stats'] as $agama => $jumlah): ?>
                                    <div class="breakdown-item">
                                        <span><?php echo $agama; ?></span>
                                        <span><?php echo number_format($jumlah); ?>
                                            (<?php echo $demographic_stats['total_penduduk_all'] > 0 ? round(($jumlah / $demographic_stats['total_penduduk_all']) * 100, 1) : 0; ?>%)</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Tingkat Pendidikan</h5>
                                <?php
                                arsort($demographic_stats['pendidikan_stats']);
                                foreach ($demographic_stats['pendidikan_stats'] as $pendidikan => $jumlah):
                                    ?>
                                    <div class="breakdown-item">
                                        <span><?php echo $pendidikan; ?></span>
                                        <span><?php echo number_format($jumlah); ?>
                                            (<?php echo $demographic_stats['total_penduduk_all'] > 0 ? round(($jumlah / $demographic_stats['total_penduduk_all']) * 100, 1) : 0; ?>%)</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Status Pernikahan</h5>
                                <?php foreach ($demographic_stats['pernikahan_stats'] as $status => $jumlah): ?>
                                    <div class="breakdown-item">
                                        <span><?php echo $status; ?></span>
                                        <span><?php echo number_format($jumlah); ?>
                                            (<?php echo $demographic_stats['total_penduduk_all'] > 0 ? round(($jumlah / $demographic_stats['total_penduduk_all']) * 100, 1) : 0; ?>%)</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Distribusi Ekonomi</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container-small">
                            <canvas id="chartEkonomiTingkat1" width="400" height="200"></canvas>
                        </div>
                        <div id="ekonomiTingkat1">
                            <div class="analisis-stats-grid">
                                <div class="stat-item">
                                    <h4>Total Mata Pencaharian</h4>
                                    <div class="stat-value"><?php echo number_format(count($economic_stats['mata_pencaharian_stats'])); ?> jenis</div>
                                </div>
                                <div class="stat-item">
                                    <h4>UMKM</h4>
                                    <div class="stat-value"><?php echo number_format($economic_stats['total_umkm']); ?> unit</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Pertanian</h4>
                                    <div class="stat-value"><?php echo number_format($economic_stats['total_pertanian']); ?> unit</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Pasar</h4>
                                    <div class="stat-value"><?php echo number_format($economic_stats['total_pasar']); ?> unit</div>
                                </div>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Mata Pencaharian</h5>
                                <?php
                                arsort($economic_stats['mata_pencaharian_stats']);
                                $total_mp = array_sum($economic_stats['mata_pencaharian_stats']);
                                foreach ($economic_stats['mata_pencaharian_stats'] as $pekerjaan => $jumlah):
                                    ?>
                                    <div class="breakdown-item">
                                        <span><?php echo $pekerjaan; ?></span>
                                        <span><?php echo number_format($jumlah); ?>
                                            (<?php echo $total_mp > 0 ? round(($jumlah / $total_mp) * 100, 1) : 0; ?>%)</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>UMKM Berdasarkan Jenis Usaha</h5>
                                <?php foreach ($umkm_stats as $jenis => $jumlah): ?>
                                    <div class="breakdown-item">
                                        <span><?php echo $jenis; ?></span>
                                        <span><?php echo number_format($jumlah); ?> unit</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Warga Miskin & Bantuan Sosial</h5>
                                <div class="breakdown-item">
                                    <span>Total Warga Miskin</span>
                                    <span><?php echo number_format($poverty_stats['total']); ?> orang</span>
                                </div>
                                <?php foreach ($poverty_stats['bantuan'] as $jenis => $jumlah): ?>
                                    <div class="breakdown-item">
                                        <span><?php echo $jenis; ?></span>
                                        <span><?php echo number_format($jumlah); ?> penerima</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Fasilitas Pendidikan</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container-small">
                            <canvas id="chartPendidikanTingkat1" width="400" height="200"></canvas>
                        </div>
                        <div id="pendidikanTingkat1">
                            <div class="analisis-stats-grid">
                                <div class="stat-item">
                                    <h4>Total Fasilitas</h4>
                                    <div class="stat-value"><?php echo number_format(array_sum($education_stats['fasilitas_stats'])); ?> unit</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Total Kapasitas</h4>
                                    <div class="stat-value"><?php echo number_format($education_stats['total_kapasitas']); ?> siswa</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Total Guru</h4>
                                    <div class="stat-value"><?php echo number_format($education_stats['total_guru']); ?> orang</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Rasio Guru:Siswa</h4>
                                    <div class="stat-value">1:<?php echo $education_stats['total_guru'] > 0 ? round($education_stats['total_kapasitas'] / $education_stats['total_guru']) : 0; ?></div>
                                </div>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Jenis Fasilitas</h5>
                                <?php foreach ($education_stats['fasilitas_stats'] as $jenis => $jumlah): ?>
                                    <div class="breakdown-item">
                                        <span><?php echo $jenis; ?></span>
                                        <span><?php echo number_format($jumlah); ?> unit</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Kapasitas Per Jenjang</h5>
                                <?php foreach ($capacity_per_level as $jenis => $kapasitas): ?>
                                    <div class="breakdown-item">
                                        <span><?php echo $jenis; ?></span>
                                        <span><?php echo number_format($kapasitas); ?> siswa</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Status Infrastruktur</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container-small">
                            <canvas id="chartInfrastrukturTingkat1" width="400" height="200"></canvas>
                        </div>
                        <div id="infrastrukturTingkat1">
                            <?php $total_jalan_unit = $infrastructure_stats['jalan_baik'] + $infrastructure_stats['jalan_sedang'] + $infrastructure_stats['jalan_rusak']; ?>
                            <div class="analisis-stats-grid">
                                <div class="stat-item">
                                    <h4>Total Jalan</h4>
                                    <div class="stat-value"><?php echo number_format($total_jalan_unit); ?> unit</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Panjang Jalan</h4>
                                    <div class="stat-value"><?php echo number_format($infrastructure_stats['total_panjang_jalan'], 1); ?> km</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Total Jembatan</h4>
                                    <div class="stat-value"><?php echo number_format($infrastructure_stats['total_jembatan']); ?> unit</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Kondisi Baik</h4>
                                    <div class="stat-value">
                                        <?php echo ($total_jalan_unit + $infrastructure_stats['total_jembatan']) > 0 ? round((($infrastructure_stats['jalan_baik'] + $infrastructure_stats['jembatan_baik']) / ($total_jalan_unit + $infrastructure_stats['total_jembatan'])) * 100, 1) : 0; ?>%
                                    </div>
                                </div>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Kondisi Jalan</h5>
                                <div class="breakdown-item">
                                    <span>Baik</span>
                                    <span><?php echo number_format($infrastructure_stats['jalan_baik']); ?>
                                        (<?php echo $total_jalan_unit > 0 ? round(($infrastructure_stats['jalan_baik'] / $total_jalan_unit) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Sedang</span>
                                    <span><?php echo number_format($infrastructure_stats['jalan_sedang']); ?>
                                        (<?php echo $total_jalan_unit > 0 ? round(($infrastructure_stats['jalan_sedang'] / $total_jalan_unit) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Rusak</span>
                                    <span><?php echo number_format($infrastructure_stats['jalan_rusak']); ?>
                                        (<?php echo $total_jalan_unit > 0 ? round(($infrastructure_stats['jalan_rusak'] / $total_jalan_unit) * 100, 1) : 0; ?>%)</span>
                                </div>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Kondisi Jembatan</h5>
                                <div class="breakdown-item">
                                    <span>Baik</span>
                                    <span><?php echo number_format($infrastructure_stats['jembatan_baik']); ?>
                                        (<?php echo $infrastructure_stats['total_jembatan'] > 0 ? round(($infrastructure_stats['jembatan_baik'] / $infrastructure_stats['total_jembatan']) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Sedang</span>
                                    <span><?php echo number_format($infrastructure_stats['jembatan_sedang']); ?>
                                        (<?php echo $infrastructure_stats['total_jembatan'] > 0 ? round(($infrastructure_stats['jembatan_sedang'] / $infrastructure_stats['total_jembatan']) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Rusak</span>
                                    <span><?php echo number_format($infrastructure_stats['jembatan_rusak']); ?>
                                        (<?php echo $infrastructure_stats['total_jembatan'] > 0 ? round(($infrastructure_stats['jembatan_rusak'] / $infrastructure_stats['total_jembatan']) * 100, 1) : 0; ?>%)</span>
                                </div>
                            </div>

                            <div class="analisis-breakdown">
                                <h5>Analisis Spasial & Geografis</h5>
                                <div class="breakdown-item">
                                    <span>Rata-rata Kepadatan Penduduk</span>
                                    <span><?php echo $spatial_analysis['rata_kepadatan']; ?> jiwa/km²</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Total Fasilitas Umum</span>
                                    <span><?php echo number_format($spatial_analysis['total_fasilitas_umum']); ?> unit</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Rata-rata Fasilitas per Desa</span>
                                    <span><?php echo count($desa_data) > 0 ? round($spatial_analysis['total_fasilitas_umum'] / count($desa_data), 1) : 0; ?> unit</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <!-- Tingkat 2 Tab -->
        <?php if (!isset($is_ajax_tab_load) || (isset($_POST['tab']) && $_POST['tab'] === 'tingkat2')): ?>
        <div class="analisis-admin-tab-content" id="tingkat2" data-loaded="false">
            <?php if ((isset($_POST['tab']) && $_POST['tab'] === 'tingkat2') || (isset($_POST['ajax_load']) && $_POST['tab'] === 'tingkat2')): ?>
            <div class="analisis-admin-grid">
                <div class="card">
                    <div class="card-header">
                        <h3>Analisis Kependudukan Lanjutan</h3>
                        <button class="card-action-btn" onclick="openDetailAnalisis('kependudukan')" title="Lihat Detail Data">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="chart-container-small">
                            <canvas id="chartKependudukanTingkat2" width="400" height="200"></canvas>
                        </div>
                        
                        <div class="analisis-stats-grid">
                            <div class="stat-item">
                                <h4>KK Perempuan + Anak Sekolah</h4>
                                <div class="stat-value"><?php echo number_format($advanced_demographics['kk_perempuan_anak_sekolah'] ?? 0); ?> KK</div>
                            </div>
                            <div class="stat-item">
                                <h4>Produktif Tanpa Kerja Tetap</h4>
                                <div class="stat-value"><?php echo $advanced_demographics['persen_produktif_tanpa_kerja'] ?? 0; ?>%</div>
                            </div>
                            <div class="stat-item">
                                <h4>KK Lansia + Tanggungan</h4>
                                <div class="stat-value"><?php echo number_format($advanced_demographics['kk_lansia_tanggungan'] ?? 0); ?> KK</div>
                            </div>
                            <div class="stat-item">
                                <h4>Rasio Ketergantungan</h4>
                                <div class="stat-value"><?php echo $advanced_demographics['rasio_ketergantungan'] ?? 0; ?>%</div>
                            </div>
                        </div>

                        <div class="analisis-breakdown">
                            <h5>Detail KK Perempuan + Anak Sekolah</h5>
                            <?php if (!empty($advanced_demographics['kk_perempuan_detail']) && array_sum($advanced_demographics['kk_perempuan_detail']) > 0): ?>
                                <div class="breakdown-item">
                                    <span>Anak 7-12 tahun</span>
                                    <span><?php echo number_format($advanced_demographics['kk_perempuan_detail']['usia_7_12'] ?? 0); ?> anak</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Anak 13-15 tahun</span>
                                    <span><?php echo number_format($advanced_demographics['kk_perempuan_detail']['usia_13_15'] ?? 0); ?> anak</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Anak 16-18 tahun</span>
                                    <span><?php echo number_format($advanced_demographics['kk_perempuan_detail']['usia_16_18'] ?? 0); ?> anak</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>KK Miskin</span>
                                    <span><?php echo number_format($advanced_demographics['kk_perempuan_detail']['miskin'] ?? 0); ?> KK</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>KK Mampu</span>
                                    <span><?php echo number_format($advanced_demographics['kk_perempuan_detail']['mampu'] ?? 0); ?> KK</span>
                                </div>
                            <?php else: ?>
                                <div class="breakdown-item">
                                    <span>Tidak ada data</span>
                                    <span>-</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="analisis-breakdown">
                            <h5>Detail Produktif Tanpa Kerja Tetap</h5>
                            <?php if (!empty($advanced_demographics['produktif_detail']) && array_sum($advanced_demographics['produktif_detail']) > 0): ?>
                            <h6 class="breakdown-section">Berdasarkan Pendidikan:</h6>
                            <div class="breakdown-item">
                                <span>SD</span>
                                <span><?php echo number_format($advanced_demographics['produktif_detail']['sd'] ?? 0); ?> orang</span>
                            </div>
                            <div class="breakdown-item">
                                <span>SMP</span>
                                <span><?php echo number_format($advanced_demographics['produktif_detail']['smp'] ?? 0); ?> orang</span>
                            </div>
                            <div class="breakdown-item">
                                <span>SMA</span>
                                <span><?php echo number_format($advanced_demographics['produktif_detail']['sma'] ?? 0); ?> orang</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Diploma</span>
                                <span><?php echo number_format($advanced_demographics['produktif_detail']['diploma'] ?? 0); ?> orang</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Sarjana</span>
                                <span><?php echo number_format($advanced_demographics['produktif_detail']['sarjana'] ?? 0); ?> orang</span>
                            </div>
                            <h6 class="breakdown-section">Berdasarkan Jenis Kelamin:</h6>
                            <div class="breakdown-item">
                                <span>Laki-laki</span>
                                <span><?php echo number_format($advanced_demographics['produktif_detail']['laki'] ?? 0); ?> orang</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Perempuan</span>
                                <span><?php echo number_format($advanced_demographics['produktif_detail']['perempuan'] ?? 0); ?> orang</span>
                            </div>
                            <?php else: ?>
                            <div class="breakdown-item">
                                <span>Tidak ada data</span>
                                <span>-</span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="analisis-breakdown">
                            <h5>Detail KK Lansia + Tanggungan</h5>
                            <?php if (!empty($advanced_demographics['lansia_detail']) && array_sum($advanced_demographics['lansia_detail']) > 0): ?>
                            <h6 class="breakdown-section">Umur Kepala Keluarga:</h6>
                            <div class="breakdown-item">
                                <span>65-70 tahun</span>
                                <span><?php echo number_format($advanced_demographics['lansia_detail']['usia_65_70']); ?> KK</span>
                            </div>
                            <div class="breakdown-item">
                                <span>71-75 tahun</span>
                                <span><?php echo number_format($advanced_demographics['lansia_detail']['usia_71_75']); ?> KK</span>
                            </div>
                            <div class="breakdown-item">
                                <span>76+ tahun</span>
                                <span><?php echo number_format($advanced_demographics['lansia_detail']['usia_76_plus']); ?> KK</span>
                            </div>
                            <h6 class="breakdown-section">Jumlah Tanggungan:</h6>
                            <div class="breakdown-item">
                                <span>1-2 anak</span>
                                <span><?php echo number_format($advanced_demographics['lansia_detail']['tanggungan_1_2']); ?> KK</span>
                            </div>
                            <div class="breakdown-item">
                                <span>3-4 anak</span>
                                <span><?php echo number_format($advanced_demographics['lansia_detail']['tanggungan_3_4']); ?> KK</span>
                            </div>
                            <div class="breakdown-item">
                                <span>5+ anak</span>
                                <span><?php echo number_format($advanced_demographics['lansia_detail']['tanggungan_5_plus']); ?> KK</span>
                            </div>
                            <?php else: ?>
                            <div class="breakdown-item">
                                <span>Tidak ada data</span>
                                <span>-</span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="analisis-breakdown">
                            <h5>Detail Pendidikan Tinggi Tidak Sesuai Bidang</h5>
                            <?php if (!empty($advanced_demographics['pendidikan_tinggi_detail']) && array_sum($advanced_demographics['pendidikan_tinggi_detail']) > 0): ?>
                            <h6 class="breakdown-section">Jenjang Pendidikan:</h6>
                            <div class="breakdown-item">
                                <span>D3</span>
                                <span><?php echo number_format($advanced_demographics['pendidikan_tinggi_detail']['d3']); ?> orang</span>
                            </div>
                            <div class="breakdown-item">
                                <span>S1</span>
                                <span><?php echo number_format($advanced_demographics['pendidikan_tinggi_detail']['s1']); ?> orang</span>
                            </div>
                            <div class="breakdown-item">
                                <span>S2</span>
                                <span><?php echo number_format($advanced_demographics['pendidikan_tinggi_detail']['s2']); ?> orang</span>
                            </div>
                            <div class="breakdown-item">
                                <span>S3</span>
                                <span><?php echo number_format($advanced_demographics['pendidikan_tinggi_detail']['s3']); ?> orang</span>
                            </div>
                            <?php else: ?>
                            <div class="breakdown-item">
                                <span>Tidak ada data</span>
                                <span>-</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Analisis Ekonomi Lanjutan</h3>
                        <button class="card-action-btn" onclick="openDetailAnalisis('ekonomi')" title="Lihat Detail Data">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="chart-container-small">
                            <canvas id="chartEkonomiTingkat2" width="400" height="200"></canvas>
                        </div>
                        
                        <div class="analisis-stats-grid">
                            <div class="stat-item">
                                <h4>Petani Lahan &lt; 1 Ha</h4>
                                <div class="stat-value"><?php echo number_format($economic_advanced['petani_lahan_kecil'] ?? 0); ?> orang</div>
                            </div>
                            <div class="stat-item">
                                <h4>Pendapatan Pertanian</h4>
                                <div class="stat-value"><?php echo $income_distribution['total_pendapatan_real'] > 0 ? round(($income_distribution['pendapatan_pertanian'] / $income_distribution['total_pendapatan_real']) * 100, 1) : 0; ?>%</div>
                            </div>
                            <div class="stat-item">
                                <h4>Korelasi Pendidikan-Pekerjaan</h4>
                                <div class="stat-value">0.73 (Tinggi)</div>
                            </div>
                            <div class="stat-item">
                                <h4>Indeks Gini</h4>
                                <div class="stat-value">0</div>
                            </div>
                        </div>

                        <div class="analisis-breakdown">
                            <h5>Korelasi Pendidikan-Pekerjaan</h5>
                            <?php if (!empty($education_job_correlation) && array_sum(array_column($education_job_correlation, 'jumlah')) > 0): ?>
                                <?php foreach ($education_job_correlation as $tingkat => $data): ?>
                                    <?php if ($data['jumlah'] > 0): ?>
                                        <?php 
                                        $rata_pendapatan = round($data['total_pendapatan'] / $data['jumlah']);
                                        $persen_pertanian = round(($data['pertanian'] / $data['jumlah']) * 100);
                                        $persen_non_pertanian = round(($data['non_pertanian'] / $data['jumlah']) * 100);
                                        ?>
                                        <div class="breakdown-item">
                                            <span><?php echo $tingkat; ?> - Rata-rata: Rp <?php echo number_format($rata_pendapatan); ?></span>
                                            <span><?php echo $persen_pertanian; ?>% Pertanian | <?php echo $persen_non_pertanian; ?>% Non-Pertanian</span>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="breakdown-item">
                                    <span>Tidak ada data korelasi</span>
                                    <span>-</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($income_gap)): ?>
                        <div class="analisis-breakdown">
                            <h5>Kesenjangan Ekonomi Antar Desa</h5>
                            <div class="breakdown-item">
                                <span>Desa Tertinggi (<?php echo $income_gap['desa_tertinggi']; ?>)</span>
                                <span>Rp <?php echo number_format($income_gap['pendapatan_tertinggi']); ?></span>
                            </div>
                            <div class="breakdown-item">
                                <span>Desa Terendah (<?php echo $income_gap['desa_terendah']; ?>)</span>
                                <span>Rp <?php echo number_format($income_gap['pendapatan_terendah']); ?></span>
                            </div>
                            <div class="breakdown-item">
                                <span>Selisih Pendapatan</span>
                                <span>Rp <?php echo number_format($income_gap['selisih']); ?> (<?php echo $income_gap['persen_selisih']; ?>%)</span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="analisis-breakdown">
                            <h5>Detail Petani Lahan &lt; 1 Ha Tanpa Bantuan</h5>
                            <?php if (array_sum($economic_advanced['petani_lahan_detail'] ?? []) > 0 || !empty($economic_advanced['komoditas_detail'] ?? [])): ?>
                            <h6 class="breakdown-section">Distribusi Luas Lahan:</h6>
                            <div class="breakdown-item">
                                <span>Lahan 0.1-0.5 Ha</span>
                                <span><?php echo number_format($economic_advanced['petani_lahan_detail']['lahan_01_05ha'] ?? 0); ?> petani</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Lahan 0.5-1 Ha</span>
                                <span><?php echo number_format($economic_advanced['petani_lahan_detail']['lahan_05_1ha'] ?? 0); ?> petani</span>
                            </div>
                            <?php if (!empty($economic_advanced['komoditas_detail'] ?? [])): ?>
                                <h6 class="breakdown-section">Jenis Komoditas:</h6>
                                <?php foreach (($economic_advanced['komoditas_detail'] ?? []) as $komoditas => $jumlah): ?>
                                <div class="breakdown-item">
                                    <span><?php echo $komoditas; ?></span>
                                    <span><?php echo number_format($jumlah); ?> petani</span>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <h6 class="breakdown-section">Tingkat Pendapatan:</h6>
                            <div class="breakdown-item">
                                <span>< 1 Juta</span>
                                <span><?php echo number_format($economic_advanced['petani_lahan_detail']['pendapatan_1juta'] ?? 0); ?> petani</span>
                            </div>
                            <div class="breakdown-item">
                                <span>1-2 Juta</span>
                                <span><?php echo number_format($economic_advanced['petani_lahan_detail']['pendapatan_1_2juta'] ?? 0); ?> petani</span>
                            </div>
                            <div class="breakdown-item">
                                <span>2-3 Juta</span>
                                <span><?php echo number_format($economic_advanced['petani_lahan_detail']['pendapatan_2_3juta'] ?? 0); ?> petani</span>
                            </div>
                            <?php else: ?>
                            <div class="breakdown-item">
                                <span>Tidak ada data</span>
                                <span>-</span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="analisis-breakdown">
                            <h5>Rincian Non-Pertanian</h5>
                            <?php if (!empty($non_agriculture['detail']) && $non_agriculture['total'] > 0): ?>
                                <?php foreach ($non_agriculture['detail'] as $sektor => $jumlah): ?>
                                    <?php $persen = round(($jumlah / $non_agriculture['total']) * 100, 1); ?>
                                    <div class="breakdown-item">
                                        <span><?php echo $sektor; ?></span>
                                        <span><?php echo number_format($jumlah); ?> orang (<?php echo $persen; ?>%)</span>
                                    </div>
                                <?php endforeach; ?>
                                <div class="breakdown-item">
                                    <span><strong>Total Non-Pertanian</strong></span>
                                    <span><strong><?php echo number_format($non_agriculture['total']); ?> orang</strong></span>
                                </div>
                            <?php else: ?>
                                <div class="breakdown-item">
                                    <span>Tidak ada data non-pertanian</span>
                                    <span>-</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="analisis-breakdown">
                            <h5>Distribusi Pendapatan Desa per bulan</h5>
                            <div class="breakdown-item">
                                <span>Sektor Pertanian</span>
                                <span>Rp <?php echo number_format($income_distribution['pendapatan_pertanian']); ?>
                                    (<?php echo $income_distribution['total_pendapatan_real'] > 0 ? round(($income_distribution['pendapatan_pertanian'] / $income_distribution['total_pendapatan_real']) * 100, 1) : 0; ?>%)</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Sektor Non-Pertanian</span>
                                <span>Rp <?php echo number_format($income_distribution['pendapatan_non_pertanian']); ?>
                                    (<?php echo $income_distribution['total_pendapatan_real'] > 0 ? round(($income_distribution['pendapatan_non_pertanian'] / $income_distribution['total_pendapatan_real']) * 100, 1) : 0; ?>%)</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Total Pendapatan Desa</span>
                                <span>Rp <?php echo number_format($income_distribution['total_pendapatan_real']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Analisis Integrasi Data Lintas Kategori</h3>
                        <button class="card-action-btn" onclick="openDetailAnalisis('integrasi')" title="Lihat Detail Data">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="chart-container-small">
                            <canvas id="chartIntegrasiTingkat2" width="400" height="200"></canvas>
                        </div>
                        
                        <div class="analisis-stats-grid">
                            <div class="stat-item">
                                <h4>KK Miskin + Anak Sekolah</h4>
                                <div class="stat-value"><?php echo number_format($integrated_analysis['kk_miskin_anak_sekolah'] ?? 0); ?> KK</div>
                            </div>
                            <div class="stat-item">
                                <h4>Pendidikan Tinggi Tapi Miskin</h4>
                                <div class="stat-value"><?php echo number_format($integrated_analysis['pendidikan_tinggi_miskin']); ?> orang</div>
                            </div>
                            <div class="stat-item">
                                <h4>Rasio Ketergantungan Ekonomi</h4>
                                <div class="stat-value"><?php echo number_format(array_sum($integrated_analysis['rasio_ketergantungan'])); ?> KK</div>
                            </div>
                            <div class="stat-item">
                                <h4>Anak Tidak Sekolah karena ekonomi keluarga</h4>
                                <div class="stat-value"><?php echo number_format(array_sum($integrated_analysis['anak_tidak_sekolah_ekonomi'])); ?> anak</div>
                            </div>
                        </div>

                        <div class="analisis-breakdown">
                            <h5>Distribusi Jenjang KK Miskin + Anak Sekolah</h5>
                            <?php if (array_sum($integrated_analysis['kk_miskin_jenjang']) > 0): ?>
                                <?php foreach ($integrated_analysis['kk_miskin_jenjang'] as $jenjang => $jumlah): ?>
                                    <div class="breakdown-item">
                                        <span><?php echo $jenjang; ?></span>
                                        <span><?php echo number_format($jumlah); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="breakdown-item">
                                    <span>Tidak ada data</span>
                                    <span>-</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="analisis-breakdown">
                            <h5>Distribusi Keluarga Rasio Ketergantungan</h5>
                            <?php foreach ($integrated_analysis['rasio_ketergantungan'] as $kategori => $jumlah): ?>
                                <div class="breakdown-item">
                                    <span><?php echo $kategori; ?> tanggungan</span>
                                    <span><?php echo number_format($jumlah); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="analisis-breakdown">
                            <h5>Faktor Penyebab Pendidikan Tinggi Tapi Miskin</h5>
                            <?php if (!empty($integrated_analysis['faktor_penyebab'])): ?>
                                <?php foreach ($integrated_analysis['faktor_penyebab'] as $faktor => $jumlah): ?>
                                    <div class="breakdown-item">
                                        <span><?php echo ucfirst($faktor); ?></span>
                                        <span><?php echo number_format($jumlah); ?> KK</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="breakdown-item">
                                    <span>Tidak ada data</span>
                                    <span>-</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="analisis-breakdown">
                            <h5>Anak Petani → Pendidikan Tinggi</h5>
                            <?php if (array_sum($integrated_analysis['anak_petani_pendidikan']) > 0): ?>
                                <div class="breakdown-item">
                                    <span>D3</span>
                                    <span><?php echo number_format($integrated_analysis['anak_petani_pendidikan']['D3']); ?></span>
                                </div>
                                <div class="breakdown-item">
                                    <span>S1</span>
                                    <span><?php echo number_format($integrated_analysis['anak_petani_pendidikan']['S1']); ?></span>
                                </div>
                                <div class="breakdown-item">
                                    <span>S2</span>
                                    <span><?php echo number_format($integrated_analysis['anak_petani_pendidikan']['S2']); ?></span>
                                </div>
                            <?php else: ?>
                                <div class="breakdown-item">
                                    <span>Tidak ada data</span>
                                    <span>-</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($integrated_analysis['bidang_studi'])): ?>
                            <div class="analisis-breakdown">
                                <h5>Bidang Studi Populer</h5>
                                <?php foreach ($integrated_analysis['bidang_studi'] as $bidang => $jumlah): ?>
                                    <div class="breakdown-item">
                                        <span><?php echo ucfirst($bidang); ?></span>
                                        <span><?php echo number_format($jumlah); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (array_sum($integrated_analysis['status_lulus']) > 0): ?>
                            <div class="analisis-breakdown">
                                <h5>Status Setelah Lulus</h5>
                                <div class="breakdown-item">
                                    <span>Bekerja</span>
                                    <span><?php echo number_format($integrated_analysis['status_lulus']['bekerja']); ?></span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Kuliah</span>
                                    <span><?php echo number_format($integrated_analysis['status_lulus']['kuliah']); ?></span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Menganggur</span>
                                    <span><?php echo number_format($integrated_analysis['status_lulus']['menganggur']); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="analisis-breakdown">
                            <h5>Anak Tidak Sekolah Berdasarkan Tingkat Ekonomi</h5>
                            <?php if (array_sum($integrated_analysis['anak_tidak_sekolah_ekonomi']) > 0): ?>
                                <?php foreach ($integrated_analysis['anak_tidak_sekolah_ekonomi'] as $tingkat => $jumlah): ?>
                                    <div class="breakdown-item">
                                        <span><?php echo ucfirst($tingkat); ?></span>
                                        <span><?php echo number_format($jumlah); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="breakdown-item">
                                    <span>Tidak ada data</span>
                                    <span>-</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Analisis Spasial & Prediktif</h3>
                        <button class="card-action-btn" onclick="openDetailAnalisis('spasial')" title="Lihat Detail Data">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="chart-container-small">
                            <canvas id="chartSpasialTingkat2" width="400" height="200"></canvas>
                        </div>
                        
                        <div class="analisis-stats-grid">
                            <div class="stat-item">
                                <h4>Desa Tanpa Fasilitas Pendidikan</h4>
                                <div class="stat-value"><?php echo number_format($spatial_predictive['desa_padat_minim_fasilitas'] ?? 0); ?> desa</div>
                            </div>
                            <div class="stat-item">
                                <h4>Persentase Jalan Baik</h4>
                                <div class="stat-value"><?php echo $spatial_predictive['indeks_aksesibilitas_rata'] ?? 0; ?>%</div>
                            </div>
                            <div class="stat-item">
                                <h4>Total Jalan Rusak</h4>
                                <div class="stat-value"><?php echo number_format($spatial_predictive['total_jalan_rusak'] ?? 0); ?> unit</div>
                            </div>
                            <div class="stat-item">
                                <h4>Desa Penduduk Terbanyak</h4>
                                <div class="stat-value">
                                    <?php echo $spatial_predictive['potensi_pengembangan'][0]['nama_desa'] ?? 'N/A'; ?>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($spatial_predictive['desa_minim_fasilitas_list'] ?? [])): ?>
                        <div class="analisis-breakdown">
                            <h5>Desa Tanpa Fasilitas Pendidikan</h5>
                            <?php foreach (($spatial_predictive['desa_minim_fasilitas_list'] ?? []) as $nama_desa): ?>
                                <div class="breakdown-item">
                                    <span><?php echo $nama_desa; ?></span>
                                    <span>0 fasilitas</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <div class="analisis-breakdown">
                            <h5>5 Desa dengan Penduduk Terbanyak</h5>
                            <?php foreach (($spatial_predictive['potensi_pengembangan'] ?? []) as $index => $desa_potensi): ?>
                                <div class="breakdown-item">
                                    <span><?php echo ($index + 1) . '. ' . $desa_potensi['nama_desa']; ?></span>
                                    <span><?php echo number_format($desa_potensi['total_penduduk']); ?> jiwa</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="tab-loading">
                <div class="loader-container">
                    <div class="loader"></div>
                    <p>Memuat analisis tingkat 2...</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Indeks Ketahanan Ekonomi Tab -->
        <?php if (!isset($is_ajax_tab_load) || (isset($_POST['tab']) && $_POST['tab'] === 'indeks_ekonomi')): ?>
        <div class="analisis-admin-tab-content" id="indeks_ekonomi" data-loaded="false">
            <?php if ((isset($_POST['tab']) && $_POST['tab'] === 'indeks_ekonomi') || (isset($_POST['ajax_load']) && $_POST['tab'] === 'indeks_ekonomi')): ?>
                <?php require_once __DIR__ . '/indeks-ekonomi-view.php'; ?>
            <?php else: ?>
            <div class="tab-loading">
                <div class="loader-container">
                    <div class="loader"></div>
                    <p>Memuat indeks ketahanan ekonomi...</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Prediksi Tab -->
        <?php if (!isset($is_ajax_tab_load) || (isset($_POST['tab']) && $_POST['tab'] === 'prediksi')): ?>
        <div class="analisis-admin-tab-content <?php echo ($_POST['tab'] ?? $_GET['tab'] ?? '') === 'prediksi' ? 'active' : ''; ?>" id="prediksi" data-loaded="<?php echo ($_POST['tab'] ?? $_GET['tab'] ?? '') === 'prediksi' ? 'true' : 'false'; ?>">
            <?php if (($_POST['tab'] ?? $_GET['tab'] ?? '') !== 'prediksi'): ?>
            <div class="tab-loading">
                <div class="loader-container">
                    <div class="loader"></div>
                    <p>Memuat prediksi dan proyeksi...</p>
                </div>
            </div>
            <?php else: ?>
            <div class="analisis-admin-prediction">
                <div class="card">
                    <div class="card-header">
                        <h3>Prediksi Struktur Penduduk 5 Tahun</h3>
                        <button class="btn btn-secondary" onclick="generatePredictions()" style="float: right;">
                            <i class="fas fa-sync"></i> Generate Prediksi
                        </button>
                    </div>
                    <div class="card-body">
                        <?php
                        $showPredictions = isset($_POST['generate_predictions']) || isset($_GET['show_predictions']) || isset($_GET['tab']);
                        $predictions = [];
                        
                        if ($showPredictions) {
                            $api_url = BASE_URL . 'api/admin/analisis.api.php?endpoint=prediksi-data&action=generate';
                            $context = stream_context_create([
                                'http' => [
                                    'method' => 'POST',
                                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                                    'content' => http_build_query([])
                                ]
                            ]);
                            
                            $response = @file_get_contents($api_url, false, $context);
                            
                            if ($response) {
                                $api_result = json_decode($response, true);
                                
                                if ($api_result && $api_result['success']) {
                                    $predictions = $api_result['data']['data'] ?? [];
                                }
                            }
                        }
                        
                        if (empty($predictions) && !$showPredictions): ?>
                            <div class="no-data">
                                <i class="fas fa-chart-line fa-2x mb-3 text-muted"></i>
                                <h6 class="text-muted">Prediksi Struktur Penduduk</h6>
                                <p class="text-muted">Sistem prediksi menggunakan data penduduk berdasarkan tahun lahir untuk memprediksi struktur penduduk 5 tahun ke depan.</p>
                            </div>
                        <?php else: ?>
                            <div class="prediction-charts-section">
                                <div class="charts-grid">
                                    <div class="chart-container">
                                        <h4>Tren Prediksi Penduduk</h4>
                                        <canvas id="chartPrediksiLine" width="400" height="200"></canvas>
                                    </div>
                                    <div class="chart-container">
                                        <h4>Struktur Usia Prediksi <?php echo date('Y') + 5; ?></h4>
                                        <canvas id="chartPrediksiBar" width="400" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="prediction-stats-grid">
                                <?php
                                $totalPredicted2029 = 0;
                                $avgGrowthRate = 0;
                                $avgConfidence = 0;
                                $predictionCount = 0;
                                
                                foreach ($predictions as $pred) {
                                    if ($pred['tahun_prediksi'] == date('Y') + 5) {
                                        $totalPredicted2029 += $pred['total_penduduk_prediksi'];
                                    }
                                    $avgGrowthRate += $pred['growth_rate'];
                                    $avgConfidence += $pred['confidence_level'];
                                    $predictionCount++;
                                }
                                
                                $avgGrowthRate = $predictionCount > 0 ? round($avgGrowthRate / $predictionCount, 2) : 0;
                                $avgConfidence = $predictionCount > 0 ? round($avgConfidence / $predictionCount, 1) : 0;
                                ?>
                                
                                <div class="stat-item">
                                    <h4>Prediksi Total Penduduk <?php echo date('Y') + 5; ?></h4>
                                    <div class="stat-value"><?php echo number_format($totalPredicted2029); ?> jiwa</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Rata-rata Tingkat Pertumbuhan</h4>
                                    <div class="stat-value"><?php echo $avgGrowthRate; ?>% per tahun</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Tingkat Kepercayaan</h4>
                                    <div class="stat-value"><?php echo $avgConfidence; ?>%</div>
                                </div>
                                <div class="stat-item">
                                    <h4>Metode Prediksi</h4>
                                    <div class="stat-value">Linear Regression</div>
                                </div>
                            </div>
                            
                            <div class="prediction-table-section">
                                <div class="table-header">
                                    <h4>Detail Prediksi Per Desa</h4>
                                    <div class="table-controls">
                                        <input type="text" id="searchPrediksi" placeholder="Cari nama desa..." class="search-input">
                                        <select id="entriesPerPage" class="entries-select">
                                            <option value="10">10 entries</option>
                                            <option value="25">25 entries</option>
                                            <option value="50">50 entries</option>
                                            <option value="100">100 entries</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="data-table" id="prediksiTable">
                                        <thead>
                                            <tr>
                                                <th>Desa</th>
                                                <th>Tahun</th>
                                                <th>Total Penduduk</th>
                                                <th>Laki-laki</th>
                                                <th>Perempuan</th>
                                                <th>Balita</th>
                                                <th>Anak</th>
                                                <th>Remaja</th>
                                                <th>Dewasa</th>
                                                <th>Lansia</th>
                                                <th>Growth Rate</th>
                                                <th>Confidence</th>
                                            </tr>
                                        </thead>
                                        <tbody id="prediksiTableBody">
                                            <?php foreach ($predictions as $pred): ?>
                                            <tr>
                                                <td><?php echo $pred['nama_desa']; ?></td>
                                                <td><?php echo $pred['tahun_prediksi']; ?></td>
                                                <td><?php echo number_format($pred['total_penduduk_prediksi']); ?></td>
                                                <td><?php echo number_format($pred['total_laki_prediksi']); ?></td>
                                                <td><?php echo number_format($pred['total_perempuan_prediksi']); ?></td>
                                                <td><?php echo number_format($pred['total_balita_prediksi']); ?></td>
                                                <td><?php echo number_format($pred['total_anak_prediksi']); ?></td>
                                                <td><?php echo number_format($pred['total_remaja_prediksi']); ?></td>
                                                <td><?php echo number_format($pred['total_dewasa_prediksi']); ?></td>
                                                <td><?php echo number_format($pred['total_lansia_prediksi']); ?></td>
                                                <td><?php echo $pred['growth_rate']; ?>%</td>
                                                <td><?php echo $pred['confidence_level']; ?>%</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="table-pagination">
                                    <div class="pagination-info">
                                        <span id="paginationInfo">Showing 1 to 10 of <?php echo count($predictions); ?> entries</span>
                                    </div>
                                    <div class="pagination-controls">
                                        <button id="prevPage" class="pagination-btn" disabled>Previous</button>
                                        <div id="pageNumbers" class="page-numbers"></div>
                                        <button id="nextPage" class="pagination-btn">Next</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="analisis-breakdown">
                                <h5>Prediksi Struktur Usia <?php echo date('Y') + 5; ?></h5>
                                <?php
                                $ageStructure2029 = [
                                    'balita' => 0, 'anak' => 0, 'remaja' => 0, 'dewasa' => 0, 'lansia' => 0
                                ];
                                
                                foreach ($predictions as $pred) {
                                    if ($pred['tahun_prediksi'] == date('Y') + 5) {
                                        $ageStructure2029['balita'] += $pred['total_balita_prediksi'];
                                        $ageStructure2029['anak'] += $pred['total_anak_prediksi'];
                                        $ageStructure2029['remaja'] += $pred['total_remaja_prediksi'];
                                        $ageStructure2029['dewasa'] += $pred['total_dewasa_prediksi'];
                                        $ageStructure2029['lansia'] += $pred['total_lansia_prediksi'];
                                    }
                                }
                                
                                $totalAge = array_sum($ageStructure2029);
                                ?>
                                <div class="breakdown-item">
                                    <span>Balita (0-5 tahun)</span>
                                    <span><?php echo number_format($ageStructure2029['balita']); ?> 
                                        (<?php echo $totalAge > 0 ? round(($ageStructure2029['balita'] / $totalAge) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Anak (6-12 tahun)</span>
                                    <span><?php echo number_format($ageStructure2029['anak']); ?> 
                                        (<?php echo $totalAge > 0 ? round(($ageStructure2029['anak'] / $totalAge) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Remaja (13-17 tahun)</span>
                                    <span><?php echo number_format($ageStructure2029['remaja']); ?> 
                                        (<?php echo $totalAge > 0 ? round(($ageStructure2029['remaja'] / $totalAge) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Dewasa (18-64 tahun)</span>
                                    <span><?php echo number_format($ageStructure2029['dewasa']); ?> 
                                        (<?php echo $totalAge > 0 ? round(($ageStructure2029['dewasa'] / $totalAge) * 100, 1) : 0; ?>%)</span>
                                </div>
                                <div class="breakdown-item">
                                    <span>Lansia (65+ tahun)</span>
                                    <span><?php echo number_format($ageStructure2029['lansia']); ?> 
                                        (<?php echo $totalAge > 0 ? round(($ageStructure2029['lansia'] / $totalAge) * 100, 1) : 0; ?>%)</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Proyeksi Kebutuhan Pembangunan</h3>
                    </div>
                    <div class="card-body">
                        <div class="prediction-grid">
                            <div class="prediction-item">
                                <div class="prediction-icon">
                                    <i class="fas fa-school"></i>
                                </div>
                                <div class="prediction-content">
                                    <h4>Fasilitas Pendidikan</h4>
                                    <p>Proyeksi kebutuhan 5 tahun ke depan</p>
                                    <div class="prediction-value">Cukup</div>
                                </div>
                            </div>

                            <div class="prediction-item">
                                <div class="prediction-icon">
                                    <i class="fas fa-road"></i>
                                </div>
                                <div class="prediction-content">
                                    <h4>Infrastruktur Jalan</h4>
                                    <p>Prioritas perbaikan berdasarkan analisis</p>
                                    <div class="prediction-value">0 km perlu perbaikan</div>
                                </div>
                            </div>

                            <div class="prediction-item">
                                <div class="prediction-icon">
                                    <i class="fas fa-chart-area"></i>
                                </div>
                                <div class="prediction-content">
                                    <h4>Dampak Program</h4>
                                    <p>Simulasi dampak program pembangunan</p>
                                    <div class="prediction-value">+0% kesejahteraan</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
<?php if (!isset($is_ajax_tab_load)): ?>
    </div>
</div>
<?php endif; ?>

<script>
    window.desaData = [
        { id: 'all', name: 'Semua Desa' },
        <?php foreach ($all_desa as $desa): ?>
        { id: '<?php echo $desa['id_desa']; ?>', name: '<?php echo addslashes($desa['nama_desa']); ?>' },
        <?php endforeach; ?>
    ];
</script>