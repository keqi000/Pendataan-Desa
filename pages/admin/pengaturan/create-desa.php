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

$nama_desa = trim($input['nama_desa'] ?? '');
$luas_wilayah = $input['luas_wilayah'] ?? 0;
$jumlah_dusun = $input['jumlah_dusun'] ?? 0;
$jumlah_rt = $input['jumlah_rt'] ?? 0;
$jumlah_rw = $input['jumlah_rw'] ?? 0;


if (empty($nama_desa)) {
    echo json_encode(['success' => false, 'message' => 'Nama desa wajib diisi']);
    exit();
}

try {
    $queries = new Queries();
    
    // Check if village name already exists
    $stmt = $queries->db->prepare("SELECT id_desa FROM desa WHERE nama_desa = ?");
    $stmt->execute([$nama_desa]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Nama desa sudah ada']);
        exit();
    }
    
    // Insert new village with auto-filled administrative data
    $stmt = $queries->db->prepare("
        INSERT INTO desa (
            nama_desa, 
            nama_kecamatan, 
            nama_kabupaten, 
            nama_provinsi, 
            luas_wilayah, 
            jumlah_dusun, 
            jumlah_rt, 
            jumlah_rw,
            created_at
        ) VALUES (?, 'Tibawa', 'Gorontalo', 'Gorontalo', ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $nama_desa,
        $luas_wilayah,
        $jumlah_dusun,
        $jumlah_rt,
        $jumlah_rw
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Desa berhasil ditambahkan']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>