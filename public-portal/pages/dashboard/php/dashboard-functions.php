<?php
// Dashboard Functions untuk Sistem Informasi Publik
// File: public-portal/pages/dashboard/php/dashboard-functions.php

require_once __DIR__ . '/../../../api/core/api-client.php';

class PublicDashboardFunctions {
    private $client;

    public function __construct() {
        $this->client = new ApiClient();
    }

    public function getOverviewStatistics() {
        return $this->client->get('statistik.php', ['type' => 'overview']) ?? [];
    }

    public function getTopDesaByPopulation() {
        $desa = $this->client->get('desa.php') ?? [];
        usort($desa, fn($a, $b) => $b['total_penduduk'] - $a['total_penduduk']);
        return array_map(fn($d) => [
            'nama_desa' => $d['nama_desa'],
            'penduduk'  => $d['total_penduduk'],
            'kk'        => $d['total_kk'],
            'id_desa'   => $d['id_desa']
        ], array_slice($desa, 0, 5));
    }

    public function getAgeDistribution() {
        $raw = $this->client->get('statistik.php', ['type' => 'usia']) ?? [];
        return [
            'labels' => $raw['labels'] ?? [],
            'data'   => $raw['data']   ?? [],
            'colors' => ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
        ];
    }

    public function getEducationDistribution() {
        $raw = $this->client->get('statistik.php', ['type' => 'pendidikan']) ?? [];
        return [
            'labels' => $raw['labels'] ?? [],
            'data'   => $raw['data']   ?? [],
            'colors' => ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF']
        ];
    }

    public function getPopularUMKMCategories() {
        $raw = $this->client->get('umkm.php', ['type' => 'rekap']) ?? [];
        $labels = $raw['labels'] ?? [];
        $data   = $raw['data']   ?? [];
        $total  = array_sum($data) ?: 1;
        $result = [];
        foreach ($labels as $i => $label) {
            $jumlah = (int)($data[$i] ?? 0);
            $result[] = [
                'kategori' => $label,
                'jumlah'   => $jumlah,
                'persen'   => round($jumlah / $total * 100, 1)
            ];
        }
        return $result;
    }

    public function getInfrastructureStatus() {
        $raw = $this->client->get('infrastruktur.php', ['type' => 'rekap']) ?? [];
        $jalan    = $raw['jalan']    ?? [];
        $jembatan = $raw['jembatan'] ?? [];

        $parseKondisi = function(array $rows, string $key) {
            $map = ['baik' => 0, 'sedang' => 0, 'rusak' => 0];
            foreach ($rows as $r) {
                $k = strtolower($r[$key] ?? '');
                if (isset($map[$k])) $map[$k] += (int)$r['jumlah'];
            }
            $map['total'] = array_sum($map);
            return $map;
        };

        return [
            'jalan'    => $parseKondisi($jalan,    'kondisi'),
            'jembatan' => $parseKondisi($jembatan, 'kondisi')
        ];
    }

    public function getFacilitiesByType() {
        $raw = $this->client->get('fasilitas.php', ['type' => 'rekap']) ?? [];
        return array_map(fn($r) => [
            'jenis'    => $r['label'],
            'jumlah'   => (int)$r['jumlah'],
            'kapasitas'=> (int)$r['total_kapasitas']
        ], $raw);
    }

    public function getQuickStats() {
        $overview = $this->getOverviewStatistics();
        return [
            [
                'title'    => 'Total Penduduk',
                'value'    => number_format($overview['total_penduduk'] ?? 0),
                'subtitle' => number_format($overview['total_kk'] ?? 0) . ' Kepala Keluarga',
                'icon'     => 'fas fa-users',
                'color'    => 'primary',
                'trend'    => ''
            ],
            [
                'title'    => 'Jumlah Desa',
                'value'    => $overview['total_desa'] ?? 0,
                'subtitle' => 'Desa Aktif',
                'icon'     => 'fas fa-map-marked-alt',
                'color'    => 'success',
                'trend'    => ''
            ],
            [
                'title'    => 'UMKM Aktif',
                'value'    => number_format($overview['total_umkm'] ?? 0),
                'subtitle' => 'Usaha Terdaftar',
                'icon'     => 'fas fa-store',
                'color'    => 'info',
                'trend'    => ''
            ],
            [
                'title'    => 'Fasilitas Pendidikan',
                'value'    => $overview['total_fasilitas_pendidikan'] ?? 0,
                'subtitle' => 'Unit Fasilitas',
                'icon'     => 'fas fa-building',
                'color'    => 'warning',
                'trend'    => ''
            ]
        ];
    }
}

// Handle AJAX requests
if (isset($_GET['endpoint'])) {
    header('Content-Type: application/json');
    $dashboard = new PublicDashboardFunctions();
    $map = [
        'overview'        => 'getOverviewStatistics',
        'top-desa'        => 'getTopDesaByPopulation',
        'age-distribution'=> 'getAgeDistribution',
        'education'       => 'getEducationDistribution',
        'umkm-categories' => 'getPopularUMKMCategories',
        'infrastructure'  => 'getInfrastructureStatus',
        'facilities'      => 'getFacilitiesByType',
        'quick-stats'     => 'getQuickStats',
    ];
    $method = $map[$_GET['endpoint']] ?? null;
    if ($method) {
        echo json_encode(['success' => true, 'data' => $dashboard->$method()]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
    }
    exit();
}
?>