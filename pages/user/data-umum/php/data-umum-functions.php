<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/session.php';
require_once __DIR__ . '/../../../../database/queries.php';

class DataUmumFunctions {
    private $queries;
    private $db;
    
    public function __construct() {
        $this->queries = new Queries();
        $database = new Database();
        $this->db = $database->getConnection();
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
    
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update-data-desa':
                    $this->updateDataDesa();
                    break;
                default:
                    $this->jsonResponse(false, 'Action not found');
            }
        }
    }
    
    public function getDesaData($id_desa) {
        return $this->queries->getDesaById($id_desa);
    }
    
    private function updateDataDesa() {
        try {
            $data = $_POST;
            $id_desa = $_SESSION['user_desa'];
            
            $query = "UPDATE desa SET luas_wilayah = ?, jumlah_dusun = ?, jumlah_rt = ?, jumlah_rw = ? WHERE id_desa = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['luas_wilayah'], 
                $data['jumlah_dusun'], 
                $data['jumlah_rt'], 
                $data['jumlah_rw'], 
                $id_desa
            ]);
            
            $this->jsonResponse(true, 'Data desa berhasil diupdate');
        } catch (Exception $e) {
            $this->jsonResponse(false, $e->getMessage());
        }
    }
    
    private function jsonResponse($success, $message, $data = null) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
        exit();
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    initSession();
    $functions = new DataUmumFunctions();
    $functions->handleRequest();
}
?>