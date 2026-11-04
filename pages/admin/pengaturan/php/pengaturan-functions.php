<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/session.php';
require_once __DIR__ . '/../../../../database/queries.php';
require_once __DIR__ . '/../../../user/kependudukan/php/PekerjaanManager.php';

class PengaturanFunctions {
    private $queries;
    private $pekerjaanManager;
    
    public function __construct() {
        try {
            $this->queries = new Queries();
            
            if (!$this->queries->db) {
                throw new Exception("Database connection failed");
            }
            
            $this->pekerjaanManager = new PekerjaanManager();
            
        } catch (Exception $e) {
            throw $e;
        }
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
    
    public function getCurrentUser($user_id) {
        $stmt = $this->queries->db->prepare("SELECT * FROM user WHERE id_user = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAllUsers() {
        $stmt = $this->queries->db->prepare("
            SELECT u.*, d.nama_desa 
            FROM user u 
            LEFT JOIN desa d ON u.id_desa = d.id_desa 
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAllDesa() {
        $stmt = $this->queries->db->prepare("SELECT * FROM desa ORDER BY nama_desa");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getUserById($user_id) {
        $stmt = $this->queries->db->prepare("SELECT * FROM user WHERE id_user = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getDesaById($desa_id) {
        $stmt = $this->queries->db->prepare("SELECT * FROM desa WHERE id_desa = ?");
        $stmt->execute([$desa_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function changePassword($user_id, $old_password, $new_password) {
        if (strlen($new_password) < 6) {
            return ['success' => false, 'message' => 'Password baru minimal 6 karakter'];
        }
        
        $stmt = $this->queries->db->prepare("SELECT password FROM user WHERE id_user = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($old_password, $user['password'])) {
            return ['success' => false, 'message' => 'Password lama tidak benar'];
        }
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->queries->db->prepare("UPDATE user SET password = ? WHERE id_user = ?");
        $stmt->execute([$hashed_password, $user_id]);
        
        return ['success' => true, 'message' => 'Password berhasil diubah'];
    }
    
    public function createUser($data) {
        if (empty($data['nama_lengkap']) || empty($data['username']) || empty($data['email']) || empty($data['password']) || empty($data['role'])) {
            return ['success' => false, 'message' => 'Semua field wajib diisi'];
        }
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Format email tidak valid'];
        }
        
        if (strlen($data['password']) < 6) {
            return ['success' => false, 'message' => 'Password minimal 6 karakter'];
        }
        
        // Check username exists
        $stmt = $this->queries->db->prepare("SELECT id_user FROM user WHERE username = ?");
        $stmt->execute([$data['username']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username sudah digunakan'];
        }
        
        // Check email exists
        $stmt = $this->queries->db->prepare("SELECT id_user FROM user WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email sudah digunakan'];
        }
        
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $this->queries->db->prepare("
            INSERT INTO user (nama_lengkap, username, email, password, role, id_desa, status_aktif, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'aktif', NOW())
        ");
        
        $stmt->execute([
            $data['nama_lengkap'],
            $data['username'],
            $data['email'],
            $hashed_password,
            $data['role'],
            $data['id_desa']
        ]);
        
        return ['success' => true, 'message' => 'User berhasil dibuat'];
    }
    
    public function updateUser($data) {
        // Check username exists (excluding current user)
        $stmt = $this->queries->db->prepare("SELECT id_user FROM user WHERE username = ? AND id_user != ?");
        $stmt->execute([$data['username'], $data['user_id']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username sudah digunakan'];
        }
        
        // Check email exists (excluding current user)
        $stmt = $this->queries->db->prepare("SELECT id_user FROM user WHERE email = ? AND id_user != ?");
        $stmt->execute([$data['email'], $data['user_id']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email sudah digunakan'];
        }
        
        if ($data['password']) {
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $this->queries->db->prepare("
                UPDATE user SET nama_lengkap = ?, username = ?, email = ?, password = ?, role = ?, id_desa = ?, status_aktif = ?, updated_at = NOW()
                WHERE id_user = ?
            ");
            $stmt->execute([$data['nama_lengkap'], $data['username'], $data['email'], $hashed_password, $data['role'], $data['id_desa'], $data['status_aktif'], $data['user_id']]);
        } else {
            $stmt = $this->queries->db->prepare("
                UPDATE user SET nama_lengkap = ?, username = ?, email = ?, role = ?, id_desa = ?, status_aktif = ?, updated_at = NOW()
                WHERE id_user = ?
            ");
            $stmt->execute([$data['nama_lengkap'], $data['username'], $data['email'], $data['role'], $data['id_desa'], $data['status_aktif'], $data['user_id']]);
        }
        
        return ['success' => true, 'message' => 'User berhasil diupdate'];
    }
    
    public function deleteUser($user_id, $current_user_id) {
        if ($user_id == $current_user_id) {
            return ['success' => false, 'message' => 'Tidak dapat menghapus akun sendiri'];
        }
        
        $stmt = $this->queries->db->prepare("DELETE FROM user WHERE id_user = ?");
        $stmt->execute([$user_id]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'User tidak ditemukan'];
        }
        
        return ['success' => true, 'message' => 'User berhasil dihapus'];
    }
    
    public function createDesa($data) {
        if (empty($data['nama_desa'])) {
            return ['success' => false, 'message' => 'Nama desa wajib diisi'];
        }
        
        // Check if village name already exists
        $stmt = $this->queries->db->prepare("SELECT id_desa FROM desa WHERE nama_desa = ?");
        $stmt->execute([$data['nama_desa']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Nama desa sudah ada'];
        }
        
        $stmt = $this->queries->db->prepare("
            INSERT INTO desa (nama_desa, nama_kecamatan, nama_kabupaten, nama_provinsi, luas_wilayah, jumlah_dusun, jumlah_rt, jumlah_rw, created_at) 
            VALUES (?, 'Modoinding', 'Minahasa Selatan', 'Sulawesi Utara', ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $data['nama_desa'],
            $data['luas_wilayah'],
            $data['jumlah_dusun'],
            $data['jumlah_rt'],
            $data['jumlah_rw']
        ]);
        
        return ['success' => true, 'message' => 'Desa berhasil ditambahkan'];
    }
    
    public function updateDesa($data) {
        if (empty($data['nama_desa']) || !$data['desa_id']) {
            return ['success' => false, 'message' => 'Data tidak lengkap'];
        }
        
        // Check if village name already exists (exclude current desa)
        $stmt = $this->queries->db->prepare("SELECT id_desa FROM desa WHERE nama_desa = ? AND id_desa != ?");
        $stmt->execute([$data['nama_desa'], $data['desa_id']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Nama desa sudah ada'];
        }
        
        $stmt = $this->queries->db->prepare("
            UPDATE desa SET nama_desa = ?, luas_wilayah = ?, jumlah_dusun = ?, jumlah_rt = ?, jumlah_rw = ?, updated_at = NOW()
            WHERE id_desa = ?
        ");
        
        $stmt->execute([
            $data['nama_desa'],
            $data['luas_wilayah'],
            $data['jumlah_dusun'],
            $data['jumlah_rt'],
            $data['jumlah_rw'],
            $data['desa_id']
        ]);
        
        return ['success' => true, 'message' => 'Desa berhasil diupdate'];
    }
    
    public function deleteDesa($desa_id) {
        $this->queries->db->beginTransaction();
        
        try {
            // Update user table to set id_desa to NULL
            $stmt = $this->queries->db->prepare("UPDATE user SET id_desa = NULL WHERE id_desa = ?");
            $stmt->execute([$desa_id]);
            
            // Delete related data in correct order
            $tables = ['warga_miskin', 'infrastruktur_jembatan', 'infrastruktur_jalan', 'pertanian', 'umkm', 'pasar', 'mata_pencaharian', 'fasilitas_pendidikan', 'penduduk', 'keluarga'];
            
            foreach ($tables as $table) {
                $stmt = $this->queries->db->prepare("DELETE FROM $table WHERE id_desa = ?");
                $stmt->execute([$desa_id]);
            }
            
            // Finally delete the village
            $stmt = $this->queries->db->prepare("DELETE FROM desa WHERE id_desa = ?");
            $stmt->execute([$desa_id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Desa tidak ditemukan');
            }
            
            $this->queries->db->commit();
            return ['success' => true, 'message' => 'Desa berhasil dihapus'];
            
        } catch (Exception $e) {
            $this->queries->db->rollback();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // API Functions - moved from pengaturan.api.php
    public function getUsersList($page = 1, $limit = 10, $search = '', $role = '') {
        $offset = ($page - 1) * $limit;
        
        $where = "WHERE 1=1";
        $params = [];
        
        if ($search) {
            $where .= " AND (u.username LIKE ? OR u.nama_lengkap LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($role) {
            $where .= " AND u.user_role = ?";
            $params[] = $role;
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM user u $where";
        $countStmt = $this->queries->db->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get users with desa info
        $query = "SELECT u.*, d.nama_desa 
                 FROM user u 
                 LEFT JOIN desa d ON u.id_desa = d.id_desa 
                 $where 
                 ORDER BY u.created_at DESC 
                 LIMIT $limit OFFSET $offset";
        $stmt = $this->queries->db->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Remove password from results
        foreach ($result as &$user) {
            unset($user['password']);
        }
        
        $pagination = [
            'current_page' => (int)$page,
            'total_pages' => ceil($total / $limit),
            'total_items' => (int)$total,
            'items_per_page' => (int)$limit
        ];
        
        return ['data' => $result, 'pagination' => $pagination];
    }
    
    public function getUserDetail($user_id) {
        $query = "SELECT u.*, d.nama_desa 
                 FROM user u 
                 LEFT JOIN desa d ON u.id_desa = d.id_desa 
                 WHERE u.id_user = ?";
        $stmt = $this->queries->db->prepare($query);
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            unset($result['password']);
        }
        
        return $result;
    }
    
    public function getDesaList() {
        $query = "SELECT d.*, 
                 (SELECT COUNT(*) FROM user u WHERE u.id_desa = d.id_desa) as total_users,
                 (SELECT COUNT(*) FROM penduduk p WHERE p.id_desa = d.id_desa) as total_penduduk
                 FROM desa d ORDER BY d.nama_desa";
        $stmt = $this->queries->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getDesaDetail($desa_id) {
        $query = "SELECT * FROM desa WHERE id_desa = ?";
        $stmt = $this->queries->db->prepare($query);
        $stmt->execute([$desa_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function createUserAPI($data) {
        // Validate required fields
        if (empty($data['username']) || empty($data['password']) || empty($data['nama_lengkap'])) {
            return ['success' => false, 'message' => 'Username, password, dan nama lengkap wajib diisi'];
        }
        
        // Check username uniqueness
        $checkStmt = $this->queries->db->prepare("SELECT COUNT(*) FROM user WHERE username = ?");
        $checkStmt->execute([$data['username']]);
        if ($checkStmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Username sudah digunakan'];
        }
        
        // Hash password
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $query = "INSERT INTO user (username, password, nama_lengkap, user_role, id_desa, status_aktif, created_at) VALUES (?, ?, ?, ?, ?, 'aktif', NOW())";
        $stmt = $this->queries->db->prepare($query);
        $result = $stmt->execute([
            $data['username'],
            $hashed_password,
            $data['nama_lengkap'],
            $data['user_role'] ?? 'operator_desa',
            $data['id_desa'] ?? null
        ]);
        
        return $result ? 
            ['success' => true, 'message' => 'User berhasil dibuat'] : 
            ['success' => false, 'message' => 'Gagal membuat user'];
    }
    
    public function updateUserAPI($data) {
        $user_id = $data['id_user'] ?? null;
        
        if (!$user_id) {
            return ['success' => false, 'message' => 'User ID required'];
        }
        
        // Check if username is unique (excluding current user)
        if (!empty($data['username'])) {
            $checkStmt = $this->queries->db->prepare("SELECT COUNT(*) FROM user WHERE username = ? AND id_user != ?");
            $checkStmt->execute([$data['username'], $user_id]);
            if ($checkStmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Username sudah digunakan'];
            }
        }
        
        $query = "UPDATE user SET username = ?, nama_lengkap = ?, user_role = ?, id_desa = ?, status_aktif = ? WHERE id_user = ?";
        $stmt = $this->queries->db->prepare($query);
        $result = $stmt->execute([
            $data['username'],
            $data['nama_lengkap'],
            $data['user_role'],
            $data['id_desa'] ?? null,
            $data['status_aktif'] ?? 'aktif',
            $user_id
        ]);
        
        return $result ? 
            ['success' => true, 'message' => 'User berhasil diupdate'] : 
            ['success' => false, 'message' => 'Gagal mengupdate user'];
    }
    
    public function deleteUserAPI($user_id, $current_user_id) {
        if (!$user_id) {
            return ['success' => false, 'message' => 'User ID required'];
        }
        
        // Prevent deleting current user
        if ($user_id == $current_user_id) {
            return ['success' => false, 'message' => 'Tidak dapat menghapus akun sendiri'];
        }
        
        $query = "DELETE FROM user WHERE id_user = ?";
        $stmt = $this->queries->db->prepare($query);
        $result = $stmt->execute([$user_id]);
        
        return $result ? 
            ['success' => true, 'message' => 'User berhasil dihapus'] : 
            ['success' => false, 'message' => 'Gagal menghapus user'];
    }
    
    public function changePasswordAPI($user_id, $new_password) {
        if (empty($new_password)) {
            return ['success' => false, 'message' => 'Password baru wajib diisi'];
        }
        
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $query = "UPDATE user SET password = ? WHERE id_user = ?";
        $stmt = $this->queries->db->prepare($query);
        $result = $stmt->execute([$hashed_password, $user_id]);
        
        return $result ? 
            ['success' => true, 'message' => 'Password berhasil diubah'] : 
            ['success' => false, 'message' => 'Gagal mengubah password'];
    }
    
    public function createDesaAPI($data) {
        if (empty($data['nama_desa'])) {
            return ['success' => false, 'message' => 'Nama desa wajib diisi'];
        }
        
        $query = "INSERT INTO desa (nama_desa, kode_desa, luas_wilayah, jumlah_dusun, jumlah_rt, jumlah_rw, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->queries->db->prepare($query);
        $result = $stmt->execute([
            $data['nama_desa'],
            $data['kode_desa'] ?? null,
            $data['luas_wilayah'] ?? 0,
            $data['jumlah_dusun'] ?? 0,
            $data['jumlah_rt'] ?? 0,
            $data['jumlah_rw'] ?? 0
        ]);
        
        return $result ? 
            ['success' => true, 'message' => 'Desa berhasil dibuat'] : 
            ['success' => false, 'message' => 'Gagal membuat desa'];
    }
    
    public function updateDesaAPI($data) {
        $desa_id = $data['id_desa'] ?? null;
        
        if (!$desa_id) {
            return ['success' => false, 'message' => 'Desa ID required'];
        }
        
        $query = "UPDATE desa SET nama_desa = ?, kode_desa = ?, luas_wilayah = ?, jumlah_dusun = ?, jumlah_rt = ?, jumlah_rw = ? WHERE id_desa = ?";
        $stmt = $this->queries->db->prepare($query);
        $result = $stmt->execute([
            $data['nama_desa'],
            $data['kode_desa'],
            $data['luas_wilayah'],
            $data['jumlah_dusun'],
            $data['jumlah_rt'],
            $data['jumlah_rw'],
            $desa_id
        ]);
        
        return $result ? 
            ['success' => true, 'message' => 'Desa berhasil diupdate'] : 
            ['success' => false, 'message' => 'Gagal mengupdate desa'];
    }
    
    public function deleteDesaAPI($desa_id) {
        if (!$desa_id) {
            return ['success' => false, 'message' => 'Desa ID required'];
        }
        
        // Check if desa has users or data
        $checkStmt = $this->queries->db->prepare("SELECT COUNT(*) FROM user WHERE id_desa = ?");
        $checkStmt->execute([$desa_id]);
        if ($checkStmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Tidak dapat menghapus desa yang masih memiliki user'];
        }
        
        $query = "DELETE FROM desa WHERE id_desa = ?";
        $stmt = $this->queries->db->prepare($query);
        $result = $stmt->execute([$desa_id]);
        
        return $result ? 
            ['success' => true, 'message' => 'Desa berhasil dihapus'] : 
            ['success' => false, 'message' => 'Gagal menghapus desa'];
    }
    
    public function handleAjaxRequest() {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            return false;
        }
        
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin_kecamatan') {
            return $this->jsonResponse(false, 'Unauthorized access', null, 401);
        }
        
        $endpoint = $_GET['endpoint'] ?? '';
        
        switch ($endpoint) {
            case 'users-list':
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 10;
                $search = $_GET['search'] ?? '';
                $role = $_GET['role'] ?? '';
                $result = $this->getUsersList($page, $limit, $search, $role);
                return $this->jsonResponse(true, 'Success', $result);
                
            case 'user-detail':
                $user_id = $_GET['id'] ?? null;
                $result = $this->getUserDetail($user_id);
                return $this->jsonResponse(true, 'Success', $result);
                
            case 'create-user':
                $data = $_POST;
                $result = $this->createUserAPI($data);
                return $this->jsonResponse($result['success'], $result['message']);
                
            case 'update-user':
                $data = $_POST;
                $result = $this->updateUserAPI($data);
                return $this->jsonResponse($result['success'], $result['message']);
                
            case 'delete-user':
                $input = json_decode(file_get_contents('php://input'), true);
                $user_id = $input['id_user'] ?? null;
                $result = $this->deleteUserAPI($user_id, $_SESSION['user_id']);
                return $this->jsonResponse($result['success'], $result['message']);
                
            case 'change-password':
                $data = $_POST;
                $user_id = $data['id_user'] ?? $_SESSION['user_id'];
                $result = $this->changePasswordAPI($user_id, $data['new_password'] ?? '');
                return $this->jsonResponse($result['success'], $result['message']);
                
            case 'desa-list':
                $result = $this->getDesaList();
                return $this->jsonResponse(true, 'Success', $result);
                
            case 'create-desa':
                $data = $_POST;
                $result = $this->createDesaAPI($data);
                return $this->jsonResponse($result['success'], $result['message']);
                
            case 'update-desa':
                $data = $_POST;
                $result = $this->updateDesaAPI($data);
                return $this->jsonResponse($result['success'], $result['message']);
                
            case 'delete-desa':
                $input = json_decode(file_get_contents('php://input'), true);
                $desa_id = $input['id_desa'] ?? null;
                $result = $this->deleteDesaAPI($desa_id);
                return $this->jsonResponse($result['success'], $result['message']);
                
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
    
    // Handle AJAX requests
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Method not allowed'];
        }
        
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        try {
            switch ($action) {
                case 'change_password':
                    return $this->changePassword($_SESSION['user_id'], $input['old_password'] ?? '', $input['new_password'] ?? '');
                    
                case 'create_user':
                    if ($_SESSION['user_role'] !== 'admin_kecamatan') {
                        return ['success' => false, 'message' => 'Unauthorized'];
                    }
                    return $this->createUser([
                        'nama_lengkap' => trim($input['nama_lengkap'] ?? ''),
                        'username' => trim($input['username'] ?? ''),
                        'email' => trim($input['email'] ?? ''),
                        'password' => $input['password'] ?? '',
                        'role' => $input['role'] ?? '',
                        'id_desa' => $input['id_desa'] ?? null
                    ]);
                    
                case 'update_user':
                    if ($_SESSION['user_role'] !== 'admin_kecamatan') {
                        return ['success' => false, 'message' => 'Unauthorized'];
                    }
                    return $this->updateUser($input);
                    
                case 'delete_user':
                    if ($_SESSION['user_role'] !== 'admin_kecamatan') {
                        return ['success' => false, 'message' => 'Unauthorized'];
                    }
                    return $this->deleteUser($input['user_id'] ?? 0, $_SESSION['user_id']);
                    
                case 'create_desa':
                    if ($_SESSION['user_role'] !== 'admin_kecamatan') {
                        return ['success' => false, 'message' => 'Unauthorized'];
                    }
                    return $this->createDesa([
                        'nama_desa' => trim($input['nama_desa'] ?? ''),
                        'luas_wilayah' => $input['luas_wilayah'] ?? 0,
                        'jumlah_dusun' => $input['jumlah_dusun'] ?? 0,
                        'jumlah_rt' => $input['jumlah_rt'] ?? 0,
                        'jumlah_rw' => $input['jumlah_rw'] ?? 0
                    ]);
                    
                case 'update_desa':
                    if ($_SESSION['user_role'] !== 'admin_kecamatan') {
                        return ['success' => false, 'message' => 'Unauthorized'];
                    }
                    return $this->updateDesa($input);
                    
                case 'delete_desa':
                    if ($_SESSION['user_role'] !== 'admin_kecamatan') {
                        return ['success' => false, 'message' => 'Unauthorized'];
                    }
                    return $this->deleteDesa($input['desa_id'] ?? 0);
                    
                default:
                    return ['success' => false, 'message' => 'Action not found'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}
?>