<?php
// Fasilitas Functions - Sistem Informasi Publik
// File: public-portal/pages/fasilitas/php/fasilitas-functions.php

require_once __DIR__ . '/../../../api/core/api-client.php';

class FasilitasFunctions {
    private $client;

    public function __construct() {
        $this->client = new ApiClient();
    }

    public function getAll($jenis = null, $id_desa = null) {
        $params = [];
        if ($id_desa) $params['id_desa'] = $id_desa;
        $data = $this->client->get('fasilitas.php', $params) ?? [];
        if ($jenis) {
            $data = array_values(array_filter($data, fn($f) => $f['jenis_pendidikan'] === $jenis));
        }
        return $data;
    }

    public function getJenisList() {
        $data = $this->client->get('fasilitas.php') ?? [];
        return array_unique(array_column($data, 'jenis_pendidikan'));
    }

    public function getDesaList() {
        $data = $this->client->get('fasilitas.php') ?? [];
        return array_unique(array_column($data, 'nama_desa'));
    }

    public function getSummaryByJenis($id_desa = null) {
        $params = ['type' => 'rekap'];
        if ($id_desa) $params['id_desa'] = $id_desa;
        $raw = $this->client->get('fasilitas.php', $params) ?? [];
        return array_map(fn($r) => [
            'jenis'    => $r['label'],
            'jumlah'   => (int)$r['jumlah'],
            'kapasitas'=> (int)$r['total_kapasitas']
        ], $raw);
    }
}
?>
