<?php
// Database Queries - Sistem Informasi Publik
// File: public-portal/database/queries.php

require_once __DIR__ . '/../config/database.php';

class PublicQueries {
    private $db;

    public function __construct() {
        $database = new PublicDatabase();
        $this->db = $database->getConnection();
    }

    public function getAllDesa() {
        $stmt = $this->db->query("SELECT id_desa, nama_desa, luas_wilayah, jumlah_dusun, jumlah_rt, jumlah_rw FROM desa ORDER BY nama_desa");
        return $stmt->fetchAll();
    }

    public function getStatistikDesa($id_desa) {
        $stmt = $this->db->prepare("SELECT total_penduduk, total_kk, total_laki, total_perempuan FROM statistik_desa WHERE id_desa = ?");
        $stmt->execute([$id_desa]);
        return $stmt->fetch();
    }

    public function getFasilitasByDesa($id_desa) {
        $stmt = $this->db->prepare("SELECT nama_fasilitas, jenis_pendidikan, kapasitas_siswa, kondisi_bangunan FROM fasilitas_pendidikan WHERE id_desa = ?");
        $stmt->execute([$id_desa]);
        return $stmt->fetchAll();
    }

    public function getUmkmByDesa($id_desa) {
        $stmt = $this->db->prepare("SELECT nama_usaha, jenis_usaha, jumlah_karyawan, status_izin FROM umkm WHERE id_desa = ?");
        $stmt->execute([$id_desa]);
        return $stmt->fetchAll();
    }
}
?>
