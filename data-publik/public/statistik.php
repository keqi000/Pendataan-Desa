<?php
// Endpoint: /api/public/statistik.php
// GET ?type=overview              → ringkasan total semua desa
// GET ?type=usia[&id_desa=1]      → distribusi usia
// GET ?type=pendidikan[&id_desa=1]→ distribusi pendidikan
// GET ?type=pekerjaan[&id_desa=1] → distribusi sektor pekerjaan
// GET ?type=agama[&id_desa=1]     → distribusi agama

require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../../config/database.php';

setCorsHeaders();

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    jsonError('Koneksi database gagal', 500);
}

$type    = $_GET['type']    ?? 'overview';
$id_desa = isset($_GET['id_desa']) ? (int)$_GET['id_desa'] : null;

// Kondisi WHERE opsional per desa
$where  = $id_desa ? 'WHERE id_desa = :id_desa' : '';
$params = $id_desa ? [':id_desa' => $id_desa] : [];

switch ($type) {

    case 'overview':
        $stmt = $db->prepare("
            SELECT
                (SELECT COUNT(*) FROM desa)                          AS total_desa,
                COUNT(DISTINCT p.nik)                                AS total_penduduk,
                COUNT(DISTINCT k.id_keluarga)                        AS total_kk,
                SUM(p.jenis_kelamin = 'L')                           AS total_laki,
                SUM(p.jenis_kelamin = 'P')                           AS total_perempuan,
                (SELECT COUNT(*) FROM umkm)                          AS total_umkm,
                (SELECT COUNT(*) FROM fasilitas_pendidikan)          AS total_fasilitas_pendidikan,
                (SELECT COUNT(*) FROM infrastruktur_jalan)           AS total_jalan,
                (SELECT COUNT(*) FROM infrastruktur_jembatan)        AS total_jembatan
            FROM penduduk p
            LEFT JOIN keluarga k ON k.id_keluarga = p.id_keluarga
        ");
        $stmt->execute();
        jsonSuccess($stmt->fetch(PDO::FETCH_ASSOC));

    case 'usia':
        $stmt = $db->prepare("
            SELECT
                SUM(usia BETWEEN 0  AND 5)  AS balita,
                SUM(usia BETWEEN 6  AND 12) AS anak,
                SUM(usia BETWEEN 13 AND 17) AS remaja,
                SUM(usia BETWEEN 18 AND 64) AS dewasa,
                SUM(usia >= 65)             AS lansia
            FROM penduduk $where
        ");
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        jsonSuccess([
            'labels' => ['Balita (0-5)', 'Anak (6-12)', 'Remaja (13-17)', 'Dewasa (18-64)', 'Lansia (65+)'],
            'data'   => array_values(array_map('intval', $row))
        ]);

    case 'pendidikan':
        $stmt = $db->prepare("
            SELECT pendidikan_terakhir AS label, COUNT(*) AS jumlah
            FROM penduduk $where
            GROUP BY pendidikan_terakhir
            ORDER BY FIELD(pendidikan_terakhir,
                'Tidak Sekolah','SD','SMP','SMA','D3','S1','S2','S3')
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonSuccess([
            'labels' => array_column($rows, 'label'),
            'data'   => array_map('intval', array_column($rows, 'jumlah'))
        ]);

    case 'pekerjaan':
        $stmt = $db->prepare("
            SELECT sektor_pekerjaan AS label, COUNT(*) AS jumlah
            FROM mata_pencaharian
            " . ($id_desa ? 'WHERE id_desa = :id_desa' : '') . "
            GROUP BY sektor_pekerjaan
            ORDER BY jumlah DESC
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonSuccess([
            'labels' => array_column($rows, 'label'),
            'data'   => array_map('intval', array_column($rows, 'jumlah'))
        ]);

    case 'agama':
        $stmt = $db->prepare("
            SELECT agama AS label, COUNT(*) AS jumlah
            FROM penduduk $where
            GROUP BY agama
            ORDER BY jumlah DESC
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonSuccess([
            'labels' => array_column($rows, 'label'),
            'data'   => array_map('intval', array_column($rows, 'jumlah'))
        ]);

    default:
        jsonError('Parameter type tidak valid. Gunakan: overview, usia, pendidikan, pekerjaan, agama');
}
