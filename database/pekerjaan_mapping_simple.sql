-- =============================================
-- Tabel Pekerjaan Mapping (Versi Sederhana)
-- =============================================

CREATE TABLE pekerjaan_mapping (
    id INT AUTO_INCREMENT PRIMARY KEY,
    input_text VARCHAR(255) NOT NULL COLLATE utf8mb4_bin,
    standard_name VARCHAR(255) NULL COLLATE utf8mb4_bin,
    kategori VARCHAR(100) DEFAULT 'Lainnya',
    status ENUM('approved', 'pending', 'rejected', 'aktif', 'nonaktif') DEFAULT 'pending',
    is_master TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    reviewed_by INT NULL,
    UNIQUE KEY unique_input (input_text),
    INDEX idx_status (status),
    INDEX idx_is_master (is_master)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;


-- Data Master Pekerjaan
INSERT IGNORE INTO `pekerjaan_mapping` (`input_text`, `standard_name`, `kategori`, `status`, `is_master`) VALUES
('Petani', 'Petani', 'Pertanian', 'aktif', 1),
('Ibu Rumah Tangga', 'Ibu Rumah Tangga', 'Lainnya', 'aktif', 1),
('Belum/Tidak Bekerja', 'Belum/Tidak Bekerja', 'Lainnya', 'aktif', 1),
('Pelajar/Mahasiswa', 'Pelajar/Mahasiswa', 'Pendidikan', 'aktif', 1),
('Wiraswasta', 'Wiraswasta', 'Perdagangan', 'aktif', 1),
('PNS', 'PNS', 'Pemerintahan', 'aktif', 1),
('TNI/Polri', 'TNI/Polri', 'Pemerintahan', 'aktif', 1),
('Karyawan Swasta', 'Karyawan Swasta', 'Industri', 'aktif', 1),
('Karyawan Honorer', 'Karyawan Honorer', 'Pemerintahan', 'aktif', 1),
('Guru', 'Guru', 'Pendidikan', 'aktif', 1),
('Pedagang', 'Pedagang', 'Perdagangan', 'aktif', 1),
('Buruh', 'Buruh', 'Industri', 'aktif', 1),
('Pensiunan', 'Pensiunan', 'Lainnya', 'aktif', 1),
('Lainnya', 'Lainnya', 'Lainnya', 'aktif', 1);

-- Contoh Mapping Umum
INSERT IGNORE INTO `pekerjaan_mapping` (`input_text`, `standard_name`, `kategori`, `status`, `is_master`) VALUES
('Tani', 'Petani', 'Pertanian', 'approved', 0),
('Bertani', 'Petani', 'Pertanian', 'approved', 0),
('Dagang', 'Pedagang', 'Perdagangan', 'approved', 0),
('Kuli', 'Buruh', 'Industri', 'approved', 0),
('IRT', 'Ibu Rumah Tangga', 'Lainnya', 'approved', 0),
('Tidak Bekerja', 'Belum/Tidak Bekerja', 'Lainnya', 'approved', 0),
('Siswa', 'Pelajar/Mahasiswa', 'Pendidikan', 'approved', 0),
('Mahasiswa', 'Pelajar/Mahasiswa', 'Pendidikan', 'approved', 0);