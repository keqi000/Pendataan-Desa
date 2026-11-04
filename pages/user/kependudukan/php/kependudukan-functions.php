<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/session.php';
require_once __DIR__ . '/../../../../database/queries.php';
require_once __DIR__ . '/PekerjaanManager.php';

class KependudukanFunctions {
    private $queries;
    private $db;
    private $id_desa;
    private $pekerjaanManager;
    
    public function __construct() {
        try {
            $this->queries = new Queries();
            
            $database = new Database();
            $this->db = $database->getConnection();
            
            if (!$this->db) {
                throw new Exception("Database connection failed");
            }
            
            $this->pekerjaanManager = new PekerjaanManager();
            
            // Initialize session first
            if (initSession()) {
                $this->id_desa = $_SESSION['user_desa'] ?? null;
            } else {
                $this->id_desa = null;
            }
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function checkAuthentication() {
        if (!initSession() || !validateSession()) {
            if ($this->isAjaxRequest()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Session tidak valid',
                    'redirect' => BASE_URL . 'pages/auth/login.php'
                ]);
                exit();
            }
            header('Location: ' . BASE_URL . 'pages/auth/login.php');
            exit();
        }
        if ($_SESSION['user_role'] !== 'operator_desa') {
            if ($this->isAjaxRequest()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Akses ditolak',
                    'redirect' => BASE_URL . 'pages/auth/login.php'
                ]);
                exit();
            }
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
            'page_title' => 'Data Kependudukan',
            'current_page' => 'kependudukan',
            'page_css' => [BASE_URL . 'pages/user/kependudukan/kependudukan.css'],
            'page_js' => [
                BASE_URL . 'pages/user/kependudukan/js/kependudukan-core.js',
                BASE_URL . 'pages/user/kependudukan/js/kependudukan-penduduk.js',
                BASE_URL . 'pages/user/kependudukan/js/kependudukan-keluarga.js',
                BASE_URL . 'pages/user/kependudukan/js/kependudukan-auto-transform.js',
                BASE_URL . 'pages/user/kependudukan/kependudukan.js'
            ]
        ];
    }
    
    public function getPendudukOptions($search = '') {
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
    
    // PENDUDUK METHODS
    public function getPenduduk($page = 1, $limit = 10, $search = '', $jenis_kelamin = '', $usia = '') {
        try {
            $offset = ($page - 1) * $limit;
            
            $where = "WHERE id_desa = ?";
            $params = [$this->id_desa];
            
            if (!$this->id_desa) {
                return ['data' => [], 'pagination' => ['current_page' => 1, 'total_pages' => 0, 'total_items' => 0, 'items_per_page' => $limit, 'page' => 1, 'limit' => $limit, 'total' => 0]];
            }
            
            if ($search) {
                $where .= " AND (nama_lengkap LIKE ? OR nik LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if ($jenis_kelamin) {
                $where .= " AND jenis_kelamin = ?";
                $params[] = $jenis_kelamin;
            }
            
            if ($usia) {
                switch($usia) {
                    case '0-5': $where .= " AND usia BETWEEN 0 AND 5"; break;
                    case '6-12': $where .= " AND usia BETWEEN 6 AND 12"; break;
                    case '13-17': $where .= " AND usia BETWEEN 13 AND 17"; break;
                    case '18-60': $where .= " AND usia BETWEEN 18 AND 60"; break;
                    case '60+': $where .= " AND usia > 60"; break;
                }
            }
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM penduduk $where";
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get data with proper LIMIT and OFFSET
            $query = "SELECT * FROM penduduk $where ORDER BY nama_lengkap LIMIT $limit OFFSET $offset";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $pagination = [
                'current_page' => (int)$page,
                'total_pages' => ceil($total / $limit),
                'total_items' => (int)$total,
                'items_per_page' => (int)$limit,
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => (int)$total,
                'total_pages' => ceil($total / $limit)
            ];

            return ['data' => $result, 'pagination' => $pagination];
        } catch (Exception $e) {
            return ['data' => [], 'pagination' => ['current_page' => 1, 'total_pages' => 0, 'total_items' => 0, 'items_per_page' => $limit, 'page' => 1, 'limit' => $limit, 'total' => 0]];
        }
    }
    
    public function getPendudukDetail($nik) {
        try {
            $query = "SELECT * FROM penduduk WHERE nik = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$nik]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function createPenduduk($data) {
        try {
            if (isset($_SESSION['penduduk_submit_flag']) && (time() - $_SESSION['penduduk_submit_flag']) < 3) {
                return ['success' => false, 'message' => 'Permintaan sedang diproses, harap tunggu'];
            }
            $_SESSION['penduduk_submit_flag'] = time();
            
            $data['id_desa'] = $this->id_desa;
            
            // Check NIK uniqueness
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM penduduk WHERE nik = ?");
            $checkStmt->execute([$data['nik']]);
            if ($checkStmt->fetchColumn() > 0) {
                unset($_SESSION['penduduk_submit_flag']);
                return ['success' => false, 'message' => 'NIK sudah terdaftar. Gunakan NIK yang berbeda.'];
            }
            
            // Normalize pekerjaan using PekerjaanManager
            if (!empty($data['pekerjaan'])) {
                $data['pekerjaan'] = $this->pekerjaanManager->normalizePekerjaan($data['pekerjaan']);
            }
            
            // Calculate age
            $birthDate = new DateTime($data['tanggal_lahir']);
            $today = new DateTime();
            $data['usia'] = $today->diff($birthDate)->y;
            
            $query = "INSERT INTO penduduk (nik, id_desa, id_keluarga, nama_lengkap, jenis_kelamin, tanggal_lahir, usia, agama, pendidikan_terakhir, pekerjaan, status_pernikahan, status_migrasi, alamat, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['nik'], $data['id_desa'], null, $data['nama_lengkap'],
                $data['jenis_kelamin'], $data['tanggal_lahir'], $data['usia'], $data['agama'],
                $data['pendidikan_terakhir'], $data['pekerjaan'] ?? null, $data['status_pernikahan'] ?? 'Belum Menikah', $data['status_migrasi'] ?? 'Menetap', $data['alamat'] ?? null
            ]);
            
            // Insert into mata_pencaharian if pekerjaan is provided and not 'Pelajar'
            if ($result && !empty($data['pekerjaan']) && strtolower(trim($data['pekerjaan'])) !== 'pelajar') {
                $query_mp = "INSERT INTO mata_pencaharian (nik, id_desa, jenis_pekerjaan, sektor_pekerjaan, penghasilan_perbulan, status_pekerjaan, created_at) VALUES (?, ?, ?, NULL, NULL, NULL, NOW())";
                $stmt_mp = $this->db->prepare($query_mp);
                $stmt_mp->execute([$data['nik'], $data['id_desa'], $data['pekerjaan']]);
            }
            
            // Insert into pertanian if pekerjaan is 'Petani'
            if ($result && !empty($data['pekerjaan']) && strtolower(trim($data['pekerjaan'])) === 'petani') {
                $query_pt = "INSERT INTO pertanian (nik_petani, id_desa, luas_lahan, jenis_komoditas, jenis_lahan, created_at) VALUES (?, ?, NULL, NULL, NULL, NOW())";
                $stmt_pt = $this->db->prepare($query_pt);
                $stmt_pt->execute([$data['nik'], $data['id_desa']]);
            }
            
            unset($_SESSION['penduduk_submit_flag']);
            
            return ['success' => $result, 'message' => $result ? 'Penduduk berhasil ditambahkan' : 'Gagal menambahkan data penduduk'];
        } catch (Exception $e) {
            unset($_SESSION['penduduk_submit_flag']);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function updatePenduduk($data) {
        try {
            $nik_original = $data['nik_original'] ?? $data['nik'];
            
            // Get original pekerjaan
            $query_original = "SELECT pekerjaan FROM penduduk WHERE nik = ?";
            $stmt_original = $this->db->prepare($query_original);
            $stmt_original->execute([$nik_original]);
            $original_pekerjaan = $stmt_original->fetchColumn();
            
            // Check NIK uniqueness only if NIK is being changed
            if ($data['nik'] !== $nik_original) {
                $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM penduduk WHERE nik = ?");
                $checkStmt->execute([$data['nik']]);
                if ($checkStmt->fetchColumn() > 0) {
                    return ['success' => false, 'message' => 'NIK sudah terdaftar. Gunakan NIK yang berbeda.'];
                }
            }
            
            // Normalize pekerjaan using PekerjaanManager
            if (!empty($data['pekerjaan'])) {
                $data['pekerjaan'] = $this->pekerjaanManager->normalizePekerjaan($data['pekerjaan']);
            }
            
            // Calculate age
            $birthDate = new DateTime($data['tanggal_lahir']);
            $today = new DateTime();
            $data['usia'] = $today->diff($birthDate)->y;
            
            $query = "UPDATE penduduk SET nik = ?, nama_lengkap = ?, jenis_kelamin = ?, tanggal_lahir = ?, usia = ?, agama = ?, pendidikan_terakhir = ?, pekerjaan = ?, status_pernikahan = ?, status_migrasi = ?, alamat = ? WHERE nik = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['nik'], $data['nama_lengkap'], $data['jenis_kelamin'], 
                $data['tanggal_lahir'], $data['usia'], $data['agama'],
                $data['pendidikan_terakhir'], $data['pekerjaan'] ?? null, 
                $data['status_pernikahan'] ?? 'Belum Menikah', 
                $data['status_migrasi'] ?? 'Menetap', $data['alamat'] ?? null,
                $nik_original
            ]);
            
            // Handle mata_pencaharian updates
            if ($result) {
                $new_pekerjaan = trim($data['pekerjaan'] ?? '');
                $original_pekerjaan = trim($original_pekerjaan ?? '');
                
                // If changed from Pelajar to something else, add to mata_pencaharian
                if (strtolower($original_pekerjaan) === 'pelajar' && !empty($new_pekerjaan) && strtolower($new_pekerjaan) !== 'pelajar') {
                    $query_mp = "INSERT INTO mata_pencaharian (nik, id_desa, jenis_pekerjaan, sektor_pekerjaan, penghasilan_perbulan, status_pekerjaan, created_at) VALUES (?, ?, ?, NULL, NULL, NULL, NOW())";
                    $stmt_mp = $this->db->prepare($query_mp);
                    $stmt_mp->execute([$data['nik'], $this->id_desa, $new_pekerjaan]);
                }
                // If changed to Pelajar, remove from mata_pencaharian
                elseif (strtolower($new_pekerjaan) === 'pelajar' && strtolower($original_pekerjaan) !== 'pelajar') {
                    $query_mp_del = "DELETE FROM mata_pencaharian WHERE nik = ?";
                    $stmt_mp_del = $this->db->prepare($query_mp_del);
                    $stmt_mp_del->execute([$data['nik']]);
                }
                // If both are not Pelajar, update mata_pencaharian
                elseif (!empty($new_pekerjaan) && strtolower($new_pekerjaan) !== 'pelajar' && strtolower($original_pekerjaan) !== 'pelajar') {
                    $query_mp_update = "UPDATE mata_pencaharian SET jenis_pekerjaan = ? WHERE nik = ?";
                    $stmt_mp_update = $this->db->prepare($query_mp_update);
                    $stmt_mp_update->execute([$new_pekerjaan, $data['nik']]);
                }
                
                // Handle pertanian updates
                // If changed from non-Petani to Petani, add to pertanian
                if (strtolower($original_pekerjaan) !== 'petani' && strtolower($new_pekerjaan) === 'petani') {
                    $query_pt = "INSERT INTO pertanian (nik_petani, id_desa, luas_lahan, jenis_komoditas, jenis_lahan, created_at) VALUES (?, ?, NULL, NULL, NULL, NOW())";
                    $stmt_pt = $this->db->prepare($query_pt);
                    $stmt_pt->execute([$data['nik'], $this->id_desa]);
                }
                // If changed from Petani to non-Petani, remove from pertanian
                elseif (strtolower($original_pekerjaan) === 'petani' && strtolower($new_pekerjaan) !== 'petani') {
                    $query_pt_del = "DELETE FROM pertanian WHERE nik_petani = ?";
                    $stmt_pt_del = $this->db->prepare($query_pt_del);
                    $stmt_pt_del->execute([$data['nik']]);
                }
            }
            
            return ['success' => $result, 'message' => 'Penduduk berhasil diupdate'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function deletePenduduk($nik) {
        try {
            // Check if this person is kepala keluarga
            $query_kepala = "SELECT k.id_keluarga FROM keluarga k JOIN penduduk p ON k.nama_kepala_keluarga = p.nama_lengkap WHERE p.nik = ? AND p.id_desa = ?";
            $stmt_kepala = $this->db->prepare($query_kepala);
            $stmt_kepala->execute([$nik, $this->id_desa]);
            $id_keluarga_kepala = $stmt_kepala->fetchColumn();
            
            // Delete from mata_pencaharian
            $query_mp = "DELETE FROM mata_pencaharian WHERE nik = ?";
            $stmt_mp = $this->db->prepare($query_mp);
            $stmt_mp->execute([$nik]);
            
            // Delete from pertanian
            $query_pt = "DELETE FROM pertanian WHERE nik_petani = ?";
            $stmt_pt = $this->db->prepare($query_pt);
            $stmt_pt->execute([$nik]);
            
            // Delete from penduduk
            $query = "DELETE FROM penduduk WHERE nik = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$nik]);
            
            // If this person was kepala keluarga, delete the keluarga
            if ($result && $id_keluarga_kepala) {
                $query_del_keluarga = "DELETE FROM keluarga WHERE id_keluarga = ?";
                $stmt_del_keluarga = $this->db->prepare($query_del_keluarga);
                $stmt_del_keluarga->execute([$id_keluarga_kepala]);
            }
            
            return ['success' => $result, 'message' => 'Penduduk berhasil dihapus'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // KELUARGA METHODS
    public function getKeluarga($page = 1, $limit = 10, $search = '') {
        try {
            $offset = ($page - 1) * $limit;
            
            $where = "WHERE k.id_desa = ?";
            $params = [$this->id_desa];
            
            if ($search) {
                $where .= " AND (k.nama_kepala_keluarga LIKE ? OR k.nomor_kk LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM keluarga k $where";
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get data with member count using proper LIMIT and OFFSET
            $query = "SELECT k.*, COUNT(p.nik) as jumlah_anggota 
                     FROM keluarga k 
                     LEFT JOIN penduduk p ON k.id_keluarga = p.id_keluarga 
                     $where 
                     GROUP BY k.id_keluarga 
                     ORDER BY k.nama_kepala_keluarga 
                     LIMIT $limit OFFSET $offset";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $pagination = [
                'current_page' => (int)$page,
                'total_pages' => ceil($total / $limit),
                'total_items' => (int)$total,
                'items_per_page' => (int)$limit,
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => (int)$total,
                'total_pages' => ceil($total / $limit)
            ];

            return ['data' => $result, 'pagination' => $pagination];
        } catch (Exception $e) {
            return ['data' => [], 'pagination' => ['current_page' => 1, 'total_pages' => 0, 'total_items' => 0, 'items_per_page' => $limit, 'page' => 1, 'limit' => $limit, 'total' => 0]];
        }
    }
    
    public function getKeluargaDetail($id) {
        try {
            $query = "SELECT k.*, p.nik as nik_kepala_keluarga FROM keluarga k LEFT JOIN penduduk p ON k.nama_kepala_keluarga = p.nama_lengkap WHERE k.id_keluarga = ? AND k.id_desa = p.id_desa LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function createKeluarga($data) {
        try {
            if (isset($_SESSION['keluarga_submit_flag']) && (time() - $_SESSION['keluarga_submit_flag']) < 3) {
                return ['success' => false, 'message' => 'Permintaan sedang diproses, harap tunggu'];
            }
            $_SESSION['keluarga_submit_flag'] = time();
            
            $data['id_desa'] = $this->id_desa;
            
            if (empty($data['nik_kepala_keluarga'])) {
                unset($_SESSION['keluarga_submit_flag']);
                return ['success' => false, 'message' => 'Pilih nama kepala keluarga sesuai dengan data penduduk yang ada'];
            }
            
            // Check if kepala keluarga already exists
            $checkKepalaStmt = $this->db->prepare("SELECT COUNT(*) FROM keluarga WHERE nama_kepala_keluarga = ? AND id_desa = ?");
            $checkKepalaStmt->execute([$data['nama_kepala_keluarga'], $data['id_desa']]);
            if ($checkKepalaStmt->fetchColumn() > 0) {
                unset($_SESSION['keluarga_submit_flag']);
                return ['success' => false, 'message' => 'Kepala keluarga sudah terdaftar di keluarga lain'];
            }
            
            $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM penduduk WHERE nik = ? AND id_desa = ?");
            $checkStmt->execute([$data['nik_kepala_keluarga'], $data['id_desa']]);
            if ($checkStmt->fetchColumn() == 0) {
                unset($_SESSION['keluarga_submit_flag']);
                return ['success' => false, 'message' => 'NIK kepala keluarga tidak ditemukan dalam data penduduk'];
            }
            
            $query = "INSERT INTO keluarga (id_desa, nomor_kk, nama_kepala_keluarga, alamat_keluarga, jumlah_anggota, created_at) VALUES (?, ?, ?, ?, 1, NOW())";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$data['id_desa'], $data['nomor_kk'], $data['nama_kepala_keluarga'], $data['alamat_keluarga']]);
            
            if ($result) {
                $id_keluarga = $this->db->lastInsertId();
                $updateStmt = $this->db->prepare("UPDATE penduduk SET id_keluarga = ? WHERE nik = ?");
                $updateStmt->execute([$id_keluarga, $data['nik_kepala_keluarga']]);
                unset($_SESSION['keluarga_submit_flag']);
                return ['success' => true, 'message' => 'Keluarga berhasil ditambahkan'];
            } else {
                unset($_SESSION['keluarga_submit_flag']);
                return ['success' => false, 'message' => 'Gagal menambahkan data keluarga'];
            }
        } catch (Exception $e) {
            unset($_SESSION['keluarga_submit_flag']);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function updateKeluarga($data) {
        try {
            $query = "UPDATE keluarga SET nomor_kk = ?, nama_kepala_keluarga = ?, alamat_keluarga = ? WHERE id_keluarga = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$data['nomor_kk'], $data['nama_kepala_keluarga'], $data['alamat_keluarga'], $data['id_keluarga']]);
            return ['success' => $result, 'message' => 'Keluarga berhasil diupdate'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function deleteKeluarga($id) {
        try {
            $query = "DELETE FROM keluarga WHERE id_keluarga = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id]);
            return ['success' => $result, 'message' => 'Keluarga berhasil dihapus'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getFamilyMembers($id_keluarga) {
        try {
            $query = "SELECT p.*, k.nama_kepala_keluarga, k.nomor_kk FROM penduduk p LEFT JOIN keluarga k ON p.id_keluarga = k.id_keluarga WHERE p.id_keluarga = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_keluarga]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function searchPendudukForFamily($search = '') {
        try {
            $query = "SELECT nik, nama_lengkap, jenis_kelamin, usia FROM penduduk WHERE id_desa = ? AND id_keluarga IS NULL";
            $params = [$this->id_desa];
            
            if ($search) {
                $query .= " AND (nama_lengkap LIKE ? OR nik LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $query .= " ORDER BY nama_lengkap LIMIT 20";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function addFamilyMember($nik, $id_keluarga) {
        try {
            // Update penduduk
            $stmt = $this->db->prepare("UPDATE penduduk SET id_keluarga = ? WHERE nik = ?");
            $stmt->execute([$id_keluarga, $nik]);
            
            // Update jumlah_anggota
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM penduduk WHERE id_keluarga = ?");
            $countStmt->execute([$id_keluarga]);
            $count = $countStmt->fetchColumn();
            
            $updateStmt = $this->db->prepare("UPDATE keluarga SET jumlah_anggota = ? WHERE id_keluarga = ?");
            $updateStmt->execute([$count, $id_keluarga]);
            
            return ['success' => true, 'message' => 'Anggota keluarga berhasil ditambahkan'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function removeFamilyMember($nik, $id_keluarga) {
        try {
            // Remove from family
            $stmt = $this->db->prepare("UPDATE penduduk SET id_keluarga = NULL WHERE nik = ?");
            $stmt->execute([$nik]);
            
            // Update jumlah_anggota
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM penduduk WHERE id_keluarga = ?");
            $countStmt->execute([$id_keluarga]);
            $count = $countStmt->fetchColumn();
            
            $updateStmt = $this->db->prepare("UPDATE keluarga SET jumlah_anggota = ? WHERE id_keluarga = ?");
            $updateStmt->execute([$count, $id_keluarga]);
            
            return ['success' => true, 'message' => 'Anggota keluarga berhasil dihapus'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function importPenduduk($file) {
        try {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if ($extension === 'xlsx') {
                return $this->importExcelXLSX($file);
            } elseif ($extension === 'xls') {
                return $this->importExcelXLS($file);
            } else {
                return $this->importCSV($file);
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    private function importExcelXLSX($file) {
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
                'nama_lengkap' => trim($cells[1] ?? ''),
                'jenis_kelamin' => trim($cells[2] ?? ''),
                'tanggal_lahir' => trim($cells[3] ?? ''),
                'agama' => trim($cells[4] ?? ''),
                'pendidikan_terakhir' => trim($cells[5] ?? ''),
                'pekerjaan' => trim($cells[6] ?? ''),
                'status_pernikahan' => trim($cells[7] ?? ''),
                'status_migrasi' => trim($cells[8] ?? ''),
                'alamat' => trim($cells[9] ?? '')
            ];
            
            // Normalize pekerjaan before creating
            if (!empty($data['pekerjaan'])) {
                $data['pekerjaan'] = $this->pekerjaanManager->normalizePekerjaan($data['pekerjaan']);
            }
            
            $result = $this->createPenduduk($data);
            if ($result['success']) {
                $imported++;
            } else {
                $logs[] = "Baris " . $row_num . ": " . $result['message'];
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
    
    private function importCSV($file) {
        $imported = 0;
        $logs = [];
        
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            return ['success' => false, 'message' => 'Tidak dapat membaca file CSV'];
        }
        
        $row_num = 0;
        while (($row = fgetcsv($handle)) !== FALSE) {
            $row_num++;
            if ($row_num == 1) continue; // Skip header
            
            if (empty($row[0])) continue; // Skip empty NIK
            
            $data = [
                'nik' => trim($row[0] ?? ''),
                'nama_lengkap' => trim($row[1] ?? ''),
                'jenis_kelamin' => trim($row[2] ?? ''),
                'tanggal_lahir' => trim($row[3] ?? ''),
                'agama' => trim($row[4] ?? ''),
                'pendidikan_terakhir' => trim($row[5] ?? ''),
                'pekerjaan' => trim($row[6] ?? ''),
                'status_pernikahan' => trim($row[7] ?? ''),
                'status_migrasi' => trim($row[8] ?? ''),
                'alamat' => trim($row[9] ?? '')
            ];
            
            // Normalize pekerjaan before creating
            if (!empty($data['pekerjaan'])) {
                $data['pekerjaan'] = $this->pekerjaanManager->normalizePekerjaan($data['pekerjaan']);
            }
            
            $result = $this->createPenduduk($data);
            if ($result['success']) {
                $imported++;
            } else {
                $logs[] = "Baris " . $row_num . ": " . $result['message'];
            }
        }
        fclose($handle);
        
        return [
            'success' => true,
            'data' => [
                'imported_count' => $imported,
                'logs' => $logs,
                'total_processed' => $row_num - 1
            ]
        ];
    }
    
    public function exportPenduduk() {
        try {
            $query = "SELECT nik, nama_lengkap, jenis_kelamin, tanggal_lahir, agama, pendidikan_terakhir, pekerjaan, status_pernikahan, status_migrasi, alamat FROM penduduk WHERE id_desa = ? ORDER BY nama_lengkap";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$this->id_desa]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $filename = 'data_penduduk_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            // Create XLSX using native PHP
            $this->createXLSX($data, $filename);
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    private function createXLSX($data, $filename) {
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
        $zip->addFromString('xl/styles.xml', $this->getStyles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->getWorksheet($data));
        
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
    
    private function getStyles() {
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
    
    private function getWorksheet($data) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<sheetData>';
        
        // Header row
        $xml .= '<row r="1">';
        $headers = ['NIK', 'Nama Lengkap', 'Jenis Kelamin', 'Tanggal Lahir', 'Agama', 'Pendidikan Terakhir', 'Pekerjaan', 'Status Pernikahan', 'Status Migrasi', 'Alamat'];
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
                if ($key === 'nik' || $key === 'tanggal_lahir') {
                    // Use text format style (s="1")
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
    
    public function importKeluarga($file) {
        try {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if ($extension === 'xlsx') {
                return $this->importKeluargaExcelXLSX($file);
            } elseif ($extension === 'xls') {
                return $this->importKeluargaExcelXLS($file);
            } else {
                return $this->importKeluargaCSV($file);
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    private function importKeluargaExcelXLSX($file) {
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
            
            if (empty($cells[0])) continue; // Skip empty nomor_kk
            
            $result = $this->processKeluargaImportRow($cells, $row_num);
            if ($result['success']) {
                $imported++;
            } else {
                $logs[] = "Baris " . $row_num . ": " . $result['message'];
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
    
    private function processKeluargaImportRow($cells, $row_num) {
        $nomor_kk = trim($cells[0] ?? '');
        $nik_kepala = trim($cells[1] ?? '');
        $nik_anggota_str = trim($cells[2] ?? '');
        $alamat = trim($cells[3] ?? '');
        
        if (empty($nomor_kk) || empty($nik_kepala)) {
            return ['success' => false, 'message' => 'Nomor KK dan NIK Kepala Keluarga wajib diisi'];
        }
        
        // Get nama kepala keluarga from NIK
        $query = "SELECT nama_lengkap FROM penduduk WHERE nik = ? AND id_desa = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$nik_kepala, $this->id_desa]);
        $nama_kepala = $stmt->fetchColumn();
        
        if (!$nama_kepala) {
            return ['success' => false, 'message' => 'NIK Kepala Keluarga tidak ditemukan dalam data penduduk'];
        }
        
        // Create keluarga
        $keluarga_data = [
            'nomor_kk' => $nomor_kk,
            'nama_kepala_keluarga' => $nama_kepala,
            'nik_kepala_keluarga' => $nik_kepala,
            'alamat_keluarga' => $alamat
        ];
        
        $result = $this->createKeluarga($keluarga_data);
        if (!$result['success']) {
            return $result;
        }
        
        // Get the created keluarga ID
        $query = "SELECT id_keluarga FROM keluarga WHERE nomor_kk = ? AND id_desa = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$nomor_kk, $this->id_desa]);
        $id_keluarga = $stmt->fetchColumn();
        
        if (!$id_keluarga) {
            return ['success' => false, 'message' => 'Gagal mendapatkan ID keluarga yang baru dibuat'];
        }
        
        // Add anggota if provided
        if (!empty($nik_anggota_str)) {
            $nik_anggota_list = array_map('trim', explode(',', $nik_anggota_str));
            
            foreach ($nik_anggota_list as $nik_anggota) {
                if (empty($nik_anggota) || $nik_anggota === $nik_kepala) continue;
                
                // Check if NIK exists and not already in a family
                $query = "SELECT COUNT(*) FROM penduduk WHERE nik = ? AND id_desa = ? AND (id_keluarga IS NULL OR id_keluarga = 0)";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$nik_anggota, $this->id_desa]);
                
                if ($stmt->fetchColumn() > 0) {
                    $add_result = $this->addFamilyMember($nik_anggota, $id_keluarga);
                }
            }
        }
        
        return ['success' => true, 'message' => 'Keluarga berhasil diimport'];
    }
    
    private function importKeluargaExcelXLS($file) {
        return ['success' => false, 'message' => 'Format .xls tidak didukung. Gunakan .xlsx atau .csv'];
    }
    
    private function importKeluargaCSV($file) {
        $imported = 0;
        $logs = [];
        
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            return ['success' => false, 'message' => 'Tidak dapat membaca file'];
        }
        
        $row_num = 0;
        while (($row = fgetcsv($handle)) !== FALSE) {
            $row_num++;
            if ($row_num == 1) continue; // Skip header
            
            if (empty($row[0])) continue; // Skip empty nomor_kk
            
            $result = $this->processKeluargaImportRow($row, $row_num);
            if ($result['success']) {
                $imported++;
            } else {
                $logs[] = "Baris " . $row_num . ": " . $result['message'];
            }
        }
        fclose($handle);
        
        return [
            'success' => true,
            'data' => [
                'imported_count' => $imported,
                'logs' => $logs,
                'total_processed' => $row_num - 1
            ]
        ];
    }
    
    public function exportKeluarga() {
        try {
            $query = "SELECT k.nomor_kk, k.nama_kepala_keluarga, k.alamat_keluarga, k.id_keluarga FROM keluarga k WHERE k.id_desa = ? ORDER BY k.nama_kepala_keluarga";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$this->id_desa]);
            $keluarga_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get NIK kepala keluarga and anggota for each keluarga
            $data = [];
            foreach ($keluarga_data as $keluarga) {
                // Get NIK kepala keluarga
                $query_kepala = "SELECT nik FROM penduduk WHERE nama_lengkap = ? AND id_desa = ? LIMIT 1";
                $stmt_kepala = $this->db->prepare($query_kepala);
                $stmt_kepala->execute([$keluarga['nama_kepala_keluarga'], $this->id_desa]);
                $nik_kepala = $stmt_kepala->fetchColumn() ?: '';
                
                // Get NIK anggota (exclude kepala keluarga)
                $query_anggota = "SELECT nik FROM penduduk WHERE id_keluarga = ? AND nik != ? ORDER BY nik";
                $stmt_anggota = $this->db->prepare($query_anggota);
                $stmt_anggota->execute([$keluarga['id_keluarga'], $nik_kepala]);
                $anggota_niks = $stmt_anggota->fetchAll(PDO::FETCH_COLUMN);
                
                $data[] = [
                    'nomor_kk' => $keluarga['nomor_kk'],
                    'nik_kepala_keluarga' => $nik_kepala,
                    'nik_anggota' => implode(',', $anggota_niks),
                    'alamat_keluarga' => $keluarga['alamat_keluarga']
                ];
            }
            
            $filename = 'data_keluarga_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            // Create XLSX using native PHP
            $this->createKeluargaXLSX($data, $filename);
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    private function createKeluargaXLSX($data, $filename) {
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
        $zip->addFromString('xl/styles.xml', $this->getKeluargaStyles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->getKeluargaWorksheet($data));
        
        $zip->close();
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        readfile($temp_file);
        unlink($temp_file);
        exit;
    }
    
    private function getKeluargaStyles() {
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
    
    private function getKeluargaWorksheet($data) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<sheetData>';
        
        // Header row
        $xml .= '<row r="1">';
        $headers = ['Nomor KK', 'NIK Kepala Keluarga', 'NIK Anggota', 'Alamat'];
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
                if ($key === 'nomor_kk' || $key === 'nik_kepala_keluarga') {
                    // Use text format style for nomor_kk and nik_kepala_keluarga
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
    
    public function handleAjaxRequest() {
        // Start output buffering to catch any unwanted output
        ob_start();
        
        if (isset($_POST['action']) && ($_POST['action'] === 'export_penduduk' || $_POST['action'] === 'export_keluarga')) {
            $this->checkAuthentication();
            if ($_POST['action'] === 'export_penduduk') {
                $this->exportPenduduk();
            } else {
                $this->exportKeluarga();
            }
            return;
        }
        
        if (!$this->isAjaxRequest()) {
            ob_end_clean();
            return;
        }
        
        // Clear any unwanted output
        ob_clean();
        
        try {
            // Check authentication for AJAX requests
            if (!initSession()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Session tidak dapat diinisialisasi',
                    'redirect' => BASE_URL . 'pages/auth/login.php'
                ]);
                exit();
            }
            
            if (!validateSession()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Session tidak valid',
                    'redirect' => BASE_URL . 'pages/auth/login.php'
                ]);
                exit();
            }
            
            if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'operator_desa') {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Akses ditolak',
                    'redirect' => BASE_URL . 'pages/auth/login.php'
                ]);
                exit();
            }
            
            header('Content-Type: application/json');
            
            $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
            switch ($action) {
                case 'get_penduduk_options':
                    $search = $_GET['search'] ?? '';
                    $data = $this->getPendudukOptions($search);
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                    
                case 'get_penduduk':
                    $page = $_GET['page'] ?? 1;
                    $limit = $_GET['limit'] ?? 10;
                    $search = $_GET['search'] ?? '';
                    $jenis_kelamin = $_GET['jenis_kelamin'] ?? '';
                    $usia = $_GET['usia'] ?? '';
                    $data = $this->getPenduduk($page, $limit, $search, $jenis_kelamin, $usia);
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                
            case 'get_penduduk_detail':
                $nik = $_GET['nik'] ?? null;
                $data = $this->getPendudukDetail($nik);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'create_penduduk':
                $result = $this->createPenduduk($_POST);
                echo json_encode($result);
                break;
                
            case 'update_penduduk':
                $result = $this->updatePenduduk($_POST);
                echo json_encode($result);
                break;
                
            case 'delete_penduduk':
                $nik = $_POST['nik'] ?? $_GET['nik'] ?? null;
                $result = $this->deletePenduduk($nik);
                echo json_encode($result);
                break;
                
            case 'get_keluarga':
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 10;
                $search = $_GET['search'] ?? '';
                $data = $this->getKeluarga($page, $limit, $search);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'get_keluarga_detail':
                $id = $_GET['id'] ?? null;
                $data = $this->getKeluargaDetail($id);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'create_keluarga':
                $result = $this->createKeluarga($_POST);
                echo json_encode($result);
                break;
                
            case 'update_keluarga':
                $result = $this->updateKeluarga($_POST);
                echo json_encode($result);
                break;
                
            case 'delete_keluarga':
                $id = $_POST['id_keluarga'] ?? $_GET['id_keluarga'] ?? null;
                $result = $this->deleteKeluarga($id);
                echo json_encode($result);
                break;
                
            case 'get_family_members':
                $id_keluarga = $_GET['id_keluarga'] ?? null;
                $data = $this->getFamilyMembers($id_keluarga);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'search_penduduk_for_family':
                $search = $_GET['search'] ?? '';
                $data = $this->searchPendudukForFamily($search);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'add_family_member':
                $input = json_decode(file_get_contents('php://input'), true);
                $nik = $input['nik'] ?? $_POST['nik'] ?? null;
                $id_keluarga = $input['id_keluarga'] ?? $_POST['id_keluarga'] ?? null;
                if ($nik && $id_keluarga) {
                    $result = $this->addFamilyMember($nik, $id_keluarga);
                } else {
                    $result = ['success' => false, 'message' => 'Data tidak lengkap'];
                }
                echo json_encode($result);
                break;
                
            case 'remove_family_member':
                $input = json_decode(file_get_contents('php://input'), true);
                $nik = $input['nik'] ?? $_POST['nik'] ?? null;
                $id_keluarga = $input['id_keluarga'] ?? $_POST['id_keluarga'] ?? null;
                if ($nik && $id_keluarga) {
                    $result = $this->removeFamilyMember($nik, $id_keluarga);
                } else {
                    $result = ['success' => false, 'message' => 'Data tidak lengkap'];
                }
                echo json_encode($result);
                break;
                
            case 'import_penduduk':
                if (isset($_FILES['excel_file'])) {
                    $result = $this->importPenduduk($_FILES['excel_file']);
                    echo json_encode($result);
                } else {
                    echo json_encode(['success' => false, 'message' => 'File tidak ditemukan']);
                }
                break;
                
            case 'export_keluarga':
                $this->exportKeluarga();
                break;
                
            case 'import_keluarga':
                if (isset($_FILES['excel_file'])) {
                    $result = $this->importKeluarga($_FILES['excel_file']);
                    echo json_encode($result);
                } else {
                    echo json_encode(['success' => false, 'message' => 'File tidak ditemukan']);
                }
                break;
                
                default:
                    http_response_code(400);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Action tidak valid: ' . $action
                    ]);
                    break;
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
            ]);
        }
        
        exit();
    }
}

// Entry point for direct access
if (isset($_GET['action']) || isset($_POST['action'])) {
    try {
        $kependudukan = new KependudukanFunctions();
        $kependudukan->handleAjaxRequest();
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}
?>