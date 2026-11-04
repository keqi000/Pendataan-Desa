<?php
// API Statistik - Sistem Informasi Publik
// File: public-portal/api/public/statistik.php

require_once __DIR__ . '/../core/api-base.php';
require_once __DIR__ . '/../core/api-client.php';

$client  = new ApiClient();
$type    = $_GET['type']    ?? 'overview';
$id_desa = isset($_GET['id_desa']) ? (int)$_GET['id_desa'] : null;

$params = ['type' => $type];
if ($id_desa) $params['id_desa'] = $id_desa;

$data = $client->get('statistik.php', $params);

if ($data === null) {
    ResponseHandler::error('Gagal mengambil data dari sistem utama', 503);
}

ResponseHandler::success($data, 'Statistik berhasil diambil');
?>
