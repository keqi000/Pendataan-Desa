<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/session.php';
require_once __DIR__ . '/../../../../database/queries.php';

class UserDashboardFunctions {
    private $queries;
    
    public function __construct() {
        $this->queries = new Queries();
    }
    
    public function checkAuthentication() {
        if (!initSession() || !validateSession()) {
            header('Location: ' . BASE_URL . 'pages/auth/login.php');
            exit();
        }
        if ($_SESSION['user_role'] !== 'operator_desa') {
            header('Location: ' . BASE_URL . 'pages/auth/login.php');
            exit();
        }
    }
    
    public function getDesaData($id_desa) {
        return $this->queries->getDesaById($id_desa);
    }
    
    public function getStatistikPenduduk($id_desa) {
        return $this->queries->getStatistikPenduduk($id_desa);
    }
    
    public function getKeluargaData($id_desa) {
        return $this->queries->getKeluargaByDesa($id_desa);
    }
    
    public function getFasilitasPendidikan($id_desa) {
        return $this->queries->getFasilitasPendidikan($id_desa);
    }
    
    public function getUMKMData($id_desa) {
        return $this->queries->getUMKM($id_desa);
    }
    
    public function getJalanData($id_desa) {
        return $this->queries->getInfrastrukturJalan($id_desa);
    }
    
    public function getJembatanData($id_desa) {
        return $this->queries->getInfrastrukturJembatan($id_desa);
    }
    
    public function calculateFasilitasText($fasilitas_pendidikan) {
        $fasilitas_count = [];
        foreach ($fasilitas_pendidikan as $fasilitas) {
            $jenis = $fasilitas['jenis_pendidikan'];
            $fasilitas_count[$jenis] = ($fasilitas_count[$jenis] ?? 0) + 1;
        }
        return implode(', ', array_map(function($k, $v) { return "$k($v)"; }, array_keys($fasilitas_count), $fasilitas_count)) ?: 'Belum ada data';
    }
    
    public function calculateSektorUMKM($umkm_data) {
        $sektor_umkm = [];
        foreach ($umkm_data as $umkm) {
            $jenis = $umkm['jenis_usaha'];
            $sektor_umkm[$jenis] = ($sektor_umkm[$jenis] ?? 0) + 1;
        }
        return count($sektor_umkm);
    }
    
    public function calculatePersentaseJalanBaik($jalan_data) {
        $jalan_baik = 0;
        $total_panjang = 0;
        foreach ($jalan_data as $jalan) {
            $total_panjang += $jalan['panjang_jalan'];
            if ($jalan['kondisi_jalan'] === 'baik') {
                $jalan_baik += $jalan['panjang_jalan'];
            }
        }
        return $total_panjang > 0 ? round(($jalan_baik / $total_panjang) * 100) : 0;
    }
    
    public function calculateAnakSekolah($stats_penduduk) {
        $anak_sekolah = 0;
        foreach ($stats_penduduk['kelompok_usia'] ?? [] as $kelompok) {
            if (in_array($kelompok['kelompok_usia'], ['Anak', 'Remaja'])) {
                $anak_sekolah += $kelompok['jumlah'];
            }
        }
        return $anak_sekolah;
    }
    
    public function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
?>