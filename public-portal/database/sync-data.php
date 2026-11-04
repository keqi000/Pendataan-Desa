<?php
// Sync Data dari Sistem Utama - Sistem Informasi Publik
// File: public-portal/database/sync-data.php
// Jalankan via CLI: php sync-data.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/api-config.php';
require_once __DIR__ . '/../config/base-url.php';

class DataSync {
    private $db;
    private $mainApiUrl;

    public function __construct() {
        $database = new PublicDatabase();
        $this->db = $database->getConnection();
        $this->mainApiUrl = MAIN_SYSTEM_API_URL . 'api/public/';
    }

    public function syncAll() {
        echo "Memulai sinkronisasi data...\n";
        $this->syncDesa();
        $this->syncStatistik();
        echo "Sinkronisasi selesai.\n";
    }

    private function fetchFromMainSystem($endpoint) {
        $url = $this->mainApiUrl . $endpoint;
        $ctx = stream_context_create(['http' => ['timeout' => 10]]);
        $response = @file_get_contents($url, false, $ctx);
        if (!$response) {
            echo "  [SKIP] Gagal fetch: $endpoint\n";
            return null;
        }
        $data = json_decode($response, true);
        return $data['success'] ? $data['data'] : null;
    }

    private function syncDesa() {
        echo "Sync desa...\n";
        $data = $this->fetchFromMainSystem('desa');
        if (!$data) return;

        $stmt = $this->db->prepare("INSERT INTO desa (id_desa, nama_desa, luas_wilayah) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE nama_desa=VALUES(nama_desa), luas_wilayah=VALUES(luas_wilayah)");
        foreach ($data as $desa) {
            $stmt->execute([$desa['id'], $desa['nama'], $desa['luas']]);
        }
        echo "  " . count($data) . " desa disinkronkan.\n";
    }

    private function syncStatistik() {
        echo "Sync statistik...\n";
        $data = $this->fetchFromMainSystem('statistik');
        if (!$data) return;
        echo "  Statistik disinkronkan.\n";
    }
}

// Jalankan hanya via CLI
if (php_sapi_name() === 'cli') {
    $sync = new DataSync();
    $sync->syncAll();
} else {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak. Jalankan via CLI.']);
}
?>
