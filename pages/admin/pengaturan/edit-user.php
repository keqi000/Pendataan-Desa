<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../database/queries.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin_kecamatan') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$queries = new Queries();

try {
    $user_id = $input['user_id'];
    $nama_lengkap = $input['nama_lengkap'];
    $username = $input['username'];
    $email = $input['email'];
    $password = $input['password'];
    $role = $input['role'];
    $id_desa = $input['id_desa'];
    $status_aktif = $input['status_aktif'];

    // Check if username already exists (excluding current user)
    $stmt = $queries->db->prepare("SELECT id_user FROM user WHERE username = ? AND id_user != ?");
    $stmt->execute([$username, $user_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username sudah digunakan']);
        exit();
    }

    // Check if email already exists (excluding current user)
    $stmt = $queries->db->prepare("SELECT id_user FROM user WHERE email = ? AND id_user != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email sudah digunakan']);
        exit();
    }

    // Update user
    if ($password) {
        // Update with new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $queries->db->prepare("
            UPDATE user 
            SET nama_lengkap = ?, username = ?, email = ?, password = ?, role = ?, id_desa = ?, status_aktif = ?, updated_at = NOW()
            WHERE id_user = ?
        ");
        $stmt->execute([$nama_lengkap, $username, $email, $hashed_password, $role, $id_desa, $status_aktif, $user_id]);
    } else {
        // Update without changing password
        $stmt = $queries->db->prepare("
            UPDATE user 
            SET nama_lengkap = ?, username = ?, email = ?, role = ?, id_desa = ?, status_aktif = ?, updated_at = NOW()
            WHERE id_user = ?
        ");
        $stmt->execute([$nama_lengkap, $username, $email, $role, $id_desa, $status_aktif, $user_id]);
    }

    echo json_encode(['success' => true, 'message' => 'User berhasil diupdate']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>