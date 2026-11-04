<?php
// BPS API Integration Service
// Domain: 7502 — Kabupaten Gorontalo
// File: pages/admin/monitoring/php/bps-integration-service.php

class BPSIntegrationService {

    private $apiKey  = '8727984217316380738619951e2de78b';
    private $baseUrl = 'https://webapi.bps.go.id/v1/api/';
    private $timeout = 30;

    // Ambil daftar subject/topik di domain 7502
    public function getSubjectList() {
        return $this->fetch("list/model/subject/domain/7502/key/{$this->apiKey}");
    }

    // Ambil daftar tabel statis di domain 7502
    public function getStaticTableList() {
        return $this->fetch("list/model/statictable/domain/7502/key/{$this->apiKey}");
    }

    // Ambil daftar variabel/indikator, opsional filter per subject
    public function getVariableList($subjectId = null) {
        $path = "list/model/var/domain/7502/key/{$this->apiKey}";
        if ($subjectId) $path .= "/subject/{$subjectId}";
        return $this->fetch($path);
    }

    // Ambil isi tabel statis berdasarkan table_id
    public function getStaticTableData($tableId) {
        return $this->fetch("view/model/statictable/domain/7502/id/{$tableId}/lang/ind/key/{$this->apiKey}");
    }

    // Ambil data dinamis berdasarkan var_id, opsional filter tahun
    public function getDynamicData($varId, $tahun = null) {
        $path = "list/model/data/domain/7502/var/{$varId}/key/{$this->apiKey}";
        if ($tahun) $path .= "/th/{$tahun}";
        return $this->fetch($path);
    }

    // ----------------------------------------------------------------
    // METHOD SPESIFIK — berdasarkan data yang sudah diketahui
    // var/114  = Indikator Kependudukan (dinamis, per kecamatan)
    //   turvar 102 = Penduduk (ribu jiwa)
    //   turvar 106 = Rasio Jenis Kelamin
    //   vervar 7   = Kecamatan Tibawa
    // th/124   = Tahun 2024
    // table_id 329 = Kepala Keluarga per Kecamatan 2018 (terbaru tersedia)
    // ----------------------------------------------------------------

    // Variabel kependudukan (sub_id=12)
    public function getVariabelKependudukan() {
        return $this->fetch("list/model/var/domain/7502/key/{$this->apiKey}/subject/12");
    }

    // Data dinamis var/114 tahun 2024 — semua kecamatan
    public function getDataDinamis2024() {
        return $this->fetch("list/model/data/lang/ind/domain/7502/var/114/th/124/key/{$this->apiKey}");
    }

    // Tabel kepala keluarga per kecamatan (table_id=329) — return raw response
    public function getTabelKKKecamatan() {
        return $this->fetch("view/model/statictable/domain/7502/id/329/lang/ind/key/{$this->apiKey}");
    }

    // Tabel penduduk per kecamatan (table_id=330) — fallback 2018
    public function getTabelPendudukKecamatan() {
        return $this->fetch("view/model/statictable/domain/7502/id/330/lang/ind/key/{$this->apiKey}");
    }

    // Parse HTML tabel BPS — ekstrak baris per kecamatan
    // BPS mengembalikan field 'table' berisi HTML string
    private function parseHtmlTable(string $html): array {
        $html = html_entity_decode(html_entity_decode($html));

        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $html, $rows);

        $result = [];
        foreach ($rows[1] as $row) {
            preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $row, $cells);
            $cols = array_map(function($c) {
                $text = strip_tags(html_entity_decode($c));
                // Bersihkan semua jenis whitespace termasuk \r\n literal
                return trim(preg_replace('/[\r\n\t\s]+/', ' ', $text));
            }, $cells[1]);

            $cols = array_values(array_filter($cols, fn($c) => $c !== ''));
            if (count($cols) >= 2) {
                $result[] = $cols;
            }
        }
        return $result;
    }

    // Ambil data BPS Kecamatan Tibawa
    // Penduduk: var/114 dinamis 2024 (vervar=7, turvar=102)
    // KK: tabel statis 329 (2018, terbaru tersedia)
    public function getDataTibawa() {
        $resDinamis = $this->getDataDinamis2024();
        $resKK      = $this->getTabelKKKecamatan();

        $tibawa_penduduk = null;
        $tibawa_kk       = null;
        $semua_kecamatan = [];

        // Parse data dinamis 2024
        // datacontent key format: {vervar}{var=114}{turvar}{tahun=124}0
        // vervar Tibawa = 7, turvar penduduk = 102
        // key: 71141021240
        if ($resDinamis['success'] && isset($resDinamis['data']['datacontent'])) {
            $dc      = $resDinamis['data']['datacontent'];
            $vervar  = $resDinamis['data']['vervar']  ?? [];
            $turvar  = $resDinamis['data']['turvar']  ?? [];

            // Indeks turvar: 102=Penduduk(ribu), 106=Rasio JK
            foreach ($vervar as $kec) {
                $v   = $kec['val'];
                $nama = $kec['label'];
                // key penduduk: {v}1141021240
                $keyPenduduk = "{$v}1141021240";
                $keyRasioJK  = "{$v}1141061240";

                $penduduk_ribu = $dc[$keyPenduduk] ?? null;
                if ($penduduk_ribu === null) continue;

                // BPS menyimpan dalam satuan jiwa (bukan ribu) untuk domain ini
                $total = (int) $penduduk_ribu;

                // Hitung laki/perempuan dari rasio jenis kelamin
                // Rasio JK = (laki/perempuan)*100
                $rasio = (float)($dc[$keyRasioJK] ?? 100);
                $perempuan = (int) round($total / (1 + $rasio / 100));
                $laki      = $total - $perempuan;

                $semua_kecamatan[] = [
                    'kecamatan' => $nama,
                    'laki'      => $laki,
                    'perempuan' => $perempuan,
                    'total'     => $total,
                ];

                if (stripos($nama, 'tibawa') !== false) {
                    $tibawa_penduduk = [
                        'kecamatan' => $nama,
                        'laki'      => $laki,
                        'perempuan' => $perempuan,
                        'total'     => $total,
                        'tahun'     => 2024,
                        'sumber'    => 'BPS — Var/114 Dinamis 2024',
                    ];
                }
            }
        }

        // Parse tabel KK (329) — 2018, terbaru tersedia
        if ($resKK['success'] && isset($resKK['data']['table'])) {
            $rows = $this->parseHtmlTable($resKK['data']['table']);
            foreach ($rows as $cols) {
                $namaIdx = preg_match('/^\d+$/', trim($cols[0] ?? '')) ? 1 : 0;
                $nama = $cols[$namaIdx] ?? '';
                $kk = 0;
                foreach ($cols as $i => $val) {
                    if ($i <= $namaIdx) continue;
                    $angka = (int) str_replace([' ', '.', ','], '', $val);
                    if ($angka > 0) { $kk = $angka; break; }
                }
                if ($kk === 0) continue;
                if (stripos($nama, 'tibawa') !== false) {
                    $tibawa_kk = ['kecamatan' => $nama, 'total_kk' => $kk, 'tahun' => 2018];
                }
            }
        }

        return [
            'success'         => $resDinamis['success'],
            'tibawa_penduduk' => $tibawa_penduduk,
            'tibawa_kk'       => $tibawa_kk,
            'semua_kecamatan' => $semua_kecamatan,
            'fetched_at'      => date('Y-m-d H:i:s'),
        ];
    }

    // Perbandingan per desa vs data BPS kecamatan Tibawa
    // Dipanggil dari quality-check-view.php
    public function getAllDesaComparison() {
        $bps = $this->getDataTibawa();

        if (!$bps['success'] || !$bps['tibawa_penduduk']) {
            return ['success' => false, 'data' => []];
        }

        // BPS hanya punya data level kecamatan, bukan per desa
        // Kembalikan sebagai satu entri kecamatan untuk dipakai quality-check
        return [
            'success'      => true,
            'bps_penduduk' => $bps['tibawa_penduduk']['total'],
            'bps_laki'     => $bps['tibawa_penduduk']['laki'],
            'bps_perempuan'=> $bps['tibawa_penduduk']['perempuan'],
            'bps_kk'       => $bps['tibawa_kk']['total_kk'] ?? 0,
            'tahun'        => $bps['tibawa_penduduk']['tahun'] ?? 2024,
            'sumber'       => $bps['tibawa_penduduk']['sumber'] ?? 'BPS — Var/114 Dinamis 2024',
            'fetched_at'   => $bps['fetched_at'],
            // data array per-desa kosong karena BPS tidak punya breakdown per desa
            'data'         => [],
        ];
    }

    // Perbandingan data sistem pendataan vs data BPS
    // $dataSistem = array dari database sistem
    public function getPerbandinganData(array $dataSistem) {
        $bps   = $this->getDataTibawa();
        $tibawa = $bps['tibawa_penduduk'] ?? [];

        $totalSistem = array_sum(array_column($dataSistem, 'total_penduduk'));
        $lakiSistem  = array_sum(array_column($dataSistem, 'total_laki'));
        $pSistem     = array_sum(array_column($dataSistem, 'total_perempuan'));

        return [
            'success' => $bps['success'],
            'bps' => [
                'total_penduduk' => $tibawa['total']     ?? 0,
                'laki'           => $tibawa['laki']      ?? 0,
                'perempuan'      => $tibawa['perempuan'] ?? 0,
                'tahun'          => $tibawa['tahun']     ?? 2018,
                'sumber'         => $tibawa['sumber']    ?? 'BPS',
            ],
            'sistem' => [
                'total_penduduk' => $totalSistem,
                'laki'           => $lakiSistem,
                'perempuan'      => $pSistem,
                'sumber'         => 'Pendataan Lapangan',
            ],
            'selisih' => [
                'total'     => $totalSistem - ($tibawa['total']     ?? 0),
                'laki'      => $lakiSistem  - ($tibawa['laki']      ?? 0),
                'perempuan' => $pSistem     - ($tibawa['perempuan'] ?? 0),
            ],
            'fetched_at' => date('Y-m-d H:i:s'),
        ];
    }

    // Status koneksi — cek dengan hit ringan ke subject list
    public function getApiStatus() {
        $test = $this->getSubjectList();
        return [
            'success'       => $test['success'],
            'api_key'       => substr($this->apiKey, 0, 8) . '...',
            'domain'        => '7502',
            'nama_domain'   => 'Kabupaten Gorontalo',
            'endpoint'      => $this->baseUrl,
            'checked_at'    => date('Y-m-d H:i:s'),
            'error'         => $test['error'] ?? null,
        ];
    }

    // HTTP GET ke BPS API, return array terstandar
    private function fetch(string $path): array {
        $url = $this->baseUrl . $path;
        $ctx = stream_context_create(['http' => [
            'timeout'       => $this->timeout,
            'ignore_errors' => true,
            'user_agent'    => 'SiPenDes-Tibawa/1.0',
        ]]);

        $raw = @file_get_contents($url, false, $ctx);

        if ($raw === false) {
            return ['success' => false, 'error' => 'Koneksi ke BPS API gagal'];
        }

        $json = json_decode($raw, true);
        if ($json === null) {
            return ['success' => false, 'error' => 'Response bukan JSON', 'raw' => substr($raw, 0, 300)];
        }
        if (($json['status'] ?? '') !== 'OK') {
            return ['success' => false, 'error' => $json['message'] ?? 'Status bukan OK', 'response' => $json];
        }

        // Endpoint dinamis (list/model/data) — field ada di root JSON langsung
        if (isset($json['datacontent'])) {
            return [
                'success' => true,
                'data'    => $json,
            ];
        }

        // Endpoint list (statictable, var, dll) — BPS format: data[0]=pagination, data[1]=items
        $rows = [];
        if (isset($json['data'][1]) && is_array($json['data'][1])) {
            $rows = $json['data'][1];
        } elseif (isset($json['data']) && is_array($json['data'])) {
            $rows = $json['data'];
        }

        return [
            'success'  => true,
            'total'    => $json['data'][0]['total'] ?? count($rows),
            'data'     => $rows,
            'metadata' => [
                'domain'      => '7502',
                'nama_domain' => 'Kabupaten Gorontalo',
                'fetched_at'  => date('Y-m-d H:i:s'),
            ],
        ];
    }
}

// Handle request langsung ke file ini
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    $bps    = new BPSIntegrationService();
    $action = $_GET['action'];

    switch ($action) {
        case 'bps_subject':
            echo json_encode($bps->getSubjectList());
            break;

        case 'bps_statictable':
            echo json_encode($bps->getStaticTableList());
            break;

        case 'bps_variable':
            echo json_encode($bps->getVariableList($_GET['subject_id'] ?? null));
            break;

        case 'bps_var_kependudukan':
            echo json_encode($bps->getVariabelKependudukan());
            break;

        case 'bps_tabel_penduduk':
            echo json_encode($bps->getTabelPendudukKecamatan());
            break;

        case 'bps_tabel_kk':
            echo json_encode($bps->getTabelKKKecamatan());
            break;

        case 'bps_debug_kk':
            // Debug: lihat raw HTML tabel 329 sebelum di-parse
            $raw = $bps->getTabelKKKecamatan();
            $html = $raw['data']['table'] ?? 'tidak ada field table';
            header('Content-Type: text/plain');
            echo html_entity_decode(html_entity_decode(substr($html, 0, 3000)));
            exit();
            break;

        case 'bps_data_tibawa':
            echo json_encode($bps->getDataTibawa());
            break;

        case 'bps_perbandingan':
            // $dataSistem bisa dikirim via POST JSON atau kosong untuk test
            $dataSistem = json_decode(file_get_contents('php://input'), true) ?? [];
            echo json_encode($bps->getPerbandinganData($dataSistem));
            break;

        case 'bps_table_data':
            $id = $_GET['table_id'] ?? '';
            echo json_encode($id ? $bps->getStaticTableData($id)
                                 : ['success' => false, 'error' => 'table_id required']);
            break;

        case 'bps_dynamic':
            $var = $_GET['var_id'] ?? '';
            echo json_encode($var ? $bps->getDynamicData($var, $_GET['tahun'] ?? null)
                                  : ['success' => false, 'error' => 'var_id required']);
            break;

        case 'api_status':
            echo json_encode($bps->getApiStatus());
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action tidak dikenal']);
    }
    exit();
}
?>
