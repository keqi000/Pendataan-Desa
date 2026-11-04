<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/session.php';
require_once __DIR__ . '/../../../../database/queries.php';

class DetailAnalisisFunctions {
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
    
    public function getFilteredDesa($selected_desa, $all_desa) {
        if ($selected_desa === 'all') {
            return $all_desa;
        } else {
            return array_filter($all_desa, function ($desa) use ($selected_desa) {
                return $desa['id_desa'] == $selected_desa;
            });
        }
    }
    
    public function getKKPerempuanData($filtered_desa) {
        $kk_perempuan_data = [];
        
        foreach ($filtered_desa as $desa) {
            $id_desa = $desa['id_desa'];
            $keluarga = $this->queries->getKeluargaByDesa($id_desa);
            $penduduk = $this->queries->getPendudukByDesa($id_desa);
            
            $query = "SELECT nik FROM warga_miskin WHERE id_desa = :id_desa";
            $stmt = $this->queries->db->prepare($query);
            $stmt->bindParam(':id_desa', $id_desa);
            $stmt->execute();
            $warga_miskin_niks = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'nik');
            
            foreach ($keluarga as $kk) {
                $kepala_keluarga = null;
                $anak_sekolah = [];
                $is_kk_miskin = false;
                
                foreach ($penduduk as $p) {
                    if ($p['id_keluarga'] == $kk['id_keluarga']) {
                        if ($p['nama_lengkap'] == $kk['nama_kepala_keluarga']) {
                            $kepala_keluarga = $p;
                            if (in_array($p['nik'], $warga_miskin_niks)) {
                                $is_kk_miskin = true;
                            }
                        }
                        if ($p['usia'] >= 7 && $p['usia'] <= 18) {
                            $anak_sekolah[] = $p;
                        }
                    }
                }
                
                if ($kepala_keluarga && $kepala_keluarga['jenis_kelamin'] == 'P' && count($anak_sekolah) > 0) {
                    $kk_perempuan_data[] = [
                        'kepala_keluarga' => $kepala_keluarga,
                        'anak_sekolah' => $anak_sekolah,
                        'is_miskin' => $is_kk_miskin,
                        'desa' => $desa['nama_desa']
                    ];
                }
            }
        }
        
        return $kk_perempuan_data;
    }
    
    public function getProduktifData($filtered_desa) {
        $produktif_detail = ['sd' => 0, 'smp' => 0, 'sma' => 0, 'diploma' => 0, 'sarjana' => 0, 'laki' => 0, 'perempuan' => 0];
        $produktif_data = [];
        
        foreach ($filtered_desa as $desa) {
            $id_desa = $desa['id_desa'];
            $penduduk = $this->queries->getPendudukByDesa($id_desa);
            $mata_pencaharian = $this->queries->getMataPencaharianByDesa($id_desa);
            
            foreach ($penduduk as $p) {
                if ($p['usia'] >= 18 && $p['usia'] <= 64) {
                    $punya_kerja_tetap = false;
                    foreach ($mata_pencaharian as $mp) {
                        if ($mp['nik'] == $p['nik'] && $mp['status_pekerjaan'] == 'tetap') {
                            $punya_kerja_tetap = true;
                            break;
                        }
                    }
                    
                    if (!$punya_kerja_tetap) {
                        $produktif_data[] = ['penduduk' => $p, 'desa' => $desa['nama_desa']];
                        
                        switch ($p['pendidikan_terakhir']) {
                            case 'SD': $produktif_detail['sd']++; break;
                            case 'SMP': $produktif_detail['smp']++; break;
                            case 'SMA': $produktif_detail['sma']++; break;
                            case 'D1': case 'D2': case 'D3': case 'D4': $produktif_detail['diploma']++; break;
                            case 'S1': case 'S2': case 'S3': $produktif_detail['sarjana']++; break;
                        }
                        
                        if ($p['jenis_kelamin'] == 'L') $produktif_detail['laki']++;
                        else $produktif_detail['perempuan']++;
                    }
                }
            }
        }
        
        return ['data' => $produktif_data, 'detail' => $produktif_detail];
    }
    
    public function getLansiaData($filtered_desa) {
        $lansia_data = [];
        
        foreach ($filtered_desa as $desa) {
            $id_desa = $desa['id_desa'];
            $keluarga = $this->queries->getKeluargaByDesa($id_desa);
            $penduduk = $this->queries->getPendudukByDesa($id_desa);
            
            foreach ($keluarga as $kk) {
                $kepala_keluarga = null;
                $anak_sekolah = [];
                
                foreach ($penduduk as $p) {
                    if ($p['id_keluarga'] == $kk['id_keluarga']) {
                        if ($p['nama_lengkap'] == $kk['nama_kepala_keluarga']) {
                            $kepala_keluarga = $p;
                        }
                        if ($p['usia'] >= 7 && $p['usia'] <= 18) {
                            $anak_sekolah[] = $p;
                        }
                    }
                }
                
                if ($kepala_keluarga && $kepala_keluarga['usia'] > 65 && count($anak_sekolah) > 0) {
                    $lansia_data[] = [
                        'kepala_keluarga' => $kepala_keluarga,
                        'anak_sekolah' => $anak_sekolah,
                        'desa' => $desa['nama_desa']
                    ];
                }
            }
        }
        
        return $lansia_data;
    }
    
    public function getPetaniData($filtered_desa) {
        $petani_data = [];
        $petani_breakdown = ['0.1-0.5' => 0, '0.5-1' => 0];
        $komoditas_detail = [];
        
        foreach ($filtered_desa as $desa) {
            $id_desa = $desa['id_desa'];
            
            $query = "SELECT p.*, pd.nama_lengkap 
                     FROM pertanian p 
                     LEFT JOIN penduduk pd ON pd.nik = p.nik_petani
                     WHERE p.id_desa = :id_desa AND p.luas_lahan < 1 AND p.bantuan_pertanian = 'tidak_ada'";
            $stmt = $this->queries->db->prepare($query);
            $stmt->bindParam(':id_desa', $id_desa);
            $stmt->execute();
            $petani_kecil = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($petani_kecil as $pk) {
                $petani_data[] = [
                    'nama_petani' => $pk['nama_lengkap'] ?? 'Petani',
                    'desa' => $desa['nama_desa'],
                    'luas_lahan' => $pk['luas_lahan'],
                    'jenis_komoditas' => $pk['jenis_komoditas'],
                    'pendapatan_per_musim' => $pk['pendapatan_per_musim'] ?? 0,
                    'penghasilan_perbulan' => ($pk['pendapatan_per_musim'] ?? 0) / 3
                ];
                
                if ($pk['luas_lahan'] <= 0.5) $petani_breakdown['0.1-0.5']++;
                else $petani_breakdown['0.5-1']++;
                
                $komoditas = $pk['jenis_komoditas'];
                $komoditas_detail[$komoditas] = ($komoditas_detail[$komoditas] ?? 0) + 1;
            }
        }
        
        return ['data' => $petani_data, 'breakdown' => $petani_breakdown, 'komoditas' => $komoditas_detail];
    }
    
    public function getKorelasiPendidikanData($filtered_desa) {
        $pendidikan_pekerjaan_data = [];
        
        foreach ($filtered_desa as $desa) {
            $id_desa = $desa['id_desa'];
            $mata_pencaharian = $this->queries->getMataPencaharianByDesa($id_desa);
            $penduduk = $this->queries->getPendudukByDesa($id_desa);
            
            $nik_pendidikan = [];
            foreach ($penduduk as $p) {
                $nik_pendidikan[$p['nik']] = $p;
            }
            
            foreach ($mata_pencaharian as $mp) {
                if (isset($nik_pendidikan[$mp['nik']])) {
                    $penduduk_data = $nik_pendidikan[$mp['nik']];
                    $pendidikan_pekerjaan_data[] = [
                        'penduduk' => $penduduk_data,
                        'pekerjaan' => $mp,
                        'desa' => $desa['nama_desa']
                    ];
                }
            }
        }
        
        return $pendidikan_pekerjaan_data;
    }
    
    public function getKesenjanganEkonomiData($all_desa) {
        $kesenjangan_data = [];
        
        foreach ($all_desa as $desa) {
            $id_desa = $desa['id_desa'];
            $mata_pencaharian = $this->queries->getMataPencaharianByDesa($id_desa);
            
            $total_pendapatan = 0;
            $jumlah_pekerja = 0;
            
            foreach ($mata_pencaharian as $mp) {
                $total_pendapatan += $mp['penghasilan_perbulan'] ?? 0;
                $jumlah_pekerja++;
            }
            
            if ($jumlah_pekerja > 0) {
                $rata_pendapatan = round($total_pendapatan / $jumlah_pekerja);
                $kesenjangan_data[] = [
                    'nama_desa' => $desa['nama_desa'],
                    'total_pendapatan' => $total_pendapatan,
                    'jumlah_pekerja' => $jumlah_pekerja,
                    'rata_pendapatan' => $rata_pendapatan
                ];
            }
        }
        
        usort($kesenjangan_data, function($a, $b) {
            return $b['rata_pendapatan'] <=> $a['rata_pendapatan'];
        });
        
        return $kesenjangan_data;
    }
    
    public function getDistribusiPendapatanData($filtered_desa) {
        $distribusi_data = [];
        $total_pertanian_all = 0;
        $total_non_pertanian_all = 0;
        
        foreach ($filtered_desa as $desa) {
            $id_desa = $desa['id_desa'];
            $mata_pencaharian = $this->queries->getMataPencaharianByDesa($id_desa);
            
            $pendapatan_pertanian = 0;
            $pendapatan_non_pertanian = 0;
            
            foreach ($mata_pencaharian as $mp) {
                $penghasilan = $mp['penghasilan_perbulan'] ?? 0;
                if ($mp['sektor_pekerjaan'] === 'pertanian') {
                    $pendapatan_pertanian += $penghasilan;
                } else {
                    $pendapatan_non_pertanian += $penghasilan;
                }
            }
            
            $total_pertanian_all += $pendapatan_pertanian;
            $total_non_pertanian_all += $pendapatan_non_pertanian;
            
            $distribusi_data[] = [
                'nama_desa' => $desa['nama_desa'],
                'pendapatan_pertanian' => $pendapatan_pertanian,
                'pendapatan_non_pertanian' => $pendapatan_non_pertanian,
                'total_pendapatan' => $pendapatan_pertanian + $pendapatan_non_pertanian
            ];
        }
        
        return ['data' => $distribusi_data, 'total_pertanian' => $total_pertanian_all, 'total_non_pertanian' => $total_non_pertanian_all];
    }
    
    public function getNonPertanianData($filtered_desa) {
        $non_pertanian_data = [];
        $non_pertanian_detail = [];
        
        foreach ($filtered_desa as $desa) {
            $id_desa = $desa['id_desa'];
            $mata_pencaharian = $this->queries->getMataPencaharianByDesa($id_desa);
            $penduduk = $this->queries->getPendudukByDesa($id_desa);
            
            $nik_penduduk = [];
            foreach ($penduduk as $p) {
                $nik_penduduk[$p['nik']] = $p;
            }
            
            foreach ($mata_pencaharian as $mp) {
                if ($mp['sektor_pekerjaan'] !== 'pertanian' && isset($nik_penduduk[$mp['nik']])) {
                    $sektor = ucfirst($mp['sektor_pekerjaan']);
                    $non_pertanian_detail[$sektor] = ($non_pertanian_detail[$sektor] ?? 0) + 1;
                    
                    $non_pertanian_data[] = [
                        'penduduk' => $nik_penduduk[$mp['nik']],
                        'pekerjaan' => $mp,
                        'desa' => $desa['nama_desa']
                    ];
                }
            }
        }
        
        return ['data' => $non_pertanian_data, 'detail' => $non_pertanian_detail];
    }
    
    public function getRasioKetergantunganData($filtered_desa) {
        $rasio_data = [];
        
        foreach ($filtered_desa as $desa) {
            $id_desa = $desa['id_desa'];
            $keluarga = $this->queries->getKeluargaByDesa($id_desa);
            $penduduk = $this->queries->getPendudukByDesa($id_desa);
            
            foreach ($keluarga as $kk) {
                $yang_bekerja = 0;
                $total_anggota = 0;
                $kepala_keluarga = null;
                
                foreach ($penduduk as $p) {
                    if ($p['id_keluarga'] == $kk['id_keluarga']) {
                        $total_anggota++;
                        if ($p['nama_lengkap'] == $kk['nama_kepala_keluarga']) {
                            $kepala_keluarga = $p;
                        }
                        if ($p['pekerjaan'] && $p['pekerjaan'] != 'Belum Bekerja') {
                            $yang_bekerja++;
                        }
                    }
                }
                
                $tanggungan = $total_anggota - $yang_bekerja;
                
                if ($kepala_keluarga) {
                    $rasio_data[] = [
                        'kepala_keluarga' => $kepala_keluarga['nama_lengkap'],
                        'desa' => $desa['nama_desa'],
                        'total_anggota' => $total_anggota,
                        'anggota_bekerja' => $yang_bekerja,
                        'tanggungan' => $tanggungan
                    ];
                }
            }
        }
        
        return $rasio_data;
    }
    
    public function getPendidikanTinggiMiskinData($filtered_desa) {
        $pendidikan_tinggi_miskin_data = [];
        
        foreach ($filtered_desa as $desa) {
            $id_desa = $desa['id_desa'];
            $penduduk = $this->queries->getPendudukByDesa($id_desa);
            $keluarga = $this->queries->getKeluargaByDesa($id_desa);
            
            $query = "SELECT * FROM warga_miskin WHERE id_desa = :id_desa";
            $stmt = $this->queries->db->prepare($query);
            $stmt->bindParam(':id_desa', $id_desa);
            $stmt->execute();
            $warga_miskin = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($warga_miskin as $wm) {
                $penduduk_miskin = null;
                foreach ($penduduk as $p) {
                    if ($p['nik'] == $wm['nik']) {
                        $penduduk_miskin = $p;
                        break;
                    }
                }
                
                if ($penduduk_miskin) {
                    $is_kepala_keluarga = false;
                    foreach ($keluarga as $kk) {
                        if ($kk['nama_kepala_keluarga'] == $penduduk_miskin['nama_lengkap'] && 
                            $penduduk_miskin['id_keluarga'] == $kk['id_keluarga']) {
                            $is_kepala_keluarga = true;
                            break;
                        }
                    }
                    
                    if ($is_kepala_keluarga && in_array($penduduk_miskin['pendidikan_terakhir'], ['D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'])) {
                        $faktor = (!$penduduk_miskin['pekerjaan'] || $penduduk_miskin['pekerjaan'] == 'Belum Bekerja') ? 
                                 'tidak bekerja' : 'ketidaksesuaian tempat kerja';
                        
                        $pendidikan_tinggi_miskin_data[] = [
                            'penduduk' => $penduduk_miskin,
                            'desa' => $desa['nama_desa'],
                            'faktor_penyebab' => $faktor
                        ];
                    }
                }
            }
        }
        
        return $pendidikan_tinggi_miskin_data;
    }
    
    public function getAnakTidakSekolahData($filtered_desa) {
        $anak_tidak_sekolah_data = [];
        
        foreach ($filtered_desa as $desa) {
            $id_desa = $desa['id_desa'];
            $penduduk = $this->queries->getPendudukByDesa($id_desa);
            $keluarga = $this->queries->getKeluargaByDesa($id_desa);
            
            $query = "SELECT * FROM warga_miskin WHERE id_desa = :id_desa";
            $stmt = $this->queries->db->prepare($query);
            $stmt->bindParam(':id_desa', $id_desa);
            $stmt->execute();
            $warga_miskin = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($keluarga as $kk) {
                $kepala_keluarga = null;
                foreach ($penduduk as $p) {
                    if ($p['id_keluarga'] == $kk['id_keluarga'] && $p['nama_lengkap'] == $kk['nama_kepala_keluarga']) {
                        $kepala_keluarga = $p;
                        break;
                    }
                }
                
                if ($kepala_keluarga) {
                    $is_kk_miskin = false;
                    foreach ($warga_miskin as $wm) {
                        if ($kepala_keluarga['nik'] == $wm['nik']) {
                            $is_kk_miskin = true;
                            break;
                        }
                    }
                    
                    if ($is_kk_miskin) {
                        foreach ($penduduk as $p) {
                            if ($p['id_keluarga'] == $kk['id_keluarga'] && 
                                $p['usia'] > 6 && 
                                $p['pekerjaan'] != 'Pelajar' && 
                                $p['pendidikan_terakhir'] == 'Tidak Sekolah') {
                                $anak_tidak_sekolah_data[] = ['penduduk' => $p, 'desa' => $desa['nama_desa'], 'status' => 'miskin'];
                            }
                        }
                    }
                }
            }
        }
        
        return $anak_tidak_sekolah_data;
    }
    
    public function getAnakPetaniData($filtered_desa) {
        $anak_petani_data = [];
        
        foreach ($filtered_desa as $desa) {
            $id_desa = $desa['id_desa'];
            $penduduk = $this->queries->getPendudukByDesa($id_desa);
            $keluarga = $this->queries->getKeluargaByDesa($id_desa);
            
            foreach ($keluarga as $kk) {
                $kepala_keluarga = null;
                foreach ($penduduk as $p) {
                    if ($p['id_keluarga'] == $kk['id_keluarga'] && $p['nama_lengkap'] == $kk['nama_kepala_keluarga']) {
                        $kepala_keluarga = $p;
                        break;
                    }
                }
                
                if ($kepala_keluarga && $kepala_keluarga['pekerjaan'] == 'Petani') {
                    foreach ($penduduk as $p) {
                        if ($p['id_keluarga'] == $kk['id_keluarga'] && 
                            $p['nama_lengkap'] != $kk['nama_kepala_keluarga'] && 
                            in_array($p['pendidikan_terakhir'], ['D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'])) {
                            $anak_petani_data[] = ['penduduk' => $p, 'desa' => $desa['nama_desa'], 'parent' => $kepala_keluarga];
                        }
                    }
                }
            }
        }
        
        return $anak_petani_data;
    }
    
    public function getIntegrasiData($filtered_desa) {
        return [
            'kk_miskin' => $this->getKKMiskinData($filtered_desa)
        ];
    }
    
    // INTEGRASI TAB - PAGINATED METHODS
    public function getKKMiskinDataPaginated($filtered_desa, $page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        // Build desa condition
        $desa_ids = array_column($filtered_desa, 'id_desa');
        if (empty($desa_ids)) {
            return ['data' => [], 'total' => 0, 'page' => (int)$page, 'limit' => (int)$limit, 'total_pages' => 0];
        }
        $desa_placeholders = str_repeat('?,', count($desa_ids) - 1) . '?';
        
        // Build search condition
        $search_condition = '';
        $search_params = [];
        if ($search) {
            $search_condition = " AND (k.nama_kepala_keluarga LIKE ? OR d.nama_desa LIKE ?)";
            $search_params = ['%' . $search . '%', '%' . $search . '%'];
        }
        
        // Count total records
        $count_query = "SELECT COUNT(DISTINCT k.id_keluarga) as total
                       FROM keluarga k
                       JOIN penduduk p_kepala ON p_kepala.id_keluarga = k.id_keluarga
                           AND p_kepala.nama_lengkap = k.nama_kepala_keluarga
                       JOIN warga_miskin wm ON wm.nik = p_kepala.nik
                       JOIN desa d ON d.id_desa = k.id_desa
                       WHERE k.id_desa IN ($desa_placeholders)
                       AND EXISTS (
                           SELECT 1 FROM penduduk p3
                           WHERE p3.id_keluarga = k.id_keluarga
                           AND p3.pekerjaan = 'Pelajar'
                       )
                       $search_condition";
        
        $count_stmt = $this->queries->db->prepare($count_query);
        $count_params = array_merge($desa_ids, $search_params);
        $count_stmt->execute($count_params);
        $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated data
        $data_query = "SELECT k.*, d.nama_desa,
                      p_kepala.nama_lengkap as kepala_nama,
                      (SELECT GROUP_CONCAT(CONCAT(p4.nama_lengkap, ':', p4.usia) SEPARATOR '|')
                       FROM penduduk p4
                       WHERE p4.id_keluarga = k.id_keluarga
                       AND p4.pekerjaan = 'Pelajar') as anak_sekolah_data
                      FROM keluarga k
                      JOIN penduduk p_kepala ON p_kepala.id_keluarga = k.id_keluarga
                          AND p_kepala.nama_lengkap = k.nama_kepala_keluarga
                      JOIN warga_miskin wm ON wm.nik = p_kepala.nik
                      JOIN desa d ON d.id_desa = k.id_desa
                      WHERE k.id_desa IN ($desa_placeholders)
                      AND EXISTS (
                          SELECT 1 FROM penduduk p3
                          WHERE p3.id_keluarga = k.id_keluarga
                          AND p3.pekerjaan = 'Pelajar'
                      )
                      $search_condition
                      ORDER BY k.nama_kepala_keluarga
                      LIMIT $limit OFFSET $offset";
        
        $data_stmt = $this->queries->db->prepare($data_query);
        $data_params = array_merge($desa_ids, $search_params);
        $data_stmt->execute($data_params);
        $results = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $kk_miskin_data = [];
        foreach ($results as $row) {
            $anak_sekolah = [];
            if ($row['anak_sekolah_data']) {
                $anak_data = explode('|', $row['anak_sekolah_data']);
                foreach ($anak_data as $anak) {
                    $anak_info = explode(':', $anak);
                    $anak_sekolah[] = [
                        'nama_lengkap' => $anak_info[0] ?? '',
                        'usia' => $anak_info[1] ?? 0
                    ];
                }
            }
            
            $kk_miskin_data[] = [
                'kepala_keluarga' => [
                    'nama_lengkap' => $row['kepala_nama'] ?? $row['nama_kepala_keluarga']
                ],
                'anak_sekolah' => $anak_sekolah,
                'desa' => $row['nama_desa']
            ];
        }
        
        return [
            'data' => $kk_miskin_data,
            'total' => $total_count,
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total_pages' => (int)ceil($total_count / $limit)
        ];
    }
    
    public function getRasioKetergantunganDataPaginated($filtered_desa, $page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        // Build desa condition
        $desa_ids = array_column($filtered_desa, 'id_desa');
        if (empty($desa_ids)) {
            return ['data' => [], 'total' => 0, 'page' => (int)$page, 'limit' => (int)$limit, 'total_pages' => 0];
        }
        $desa_placeholders = str_repeat('?,', count($desa_ids) - 1) . '?';
        
        // Build search condition
        $search_condition = '';
        $search_params = [];
        if ($search) {
            $search_condition = " AND (k.nama_kepala_keluarga LIKE ? OR d.nama_desa LIKE ?)";
            $search_params = ['%' . $search . '%', '%' . $search . '%'];
        }
        
        // Count total records
        $count_query = "SELECT COUNT(DISTINCT k.id_keluarga) as total
                       FROM keluarga k
                       JOIN desa d ON d.id_desa = k.id_desa
                       WHERE k.id_desa IN ($desa_placeholders)
                       $search_condition";
        
        $count_stmt = $this->queries->db->prepare($count_query);
        $count_params = array_merge($desa_ids, $search_params);
        $count_stmt->execute($count_params);
        $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated data with calculations
        $data_query = "SELECT k.*, d.nama_desa,
                      (SELECT COUNT(*) FROM penduduk p WHERE p.id_keluarga = k.id_keluarga) as total_anggota,
                      (SELECT COUNT(*) FROM penduduk p 
                       WHERE p.id_keluarga = k.id_keluarga 
                       AND p.pekerjaan IS NOT NULL 
                       AND p.pekerjaan != 'Belum Bekerja'
                       AND p.pekerjaan != 'Pelajar') as anggota_bekerja
                      FROM keluarga k
                      JOIN desa d ON d.id_desa = k.id_desa
                      WHERE k.id_desa IN ($desa_placeholders)
                      $search_condition
                      ORDER BY k.nama_kepala_keluarga
                      LIMIT $limit OFFSET $offset";
        
        $data_stmt = $this->queries->db->prepare($data_query);
        $data_params = array_merge($desa_ids, $search_params);
        $data_stmt->execute($data_params);
        $results = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $rasio_data = [];
        foreach ($results as $row) {
            $tanggungan = $row['total_anggota'] - $row['anggota_bekerja'];
            $rasio_data[] = [
                'kepala_keluarga' => $row['nama_kepala_keluarga'],
                'desa' => $row['nama_desa'],
                'total_anggota' => $row['total_anggota'],
                'anggota_bekerja' => $row['anggota_bekerja'],
                'tanggungan' => $tanggungan
            ];
        }
        
        return [
            'data' => $rasio_data,
            'total' => $total_count,
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total_pages' => (int)ceil($total_count / $limit)
        ];
    }
    
    public function getPendidikanTinggiMiskinDataPaginated($filtered_desa, $page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        // Build desa condition
        $desa_ids = array_column($filtered_desa, 'id_desa');
        if (empty($desa_ids)) {
            return ['data' => [], 'total' => 0, 'page' => (int)$page, 'limit' => (int)$limit, 'total_pages' => 0];
        }
        $desa_placeholders = str_repeat('?,', count($desa_ids) - 1) . '?';
        
        // Build search condition
        $search_condition = '';
        $search_params = [];
        if ($search) {
            $search_condition = " AND (p.nama_lengkap LIKE ? OR d.nama_desa LIKE ?)";
            $search_params = ['%' . $search . '%', '%' . $search . '%'];
        }
        
        // Count total records
        $count_query = "SELECT COUNT(*) as total
                       FROM warga_miskin wm
                       JOIN penduduk p ON p.nik = wm.nik
                       JOIN keluarga k ON k.id_keluarga = p.id_keluarga
                       JOIN desa d ON d.id_desa = wm.id_desa
                       WHERE wm.id_desa IN ($desa_placeholders)
                       AND p.nama_lengkap = k.nama_kepala_keluarga
                       AND p.pendidikan_terakhir IN ('D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3')
                       $search_condition";
        
        $count_stmt = $this->queries->db->prepare($count_query);
        $count_params = array_merge($desa_ids, $search_params);
        $count_stmt->execute($count_params);
        $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated data
        $data_query = "SELECT p.*, d.nama_desa,
                      CASE 
                          WHEN p.pekerjaan IS NULL OR p.pekerjaan = 'Belum Bekerja' THEN 'tidak bekerja'
                          ELSE 'ketidaksesuaian tempat kerja'
                      END as faktor_penyebab
                      FROM warga_miskin wm
                      JOIN penduduk p ON p.nik = wm.nik
                      JOIN keluarga k ON k.id_keluarga = p.id_keluarga
                      JOIN desa d ON d.id_desa = wm.id_desa
                      WHERE wm.id_desa IN ($desa_placeholders)
                      AND p.nama_lengkap = k.nama_kepala_keluarga
                      AND p.pendidikan_terakhir IN ('D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3')
                      $search_condition
                      ORDER BY p.nama_lengkap
                      LIMIT $limit OFFSET $offset";
        
        $data_stmt = $this->queries->db->prepare($data_query);
        $data_params = array_merge($desa_ids, $search_params);
        $data_stmt->execute($data_params);
        $results = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $pendidikan_tinggi_data = [];
        foreach ($results as $row) {
            $pendidikan_tinggi_data[] = [
                'penduduk' => $row,
                'desa' => $row['nama_desa'],
                'faktor_penyebab' => $row['faktor_penyebab']
            ];
        }
        
        return [
            'data' => $pendidikan_tinggi_data,
            'total' => $total_count,
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total_pages' => (int)ceil($total_count / $limit)
        ];
    }
    
    public function getAnakTidakSekolahDataPaginated($filtered_desa, $page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        // Build desa condition
        $desa_ids = array_column($filtered_desa, 'id_desa');
        if (empty($desa_ids)) {
            return ['data' => [], 'total' => 0, 'page' => (int)$page, 'limit' => (int)$limit, 'total_pages' => 0];
        }
        $desa_placeholders = str_repeat('?,', count($desa_ids) - 1) . '?';
        
        // Build search condition
        $search_condition = '';
        $search_params = [];
        if ($search) {
            $search_condition = " AND (p.nama_lengkap LIKE ? OR d.nama_desa LIKE ?)";
            $search_params = ['%' . $search . '%', '%' . $search . '%'];
        }
        
        // Count total records
        $count_query = "SELECT COUNT(*) as total
                       FROM penduduk p
                       JOIN keluarga k ON k.id_keluarga = p.id_keluarga
                       JOIN desa d ON d.id_desa = p.id_desa
                       WHERE p.id_desa IN ($desa_placeholders)
                       AND p.usia BETWEEN 6 AND 19
                       AND p.pekerjaan != 'Pelajar'
                       AND p.nama_lengkap != k.nama_kepala_keluarga
                       AND p.pendidikan_terakhir NOT IN ('SMA', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3')
                       AND EXISTS (
                           SELECT 1 FROM penduduk p2
                           JOIN warga_miskin wm ON wm.nik = p2.nik
                           WHERE p2.id_keluarga = k.id_keluarga
                           AND p2.nama_lengkap = k.nama_kepala_keluarga
                       )
                       $search_condition";
        
        $count_stmt = $this->queries->db->prepare($count_query);
        $count_params = array_merge($desa_ids, $search_params);
        $count_stmt->execute($count_params);
        $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated data
        $data_query = "SELECT p.*, d.nama_desa
                      FROM penduduk p
                      JOIN keluarga k ON k.id_keluarga = p.id_keluarga
                      JOIN desa d ON d.id_desa = p.id_desa
                      WHERE p.id_desa IN ($desa_placeholders)
                      AND p.usia BETWEEN 6 AND 19
                      AND p.pekerjaan != 'Pelajar'
                      AND p.nama_lengkap != k.nama_kepala_keluarga
                      AND p.pendidikan_terakhir NOT IN ('SMA', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3')
                      AND EXISTS (
                          SELECT 1 FROM penduduk p2
                          JOIN warga_miskin wm ON wm.nik = p2.nik
                          WHERE p2.id_keluarga = k.id_keluarga
                          AND p2.nama_lengkap = k.nama_kepala_keluarga
                      )
                      $search_condition
                      ORDER BY p.nama_lengkap
                      LIMIT $limit OFFSET $offset";
        
        $data_stmt = $this->queries->db->prepare($data_query);
        $data_params = array_merge($desa_ids, $search_params);
        $data_stmt->execute($data_params);
        $results = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $anak_tidak_sekolah_data = [];
        foreach ($results as $row) {
            $anak_tidak_sekolah_data[] = [
                'penduduk' => $row,
                'desa' => $row['nama_desa'],
                'status' => 'miskin'
            ];
        }
        
        return [
            'data' => $anak_tidak_sekolah_data,
            'total' => $total_count,
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total_pages' => (int)ceil($total_count / $limit)
        ];
    }
    
    public function getAnakPetaniDataPaginated($filtered_desa, $page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        // Build desa condition
        $desa_ids = array_column($filtered_desa, 'id_desa');
        if (empty($desa_ids)) {
            return ['data' => [], 'total' => 0, 'page' => (int)$page, 'limit' => (int)$limit, 'total_pages' => 0];
        }
        $desa_placeholders = str_repeat('?,', count($desa_ids) - 1) . '?';
        
        // Build search condition
        $search_condition = '';
        $search_params = [];
        if ($search) {
            $search_condition = " AND (p.nama_lengkap LIKE ? OR d.nama_desa LIKE ?)";
            $search_params = ['%' . $search . '%', '%' . $search . '%'];
        }
        
        // Count total records
        $count_query = "SELECT COUNT(*) as total
                       FROM penduduk p
                       JOIN keluarga k ON k.id_keluarga = p.id_keluarga
                       JOIN penduduk p_kepala ON p_kepala.id_keluarga = k.id_keluarga AND p_kepala.nama_lengkap = k.nama_kepala_keluarga
                       JOIN desa d ON d.id_desa = p.id_desa
                       WHERE p.id_desa IN ($desa_placeholders)
                       AND p.nama_lengkap != k.nama_kepala_keluarga
                       AND p_kepala.pekerjaan = 'Petani'
                       AND p.pendidikan_terakhir IN ('D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3')
                       $search_condition";
        
        $count_stmt = $this->queries->db->prepare($count_query);
        $count_params = array_merge($desa_ids, $search_params);
        $count_stmt->execute($count_params);
        $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated data
        $data_query = "SELECT p.*, d.nama_desa, p_kepala.nama_lengkap as nama_kepala, p_kepala.pekerjaan as pekerjaan_kepala
                      FROM penduduk p
                      JOIN keluarga k ON k.id_keluarga = p.id_keluarga
                      JOIN penduduk p_kepala ON p_kepala.id_keluarga = k.id_keluarga AND p_kepala.nama_lengkap = k.nama_kepala_keluarga
                      JOIN desa d ON d.id_desa = p.id_desa
                      WHERE p.id_desa IN ($desa_placeholders)
                      AND p.nama_lengkap != k.nama_kepala_keluarga
                      AND p_kepala.pekerjaan = 'Petani'
                      AND p.pendidikan_terakhir IN ('D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3')
                      $search_condition
                      ORDER BY p.nama_lengkap
                      LIMIT $limit OFFSET $offset";
        
        $data_stmt = $this->queries->db->prepare($data_query);
        $data_params = array_merge($desa_ids, $search_params);
        $data_stmt->execute($data_params);
        $results = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $anak_petani_data = [];
        foreach ($results as $row) {
            $anak_petani_data[] = [
                'penduduk' => $row,
                'desa' => $row['nama_desa'],
                'parent' => [
                    'nama_lengkap' => $row['nama_kepala'],
                    'pekerjaan' => $row['pekerjaan_kepala']
                ]
            ];
        }
        
        return [
            'data' => $anak_petani_data,
            'total' => $total_count,
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total_pages' => (int)ceil($total_count / $limit)
        ];
    }
    
    // SPASIAL TAB - PAGINATED METHODS
    public function getSpasialDataPaginated($filtered_desa, $page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        $spasial_data = [];
        
        foreach ($filtered_desa as $desa) {
            if ($search && stripos($desa['nama_desa'], $search) === false) {
                continue;
            }
            
            $id_desa = $desa['id_desa'];
            $fasilitas = $this->queries->getFasilitasPendidikan($id_desa);
            
            // Hanya tampilkan desa yang tidak punya fasilitas pendidikan
            if (count($fasilitas) > 0) continue;
            
            $penduduk = $this->queries->getPendudukByDesa($id_desa);
            
            $spasial_data[] = [
                'nama_desa' => $desa['nama_desa'],
                'total_penduduk' => count($penduduk),
                'fasilitas_pendidikan' => 0
            ];
        }
        
        $paginated_data = array_slice($spasial_data, $offset, $limit);
        
        return [
            'data' => $paginated_data,
            'total' => count($spasial_data),
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total_pages' => (int)ceil(count($spasial_data) / $limit)
        ];
    }
    
    public function getAksesibilitasDataPaginated($filtered_desa, $page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        $aksesibilitas_data = [];
        
        foreach ($filtered_desa as $desa) {
            if ($search && stripos($desa['nama_desa'], $search) === false) {
                continue;
            }
            
            $id_desa = $desa['id_desa'];
            $jalan = $this->queries->getInfrastrukturJalan($id_desa);
            
            $jalan_baik = 0;
            foreach ($jalan as $j) {
                if ($j['kondisi_jalan'] == 'baik') {
                    $jalan_baik++;
                }
            }
            $aksesibilitas = count($jalan) > 0 ? ($jalan_baik / count($jalan)) * 100 : 0;
            
            $aksesibilitas_data[] = [
                'nama_desa' => $desa['nama_desa'],
                'jalan_baik' => $jalan_baik,
                'total_jalan' => count($jalan),
                'aksesibilitas' => round($aksesibilitas, 1)
            ];
        }
        
        $paginated_data = array_slice($aksesibilitas_data, $offset, $limit);
        
        return [
            'data' => $paginated_data,
            'total' => count($aksesibilitas_data),
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total_pages' => (int)ceil(count($aksesibilitas_data) / $limit)
        ];
    }
    
    public function getPotensiPengembanganDataPaginated($filtered_desa, $page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        $potensi_pengembangan = [];
        
        foreach ($filtered_desa as $desa) {
            if ($search && stripos($desa['nama_desa'], $search) === false) {
                continue;
            }
            
            $id_desa = $desa['id_desa'];
            
            $query = "SELECT 
                        COUNT(*) as total_penduduk,
                        SUM(CASE WHEN jenis_kelamin = 'L' THEN 1 ELSE 0 END) as total_laki,
                        SUM(CASE WHEN jenis_kelamin = 'P' THEN 1 ELSE 0 END) as total_perempuan
                      FROM penduduk WHERE id_desa = :id_desa";
            $stmt = $this->queries->db->prepare($query);
            $stmt->bindParam(':id_desa', $id_desa);
            $stmt->execute();
            $penduduk_stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $query_kk = "SELECT COUNT(*) as total_kk FROM keluarga WHERE id_desa = :id_desa";
            $stmt_kk = $this->queries->db->prepare($query_kk);
            $stmt_kk->bindParam(':id_desa', $id_desa);
            $stmt_kk->execute();
            $kk_stats = $stmt_kk->fetch(PDO::FETCH_ASSOC);
            
            $potensi_pengembangan[] = [
                'nama_desa'       => $desa['nama_desa'],
                'total_penduduk'  => (int)($penduduk_stats['total_penduduk'] ?? 0),
                'total_laki'      => (int)($penduduk_stats['total_laki'] ?? 0),
                'total_perempuan' => (int)($penduduk_stats['total_perempuan'] ?? 0),
                'total_kk'        => (int)($kk_stats['total_kk'] ?? 0)
            ];
        }
        
        // Urutkan berdasarkan jumlah penduduk terbanyak
        usort($potensi_pengembangan, function ($a, $b) {
            return $b['total_penduduk'] <=> $a['total_penduduk'];
        });
        
        $paginated_data = array_slice($potensi_pengembangan, $offset, $limit);
        
        return [
            'data'        => $paginated_data,
            'total'       => count($potensi_pengembangan),
            'page'        => (int)$page,
            'limit'       => (int)$limit,
            'total_pages' => (int)ceil(count($potensi_pengembangan) / $limit)
        ];
    }
    
    public function getKKMiskinData($filtered_desa) {
        $kk_miskin_data = [];
        
        foreach ($filtered_desa as $desa) {
            $id_desa = $desa['id_desa'];
            $penduduk = $this->queries->getPendudukByDesa($id_desa);
            $keluarga = $this->queries->getKeluargaByDesa($id_desa);
            
            $query = "SELECT nik FROM warga_miskin WHERE id_desa = :id_desa";
            $stmt = $this->queries->db->prepare($query);
            $stmt->bindParam(':id_desa', $id_desa);
            $stmt->execute();
            $warga_miskin_niks = array_flip(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'nik'));
            
            foreach ($keluarga as $kk) {
                $kepala_keluarga = null;
                $anak_sekolah = [];
                
                foreach ($penduduk as $p) {
                    if ($p['id_keluarga'] == $kk['id_keluarga'] && $p['nama_lengkap'] == $kk['nama_kepala_keluarga']) {
                        $kepala_keluarga = $p;
                        break;
                    }
                }
                
                // Hanya lanjut jika kepala keluarga ditemukan DAN NIK-nya ada di warga_miskin
                if (!$kepala_keluarga || !isset($warga_miskin_niks[$kepala_keluarga['nik']])) {
                    continue;
                }
                
                foreach ($penduduk as $p) {
                    if ($p['id_keluarga'] == $kk['id_keluarga'] && $p['pekerjaan'] == 'Pelajar') {
                        $anak_sekolah[] = $p;
                    }
                }
                
                if (count($anak_sekolah) > 0) {
                    $kk_miskin_data[] = [
                        'kepala_keluarga' => $kepala_keluarga,
                        'anak_sekolah' => $anak_sekolah,
                        'desa' => $desa['nama_desa']
                    ];
                }
            }
        }
        
        return $kk_miskin_data;
    }
    
    public function getSpasialAdvancedData($filtered_desa) {
        $spasial_data = [];
        $aksesibilitas_data = [];
        $potensi_pengembangan = [];
        
        foreach ($filtered_desa as $desa) {
            $id_desa = $desa['id_desa'];
            $penduduk = $this->queries->getPendudukByDesa($id_desa);
            $fasilitas = $this->queries->getFasilitasPendidikan($id_desa);
            $ekonomi = $this->queries->getDataEkonomi($id_desa);
            $jalan = $this->queries->getInfrastrukturJalan($id_desa);
            
            $luas_ha = $desa['luas_wilayah'] ?? 1;
            $luas_km2 = $luas_ha * 0.01;
            $kepadatan = $luas_km2 > 0 ? round(count($penduduk) / $luas_km2, 2) : 0;
            $total_fasilitas_pendidikan = count($fasilitas);
            
            $kapasitas_per_jenjang = [];
            foreach ($fasilitas as $f) {
                $jenis = $f['jenis_pendidikan'];
                $kapasitas_per_jenjang[$jenis] = ($kapasitas_per_jenjang[$jenis] ?? 0) + ($f['kapasitas_siswa'] ?? 0);
            }
            
            $anak_per_jenjang = ['SD' => 0, 'SMP' => 0, 'SMA' => 0];
            foreach ($penduduk as $p) {
                if ($p['usia'] >= 7 && $p['usia'] <= 12) $anak_per_jenjang['SD']++;
                elseif ($p['usia'] >= 13 && $p['usia'] <= 15) $anak_per_jenjang['SMP']++;
                elseif ($p['usia'] >= 16 && $p['usia'] <= 18) $anak_per_jenjang['SMA']++;
            }
            
            $jalan_baik = 0;
            foreach ($jalan as $j) {
                if ($j['kondisi_jalan'] == 'baik') {
                    $jalan_baik++;
                }
            }
            $aksesibilitas = count($jalan) > 0 ? ($jalan_baik / count($jalan)) * 100 : 0;
            
            $nilai_ekonomi_total = 0;
            foreach ($ekonomi as $e) {
                $nilai_ekonomi_total += $e['nilai_ekonomi'] ?? 0;
            }
            
            $spasial_data[] = [
                'nama_desa' => $desa['nama_desa'],
                'kepadatan' => $kepadatan,
                'fasilitas_pendidikan' => $total_fasilitas_pendidikan,
                'kapasitas_per_jenjang' => $kapasitas_per_jenjang,
                'anak_per_jenjang' => $anak_per_jenjang,
                'total_penduduk' => count($penduduk),
                'luas_wilayah' => $desa['luas_wilayah'] ?? 0
            ];
            
            $aksesibilitas_data[] = [
                'nama_desa' => $desa['nama_desa'],
                'jalan_baik' => $jalan_baik,
                'total_jalan' => count($jalan),
                'aksesibilitas' => round($aksesibilitas, 1)
            ];
            
            $potensi_pengembangan[] = [
                'nama_desa' => $desa['nama_desa'],
                'kepadatan' => $kepadatan,
                'fasilitas' => $total_fasilitas_pendidikan,
                'nilai_ekonomi' => $nilai_ekonomi_total,
                'aksesibilitas' => round($aksesibilitas, 1)
            ];
        }
        
        usort($potensi_pengembangan, function ($a, $b) {
            $score_a = ($a['kepadatan'] * 0.2) + ($a['nilai_ekonomi'] / 1000000 * 0.5) + ($a['aksesibilitas'] / 100 * 0.3);
            $score_b = ($b['kepadatan'] * 0.2) + ($b['nilai_ekonomi'] / 1000000 * 0.5) + ($b['aksesibilitas'] / 100 * 0.3);
            return $score_b <=> $score_a;
        });
        
        return ['spasial' => $spasial_data, 'aksesibilitas' => $aksesibilitas_data, 'potensi' => $potensi_pengembangan];
    }
    
    public function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    // SERVER-SIDE PAGINATED DATA METHODS
    public function getKKPerempuanDataPaginated($filtered_desa, $page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        // Build desa condition
        $desa_ids = array_column($filtered_desa, 'id_desa');
        $desa_placeholders = str_repeat('?,', count($desa_ids) - 1) . '?';
        
        // Build search condition
        $search_condition = '';
        $search_params = [];
        if ($search) {
            $search_condition = " AND (k.nama_kepala_keluarga LIKE ? OR d.nama_desa LIKE ?)";
            $search_params = ['%' . $search . '%', '%' . $search . '%'];
        }
        
        // Count total records
        $count_query = "SELECT COUNT(DISTINCT k.id_keluarga) as total
                       FROM keluarga k
                       JOIN penduduk p ON p.id_keluarga = k.id_keluarga AND p.nama_lengkap = k.nama_kepala_keluarga
                       JOIN desa d ON d.id_desa = k.id_desa
                       WHERE k.id_desa IN ($desa_placeholders)
                       AND p.jenis_kelamin = 'P'
                       AND EXISTS (
                           SELECT 1 FROM penduduk p2 
                           WHERE p2.id_keluarga = k.id_keluarga 
                           AND p2.usia BETWEEN 7 AND 18
                       )
                       $search_condition";
        
        $count_stmt = $this->queries->db->prepare($count_query);
        $count_params = array_merge($desa_ids, $search_params);
        $count_stmt->execute($count_params);
        $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated data
        $data_query = "SELECT k.*, p.*, d.nama_desa,
                      (SELECT GROUP_CONCAT(CONCAT(p3.nama_lengkap, ':', p3.usia) SEPARATOR '|')
                       FROM penduduk p3 
                       WHERE p3.id_keluarga = k.id_keluarga 
                       AND p3.usia BETWEEN 7 AND 18) as anak_sekolah_data,
                      (SELECT COUNT(*) FROM warga_miskin wm WHERE wm.nik = p.nik) as is_miskin
                      FROM keluarga k
                      JOIN penduduk p ON p.id_keluarga = k.id_keluarga AND p.nama_lengkap = k.nama_kepala_keluarga
                      JOIN desa d ON d.id_desa = k.id_desa
                      WHERE k.id_desa IN ($desa_placeholders)
                      AND p.jenis_kelamin = 'P'
                      AND EXISTS (
                          SELECT 1 FROM penduduk p2 
                          WHERE p2.id_keluarga = k.id_keluarga 
                          AND p2.usia BETWEEN 7 AND 18
                      )
                      $search_condition
                      ORDER BY k.nama_kepala_keluarga
                      LIMIT $limit OFFSET $offset";
        
        $data_stmt = $this->queries->db->prepare($data_query);
        $data_params = array_merge($desa_ids, $search_params);
        $data_stmt->execute($data_params);
        $results = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $kk_perempuan_data = [];
        foreach ($results as $row) {
            $anak_sekolah = [];
            if ($row['anak_sekolah_data']) {
                $anak_data = explode('|', $row['anak_sekolah_data']);
                foreach ($anak_data as $anak) {
                    $anak_info = explode(':', $anak);
                    $anak_sekolah[] = [
                        'nama_lengkap' => $anak_info[0] ?? '',
                        'usia' => $anak_info[1] ?? 0
                    ];
                }
            }
            
            $kk_perempuan_data[] = [
                'kepala_keluarga' => [
                    'nama_lengkap' => $row['nama_lengkap'],
                    'usia' => $row['usia'],
                    'jenis_kelamin' => $row['jenis_kelamin']
                ],
                'anak_sekolah' => $anak_sekolah,
                'is_miskin' => $row['is_miskin'] > 0,
                'desa' => $row['nama_desa']
            ];
        }
        
        return [
            'data' => $kk_perempuan_data,
            'total' => $total_count,
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total_pages' => (int)ceil($total_count / $limit)
        ];
    }
    
    public function getProduktifDataPaginated($filtered_desa, $page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        // Build desa condition
        $desa_ids = array_column($filtered_desa, 'id_desa');
        $desa_placeholders = str_repeat('?,', count($desa_ids) - 1) . '?';
        
        // Build search condition
        $search_condition = '';
        $search_params = [];
        if ($search) {
            $search_condition = " AND (p.nama_lengkap LIKE ? OR d.nama_desa LIKE ?)";
            $search_params = ['%' . $search . '%', '%' . $search . '%'];
        }
        
        // Count total records
        $count_query = "SELECT COUNT(*) as total
                       FROM penduduk p
                       JOIN desa d ON d.id_desa = p.id_desa
                       WHERE p.id_desa IN ($desa_placeholders)
                       AND p.usia BETWEEN 18 AND 64
                       AND NOT EXISTS (
                           SELECT 1 FROM mata_pencaharian mp 
                           WHERE mp.nik = p.nik 
                           AND mp.status_pekerjaan = 'tetap'
                       )
                       $search_condition";
        
        $count_stmt = $this->queries->db->prepare($count_query);
        $count_params = array_merge($desa_ids, $search_params);
        $count_stmt->execute($count_params);
        $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated data
        $data_query = "SELECT p.*, d.nama_desa
                      FROM penduduk p
                      JOIN desa d ON d.id_desa = p.id_desa
                      WHERE p.id_desa IN ($desa_placeholders)
                      AND p.usia BETWEEN 18 AND 64
                      AND NOT EXISTS (
                          SELECT 1 FROM mata_pencaharian mp 
                          WHERE mp.nik = p.nik 
                          AND mp.status_pekerjaan = 'tetap'
                      )
                      $search_condition
                      ORDER BY p.nama_lengkap
                      LIMIT $limit OFFSET $offset";
        
        $data_stmt = $this->queries->db->prepare($data_query);
        $data_params = array_merge($desa_ids, $search_params);
        $data_stmt->execute($data_params);
        $results = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $produktif_data = [];
        foreach ($results as $row) {
            $produktif_data[] = [
                'penduduk' => $row,
                'desa' => $row['nama_desa']
            ];
        }
        
        // Get detail breakdown (separate query for efficiency)
        $detail = $this->getProduktifDetail($filtered_desa);
        
        return [
            'data' => $produktif_data,
            'detail' => $detail,
            'total' => $total_count,
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total_pages' => (int)ceil($total_count / $limit)
        ];
    }
    
    public function getLansiaDataPaginated($filtered_desa, $page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        // Build desa condition
        $desa_ids = array_column($filtered_desa, 'id_desa');
        $desa_placeholders = str_repeat('?,', count($desa_ids) - 1) . '?';
        
        // Build search condition
        $search_condition = '';
        $search_params = [];
        if ($search) {
            $search_condition = " AND (k.nama_kepala_keluarga LIKE ? OR d.nama_desa LIKE ?)";
            $search_params = ['%' . $search . '%', '%' . $search . '%'];
        }
        
        // Count total records
        $count_query = "SELECT COUNT(DISTINCT k.id_keluarga) as total
                       FROM keluarga k
                       JOIN penduduk p ON p.id_keluarga = k.id_keluarga AND p.nama_lengkap = k.nama_kepala_keluarga
                       JOIN desa d ON d.id_desa = k.id_desa
                       WHERE k.id_desa IN ($desa_placeholders)
                       AND p.usia > 65
                       AND EXISTS (
                           SELECT 1 FROM penduduk p2 
                           WHERE p2.id_keluarga = k.id_keluarga 
                           AND p2.usia BETWEEN 7 AND 18
                       )
                       $search_condition";
        
        $count_stmt = $this->queries->db->prepare($count_query);
        $count_params = array_merge($desa_ids, $search_params);
        $count_stmt->execute($count_params);
        $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated data
        $data_query = "SELECT k.*, p.*, d.nama_desa,
                      (SELECT GROUP_CONCAT(CONCAT(p3.nama_lengkap, ':', p3.usia) SEPARATOR '|')
                       FROM penduduk p3 
                       WHERE p3.id_keluarga = k.id_keluarga 
                       AND p3.usia BETWEEN 7 AND 18) as tanggungan_data
                      FROM keluarga k
                      JOIN penduduk p ON p.id_keluarga = k.id_keluarga AND p.nama_lengkap = k.nama_kepala_keluarga
                      JOIN desa d ON d.id_desa = k.id_desa
                      WHERE k.id_desa IN ($desa_placeholders)
                      AND p.usia > 65
                      AND EXISTS (
                          SELECT 1 FROM penduduk p2 
                          WHERE p2.id_keluarga = k.id_keluarga 
                          AND p2.usia BETWEEN 7 AND 18
                      )
                      $search_condition
                      ORDER BY k.nama_kepala_keluarga
                      LIMIT $limit OFFSET $offset";
        
        $data_stmt = $this->queries->db->prepare($data_query);
        $data_params = array_merge($desa_ids, $search_params);
        $data_stmt->execute($data_params);
        $results = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $lansia_data = [];
        foreach ($results as $row) {
            $tanggungan = [];
            if ($row['tanggungan_data']) {
                $tanggungan_info = explode('|', $row['tanggungan_data']);
                foreach ($tanggungan_info as $t) {
                    $t_info = explode(':', $t);
                    $tanggungan[] = [
                        'nama_lengkap' => $t_info[0] ?? '',
                        'usia' => $t_info[1] ?? 0
                    ];
                }
            }
            
            $lansia_data[] = [
                'kepala_keluarga' => [
                    'nama_lengkap' => $row['nama_lengkap'],
                    'usia' => $row['usia']
                ],
                'tanggungan' => $tanggungan,
                'desa' => $row['nama_desa']
            ];
        }
        
        return [
            'data' => $lansia_data,
            'total' => $total_count,
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total_pages' => (int)ceil($total_count / $limit)
        ];
    }
    
    // Helper methods for breakdown data
    private function getProduktifDetail($filtered_desa) {
        $detail = ['sd' => 0, 'smp' => 0, 'sma' => 0, 'diploma' => 0, 'sarjana' => 0, 'laki' => 0, 'perempuan' => 0];
        
        foreach ($filtered_desa as $desa) {
            $id_desa = $desa['id_desa'];
            
            $query = "SELECT p.pendidikan_terakhir, p.jenis_kelamin, COUNT(*) as jumlah
                     FROM penduduk p
                     WHERE p.id_desa = :id_desa 
                     AND p.usia BETWEEN 18 AND 64
                     AND NOT EXISTS (
                         SELECT 1 FROM mata_pencaharian mp 
                         WHERE mp.nik = p.nik 
                         AND mp.status_pekerjaan = 'tetap'
                     )
                     GROUP BY p.pendidikan_terakhir, p.jenis_kelamin";
            
            $stmt = $this->queries->db->prepare($query);
            $stmt->bindParam(':id_desa', $id_desa);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                $jumlah = $row['jumlah'];
                
                // Count by education
                switch ($row['pendidikan_terakhir']) {
                    case 'SD': $detail['sd'] += $jumlah; break;
                    case 'SMP': $detail['smp'] += $jumlah; break;
                    case 'SMA': $detail['sma'] += $jumlah; break;
                    case 'D1': case 'D2': case 'D3': case 'D4': $detail['diploma'] += $jumlah; break;
                    case 'S1': case 'S2': case 'S3': $detail['sarjana'] += $jumlah; break;
                }
                
                // Count by gender
                if ($row['jenis_kelamin'] == 'L') $detail['laki'] += $jumlah;
                else $detail['perempuan'] += $jumlah;
            }
        }
        
        return $detail;
    }
    
    private function getPetaniBreakdown($filtered_desa) {
        $breakdown = ['0.1-0.5' => 0, '0.5-1' => 0];
        
        foreach ($filtered_desa as $desa) {
            $id_desa = $desa['id_desa'];
            
            $query = "SELECT 
                     SUM(CASE WHEN luas_lahan <= 0.5 THEN 1 ELSE 0 END) as kecil,
                     SUM(CASE WHEN luas_lahan > 0.5 AND luas_lahan < 1 THEN 1 ELSE 0 END) as sedang
                     FROM pertanian 
                     WHERE id_desa = :id_desa 
                     AND luas_lahan < 1 
                     AND bantuan_pertanian = 'tidak_ada'";
            
            $stmt = $this->queries->db->prepare($query);
            $stmt->bindParam(':id_desa', $id_desa);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $breakdown['0.1-0.5'] += $result['kecil'] ?? 0;
            $breakdown['0.5-1'] += $result['sedang'] ?? 0;
        }
        
        return $breakdown;
    }
    
    private function getPetaniKomoditas($filtered_desa) {
        $komoditas = [];
        
        foreach ($filtered_desa as $desa) {
            $id_desa = $desa['id_desa'];
            
            $query = "SELECT jenis_komoditas, COUNT(*) as jumlah
                     FROM pertanian 
                     WHERE id_desa = :id_desa 
                     AND luas_lahan < 1 
                     AND bantuan_pertanian = 'tidak_ada'
                     GROUP BY jenis_komoditas";
            
            $stmt = $this->queries->db->prepare($query);
            $stmt->bindParam(':id_desa', $id_desa);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                $jenis = $row['jenis_komoditas'];
                $komoditas[$jenis] = ($komoditas[$jenis] ?? 0) + $row['jumlah'];
            }
        }
        
        return $komoditas;
    }
    
    private function getDistribusiTotals($filtered_desa) {
        $total_pertanian = 0;
        $total_non_pertanian = 0;
        
        foreach ($filtered_desa as $desa) {
            $id_desa = $desa['id_desa'];
            
            $query = "SELECT 
                     SUM(CASE WHEN sektor_pekerjaan = 'pertanian' THEN penghasilan_perbulan ELSE 0 END) as pertanian,
                     SUM(CASE WHEN sektor_pekerjaan != 'pertanian' THEN penghasilan_perbulan ELSE 0 END) as non_pertanian
                     FROM mata_pencaharian 
                     WHERE id_desa = :id_desa";
            
            $stmt = $this->queries->db->prepare($query);
            $stmt->bindParam(':id_desa', $id_desa);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $total_pertanian += $result['pertanian'] ?? 0;
            $total_non_pertanian += $result['non_pertanian'] ?? 0;
        }
        
        return [
            'total_pertanian' => $total_pertanian,
            'total_non_pertanian' => $total_non_pertanian
        ];
    }
    
    private function getNonPertanianDetail($filtered_desa) {
        $detail = [];
        
        foreach ($filtered_desa as $desa) {
            $id_desa = $desa['id_desa'];
            
            $query = "SELECT sektor_pekerjaan, COUNT(*) as jumlah
                     FROM mata_pencaharian 
                     WHERE id_desa = :id_desa 
                     AND sektor_pekerjaan != 'pertanian'
                     GROUP BY sektor_pekerjaan";
            
            $stmt = $this->queries->db->prepare($query);
            $stmt->bindParam(':id_desa', $id_desa);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                $sektor = ucfirst($row['sektor_pekerjaan']);
                $detail[$sektor] = ($detail[$sektor] ?? 0) + $row['jumlah'];
            }
        }
        
        return $detail;
    }
    
    public function getPetaniDataPaginated($filtered_desa, $page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        // Build desa condition
        $desa_ids = array_column($filtered_desa, 'id_desa');
        if (empty($desa_ids)) {
            return ['data' => [], 'breakdown' => ['0.1-0.5' => 0, '0.5-1' => 0], 'komoditas' => [], 'total' => 0, 'page' => (int)$page, 'limit' => (int)$limit, 'total_pages' => 0];
        }
        $desa_placeholders = str_repeat('?,', count($desa_ids) - 1) . '?';
        
        // Build search condition
        $search_condition = '';
        $search_params = [];
        if ($search) {
            $search_condition = " AND (pd.nama_lengkap LIKE ? OR d.nama_desa LIKE ? OR p.jenis_komoditas LIKE ?)";
            $search_params = ['%' . $search . '%', '%' . $search . '%', '%' . $search . '%'];
        }
        
        // Count total records across all filtered desa
        $count_query = "SELECT COUNT(*) as total
                       FROM pertanian p 
                       LEFT JOIN penduduk pd ON pd.nik = p.nik_petani
                       JOIN desa d ON d.id_desa = p.id_desa
                       WHERE p.id_desa IN ($desa_placeholders)
                       AND p.luas_lahan < 1 
                       AND p.bantuan_pertanian = 'tidak_ada'
                       $search_condition";
        
        $count_stmt = $this->queries->db->prepare($count_query);
        $count_params = array_merge($desa_ids, $search_params);
        $count_stmt->execute($count_params);
        $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated data across all filtered desa
        $data_query = "SELECT p.*, pd.nama_lengkap, d.nama_desa
                      FROM pertanian p 
                      LEFT JOIN penduduk pd ON pd.nik = p.nik_petani
                      JOIN desa d ON d.id_desa = p.id_desa
                      WHERE p.id_desa IN ($desa_placeholders)
                      AND p.luas_lahan < 1 
                      AND p.bantuan_pertanian = 'tidak_ada'
                      $search_condition
                      ORDER BY pd.nama_lengkap
                      LIMIT $limit OFFSET $offset";
        
        $data_stmt = $this->queries->db->prepare($data_query);
        $data_params = array_merge($desa_ids, $search_params);
        $data_stmt->execute($data_params);
        $results = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $petani_data = [];
        foreach ($results as $row) {
            $petani_data[] = [
                'nama_petani' => $row['nama_lengkap'] ?? 'Petani',
                'desa' => $row['nama_desa'],
                'luas_lahan' => $row['luas_lahan'],
                'jenis_komoditas' => $row['jenis_komoditas'],
                'pendapatan_per_musim' => $row['pendapatan_per_musim'] ?? 0,
                'penghasilan_perbulan' => ($row['pendapatan_per_musim'] ?? 0) / 3
            ];
        }
        
        // Get breakdown data (separate query)
        $breakdown = $this->getPetaniBreakdown($filtered_desa);
        $komoditas = $this->getPetaniKomoditas($filtered_desa);
        
        return [
            'data' => $petani_data,
            'breakdown' => $breakdown,
            'komoditas' => $komoditas,
            'total' => $total_count,
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total_pages' => (int)ceil($total_count / $limit)
        ];
    }
    
    public function getKorelasiPendidikanDataPaginated($filtered_desa, $page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        // Build desa condition
        $desa_ids = array_column($filtered_desa, 'id_desa');
        if (empty($desa_ids)) {
            return ['data' => [], 'total' => 0, 'page' => (int)$page, 'limit' => (int)$limit, 'total_pages' => 0];
        }
        $desa_placeholders = str_repeat('?,', count($desa_ids) - 1) . '?';
        
        // Build search condition
        $search_condition = '';
        $search_params = [];
        if ($search) {
            $search_condition = " AND (p.nama_lengkap LIKE ? OR d.nama_desa LIKE ? OR mp.sektor_pekerjaan LIKE ?)";
            $search_params = ['%' . $search . '%', '%' . $search . '%', '%' . $search . '%'];
        }
        
        // Count total records across all filtered desa
        $count_query = "SELECT COUNT(*) as total
                       FROM mata_pencaharian mp
                       JOIN penduduk p ON p.nik = mp.nik
                       JOIN desa d ON d.id_desa = mp.id_desa
                       WHERE mp.id_desa IN ($desa_placeholders)
                       $search_condition";
        
        $count_stmt = $this->queries->db->prepare($count_query);
        $count_params = array_merge($desa_ids, $search_params);
        $count_stmt->execute($count_params);
        $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated data across all filtered desa
        $data_query = "SELECT mp.*, p.*, d.nama_desa
                      FROM mata_pencaharian mp
                      JOIN penduduk p ON p.nik = mp.nik
                      JOIN desa d ON d.id_desa = mp.id_desa
                      WHERE mp.id_desa IN ($desa_placeholders)
                      $search_condition
                      ORDER BY p.nama_lengkap
                      LIMIT $limit OFFSET $offset";
        
        $data_stmt = $this->queries->db->prepare($data_query);
        $data_params = array_merge($desa_ids, $search_params);
        $data_stmt->execute($data_params);
        $results = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $korelasi_data = [];
        foreach ($results as $row) {
            $korelasi_data[] = [
                'penduduk' => [
                    'nama_lengkap' => $row['nama_lengkap'],
                    'pendidikan_terakhir' => $row['pendidikan_terakhir']
                ],
                'pekerjaan' => [
                    'sektor_pekerjaan' => $row['sektor_pekerjaan'],
                    'penghasilan_perbulan' => $row['penghasilan_perbulan']
                ],
                'desa' => $row['nama_desa']
            ];
        }
        
        return [
            'data' => $korelasi_data,
            'total' => $total_count,
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total_pages' => (int)ceil($total_count / $limit)
        ];
    }
    
    public function getKesenjanganEkonomiDataPaginated($all_desa, $page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        // Build search condition
        $search_condition = '';
        $search_params = [];
        if ($search) {
            $search_condition = " AND d.nama_desa LIKE :search";
            $search_params[':search'] = '%' . $search . '%';
        }
        
        // Count total records
        $count_query = "SELECT COUNT(DISTINCT d.id_desa) as total
                       FROM desa d
                       WHERE 1=1 $search_condition";
        
        $count_stmt = $this->queries->db->prepare($count_query);
        foreach ($search_params as $key => $value) {
            $count_stmt->bindParam($key, $value);
        }
        $count_stmt->execute();
        $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated data with aggregated calculations
        $data_query = "SELECT d.nama_desa,
                      COALESCE(SUM(mp.penghasilan_perbulan), 0) as total_pendapatan,
                      COUNT(mp.nik) as jumlah_pekerja,
                      CASE WHEN COUNT(mp.nik) > 0 
                           THEN ROUND(SUM(mp.penghasilan_perbulan) / COUNT(mp.nik))
                           ELSE 0 END as rata_pendapatan
                      FROM desa d
                      LEFT JOIN mata_pencaharian mp ON mp.id_desa = d.id_desa
                      WHERE 1=1 $search_condition
                      GROUP BY d.id_desa, d.nama_desa
                      HAVING jumlah_pekerja > 0
                      ORDER BY rata_pendapatan DESC
                      LIMIT $limit OFFSET $offset";
        
        $data_stmt = $this->queries->db->prepare($data_query);
        foreach ($search_params as $key => $value) {
            $data_stmt->bindParam($key, $value);
        }
        $data_stmt->execute();
        $results = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $results,
            'total' => $total_count,
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total_pages' => (int)ceil($total_count / $limit)
        ];
    }
    
    public function getDistribusiPendapatanDataPaginated($filtered_desa, $page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        // Build desa condition
        $desa_ids = array_column($filtered_desa, 'id_desa');
        if (empty($desa_ids)) {
            return ['data' => [], 'total_pertanian' => 0, 'total_non_pertanian' => 0, 'total' => 0, 'page' => (int)$page, 'limit' => (int)$limit, 'total_pages' => 0];
        }
        $desa_placeholders = str_repeat('?,', count($desa_ids) - 1) . '?';
        
        // Build search condition
        $search_condition = '';
        $search_params = [];
        if ($search) {
            $search_condition = " AND d.nama_desa LIKE ?";
            $search_params = ['%' . $search . '%'];
        }
        
        // Count total records
        $count_query = "SELECT COUNT(DISTINCT d.id_desa) as total
                       FROM desa d
                       WHERE d.id_desa IN ($desa_placeholders)
                       $search_condition";
        
        $count_stmt = $this->queries->db->prepare($count_query);
        $count_params = array_merge($desa_ids, $search_params);
        $count_stmt->execute($count_params);
        $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated data across all filtered desa
        $data_query = "SELECT d.nama_desa,
                      COALESCE(SUM(CASE WHEN mp.sektor_pekerjaan = 'pertanian' THEN mp.penghasilan_perbulan ELSE 0 END), 0) as pendapatan_pertanian,
                      COALESCE(SUM(CASE WHEN mp.sektor_pekerjaan != 'pertanian' THEN mp.penghasilan_perbulan ELSE 0 END), 0) as pendapatan_non_pertanian,
                      COALESCE(SUM(mp.penghasilan_perbulan), 0) as total_pendapatan
                      FROM desa d
                      LEFT JOIN mata_pencaharian mp ON mp.id_desa = d.id_desa
                      WHERE d.id_desa IN ($desa_placeholders)
                      $search_condition
                      GROUP BY d.id_desa, d.nama_desa
                      ORDER BY d.nama_desa
                      LIMIT $limit OFFSET $offset";
        
        $data_stmt = $this->queries->db->prepare($data_query);
        $data_params = array_merge($desa_ids, $search_params);
        $data_stmt->execute($data_params);
        $distribusi_data = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get totals (separate query)
        $totals = $this->getDistribusiTotals($filtered_desa);
        
        return [
            'data' => $distribusi_data,
            'total_pertanian' => $totals['total_pertanian'],
            'total_non_pertanian' => $totals['total_non_pertanian'],
            'total' => $total_count,
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total_pages' => (int)ceil($total_count / $limit)
        ];
    }
    
    public function getNonPertanianDataPaginated($filtered_desa, $page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        
        // Build desa condition
        $desa_ids = array_column($filtered_desa, 'id_desa');
        if (empty($desa_ids)) {
            return ['data' => [], 'detail' => [], 'total' => 0, 'page' => (int)$page, 'limit' => (int)$limit, 'total_pages' => 0];
        }
        $desa_placeholders = str_repeat('?,', count($desa_ids) - 1) . '?';
        
        // Build search condition
        $search_condition = '';
        $search_params = [];
        if ($search) {
            $search_condition = " AND (p.nama_lengkap LIKE ? OR d.nama_desa LIKE ? OR mp.sektor_pekerjaan LIKE ?)";
            $search_params = ['%' . $search . '%', '%' . $search . '%', '%' . $search . '%'];
        }
        
        // Count total records across all filtered desa
        $count_query = "SELECT COUNT(*) as total
                       FROM mata_pencaharian mp
                       JOIN penduduk p ON p.nik = mp.nik
                       JOIN desa d ON d.id_desa = mp.id_desa
                       WHERE mp.id_desa IN ($desa_placeholders)
                       AND mp.sektor_pekerjaan != 'pertanian'
                       $search_condition";
        
        $count_stmt = $this->queries->db->prepare($count_query);
        $count_params = array_merge($desa_ids, $search_params);
        $count_stmt->execute($count_params);
        $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated data across all filtered desa
        $data_query = "SELECT mp.*, p.*, d.nama_desa
                      FROM mata_pencaharian mp
                      JOIN penduduk p ON p.nik = mp.nik
                      JOIN desa d ON d.id_desa = mp.id_desa
                      WHERE mp.id_desa IN ($desa_placeholders)
                      AND mp.sektor_pekerjaan != 'pertanian'
                      $search_condition
                      ORDER BY p.nama_lengkap
                      LIMIT $limit OFFSET $offset";
        
        $data_stmt = $this->queries->db->prepare($data_query);
        $data_params = array_merge($desa_ids, $search_params);
        $data_stmt->execute($data_params);
        $results = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $non_pertanian_data = [];
        foreach ($results as $row) {
            $non_pertanian_data[] = [
                'penduduk' => [
                    'nama_lengkap' => $row['nama_lengkap']
                ],
                'pekerjaan' => [
                    'sektor_pekerjaan' => $row['sektor_pekerjaan'],
                    'jenis_pekerjaan' => $row['jenis_pekerjaan'],
                    'penghasilan_perbulan' => $row['penghasilan_perbulan'],
                    'status_pekerjaan' => $row['status_pekerjaan']
                ],
                'desa' => $row['nama_desa']
            ];
        }
        
        // Get detail breakdown (separate query)
        $detail = $this->getNonPertanianDetail($filtered_desa);
        
        return [
            'data' => $non_pertanian_data,
            'detail' => $detail,
            'total' => $total_count,
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total_pages' => (int)ceil($total_count / $limit)
        ];
    }
    
    // AJAX REQUEST HANDLER
    public function handleAjaxRequest() {
        if (!$this->isAjaxRequest()) return;
        
        $action = $_GET['action'] ?? '';
        $desa_param = $_GET['desa'] ?? 'all';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(5, min(50, (int)($_GET['limit'] ?? 10)));
        $search = trim($_GET['search'] ?? '');
        
        $all_desa = $this->getAllDesa();
        $filtered_desa = $this->getFilteredDesa($desa_param, $all_desa);
        
        try {
            switch ($action) {
                case 'get_kk_perempuan':
                    $data = $this->getKKPerempuanDataPaginated($filtered_desa, $page, $limit, $search);
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                    
                case 'get_produktif':
                    $data = $this->getProduktifDataPaginated($filtered_desa, $page, $limit, $search);
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                    
                case 'get_lansia':
                    $data = $this->getLansiaDataPaginated($filtered_desa, $page, $limit, $search);
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                    
                case 'get_petani':
                    $data = $this->getPetaniDataPaginated($filtered_desa, $page, $limit, $search);
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                    
                case 'get_korelasi':
                    $data = $this->getKorelasiPendidikanDataPaginated($filtered_desa, $page, $limit, $search);
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                    
                case 'get_kesenjangan':
                    $data = $this->getKesenjanganEkonomiDataPaginated($all_desa, $page, $limit, $search);
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                    
                case 'get_distribusi':
                    $data = $this->getDistribusiPendapatanDataPaginated($filtered_desa, $page, $limit, $search);
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                    
                case 'get_non_pertanian':
                    $data = $this->getNonPertanianDataPaginated($filtered_desa, $page, $limit, $search);
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                    
                case 'get_kk_miskin':
                    $data = $this->getKKMiskinDataPaginated($filtered_desa, $page, $limit, $search);
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                    
                case 'get_rasio_ekonomi':
                    $data = $this->getRasioKetergantunganDataPaginated($filtered_desa, $page, $limit, $search);
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                    
                case 'get_pendidikan_tinggi_miskin':
                    $data = $this->getPendidikanTinggiMiskinDataPaginated($filtered_desa, $page, $limit, $search);
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                    
                case 'get_anak_tidak_sekolah':
                    $data = $this->getAnakTidakSekolahDataPaginated($filtered_desa, $page, $limit, $search);
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                    
                case 'get_anak_petani':
                    $data = $this->getAnakPetaniDataPaginated($filtered_desa, $page, $limit, $search);
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                    
                case 'get_spasial':
                    $data = $this->getSpasialDataPaginated($filtered_desa, $page, $limit, $search);
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                    
                case 'get_aksesibilitas':
                    $data = $this->getAksesibilitasDataPaginated($filtered_desa, $page, $limit, $search);
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                    
                case 'get_potensi':
                    $data = $this->getPotensiPengembanganDataPaginated($filtered_desa, $page, $limit, $search);
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Action not found']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
        exit();
    }
}
?>