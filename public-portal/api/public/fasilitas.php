<?php
// API Fasilitas - Sistem Informasi Publik
// File: public-portal/api/public/fasilitas.php

require_once __DIR__ . '/../core/api-base.php';
require_once __DIR__ . '/../core/api-client.php';

$client  = new ApiClient();
$type    = $_GET['type']    ?? 'list';
$id_desa = isset($_GET['id_desa']) ? (int)$_GET['id_desa'] : null;

$params = [];
if ($id_desa) $params['id_desa'] = $id_desa;

if ($type === 'rekap' || $type === 'summary') {
    $params['type'] = 'rekap';
    $data = $client->get('fasilitas.php', $params);
} else {
    $data = $client->get('fasilitas.php', $params);
}

if ($data === null) {
    ResponseHandler::error('Gagal mengambil data dari sistem utama', 503);
}

ResponseHandler::success($data, 'Data fasilitas berhasil diambil');
?>
