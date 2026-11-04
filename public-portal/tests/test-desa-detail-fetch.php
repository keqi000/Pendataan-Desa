<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Jalankan file ini dari CLI.\n");
}

require_once __DIR__ . '/../config/base-url.php';
require_once __DIR__ . '/../pages/desa-detail/php/desa-detail-functions.php';

$slug = $argv[1] ?? 'ilomata';
$functions = new DesaDetailFunctions();

echo 'API base: ' . MAIN_SYSTEM_API_URL . "data-publik/public/\n";
echo "Slug diuji: {$slug}\n";

$desaList = $functions->getAllDesaList();
echo 'Jumlah desa dari API: ' . count($desaList) . "\n";

$desa = $functions->getDesaBySlug($slug);
if (empty($desa['id_desa'])) {
    fwrite(STDERR, "GAGAL: desa tidak ditemukan dari API berdasarkan slug.\n");
    exit(1);
}

$idDesa = (int)$desa['id_desa'];
echo "Desa terpilih: {$desa['nama_desa']} (ID {$idDesa})\n";
echo 'Penduduk: ' . ($desa['total_penduduk'] ?? 'tidak tersedia') . "\n";

$infrastruktur = $functions->getInfrastrukturDesa($idDesa);
echo 'Rekap jalan: ' . json_encode($infrastruktur, JSON_UNESCAPED_UNICODE) . "\n";

if (array_sum([
    $infrastruktur['jalan_baik'] ?? 0,
    $infrastruktur['jalan_sedang'] ?? 0,
    $infrastruktur['jalan_rusak'] ?? 0
]) === 0) {
    fwrite(STDERR, "GAGAL: API tidak mengembalikan jumlah jalan untuk desa ini.\n");
    exit(1);
}

echo "BERHASIL: detail desa dan data jalan diterima melalui alur halaman.\n";
