<?php
// API Desa - Sistem Informasi Publik
// File: public-portal/api/public/desa.php

require_once __DIR__ . '/../core/api-base.php';
require_once __DIR__ . '/../core/api-client.php';

$client = new ApiClient();
$id     = $_GET['id'] ?? null;

$data = $id
    ? $client->get('desa.php', ['id' => (int)$id])
    : $client->get('desa.php');

if ($data === null) {
    ResponseHandler::error('Gagal mengambil data dari sistem utama', 503);
}

ResponseHandler::success($data, 'Data desa berhasil diambil');
?>
