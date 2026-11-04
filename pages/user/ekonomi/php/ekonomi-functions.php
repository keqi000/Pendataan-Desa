<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/session.php';
require_once __DIR__ . '/../../../../database/queries.php';

class EkonomiFunctions {
    private $queries;
    private $db;
    private $id_desa;
    
    public function __construct() {
        $this->queries = new Queries();
        $database = new Database();
        $this->db = $database->getConnection();
        // Initialize session first
        if (!initSession()) {
            $this->id_desa = null;
        } else {
            $this->id_desa = $_SESSION['user_desa'] ?? null;
        }
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
    
    public function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    public function getPageConfig() {
        return [
            'page_title' => 'Data Ekonomi',
            'current_page' => 'ekonomi',
            'page_css' => [BASE_URL . 'pages/user/ekonomi/ekonomi.css'],
            'page_js' => [
                BASE_URL . 'pages/user/ekonomi/js/ekonomi-core.js',
                BASE_URL . 'pages/user/ekonomi/js/ekonomi-mata-pencaharian.js',
                BASE_URL . 'pages/user/ekonomi/js/ekonomi-umkm.js',
                BASE_URL . 'pages/user/ekonomi/js/ekonomi-pertanian.js',
                BASE_URL . 'pages/user/ekonomi/js/ekonomi-pasar.js',
                BASE_URL . 'pages/user/ekonomi/js/ekonomi-bantuan.js',
                BASE_URL . 'pages/user/ekonomi/ekonomi.js'
            ]
        ];
    }
    
    // MATA PENCAHARIAN METHODS
    public function getMataPencaharian($page = 1, $limit = 10, $search = '', $sektor = '') {
        try {
            $offset = ($page - 1) * $limit;
            
            $where = "WHERE mp.id_desa = ?";
            $params = [$this->id_desa];
            
            if ($search) {
                $where .= " AND (p.nama_lengkap LIKE ? OR mp.nik LIKE ? OR mp.jenis_pekerjaan LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if ($sektor) {
                $where .= " AND mp.sektor_pekerjaan = ?";
                $params[] = $sektor;
            }
            
            // Get total count with optimized query
            $countQuery = "SELECT COUNT(*) as total FROM mata_pencaharian mp LEFT JOIN penduduk p ON mp.nik = p.nik $where";
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get data with proper LIMIT binding
            $query = "SELECT mp.*, p.nama_lengkap FROM mata_pencaharian mp LEFT JOIN penduduk p ON mp.nik = p.nik $where ORDER BY p.nama_lengkap LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($query);
            
            // Bind all parameters
            for ($i = 0; $i < count($params); $i++) {
                $stmt->bindValue($i + 1, $params[$i]);
            }
            $stmt->bindValue(count($params) + 1, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(count($params) + 2, (int)$offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $pagination = [
                'current_page' => (int)$page,
                'total_pages' => ceil($total / $limit),
                'total_items' => (int)$total,
                'items_per_page' => (int)$limit
            ];

            return ['data' => $result, 'pagination' => $pagination];
        } catch (Exception $e) {
            return ['data' => [], 'pagination' => ['current_page' => 1, 'total_pages' => 0, 'total_items' => 0, 'items_per_page' => $limit]];
        }
    }
    
    public function getMataPencaharianDetail($id) {
        try {
            $query = "SELECT mp.*, p.nama_lengkap FROM mata_pencaharian mp LEFT JOIN penduduk p ON mp.nik = p.nik WHERE mp.id_pencaharian = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function createMataPencaharian($data) {
        try {
            $data['id_desa'] = $this->id_desa;
            
            $query = "INSERT INTO mata_pencaharian (id_desa, nik, jenis_pekerjaan, sektor_pekerjaan, penghasilan_perbulan, status_pekerjaan) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['id_desa'], $data['nik'], $data['jenis_pekerjaan'], 
                $data['sektor_pekerjaan'], $data['penghasilan_perbulan'], $data['status_pekerjaan']
            ]);
            
            return ['success' => $result, 'message' => $result ? 'Data mata pencaharian berhasil ditambahkan' : 'Gagal menambahkan data'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function updateMataPencaharian($data) {
        try {
            $query = "UPDATE mata_pencaharian SET nik = ?, jenis_pekerjaan = ?, sektor_pekerjaan = ?, penghasilan_perbulan = ?, status_pekerjaan = ? WHERE id_pencaharian = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['nik'], $data['jenis_pekerjaan'], $data['sektor_pekerjaan'], 
                $data['penghasilan_perbulan'], $data['status_pekerjaan'], $data['id_pencaharian']
            ]);
            
            return ['success' => $result, 'message' => 'Data mata pencaharian berhasil diupdate'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function deleteMataPencaharian($id) {
        try {
            $query = "DELETE FROM mata_pencaharian WHERE id_pencaharian = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id]);
            
            return ['success' => $result, 'message' => 'Data mata pencaharian berhasil dihapus'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // UMKM METHODS
    public function getUmkm($page = 1, $limit = 10, $search = '') {
        try {
            $offset = ($page - 1) * $limit;
            
            $where = "WHERE u.id_desa = ?";
            $params = [$this->id_desa];
            
            if ($search) {
                $where .= " AND (u.nama_usaha LIKE ? OR p.nama_lengkap LIKE ? OR u.jenis_usaha LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            // Optimized count query
            $countQuery = "SELECT COUNT(*) as total FROM umkm u LEFT JOIN penduduk p ON u.nik_pemilik = p.nik $where";
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Data query with proper parameter binding
            $query = "SELECT u.*, p.nama_lengkap as nama_pemilik FROM umkm u LEFT JOIN penduduk p ON u.nik_pemilik = p.nik $where ORDER BY u.nama_usaha LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($query);
            
            for ($i = 0; $i < count($params); $i++) {
                $stmt->bindValue($i + 1, $params[$i]);
            }
            $stmt->bindValue(count($params) + 1, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(count($params) + 2, (int)$offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $pagination = [
                'current_page' => (int)$page,
                'total_pages' => ceil($total / $limit),
                'total_items' => (int)$total,
                'items_per_page' => (int)$limit
            ];

            return ['data' => $result, 'pagination' => $pagination];
        } catch (Exception $e) {
            return ['data' => [], 'pagination' => ['current_page' => 1, 'total_pages' => 0, 'total_items' => 0, 'items_per_page' => $limit]];
        }
    }
    
    public function getUmkmDetail($id) {
        try {
            $query = "SELECT u.*, p.nama_lengkap as nama_pemilik FROM umkm u LEFT JOIN penduduk p ON u.nik_pemilik = p.nik WHERE u.id_umkm = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function createUmkm($data) {
        try {
            if (isset($_SESSION['umkm_submit_flag']) && (time() - $_SESSION['umkm_submit_flag']) < 3) {
                return ['success' => false, 'message' => 'Permintaan sedang diproses, harap tunggu'];
            }
            $_SESSION['umkm_submit_flag'] = time();
            
            $data['id_desa'] = $this->id_desa;
            
            $query = "INSERT INTO umkm (id_desa, nik_pemilik, nama_usaha, jenis_usaha, modal_usaha, omzet_perbulan, jumlah_karyawan, status_izin, alamat_usaha, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['id_desa'], $data['nik_pemilik'], $data['nama_usaha'], $data['jenis_usaha'],
                $data['modal_usaha'], $data['omzet_perbulan'], $data['jumlah_karyawan'], 
                $data['status_izin'], $data['alamat_usaha']
            ]);
            
            unset($_SESSION['umkm_submit_flag']);
            
            return ['success' => $result, 'message' => $result ? 'Data UMKM berhasil ditambahkan' : 'Gagal menambahkan data UMKM'];
        } catch (Exception $e) {
            unset($_SESSION['umkm_submit_flag']);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function updateUmkm($data) {
        try {
            $query = "UPDATE umkm SET nik_pemilik = ?, nama_usaha = ?, jenis_usaha = ?, modal_usaha = ?, omzet_perbulan = ?, jumlah_karyawan = ?, status_izin = ?, alamat_usaha = ? WHERE id_umkm = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['nik_pemilik'], $data['nama_usaha'], $data['jenis_usaha'], $data['modal_usaha'],
                $data['omzet_perbulan'], $data['jumlah_karyawan'], $data['status_izin'], $data['alamat_usaha'], $data['id_umkm']
            ]);
            
            return ['success' => $result, 'message' => 'Data UMKM berhasil diupdate'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function deleteUmkm($id) {
        try {
            $query = "DELETE FROM umkm WHERE id_umkm = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id]);
            
            return ['success' => $result, 'message' => 'Data UMKM berhasil dihapus'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // PERTANIAN METHODS
    public function getPertanian($page = 1, $limit = 10, $search = '') {
        try {
            $offset = ($page - 1) * $limit;
            
            $where = "WHERE pt.id_desa = ?";
            $params = [$this->id_desa];
            
            if ($search) {
                $where .= " AND (p.nama_lengkap LIKE ? OR pt.nik_petani LIKE ? OR pt.jenis_komoditas LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            // Optimized count query
            $countQuery = "SELECT COUNT(*) as total FROM pertanian pt LEFT JOIN penduduk p ON pt.nik_petani = p.nik $where";
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Data query with proper parameter binding
            $query = "SELECT pt.*, p.nama_lengkap as nama_petani FROM pertanian pt LEFT JOIN penduduk p ON pt.nik_petani = p.nik $where ORDER BY p.nama_lengkap LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($query);
            
            for ($i = 0; $i < count($params); $i++) {
                $stmt->bindValue($i + 1, $params[$i]);
            }
            $stmt->bindValue(count($params) + 1, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(count($params) + 2, (int)$offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $pagination = [
                'current_page' => (int)$page,
                'total_pages' => ceil($total / $limit),
                'total_items' => (int)$total,
                'items_per_page' => (int)$limit
            ];

            return ['data' => $result, 'pagination' => $pagination];
        } catch (Exception $e) {
            return ['data' => [], 'pagination' => ['current_page' => 1, 'total_pages' => 0, 'total_items' => 0, 'items_per_page' => $limit]];
        }
    }
    
    public function getPertanianDetail($id) {
        try {
            $query = "SELECT pt.*, p.nama_lengkap FROM pertanian pt LEFT JOIN penduduk p ON pt.nik_petani = p.nik WHERE pt.id_pertanian = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function createPertanian($data) {
        try {
            $data['id_desa'] = $this->id_desa;
            
            $query = "INSERT INTO pertanian (nik_petani, id_desa, luas_lahan, jenis_komoditas, jenis_lahan, status_kepemilikan, produksi_per_musim, satuan_produksi, pendapatan_per_musim, musim_tanam, bantuan_pertanian, jenis_bantuan, teknologi_pertanian, irigasi, deskripsi, jumlah_pelaku, nilai_ekonomi, lokasi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['nik_petani'], $data['id_desa'], $data['luas_lahan'], $data['jenis_komoditas'], $data['jenis_lahan'],
                $data['status_kepemilikan'] ?? 'milik_sendiri',
                $data['produksi_per_musim'] ?? null,
                $data['satuan_produksi'] ?? 'kg',
                $data['pendapatan_per_musim'] ?? null,
                $data['musim_tanam'] ?? 'sepanjang_tahun',
                $data['bantuan_pertanian'] ?? 'tidak_ada',
                $data['jenis_bantuan'] ?? null,
                $data['teknologi_pertanian'] ?? 'tradisional',
                $data['irigasi'] ?? 'tadah_hujan',
                $data['deskripsi'] ?? null,
                $data['jumlah_pelaku'] ?? 1,
                $data['nilai_ekonomi'] ?? null,
                $data['lokasi'] ?? null
            ]);
            
            return ['success' => $result, 'message' => 'Data pertanian berhasil ditambahkan'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function updatePertanian($data) {
        try {
            $query = "UPDATE pertanian SET nik_petani = ?, luas_lahan = ?, jenis_komoditas = ?, jenis_lahan = ?, status_kepemilikan = ?, produksi_per_musim = ?, satuan_produksi = ?, pendapatan_per_musim = ?, musim_tanam = ?, bantuan_pertanian = ?, jenis_bantuan = ?, teknologi_pertanian = ?, irigasi = ?, deskripsi = ?, jumlah_pelaku = ?, nilai_ekonomi = ?, lokasi = ? WHERE id_pertanian = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['nik_petani'], $data['luas_lahan'], $data['jenis_komoditas'], $data['jenis_lahan'],
                $data['status_kepemilikan'] ?? 'milik_sendiri', $data['produksi_per_musim'], $data['satuan_produksi'],
                $data['pendapatan_per_musim'], $data['musim_tanam'], $data['bantuan_pertanian'] ?? 'tidak_ada',
                $data['jenis_bantuan'], $data['teknologi_pertanian'], $data['irigasi'], $data['deskripsi'],
                $data['jumlah_pelaku'], $data['nilai_ekonomi'], $data['lokasi'], $data['id_pertanian']
            ]);
            
            return ['success' => true, 'message' => 'Data pertanian berhasil diupdate'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function deletePertanian($id) {
        try {
            $query = "DELETE FROM pertanian WHERE id_pertanian = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id]);
            
            return ['success' => $result, 'message' => 'Data pertanian berhasil dihapus'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // PASAR METHODS
    public function getPasar($page = 1, $limit = 10, $search = '') {
        try {
            $offset = ($page - 1) * $limit;
            
            $where = "WHERE id_desa = ?";
            $params = [$this->id_desa];
            
            if ($search) {
                $where .= " AND (nama_pasar LIKE ? OR jenis_pasar LIKE ? OR alamat_pasar LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $countQuery = "SELECT COUNT(*) as total FROM pasar $where";
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $query = "SELECT * FROM pasar $where ORDER BY nama_pasar LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($query);
            for ($i = 0; $i < count($params); $i++) {
                $stmt->bindValue($i + 1, $params[$i]);
            }
            $stmt->bindValue(count($params) + 1, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(count($params) + 2, (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $pagination = [
                'current_page' => (int)$page,
                'total_pages' => ceil($total / $limit),
                'total_items' => (int)$total,
                'items_per_page' => (int)$limit
            ];

            return ['data' => $result, 'pagination' => $pagination];
        } catch (Exception $e) {
            return ['data' => [], 'pagination' => ['current_page' => 1, 'total_pages' => 0, 'total_items' => 0, 'items_per_page' => $limit]];
        }
    }
    
    public function getPasarDetail($id) {
        try {
            $query = "SELECT * FROM pasar WHERE id_pasar = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function createPasar($data) {
        try {
            if (isset($_SESSION['pasar_submit_flag']) && (time() - $_SESSION['pasar_submit_flag']) < 3) {
                return ['success' => false, 'message' => 'Permintaan sedang diproses, harap tunggu'];
            }
            $_SESSION['pasar_submit_flag'] = time();
            
            $data['id_desa'] = $this->id_desa;
            
            $query = "INSERT INTO pasar (id_desa, nama_pasar, jenis_pasar, jumlah_pedagang, hari_operasional, alamat_pasar, deskripsi, hasil_produksi, nilai_ekonomi, kondisi_fasilitas, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['id_desa'], $data['nama_pasar'], $data['jenis_pasar'], 
                $data['jumlah_pedagang'], $data['hari_operasional'], $data['alamat_pasar'],
                $data['deskripsi'] ?? null, $data['hasil_produksi'] ?? null, $data['nilai_ekonomi'] ?? null,
                $data['kondisi_fasilitas'] ?? 'Baik'
            ]);
            
            unset($_SESSION['pasar_submit_flag']);
            
            return ['success' => $result, 'message' => $result ? 'Data pasar berhasil ditambahkan' : 'Gagal menambahkan data pasar'];
        } catch (Exception $e) {
            unset($_SESSION['pasar_submit_flag']);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function updatePasar($data) {
        try {
            $query = "UPDATE pasar SET nama_pasar = ?, jenis_pasar = ?, jumlah_pedagang = ?, hari_operasional = ?, alamat_pasar = ?, deskripsi = ?, hasil_produksi = ?, nilai_ekonomi = ?, kondisi_fasilitas = ? WHERE id_pasar = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['nama_pasar'], $data['jenis_pasar'], $data['jumlah_pedagang'], $data['hari_operasional'],
                $data['alamat_pasar'], $data['deskripsi'], $data['hasil_produksi'], $data['nilai_ekonomi'],
                $data['kondisi_fasilitas'], $data['id_pasar']
            ]);
            
            return ['success' => $result, 'message' => 'Data pasar berhasil diupdate'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function deletePasar($id) {
        try {
            $query = "DELETE FROM pasar WHERE id_pasar = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id]);
            
            return ['success' => $result, 'message' => 'Data pasar berhasil dihapus'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // BANTUAN SOSIAL METHODS
    public function getBantuanSosial($search = '') {
        try {
            $query = "SELECT wm.*, p.nama_lengkap FROM warga_miskin wm LEFT JOIN penduduk p ON wm.nik = p.nik WHERE wm.id_desa = ?";
            $params = [$this->id_desa];
            
            if ($search) {
                $query .= " AND (p.nama_lengkap LIKE ? OR wm.nik LIKE ? OR wm.jenis_bantuan LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $query .= " ORDER BY p.nama_lengkap LIMIT 50";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $result;
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getBantuanDetail($id) {
        try {
            $query = "SELECT wm.*, p.nama_lengkap FROM warga_miskin wm LEFT JOIN penduduk p ON wm.nik = p.nik WHERE wm.id_warga_miskin = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function createBantuan($data) {
        try {
            if (isset($_SESSION['bantuan_submit_flag']) && (time() - $_SESSION['bantuan_submit_flag']) < 3) {
                return ['success' => false, 'message' => 'Permintaan sedang diproses, harap tunggu'];
            }
            $_SESSION['bantuan_submit_flag'] = time();
            
            $data['id_desa'] = $this->id_desa;
            
            $query = "INSERT INTO warga_miskin (id_desa, nik, jenis_bantuan, nominal_bantuan, periode_bantuan, status_penerima, created_at) VALUES (?, ?, ?, ?, ?, 'aktif', NOW())";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['id_desa'], $data['nik'], $data['jenis_bantuan'], 
                $data['nominal_bantuan'], $data['periode_bantuan']
            ]);
            
            unset($_SESSION['bantuan_submit_flag']);
            
            return ['success' => $result, 'message' => $result ? 'Data bantuan sosial berhasil ditambahkan' : 'Gagal menambahkan data bantuan sosial'];
        } catch (Exception $e) {
            unset($_SESSION['bantuan_submit_flag']);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function updateBantuan($data) {
        try {
            $query = "UPDATE warga_miskin SET nik = ?, jenis_bantuan = ?, nominal_bantuan = ?, periode_bantuan = ?, status_penerima = ? WHERE id_warga_miskin = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['nik'], $data['jenis_bantuan'], $data['nominal_bantuan'], 
                $data['periode_bantuan'], $data['status_penerima'], $data['id_warga_miskin']
            ]);
            
            return ['success' => $result, 'message' => 'Data bantuan sosial berhasil diupdate'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function deleteBantuan($id) {
        try {
            $query = "DELETE FROM warga_miskin WHERE id_warga_miskin = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id]);
            
            return ['success' => $result, 'message' => 'Data bantuan sosial berhasil dihapus'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function searchPenduduk($search = '') {
        try {
            $query = "SELECT nik, nama_lengkap FROM penduduk WHERE id_desa = ?";
            $params = [$this->id_desa];
            
            if ($search) {
                $query .= " AND (nama_lengkap LIKE ? OR nik LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $query .= " ORDER BY nama_lengkap LIMIT 10";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function importMataPencaharian($file) {
        try {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if ($extension === 'xlsx') {
                return $this->importMataPencaharianXLSX($file);
            } else {
                return ['success' => false, 'message' => 'Format file tidak didukung. Gunakan .xlsx'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    private function importMataPencaharianXLSX($file) {
        $imported = 0;
        $logs = [];
        
        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) !== TRUE) {
            return ['success' => false, 'message' => 'File Excel tidak dapat dibaca'];
        }
        
        $sharedStrings = [];
        $sharedStringsXML = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXML) {
            $xml = simplexml_load_string($sharedStringsXML);
            foreach ($xml->si as $si) {
                $sharedStrings[] = (string)$si->t;
            }
        }
        
        $worksheetXML = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        
        if (!$worksheetXML) {
            return ['success' => false, 'message' => 'Worksheet tidak ditemukan'];
        }
        
        $xml = simplexml_load_string($worksheetXML);
        $rows = $xml->sheetData->row;
        
        $row_num = 0;
        foreach ($rows as $row) {
            $row_num++;
            if ($row_num == 1) continue; // Skip header
            
            $cells = [];
            foreach ($row->c as $cell) {
                $value = '';
                if (isset($cell->v)) {
                    if (isset($cell['t']) && $cell['t'] == 's') {
                        $value = $sharedStrings[(int)$cell->v];
                    } else {
                        $value = (string)$cell->v;
                    }
                }
                $cells[] = $value;
            }
            
            if (empty($cells[0])) continue; // Skip empty NIK
            
            $data = [
                'nik' => trim($cells[0] ?? ''),
                'sektor_pekerjaan' => trim($cells[3] ?? ''),
                'penghasilan_perbulan' => trim($cells[4] ?? ''),
                'status_pekerjaan' => trim($cells[5] ?? '')
            ];
            
            // Update existing or insert new mata_pencaharian record
            $checkQuery = "SELECT COUNT(*) FROM mata_pencaharian WHERE nik = ? AND id_desa = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$data['nik'], $this->id_desa]);
            
            if ($checkStmt->fetchColumn() > 0) {
                // Update existing record
                $query = "UPDATE mata_pencaharian SET sektor_pekerjaan = ?, penghasilan_perbulan = ?, status_pekerjaan = ? WHERE nik = ? AND id_desa = ?";
                $stmt = $this->db->prepare($query);
                $result = $stmt->execute([
                    $data['sektor_pekerjaan'] ?: null,
                    $data['penghasilan_perbulan'] ?: null,
                    $data['status_pekerjaan'] ?: null,
                    $data['nik'],
                    $this->id_desa
                ]);
            } else {
                // Check if NIK exists in penduduk and job is not 'Pelajar'
                $pendudukQuery = "SELECT pekerjaan FROM penduduk WHERE nik = ? AND id_desa = ?";
                $pendudukStmt = $this->db->prepare($pendudukQuery);
                $pendudukStmt->execute([$data['nik'], $this->id_desa]);
                $pekerjaan = $pendudukStmt->fetchColumn();
                
                if ($pekerjaan && strtolower(trim($pekerjaan)) !== 'pelajar') {
                    // Insert new record
                    $query = "INSERT INTO mata_pencaharian (nik, id_desa, jenis_pekerjaan, sektor_pekerjaan, penghasilan_perbulan, status_pekerjaan, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
                    $stmt = $this->db->prepare($query);
                    $result = $stmt->execute([
                        $data['nik'],
                        $this->id_desa,
                        $pekerjaan,
                        $data['sektor_pekerjaan'] ?: null,
                        $data['penghasilan_perbulan'] ?: null,
                        $data['status_pekerjaan'] ?: null
                    ]);
                } else {
                    $result = false;
                }
            }
            
            if ($result && $stmt->rowCount() > 0) {
                $imported++;
            } else {
                $logs[] = "Baris " . $row_num . ": NIK tidak ditemukan atau tidak ada perubahan";
            }
        }
        
        return [
            'success' => true,
            'data' => [
                'imported_count' => $imported,
                'logs' => $logs,
                'total_processed' => $row_num - 1
            ]
        ];
    }
    
    public function exportMataPencaharian() {
        try {
            $query = "SELECT mp.nik, p.nama_lengkap, mp.jenis_pekerjaan, mp.sektor_pekerjaan, mp.penghasilan_perbulan, mp.status_pekerjaan FROM mata_pencaharian mp LEFT JOIN penduduk p ON mp.nik = p.nik WHERE mp.id_desa = ? ORDER BY p.nama_lengkap";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$this->id_desa]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $filename = 'data_mata_pencaharian_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            // Create XLSX using native PHP
            $this->createMataPencaharianXLSX($data, $filename);
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    private function createMataPencaharianXLSX($data, $filename) {
        $zip = new ZipArchive();
        $temp_file = tempnam(sys_get_temp_dir(), 'xlsx');
        
        if ($zip->open($temp_file, ZipArchive::CREATE) !== TRUE) {
            throw new Exception('Cannot create XLSX file');
        }
        
        // Add required files for XLSX
        $zip->addFromString('[Content_Types].xml', $this->getContentTypes());
        $zip->addFromString('_rels/.rels', $this->getRels());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->getWorkbookRels());
        $zip->addFromString('xl/workbook.xml', $this->getWorkbook());
        $zip->addFromString('xl/styles.xml', $this->getMataPencaharianStyles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->getMataPencaharianWorksheet($data));
        
        $zip->close();
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        readfile($temp_file);
        unlink($temp_file);
        exit;
    }
    
    private function getContentTypes() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>';
    }
    
    private function getRels() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
    }
    
    private function getWorkbookRels() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
    }
    
    private function getWorkbook() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets>
<sheet name="Sheet1" sheetId="1" r:id="rId1"/>
</sheets>
</workbook>';
    }
    
    private function getMataPencaharianStyles() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<numFmts count="1">
<numFmt numFmtId="164" formatCode="@"/>
</numFmts>
<fonts count="1">
<font>
<sz val="11"/>
<name val="Calibri"/>
</font>
</fonts>
<fills count="1">
<fill>
<patternFill patternType="none"/>
</fill>
</fills>
<borders count="1">
<border>
<left/>
<right/>
<top/>
<bottom/>
<diagonal/>
</border>
</borders>
<cellStyleXfs count="1">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
</cellStyleXfs>
<cellXfs count="2">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0"/>
</cellXfs>
</styleSheet>';
    }
    
    private function getMataPencaharianWorksheet($data) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<sheetData>';
        
        // Header row
        $xml .= '<row r="1">';
        $headers = ['NIK', 'Nama Lengkap', 'Jenis Pekerjaan', 'Sektor Pekerjaan', 'Penghasilan Per Bulan', 'Status Pekerjaan'];
        $col = 'A';
        foreach ($headers as $header) {
            $xml .= '<c r="' . $col . '1" t="inlineStr"><is><t>' . htmlspecialchars($header) . '</t></is></c>';
            $col++;
        }
        $xml .= '</row>';
        
        // Data rows
        $row = 2;
        foreach ($data as $item) {
            $xml .= '<row r="' . $row . '">';
            $col = 'A';
            foreach ($item as $key => $value) {
                if ($key === 'nik') {
                    // Use text format style for NIK
                    $xml .= '<c r="' . $col . $row . '" s="1" t="inlineStr"><is><t>' . htmlspecialchars($value ?? '') . '</t></is></c>';
                } else {
                    $xml .= '<c r="' . $col . $row . '" t="inlineStr"><is><t>' . htmlspecialchars($value ?? '') . '</t></is></c>';
                }
                $col++;
            }
            $xml .= '</row>';
            $row++;
        }
        
        $xml .= '</sheetData>
</worksheet>';
        return $xml;
    }
    
    public function exportPertanian() {
        try {
            $query = "SELECT pt.nik_petani, p.nama_lengkap, pt.jenis_komoditas, pt.luas_lahan, pt.jenis_lahan, pt.status_kepemilikan, pt.produksi_per_musim, pt.satuan_produksi, pt.pendapatan_per_musim, pt.musim_tanam, pt.bantuan_pertanian, pt.jenis_bantuan, pt.teknologi_pertanian, pt.irigasi, pt.jumlah_pelaku, pt.nilai_ekonomi, pt.lokasi, pt.deskripsi FROM pertanian pt LEFT JOIN penduduk p ON pt.nik_petani = p.nik WHERE pt.id_desa = ? ORDER BY p.nama_lengkap";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$this->id_desa]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $filename = 'data_pertanian_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            $this->createPertanianXLSX($data, $filename);
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    private function createPertanianXLSX($data, $filename) {
        $zip = new ZipArchive();
        $temp_file = tempnam(sys_get_temp_dir(), 'xlsx');
        
        if ($zip->open($temp_file, ZipArchive::CREATE) !== TRUE) {
            throw new Exception('Cannot create XLSX file');
        }
        
        $zip->addFromString('[Content_Types].xml', $this->getContentTypes());
        $zip->addFromString('_rels/.rels', $this->getRels());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->getWorkbookRels());
        $zip->addFromString('xl/workbook.xml', $this->getWorkbook());
        $zip->addFromString('xl/styles.xml', $this->getPertanianStyles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->getPertanianWorksheet($data));
        
        $zip->close();
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        readfile($temp_file);
        unlink($temp_file);
        exit;
    }
    
    private function getPertanianStyles() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<numFmts count="1">
<numFmt numFmtId="164" formatCode="@"/>
</numFmts>
<fonts count="1">
<font>
<sz val="11"/>
<name val="Calibri"/>
</font>
</fonts>
<fills count="1">
<fill>
<patternFill patternType="none"/>
</fill>
</fills>
<borders count="1">
<border>
<left/>
<right/>
<top/>
<bottom/>
<diagonal/>
</border>
</borders>
<cellStyleXfs count="1">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
</cellStyleXfs>
<cellXfs count="2">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0"/>
</cellXfs>
</styleSheet>';
    }
    
    private function getPertanianWorksheet($data) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<sheetData>';
        
        $xml .= '<row r="1">';
        $headers = ['NIK Petani', 'Nama Petani', 'Jenis Komoditas', 'Luas Lahan (Ha)', 'Jenis Lahan', 'Status Kepemilikan', 'Produksi Per Musim', 'Satuan Produksi', 'Pendapatan Per Musim', 'Musim Tanam', 'Bantuan Pertanian', 'Jenis Bantuan', 'Teknologi Pertanian', 'Irigasi', 'Jumlah Pelaku', 'Nilai Ekonomi', 'Lokasi', 'Deskripsi'];
        $col = 'A';
        foreach ($headers as $header) {
            $xml .= '<c r="' . $col . '1" t="inlineStr"><is><t>' . htmlspecialchars($header) . '</t></is></c>';
            $col++;
        }
        $xml .= '</row>';
        
        $row = 2;
        foreach ($data as $item) {
            $xml .= '<row r="' . $row . '">';
            $col = 'A';
            foreach ($item as $key => $value) {
                if ($key === 'nik_petani') {
                    $xml .= '<c r="' . $col . $row . '" s="1" t="inlineStr"><is><t>' . htmlspecialchars($value ?? '') . '</t></is></c>';
                } else {
                    $xml .= '<c r="' . $col . $row . '" t="inlineStr"><is><t>' . htmlspecialchars($value ?? '') . '</t></is></c>';
                }
                $col++;
            }
            $xml .= '</row>';
            $row++;
        }
        
        $xml .= '</sheetData>
</worksheet>';
        return $xml;
    }
    
    public function importPertanian($file) {
        try {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if ($extension === 'xlsx') {
                return $this->importPertanianXLSX($file);
            } else {
                return ['success' => false, 'message' => 'Format file tidak didukung. Gunakan .xlsx'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    private function importPertanianXLSX($file) {
        $imported = 0;
        $logs = [];
        
        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) !== TRUE) {
            return ['success' => false, 'message' => 'File Excel tidak dapat dibaca'];
        }
        
        $sharedStrings = [];
        $sharedStringsXML = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXML) {
            $xml = simplexml_load_string($sharedStringsXML);
            foreach ($xml->si as $si) {
                $sharedStrings[] = (string)$si->t;
            }
        }
        
        $worksheetXML = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        
        if (!$worksheetXML) {
            return ['success' => false, 'message' => 'Worksheet tidak ditemukan'];
        }
        
        $xml = simplexml_load_string($worksheetXML);
        $rows = $xml->sheetData->row;
        
        $row_num = 0;
        foreach ($rows as $row) {
            $row_num++;
            if ($row_num == 1) continue;
            
            $cells = [];
            foreach ($row->c as $cell) {
                $value = '';
                if (isset($cell->v)) {
                    if (isset($cell['t']) && $cell['t'] == 's') {
                        $value = $sharedStrings[(int)$cell->v];
                    } else {
                        $value = (string)$cell->v;
                    }
                }
                $cells[] = $value;
            }
            
            if (empty($cells[0])) continue;
            
            $data = [
                'nik_petani' => trim($cells[0] ?? ''),
                'jenis_komoditas' => trim($cells[2] ?? '') ?: null,
                'luas_lahan' => trim($cells[3] ?? '') ?: null,
                'jenis_lahan' => trim($cells[4] ?? '') ?: null,
                'status_kepemilikan' => trim($cells[5] ?? '') ?: null,
                'produksi_per_musim' => trim($cells[6] ?? '') ?: null,
                'satuan_produksi' => trim($cells[7] ?? '') ?: null,
                'pendapatan_per_musim' => trim($cells[8] ?? '') ?: null,
                'musim_tanam' => trim($cells[9] ?? '') ?: null,
                'bantuan_pertanian' => trim($cells[10] ?? '') ?: null,
                'jenis_bantuan' => trim($cells[11] ?? '') ?: null,
                'teknologi_pertanian' => trim($cells[12] ?? '') ?: null,
                'irigasi' => trim($cells[13] ?? '') ?: null,
                'jumlah_pelaku' => trim($cells[14] ?? '') ?: null,
                'nilai_ekonomi' => trim($cells[15] ?? '') ?: null,
                'lokasi' => trim($cells[16] ?? '') ?: null,
                'deskripsi' => trim($cells[17] ?? '') ?: null
            ];
            
            // Update existing or insert new pertanian record
            $checkQuery = "SELECT COUNT(*) FROM pertanian WHERE nik_petani = ? AND id_desa = ?";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$data['nik_petani'], $this->id_desa]);
            
            if ($checkStmt->fetchColumn() > 0) {
                // Update existing record
                $query = "UPDATE pertanian SET jenis_komoditas = ?, luas_lahan = ?, jenis_lahan = ?, status_kepemilikan = ?, produksi_per_musim = ?, satuan_produksi = ?, pendapatan_per_musim = ?, musim_tanam = ?, bantuan_pertanian = ?, jenis_bantuan = ?, teknologi_pertanian = ?, irigasi = ?, jumlah_pelaku = ?, nilai_ekonomi = ?, lokasi = ?, deskripsi = ? WHERE nik_petani = ? AND id_desa = ?";
                $stmt = $this->db->prepare($query);
                $result = $stmt->execute([
                    $data['jenis_komoditas'], $data['luas_lahan'], $data['jenis_lahan'], $data['status_kepemilikan'],
                    $data['produksi_per_musim'], $data['satuan_produksi'], $data['pendapatan_per_musim'], $data['musim_tanam'],
                    $data['bantuan_pertanian'], $data['jenis_bantuan'], $data['teknologi_pertanian'], $data['irigasi'],
                    $data['jumlah_pelaku'], $data['nilai_ekonomi'], $data['lokasi'], $data['deskripsi'],
                    $data['nik_petani'], $this->id_desa
                ]);
            } else {
                // Check if NIK exists in penduduk and job is 'Petani'
                $pendudukQuery = "SELECT pekerjaan FROM penduduk WHERE nik = ? AND id_desa = ?";
                $pendudukStmt = $this->db->prepare($pendudukQuery);
                $pendudukStmt->execute([$data['nik_petani'], $this->id_desa]);
                $pekerjaan = $pendudukStmt->fetchColumn();
                
                if ($pekerjaan && strtolower(trim($pekerjaan)) === 'petani') {
                    // Insert new record
                    $query = "INSERT INTO pertanian (nik_petani, id_desa, jenis_komoditas, luas_lahan, jenis_lahan, status_kepemilikan, produksi_per_musim, satuan_produksi, pendapatan_per_musim, musim_tanam, bantuan_pertanian, jenis_bantuan, teknologi_pertanian, irigasi, jumlah_pelaku, nilai_ekonomi, lokasi, deskripsi, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                    $stmt = $this->db->prepare($query);
                    $result = $stmt->execute([
                        $data['nik_petani'], $this->id_desa, $data['jenis_komoditas'], $data['luas_lahan'], $data['jenis_lahan'],
                        $data['status_kepemilikan'], $data['produksi_per_musim'], $data['satuan_produksi'], $data['pendapatan_per_musim'],
                        $data['musim_tanam'], $data['bantuan_pertanian'], $data['jenis_bantuan'], $data['teknologi_pertanian'],
                        $data['irigasi'], $data['jumlah_pelaku'], $data['nilai_ekonomi'], $data['lokasi'], $data['deskripsi']
                    ]);
                } else {
                    $result = false;
                }
            }
            
            if ($result && $stmt->rowCount() > 0) {
                $imported++;
            } else {
                $logs[] = "Baris " . $row_num . ": NIK tidak ditemukan atau tidak ada perubahan";
            }
        }
        
        return [
            'success' => true,
            'data' => [
                'imported_count' => $imported,
                'logs' => $logs,
                'total_processed' => $row_num - 1
            ]
        ];
    }
    
    public function handleAjaxRequest() {
        if (isset($_POST['action']) && ($_POST['action'] === 'export_mata_pencaharian' || $_POST['action'] === 'export_pertanian')) {
            $this->checkAuthentication();
            if ($_POST['action'] === 'export_mata_pencaharian') {
                $this->exportMataPencaharian();
            } else {
                $this->exportPertanian();
            }
            return;
        }
        
        if (!$this->isAjaxRequest()) return;
        
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        switch ($action) {
            case 'get_mata_pencaharian':
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 10;
                $search = $_GET['search'] ?? '';
                $sektor = $_GET['sektor'] ?? '';
                $data = $this->getMataPencaharian($page, $limit, $search, $sektor);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'get_mata_pencaharian_detail':
                $id = $_GET['id'] ?? null;
                $data = $this->getMataPencaharianDetail($id);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'create_mata_pencaharian':
                $result = $this->createMataPencaharian($_POST);
                echo json_encode($result);
                break;
                
            case 'update_mata_pencaharian':
                $result = $this->updateMataPencaharian($_POST);
                echo json_encode($result);
                break;
                
            case 'delete_mata_pencaharian':
                $id = $_POST['id_pencaharian'] ?? $_GET['id_pencaharian'] ?? null;
                $result = $this->deleteMataPencaharian($id);
                echo json_encode($result);
                break;
                
            // UMKM
            case 'get_umkm':
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 10;
                $search = $_GET['search'] ?? '';
                $data = $this->getUmkm($page, $limit, $search);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'get_umkm_detail':
                $id = $_GET['id'] ?? null;
                $data = $this->getUmkmDetail($id);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'create_umkm':
                $result = $this->createUmkm($_POST);
                echo json_encode($result);
                break;
                
            case 'update_umkm':
                $result = $this->updateUmkm($_POST);
                echo json_encode($result);
                break;
                
            case 'delete_umkm':
                $id = $_POST['id_umkm'] ?? $_GET['id_umkm'] ?? null;
                $result = $this->deleteUmkm($id);
                echo json_encode($result);
                break;
                
            // PERTANIAN
            case 'get_pertanian':
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 10;
                $search = $_GET['search'] ?? '';
                $data = $this->getPertanian($page, $limit, $search);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'get_pertanian_detail':
                $id = $_GET['id'] ?? null;
                $data = $this->getPertanianDetail($id);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'create_pertanian':
                $result = $this->createPertanian($_POST);
                echo json_encode($result);
                break;
                
            case 'update_pertanian':
                $result = $this->updatePertanian($_POST);
                echo json_encode($result);
                break;
                
            case 'delete_pertanian':
                $id = $_POST['id_pertanian'] ?? $_GET['id_pertanian'] ?? null;
                $result = $this->deletePertanian($id);
                echo json_encode($result);
                break;
                
            // PASAR
            case 'get_pasar':
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 10;
                $search = $_GET['search'] ?? '';
                $data = $this->getPasar($page, $limit, $search);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'get_pasar_detail':
                $id = $_GET['id'] ?? null;
                $data = $this->getPasarDetail($id);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'create_pasar':
                $result = $this->createPasar($_POST);
                echo json_encode($result);
                break;
                
            case 'update_pasar':
                $result = $this->updatePasar($_POST);
                echo json_encode($result);
                break;
                
            case 'delete_pasar':
                $id = $_POST['id_pasar'] ?? $_GET['id_pasar'] ?? null;
                $result = $this->deletePasar($id);
                echo json_encode($result);
                break;
                
            // BANTUAN SOSIAL
            case 'get_bantuan_sosial':
                $search = $_GET['search'] ?? '';
                $data = $this->getBantuanSosial($search);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'get_bantuan_detail':
                $id = $_GET['id'] ?? null;
                $data = $this->getBantuanDetail($id);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'create_bantuan':
                $result = $this->createBantuan($_POST);
                echo json_encode($result);
                break;
                
            case 'update_bantuan':
                $result = $this->updateBantuan($_POST);
                echo json_encode($result);
                break;
                
            case 'delete_bantuan':
                $id = $_POST['id_warga_miskin'] ?? $_GET['id_warga_miskin'] ?? null;
                $result = $this->deleteBantuan($id);
                echo json_encode($result);
                break;
                
            case 'import_pertanian':
                if (isset($_FILES['excel_file'])) {
                    $result = $this->importPertanian($_FILES['excel_file']);
                    echo json_encode($result);
                } else {
                    echo json_encode(['success' => false, 'message' => 'File tidak ditemukan']);
                }
                break;
                
            case 'import_mata_pencaharian':
                if (isset($_FILES['excel_file'])) {
                    $result = $this->importMataPencaharian($_FILES['excel_file']);
                    echo json_encode($result);
                } else {
                    echo json_encode(['success' => false, 'message' => 'File tidak ditemukan']);
                }
                break;
                
            case 'penduduk_search':
                $search = $_GET['search'] ?? '';
                $data = $this->searchPenduduk($search);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            default:
                if ($action) {
                    echo json_encode(['success' => false, 'message' => 'Action not found']);
                } else {
                    return;
                }
        }
        exit();
    }
}

// Entry point for direct access
if (isset($_GET['action']) || isset($_POST['action'])) {
    $ekonomi = new EkonomiFunctions();
    $ekonomi->handleAjaxRequest();
}
?>