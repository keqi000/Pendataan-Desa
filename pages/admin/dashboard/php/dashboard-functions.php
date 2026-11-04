<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/session.php';
require_once __DIR__ . '/../../../../database/queries.php';

class DashboardFunctions {
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
    
    public function calculateAggregatedStats($all_desa) {
        $stats = [
            'total_desa' => count($all_desa),
            'total_penduduk' => 0,
            'total_kk' => 0,
            'total_fasilitas_pendidikan' => 0,
            'total_umkm' => 0,
            'total_jalan_baik' => 0,
            'total_panjang_jalan' => 0,
            'total_jembatan' => 0,
            'total_luas_wilayah' => 0,
            'total_dusun' => 0,
            'total_rt' => 0,
            'total_rw' => 0,
            'total_anak_sekolah' => 0,
            'total_sektor_usaha' => [],
            'total_warga_miskin' => 0
        ];

        foreach ($all_desa as $desa) {
            $id_desa = $desa['id_desa'];

            // Penduduk stats
            $stats_penduduk = $this->queries->getStatistikPenduduk($id_desa);
            $stats['total_penduduk'] += $stats_penduduk['total'] ?? 0;
            $kk_data = $this->queries->analisisTingkat1Kependudukan($id_desa);
            $stats['total_kk'] += $kk_data['kepala_keluarga']['jumlah_kk'] ?? 0;

            // Fasilitas pendidikan
            $fasilitas = $this->queries->getFasilitasPendidikan($id_desa);
            $stats['total_fasilitas_pendidikan'] += count($fasilitas);

            // UMKM
            $umkm = $this->queries->getUMKM($id_desa);
            $stats['total_umkm'] += count($umkm);
            foreach ($umkm as $u) {
                $stats['total_sektor_usaha'][$u['jenis_usaha']] = true;
            }

            // Jalan
            $jalan = $this->queries->getInfrastrukturJalan($id_desa);
            foreach ($jalan as $j) {
                $stats['total_panjang_jalan'] += $j['panjang_jalan'];
                if ($j['kondisi_jalan'] === 'baik') {
                    $stats['total_jalan_baik'] += $j['panjang_jalan'];
                }
            }

            // Jembatan
            $jembatan = $this->queries->getInfrastrukturJembatan($id_desa);
            $stats['total_jembatan'] += count($jembatan);

            // Wilayah
            $stats['total_luas_wilayah'] += $desa['luas_wilayah'] ?? 0;
            $stats['total_dusun'] += $desa['jumlah_dusun'] ?? 0;
            $stats['total_rt'] += $desa['jumlah_rt'] ?? 0;
            $stats['total_rw'] += $desa['jumlah_rw'] ?? 0;

            // Anak sekolah (usia 7-18)
            foreach ($stats_penduduk['kelompok_usia'] ?? [] as $kelompok) {
                if (in_array($kelompok['kelompok_usia'], ['Anak', 'Remaja'])) {
                    $stats['total_anak_sekolah'] += $kelompok['jumlah'];
                }
            }

            // Warga miskin
            $query_miskin = "SELECT COUNT(*) as total FROM warga_miskin WHERE id_desa = :id_desa AND status_penerima = 'aktif'";
            $stmt = $this->queries->db->prepare($query_miskin);
            $stmt->bindParam(':id_desa', $id_desa);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_warga_miskin'] += $result['total'] ?? 0;
        }

        return $stats;
    }
    
    public function calculateDerivedStats($stats) {
        return [
            'rata_anggota_kk' => $stats['total_kk'] > 0 ? round($stats['total_penduduk'] / $stats['total_kk'], 1) : 0,
            'persentase_jalan_baik' => $stats['total_panjang_jalan'] > 0 ? round(($stats['total_jalan_baik'] / $stats['total_panjang_jalan']) * 100) : 0,
            'jumlah_sektor_usaha' => count($stats['total_sektor_usaha']),
            'persentase_miskin' => $stats['total_penduduk'] > 0 ? round(($stats['total_warga_miskin'] / $stats['total_penduduk']) * 100, 1) : 0
        ];
    }
    
    public function getAvailableYears() {
        $query_years = "SELECT DISTINCT tahun FROM (
            SELECT YEAR(created_at) as tahun FROM desa
            UNION
            SELECT YEAR(created_at) as tahun FROM penduduk
            UNION
            SELECT YEAR(created_at) as tahun FROM fasilitas_pendidikan
            UNION
            SELECT YEAR(created_at) as tahun FROM umkm
            UNION
            SELECT YEAR(created_at) as tahun FROM infrastruktur_jalan
        ) AS years ORDER BY tahun DESC";
        $stmt = $this->queries->db->prepare($query_years);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function calculateTrend($table, $date_field, $year, $start_month, $end_month, $condition = '') {
        // Get total existing data
        $query_total = "SELECT COUNT(*) as total FROM $table WHERE 1=1 $condition";
        $stmt = $this->queries->db->prepare($query_total);
        $stmt->execute();
        $total_existing = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // Get data added in selected period
        $query_period = "SELECT COUNT(*) as period_count FROM $table 
                       WHERE YEAR($date_field) = :year 
                       AND MONTH($date_field) BETWEEN :start_month AND :end_month
                       $condition";
        $stmt = $this->queries->db->prepare($query_period);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':start_month', $start_month);
        $stmt->bindParam(':end_month', $end_month);
        $stmt->execute();
        $period_count = $stmt->fetch(PDO::FETCH_ASSOC)['period_count'] ?? 0;

        // Calculate percentage
        if ($total_existing == 0) {
            return $period_count > 0 ? 100 : 0;
        }
        return round(($period_count / $total_existing) * 100, 1);
    }
    
    public function getTrendData($selected_year, $start_month, $end_month) {
        return [
            'desa' => $this->calculateTrend('desa', 'created_at', $selected_year, $start_month, $end_month),
            'penduduk' => $this->calculateTrend('penduduk', 'created_at', $selected_year, $start_month, $end_month),
            'pendidikan' => $this->calculateTrend('fasilitas_pendidikan', 'created_at', $selected_year, $start_month, $end_month),
            'umkm' => $this->calculateTrend('umkm', 'created_at', $selected_year, $start_month, $end_month),
            'jalan' => $this->calculateTrend('infrastruktur_jalan', 'created_at', $selected_year, $start_month, $end_month)
        ];
    }
    
    public function getMonthlyData($selected_year, $start_month, $end_month) {
        $monthly_data = [];
        for ($month = $start_month; $month <= $end_month; $month++) {
            $monthly_data[$month] = [
                'desa' => 0,
                'penduduk' => 0,
                'pendidikan' => 0,
                'umkm' => 0,
                'jalan' => 0
            ];

            // Count data for each month
            $tables = ['desa', 'penduduk', 'fasilitas_pendidikan', 'umkm'];
            $keys = ['desa', 'penduduk', 'pendidikan', 'umkm'];

            for ($i = 0; $i < count($tables); $i++) {
                $query = "SELECT COUNT(*) as count FROM {$tables[$i]} 
                         WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month";
                $stmt = $this->queries->db->prepare($query);
                $stmt->bindParam(':year', $selected_year);
                $stmt->bindParam(':month', $month);
                $stmt->execute();
                $monthly_data[$month][$keys[$i]] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            }

            // New roads added for the month
            $query = "SELECT COUNT(*) as count FROM infrastruktur_jalan 
                     WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month";
            $stmt = $this->queries->db->prepare($query);
            $stmt->bindParam(':year', $selected_year);
            $stmt->bindParam(':month', $month);
            $stmt->execute();
            $monthly_data[$month]['jalan'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        }
        return $monthly_data;
    }
    
    public function getDesaDataCompleteness($id_desa) {
        $penduduk_count = $this->queries->db->prepare("SELECT COUNT(*) as total FROM penduduk WHERE id_desa = ?");
        $penduduk_count->execute([$id_desa]);
        $penduduk = $penduduk_count->fetch()['total'];

        $fasilitas_count = $this->queries->db->prepare("SELECT COUNT(*) as total FROM fasilitas_pendidikan WHERE id_desa = ?");
        $fasilitas_count->execute([$id_desa]);
        $fasilitas = $fasilitas_count->fetch()['total'];

        $umkm_count = $this->queries->db->prepare("SELECT COUNT(*) as total FROM umkm WHERE id_desa = ?");
        $umkm_count->execute([$id_desa]);
        $umkm = $umkm_count->fetch()['total'];

        $total_data = $penduduk + $fasilitas + $umkm;
        $status = $total_data > 20 ? 'Lengkap' : ($total_data > 10 ? 'Sebagian' : 'Minim');
        $badge_class = $total_data > 20 ? 'success' : ($total_data > 10 ? 'warning' : 'danger');
        
        return [
            'penduduk' => $penduduk,
            'fasilitas' => $fasilitas,
            'umkm' => $umkm,
            'total_data' => $total_data,
            'status' => $status,
            'badge_class' => $badge_class
        ];
    }
    
    public function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    // API Functions - moved from dashboard.api.php
    public function getDesaDetail($desa_id) {
        if (!$desa_id) {
            return null;
        }
        
        $query = "SELECT * FROM desa WHERE id_desa = ?";
        $stmt = $this->queries->db->prepare($query);
        $stmt->execute([$desa_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getDashboardStats() {
        $stats = [];
        
        // Total desa
        $query = "SELECT COUNT(*) as total FROM desa";
        $stmt = $this->queries->db->prepare($query);
        $stmt->execute();
        $stats['total_desa'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total penduduk
        $query = "SELECT COUNT(*) as total FROM penduduk";
        $stmt = $this->queries->db->prepare($query);
        $stmt->execute();
        $stats['total_penduduk'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total keluarga
        $query = "SELECT COUNT(*) as total FROM keluarga";
        $stmt = $this->queries->db->prepare($query);
        $stmt->execute();
        $stats['total_keluarga'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total UMKM
        $query = "SELECT COUNT(*) as total FROM umkm";
        $stmt = $this->queries->db->prepare($query);
        $stmt->execute();
        $stats['total_umkm'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total jalan
        $query = "SELECT COUNT(*) as total FROM infrastruktur_jalan";
        $stmt = $this->queries->db->prepare($query);
        $stmt->execute();
        $stats['total_jalan'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return $stats;
    }
    
    public function getDesaSummary() {
        $query = "SELECT d.*, 
                 (SELECT COUNT(*) FROM penduduk p WHERE p.id_desa = d.id_desa) as total_penduduk,
                 (SELECT COUNT(*) FROM keluarga k WHERE k.id_desa = d.id_desa) as total_keluarga,
                 (SELECT COUNT(*) FROM umkm u WHERE u.id_desa = d.id_desa) as total_umkm,
                 (SELECT COUNT(*) FROM infrastruktur_jalan ij WHERE ij.id_desa = d.id_desa) as total_jalan
                 FROM desa d ORDER BY d.nama_desa";
        $stmt = $this->queries->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getMonthlyCount($table, $year, $month) {
        $query = "SELECT COUNT(*) as count FROM $table 
                 WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month";
        $stmt = $this->queries->db->prepare($query);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':month', $month);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    }
    
    public function getTrendDataAPI($year = null, $period = null) {
        $year = $year ?? date('Y');
        $period = $period ?? 'jan-jun';
        
        // Set month range
        if ($period == 'jan-jun') {
            $start_month = 1;
            $end_month = 6;
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'];
        } else {
            $start_month = 7;
            $end_month = 12;
            $months = ['Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        }
        
        // Calculate trends
        $trend_data = [
            'desa' => $this->calculateTrend('desa', 'created_at', $year, $start_month, $end_month),
            'penduduk' => $this->calculateTrend('penduduk', 'created_at', $year, $start_month, $end_month),
            'pendidikan' => $this->calculateTrend('fasilitas_pendidikan', 'created_at', $year, $start_month, $end_month),
            'umkm' => $this->calculateTrend('umkm', 'created_at', $year, $start_month, $end_month),
            'jalan' => $this->calculateTrend('infrastruktur_jalan', 'created_at', $year, $start_month, $end_month)
        ];
        
        // Get monthly data
        $monthly_data = [];
        for ($month = $start_month; $month <= $end_month; $month++) {
            $monthly_data[$month] = [
                'desa' => $this->getMonthlyCount('desa', $year, $month),
                'penduduk' => $this->getMonthlyCount('penduduk', $year, $month),
                'pendidikan' => $this->getMonthlyCount('fasilitas_pendidikan', $year, $month),
                'umkm' => $this->getMonthlyCount('umkm', $year, $month),
                'jalan' => $this->getMonthlyCount('infrastruktur_jalan', $year, $month)
            ];
        }
        
        return [
            'trendData' => $trend_data,
            'monthlyData' => $monthly_data,
            'months' => $months,
            'year' => $year,
            'period' => $period
        ];
    }
    
    public function getDesaOverviewData($desa_id) {
        if (!$desa_id) return null;
        
        // Get desa detail
        $desa = $this->getDesaDetail($desa_id);
        if (!$desa) return null;
        
        // Get counts
        $penduduk_count = $this->queries->db->prepare("SELECT COUNT(*) as total FROM penduduk WHERE id_desa = ?");
        $penduduk_count->execute([$desa_id]);
        $total_penduduk = $penduduk_count->fetch()['total'];
        
        $umkm_count = $this->queries->db->prepare("SELECT COUNT(*) as total FROM umkm WHERE id_desa = ?");
        $umkm_count->execute([$desa_id]);
        $total_umkm = $umkm_count->fetch()['total'];
        
        $sekolah_count = $this->queries->db->prepare("SELECT COUNT(*) as total FROM fasilitas_pendidikan WHERE id_desa = ?");
        $sekolah_count->execute([$desa_id]);
        $total_sekolah = $sekolah_count->fetch()['total'];
        
        $jalan_query = $this->queries->db->prepare("SELECT kondisi_jalan FROM infrastruktur_jalan WHERE id_desa = ?");
        $jalan_query->execute([$desa_id]);
        $jalan_data = $jalan_query->fetchAll();
        $total_jalan = count($jalan_data);
        $jalan_baik = count(array_filter($jalan_data, fn($j) => $j['kondisi_jalan'] === 'baik'));
        $persentase_jalan_baik = $total_jalan > 0 ? round(($jalan_baik / $total_jalan) * 100) : 0;
        
        return [
            'desa' => $desa,
            'total_penduduk' => $total_penduduk,
            'total_umkm' => $total_umkm,
            'total_sekolah' => $total_sekolah,
            'persentase_jalan_baik' => $persentase_jalan_baik
        ];
    }
    
    public function getDesaOverview($desa_id) {
        return $this->getDesaOverviewData($desa_id);
    }
    
    public function handleAjaxRequest() {
        $endpoint = $_GET['endpoint'] ?? '';
        
        switch ($endpoint) {
            case 'desa-detail':
                $desa_id = $_GET['id'] ?? null;
                $result = $this->getDesaDetail($desa_id);
                return $this->jsonResponse(true, 'Success', $result);
                
            case 'desa-overview':
                $desa_id = $_GET['id'] ?? null;
                $result = $this->getDesaOverviewData($desa_id);
                return $this->jsonResponse(true, 'Success', $result);
                
            case 'dashboard-stats':
                $result = $this->getDashboardStats();
                return $this->jsonResponse(true, 'Success', $result);
                
            case 'desa-summary':
                $result = $this->getDesaSummary();
                return $this->jsonResponse(true, 'Success', $result);
                
            case 'trend-data':
                $year = $_GET['year'] ?? null;
                $period = $_GET['period'] ?? null;
                $result = $this->getTrendDataAPI($year, $period);
                return $this->jsonResponse(true, 'Success', $result);
                
            default:
                return $this->jsonResponse(false, 'Endpoint not found', null, 404);
        }
    }
    
    private function jsonResponse($success, $message, $data = null, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        return json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
    }
}
?>