<?php
// UMKM Functions - Sistem Informasi Publik
// File: public-portal/pages/umkm/php/umkm-functions.php

require_once __DIR__ . '/../../../api/core/api-client.php';

class UmkmFunctions {
    private $client;

    public function __construct() {
        $this->client = new ApiClient();
    }

    public function getAll($jenis = null, $id_desa = null, $search = null) {
        $params = [];
        if ($id_desa) $params['id_desa'] = $id_desa;
        $data = $this->client->get('umkm.php', $params) ?? [];
        if ($jenis)  $data = array_values(array_filter($data, fn($u) => $u['jenis_usaha'] === $jenis));
        if ($search) $data = array_values(array_filter($data, fn($u) => stripos($u['nama_usaha'], $search) !== false));
        return $data;
    }

    public function getJenisList() {
        $data = $this->client->get('umkm.php') ?? [];
        return array_unique(array_filter(array_column($data, 'jenis_usaha')));
    }

    public function getDesaList() {
        $data = $this->client->get('umkm.php') ?? [];
        return array_unique(array_column($data, 'nama_desa'));
    }

    public function getKategoriSummary() {
        $raw = $this->client->get('umkm.php', ['type' => 'rekap']) ?? [];
        $labels = $raw['labels'] ?? [];
        $data   = $raw['data']   ?? [];
        $result = [];
        foreach ($labels as $i => $label) {
            $result[$label] = (int)($data[$i] ?? 0);
        }
        arsort($result);
        return $result;
    }
}
?>
