<?php
// Endpoint: /api/public/fasilitas.php
// GET /api/public/fasilitas.php             → semua fasilitas pendidikan
// GET /api/public/fasilitas.php?id_desa=1   → per desa
// GET /api/public/fasilitas.php?type=rekap  → rekap per jenis

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

$where  = $id_desa ? 'WHERE f.id_desa = :id_desa' : '';
$params = $id_desa ? [':id_desa' => $id_desa] : [];

if ($type === 'rekap') {
    $stmt = $db->prepare("
        SELECT jenis_pendidikan AS label,
               COUNT(*)         AS jumlah,
               SUM(kapasitas_siswa) AS total_kapasitas,
               SUM(jumlah_guru)    AS total_guru
        FROM fasilitas_pendidikan f
        $where
        GROUP BY jenis_pendidikan
        ORDER BY FIELD(jenis_pendidikan,'PAUD','TK','SD','SMP','SMA','SMK')
    ");
    $stmt->execute($params);
    jsonSuccess($stmt->fetchAll(PDO::FETCH_ASSOC));
}

$stmt = $db->prepare("
    SELECT f.id_fasilitas, d.nama_desa, f.nama_fasilitas, f.jenis_pendidikan,
           f.alamat_fasilitas, f.kapasitas_siswa, f.kondisi_bangunan,
           f.jumlah_guru, f.koordinat_latitude, f.koordinat_longitude
    FROM fasilitas_pendidikan f
    JOIN desa d ON d.id_desa = f.id_desa
    $where
    ORDER BY d.nama_desa, f.jenis_pendidikan, f.nama_fasilitas
");
$stmt->execute($params);
jsonSuccess($stmt->fetchAll(PDO::FETCH_ASSOC));
