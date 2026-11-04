<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/session.php';
require_once __DIR__ . '/../../../../database/queries.php';

class InfrastrukturFunctions {
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
            'page_title' => 'Data Infrastruktur',
            'current_page' => 'infrastruktur',
            'page_css' => ['infrastruktur.css'],
            'page_js' => ['infrastruktur.js']
        ];
    }
    
    // JALAN METHODS
    public function getInfrastrukturJalan($search = '', $kondisi = '', $permukaan = '') {
        try {
            $query = "SELECT * FROM infrastruktur_jalan WHERE id_desa = ?";
            $params = [$this->id_desa];
            
            if ($search) {
                $query .= " AND nama_jalan LIKE ?";
                $params[] = "%$search%";
            }
            
            if ($kondisi) {
                $query .= " AND kondisi_jalan = ?";
                $params[] = $kondisi;
            }
            
            if ($permukaan) {
                $query .= " AND jenis_permukaan = ?";
                $params[] = $permukaan;
            }
            
            $query .= " ORDER BY nama_jalan LIMIT 20";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getJalanDetail($id) {
        try {
            $query = "SELECT * FROM infrastruktur_jalan WHERE id_jalan = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function createJalan($data) {
        try {
            if (isset($_SESSION['jalan_submit_flag']) && (time() - $_SESSION['jalan_submit_flag']) < 3) {
                return ['success' => false, 'message' => 'Permintaan sedang diproses, harap tunggu'];
            }
            $_SESSION['jalan_submit_flag'] = time();
            
            $data['id_desa'] = $this->id_desa;
            
            $checkQuery = "SELECT COUNT(*) FROM infrastruktur_jalan WHERE nama_jalan = ? AND id_desa = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 5 SECOND)";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$data['nama_jalan'], $data['id_desa']]);
            
            if ($checkStmt->fetchColumn() > 0) {
                unset($_SESSION['jalan_submit_flag']);
                return ['success' => false, 'message' => 'Data jalan dengan nama yang sama baru saja ditambahkan'];
            }
            
            $query = "INSERT INTO infrastruktur_jalan (id_desa, nama_jalan, panjang_jalan, lebar_jalan, kondisi_jalan, jenis_permukaan, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['id_desa'], $data['nama_jalan'], $data['panjang_jalan'], 
                $data['lebar_jalan'], $data['kondisi_jalan'], $data['jenis_permukaan']
            ]);
            
            unset($_SESSION['jalan_submit_flag']);
            
            return ['success' => $result, 'message' => $result ? 'Data jalan berhasil ditambahkan' : 'Gagal menambahkan data jalan'];
        } catch (Exception $e) {
            unset($_SESSION['jalan_submit_flag']);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function updateJalan($data) {
        try {
            $query = "UPDATE infrastruktur_jalan SET nama_jalan = ?, panjang_jalan = ?, lebar_jalan = ?, kondisi_jalan = ?, jenis_permukaan = ? WHERE id_jalan = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['nama_jalan'], $data['panjang_jalan'], $data['lebar_jalan'], 
                $data['kondisi_jalan'], $data['jenis_permukaan'], $data['id_jalan']
            ]);
            
            return ['success' => $result, 'message' => 'Data jalan berhasil diupdate'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function deleteJalan($id) {
        try {
            $query = "DELETE FROM infrastruktur_jalan WHERE id_jalan = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id]);
            
            return ['success' => $result, 'message' => 'Data jalan berhasil dihapus'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // JEMBATAN METHODS
    public function getInfrastrukturJembatan($search = '', $kondisi = '') {
        try {
            $query = "SELECT * FROM infrastruktur_jembatan WHERE id_desa = ?";
            $params = [$this->id_desa];
            
            if ($search) {
                $query .= " AND nama_jembatan LIKE ?";
                $params[] = "%$search%";
            }
            
            if ($kondisi) {
                $query .= " AND kondisi_jembatan = ?";
                $params[] = $kondisi;
            }
            
            $query .= " ORDER BY nama_jembatan LIMIT 20";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getJembatanDetail($id) {
        try {
            $query = "SELECT * FROM infrastruktur_jembatan WHERE id_jembatan = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function createJembatan($data) {
        try {
            if (isset($_SESSION['jembatan_submit_flag']) && (time() - $_SESSION['jembatan_submit_flag']) < 3) {
                return ['success' => false, 'message' => 'Permintaan sedang diproses, harap tunggu'];
            }
            $_SESSION['jembatan_submit_flag'] = time();
            
            $data['id_desa'] = $this->id_desa;
            
            $checkQuery = "SELECT COUNT(*) FROM infrastruktur_jembatan WHERE nama_jembatan = ? AND id_desa = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 5 SECOND)";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$data['nama_jembatan'], $data['id_desa']]);
            
            if ($checkStmt->fetchColumn() > 0) {
                unset($_SESSION['jembatan_submit_flag']);
                return ['success' => false, 'message' => 'Data jembatan dengan nama yang sama baru saja ditambahkan'];
            }
            
            $query = "INSERT INTO infrastruktur_jembatan (id_desa, nama_jembatan, panjang_jembatan, lebar_jembatan, kondisi_jembatan, material_jembatan, koordinat_latitude, koordinat_longitude, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['id_desa'], $data['nama_jembatan'], $data['panjang_jembatan'], 
                $data['lebar_jembatan'], $data['kondisi_jembatan'], $data['material_jembatan'],
                $data['koordinat_latitude'], $data['koordinat_longitude']
            ]);
            
            unset($_SESSION['jembatan_submit_flag']);
            
            return ['success' => $result, 'message' => $result ? 'Data jembatan berhasil ditambahkan' : 'Gagal menambahkan data jembatan'];
        } catch (Exception $e) {
            unset($_SESSION['jembatan_submit_flag']);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function updateJembatan($data) {
        try {
            $query = "UPDATE infrastruktur_jembatan SET nama_jembatan = ?, panjang_jembatan = ?, lebar_jembatan = ?, kondisi_jembatan = ?, material_jembatan = ?, koordinat_latitude = ?, koordinat_longitude = ? WHERE id_jembatan = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $data['nama_jembatan'], $data['panjang_jembatan'], $data['lebar_jembatan'], 
                $data['kondisi_jembatan'], $data['material_jembatan'],
                $data['koordinat_latitude'], $data['koordinat_longitude'], $data['id_jembatan']
            ]);
            
            return ['success' => $result, 'message' => 'Data jembatan berhasil diupdate'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function deleteJembatan($id) {
        try {
            $query = "DELETE FROM infrastruktur_jembatan WHERE id_jembatan = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id]);
            
            return ['success' => $result, 'message' => 'Data jembatan berhasil dihapus'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function handleAjaxRequest() {
        if (!$this->isAjaxRequest()) return;
        
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        switch ($action) {
            case 'get_jalan':
                $search = $_GET['search'] ?? '';
                $kondisi = $_GET['kondisi'] ?? '';
                $permukaan = $_GET['permukaan'] ?? '';
                $data = $this->getInfrastrukturJalan($search, $kondisi, $permukaan);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'get_jalan_detail':
                $id = $_GET['id'] ?? null;
                $data = $this->getJalanDetail($id);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'create_jalan':
                $result = $this->createJalan($_POST);
                echo json_encode($result);
                break;
                
            case 'update_jalan':
                $result = $this->updateJalan($_POST);
                echo json_encode($result);
                break;
                
            case 'delete_jalan':
                $id = $_POST['id_jalan'] ?? $_GET['id_jalan'] ?? null;
                $result = $this->deleteJalan($id);
                echo json_encode($result);
                break;
                
            case 'get_jembatan':
                $search = $_GET['search'] ?? '';
                $kondisi = $_GET['kondisi'] ?? '';
                $data = $this->getInfrastrukturJembatan($search, $kondisi);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'get_jembatan_detail':
                $id = $_GET['id'] ?? null;
                $data = $this->getJembatanDetail($id);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'create_jembatan':
                $result = $this->createJembatan($_POST);
                echo json_encode($result);
                break;
                
            case 'update_jembatan':
                $result = $this->updateJembatan($_POST);
                echo json_encode($result);
                break;
                
            case 'delete_jembatan':
                $id = $_POST['id_jembatan'] ?? $_GET['id_jembatan'] ?? null;
                $result = $this->deleteJembatan($id);
                echo json_encode($result);
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