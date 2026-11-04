<?php
// Desa Detail Functions - Sistem Informasi Publik
// File: public-portal/pages/desa-detail/php/desa-detail-functions.php

require_once __DIR__ . '/../../../api/core/api-client.php';

class DesaDetailFunctions {
    private $client;

    public function __construct() {
        $this->client = new ApiClient();
    }

    // Ambil semua desa, kembalikan array dengan slug sebagai key
    public function getAllDesaList() {
        $desa = $this->client->get('desa.php') ?? [];
        return array_map(fn($d) => [
            'slug'    => $this->toSlug($d['nama_desa']),
            'nama'    => $d['nama_desa'],
            'id_desa' => $d['id_desa']
        ], $desa);
    }

    // Cari desa berdasarkan slug atau id
    public function getDesaBySlug($slug) {
        $slug = $this->toSlug($slug);
        $list = $this->getAllDesaList();
        foreach ($list as $item) {
            if ($item['slug'] === $slug) {
                return $this->client->get('desa.php', ['id' => $item['id_desa']]) ?? [];
            }
        }
        return [];
    }

    public function getDesaById($id_desa) {
        return $this->client->get('desa.php', ['id' => $id_desa]) ?? [];
    }

    public function getFasilitasDesa($id_desa) {
        $raw = $this->client->get('fasilitas.php', ['type' => 'rekap', 'id_desa' => $id_desa]) ?? [];
        return array_map(fn($r) => [
            'jenis'    => $r['label'],
            'jumlah'   => (int)$r['jumlah'],
            'kapasitas'=> (int)$r['total_kapasitas'],
            'guru'     => (int)$r['total_guru']
        ], $raw);
    }

    public function getUmkmDesa($id_desa) {
        return $this->client->get('umkm.php', ['id_desa' => $id_desa]) ?? [];
    }

    public function getInfrastrukturDesa($id_desa) {
        $raw = $this->client->get('infrastruktur.php', ['type' => 'rekap', 'id_desa' => $id_desa]) ?? [];
        $jalan    = $raw['jalan']    ?? [];
        $jembatan = $raw['jembatan'] ?? [];

        $result = ['jalan_baik' => 0, 'jalan_sedang' => 0, 'jalan_rusak' => 0, 'jembatan' => 0];
        foreach ($jalan as $j) {
            $k = 'jalan_' . strtolower($j['kondisi'] ?? '');
            if (isset($result[$k])) $result[$k] += (int)$j['jumlah'];
        }
        foreach ($jembatan as $j) {
            $result['jembatan'] += (int)$j['jumlah'];
        }
        return $result;
    }

    public function getStatistikUsia($id_desa) {
        return $this->client->get('statistik.php', ['type' => 'usia', 'id_desa' => $id_desa]) ?? [];
    }

    private function toSlug($nama) {
        $slug = strtolower(trim($nama));
        return trim(preg_replace('/[\s\/-]+/', '_', $slug), '_');
    }
}
?>
