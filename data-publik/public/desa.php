<?php
// Endpoint: /api/public/desa.php
// GET /api/public/desa.php          → list semua desa
// GET /api/public/desa.php?id=1     → detail 1 desa

require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../../config/database.php';

setCorsHeaders();

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    jsonError('Koneksi database gagal', 500);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($id) {
    // Detail satu desa + ringkasan statistik
    $stmt = $db->prepare("
        SELECT d.id_desa, d.nama_desa, d.nama_kecamatan, d.nama_kabupaten,
               d.nama_provinsi, d.luas_wilayah, d.jumlah_dusun, d.jumlah_rt, d.jumlah_rw,
               (SELECT COUNT(*) FROM penduduk p WHERE p.id_desa = d.id_desa) AS total_penduduk,
               (SELECT COUNT(*) FROM keluarga k WHERE k.id_desa = d.id_desa) AS total_kk,
               (SELECT COUNT(*) FROM penduduk p WHERE p.id_desa = d.id_desa AND p.jenis_kelamin = 'L') AS total_laki,
               (SELECT COUNT(*) FROM penduduk p WHERE p.id_desa = d.id_desa AND p.jenis_kelamin = 'P') AS total_perempuan
        FROM desa d
        WHERE d.id_desa = :id
    ");
    $stmt->execute([':id' => $id]);
    $desa = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$desa) {
        jsonError('Desa tidak ditemukan', 404);
    }

    jsonSuccess($desa);

} else {
    // List semua desa + ringkasan penduduk
    $stmt = $db->prepare("
        SELECT d.id_desa, d.nama_desa, d.nama_kecamatan, d.luas_wilayah,
               d.jumlah_dusun, d.jumlah_rt, d.jumlah_rw,
               (SELECT COUNT(*) FROM penduduk p WHERE p.id_desa = d.id_desa) AS total_penduduk,
               (SELECT COUNT(*) FROM keluarga k WHERE k.id_desa = d.id_desa) AS total_kk
        FROM desa d
        ORDER BY d.nama_desa ASC
    ");
    $stmt->execute();
    $desa = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonSuccess($desa);
}
