<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/session.php';
require_once __DIR__ . '/../../../../database/queries.php';

class MonitoringFunctions {
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
    
    // Detail Desa Data Functions with Pagination
    public function getDesaDataPaginated($desa_id, $type, $page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        $search_condition = '';
        $params = [$desa_id];
        
        if (!empty($search)) {
            $search = '%' . $search . '%';
        }
        
        switch ($type) {
            case 'penduduk':
                $search_condition = !empty($search) ? "AND (p.nama_lengkap LIKE ? OR p.nik LIKE ?)" : "";
                if (!empty($search)) {
                    $params[] = $search;
                    $params[] = $search;
                }
                $query = "SELECT p.nik, p.nama_lengkap, p.jenis_kelamin, p.usia, p.pendidikan_terakhir, p.pekerjaan 
                         FROM penduduk p 
                         WHERE p.id_desa = ? $search_condition
                         ORDER BY p.nama_lengkap LIMIT ? OFFSET ?";
                break;
                
            case 'fasilitas':
                $search_condition = !empty($search) ? "AND (f.nama_fasilitas LIKE ? OR f.jenis_pendidikan LIKE ?)" : "";
                if (!empty($search)) {
                    $params[] = $search;
                    $params[] = $search;
                }
                $query = "SELECT f.nama_fasilitas, f.jenis_pendidikan, f.alamat_fasilitas, f.kapasitas_siswa, f.kondisi_bangunan 
                         FROM fasilitas_pendidikan f 
                         WHERE f.id_desa = ? $search_condition
                         ORDER BY f.nama_fasilitas LIMIT ? OFFSET ?";
                break;
                
            case 'umkm':
                $search_condition = !empty($search) ? "AND (u.nama_usaha LIKE ? OR u.jenis_usaha LIKE ?)" : "";
                if (!empty($search)) {
                    $params[] = $search;
                    $params[] = $search;
                }
                $query = "SELECT u.nama_usaha, u.jenis_usaha, u.modal_usaha, u.omzet_perbulan, u.jumlah_karyawan 
                         FROM umkm u 
                         WHERE u.id_desa = ? $search_condition
                         ORDER BY u.nama_usaha LIMIT ? OFFSET ?";
                break;
                
            case 'pasar':
                $search_condition = !empty($search) ? "AND (p.nama_pasar LIKE ? OR p.jenis_pasar LIKE ?)" : "";
                if (!empty($search)) {
                    $params[] = $search;
                    $params[] = $search;
                }
                $query = "SELECT p.nama_pasar, p.jenis_pasar, p.jumlah_pedagang, p.hari_operasional, p.kondisi_fasilitas 
                         FROM pasar p 
                         WHERE p.id_desa = ? $search_condition
                         ORDER BY p.nama_pasar LIMIT ? OFFSET ?";
                break;
                
            case 'jalan':
                $search_condition = !empty($search) ? "AND (j.nama_jalan LIKE ? OR j.kondisi_jalan LIKE ?)" : "";
                if (!empty($search)) {
                    $params[] = $search;
                    $params[] = $search;
                }
                $query = "SELECT j.nama_jalan, j.panjang_jalan, j.lebar_jalan, j.kondisi_jalan, j.jenis_permukaan 
                         FROM infrastruktur_jalan j 
                         WHERE j.id_desa = ? $search_condition
                         ORDER BY j.nama_jalan LIMIT ? OFFSET ?";
                break;
                
            case 'jembatan':
                $search_condition = !empty($search) ? "AND (j.nama_jembatan LIKE ? OR j.kondisi_jembatan LIKE ?)" : "";
                if (!empty($search)) {
                    $params[] = $search;
                    $params[] = $search;
                }
                $query = "SELECT j.nama_jembatan, j.panjang_jembatan, j.lebar_jembatan, j.kondisi_jembatan, j.material_jembatan 
                         FROM infrastruktur_jembatan j 
                         WHERE j.id_desa = ? $search_condition
                         ORDER BY j.nama_jembatan LIMIT ? OFFSET ?";
                break;
                
            default:
                return [
                    'data' => [],
                    'total' => 0,
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'total_pages' => 0
                ];
        }
        
        // Execute main query with proper parameter binding
        $stmt = $this->queries->db->prepare($query);
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i]);
        }
        $stmt->bindValue(count($params) + 1, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count with optimized query
        $count_query = str_replace('SELECT ', 'SELECT COUNT(*) as total FROM (SELECT ', $query);
        $count_query = preg_replace('/ORDER BY.*/', '', $count_query);
        $count_query = preg_replace('/LIMIT.*/', '', $count_query);
        $count_query .= ') as count_table';
        
        $count_stmt = $this->queries->db->prepare($count_query);
        for ($i = 0; $i < count($params); $i++) {
            $count_stmt->bindValue($i + 1, $params[$i]);
        }
        $count_stmt->execute();
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        return [
            'data' => $data,
            'total' => (int)$total,
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total_pages' => (int)ceil($total / $limit)
        ];
    }
    
    // Public methods for accessing data
    public function getDesaById($desa_id) {
        return $this->queries->getDesaById($desa_id);
    }
    
    public function getStatistikPenduduk($desa_id) {
        return $this->queries->getStatistikPenduduk($desa_id);
    }
    
    // Detail Desa Functions
    public function validateDesaId($desa_id) {
        if (!$desa_id) {
            header('Location: ' . BASE_URL . 'pages/admin/monitoring/monitoring.php');
            exit();
        }
        return $desa_id;
    }
    
    public function calculateDetailStatistics($desa_id, $stats_penduduk) {
        $total_penduduk = $stats_penduduk['total'] ?? 0;
        
        $query_kk = "SELECT COUNT(*) as total_kk FROM keluarga WHERE id_desa = :id_desa";
        $stmt = $this->queries->db->prepare($query_kk);
        $stmt->bindParam(':id_desa', $desa_id);
        $stmt->execute();
        $total_kk = $stmt->fetch(PDO::FETCH_ASSOC)['total_kk'] ?? 0;
        
        $rata_per_kk = $total_kk > 0 ? round($total_penduduk / $total_kk, 1) : 0;
        
        $desa_data = $this->queries->getDesaById($desa_id);
        $luas_km2 = ($desa_data['luas_wilayah'] ?? 0) * 0.01;
        $kepadatan = $luas_km2 > 0 ? round($total_penduduk / $luas_km2, 2) : 0;
        
        return [
            'total_penduduk' => $total_penduduk,
            'total_kk' => $total_kk,
            'rata_per_kk' => $rata_per_kk,
            'kepadatan' => $kepadatan
        ];
    }
    
    public function calculateMonitoringStats($all_desa) {
        $data_lengkap = 0;
        $perlu_update = 0;
        $data_kosong = 0;
        
        foreach ($all_desa as $desa) {
            $id_desa = $desa['id_desa'];
            $stats_penduduk = $this->queries->getStatistikPenduduk($id_desa);
            $fasilitas = $this->queries->getFasilitasPendidikan($id_desa);
            $ekonomi = $this->queries->getDataEkonomi($id_desa);
            
            $total_data = ($stats_penduduk['total'] ?? 0) + count($fasilitas) + count($ekonomi);
            
            if ($total_data == 0) {
                $data_kosong++;
            } elseif ($total_data > 0 && count($fasilitas) > 0 && count($ekonomi) > 0) {
                $data_lengkap++;
            } else {
                $perlu_update++;
            }
        }
        
        return [
            'data_lengkap' => $data_lengkap,
            'perlu_update' => $perlu_update,
            'data_kosong' => $data_kosong
        ];
    }
    
    public function getRecommendation($status, $persentase_selisih) {
        switch ($status) {
            case 'sesuai':
                return 'Data sudah sesuai dengan BPS';
            case 'selisih_kecil':
                return 'Perlu verifikasi data penduduk';
            case 'selisih_besar':
                return 'Perlu audit dan koreksi data';
            default:
                return 'Status tidak diketahui';
        }
    }
    
    public function getDesaDataForTable($desa) {
        $id_desa = $desa['id_desa'];
        $stats = $this->queries->getStatistikPenduduk($id_desa);
        $fasilitas = $this->queries->getFasilitasPendidikan($id_desa);
        $ekonomi = $this->queries->getDataEkonomi($id_desa);
        $jalan = $this->queries->getInfrastrukturJalan($id_desa);
        $data = $this->queries->getKeluargaByDesa($id_desa);
        
        $total_penduduk = $stats['total'] ?? 0;
        $total_fasilitas = count($fasilitas);
        $total_ekonomi = count($ekonomi);
        $total_jalan = count($jalan);
        $total_kk = count($data);
        
        // Determine status
        $status_class = 'badge-warning';
        $status_text = 'Belum Ada Data';
        
        if ($total_penduduk > 0 && $total_fasilitas > 0 && $total_ekonomi > 0) {
            $status_class = 'badge-success';
            $status_text = 'Lengkap';
        } elseif ($total_penduduk > 0 || $total_fasilitas > 0 || $total_ekonomi > 0) {
            $status_class = 'badge-warning';
            $status_text = 'Tidak Lengkap';
        }
        
        return [
            'total_penduduk' => $total_penduduk,
            'total_kk' => $total_kk,
            'total_fasilitas' => $total_fasilitas,
            'total_ekonomi' => $total_ekonomi,
            'total_jalan' => $total_jalan,
            'status_class' => $status_class,
            'status_text' => $status_text
        ];
    }
    
    public function getDesaQualityData($desa) {
        $id_desa = $desa['id_desa'];
        $stats = $this->queries->getStatistikPenduduk($id_desa);
        $fasilitas = $this->queries->getFasilitasPendidikan($id_desa);
        $ekonomi = $this->queries->getDataEkonomi($id_desa);
        $jalan = $this->queries->getInfrastrukturJalan($id_desa);
        $jembatan = $this->queries->getInfrastrukturJembatan($id_desa);
        $mataPencaharian = $this->queries->getMataPencaharianByDesa($id_desa);
        
        $total_penduduk = $stats['total'] ?? 0;
        $total_fasilitas = count($fasilitas);
        $total_ekonomi = count($ekonomi);
        $total_infrastruktur = count($jalan) + count($jembatan);
        
        // Check consistency
        $konsistensi_pekerjaan = $total_penduduk > 0 && count($mataPencaharian) > 0;
        
        // Calculate quality score
        $quality_score = 0;
        if ($total_penduduk > 0) $quality_score += 25;
        if ($total_ekonomi > 0) $quality_score += 25;
        if ($total_fasilitas > 0) $quality_score += 25;
        if ($total_infrastruktur > 0) $quality_score += 25;
        
        $quality_class = 'warning';
        $quality_text = 'Perlu Perhatian';
        if ($quality_score >= 75) {
            $quality_class = 'good';
            $quality_text = 'Baik';
        } elseif ($quality_score >= 50) {
            $quality_class = 'warning';
            $quality_text = 'Sedang';
        }
        
        return [
            'total_penduduk' => $total_penduduk,
            'total_ekonomi' => $total_ekonomi,
            'total_fasilitas' => $total_fasilitas,
            'total_infrastruktur' => $total_infrastruktur,
            'konsistensi_pekerjaan' => $konsistensi_pekerjaan,
            'quality_score' => $quality_score,
            'quality_class' => $quality_class,
            'quality_text' => $quality_text
        ];
    }
    
    public function getCombinedDataPaginated($type, $page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        $search_condition = '';
        $params = [];
        
        if (!empty($search)) {
            $search = '%' . $search . '%';
            $params[':search'] = $search;
        }
        
        switch ($type) {
            case 'penduduk':
                $search_condition = !empty($search) ? "AND (p.nama_lengkap LIKE :search OR p.nik LIKE :search OR d.nama_desa LIKE :search)" : "";
                $query = "SELECT p.nik, p.nama_lengkap, p.jenis_kelamin, p.usia, p.pendidikan_terakhir, p.pekerjaan, d.nama_desa 
                         FROM penduduk p 
                         JOIN desa d ON p.id_desa = d.id_desa 
                         WHERE 1=1 $search_condition
                         ORDER BY p.nama_lengkap LIMIT :limit OFFSET :offset";
                break;
                
            case 'fasilitas':
                $search_condition = !empty($search) ? "AND (f.nama_fasilitas LIKE :search OR f.jenis_pendidikan LIKE :search OR d.nama_desa LIKE :search)" : "";
                $query = "SELECT f.nama_fasilitas, f.jenis_pendidikan, f.alamat_fasilitas, f.kapasitas_siswa, f.kondisi_bangunan, d.nama_desa 
                         FROM fasilitas_pendidikan f 
                         JOIN desa d ON f.id_desa = d.id_desa 
                         WHERE 1=1 $search_condition
                         ORDER BY f.nama_fasilitas LIMIT :limit OFFSET :offset";
                break;
                
            case 'umkm':
                $search_condition = !empty($search) ? "AND (u.nama_usaha LIKE :search OR u.jenis_usaha LIKE :search OR d.nama_desa LIKE :search)" : "";
                $query = "SELECT u.nama_usaha, u.jenis_usaha, u.modal_usaha, u.omzet_perbulan, u.jumlah_karyawan, d.nama_desa 
                         FROM umkm u 
                         JOIN desa d ON u.id_desa = d.id_desa 
                         WHERE 1=1 $search_condition
                         ORDER BY u.nama_usaha LIMIT :limit OFFSET :offset";
                break;
                
            case 'pasar':
                $search_condition = !empty($search) ? "AND (p.nama_pasar LIKE :search OR p.jenis_pasar LIKE :search OR d.nama_desa LIKE :search)" : "";
                $query = "SELECT p.nama_pasar, p.jenis_pasar, p.jumlah_pedagang, p.hari_operasional, p.kondisi_fasilitas, d.nama_desa 
                         FROM pasar p 
                         JOIN desa d ON p.id_desa = d.id_desa 
                         WHERE 1=1 $search_condition
                         ORDER BY p.nama_pasar LIMIT :limit OFFSET :offset";
                break;
                
            case 'jalan':
                $search_condition = !empty($search) ? "AND (j.nama_jalan LIKE :search OR j.kondisi_jalan LIKE :search OR d.nama_desa LIKE :search)" : "";
                $query = "SELECT j.nama_jalan, j.panjang_jalan, j.lebar_jalan, j.kondisi_jalan, j.jenis_permukaan, d.nama_desa 
                         FROM infrastruktur_jalan j 
                         JOIN desa d ON j.id_desa = d.id_desa 
                         WHERE 1=1 $search_condition
                         ORDER BY j.nama_jalan LIMIT :limit OFFSET :offset";
                break;
                
            case 'jembatan':
                $search_condition = !empty($search) ? "AND (j.nama_jembatan LIKE :search OR j.kondisi_jembatan LIKE :search OR d.nama_desa LIKE :search)" : "";
                $query = "SELECT j.nama_jembatan, j.panjang_jembatan, j.lebar_jembatan, j.kondisi_jembatan, j.material_jembatan, d.nama_desa 
                         FROM infrastruktur_jembatan j 
                         JOIN desa d ON j.id_desa = d.id_desa 
                         WHERE 1=1 $search_condition
                         ORDER BY j.nama_jembatan LIMIT :limit OFFSET :offset";
                break;
                
            default:
                return [
                    'data' => [],
                    'total' => 0,
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'total_pages' => 0
                ];
        }
        
        // Execute main query
        $stmt = $this->queries->db->prepare($query);
        if (!empty($search)) {
            $stmt->bindParam(':search', $params[':search']);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $count_query = str_replace('SELECT ', 'SELECT COUNT(*) as total FROM (SELECT ', $query);
        $count_query = preg_replace('/ORDER BY.*/', '', $count_query);
        $count_query = preg_replace('/LIMIT.*/', '', $count_query);
        $count_query .= ') as count_table';
        
        $count_stmt = $this->queries->db->prepare($count_query);
        if (!empty($search)) {
            $count_stmt->bindParam(':search', $params[':search']);
        }
        $count_stmt->execute();
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        return [
            'data' => $data,
            'total' => (int)$total,
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total_pages' => (int)ceil($total / $limit)
        ];
    }
    
    public function getAllCombinedData() {
        // Keep for statistics calculation only
        $all_desa = $this->getAllDesa();
        $combined_data = [
            'penduduk_list' => [],
            'fasilitas_pendidikan' => [],
            'umkm_list' => [],
            'pasar_list' => [],
            'jalan_list' => [],
            'jembatan_list' => []
        ];
        
        foreach ($all_desa as $desa) {
            $id_desa = $desa['id_desa'];
            $nama_desa = $desa['nama_desa'];
            
            // Get penduduk with desa name
            $penduduk = $this->queries->getPendudukByDesa($id_desa);
            foreach ($penduduk as &$p) {
                $p['nama_desa'] = $nama_desa;
            }
            $combined_data['penduduk_list'] = array_merge($combined_data['penduduk_list'], $penduduk);
            
            // Get fasilitas pendidikan with desa name
            $fasilitas = $this->queries->getFasilitasPendidikan($id_desa);
            foreach ($fasilitas as &$f) {
                $f['nama_desa'] = $nama_desa;
            }
            $combined_data['fasilitas_pendidikan'] = array_merge($combined_data['fasilitas_pendidikan'], $fasilitas);
            
            // Get UMKM with desa name
            $umkm = $this->queries->getUMKM($id_desa);
            foreach ($umkm as &$u) {
                $u['nama_desa'] = $nama_desa;
            }
            $combined_data['umkm_list'] = array_merge($combined_data['umkm_list'], $umkm);
            
            // Get Pasar with desa name
            $pasar = $this->queries->getPasarByDesa($id_desa);
            foreach ($pasar as &$p) {
                $p['nama_desa'] = $nama_desa;
            }
            $combined_data['pasar_list'] = array_merge($combined_data['pasar_list'], $pasar);
            
            // Get Jalan with desa name
            $jalan = $this->queries->getInfrastrukturJalan($id_desa);
            foreach ($jalan as &$j) {
                $j['nama_desa'] = $nama_desa;
            }
            $combined_data['jalan_list'] = array_merge($combined_data['jalan_list'], $jalan);
            
            // Get Jembatan with desa name
            $jembatan = $this->queries->getInfrastrukturJembatan($id_desa);
            foreach ($jembatan as &$jmb) {
                $jmb['nama_desa'] = $nama_desa;
            }
            $combined_data['jembatan_list'] = array_merge($combined_data['jembatan_list'], $jembatan);
        }
        
        return $combined_data;
    }
    
    public function calculateCombinedStatistics($all_data) {
        $all_desa = $this->getAllDesa();
        $total_desa = count($all_desa);
        $total_penduduk = count($all_data['penduduk_list']);
        
        $total_kk = 0;
        $total_luas = 0;
        $total_dusun = 0;
        $total_rt = 0;
        $total_rw = 0;
        
        foreach ($all_desa as $desa) {
            $id_desa = $desa['id_desa'];
            
            $query_kk = "SELECT COUNT(*) as total_kk FROM keluarga WHERE id_desa = :id_desa";
            $stmt = $this->queries->db->prepare($query_kk);
            $stmt->bindParam(':id_desa', $id_desa);
            $stmt->execute();
            $total_kk += $stmt->fetch(PDO::FETCH_ASSOC)['total_kk'] ?? 0;
            
            $total_luas += $desa['luas_wilayah'] ?? 0;
            $total_dusun += $desa['jumlah_dusun'] ?? 0;
            $total_rt += $desa['jumlah_rt'] ?? 0;
            $total_rw += $desa['jumlah_rw'] ?? 0;
        }
        
        $rata_per_kk = $total_kk > 0 ? round($total_penduduk / $total_kk, 1) : 0;
        $luas_km2 = $total_luas * 0.01;
        $kepadatan = $luas_km2 > 0 ? round($total_penduduk / $luas_km2, 2) : 0;
        
        return [
            'total_desa' => $total_desa,
            'total_penduduk' => $total_penduduk,
            'total_kk' => $total_kk,
            'rata_per_kk' => $rata_per_kk,
            'kepadatan' => $kepadatan,
            'total_luas' => $total_luas,
            'total_dusun' => $total_dusun,
            'total_rt' => $total_rt,
            'total_rw' => $total_rw
        ];
    }
    
    public function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    // API Functions - moved from monitoring.api.php
    public function getAllDesaDataAPI($type = 'all', $desa_id = null) {
        switch ($type) {
            case 'penduduk':
                if ($desa_id) {
                    return $this->queries->getPendudukByDesa($desa_id);
                } else {
                    $result = [];
                    foreach ($this->getAllDesa() as $desa) {
                        $penduduk = $this->queries->getPendudukByDesa($desa['id_desa']);
                        $result = array_merge($result, $penduduk);
                    }
                    return $result;
                }
                
            case 'ekonomi':
                if ($desa_id) {
                    return $this->queries->getMataPencaharianByDesa($desa_id);
                } else {
                    $result = [];
                    foreach ($this->getAllDesa() as $desa) {
                        $ekonomi = $this->queries->getMataPencaharianByDesa($desa['id_desa']);
                        $result = array_merge($result, $ekonomi);
                    }
                    return $result;
                }
                
            case 'pendidikan':
                if ($desa_id) {
                    return $this->queries->getFasilitasPendidikan($desa_id);
                } else {
                    $result = [];
                    foreach ($this->getAllDesa() as $desa) {
                        $fasilitas = $this->queries->getFasilitasPendidikan($desa['id_desa']);
                        $result = array_merge($result, $fasilitas);
                    }
                    return $result;
                }
                
            case 'infrastruktur':
                if ($desa_id) {
                    $jalan = $this->queries->getInfrastrukturJalan($desa_id);
                    $jembatan = $this->queries->getInfrastrukturJembatan($desa_id);
                    return ['jalan' => $jalan, 'jembatan' => $jembatan];
                } else {
                    $result = ['jalan' => [], 'jembatan' => []];
                    foreach ($this->getAllDesa() as $desa) {
                        $jalan = $this->queries->getInfrastrukturJalan($desa['id_desa']);
                        $jembatan = $this->queries->getInfrastrukturJembatan($desa['id_desa']);
                        $result['jalan'] = array_merge($result['jalan'], $jalan);
                        $result['jembatan'] = array_merge($result['jembatan'], $jembatan);
                    }
                    return $result;
                }
                
            case 'umkm':
                if ($desa_id) {
                    return $this->queries->getUMKM($desa_id);
                } else {
                    $result = [];
                    foreach ($this->getAllDesa() as $desa) {
                        $umkm = $this->queries->getUMKM($desa['id_desa']);
                        $result = array_merge($result, $umkm);
                    }
                    return $result;
                }
                
            default:
                $query = "SELECT d.*, 
                         (SELECT COUNT(*) FROM penduduk p WHERE p.id_desa = d.id_desa) as total_penduduk,
                         (SELECT COUNT(*) FROM keluarga k WHERE k.id_desa = d.id_desa) as total_keluarga,
                         (SELECT COUNT(*) FROM umkm u WHERE u.id_desa = d.id_desa) as total_umkm,
                         (SELECT COUNT(*) FROM fasilitas_pendidikan f WHERE f.id_desa = d.id_desa) as total_fasilitas
                         FROM desa d ORDER BY d.nama_desa";
                $stmt = $this->queries->db->prepare($query);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    public function getDesaDetailAPI($id_desa) {
        if (!$id_desa) return null;
        
        return [
            'desa_info' => $this->queries->getDesaById($id_desa),
            'statistik_penduduk' => $this->queries->getStatistikPenduduk($id_desa),
            'data_ekonomi' => $this->queries->getDataEkonomi($id_desa),
            'fasilitas_pendidikan' => $this->queries->getFasilitasPendidikan($id_desa)
        ];
    }
    
    public function getDataCompleteness() {
        $completeness_data = [];
        
        foreach ($this->getAllDesa() as $desa) {
            $id_desa = $desa['id_desa'];
            
            $penduduk_count = count($this->queries->getPendudukByDesa($id_desa));
            $keluarga_count = count($this->queries->getKeluargaByDesa($id_desa));
            $ekonomi_count = count($this->queries->getDataEkonomi($id_desa));
            $fasilitas_count = count($this->queries->getFasilitasPendidikan($id_desa));
            
            $total_expected = 100;
            $total_actual = $penduduk_count + $keluarga_count + $ekonomi_count + $fasilitas_count;
            $completeness_percentage = min(100, ($total_actual / $total_expected) * 100);
            
            $completeness_data[] = [
                'desa_id' => $id_desa,
                'nama_desa' => $desa['nama_desa'],
                'completeness_percentage' => round($completeness_percentage, 1),
                'data_counts' => [
                    'penduduk' => $penduduk_count,
                    'keluarga' => $keluarga_count,
                    'ekonomi' => $ekonomi_count,
                    'fasilitas' => $fasilitas_count
                ]
            ];
        }
        
        return $completeness_data;
    }
    
    public function getActivityLog() {
        return [[
            'timestamp' => date('Y-m-d H:i:s'),
            'user' => 'Admin Kecamatan',
            'action' => 'Data monitoring accessed',
            'details' => 'Monitoring dashboard viewed'
        ]];
    }
    
    public function getPerformanceMetrics() {
        $all_desa = $this->getAllDesa();
        $total_desa = count($all_desa);
        $total_penduduk = 0;
        $total_umkm = 0;
        $total_fasilitas = 0;
        
        foreach ($all_desa as $desa) {
            $id_desa = $desa['id_desa'];
            $total_penduduk += count($this->queries->getPendudukByDesa($id_desa));
            $total_umkm += count($this->queries->getUMKM($id_desa));
            $total_fasilitas += count($this->queries->getFasilitasPendidikan($id_desa));
        }
        
        return [
            'total_desa' => $total_desa,
            'total_penduduk' => $total_penduduk,
            'total_umkm' => $total_umkm,
            'total_fasilitas' => $total_fasilitas,
            'avg_penduduk_per_desa' => $total_desa > 0 ? round($total_penduduk / $total_desa, 2) : 0
        ];
    }
    
    public function handleAjaxRequest() {
        header('Content-Type: application/json');
        
        if (!$this->isAjaxRequest()) {
            return $this->jsonResponse(false, 'Invalid request method', null, 400);
        }
        
        $endpoint = $_GET['endpoint'] ?? '';
        
        switch ($endpoint) {
            case 'tab-perbandingan':
                $all_desa = $this->getAllDesa();
                return $this->jsonResponse(true, 'Success', ['desa_list' => $all_desa]);
                
            case 'tab-quality':
                ob_start();
                require_once __DIR__ . '/quality-check-view.php';
                $content = ob_get_clean();
                return $this->jsonResponse(true, 'Success', ['html' => $content]);
                
            case 'desa-data-paginated':
                $desa_id = (int)($_GET['desa_id'] ?? 0);
                $type = $_GET['type'] ?? 'penduduk';
                $page = max(1, (int)($_GET['page'] ?? 1));
                $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
                $search = trim($_GET['search'] ?? '');
                
                $result = $this->getDesaDataPaginated($desa_id, $type, $page, $limit, $search);
                return $this->jsonResponse(true, 'Success', $result);
                
            case 'combined-data-paginated':
                $type = $_GET['type'] ?? 'penduduk';
                $page = max(1, (int)($_GET['page'] ?? 1));
                $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
                $search = trim($_GET['search'] ?? '');
                
                $result = $this->getCombinedDataPaginated($type, $page, $limit, $search);
                return $this->jsonResponse(true, 'Success', $result);
                
            case 'all-desa-data':
                $type = $_GET['type'] ?? 'all';
                $desa_id = $_GET['desa_id'] ?? null;
                $result = $this->getAllDesaDataAPI($type, $desa_id);
                return $this->jsonResponse(true, 'Success', $result);
                
            case 'desa-detail':
                $id_desa = $_GET['id'] ?? null;
                $result = $this->getDesaDetailAPI($id_desa);
                return $this->jsonResponse(true, 'Success', $result);
                
            case 'data-completeness':
                $result = $this->getDataCompleteness();
                return $this->jsonResponse(true, 'Success', $result);
                
            case 'activity-log':
                $result = $this->getActivityLog();
                return $this->jsonResponse(true, 'Success', $result);
                
            case 'performance-metrics':
                $result = $this->getPerformanceMetrics();
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