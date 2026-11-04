<?php
// Endpoint: /api/public/infrastruktur.php
// GET ?type=jalan[&id_desa=1]     → data jalan
// GET ?type=jembatan[&id_desa=1]  → data jembatan
// GET ?type=rekap[&id_desa=1]     → ringkasan kondisi jalan & jembatan

require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../../config/database.php';

setCorsHeaders();

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    jsonError('Koneksi database gagal', 500);
}

$type    = $_GET['type']    ?? 'rekap';
$id_desa = isset($_GET['id_desa']) ? (int)$_GET['id_desa'] : null;

$where  = $id_desa ? 'WHERE t.id_desa = :id_desa' : '';
$params = $id_desa ? [':id_desa' => $id_desa] : [];

switch ($type) {

    case 'jalan':
        $stmt = $db->prepare("
            SELECT t.id_jalan, d.nama_desa, t.nama_jalan, t.panjang_jalan,
                   t.lebar_jalan, t.kondisi_jalan, t.jenis_permukaan
            FROM infrastruktur_jalan t
            JOIN desa d ON d.id_desa = t.id_desa
            $where
            ORDER BY d.nama_desa, t.nama_jalan
        ");
        $stmt->execute($params);
        jsonSuccess($stmt->fetchAll(PDO::FETCH_ASSOC));

    case 'jembatan':
        $stmt = $db->prepare("
            SELECT t.id_jembatan, d.nama_desa, t.nama_jembatan, t.panjang_jembatan,
                   t.lebar_jembatan, t.kondisi_jembatan, t.material_jembatan,
                   t.koordinat_latitude, t.koordinat_longitude
            FROM infrastruktur_jembatan t
            JOIN desa d ON d.id_desa = t.id_desa
            $where
            ORDER BY d.nama_desa, t.nama_jembatan
        ");
        $stmt->execute($params);
        jsonSuccess($stmt->fetchAll(PDO::FETCH_ASSOC));

    case 'rekap':
    default:
        // Rekap kondisi jalan
        $stmtJ = $db->prepare("
            SELECT kondisi_jalan AS kondisi,
                   COUNT(*)      AS jumlah,
                   SUM(panjang_jalan) AS total_panjang
            FROM infrastruktur_jalan t
            $where
            GROUP BY kondisi_jalan
        ");
        $stmtJ->execute($params);
        $jalan = $stmtJ->fetchAll(PDO::FETCH_ASSOC);

        // Rekap kondisi jembatan
        $stmtJmb = $db->prepare("
            SELECT kondisi_jembatan AS kondisi, COUNT(*) AS jumlah
            FROM infrastruktur_jembatan t
            $where
            GROUP BY kondisi_jembatan
        ");
        $stmtJmb->execute($params);
        $jembatan = $stmtJmb->fetchAll(PDO::FETCH_ASSOC);

        jsonSuccess([
            'jalan'    => $jalan,
            'jembatan' => $jembatan
        ]);
}
