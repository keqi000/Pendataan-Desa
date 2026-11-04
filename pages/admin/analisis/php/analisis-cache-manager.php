<?php
require_once realpath(__DIR__ . '/../../../../config/config.php');
require_once realpath(__DIR__ . '/../../../../database/queries.php');
require_once realpath(__DIR__ . '/analisis-functions.php');

class AnalisisCacheManager {
    private $db;
    private $queries;
    private $analisis;
    
    // Cache expiry time (dalam detik)
    const CACHE_EXPIRY = 600; // 10 menit
    const STALE_THRESHOLD = 300; // 5 menit (trigger background refresh)
    
    public function __construct() {
        $this->queries = new Queries();
        $this->db = $this->queries->db;
        $this->analisis = new AnalisisFunctions();
    }
    
    /**
     * Get analisis data - simplified version without auto-refresh
     * 
     * @param string $tingkat 'tingkat1', 'tingkat2', 'prediksi'
     * @param string $id_desa 'all' atau ID desa spesifik
     * @param bool $force_refresh Force recalculation
     * @return array
     */
    public function getAnalisisData($tingkat, $id_desa = 'all', $force_refresh = false) {
        // Check cache first
        $cached = $this->getCachedData($tingkat, $id_desa);
        
        // If cache exists and not forcing refresh, use cache
        if ($cached && !$force_refresh) {
            $age = time() - strtotime($cached['updated_at']);
            
            return [
                'data' => $cached,
                'from_cache' => true,
                'age_seconds' => $age,
                'last_update' => $cached['updated_at']
            ];
        }
        
        // No cache or force refresh - calculate new data
        return $this->calculateAndCache($tingkat, $id_desa);
    }
    
    /**
     * Get cached data from database
     */
    private function getCachedData($tingkat, $id_desa) {
        $table = $this->getTableName($tingkat);
        
        // Get latest data for this id_desa
        $query = "SELECT * FROM {$table} t1 
                  WHERE t1.id_desa = :id_desa 
                  AND t1.updated_at = (
                      SELECT MAX(t2.updated_at) 
                      FROM {$table} t2 
                      WHERE t2.id_desa = :id_desa2
                  ) 
                  LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->bindParam(':id_desa2', $id_desa);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Check if data is empty
            if ($tingkat === 'tingkat1' && ($result['total_penduduk'] ?? 0) == 0) {
                return null;
            }
            
            if ($tingkat === 'tingkat2' && ($result['total_produktif'] ?? 0) == 0) {
                return null;
            }
            
            // Decode JSON fields and reconstruct data structure
            if ($tingkat === 'tingkat1') {
                return [
                    'demographic_stats' => [
                        'total_penduduk_all' => $result['total_penduduk'],
                        'total_laki' => $result['total_laki'],
                        'total_perempuan' => $result['total_perempuan'],
                        'total_kk' => $result['total_kk'],
                        'total_balita' => $result['total_balita'],
                        'total_anak' => $result['total_anak'],
                        'total_remaja' => $result['total_remaja'],
                        'total_dewasa' => $result['total_dewasa'],
                        'total_lansia' => $result['total_lansia'],
                        'agama_stats' => json_decode($result['agama_stats'], true) ?: [],
                        'pendidikan_stats' => json_decode($result['pendidikan_stats'], true) ?: [],
                        'pernikahan_stats' => json_decode($result['pernikahan_stats'], true) ?: [],
                        'pekerjaan_stats' => json_decode($result['pekerjaan_stats'], true) ?: []
                    ],
                    'economic_stats' => [
                        'mata_pencaharian_stats' => json_decode($result['mata_pencaharian_stats'], true) ?: [],
                        'total_pertanian' => $result['total_pertanian'],
                        'total_umkm' => $result['total_umkm'],
                        'total_pasar' => $result['total_pasar']
                    ],
                    'umkm_stats' => json_decode($result['umkm_jenis_stats'], true) ?: [],
                    'poverty_stats' => $this->calculatePovertyStatsFromCache($result['id_desa']),
                    'education_stats' => [
                        'fasilitas_stats' => json_decode($result['fasilitas_stats'], true) ?: [],
                        'total_kapasitas' => $result['total_kapasitas'],
                        'total_guru' => $result['total_guru']
                    ],
                    'capacity_per_level' => json_decode($result['kapasitas_per_level'], true) ?: [],
                    'infrastructure_stats' => [
                        'jalan_baik' => $result['jalan_baik'],
                        'jalan_sedang' => $result['jalan_sedang'],
                        'jalan_rusak' => $result['jalan_rusak'],
                        'total_panjang_jalan' => $result['total_panjang_jalan'],
                        'jembatan_baik' => $result['jembatan_baik'],
                        'jembatan_sedang' => $result['jembatan_sedang'],
                        'jembatan_rusak' => $result['jembatan_rusak'],
                        'total_jembatan' => $result['total_jembatan']
                    ],
                    'spatial_analysis' => [
                        'rata_kepadatan' => 0,
                        'total_fasilitas_umum' => 0
                    ],
                    'updated_at' => $result['updated_at']
                ];
            } elseif ($tingkat === 'tingkat2') {
                $petani_lahan_detail = json_decode($result['petani_lahan_detail'] ?? '', true);
                $spatial_predictive = json_decode($result['spatial_predictive'] ?? '', true);
                $required_lahan_keys = [
                    'lahan_01_05ha',
                    'lahan_05_1ha',
                    'pendapatan_1juta',
                    'pendapatan_1_2juta',
                    'pendapatan_2_3juta'
                ];
                $required_spatial_keys = [
                    'desa_padat_minim_fasilitas',
                    'desa_minim_fasilitas_list',
                    'indeks_aksesibilitas_rata',
                    'total_jalan_rusak',
                    'potensi_pengembangan'
                ];

                if (
                    !is_array($petani_lahan_detail)
                    || array_diff($required_lahan_keys, array_keys($petani_lahan_detail))
                    || !is_array($spatial_predictive)
                    || array_diff($required_spatial_keys, array_keys($spatial_predictive))
                ) {
                    return null;
                }

                return [
                    'advanced_demographics' => [
                        'kk_perempuan_anak_sekolah' => $result['kk_perempuan_anak_sekolah'],
                        'produktif_tanpa_kerja_tetap' => $result['produktif_tanpa_kerja_tetap'],
                        'total_produktif' => $result['total_produktif'],
                        'kk_lansia_tanggungan' => $result['kk_lansia_tanggungan'],
                        'penduduk_non_produktif' => $result['penduduk_non_produktif'],
                        'penduduk_produktif' => $result['penduduk_produktif'],
                        'rasio_ketergantungan' => $result['rasio_ketergantungan'],
                        'persen_produktif_tanpa_kerja' => $result['persen_produktif_tanpa_kerja'],
                        'kk_perempuan_detail' => json_decode($result['kk_perempuan_detail'], true) ?: [],
                        'produktif_detail' => json_decode($result['produktif_detail'], true) ?: [],
                        'lansia_detail' => json_decode($result['lansia_detail'], true) ?: [],
                        'pendidikan_tinggi_detail' => json_decode($result['pendidikan_tinggi_detail'], true) ?: []
                    ],
                    'economic_advanced' => [
                        'petani_lahan_kecil' => $result['petani_lahan_kecil'],
                        'petani_lahan_detail' => $petani_lahan_detail,
                        'komoditas_detail' => json_decode($result['komoditas_detail'], true) ?: []
                    ],
                    'education_job_correlation' => json_decode($result['education_job_correlation'], true) ?: [],
                    'income_gap' => json_decode($result['income_gap'], true) ?: [],
                    'non_agriculture' => json_decode($result['non_agriculture'], true) ?: [],
                    'integrated_analysis' => json_decode($result['integrated_analysis'], true) ?: [],
                    'spatial_predictive' => $spatial_predictive,
                    'income_distribution' => json_decode($result['income_distribution'], true) ?: [],
                    'updated_at' => $result['updated_at']
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Calculate and save to cache - simplified version
     */
    public function calculateAndCache($tingkat, $id_desa = 'all') {
        $start_time = microtime(true);
        
        try {
            // Get filtered desa
            $all_desa = $this->queries->getAllDesa();
            if ($id_desa === 'all') {
                $filtered_desa = $all_desa;
            } else {
                $filtered_desa = array_filter($all_desa, function ($desa) use ($id_desa) {
                    return $desa['id_desa'] == $id_desa;
                });
            }
            
            // Preload data
            $this->analisis->preloadDesaData($filtered_desa);
            
            // Calculate based on tingkat
            $data = $this->calculateByTingkat($tingkat);
            
            // Save to cache
            $this->saveToCache($tingkat, $id_desa, $data);
            
            $execution_time = round((microtime(true) - $start_time) * 1000, 2);
            
            return [
                'data' => $data,
                'from_cache' => false,
                'calculation_time_ms' => $execution_time,
                'last_update' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Calculate data based on tingkat
     */
    private function calculateByTingkat($tingkat) {
        switch ($tingkat) {
            case 'tingkat1':
                return [
                    'demographic_stats' => $this->analisis->calculateDemographicStats(),
                    'economic_stats' => $this->analisis->calculateEconomicStats(),
                    'umkm_stats' => $this->analisis->calculateUMKMStats(),
                    'poverty_stats' => $this->analisis->calculatePovertyStats(),
                    'education_stats' => $this->analisis->calculateEducationStats(),
                    'capacity_per_level' => $this->analisis->calculateCapacityPerLevel(),
                    'infrastructure_stats' => $this->analisis->calculateInfrastructureStats(),
                    'spatial_analysis' => $this->analisis->calculateSpatialAnalysis()
                ];
                
            case 'tingkat2':
                return [
                    'advanced_demographics' => $this->analisis->calculateAdvancedDemographics(),
                    'economic_advanced' => $this->analisis->calculateEconomicAdvanced(),
                    'education_job_correlation' => $this->analisis->calculateEducationJobCorrelation(),
                    'income_gap' => $this->analisis->calculateIncomeGap(),
                    'non_agriculture' => $this->analisis->calculateNonAgricultureBreakdown(),
                    'integrated_analysis' => $this->analisis->calculateIntegratedAnalysis(),
                    'spatial_predictive' => $this->analisis->calculateSpatialPredictive(),
                    'income_distribution' => $this->analisis->calculateIncomeDistribution()
                ];
                
            default:
                throw new Exception("Unknown tingkat: {$tingkat}");
        }
    }
    
    /**
     * Save data to cache table
     */
    /**
     * Save data to cache table (public method)
     */
    public function saveToCache($tingkat, $id_desa, $data) {
        if ($tingkat === 'tingkat1') {
            $this->saveTingkat1Cache($id_desa, $data);
        } elseif ($tingkat === 'tingkat2') {
            $this->saveTingkat2Cache($id_desa, $data);
        }
    }
    
    /**
     * Save tingkat 1 cache
     */
    private function saveTingkat1Cache($id_desa, $data) {
        $demographic = $data['demographic_stats'] ?? [];
        $economic = $data['economic_stats'] ?? [];
        $education = $data['education_stats'] ?? [];
        $infrastructure = $data['infrastructure_stats'] ?? [];
        
        // Debug logging
        error_log("Saving tingkat1 cache for {$id_desa}: total_penduduk = " . ($demographic['total_penduduk_all'] ?? 0));
        
        $query = "INSERT INTO analisis_tingkat1_cache (
            id_desa, total_penduduk, total_laki, total_perempuan, total_kk,
            total_balita, total_anak, total_remaja, total_dewasa, total_lansia,
            agama_stats, pendidikan_stats, pernikahan_stats, pekerjaan_stats,
            mata_pencaharian_stats, total_pertanian, total_umkm, total_pasar,
            umkm_jenis_stats, fasilitas_stats, total_kapasitas, total_guru,
            kapasitas_per_level, jalan_baik, jalan_sedang, jalan_rusak,
            total_panjang_jalan, jembatan_baik, jembatan_sedang, jembatan_rusak,
            total_jembatan, updated_at
        ) VALUES (
            :id_desa, :total_penduduk, :total_laki, :total_perempuan, :total_kk,
            :total_balita, :total_anak, :total_remaja, :total_dewasa, :total_lansia,
            :agama_stats, :pendidikan_stats, :pernikahan_stats, :pekerjaan_stats,
            :mata_pencaharian_stats, :total_pertanian, :total_umkm, :total_pasar,
            :umkm_jenis_stats, :fasilitas_stats, :total_kapasitas, :total_guru,
            :kapasitas_per_level, :jalan_baik, :jalan_sedang, :jalan_rusak,
            :total_panjang_jalan, :jembatan_baik, :jembatan_sedang, :jembatan_rusak,
            :total_jembatan, NOW()
        ) ON DUPLICATE KEY UPDATE
            total_penduduk = VALUES(total_penduduk),
            total_laki = VALUES(total_laki),
            total_perempuan = VALUES(total_perempuan),
            total_kk = VALUES(total_kk),
            total_balita = VALUES(total_balita),
            total_anak = VALUES(total_anak),
            total_remaja = VALUES(total_remaja),
            total_dewasa = VALUES(total_dewasa),
            total_lansia = VALUES(total_lansia),
            agama_stats = VALUES(agama_stats),
            pendidikan_stats = VALUES(pendidikan_stats),
            pernikahan_stats = VALUES(pernikahan_stats),
            pekerjaan_stats = VALUES(pekerjaan_stats),
            mata_pencaharian_stats = VALUES(mata_pencaharian_stats),
            total_pertanian = VALUES(total_pertanian),
            total_umkm = VALUES(total_umkm),
            total_pasar = VALUES(total_pasar),
            umkm_jenis_stats = VALUES(umkm_jenis_stats),
            fasilitas_stats = VALUES(fasilitas_stats),
            total_kapasitas = VALUES(total_kapasitas),
            total_guru = VALUES(total_guru),
            kapasitas_per_level = VALUES(kapasitas_per_level),
            jalan_baik = VALUES(jalan_baik),
            jalan_sedang = VALUES(jalan_sedang),
            jalan_rusak = VALUES(jalan_rusak),
            total_panjang_jalan = VALUES(total_panjang_jalan),
            jembatan_baik = VALUES(jembatan_baik),
            jembatan_sedang = VALUES(jembatan_sedang),
            jembatan_rusak = VALUES(jembatan_rusak),
            total_jembatan = VALUES(total_jembatan),
            updated_at = NOW()";
        
        $stmt = $this->db->prepare($query);
        $result = $stmt->execute([
            ':id_desa' => $id_desa,
            ':total_penduduk' => $demographic['total_penduduk_all'] ?? 0,
            ':total_laki' => $demographic['total_laki'] ?? 0,
            ':total_perempuan' => $demographic['total_perempuan'] ?? 0,
            ':total_kk' => $demographic['total_kk'] ?? 0,
            ':total_balita' => $demographic['total_balita'] ?? 0,
            ':total_anak' => $demographic['total_anak'] ?? 0,
            ':total_remaja' => $demographic['total_remaja'] ?? 0,
            ':total_dewasa' => $demographic['total_dewasa'] ?? 0,
            ':total_lansia' => $demographic['total_lansia'] ?? 0,
            ':agama_stats' => json_encode($demographic['agama_stats'] ?? []),
            ':pendidikan_stats' => json_encode($demographic['pendidikan_stats'] ?? []),
            ':pernikahan_stats' => json_encode($demographic['pernikahan_stats'] ?? []),
            ':pekerjaan_stats' => json_encode($demographic['pekerjaan_stats'] ?? []),
            ':mata_pencaharian_stats' => json_encode($economic['mata_pencaharian_stats'] ?? []),
            ':total_pertanian' => $economic['total_pertanian'] ?? 0,
            ':total_umkm' => $economic['total_umkm'] ?? 0,
            ':total_pasar' => $economic['total_pasar'] ?? 0,
            ':umkm_jenis_stats' => json_encode($data['umkm_stats'] ?? []),
            ':fasilitas_stats' => json_encode($education['fasilitas_stats'] ?? []),
            ':total_kapasitas' => $education['total_kapasitas'] ?? 0,
            ':total_guru' => $education['total_guru'] ?? 0,
            ':kapasitas_per_level' => json_encode($data['capacity_per_level'] ?? []),
            ':jalan_baik' => $infrastructure['jalan_baik'] ?? 0,
            ':jalan_sedang' => $infrastructure['jalan_sedang'] ?? 0,
            ':jalan_rusak' => $infrastructure['jalan_rusak'] ?? 0,
            ':total_panjang_jalan' => $infrastructure['total_panjang_jalan'] ?? 0,
            ':jembatan_baik' => $infrastructure['jembatan_baik'] ?? 0,
            ':jembatan_sedang' => $infrastructure['jembatan_sedang'] ?? 0,
            ':jembatan_rusak' => $infrastructure['jembatan_rusak'] ?? 0,
            ':total_jembatan' => $infrastructure['total_jembatan'] ?? 0
        ]);
        
        if ($result) {
            error_log("Successfully saved tingkat1 cache for {$id_desa}");
        } else {
            error_log("Failed to save tingkat1 cache for {$id_desa}: " . print_r($stmt->errorInfo(), true));
        }
    }
    
    /**
     * Save tingkat 2 cache
     */
    private function saveTingkat2Cache($id_desa, $data) {
        $advanced = $data['advanced_demographics'] ?? [];
        $economic = $data['economic_advanced'] ?? [];
        
        $query = "INSERT INTO analisis_tingkat2_cache (
            id_desa, kk_perempuan_anak_sekolah, produktif_tanpa_kerja_tetap,
            total_produktif, kk_lansia_tanggungan, penduduk_non_produktif,
            penduduk_produktif, rasio_ketergantungan, persen_produktif_tanpa_kerja,
            kk_perempuan_detail, produktif_detail, lansia_detail, pendidikan_tinggi_detail,
            petani_lahan_kecil, petani_lahan_detail, komoditas_detail,
            education_job_correlation, income_gap, non_agriculture,
            integrated_analysis, spatial_predictive, income_distribution, updated_at
        ) VALUES (
            :id_desa, :kk_perempuan, :produktif_tanpa_kerja, :total_produktif,
            :kk_lansia, :non_produktif, :produktif, :rasio, :persen_produktif,
            :kk_perempuan_detail, :produktif_detail, :lansia_detail, :pendidikan_tinggi_detail,
            :petani_lahan_kecil, :petani_lahan_detail, :komoditas_detail,
            :education_job, :income_gap, :non_agriculture,
            :integrated, :spatial, :income_distribution, NOW()
        ) ON DUPLICATE KEY UPDATE
            kk_perempuan_anak_sekolah = VALUES(kk_perempuan_anak_sekolah),
            produktif_tanpa_kerja_tetap = VALUES(produktif_tanpa_kerja_tetap),
            total_produktif = VALUES(total_produktif),
            kk_lansia_tanggungan = VALUES(kk_lansia_tanggungan),
            penduduk_non_produktif = VALUES(penduduk_non_produktif),
            penduduk_produktif = VALUES(penduduk_produktif),
            rasio_ketergantungan = VALUES(rasio_ketergantungan),
            persen_produktif_tanpa_kerja = VALUES(persen_produktif_tanpa_kerja),
            kk_perempuan_detail = VALUES(kk_perempuan_detail),
            produktif_detail = VALUES(produktif_detail),
            lansia_detail = VALUES(lansia_detail),
            pendidikan_tinggi_detail = VALUES(pendidikan_tinggi_detail),
            petani_lahan_kecil = VALUES(petani_lahan_kecil),
            petani_lahan_detail = VALUES(petani_lahan_detail),
            komoditas_detail = VALUES(komoditas_detail),
            education_job_correlation = VALUES(education_job_correlation),
            income_gap = VALUES(income_gap),
            non_agriculture = VALUES(non_agriculture),
            integrated_analysis = VALUES(integrated_analysis),
            spatial_predictive = VALUES(spatial_predictive),
            income_distribution = VALUES(income_distribution),
            updated_at = NOW()";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':id_desa' => $id_desa,
            ':kk_perempuan' => $advanced['kk_perempuan_anak_sekolah'] ?? 0,
            ':produktif_tanpa_kerja' => $advanced['produktif_tanpa_kerja_tetap'] ?? 0,
            ':total_produktif' => $advanced['total_produktif'] ?? 0,
            ':kk_lansia' => $advanced['kk_lansia_tanggungan'] ?? 0,
            ':non_produktif' => $advanced['penduduk_non_produktif'] ?? 0,
            ':produktif' => $advanced['penduduk_produktif'] ?? 0,
            ':rasio' => $advanced['rasio_ketergantungan'] ?? 0,
            ':persen_produktif' => $advanced['persen_produktif_tanpa_kerja'] ?? 0,
            ':kk_perempuan_detail' => json_encode($advanced['kk_perempuan_detail'] ?? []),
            ':produktif_detail' => json_encode($advanced['produktif_detail'] ?? []),
            ':lansia_detail' => json_encode($advanced['lansia_detail'] ?? []),
            ':pendidikan_tinggi_detail' => json_encode($advanced['pendidikan_tinggi_detail'] ?? []),
            ':petani_lahan_kecil' => $economic['petani_lahan_kecil'] ?? 0,
            ':petani_lahan_detail' => json_encode($economic['petani_lahan_detail'] ?? []),
            ':komoditas_detail' => json_encode($economic['komoditas_detail'] ?? []),
            ':education_job' => json_encode($data['education_job_correlation'] ?? []),
            ':income_gap' => json_encode($data['income_gap'] ?? []),
            ':non_agriculture' => json_encode($data['non_agriculture'] ?? []),
            ':integrated' => json_encode($data['integrated_analysis'] ?? []),
            ':spatial' => json_encode($data['spatial_predictive'] ?? []),
            ':income_distribution' => json_encode($data['income_distribution'] ?? [])
        ]);
    }
    
    /**
     * Trigger background recalculation (non-blocking) - Public method
     */
    public function triggerBackgroundRecalculation($tingkat, $id_desa) {
        // Check if already calculating
        if ($this->isCalculating($tingkat, $id_desa)) {
            error_log("Already calculating for {$tingkat} - {$id_desa}, skipping trigger");
            return false;
        }
        
        // Check if trigger file already exists
        $trigger_file = sys_get_temp_dir() . '/analisis_recalc_' . $tingkat . '_' . $id_desa . '.trigger';
        if (file_exists($trigger_file)) {
            error_log("Trigger file already exists for {$tingkat} - {$id_desa}, skipping");
            return false;
        }
        
        // Create trigger file for background process
        file_put_contents($trigger_file, json_encode([
            'tingkat' => $tingkat,
            'id_desa' => $id_desa,
            'timestamp' => time()
        ]));
        
        error_log("Background recalculation triggered for {$tingkat} - {$id_desa}");
        
        // Try to trigger background processor immediately via async request
        $this->triggerBackgroundProcessor();
        
        return true;
    }
    
    /**
     * Trigger background processor via async HTTP request
     */
    private function triggerBackgroundProcessor() {
        $baseUrl = $this->getBaseUrl();
        $processorUrl = $baseUrl . 'pages/admin/analisis/background-processor.php';
        
        // Make async request to background processor
        $this->asyncRequest($processorUrl, []);
    }
    
    /**
     * Get base URL dynamically
     */
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        
        if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false) {
            return $protocol . $host . '/Pendataan-desa/';
        } else {
            return $protocol . $host . '/';
        }
    }
    
    /**
     * Make async HTTP request (non-blocking)
     */
    private function asyncRequest($url, $data) {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($data),
                'timeout' => 1,
                'ignore_errors' => true
            ]
        ]);
        @file_get_contents($url, false, $context);
    }
    
    /**
     * Check if calculation is in progress
     */
    public function isCalculating($tingkat, $id_desa) {
        // First check database status
        $table = $this->getTableName($tingkat);
        
        $query = "SELECT is_calculating FROM {$table} WHERE id_desa = :id_desa LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $dbCalculating = $result ? (bool)$result['is_calculating'] : false;
        
        // Also check if trigger file exists
        $triggerFile = sys_get_temp_dir() . '/analisis_recalc_' . $tingkat . '_' . $id_desa . '.trigger';
        $hasTriggerFile = file_exists($triggerFile);
        
        // If database says not calculating but trigger file exists, clean it up
        if (!$dbCalculating && $hasTriggerFile) {
            $fileAge = time() - filemtime($triggerFile);
            if ($fileAge > 300) { // 5 minutes old
                unlink($triggerFile);
                $hasTriggerFile = false;
                error_log("Cleaned up old trigger file for {$tingkat} - {$id_desa}");
            }
        }
        
        return $dbCalculating || $hasTriggerFile;
    }
    
    /**
     * Set calculating status
     */
    private function setCalculatingStatus($tingkat, $id_desa, $status) {
        $table = $this->getTableName($tingkat);
        
        $query = "UPDATE {$table} SET is_calculating = :status WHERE id_desa = :id_desa";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':status' => $status ? 1 : 0,
            ':id_desa' => $id_desa
        ]);
    }
    
    /**
     * Log calculation start
     */
    private function logCalculationStart($tingkat, $id_desa) {
        $query = "INSERT INTO analisis_calculation_log (
            tingkat, id_desa, status, started_at, triggered_by
        ) VALUES (
            :tingkat, :id_desa, 'running', NOW(), :triggered_by
        )";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':tingkat' => $tingkat,
            ':id_desa' => $id_desa,
            ':triggered_by' => isset($_SESSION['user_id']) ? 'user' : 'auto'
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Log calculation complete
     */
    private function logCalculationComplete($log_id, $execution_time) {
        $query = "UPDATE analisis_calculation_log SET 
            status = 'completed',
            completed_at = NOW(),
            execution_time_ms = :execution_time
        WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':id' => $log_id,
            ':execution_time' => $execution_time
        ]);
    }
    
    /**
     * Log calculation error
     */
    private function logCalculationError($log_id, $error_message) {
        $query = "UPDATE analisis_calculation_log SET 
            status = 'failed',
            completed_at = NOW(),
            error_message = :error
        WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':id' => $log_id,
            ':error' => $error_message
        ]);
    }
    
    /**
     * Create notification
     */
    private function createNotification($tingkat, $id_desa, $message, $type = 'info') {
        $query = "INSERT INTO analisis_notifications (
            user_id, tingkat, id_desa, message, type
        ) VALUES (
            :user_id, :tingkat, :id_desa, :message, :type
        )";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'] ?? null,
            ':tingkat' => $tingkat,
            ':id_desa' => $id_desa,
            ':message' => $message,
            ':type' => $type
        ]);
    }
    
    /**
     * Get table name based on tingkat
     */
    private function getTableName($tingkat) {
        switch ($tingkat) {
            case 'tingkat1':
                return 'analisis_tingkat1_cache';
            case 'tingkat2':
                return 'analisis_tingkat2_cache';
            default:
                throw new Exception("Unknown tingkat: {$tingkat}");
        }
    }
    
    /**
     * Decode JSON fields
     */
    private function decodeJsonFields($data, $tingkat) {
        $json_fields = [];
        
        if ($tingkat === 'tingkat1') {
            $json_fields = [
                'agama_stats', 'pendidikan_stats', 'pernikahan_stats', 'pekerjaan_stats',
                'mata_pencaharian_stats', 'umkm_jenis_stats', 'fasilitas_stats', 'kapasitas_per_level'
            ];
        } elseif ($tingkat === 'tingkat2') {
            $json_fields = [
                'kk_perempuan_detail', 'produktif_detail', 'lansia_detail', 'pendidikan_tinggi_detail',
                'petani_lahan_detail', 'komoditas_detail', 'education_job_correlation', 'income_gap',
                'non_agriculture', 'integrated_analysis', 'spatial_predictive', 'income_distribution'
            ];
        }
        
        foreach ($json_fields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = json_decode($data[$field], true);
            }
        }
        
        return $data;
    }
    
    /**
     * Get calculation status
     */
    public function getCalculationStatus($tingkat, $id_desa) {
        $query = "SELECT * FROM analisis_calculation_log 
                  WHERE tingkat = :tingkat AND id_desa = :id_desa 
                  ORDER BY created_at DESC LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':tingkat' => $tingkat,
            ':id_desa' => $id_desa
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get unread notifications
     */
    public function getUnreadNotifications($user_id) {
        $query = "SELECT * FROM analisis_notifications 
                  WHERE user_id = :user_id AND is_read = 0 
                  ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Mark notification as read
     */
    public function markNotificationAsRead($notification_id) {
        $query = "UPDATE analisis_notifications SET is_read = 1 WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $notification_id]);
    }
    
    /**
     * Clear specific cache entry
     */
    public function clearSpecificCache($tingkat, $id_desa) {
        $table = $this->getTableName($tingkat);
        
        $query = "DELETE FROM {$table} WHERE id_desa = :id_desa";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id_desa' => $id_desa]);
        
        error_log("Cleared cache for {$tingkat} - {$id_desa}");
    }
    
    /**
     * Clear old cache (older than 24 hours)
     */
    public function clearOldCache() {
        $tables = ['analisis_tingkat1_cache', 'analisis_tingkat2_cache'];
        
        foreach ($tables as $table) {
            $query = "DELETE FROM {$table} WHERE updated_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            $this->db->exec($query);
        }
        
        // Clear old logs (older than 7 days)
        $query = "DELETE FROM analisis_calculation_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $this->db->exec($query);
        
        // Clear old notifications (older than 30 days)
        $query = "DELETE FROM analisis_notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $this->db->exec($query);
    }
    
    /**
     * Calculate poverty stats directly from database (used when loading from cache)
     */
    private function calculatePovertyStatsFromCache($id_desa) {
        try {
            if ($id_desa === 'all') {
                $query = "SELECT COUNT(*) as total, jenis_bantuan FROM warga_miskin GROUP BY jenis_bantuan";
                $stmt = $this->db->prepare($query);
                $stmt->execute();
            } else {
                $query = "SELECT COUNT(*) as total, jenis_bantuan FROM warga_miskin WHERE id_desa = :id_desa GROUP BY jenis_bantuan";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':id_desa', $id_desa);
                $stmt->execute();
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $total = 0;
            $bantuan = [];
            foreach ($rows as $row) {
                $total += $row['total'];
                $bantuan[$row['jenis_bantuan']] = $row['total'];
            }
            return ['total' => $total, 'bantuan' => $bantuan];
        } catch (Exception $e) {
            error_log('Error calculating poverty stats from cache: ' . $e->getMessage());
            return ['total' => 0, 'bantuan' => []];
        }
    }
    
    /**
     * Helper function to format time ago
     */
    public function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'baru saja';
        if ($time < 3600) return floor($time/60) . ' menit yang lalu';
        if ($time < 86400) return floor($time/3600) . ' jam yang lalu';
        if ($time < 2592000) return floor($time/86400) . ' hari yang lalu';
        if ($time < 31536000) return floor($time/2592000) . ' bulan yang lalu';
        return floor($time/31536000) . ' tahun yang lalu';
    }
}
?>
