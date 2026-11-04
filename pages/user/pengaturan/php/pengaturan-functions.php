<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/session.php';
require_once __DIR__ . '/../../../../database/queries.php';

class PengaturanFunctions {
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
    
    public function handleChangePassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['old_password']) || !isset($input['new_password'])) {
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit();
            }
            
            $old_password = $input['old_password'];
            $new_password = $input['new_password'];
            
            if (strlen($new_password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Password baru minimal 6 karakter']);
                exit();
            }
            
            try {
                $stmt = $this->queries->db->prepare("SELECT password FROM user WHERE id_user = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user) {
                    echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
                    exit();
                }
                
                if (!password_verify($old_password, $user['password'])) {
                    echo json_encode(['success' => false, 'message' => 'Password lama tidak benar']);
                    exit();
                }
                
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $this->queries->db->prepare("UPDATE user SET password = ? WHERE id_user = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                
                echo json_encode(['success' => true, 'message' => 'Password berhasil diubah']);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
            }
            exit();
        }
    }
    
    public function getCurrentUser() {
        $stmt = $this->queries->db->prepare("SELECT * FROM user WHERE id_user = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
?>