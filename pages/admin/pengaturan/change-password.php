<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../database/queries.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$old_password = $input['old_password'] ?? '';
$new_password = $input['new_password'] ?? '';

if (empty($old_password) || empty($new_password)) {
    echo json_encode(['success' => false, 'message' => 'Password lama dan baru wajib diisi']);
    exit();
}

if (strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password baru minimal 6 karakter']);
    exit();
}

try {
    $queries = new Queries();
    
    // Get current user data
    $stmt = $queries->db->prepare("SELECT password FROM user WHERE id_user = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
        exit();
    }
    
    // Verify old password
    if (!password_verify($old_password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Password lama tidak benar']);
        exit();
    }
    
    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password
    $stmt = $queries->db->prepare("UPDATE user SET password = ? WHERE id_user = ?");
    $stmt->execute([$hashed_password, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Password berhasil diubah']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>