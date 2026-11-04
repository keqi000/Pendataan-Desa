<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/session.php';
require_once __DIR__ . '/../../../../database/queries.php';

class PendidikanFunctions {
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
            'page_title' => 'Data Pendidikan',
            'current_page' => 'pendidikan',
            'page_css' => ['pendidikan.css'],
            'page_js' => [
                'pendidikan.js',
                'js/pendidikan-core.js',
                'js/pendidikan-fasilitas.js'
            ]
        ];
    }
    
    public function getFasilitasPendidikan($search = '', $jenis = '', $kondisi = '') {
        try {
            $query = "SELECT * FROM fasilitas_pendidikan WHERE id_desa = ?";
            $params = [$this->id_desa];
            
            if ($search) {
                $query .= " AND nama_fasilitas LIKE ?";
                $params[] = "%$search%";
            }
            
            if ($jenis) {
                $query .= " AND jenis_pendidikan = ?";
                $params[] = $jenis;
            }
            
            if ($kondisi) {
                $query .= " AND kondisi_bangunan = ?";
                $params[] = $kondisi;
            }
            
            $query .= " ORDER BY nama_fasilitas LIMIT 20";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getPendidikanStats() {
        try {
            $stats = [];
            $query = "SELECT jenis_pendidikan, COUNT(*) as total FROM fasilitas_pendidikan WHERE id_desa = ? GROUP BY jenis_pendidikan";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$this->id_desa]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($result as $row) {
                $stats[$row['jenis_pendidikan']] = $row['total'];
            }
            
            return $stats;
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getFasilitasDetail($id) {
        try {
            $query = "SELECT * FROM fasilitas_pendidikan WHERE id_fasilitas = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function createFasilitas($data) {
        try {
            if (isset($_SESSION['fasilitas_submit_flag']) && (time() - $_SESSION['fasilitas_submit_flag']) < 3) {
                return ['success' => false, 'message' => 'Permintaan sedang diproses, harap tunggu'];
            }
            $_SESSION['fasilitas_submit_flag'] = time();
            
            $data['id_desa'] = $this->id_desa;
            
            $checkQuery = "SELECT COUNT(*) FROM fasilitas_pendidikan WHERE nama_fasilitas = ? AND id_desa = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 5 SECOND)";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$data['nama_fasilitas'], $data['id_desa']]);
            
            if ($checkStmt->fetchColumn() > 0) {
                unset($_SESSION['fasilitas_submit_flag']);
                return ['success' => false, 'message' => 'Data fasilitas dengan nama yang sama baru saja ditambahkan'];
            }
            
            $query = "INSERT INTO fasilitas_pendidikan (id_desa, nama_fasilitas, jenis_pendidikan, alamat_fasilitas, kapasitas_siswa, kondisi_bangunan, jumlah_guru, koordinat_latitude, koordinat_longitude, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['id_desa'], $data['nama_fasilitas'], $data['jenis_pendidikan'], 
                $data['alamat_fasilitas'], $data['kapasitas_siswa'], $data['kondisi_bangunan'], 
                $data['jumlah_guru'], $data['koordinat_latitude'], $data['koordinat_longitude']
            ]);
            
            unset($_SESSION['fasilitas_submit_flag']);
            
            return ['success' => $result, 'message' => $result ? 'Fasilitas pendidikan berhasil ditambahkan' : 'Gagal menambahkan fasilitas pendidikan'];
        } catch (Exception $e) {
            unset($_SESSION['fasilitas_submit_flag']);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function updateFasilitas($data) {
        try {
            $query = "UPDATE fasilitas_pendidikan SET nama_fasilitas = ?, jenis_pendidikan = ?, alamat_fasilitas = ?, kapasitas_siswa = ?, kondisi_bangunan = ?, jumlah_guru = ?, koordinat_latitude = ?, koordinat_longitude = ? WHERE id_fasilitas = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['nama_fasilitas'], $data['jenis_pendidikan'], $data['alamat_fasilitas'], 
                $data['kapasitas_siswa'], $data['kondisi_bangunan'], $data['jumlah_guru'], 
                $data['koordinat_latitude'], $data['koordinat_longitude'], $data['id_fasilitas']
            ]);
            
            return ['success' => $result, 'message' => 'Fasilitas pendidikan berhasil diupdate'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function deleteFasilitas($id) {
        try {
            $getStmt = $this->db->prepare("SELECT nama_fasilitas FROM fasilitas_pendidikan WHERE id_fasilitas = ?");
            $getStmt->execute([$id]);
            $facility = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$facility) {
                return ['success' => false, 'message' => 'Fasilitas tidak ditemukan'];
            }
            
            $query = "DELETE FROM fasilitas_pendidikan WHERE id_fasilitas = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id]);
            
            return ['success' => $result, 'message' => 'Fasilitas pendidikan berhasil dihapus'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function handleAjaxRequest() {
        if (!$this->isAjaxRequest()) return;
        
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        switch ($action) {
            case 'get_fasilitas':
                $search = $_GET['search'] ?? '';
                $jenis = $_GET['jenis'] ?? '';
                $kondisi = $_GET['kondisi'] ?? '';
                $data = $this->getFasilitasPendidikan($search, $jenis, $kondisi);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'get_stats':
                $data = $this->getPendidikanStats();
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'get_detail':
                $id = $_GET['id'] ?? null;
                $data = $this->getFasilitasDetail($id);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'create_fasilitas':
                $result = $this->createFasilitas($_POST);
                echo json_encode($result);
                break;
                
            case 'update_fasilitas':
                $result = $this->updateFasilitas($_POST);
                echo json_encode($result);
                break;
                
            case 'delete_fasilitas':
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id_fasilitas'] ?? $_POST['id_fasilitas'] ?? $_GET['id_fasilitas'] ?? null;
                if ($id) {
                    $result = $this->deleteFasilitas($id);
                    echo json_encode($result);
                } else {
                    echo json_encode(['success' => false, 'message' => 'ID fasilitas tidak ditemukan']);
                }
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
?>