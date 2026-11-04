<?php
// API UMKM - Sistem Informasi Publik
// File: public-portal/api/public/umkm.php

require_once __DIR__ . '/../core/api-base.php';
require_once __DIR__ . '/../core/api-client.php';

$client  = new ApiClient();
$type    = $_GET['type']    ?? 'list';
$id_desa = isset($_GET['id_desa']) ? (int)$_GET['id_desa'] : null;
$search  = $_GET['search']  ?? null;

$params = [];
if ($id_desa) $params['id_desa'] = $id_desa;

if ($type === 'rekap' || $type === 'kategori') {
    $params['type'] = 'rekap';
    $data = $client->get('umkm.php', $params);
} else {
    $data = $client->get('umkm.php', $params);
    // filter search di sisi portal jika ada
    if ($data && $search) {
        $data = array_values(array_filter($data, fn($u) => stripos($u['nama_usaha'], $search) !== false));
    }
}

if ($data === null) {
    ResponseHandler::error('Gagal mengambil data dari sistem utama', 503);
}

ResponseHandler::success($data, 'Data UMKM berhasil diambil');
?>
