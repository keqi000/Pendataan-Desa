-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 20 Feb 2026 pada 07.48
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pendataan_desa`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `administrative_boundaries`
--

CREATE TABLE `administrative_boundaries` (
  `id_boundary` int(11) NOT NULL,
  `kd_propinsi` varchar(10) DEFAULT NULL,
  `kd_dati2` varchar(10) DEFAULT NULL,
  `kd_kecamatan` varchar(10) DEFAULT NULL,
  `kd_desa` varchar(10) DEFAULT NULL,
  `kd_kelurahan` varchar(10) DEFAULT NULL,
  `nm_propinsi` varchar(100) DEFAULT NULL,
  `nm_kabupaten` varchar(100) DEFAULT NULL,
  `nm_kecamatan` varchar(100) DEFAULT NULL,
  `nm_desa` varchar(100) DEFAULT NULL,
  `nm_kelurahan` varchar(100) DEFAULT NULL,
  `boundary_type` enum('provinsi','kabupaten','kecamatan','desa') NOT NULL,
  `geometry_type` enum('Polygon','MultiPolygon') DEFAULT 'MultiPolygon',
  `geojson_data` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `data_historis`
--

CREATE TABLE `data_historis` (
  `id_historis` int(11) NOT NULL,
  `id_desa` int(11) NOT NULL,
  `tahun` year(4) NOT NULL,
  `bulan` tinyint(4) NOT NULL,
  `total_penduduk` int(11) DEFAULT 0,
  `total_laki` int(11) DEFAULT 0,
  `total_perempuan` int(11) DEFAULT 0,
  `total_balita` int(11) DEFAULT 0,
  `total_anak` int(11) DEFAULT 0,
  `total_remaja` int(11) DEFAULT 0,
  `total_dewasa` int(11) DEFAULT 0,
  `total_lansia` int(11) DEFAULT 0,
  `total_kk` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `desa`
--

CREATE TABLE `desa` (
  `id_desa` int(11) NOT NULL,
  `nama_desa` varchar(100) NOT NULL,
  `nama_kecamatan` varchar(100) NOT NULL,
  `nama_kabupaten` varchar(100) NOT NULL,
  `nama_provinsi` varchar(100) NOT NULL,
  `luas_wilayah` decimal(10,2) DEFAULT NULL,
  `jumlah_dusun` int(11) DEFAULT 0,
  `jumlah_rt` int(11) DEFAULT 0,
  `jumlah_rw` int(11) DEFAULT 0,
  `peta_fisik` varchar(255) DEFAULT NULL,
  `peta_administratif` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `fasilitas_pendidikan`
--

CREATE TABLE `fasilitas_pendidikan` (
  `id_fasilitas` int(11) NOT NULL,
  `id_desa` int(11) NOT NULL,
  `nama_fasilitas` varchar(100) NOT NULL,
  `jenis_pendidikan` enum('PAUD','TK','SD','SMP','SMA','SMK') NOT NULL,
  `alamat_fasilitas` text DEFAULT NULL,
  `kapasitas_siswa` int(11) DEFAULT 0,
  `kondisi_bangunan` enum('Baik','Sedang','Rusak') DEFAULT 'Baik',
  `jumlah_guru` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `koordinat_latitude` decimal(10,8) DEFAULT NULL,
  `koordinat_longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `infrastruktur_jalan`
--

CREATE TABLE `infrastruktur_jalan` (
  `id_jalan` int(11) NOT NULL,
  `id_desa` int(11) NOT NULL,
  `nama_jalan` varchar(100) NOT NULL,
  `panjang_jalan` decimal(8,2) NOT NULL,
  `lebar_jalan` decimal(5,2) DEFAULT NULL,
  `kondisi_jalan` enum('baik','sedang','rusak') DEFAULT 'baik',
  `jenis_permukaan` enum('aspal','beton','tanah','kerikil') DEFAULT 'tanah',
  `foto_jalan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `infrastruktur_jembatan`
--

CREATE TABLE `infrastruktur_jembatan` (
  `id_jembatan` int(11) NOT NULL,
  `id_desa` int(11) NOT NULL,
  `nama_jembatan` varchar(100) NOT NULL,
  `panjang_jembatan` decimal(8,2) NOT NULL,
  `lebar_jembatan` decimal(5,2) DEFAULT NULL,
  `kondisi_jembatan` enum('baik','sedang','rusak') DEFAULT 'baik',
  `material_jembatan` varchar(50) DEFAULT NULL,
  `foto_jembatan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `koordinat_latitude` decimal(10,8) DEFAULT NULL,
  `koordinat_longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `keluarga`
--

CREATE TABLE `keluarga` (
  `id_keluarga` int(11) NOT NULL,
  `id_desa` int(11) NOT NULL,
  `nomor_kk` varchar(20) NOT NULL,
  `nama_kepala_keluarga` varchar(100) NOT NULL,
  `alamat_keluarga` text DEFAULT NULL,
  `jumlah_anggota` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `laporan_riwayat`
--

CREATE TABLE `laporan_riwayat` (
  `id_riwayat` int(11) NOT NULL,
  `nama_laporan` varchar(255) NOT NULL,
  `file_pdf` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT 0,
  `sections` text DEFAULT NULL,
  `tahun_data` year(4) DEFAULT 2025,
  `cakupan_desa` varchar(100) DEFAULT 'all',
  `file_path` varchar(500) DEFAULT NULL,
  `status` enum('draft','completed','failed') DEFAULT 'completed',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `laporan_template`
--

CREATE TABLE `laporan_template` (
  `id_template` int(11) NOT NULL,
  `nama_template` varchar(255) NOT NULL,
  `konfigurasi` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `mata_pencaharian`
--

CREATE TABLE `mata_pencaharian` (
  `id_pencaharian` int(11) NOT NULL,
  `nik` varchar(16) NOT NULL,
  `id_desa` int(11) NOT NULL,
  `jenis_pekerjaan` varchar(100) NOT NULL,
  `sektor_pekerjaan` enum('pertanian','industri','jasa','lainnya') DEFAULT 'lainnya',
  `penghasilan_perbulan` decimal(12,2) DEFAULT NULL,
  `status_pekerjaan` enum('tetap','tidak_tetap') DEFAULT 'tidak_tetap',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pasar`
--

CREATE TABLE `pasar` (
  `id_pasar` int(11) NOT NULL,
  `id_desa` int(11) NOT NULL,
  `nama_pasar` varchar(100) NOT NULL,
  `jenis_pasar` enum('tradisional','modern') DEFAULT 'tradisional',
  `jumlah_pedagang` int(11) DEFAULT 0,
  `hari_operasional` varchar(100) DEFAULT NULL,
  `alamat_pasar` text DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `hasil_produksi` varchar(100) DEFAULT NULL,
  `nilai_ekonomi` decimal(15,2) DEFAULT NULL,
  `kondisi_fasilitas` enum('Baik','Sedang','Rusak') DEFAULT 'Baik',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `penduduk`
--

CREATE TABLE `penduduk` (
  `nik` varchar(16) NOT NULL,
  `id_desa` int(11) NOT NULL,
  `id_keluarga` int(11) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `usia` int(11) DEFAULT NULL,
  `agama` enum('Islam','Kristen','Katolik','Hindu','Buddha','Konghucu') NOT NULL,
  `pendidikan_terakhir` enum('Tidak Sekolah','SD','SMP','SMA','D3','S1','S2','S3') DEFAULT 'Tidak Sekolah',
  `pekerjaan` varchar(100) DEFAULT NULL,
  `status_pernikahan` enum('Belum Menikah','Menikah','Cerai Hidup','Cerai Mati') DEFAULT 'Belum Menikah',
  `status_migrasi` enum('Menetap','Pendatang','Pindah') DEFAULT 'Menetap',
  `alamat` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pertanian`
--

CREATE TABLE `pertanian` (
  `id_pertanian` int(11) NOT NULL,
  `nik_petani` varchar(16) NOT NULL,
  `id_desa` int(11) NOT NULL,
  `luas_lahan` decimal(8,2) DEFAULT NULL,
  `jenis_komoditas` varchar(100) DEFAULT NULL,
  `jenis_lahan` enum('sawah','ladang','kebun','tambak') DEFAULT NULL,
  `status_kepemilikan` enum('milik_sendiri','sewa','bagi_hasil','hibah') DEFAULT NULL,
  `produksi_per_musim` decimal(10,2) DEFAULT NULL,
  `satuan_produksi` enum('ton','kuintal','kg','ikat','buah') DEFAULT NULL,
  `pendapatan_per_musim` decimal(15,2) DEFAULT NULL,
  `musim_tanam` enum('musim_hujan','musim_kemarau','sepanjang_tahun') DEFAULT NULL,
  `bantuan_pertanian` enum('ada','tidak_ada') DEFAULT NULL,
  `jenis_bantuan` varchar(100) DEFAULT NULL,
  `teknologi_pertanian` enum('tradisional','semi_modern','modern') DEFAULT NULL,
  `irigasi` enum('teknis','semi_teknis','sederhana','tadah_hujan') DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `jumlah_pelaku` int(11) DEFAULT 0,
  `nilai_ekonomi` decimal(15,2) DEFAULT NULL,
  `lokasi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `prediksi_penduduk`
--

CREATE TABLE `prediksi_penduduk` (
  `id_prediksi` int(11) NOT NULL,
  `id_desa` int(11) NOT NULL,
  `tahun_prediksi` year(4) NOT NULL,
  `total_penduduk_prediksi` int(11) DEFAULT 0,
  `total_laki_prediksi` int(11) DEFAULT 0,
  `total_perempuan_prediksi` int(11) DEFAULT 0,
  `total_balita_prediksi` int(11) DEFAULT 0,
  `total_anak_prediksi` int(11) DEFAULT 0,
  `total_remaja_prediksi` int(11) DEFAULT 0,
  `total_dewasa_prediksi` int(11) DEFAULT 0,
  `total_lansia_prediksi` int(11) DEFAULT 0,
  `total_kk_prediksi` int(11) DEFAULT 0,
  `growth_rate` decimal(5,2) DEFAULT 0.00,
  `confidence_level` decimal(5,2) DEFAULT 0.00,
  `metode_prediksi` enum('linear_regression','exponential_smoothing','arima') DEFAULT 'linear_regression',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `umkm`
--

CREATE TABLE `umkm` (
  `id_umkm` int(11) NOT NULL,
  `id_desa` int(11) NOT NULL,
  `nik_pemilik` varchar(16) NOT NULL,
  `nama_usaha` varchar(100) NOT NULL,
  `jenis_usaha` varchar(100) DEFAULT NULL,
  `modal_usaha` decimal(15,2) DEFAULT NULL,
  `omzet_perbulan` decimal(15,2) DEFAULT NULL,
  `jumlah_karyawan` int(11) DEFAULT 0,
  `alamat_usaha` text DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `hasil_produksi` varchar(100) DEFAULT NULL,
  `nilai_ekonomi` decimal(15,2) DEFAULT NULL,
  `lokasi` text DEFAULT NULL,
  `status_izin` enum('Ada','Tidak Ada') DEFAULT 'Tidak Ada',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `id_desa` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('operator_desa','admin_kecamatan') NOT NULL,
  `status_aktif` enum('aktif','non_aktif') DEFAULT 'aktif',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `warga_miskin`
--

CREATE TABLE `warga_miskin` (
  `id_warga_miskin` int(11) NOT NULL,
  `nik` varchar(16) NOT NULL,
  `id_desa` int(11) NOT NULL,
  `jenis_bantuan` varchar(100) DEFAULT NULL,
  `nominal_bantuan` decimal(12,2) DEFAULT NULL,
  `periode_bantuan` varchar(50) DEFAULT NULL,
  `status_penerima` enum('aktif','non_aktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `administrative_boundaries`
--
ALTER TABLE `administrative_boundaries`
  ADD PRIMARY KEY (`id_boundary`),
  ADD KEY `idx_kecamatan` (`kd_kecamatan`),
  ADD KEY `idx_desa` (`kd_desa`),
  ADD KEY `idx_type` (`boundary_type`);

--
-- Indeks untuk tabel `data_historis`
--
ALTER TABLE `data_historis`
  ADD PRIMARY KEY (`id_historis`),
  ADD UNIQUE KEY `unique_desa_periode` (`id_desa`,`tahun`,`bulan`);

--
-- Indeks untuk tabel `desa`
--
ALTER TABLE `desa`
  ADD PRIMARY KEY (`id_desa`),
  ADD UNIQUE KEY `unique_desa_created` (`nama_desa`,`created_at`);

--
-- Indeks untuk tabel `fasilitas_pendidikan`
--
ALTER TABLE `fasilitas_pendidikan`
  ADD PRIMARY KEY (`id_fasilitas`),
  ADD UNIQUE KEY `unique_fasilitas_created` (`nama_fasilitas`,`created_at`),
  ADD KEY `id_desa` (`id_desa`);

--
-- Indeks untuk tabel `infrastruktur_jalan`
--
ALTER TABLE `infrastruktur_jalan`
  ADD PRIMARY KEY (`id_jalan`),
  ADD UNIQUE KEY `unique_jalan_created` (`nama_jalan`,`created_at`),
  ADD KEY `id_desa` (`id_desa`);

--
-- Indeks untuk tabel `infrastruktur_jembatan`
--
ALTER TABLE `infrastruktur_jembatan`
  ADD PRIMARY KEY (`id_jembatan`),
  ADD KEY `id_desa` (`id_desa`);

--
-- Indeks untuk tabel `keluarga`
--
ALTER TABLE `keluarga`
  ADD PRIMARY KEY (`id_keluarga`),
  ADD UNIQUE KEY `unique_kk_created` (`nomor_kk`,`created_at`),
  ADD KEY `id_desa` (`id_desa`);

--
-- Indeks untuk tabel `laporan_riwayat`
--
ALTER TABLE `laporan_riwayat`
  ADD PRIMARY KEY (`id_riwayat`),
  ADD KEY `created_by` (`created_by`);

--
-- Indeks untuk tabel `laporan_template`
--
ALTER TABLE `laporan_template`
  ADD PRIMARY KEY (`id_template`),
  ADD KEY `created_by` (`created_by`);

--
-- Indeks untuk tabel `mata_pencaharian`
--
ALTER TABLE `mata_pencaharian`
  ADD PRIMARY KEY (`id_pencaharian`),
  ADD UNIQUE KEY `unique_nik_created` (`nik`,`created_at`),
  ADD KEY `id_desa` (`id_desa`);

--
-- Indeks untuk tabel `pasar`
--
ALTER TABLE `pasar`
  ADD PRIMARY KEY (`id_pasar`),
  ADD UNIQUE KEY `unique_pasar_created` (`nama_pasar`,`created_at`),
  ADD KEY `id_desa` (`id_desa`);

--
-- Indeks untuk tabel `penduduk`
--
ALTER TABLE `penduduk`
  ADD PRIMARY KEY (`nik`,`created_at`),
  ADD KEY `id_desa` (`id_desa`),
  ADD KEY `id_keluarga` (`id_keluarga`);

--
-- Indeks untuk tabel `pertanian`
--
ALTER TABLE `pertanian`
  ADD PRIMARY KEY (`id_pertanian`),
  ADD KEY `id_desa` (`id_desa`);

--
-- Indeks untuk tabel `prediksi_penduduk`
--
ALTER TABLE `prediksi_penduduk`
  ADD PRIMARY KEY (`id_prediksi`),
  ADD UNIQUE KEY `unique_desa_tahun_prediksi` (`id_desa`,`tahun_prediksi`);

--
-- Indeks untuk tabel `umkm`
--
ALTER TABLE `umkm`
  ADD PRIMARY KEY (`id_umkm`),
  ADD UNIQUE KEY `unique_umkm_created` (`nama_usaha`,`created_at`),
  ADD KEY `id_desa` (`id_desa`);

--
-- Indeks untuk tabel `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `id_desa` (`id_desa`);

--
-- Indeks untuk tabel `warga_miskin`
--
ALTER TABLE `warga_miskin`
  ADD PRIMARY KEY (`id_warga_miskin`),
  ADD KEY `id_desa` (`id_desa`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `data_historis`
--
ALTER TABLE `data_historis`
  MODIFY `id_historis` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `desa`
--
ALTER TABLE `desa`
  MODIFY `id_desa` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `fasilitas_pendidikan`
--
ALTER TABLE `fasilitas_pendidikan`
  MODIFY `id_fasilitas` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `infrastruktur_jalan`
--
ALTER TABLE `infrastruktur_jalan`
  MODIFY `id_jalan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `infrastruktur_jembatan`
--
ALTER TABLE `infrastruktur_jembatan`
  MODIFY `id_jembatan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `keluarga`
--
ALTER TABLE `keluarga`
  MODIFY `id_keluarga` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `laporan_riwayat`
--
ALTER TABLE `laporan_riwayat`
  MODIFY `id_riwayat` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `laporan_template`
--
ALTER TABLE `laporan_template`
  MODIFY `id_template` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `mata_pencaharian`
--
ALTER TABLE `mata_pencaharian`
  MODIFY `id_pencaharian` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pasar`
--
ALTER TABLE `pasar`
  MODIFY `id_pasar` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pertanian`
--
ALTER TABLE `pertanian`
  MODIFY `id_pertanian` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `prediksi_penduduk`
--
ALTER TABLE `prediksi_penduduk`
  MODIFY `id_prediksi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `umkm`
--
ALTER TABLE `umkm`
  MODIFY `id_umkm` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `warga_miskin`
--
ALTER TABLE `warga_miskin`
  MODIFY `id_warga_miskin` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `data_historis`
--
ALTER TABLE `data_historis`
  ADD CONSTRAINT `data_historis_ibfk_1` FOREIGN KEY (`id_desa`) REFERENCES `desa` (`id_desa`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `fasilitas_pendidikan`
--
ALTER TABLE `fasilitas_pendidikan`
  ADD CONSTRAINT `fasilitas_pendidikan_ibfk_1` FOREIGN KEY (`id_desa`) REFERENCES `desa` (`id_desa`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `infrastruktur_jalan`
--
ALTER TABLE `infrastruktur_jalan`
  ADD CONSTRAINT `infrastruktur_jalan_ibfk_1` FOREIGN KEY (`id_desa`) REFERENCES `desa` (`id_desa`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `infrastruktur_jembatan`
--
ALTER TABLE `infrastruktur_jembatan`
  ADD CONSTRAINT `infrastruktur_jembatan_ibfk_1` FOREIGN KEY (`id_desa`) REFERENCES `desa` (`id_desa`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `keluarga`
--
ALTER TABLE `keluarga`
  ADD CONSTRAINT `keluarga_ibfk_1` FOREIGN KEY (`id_desa`) REFERENCES `desa` (`id_desa`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `laporan_riwayat`
--
ALTER TABLE `laporan_riwayat`
  ADD CONSTRAINT `laporan_riwayat_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `laporan_template`
--
ALTER TABLE `laporan_template`
  ADD CONSTRAINT `laporan_template_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `mata_pencaharian`
--
ALTER TABLE `mata_pencaharian`
  ADD CONSTRAINT `mata_pencaharian_ibfk_1` FOREIGN KEY (`id_desa`) REFERENCES `desa` (`id_desa`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pasar`
--
ALTER TABLE `pasar`
  ADD CONSTRAINT `pasar_ibfk_1` FOREIGN KEY (`id_desa`) REFERENCES `desa` (`id_desa`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `penduduk`
--
ALTER TABLE `penduduk`
  ADD CONSTRAINT `penduduk_ibfk_1` FOREIGN KEY (`id_desa`) REFERENCES `desa` (`id_desa`) ON DELETE CASCADE,
  ADD CONSTRAINT `penduduk_ibfk_2` FOREIGN KEY (`id_keluarga`) REFERENCES `keluarga` (`id_keluarga`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `pertanian`
--
ALTER TABLE `pertanian`
  ADD CONSTRAINT `pertanian_ibfk_2` FOREIGN KEY (`id_desa`) REFERENCES `desa` (`id_desa`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `prediksi_penduduk`
--
ALTER TABLE `prediksi_penduduk`
  ADD CONSTRAINT `prediksi_penduduk_ibfk_1` FOREIGN KEY (`id_desa`) REFERENCES `desa` (`id_desa`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `umkm`
--
ALTER TABLE `umkm`
  ADD CONSTRAINT `umkm_ibfk_1` FOREIGN KEY (`id_desa`) REFERENCES `desa` (`id_desa`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`id_desa`) REFERENCES `desa` (`id_desa`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `warga_miskin`
--
ALTER TABLE `warga_miskin`
  ADD CONSTRAINT `warga_miskin_ibfk_1` FOREIGN KEY (`id_desa`) REFERENCES `desa` (`id_desa`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
