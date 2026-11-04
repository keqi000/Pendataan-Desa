-- =============================================
-- Tabel Pekerjaan Mapping
-- =============================================
-- Tabel untuk mengelola normalisasi data pekerjaan
-- Digunakan oleh PekerjaanManager untuk mapping input user ke pekerjaan standar

CREATE TABLE IF NOT EXISTS `pekerjaan_mapping` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `input_text` VARCHAR(255) NOT NULL COMMENT 'Input pekerjaan dari user',
    `standard_name` VARCHAR(255) NULL COMMENT 'Nama pekerjaan standar hasil mapping',
    `kategori` VARCHAR(100) DEFAULT 'Lainnya' COMMENT 'Kategori pekerjaan (Pertanian, Perdagangan, dll)',
    `status` ENUM('approved', 'pending', 'rejected', 'aktif', 'nonaktif') DEFAULT 'pending' COMMENT 'Status mapping atau master',
    `is_master` TINYINT(1) DEFAULT 0 COMMENT '1 jika ini adalah pekerjaan master/standar',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `reviewed_by` INT NULL COMMENT 'ID admin yang mereview mapping',
    
    -- Indexes
    UNIQUE KEY `unique_input` (`input_text`),
    INDEX `idx_status` (`status`),
    INDEX `idx_is_master` (`is_master`),
    INDEX `idx_kategori` (`kategori`),
    
    -- Foreign Key
    FOREIGN KEY (`reviewed_by`) REFERENCES `user`(`id_user`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabel mapping pekerjaan untuk normalisasi data';

-- =============================================
-- Insert Data Master Pekerjaan
-- =============================================
-- Data pekerjaan standar yang akan digunakan sebagai acuan

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

-- =============================================
-- Contoh Data Mapping (Opsional)
-- =============================================
-- Contoh mapping input user ke pekerjaan standar

INSERT IGNORE INTO `pekerjaan_mapping` (`input_text`, `standard_name`, `kategori`, `status`, `is_master`) VALUES
-- Variasi input untuk Petani
('Tani', 'Petani', 'Pertanian', 'approved', 0),
('Bertani', 'Petani', 'Pertanian', 'approved', 0),
('Pekebun', 'Petani', 'Pertanian', 'approved', 0),

-- Variasi input untuk Pedagang
('Dagang', 'Pedagang', 'Perdagangan', 'approved', 0),
('Berdagang', 'Pedagang', 'Perdagangan', 'approved', 0),
('Jual Beli', 'Pedagang', 'Perdagangan', 'approved', 0),

-- Variasi input untuk Buruh
('Kuli', 'Buruh', 'Industri', 'approved', 0),
('Pekerja Harian', 'Buruh', 'Industri', 'approved', 0),
('Buruh Harian', 'Buruh', 'Industri', 'approved', 0),

-- Variasi input untuk IRT
('IRT', 'Ibu Rumah Tangga', 'Lainnya', 'approved', 0),
('Mengurus Rumah Tangga', 'Ibu Rumah Tangga', 'Lainnya', 'approved', 0),

-- Variasi input untuk tidak bekerja
('Tidak Bekerja', 'Belum/Tidak Bekerja', 'Lainnya', 'approved', 0),
('Belum Bekerja', 'Belum/Tidak Bekerja', 'Lainnya', 'approved', 0),
('Pengangguran', 'Belum/Tidak Bekerja', 'Lainnya', 'approved', 0),

-- Variasi input untuk pelajar
('Siswa', 'Pelajar/Mahasiswa', 'Pendidikan', 'approved', 0),
('Mahasiswa', 'Pelajar/Mahasiswa', 'Pendidikan', 'approved', 0),
('Sekolah', 'Pelajar/Mahasiswa', 'Pendidikan', 'approved', 0);

-- =============================================
-- Indexes Tambahan untuk Optimasi
-- =============================================

-- Index untuk pencarian berdasarkan standard_name
CREATE INDEX `idx_standard_name` ON `pekerjaan_mapping` (`standard_name`);

-- Index gabungan untuk query yang sering digunakan
CREATE INDEX `idx_status_master` ON `pekerjaan_mapping` (`status`, `is_master`);
CREATE INDEX `idx_kategori_status` ON `pekerjaan_mapping` (`kategori`, `status`);

-- =============================================
-- View untuk Statistik Pekerjaan
-- =============================================

CREATE OR REPLACE VIEW `v_pekerjaan_stats` AS
SELECT 
    pm.standard_name,
    pm.kategori,
    COUNT(p.nik) as jumlah_penduduk,
    pm.status,
    pm.is_master
FROM pekerjaan_mapping pm
LEFT JOIN penduduk p ON p.pekerjaan = pm.standard_name
WHERE pm.is_master = 1 AND pm.status = 'aktif'
GROUP BY pm.id, pm.standard_name, pm.kategori, pm.status, pm.is_master
ORDER BY jumlah_penduduk DESC, pm.standard_name;

-- =============================================
-- View untuk Pending Review
-- =============================================

CREATE OR REPLACE VIEW `v_pekerjaan_pending` AS
SELECT 
    pm.*,
    COUNT(p.nik) as usage_count,
    u.nama_lengkap as reviewed_by_name
FROM pekerjaan_mapping pm
LEFT JOIN penduduk p ON p.pekerjaan = pm.input_text
LEFT JOIN user u ON pm.reviewed_by = u.id_user
WHERE pm.status = 'pending'
GROUP BY pm.id
ORDER BY usage_count DESC, pm.created_at ASC;

-- =============================================
-- Trigger untuk Update Timestamp
-- =============================================

DELIMITER $$

CREATE TRIGGER `tr_pekerjaan_mapping_update` 
BEFORE UPDATE ON `pekerjaan_mapping`
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END$$

DELIMITER ;

-- =============================================
-- Stored Procedure untuk Normalisasi Batch
-- =============================================

DELIMITER $$

CREATE PROCEDURE `sp_normalize_pekerjaan_batch`()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_input_text VARCHAR(255);
    DECLARE v_standard_name VARCHAR(255);
    
    DECLARE cur CURSOR FOR 
        SELECT DISTINCT p.pekerjaan, pm.standard_name
        FROM penduduk p
        JOIN pekerjaan_mapping pm ON p.pekerjaan = pm.input_text
        WHERE pm.status = 'approved' 
        AND pm.standard_name IS NOT NULL
        AND p.pekerjaan != pm.standard_name;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_input_text, v_standard_name;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        UPDATE penduduk 
        SET pekerjaan = v_standard_name 
        WHERE pekerjaan = v_input_text;
        
    END LOOP;
    
    CLOSE cur;
    
    SELECT ROW_COUNT() as updated_rows;
END$$

DELIMITER ;

-- =============================================
-- Function untuk Mendapatkan Pekerjaan Standar
-- =============================================

DELIMITER $$

CREATE FUNCTION `fn_get_standard_pekerjaan`(input_pekerjaan VARCHAR(255)) 
RETURNS VARCHAR(255)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE result VARCHAR(255) DEFAULT 'Belum/Tidak Bekerja';
    
    -- Cek jika input kosong atau null
    IF input_pekerjaan IS NULL OR TRIM(input_pekerjaan) = '' OR input_pekerjaan = '-' THEN
        RETURN 'Belum/Tidak Bekerja';
    END IF;
    
    -- Cari mapping yang sudah approved
    SELECT standard_name INTO result
    FROM pekerjaan_mapping 
    WHERE input_text = TRIM(input_pekerjaan) 
    AND status = 'approved' 
    AND standard_name IS NOT NULL
    LIMIT 1;
    
    -- Jika tidak ditemukan, return input asli
    IF result IS NULL THEN
        SET result = input_pekerjaan;
    END IF;
    
    RETURN result;
END$$

DELIMITER ;

-- =============================================
-- Contoh Penggunaan
-- =============================================

/*
-- Untuk mendapatkan statistik pekerjaan:
SELECT * FROM v_pekerjaan_stats;

-- Untuk melihat pekerjaan yang menunggu review:
SELECT * FROM v_pekerjaan_pending;

-- Untuk normalisasi batch:
CALL sp_normalize_pekerjaan_batch();

-- Untuk mendapatkan pekerjaan standar:
SELECT fn_get_standard_pekerjaan('Tani') as standard_pekerjaan;
*/