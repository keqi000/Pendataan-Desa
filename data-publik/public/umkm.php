<?php
// Endpoint: /api/public/umkm.php
// GET /api/public/umkm.php              → semua UMKM
// GET /api/public/umkm.php?id_desa=1    → UMKM per desa
// GET /api/public/umkm.php?type=rekap   → rekap per jenis usaha

require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../../config/database.php';

setCorsHeaders();

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    jsonError('Koneksi database gagal', 500);
}

$id_desa = isset($_GET['id_desa']) ? (int)$_GET['id_desa'] : null;
$type    = $_GET['type'] ?? 'list';

if ($type === 'rekap') {
    // Rekap jumlah UMKM per jenis usaha
    $where  = $id_desa ? 'WHERE u.id_desa = :id_desa' : '';
    $params = $id_desa ? [':id_desa' => $id_desa] : [];

    $stmt = $db->prepare("
        SELECT jenis_usaha AS label, COUNT(*) AS jumlah
        FROM umkm u
        $where
        GROUP BY jenis_usaha
        ORDER BY jumlah DESC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonSuccess([
        'labels' => array_column($rows, 'label'),
        'data'   => array_map('intval', array_column($rows, 'jumlah'))
    ]);
}

// List UMKM — field sensitif (modal, omzet, nik_pemilik) tidak disertakan
$where  = $id_desa ? 'WHERE u.id_desa = :id_desa' : '';
$params = $id_desa ? [':id_desa' => $id_desa] : [];

$stmt = $db->prepare("
    SELECT u.id_umkm, d.nama_desa, u.nama_usaha, u.jenis_usaha,
           u.jumlah_karyawan, u.alamat_usaha, u.status_izin,
           u.hasil_produksi, u.deskripsi
    FROM umkm u
    JOIN desa d ON d.id_desa = u.id_desa
    $where
    ORDER BY d.nama_desa, u.nama_usaha
");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonSuccess($rows);
