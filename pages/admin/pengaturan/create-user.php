<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../database/queries.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin_kecamatan') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$nama_lengkap = trim($input['nama_lengkap'] ?? '');
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$role = $input['role'] ?? '';
$id_desa = $input['id_desa'] ?? null;

if (empty($nama_lengkap) || empty($username) || empty($email) || empty($password) || empty($role)) {
    echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Format email tidak valid']);
    exit();
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
    exit();
}

try {
    $queries = new Queries();
    
    // Check if username already exists
    $stmt = $queries->db->prepare("SELECT id_user FROM user WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username sudah digunakan']);
        exit();
    }
    
    // Check if email already exists
    $stmt = $queries->db->prepare("SELECT id_user FROM user WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email sudah digunakan']);
        exit();
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $queries->db->prepare("
        INSERT INTO user (
            nama_lengkap, 
            username, 
            email, 
            password, 
            role, 
            id_desa, 
            status_aktif,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'aktif', NOW())
    ");
    
    $stmt->execute([
        $nama_lengkap,
        $username,
        $email,
        $hashed_password,
        $role,
        $id_desa
    ]);
    
    echo json_encode(['success' => true, 'message' => 'User berhasil dibuat']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>