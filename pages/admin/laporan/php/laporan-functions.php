<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/session.php';
require_once __DIR__ . '/../../../../database/queries.php';

class LaporanFunctions {
    private $queries;
    
    public function __construct() {
        $this->queries = new Queries();
    }
    
    public function checkAuthentication() {
        if (!initSession() || !validateSession()) {
            header('Location: ' . BASE_URL . 'pages/auth/login.php');
            exit();
        }
        if ($_SESSION['user_role'] !== 'admin_kecamatan') {
            header('Location: ' . BASE_URL . 'pages/auth/login.php');
            exit();
        }
    }
    
    public function getAllDesa() {
        return $this->queries->getAllDesa();
    }
    
    public function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    public function handleAjaxRequests() {
        if (isset($_GET['endpoint'])) {
            header('Content-Type: application/json');
            
            switch ($_GET['endpoint']) {
                case 'report-templates':
                    if (isset($_GET['template_id'])) {
                        echo json_encode($this->getTemplateDetail($_GET['template_id']));
                    } else {
                        echo json_encode($this->getReportTemplates());
                    }
                    exit();
                    
                case 'delete-template':
                    echo json_encode($this->deleteTemplate());
                    exit();
            }
        }
        
        if (isset($_POST['action'])) {
            header('Content-Type: application/json');
            
            switch ($_POST['action']) {
                case 'save_template':
                    echo json_encode($this->saveTemplate());
                    exit();
                    
                case 'update_template':
                    echo json_encode($this->updateTemplate());
                    exit();
                    
                case 'reorder_template_ids':
                    echo json_encode($this->reorderTemplateIds());
                    exit();
                    
                case 'generate_pdf':
                    $this->generatePDF();
                    exit;
            }
        }
    }
    
    private function saveTemplate() {
        $template_name = $_POST['template_name'];
        $template_config = $_POST['template_config'];
        
        // Find the lowest available ID
        $query = "SELECT MIN(t1.id_template + 1) AS next_id 
                 FROM laporan_template t1 
                 LEFT JOIN laporan_template t2 ON t1.id_template + 1 = t2.id_template 
                 WHERE t2.id_template IS NULL";
        $stmt = $this->queries->db->prepare($query);
        $stmt->execute();
        $next_id = $stmt->fetchColumn();
        
        if (!$next_id) {
            $next_id = 1;
        }
        
        // Save template with specific ID
        $query = "INSERT INTO laporan_template (id_template, nama_template, konfigurasi, created_by, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->queries->db->prepare($query);
        $result = $stmt->execute([$next_id, $template_name, $template_config, $_SESSION['user_id']]);
        
        return ['success' => $result];
    }
    
    private function updateTemplate() {
        $template_id = $_POST['template_id'];
        $template_name = $_POST['template_name'];
        $template_config = $_POST['template_config'];
        
        // Update existing template
        $query = "UPDATE laporan_template SET nama_template = ?, konfigurasi = ?, updated_at = NOW() WHERE id_template = ? AND created_by = ?";
        $stmt = $this->queries->db->prepare($query);
        $result = $stmt->execute([$template_name, $template_config, $template_id, $_SESSION['user_id']]);
        
        return ['success' => $result];
    }
    
    private function reorderTemplateIds() {
        // Get all templates ordered by creation date
        $query = "SELECT id_template FROM laporan_template ORDER BY created_at ASC";
        $stmt = $this->queries->db->prepare($query);
        $stmt->execute();
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Reorder IDs starting from 1
        $new_id = 1;
        foreach ($templates as $template) {
            $query = "UPDATE laporan_template SET id_template = ? WHERE id_template = ?";
            $stmt = $this->queries->db->prepare($query);
            $stmt->execute([$new_id, $template['id_template']]);
            $new_id++;
        }
        
        // Reset AUTO_INCREMENT
        $query = "ALTER TABLE laporan_template AUTO_INCREMENT = ?";
        $stmt = $this->queries->db->prepare($query);
        $stmt->execute([$new_id]);
        
        return ['success' => true];
    }
    

    private function generatePDF() {
        $config = json_decode($_POST['config'], true);
        
        // Generate HTML content for PDF
        $html_content = $this->generateHTMLForPDF($config);
        
        // Create filename with timestamp
        $timestamp = date('Y-m-d_H-i-s');
        $filename = 'laporan_' . $timestamp . '.pdf';
        $filepath = 'assets/laporan/' . $filename;
        
        // Save HTML file temporarily (can be converted to PDF using external tools)
        $full_path = __DIR__ . '/../../../../assets/laporan/' . $filename . '.html';
        file_put_contents($full_path, $html_content);
        $file_size = filesize($full_path);
        
        // Save to database
        $selected_data_text = [];
        foreach ($config['selected_data'] as $category => $items) {
            if (!empty($items)) {
                $selected_data_text[] = ucfirst($category) . ': ' . implode(', ', $items);
            }
        }
        $sections = implode('; ', $selected_data_text);
        
        $query = "INSERT INTO laporan_riwayat (nama_laporan, file_pdf, file_size, sections, tahun_data, cakupan_desa, file_path, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'completed', ?, NOW())";
        $stmt = $this->queries->db->prepare($query);
        $stmt->execute([
            $config['nama_laporan'],
            $filepath . '.html',
            $file_size,
            $sections,
            $config['tahun'],
            $config['desa'],
            $filepath . '.html',
            $_SESSION['user_id']
        ]);
        
        // Send HTML file to browser (can be printed as PDF)
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: inline; filename="' . $filename . '.html"');
        
        echo $html_content;
    }
    
    private function generateHTMLForPDF($config) {
        // Check cache availability first
        require_once __DIR__ . '/../../analisis/php/analisis-cache-manager.php';
        $cacheManager = new AnalisisCacheManager();
        
        $cached_tingkat1 = $cacheManager->getAnalisisData('tingkat1', $config['desa']);
        $cached_tingkat2 = $cacheManager->getAnalisisData('tingkat2', $config['desa']);
        
        $cache_status = '';
        if ($cached_tingkat1['from_cache'] && $cached_tingkat2['from_cache']) {
            $cache_status = '<!-- Data diambil dari cache analisis (cepat) -->';
            error_log("Laporan PDF: Using cached data for desa {$config['desa']}");
        } else {
            $cache_status = '<!-- Data dikalkulasi ulang (lambat) - Silakan generate analisis terlebih dahulu -->';
            error_log("Laporan PDF: Calculating fresh data for desa {$config['desa']}");
        }
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($config['nama_laporan']) . '</title>
    ' . $cache_status . '
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .kop { text-align: center; border-bottom: 3px solid #000; padding-bottom: 20px; margin-bottom: 30px; }
        .kop h1 { margin: 0; font-size: 18px; font-weight: bold; }
        .kop h2 { margin: 5px 0; font-size: 16px; }
        .kop p { margin: 2px 0; font-size: 12px; }
        .title { text-align: center; margin: 30px 0; }
        .title h3 { margin: 0; font-size: 16px; font-weight: bold; text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .signature { margin-top: 50px; float: right; text-align: center; }
        .signature p { margin: 5px 0; }
        .page-break { page-break-before: always; }
        .print-controls { position: fixed; top: 10px; right: 10px; z-index: 1000; background: white; padding: 10px; border: 1px solid #ccc; }
        .print-controls button { margin: 5px; padding: 10px 15px; cursor: pointer; }
        .cache-info { position: fixed; top: 10px; left: 10px; z-index: 1000; background: #e8f5e8; padding: 8px 12px; border: 1px solid #4caf50; border-radius: 4px; font-size: 12px; }
        @media print { .print-controls, .cache-info { display: none; } }
    </style>
</head>
<body>
    <div class="cache-info">
        ' . ($cached_tingkat1['from_cache'] && $cached_tingkat2['from_cache'] ? 
            '✓ Data dari cache analisis (update: ' . date('d/m/Y H:i', strtotime($cached_tingkat1['last_update'])) . ')' : 
            '⚠ Data dikalkulasi ulang - Generate analisis untuk performa lebih cepat') . '
    </div>
    <div class="print-controls">
        <button onclick="window.print()">Print/Save as PDF</button>
        <button onclick="downloadHTML()">Download HTML</button>
    </div>
    <script>
        function downloadHTML() {
            const element = document.documentElement;
            const html = element.outerHTML;
            const blob = new Blob([html], {type: "text/html"});
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = "laporan.html";
            a.click();
            URL.revokeObjectURL(url);
        }
        setTimeout(() => {
            if (confirm("Apakah Anda ingin mencetak laporan sekarang?")) {
                window.print();
            }
        }, 1000);
    </script>';
        
        $pageCount = 0;
        
        // Process each selected data category
        foreach ($config['selected_data'] as $level => $items) {
            if (empty($items)) continue;
            
            foreach ($items as $item) {
                // Add page break for subsequent pages
                if ($pageCount > 0) {
                    $html .= '<div class="page-break"></div>';
                }
                
                // Kop Kecamatan for each page
                $html .= '
        <div class="kop">
            <h1>PEMERINTAH KABUPATEN GORONTALO</h1>
            <h2>KECAMATAN TIBAWA</h2>
            <p>Alamat: Jl. Trans Sulawesi, Tibawa, Kabupaten Gorontalo, Gorontalo</p>
            <p>Telepon: (0435) 123456 | Email: kecamatan.tibawa@gorontalokab.go.id</p>
        </div>';
                
                // Title for this specific data
                $dataTitle = $this->getDataTitle($level, $item);
                $html .= '
        <div class="title">
            <h3>' . strtoupper($dataTitle) . '</h3>
            <p>Tahun ' . $config['tahun'] . '</p>
        </div>';
                
                // Generate content for this specific item
                $html .= $this->generateSingleDataTable($level, $item, $config);
                
                // Signature for each page
                $html .= '
        <div class="signature">
            <p>Tibawa, ' . date('d F Y') . '</p>
            <p>Mengetahui,</p>
            <p>Camat Tibawa</p>
            <br><br><br>
            <p><strong>Drs. H. ABDUL RAHMAN, M.Si</strong></p>
            <p>NIP. 19650815 199203 1 004</p>
        </div>';
                
                $pageCount++;
            }
        }
        
        $html .= '
</body>
</html>';
        
        return $html;
    }
    
    private function getDataTitle($level, $item) {
        $titles = [
            'tingkat1' => [
                'kependudukan_data_umum' => 'Laporan Data Umum Penduduk',
                'kependudukan_kelompok_usia' => 'Laporan Kelompok Usia',
                'kependudukan_agama' => 'Laporan Data Agama',
                'kependudukan_tingkat_pendidikan' => 'Laporan Tingkat Pendidikan',
                'kependudukan_status_pernikahan' => 'Laporan Status Pernikahan',
                'ekonomi_data_umum' => 'Laporan Data Umum Ekonomi',
                'ekonomi_mata_pencaharian' => 'Laporan Mata Pencaharian',
                'ekonomi_umkm_jenis_usaha' => 'Laporan UMKM Berdasarkan Jenis Usaha',
                'ekonomi_warga_miskin_bantuan' => 'Laporan Warga Miskin & Bantuan Sosial',
                'pendidikan_data_umum' => 'Laporan Data Umum Pendidikan',
                'pendidikan_jenis_fasilitas' => 'Laporan Jenis Fasilitas Pendidikan',
                'pendidikan_kapasitas_per_jenjang' => 'Laporan Kapasitas Per Jenjang',
                'infrastruktur_data_umum' => 'Laporan Data Umum Infrastruktur',
                'infrastruktur_kondisi_jalan' => 'Laporan Kondisi Jalan',
                'infrastruktur_kondisi_jembatan' => 'Laporan Kondisi Jembatan',
                'infrastruktur_analisis_spasial' => 'Laporan Analisis Spasial & Geografis'
            ],
            'tingkat2' => [
                'kependudukan_kk_perempuan_anak_sekolah' => 'Laporan KK Perempuan + Anak Sekolah',
                'kependudukan_produktif_tanpa_kerja' => 'Laporan Produktif Tanpa Kerja Tetap',
                'kependudukan_kk_lansia_tanggungan' => 'Laporan KK Lansia + Tanggungan',
                'kependudukan_pendidikan_tinggi_tidak_sesuai' => 'Laporan Pendidikan Tinggi Tidak Sesuai Bidang',
                'ekonomi_petani_lahan_kecil' => 'Laporan Petani Lahan Kecil',
                'ekonomi_korelasi_pendidikan_pekerjaan' => 'Laporan Korelasi Pendidikan-Pekerjaan',
                'ekonomi_kesenjangan_antar_desa' => 'Laporan Kesenjangan Ekonomi Antar Desa',
                'ekonomi_distribusi_pendapatan' => 'Laporan Distribusi Pendapatan Desa',
                'ekonomi_rincian_non_pertanian' => 'Laporan Rincian Non-Pertanian',
                'integrasi_kk_miskin_anak_sekolah' => 'Laporan KK Miskin + Anak Sekolah',
                'integrasi_pendidikan_tinggi_miskin' => 'Laporan Pendidikan Tinggi Tapi Miskin',
                'integrasi_rasio_ketergantungan_ekonomi' => 'Laporan Rasio Ketergantungan Ekonomi',
                'integrasi_anak_tidak_sekolah_ekonomi' => 'Laporan Anak Tidak Sekolah karena Ekonomi',
                'integrasi_anak_petani_pendidikan_tinggi' => 'Laporan Anak Petani → Pendidikan Tinggi',
                'integrasi_bidang_studi_populer' => 'Laporan Bidang Studi Populer',
                'integrasi_status_setelah_lulus' => 'Laporan Status Setelah Lulus',
                'spasial_data_umum' => 'Laporan Data Umum Spasial',
                'spasial_ranking_potensi_pengembangan' => 'Laporan Ranking Potensi Pengembangan Desa'
            ],
            'tingkat3' => [
                'prediksi_penduduk_5_tahun' => 'Laporan Prediksi Penduduk 5 Tahun',
                'prediksi_data_umum' => 'Laporan Data Umum Prediksi',
                'proyeksi_kebutuhan_pembangunan' => 'Laporan Proyeksi Kebutuhan Pembangunan'
            ]
        ];
        
        return $titles[$level][$item] ?? 'Laporan Data';
    }
    
    private function generateSingleDataTable($level, $item, $config) {
        // Use cache from analysis system instead of recalculating
        require_once __DIR__ . '/../../analisis/php/analisis-cache-manager.php';
        $cacheManager = new AnalisisCacheManager();
        
        // Try to get data from cache first
        $cached_tingkat1 = $cacheManager->getAnalisisData('tingkat1', $config['desa']);
        $cached_tingkat2 = $cacheManager->getAnalisisData('tingkat2', $config['desa']);
        
        if ($cached_tingkat1['from_cache'] && $cached_tingkat2['from_cache']) {
            // Use cached data - much faster!
            $data = [
                'demographic_stats' => $cached_tingkat1['data']['demographic_stats'] ?? [],
                'economic_stats' => $cached_tingkat1['data']['economic_stats'] ?? [],
                'umkm_stats' => $cached_tingkat1['data']['umkm_stats'] ?? [],
                'poverty_stats' => $cached_tingkat1['data']['poverty_stats'] ?? [],
                'education_stats' => $cached_tingkat1['data']['education_stats'] ?? [],
                'infrastructure_stats' => $cached_tingkat1['data']['infrastructure_stats'] ?? [],
                'advanced_demographics' => $cached_tingkat2['data']['advanced_demographics'] ?? [],
                'economic_advanced' => $cached_tingkat2['data']['economic_advanced'] ?? [],
                'education_job_correlation' => $cached_tingkat2['data']['education_job_correlation'] ?? [],
                'income_gap' => $cached_tingkat2['data']['income_gap'] ?? [],
                'income_distribution' => $cached_tingkat2['data']['income_distribution'] ?? [],
                'non_agriculture' => $cached_tingkat2['data']['non_agriculture'] ?? [],
                'integrated_analysis' => $cached_tingkat2['data']['integrated_analysis'] ?? [],
                'spatial_predictive' => $cached_tingkat2['data']['spatial_predictive'] ?? []
            ];
            
            error_log("Laporan: Using cached data for {$level} - {$item} (desa: {$config['desa']})");
        } else {
            // Fallback: calculate if cache not available
            error_log("Laporan: Cache not available, calculating fresh data for {$level} - {$item}");
            
            // Include existing analysis functions
            require_once __DIR__ . '/../../analisis/php/analisis-functions.php';
            $analisisFunctions = new AnalisisFunctions();
            
            // Get desa data
            $all_desa = $this->queries->getAllDesa();
            $filtered_desa = $config['desa'] === 'all' ? $all_desa : [$this->queries->getDesaById($config['desa'])];
            
            // Preload data and calculate stats
            $analisisFunctions->preloadDesaData($filtered_desa);
            $demographic_stats = $analisisFunctions->calculateDemographicStats();
            $economic_stats = $analisisFunctions->calculateEconomicStats();
            $umkm_stats = $analisisFunctions->calculateUMKMStats();
            $poverty_stats = $analisisFunctions->calculatePovertyStats();
            $education_stats = $analisisFunctions->calculateEducationStats();
            $infrastructure_stats = $analisisFunctions->calculateInfrastructureStats();
            $advanced_demographics = $analisisFunctions->calculateAdvancedDemographics();
            $economic_advanced = $analisisFunctions->calculateEconomicAdvanced();
            $education_job_correlation = $analisisFunctions->calculateEducationJobCorrelation();
            $income_gap = $analisisFunctions->calculateIncomeGap();
            $income_distribution = $analisisFunctions->calculateIncomeDistribution();
            $non_agriculture = $analisisFunctions->calculateNonAgricultureBreakdown();
            $integrated_analysis = $analisisFunctions->calculateIntegratedAnalysis();
            $spatial_predictive = $analisisFunctions->calculateSpatialPredictive();
            
            $data = [
                'demographic_stats' => $demographic_stats,
                'economic_stats' => $economic_stats,
                'umkm_stats' => $umkm_stats,
                'poverty_stats' => $poverty_stats,
                'education_stats' => $education_stats,
                'infrastructure_stats' => $infrastructure_stats,
                'advanced_demographics' => $advanced_demographics,
                'economic_advanced' => $economic_advanced,
                'education_job_correlation' => $education_job_correlation,
                'income_gap' => $income_gap,
                'income_distribution' => $income_distribution,
                'non_agriculture' => $non_agriculture,
                'integrated_analysis' => $integrated_analysis,
                'spatial_predictive' => $spatial_predictive
            ];
        }
        
        return $this->generateTableFromAnalysisData($level, $item, $data);
    }

    
    private function generateTableFromAnalysisData($level, $item, $analysis_data) {
        $html = '';
        
        switch ($level) {
            case 'tingkat1':
                $html .= $this->generateTingkat1Table($item, $analysis_data);
                break;
            case 'tingkat2':
                $html .= $this->generateTingkat2Table($item, $analysis_data);
                break;
            case 'tingkat3':
                $html .= $this->generateTingkat3Table($item, $analysis_data);
                break;
        }
        
        return $html;
    }
    
    private function generateTingkat1Table($item, $data) {
        $html = '';
        
        switch ($item) {
            case 'kependudukan_data_umum':
                $html .= '<h4>Data Umum Penduduk</h4>';
                $html .= '<table><tr><th>Kategori</th><th>Jumlah</th><th>Persentase</th></tr>';
                $total = $data['demographic_stats']['total_penduduk_all'];
                $html .= '<tr><td>Total Penduduk</td><td>' . number_format($total) . ' jiwa</td><td>100%</td></tr>';
                $html .= '<tr><td>Laki-laki</td><td>' . number_format($data['demographic_stats']['total_laki']) . '</td><td>' . ($total > 0 ? round(($data['demographic_stats']['total_laki']/$total)*100, 1) : 0) . '%</td></tr>';
                $html .= '<tr><td>Perempuan</td><td>' . number_format($data['demographic_stats']['total_perempuan']) . '</td><td>' . ($total > 0 ? round(($data['demographic_stats']['total_perempuan']/$total)*100, 1) : 0) . '%</td></tr>';
                $html .= '<tr><td>Total KK</td><td>' . number_format($data['demographic_stats']['total_kk']) . ' KK</td><td>-</td></tr>';
                $html .= '</table><br>';
                break;
                
            case 'kependudukan_kelompok_usia':
                $html .= '<h4>Kelompok Usia</h4>';
                $html .= '<table><tr><th>Kelompok Usia</th><th>Jumlah</th><th>Persentase</th></tr>';
                $total = $data['demographic_stats']['total_penduduk_all'];
                $html .= '<tr><td>Balita (0-5 tahun)</td><td>' . number_format($data['demographic_stats']['total_balita']) . '</td><td>' . ($total > 0 ? round(($data['demographic_stats']['total_balita']/$total)*100, 1) : 0) . '%</td></tr>';
                $html .= '<tr><td>Anak (6-12 tahun)</td><td>' . number_format($data['demographic_stats']['total_anak']) . '</td><td>' . ($total > 0 ? round(($data['demographic_stats']['total_anak']/$total)*100, 1) : 0) . '%</td></tr>';
                $html .= '<tr><td>Remaja (13-17 tahun)</td><td>' . number_format($data['demographic_stats']['total_remaja']) . '</td><td>' . ($total > 0 ? round(($data['demographic_stats']['total_remaja']/$total)*100, 1) : 0) . '%</td></tr>';
                $html .= '<tr><td>Dewasa (18-64 tahun)</td><td>' . number_format($data['demographic_stats']['total_dewasa']) . '</td><td>' . ($total > 0 ? round(($data['demographic_stats']['total_dewasa']/$total)*100, 1) : 0) . '%</td></tr>';
                $html .= '<tr><td>Lansia (65+ tahun)</td><td>' . number_format($data['demographic_stats']['total_lansia']) . '</td><td>' . ($total > 0 ? round(($data['demographic_stats']['total_lansia']/$total)*100, 1) : 0) . '%</td></tr>';
                $html .= '</table><br>';
                break;
                
            case 'kependudukan_agama':
                $html .= '<h4>Data Agama</h4>';
                $html .= '<table><tr><th>Agama</th><th>Jumlah</th><th>Persentase</th></tr>';
                foreach ($data['demographic_stats']['agama_stats'] as $agama => $jumlah) {
                    $persen = $data['demographic_stats']['total_penduduk_all'] > 0 ? 
                        round(($jumlah / $data['demographic_stats']['total_penduduk_all']) * 100, 1) : 0;
                    $html .= '<tr><td>' . htmlspecialchars($agama) . '</td><td>' . number_format($jumlah) . '</td><td>' . $persen . '%</td></tr>';
                }
                $html .= '</table><br>';
                break;
                
            case 'kependudukan_tingkat_pendidikan':
                $html .= '<h4>Tingkat Pendidikan</h4>';
                $html .= '<table><tr><th>Tingkat Pendidikan</th><th>Jumlah</th><th>Persentase</th></tr>';
                foreach ($data['demographic_stats']['pendidikan_stats'] as $pendidikan => $jumlah) {
                    $persen = $data['demographic_stats']['total_penduduk_all'] > 0 ? 
                        round(($jumlah / $data['demographic_stats']['total_penduduk_all']) * 100, 1) : 0;
                    $html .= '<tr><td>' . htmlspecialchars($pendidikan) . '</td><td>' . number_format($jumlah) . '</td><td>' . $persen . '%</td></tr>';
                }
                $html .= '</table><br>';
                break;
                
            case 'kependudukan_status_pernikahan':
                $html .= '<h4>Status Pernikahan</h4>';
                $html .= '<table><tr><th>Status Pernikahan</th><th>Jumlah</th><th>Persentase</th></tr>';
                foreach ($data['demographic_stats']['pernikahan_stats'] as $status => $jumlah) {
                    $persen = $data['demographic_stats']['total_penduduk_all'] > 0 ? 
                        round(($jumlah / $data['demographic_stats']['total_penduduk_all']) * 100, 1) : 0;
                    $html .= '<tr><td>' . htmlspecialchars($status) . '</td><td>' . number_format($jumlah) . '</td><td>' . $persen . '%</td></tr>';
                }
                $html .= '</table><br>';
                break;
                
            case 'ekonomi_data_umum':
                $html .= '<h4>Data Umum Ekonomi</h4>';
                $html .= '<table><tr><th>Kategori</th><th>Jumlah</th></tr>';
                $html .= '<tr><td>Total Mata Pencaharian</td><td>' . count($data['economic_stats']['mata_pencaharian_stats']) . ' jenis</td></tr>';
                $html .= '<tr><td>UMKM</td><td>' . number_format($data['economic_stats']['total_umkm']) . ' unit</td></tr>';
                $html .= '<tr><td>Pertanian</td><td>' . number_format($data['economic_stats']['total_pertanian']) . ' unit</td></tr>';
                $html .= '<tr><td>Pasar</td><td>' . number_format($data['economic_stats']['total_pasar']) . ' unit</td></tr>';
                $html .= '</table><br>';
                break;
                
            case 'ekonomi_mata_pencaharian':
                $html .= '<h4>Mata Pencaharian</h4>';
                $html .= '<table><tr><th>Jenis Pekerjaan</th><th>Jumlah</th><th>Persentase</th></tr>';
                $total_mp = array_sum($data['economic_stats']['mata_pencaharian_stats']);
                foreach ($data['economic_stats']['mata_pencaharian_stats'] as $pekerjaan => $jumlah) {
                    $persen = $total_mp > 0 ? round(($jumlah / $total_mp) * 100, 1) : 0;
                    $html .= '<tr><td>' . htmlspecialchars($pekerjaan) . '</td><td>' . number_format($jumlah) . '</td><td>' . $persen . '%</td></tr>';
                }
                $html .= '</table><br>';
                break;
                
            case 'pendidikan_data_umum':
                $html .= '<h4>Data Umum Pendidikan</h4>';
                $html .= '<table><tr><th>Kategori</th><th>Jumlah</th></tr>';
                $html .= '<tr><td>Total Fasilitas</td><td>' . number_format(array_sum($data['education_stats']['fasilitas_stats'])) . ' unit</td></tr>';
                $html .= '<tr><td>Total Kapasitas</td><td>' . number_format($data['education_stats']['total_kapasitas']) . ' siswa</td></tr>';
                $html .= '<tr><td>Total Guru</td><td>' . number_format($data['education_stats']['total_guru']) . ' orang</td></tr>';
                $html .= '<tr><td>Rasio Guru:Siswa</td><td>1:' . ($data['education_stats']['total_guru'] > 0 ? round($data['education_stats']['total_kapasitas'] / $data['education_stats']['total_guru']) : 0) . '</td></tr>';
                $html .= '</table><br>';
                break;
                
            case 'infrastruktur_data_umum':
                $total_jalan_unit = $data['infrastructure_stats']['jalan_baik'] + $data['infrastructure_stats']['jalan_sedang'] + $data['infrastructure_stats']['jalan_rusak'];
                $html .= '<h4>Data Umum Infrastruktur</h4>';
                $html .= '<table><tr><th>Kategori</th><th>Jumlah</th></tr>';
                $html .= '<tr><td>Total Jalan</td><td>' . number_format($total_jalan_unit) . ' unit</td></tr>';
                $html .= '<tr><td>Panjang Jalan</td><td>' . number_format($data['infrastructure_stats']['total_panjang_jalan'], 1) . ' km</td></tr>';
                $html .= '<tr><td>Total Jembatan</td><td>' . number_format($data['infrastructure_stats']['total_jembatan']) . ' unit</td></tr>';
                $html .= '<tr><td>Kondisi Baik</td><td>' . (($total_jalan_unit + $data['infrastructure_stats']['total_jembatan']) > 0 ? round((($data['infrastructure_stats']['jalan_baik'] + $data['infrastructure_stats']['jembatan_baik']) / ($total_jalan_unit + $data['infrastructure_stats']['total_jembatan'])) * 100, 1) : 0) . '%</td></tr>';
                $html .= '</table><br>';
                break;
                
            case 'pendidikan_jenis_fasilitas':
                $html .= '<h4>Jenis Fasilitas</h4>';
                $html .= '<table><tr><th>Jenis Fasilitas</th><th>Jumlah</th></tr>';
                if (!empty($data['education_stats']['fasilitas_stats'])) {
                    foreach ($data['education_stats']['fasilitas_stats'] as $jenis => $jumlah) {
                        $html .= '<tr><td>' . htmlspecialchars($jenis) . '</td><td>' . number_format($jumlah) . ' unit</td></tr>';
                    }
                } else {
                    $html .= '<tr><td colspan="2">Tidak ada data fasilitas</td></tr>';
                }
                $html .= '</table><br>';
                break;
                
            case 'pendidikan_kapasitas_per_jenjang':
                $html .= '<h4>Kapasitas Per Jenjang</h4>';
                $html .= '<table><tr><th>Jenjang</th><th>Kapasitas</th></tr>';
                // Hitung capacity_per_level dari education_stats
                $capacity_per_level = [];
                if (!empty($data['education_stats']['fasilitas_stats'])) {
                    foreach ($data['education_stats']['fasilitas_stats'] as $jenis => $jumlah) {
                        $capacity_per_level[$jenis] = $jumlah * 30; // Estimasi kapasitas per fasilitas
                    }
                }
                if (!empty($capacity_per_level)) {
                    foreach ($capacity_per_level as $jenis => $kapasitas) {
                        $html .= '<tr><td>' . htmlspecialchars($jenis) . '</td><td>' . number_format($kapasitas) . ' siswa</td></tr>';
                    }
                } else {
                    $html .= '<tr><td colspan="2">Tidak ada data kapasitas</td></tr>';
                }
                $html .= '</table><br>';
                break;
                
            case 'infrastruktur_kondisi_jalan':
                $html .= '<h4>Kondisi Jalan</h4>';
                $html .= '<table><tr><th>Kondisi</th><th>Jumlah</th><th>Persentase</th></tr>';
                $total_jalan_unit = $data['infrastructure_stats']['jalan_baik'] + $data['infrastructure_stats']['jalan_sedang'] + $data['infrastructure_stats']['jalan_rusak'];
                $html .= '<tr><td>Baik</td><td>' . number_format($data['infrastructure_stats']['jalan_baik']) . '</td><td>' . ($total_jalan_unit > 0 ? round(($data['infrastructure_stats']['jalan_baik'] / $total_jalan_unit) * 100, 1) : 0) . '%</td></tr>';
                $html .= '<tr><td>Sedang</td><td>' . number_format($data['infrastructure_stats']['jalan_sedang']) . '</td><td>' . ($total_jalan_unit > 0 ? round(($data['infrastructure_stats']['jalan_sedang'] / $total_jalan_unit) * 100, 1) : 0) . '%</td></tr>';
                $html .= '<tr><td>Rusak</td><td>' . number_format($data['infrastructure_stats']['jalan_rusak']) . '</td><td>' . ($total_jalan_unit > 0 ? round(($data['infrastructure_stats']['jalan_rusak'] / $total_jalan_unit) * 100, 1) : 0) . '%</td></tr>';
                $html .= '</table><br>';
                break;
                
            case 'infrastruktur_kondisi_jembatan':
                $html .= '<h4>Kondisi Jembatan</h4>';
                $html .= '<table><tr><th>Kondisi</th><th>Jumlah</th><th>Persentase</th></tr>';
                $total_jembatan = $data['infrastructure_stats']['total_jembatan'];
                $html .= '<tr><td>Baik</td><td>' . number_format($data['infrastructure_stats']['jembatan_baik']) . '</td><td>' . ($total_jembatan > 0 ? round(($data['infrastructure_stats']['jembatan_baik'] / $total_jembatan) * 100, 1) : 0) . '%</td></tr>';
                $html .= '<tr><td>Sedang</td><td>' . number_format($data['infrastructure_stats']['jembatan_sedang']) . '</td><td>' . ($total_jembatan > 0 ? round(($data['infrastructure_stats']['jembatan_sedang'] / $total_jembatan) * 100, 1) : 0) . '%</td></tr>';
                $html .= '<tr><td>Rusak</td><td>' . number_format($data['infrastructure_stats']['jembatan_rusak']) . '</td><td>' . ($total_jembatan > 0 ? round(($data['infrastructure_stats']['jembatan_rusak'] / $total_jembatan) * 100, 1) : 0) . '%</td></tr>';
                $html .= '</table><br>';
                break;
                
            case 'infrastruktur_analisis_spasial':
                $html .= '<h4>Analisis Spasial & Geografis</h4>';
                $html .= '<table><tr><th>Indikator</th><th>Nilai</th></tr>';
                // Hitung spatial analysis dari infrastructure_stats
                $desa_count = count($data['infrastructure_stats']) > 0 ? 16 : 0; // Estimasi jumlah desa
                $rata_kepadatan = $desa_count > 0 ? round($data['demographic_stats']['total_penduduk_all'] / $desa_count, 1) : 0;
                $total_fasilitas = array_sum($data['education_stats']['fasilitas_stats'] ?? []);
                $html .= '<tr><td>Rata-rata Kepadatan Penduduk</td><td>' . $rata_kepadatan . ' jiwa/km²</td></tr>';
                $html .= '<tr><td>Total Fasilitas Umum</td><td>' . number_format($total_fasilitas) . ' unit</td></tr>';
                $html .= '<tr><td>Rata-rata Fasilitas per Desa</td><td>' . ($desa_count > 0 ? round($total_fasilitas / $desa_count, 1) : 0) . ' unit</td></tr>';
                $html .= '</table><br>';
                break;
                
            case 'ekonomi_umkm_jenis_usaha':
                $html .= '<h4>UMKM Berdasarkan Jenis Usaha</h4>';
                $html .= '<table><tr><th>Jenis Usaha</th><th>Jumlah</th></tr>';
                if (!empty($data['umkm_stats'])) {
                    foreach ($data['umkm_stats'] as $jenis => $jumlah) {
                        $html .= '<tr><td>' . htmlspecialchars($jenis) . '</td><td>' . number_format($jumlah) . ' unit</td></tr>';
                    }
                } else {
                    $html .= '<tr><td colspan="2">Tidak ada data UMKM</td></tr>';
                }
                $html .= '</table><br>';
                break;
                
            case 'ekonomi_warga_miskin_bantuan':
                $html .= '<h4>Warga Miskin & Bantuan Sosial</h4>';
                $html .= '<table><tr><th>Kategori</th><th>Jumlah</th></tr>';
                if (!empty($data['poverty_stats'])) {
                    $html .= '<tr><td>Total Warga Miskin</td><td>' . number_format($data['poverty_stats']['total']) . ' orang</td></tr>';
                    if (!empty($data['poverty_stats']['bantuan'])) {
                        foreach ($data['poverty_stats']['bantuan'] as $jenis => $jumlah) {
                            $html .= '<tr><td>' . htmlspecialchars($jenis) . '</td><td>' . number_format($jumlah) . ' penerima</td></tr>';
                        }
                    }
                } else {
                    $html .= '<tr><td colspan="2">Tidak ada data warga miskin</td></tr>';
                }
                $html .= '</table><br>';
                break;
        }
        
        return $html;
    }
    
    private function generateTingkat2Table($item, $data) {
        $html = '';
        
        switch ($item) {
            case 'kependudukan_produktif_tanpa_kerja':
                $html .= '<h4>Data Produktif Tanpa Kerja Tetap</h4>';
                $html .= '<table><tr><th>Kategori</th><th>Jumlah</th></tr>';
                $html .= '<tr><td>Persentase Produktif Tanpa Kerja</td><td>' . $data['advanced_demographics']['persen_produktif_tanpa_kerja'] . '%</td></tr>';
                if (!empty($data['advanced_demographics']['produktif_detail'])) {
                    $html .= '<tr><td colspan="2"><strong>Detail Berdasarkan Pendidikan:</strong></td></tr>';
                    foreach ($data['advanced_demographics']['produktif_detail'] as $pendidikan => $jumlah) {
                        if ($jumlah > 0) {
                            $html .= '<tr><td>' . ucfirst($pendidikan) . '</td><td>' . number_format($jumlah) . ' orang</td></tr>';
                        }
                    }
                }
                $html .= '</table><br>';
                break;
                
            case 'ekonomi_petani_lahan_kecil':
                $html .= '<h4>Data Petani Lahan < 0.5 Ha</h4>';
                $html .= '<table><tr><th>Kategori</th><th>Jumlah</th></tr>';
                $html .= '<tr><td>Total Petani Lahan Kecil</td><td>' . number_format($data['economic_advanced']['petani_lahan_kecil']) . ' orang</td></tr>';
                if (!empty($data['economic_advanced']['petani_lahan_detail'])) {
                    foreach ($data['economic_advanced']['petani_lahan_detail'] as $kategori => $jumlah) {
                        if ($jumlah > 0) {
                            $html .= '<tr><td>' . ucwords(str_replace('_', ' ', $kategori)) . '</td><td>' . number_format($jumlah) . '</td></tr>';
                        }
                    }
                }
                $html .= '</table><br>';
                break;
                
            case 'kependudukan_kk_perempuan_anak_sekolah':
                $html .= '<h4>Data KK Perempuan + Anak Sekolah</h4>';
                $html .= '<table><tr><th>Kategori</th><th>Jumlah</th></tr>';
                $html .= '<tr><td>Total KK Perempuan + Anak Sekolah</td><td>' . number_format($data['advanced_demographics']['kk_perempuan_anak_sekolah']) . ' KK</td></tr>';
                if (!empty($data['advanced_demographics']['kk_perempuan_detail'])) {
                    $html .= '<tr><td colspan="2"><strong>Detail Berdasarkan Usia Anak:</strong></td></tr>';
                    foreach ($data['advanced_demographics']['kk_perempuan_detail'] as $kategori => $jumlah) {
                        if ($jumlah > 0) {
                            $html .= '<tr><td>' . ucwords(str_replace('_', ' ', $kategori)) . '</td><td>' . number_format($jumlah) . '</td></tr>';
                        }
                    }
                }
                $html .= '</table><br>';
                break;
                
            case 'kependudukan_kk_lansia_tanggungan':
                $html .= '<h4>Data KK Lansia + Tanggungan</h4>';
                $html .= '<table><tr><th>Kategori</th><th>Jumlah</th></tr>';
                $html .= '<tr><td>Total KK Lansia + Tanggungan</td><td>' . number_format($data['advanced_demographics']['kk_lansia_tanggungan']) . ' KK</td></tr>';
                if (!empty($data['advanced_demographics']['lansia_detail'])) {
                    $html .= '<tr><td colspan="2"><strong>Detail Berdasarkan Umur:</strong></td></tr>';
                    foreach ($data['advanced_demographics']['lansia_detail'] as $kategori => $jumlah) {
                        if ($jumlah > 0) {
                            $html .= '<tr><td>' . ucwords(str_replace('_', ' ', $kategori)) . '</td><td>' . number_format($jumlah) . ' KK</td></tr>';
                        }
                    }
                }
                $html .= '</table><br>';
                break;
                
            case 'kependudukan_rasio_ketergantungan':
                $html .= '<h4>Data Rasio Ketergantungan</h4>';
                $html .= '<table><tr><th>Kategori</th><th>Nilai</th></tr>';
                $html .= '<tr><td>Rasio Ketergantungan</td><td>' . $data['advanced_demographics']['rasio_ketergantungan'] . '%</td></tr>';
                $html .= '</table><br>';
                break;
                
            case 'kependudukan_pendidikan_tinggi_tidak_sesuai':
                $html .= '<h4>Detail Pendidikan Tinggi Tidak Sesuai Bidang</h4>';
                $html .= '<table><tr><th>Jenjang Pendidikan</th><th>Jumlah</th></tr>';
                if (!empty($data['advanced_demographics']['pendidikan_tinggi_detail'])) {
                    foreach ($data['advanced_demographics']['pendidikan_tinggi_detail'] as $jenjang => $jumlah) {
                        if ($jumlah > 0) {
                            $html .= '<tr><td>' . strtoupper($jenjang) . '</td><td>' . number_format($jumlah) . ' orang</td></tr>';
                        }
                    }
                } else {
                    $html .= '<tr><td colspan="2">Tidak ada data</td></tr>';
                }
                $html .= '</table><br>';
                break;
                
            case 'ekonomi_korelasi_pendidikan_pekerjaan':
                $html .= '<h4>Korelasi Pendidikan-Pekerjaan</h4>';
                $html .= '<table><tr><th>Tingkat Pendidikan</th><th>Rata-rata Pendapatan</th><th>Pertanian</th><th>Non-Pertanian</th></tr>';
                if (!empty($data['education_job_correlation'])) {
                    foreach ($data['education_job_correlation'] as $tingkat => $info) {
                        if ($info['jumlah'] > 0) {
                            $rata_pendapatan = round($info['total_pendapatan'] / $info['jumlah']);
                            $persen_pertanian = round(($info['pertanian'] / $info['jumlah']) * 100, 1);
                            $persen_non_pertanian = round(($info['non_pertanian'] / $info['jumlah']) * 100, 1);
                            $html .= '<tr><td>' . $tingkat . '</td><td>Rp ' . number_format($rata_pendapatan) . '</td><td>' . $persen_pertanian . '%</td><td>' . $persen_non_pertanian . '%</td></tr>';
                        }
                    }
                } else {
                    $html .= '<tr><td colspan="4">Tidak ada data</td></tr>';
                }
                $html .= '</table><br>';
                break;
                
            case 'ekonomi_kesenjangan_antar_desa':
                $html .= '<h4>Kesenjangan Ekonomi Antar Desa</h4>';
                $html .= '<table><tr><th>Kategori</th><th>Nilai</th></tr>';
                if (!empty($data['income_gap'])) {
                    $html .= '<tr><td>Desa Tertinggi (' . $data['income_gap']['desa_tertinggi'] . ')</td><td>Rp ' . number_format($data['income_gap']['pendapatan_tertinggi']) . '</td></tr>';
                    $html .= '<tr><td>Desa Terendah (' . $data['income_gap']['desa_terendah'] . ')</td><td>Rp ' . number_format($data['income_gap']['pendapatan_terendah']) . '</td></tr>';
                    $html .= '<tr><td>Selisih Pendapatan</td><td>Rp ' . number_format($data['income_gap']['selisih']) . ' (' . $data['income_gap']['persen_selisih'] . '%)</td></tr>';
                } else {
                    $html .= '<tr><td colspan="2">Tidak ada data</td></tr>';
                }
                $html .= '</table><br>';
                break;
                
            case 'ekonomi_distribusi_pendapatan':
                $html .= '<h4>Distribusi Pendapatan Desa per bulan</h4>';
                $html .= '<table><tr><th>Sektor</th><th>Pendapatan</th><th>Persentase</th></tr>';
                if (!empty($data['income_distribution'])) {
                    $total = $data['income_distribution']['total_pendapatan_real'];
                    $html .= '<tr><td>Sektor Pertanian</td><td>Rp ' . number_format($data['income_distribution']['pendapatan_pertanian']) . '</td><td>' . ($total > 0 ? round(($data['income_distribution']['pendapatan_pertanian'] / $total) * 100, 1) : 0) . '%</td></tr>';
                    $html .= '<tr><td>Sektor Non-Pertanian</td><td>Rp ' . number_format($data['income_distribution']['pendapatan_non_pertanian']) . '</td><td>' . ($total > 0 ? round(($data['income_distribution']['pendapatan_non_pertanian'] / $total) * 100, 1) : 0) . '%</td></tr>';
                    $html .= '<tr><td>Total Pendapatan Desa</td><td>Rp ' . number_format($total) . '</td><td>100%</td></tr>';
                } else {
                    $html .= '<tr><td colspan="3">Tidak ada data</td></tr>';
                }
                $html .= '</table><br>';
                break;
                
            case 'ekonomi_rincian_non_pertanian':
                $html .= '<h4>Rincian Non-Pertanian</h4>';
                $html .= '<table><tr><th>Sektor</th><th>Persentase</th></tr>';
                if (!empty($data['non_agriculture']) && $data['non_agriculture']['total'] > 0) {
                    foreach ($data['non_agriculture']['detail'] as $sektor => $jumlah) {
                        $persen = round(($jumlah / $data['non_agriculture']['total']) * 100, 1);
                        $html .= '<tr><td>' . $sektor . '</td><td>' . $persen . '%</td></tr>';
                    }
                } else {
                    $html .= '<tr><td colspan="2">Tidak ada data</td></tr>';
                }
                $html .= '</table><br>';
                break;
                
            case 'integrasi_kk_miskin_anak_sekolah':
                $html .= '<h4>KK Miskin + Anak Sekolah</h4>';
                $html .= '<table><tr><th>Jenjang</th><th>Jumlah</th></tr>';
                if (!empty($data['integrated_analysis']['kk_miskin_jenjang'])) {
                    foreach ($data['integrated_analysis']['kk_miskin_jenjang'] as $jenjang => $jumlah) {
                        $html .= '<tr><td>' . $jenjang . '</td><td>' . number_format($jumlah) . '</td></tr>';
                    }
                } else {
                    $html .= '<tr><td colspan="2">Tidak ada data</td></tr>';
                }
                $html .= '</table><br>';
                break;
                
            case 'integrasi_pendidikan_tinggi_miskin':
                $html .= '<h4>Pendidikan Tinggi Tapi Miskin</h4>';
                $html .= '<table><tr><th>Kategori</th><th>Jumlah</th></tr>';
                $html .= '<tr><td>Total Pendidikan Tinggi Tapi Miskin</td><td>' . number_format($data['integrated_analysis']['pendidikan_tinggi_miskin']) . ' orang</td></tr>';
                $html .= '</table><br>';
                break;
                
            case 'integrasi_rasio_ketergantungan_ekonomi':
                $html .= '<h4>Rasio Ketergantungan Ekonomi</h4>';
                $html .= '<table><tr><th>Kategori</th><th>Jumlah</th></tr>';
                if (!empty($data['integrated_analysis']['rasio_ketergantungan'])) {
                    foreach ($data['integrated_analysis']['rasio_ketergantungan'] as $kategori => $jumlah) {
                        $html .= '<tr><td>' . $kategori . ' tanggungan</td><td>' . number_format($jumlah) . '</td></tr>';
                    }
                } else {
                    $html .= '<tr><td colspan="2">Tidak ada data</td></tr>';
                }
                $html .= '</table><br>';
                break;
                
            case 'integrasi_anak_tidak_sekolah_ekonomi':
                $html .= '<h4>Anak Tidak Sekolah karena Ekonomi</h4>';
                $html .= '<table><tr><th>Tingkat Ekonomi</th><th>Jumlah</th></tr>';
                if (!empty($data['integrated_analysis']['anak_tidak_sekolah_ekonomi'])) {
                    foreach ($data['integrated_analysis']['anak_tidak_sekolah_ekonomi'] as $tingkat => $jumlah) {
                        $html .= '<tr><td>' . ucfirst($tingkat) . '</td><td>' . number_format($jumlah) . '</td></tr>';
                    }
                } else {
                    $html .= '<tr><td colspan="2">Tidak ada data</td></tr>';
                }
                $html .= '</table><br>';
                break;
                
            case 'integrasi_anak_petani_pendidikan_tinggi':
                $html .= '<h4>Anak Petani → Pendidikan Tinggi</h4>';
                $html .= '<table><tr><th>Jenjang</th><th>Jumlah</th></tr>';
                if (!empty($data['integrated_analysis']['anak_petani_pendidikan'])) {
                    foreach ($data['integrated_analysis']['anak_petani_pendidikan'] as $jenjang => $jumlah) {
                        $html .= '<tr><td>' . $jenjang . '</td><td>' . number_format($jumlah) . '</td></tr>';
                    }
                } else {
                    $html .= '<tr><td colspan="2">Tidak ada data</td></tr>';
                }
                $html .= '</table><br>';
                break;
                
            case 'integrasi_bidang_studi_populer':
                $html .= '<h4>Bidang Studi Populer</h4>';
                $html .= '<table><tr><th>Bidang Studi</th><th>Jumlah</th></tr>';
                if (!empty($data['integrated_analysis']['bidang_studi'])) {
                    foreach ($data['integrated_analysis']['bidang_studi'] as $bidang => $jumlah) {
                        $html .= '<tr><td>' . ucfirst($bidang) . '</td><td>' . number_format($jumlah) . '</td></tr>';
                    }
                } else {
                    $html .= '<tr><td colspan="2">Tidak ada data</td></tr>';
                }
                $html .= '</table><br>';
                break;
                
            case 'integrasi_status_setelah_lulus':
                $html .= '<h4>Status Setelah Lulus</h4>';
                $html .= '<table><tr><th>Status</th><th>Jumlah</th></tr>';
                if (!empty($data['integrated_analysis']['status_lulus'])) {
                    foreach ($data['integrated_analysis']['status_lulus'] as $status => $jumlah) {
                        $html .= '<tr><td>' . ucfirst($status) . '</td><td>' . number_format($jumlah) . '</td></tr>';
                    }
                } else {
                    $html .= '<tr><td colspan="2">Tidak ada data</td></tr>';
                }
                $html .= '</table><br>';
                break;
                
            case 'spasial_data_umum':
                $html .= '<h4>Data Umum Spasial</h4>';
                $html .= '<table><tr><th>Indikator</th><th>Nilai</th></tr>';
                if (!empty($data['spatial_predictive'])) {
                    $html .= '<tr><td>Desa Padat Minim Fasilitas</td><td>' . number_format($data['spatial_predictive']['desa_padat_minim_fasilitas']) . ' desa</td></tr>';
                    $html .= '<tr><td>Indeks Aksesibilitas Rata-rata</td><td>' . $data['spatial_predictive']['indeks_aksesibilitas_rata'] . '%</td></tr>';
                    $html .= '<tr><td>Prioritas Pembangunan</td><td>' . $data['spatial_predictive']['desa_padat_minim_fasilitas'] . ' area</td></tr>';
                } else {
                    $html .= '<tr><td colspan="2">Tidak ada data</td></tr>';
                }
                $html .= '</table><br>';
                break;
                
            case 'spasial_ranking_potensi_pengembangan':
                $html .= '<h4>Ranking Potensi Pengembangan Desa</h4>';
                $html .= '<table><tr><th>Ranking</th><th>Nama Desa</th><th>Kepadatan</th><th>Aksesibilitas</th></tr>';
                if (!empty($data['spatial_predictive']['potensi_pengembangan'])) {
                    foreach ($data['spatial_predictive']['potensi_pengembangan'] as $index => $desa_potensi) {
                        $html .= '<tr><td>' . ($index + 1) . '</td><td>' . $desa_potensi['nama_desa'] . '</td><td>' . $desa_potensi['kepadatan'] . '/km²</td><td>' . $desa_potensi['aksesibilitas'] . '%</td></tr>';
                    }
                } else {
                    $html .= '<tr><td colspan="4">Tidak ada data</td></tr>';
                }
                $html .= '</table><br>';
                break;
        }
        
        return $html;
    }
    
    private function generateTingkat3Table($item, $data) {
        $html = '';
        
        switch ($item) {
            case 'prediksi_penduduk_5_tahun':
                // Get prediction data from database
                $query = "SELECT pp.*, d.nama_desa FROM prediksi_penduduk pp 
                         JOIN desa d ON pp.id_desa = d.id_desa 
                         WHERE pp.tahun_prediksi = ? 
                         ORDER BY d.nama_desa";
                $stmt = $this->queries->db->prepare($query);
                $stmt->execute([date('Y') + 5]);
                $predictions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $html .= '<h4>Prediksi Penduduk 5 Tahun ke Depan</h4>';
                $html .= '<table><tr><th>Desa</th><th>Tahun</th><th>Total Penduduk</th><th>Growth Rate</th></tr>';
                
                foreach ($predictions as $pred) {
                    $html .= '<tr>';
                    $html .= '<td>' . htmlspecialchars($pred['nama_desa']) . '</td>';
                    $html .= '<td>' . $pred['tahun_prediksi'] . '</td>';
                    $html .= '<td>' . number_format($pred['total_penduduk_prediksi']) . '</td>';
                    $html .= '<td>' . $pred['growth_rate'] . '%</td>';
                    $html .= '</tr>';
                }
                $html .= '</table><br>';
                break;
                
            case 'prediksi_data_umum':
                // Get prediction statistics
                $query = "SELECT pp.*, d.nama_desa FROM prediksi_penduduk pp 
                         JOIN desa d ON pp.id_desa = d.id_desa 
                         WHERE pp.tahun_prediksi = ?";
                $stmt = $this->queries->db->prepare($query);
                $stmt->execute([date('Y') + 5]);
                $predictions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $stats = $this->calculatePredictionStats($predictions);
                
                $html .= '<h4>Data Umum Prediksi</h4>';
                $html .= '<table><tr><th>Indikator</th><th>Nilai</th></tr>';
                $html .= '<tr><td>Rata-rata Tingkat Pertumbuhan</td><td>' . $stats['avgGrowthRate'] . '% per tahun</td></tr>';
                $html .= '<tr><td>Tingkat Kepercayaan</td><td>' . $stats['avgConfidence'] . '%</td></tr>';
                $html .= '<tr><td>Metode Prediksi</td><td>Linear Regression</td></tr>';
                $html .= '<tr><td>Prediksi Total Penduduk ' . (date('Y') + 5) . '</td><td>' . number_format($stats['totalPredicted2029']) . ' jiwa</td></tr>';
                $html .= '</table><br>';
                break;
                
            case 'proyeksi_kebutuhan_pembangunan':
                // Calculate development needs projection
                $all_desa = $this->queries->getAllDesa();
                $filtered_desa = [$this->queries->getDesaById(1)]; // Default to first desa or adjust as needed
                $stats = $this->calculateProyeksiStats($this->queries, $filtered_desa);
                
                $html .= '<h4>Proyeksi Kebutuhan Pembangunan</h4>';
                $html .= '<table><tr><th>Sektor</th><th>Kebutuhan</th></tr>';
                
                // Fasilitas Pendidikan
                $kebutuhan_pendidikan = $stats['rasio_siswa_fasilitas'] > 50 ? 'Perlu penambahan fasilitas' : 'Cukup';
                $html .= '<tr><td>Fasilitas Pendidikan</td><td>' . $kebutuhan_pendidikan . '</td></tr>';
                
                // Infrastruktur Jalan
                $html .= '<tr><td>Infrastruktur Jalan</td><td>' . round($stats['total_jalan_rusak'], 1) . ' km perlu perbaikan</td></tr>';
                
                // Dampak Program
                $dampak_kesejahteraan = $stats['total_penduduk'] > 0 ? round((($stats['total_fasilitas'] + 1) / $stats['total_penduduk']) * 1000, 1) : 0;
                $html .= '<tr><td>Dampak Program</td><td>Peningkatan kesejahteraan +' . $dampak_kesejahteraan . '%</td></tr>';
                
                $html .= '</table><br>';
                break;
        }
        
        return $html;
    }

    
    public function getTemplates() {
        $query = "SELECT * FROM laporan_template ORDER BY created_at DESC";
        $stmt = $this->queries->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getReportTemplates() {
        try {
            $customTemplates = $this->getTemplates();
            
            $defaultTemplates = [
                [
                    'id' => 'comprehensive',
                    'name' => 'Laporan Komprehensif',
                    'description' => 'Laporan lengkap semua data desa',
                    'sections' => ['kependudukan', 'ekonomi', 'pendidikan', 'infrastruktur'],
                    'type' => 'default'
                ],
                [
                    'id' => 'demographic',
                    'name' => 'Laporan Kependudukan',
                    'description' => 'Laporan khusus data penduduk dan keluarga',
                    'sections' => ['kependudukan'],
                    'type' => 'default'
                ]
            ];
            
            foreach ($customTemplates as &$template) {
                $template['type'] = 'custom';
            }
            
            return $this->response(true, 'Success', [
                'default' => $defaultTemplates,
                'custom' => $customTemplates
            ]);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    public function getTemplateDetail($template_id) {
        try {
            if (!$template_id) {
                return $this->response(false, 'Template ID required');
            }
            
            $query = "SELECT * FROM laporan_template WHERE id_template = ?";
            $stmt = $this->queries->db->prepare($query);
            $stmt->execute([$template_id]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($template) {
                return $this->response(true, 'Success', ['template' => $template]);
            } else {
                return $this->response(false, 'Template not found');
            }
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    public function deleteTemplate() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $template_id = $input['template_id'] ?? $input['id'] ?? $_GET['template_id'] ?? null;
            
            if (!$template_id) {
                return $this->response(false, 'Template ID required');
            }
            
            $query = "DELETE FROM laporan_template WHERE id_template = ?";
            $stmt = $this->queries->db->prepare($query);
            $result = $stmt->execute([$template_id]);
            
            if ($result && $stmt->rowCount() > 0) {
                return $this->response(true, 'Template berhasil dihapus');
            } else {
                return $this->response(false, 'Template not found');
            }
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function response($success, $message, $data = null, $code = 200) {
        http_response_code($code);
        return [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ];
    }
    
    public function getRiwayat() {
        $query = "SELECT * FROM laporan_riwayat ORDER BY created_at DESC";
        $stmt = $this->queries->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

    public function calculatePredictionStats($predictions) {
        $totalPredicted2029 = 0;
        $avgGrowthRate = 0;
        $avgConfidence = 0;
        $predictionCount = 0;
        
        foreach ($predictions as $pred) {
            if ($pred['tahun_prediksi'] == date('Y') + 5) {
                $totalPredicted2029 += $pred['total_penduduk_prediksi'] ?? 0;
            }
            $avgGrowthRate += $pred['growth_rate'] ?? 0;
            $avgConfidence += $pred['confidence_level'] ?? 0;
            $predictionCount++;
        }
        
        return [
            'totalPredicted2029' => $totalPredicted2029,
            'avgGrowthRate' => $predictionCount > 0 ? round($avgGrowthRate / $predictionCount, 2) : 0,
            'avgConfidence' => $predictionCount > 0 ? round($avgConfidence / $predictionCount, 1) : 0
        ];
    }
    

    public function calculateProyeksiStats($queries, $filtered_desa) {
        $total_anak_sekolah = 0;
        $total_fasilitas = 0;
        $total_jalan_rusak = 0;
        $total_penduduk = 0;
        
        foreach ($filtered_desa as $desa) {
            $id_desa = $desa['id_desa'];
            $penduduk = $queries->getPendudukByDesa($id_desa);
            $fasilitas = $queries->getFasilitasPendidikan($id_desa);
            $jalan = $queries->getInfrastrukturJalan($id_desa);
            
            $total_penduduk += count($penduduk);
            $total_fasilitas += count($fasilitas);
            
            foreach ($penduduk as $p) {
                if ($p['usia'] >= 6 && $p['usia'] <= 18) {
                    $total_anak_sekolah++;
                }
            }
            
            foreach ($jalan as $j) {
                if ($j['kondisi_jalan'] === 'rusak') {
                    $total_jalan_rusak += $j['panjang_jalan'];
                }
            }
        }
        
        return [
            'total_anak_sekolah' => $total_anak_sekolah,
            'total_fasilitas' => $total_fasilitas,
            'total_jalan_rusak' => $total_jalan_rusak,
            'total_penduduk' => $total_penduduk,
            'rasio_siswa_fasilitas' => $total_fasilitas > 0 ? $total_anak_sekolah / $total_fasilitas : 0
        ];
    }
    


}
?>