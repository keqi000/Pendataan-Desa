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
$desa_id = $input['desa_id'] ?? 0;

if (!$desa_id) {
    echo json_encode(['success' => false, 'message' => 'ID desa tidak valid']);
    exit();
}

try {
    $queries = new Queries();
    
    // Start transaction
    $queries->db->beginTransaction();
    
    // Delete related data first (foreign key constraints)
    $tables = [
        'warga_miskin',
        'infrastruktur_jembatan', 
        'infrastruktur_jalan',
        'umkm',
        'mata_pencaharian',
        'data_ekonomi',
        'fasilitas_pendidikan',
        'penduduk',
        'keluarga',
        'user'
    ];
    
    foreach ($tables as $table) {
        $stmt = $queries->db->prepare("DELETE FROM $table WHERE id_desa = ?");
        $stmt->execute([$desa_id]);
    }
    
    // Finally delete the village
    $stmt = $queries->db->prepare("DELETE FROM desa WHERE id_desa = ?");
    $stmt->execute([$desa_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Desa tidak ditemukan');
    }
    
    // Commit transaction
    $queries->db->commit();
    
    echo json_encode(['success' => true, 'message' => 'Desa berhasil dihapus']);
    
} catch (Exception $e) {
    // Rollback transaction
    $queries->db->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>