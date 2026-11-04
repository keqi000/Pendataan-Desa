<?php
// Database Migration - Sistem Informasi Publik
// File: public-portal/database/migration.php
// Jalankan sekali untuk setup database: php migration.php

require_once __DIR__ . '/../config/database.php';

class Migration {
    private $db;

    public function __construct() {
        // Koneksi tanpa dbname dulu untuk buat database
        try {
            $pdo = new PDO("mysql:host=localhost;charset=utf8", 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS pendataan_desa_public CHARACTER SET utf8 COLLATE utf8_general_ci");
            echo "Database pendataan_desa_public siap.\n";
        } catch (PDOException $e) {
            die("Gagal membuat database: " . $e->getMessage() . "\n");
        }

        $database = new PublicDatabase();
        $this->db = $database->getConnection();
    }

    public function run() {
        $this->createTableDesa();
        $this->createTableStatistik();
        $this->createTableFasilitas();
        $this->createTableUmkm();
        echo "Migrasi selesai.\n";
    }

    private function createTableDesa() {
        $this->db->exec("CREATE TABLE IF NOT EXISTS desa (
            id_desa INT PRIMARY KEY,
            nama_desa VARCHAR(100) NOT NULL,
            luas_wilayah DECIMAL(8,2),
            jumlah_dusun INT DEFAULT 0,
            jumlah_rt INT DEFAULT 0,
            jumlah_rw INT DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        echo "  Tabel desa OK.\n";
    }

    private function createTableStatistik() {
        $this->db->exec("CREATE TABLE IF NOT EXISTS statistik_desa (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_desa INT NOT NULL,
            total_penduduk INT DEFAULT 0,
            total_kk INT DEFAULT 0,
            total_laki INT DEFAULT 0,
            total_perempuan INT DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (id_desa) REFERENCES desa(id_desa)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        echo "  Tabel statistik_desa OK.\n";
    }

    private function createTableFasilitas() {
        $this->db->exec("CREATE TABLE IF NOT EXISTS fasilitas_pendidikan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_desa INT NOT NULL,
            nama_fasilitas VARCHAR(150),
            jenis_pendidikan VARCHAR(50),
            kapasitas_siswa INT DEFAULT 0,
            kondisi_bangunan ENUM('Baik','Sedang','Rusak') DEFAULT 'Baik',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (id_desa) REFERENCES desa(id_desa)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        echo "  Tabel fasilitas_pendidikan OK.\n";
    }

    private function createTableUmkm() {
        $this->db->exec("CREATE TABLE IF NOT EXISTS umkm (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_desa INT NOT NULL,
            nama_usaha VARCHAR(150),
            jenis_usaha VARCHAR(100),
            jumlah_karyawan INT DEFAULT 0,
            status_izin ENUM('Aktif','Tidak Aktif') DEFAULT 'Aktif',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (id_desa) REFERENCES desa(id_desa)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        echo "  Tabel umkm OK.\n";
    }
}

if (php_sapi_name() === 'cli') {
    $migration = new Migration();
    $migration->run();
} else {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak. Jalankan via CLI.']);
}
?>
