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
$user_id = $input['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'ID user tidak valid']);
    exit();
}

// Prevent deleting own account
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus akun sendiri']);
    exit();
}

try {
    $queries = new Queries();
    
    // Delete user
    $stmt = $queries->db->prepare("DELETE FROM user WHERE id_user = ?");
    $stmt->execute([$user_id]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
        exit();
    }
    
    echo json_encode(['success' => true, 'message' => 'User berhasil dihapus']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>