-- Tabel untuk menyimpan hasil analisis tingkat 1
CREATE TABLE IF NOT EXISTS analisis_tingkat1_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_desa VARCHAR(50) DEFAULT 'all',
    
    -- Statistik Kependudukan
    total_penduduk INT DEFAULT 0,
    total_laki INT DEFAULT 0,
    total_perempuan INT DEFAULT 0,
    total_kk INT DEFAULT 0,
    total_balita INT DEFAULT 0,
    total_anak INT DEFAULT 0,
    total_remaja INT DEFAULT 0,
    total_dewasa INT DEFAULT 0,
    total_lansia INT DEFAULT 0,
    
    -- JSON untuk data kompleks
    agama_stats JSON,
    pendidikan_stats JSON,
    pernikahan_stats JSON,
    pekerjaan_stats JSON,
    
    -- Statistik Ekonomi
    mata_pencaharian_stats JSON,
    total_pertanian INT DEFAULT 0,
    total_umkm INT DEFAULT 0,
    total_pasar INT DEFAULT 0,
    umkm_jenis_stats JSON,
    
    -- Statistik Pendidikan
    fasilitas_stats JSON,
    total_kapasitas INT DEFAULT 0,
    total_guru INT DEFAULT 0,
    kapasitas_per_level JSON,
    
    -- Statistik Infrastruktur
    jalan_baik INT DEFAULT 0,
    jalan_sedang INT DEFAULT 0,
    jalan_rusak INT DEFAULT 0,
    total_panjang_jalan DECIMAL(10,2) DEFAULT 0,
    jembatan_baik INT DEFAULT 0,
    jembatan_sedang INT DEFAULT 0,
    jembatan_rusak INT DEFAULT 0,
    total_jembatan INT DEFAULT 0,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    calculation_time_ms INT DEFAULT 0,
    is_calculating BOOLEAN DEFAULT FALSE,
    
    UNIQUE KEY unique_desa (id_desa),
    INDEX idx_updated_at (updated_at),
    INDEX idx_is_calculating (is_calculating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk menyimpan hasil analisis tingkat 2
CREATE TABLE IF NOT EXISTS analisis_tingkat2_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_desa VARCHAR(50) DEFAULT 'all',
    
    -- Analisis Demografi Lanjutan
    kk_perempuan_anak_sekolah INT DEFAULT 0,
    produktif_tanpa_kerja_tetap INT DEFAULT 0,
    total_produktif INT DEFAULT 0,
    kk_lansia_tanggungan INT DEFAULT 0,
    penduduk_non_produktif INT DEFAULT 0,
    penduduk_produktif INT DEFAULT 0,
    rasio_ketergantungan DECIMAL(5,2) DEFAULT 0,
    persen_produktif_tanpa_kerja DECIMAL(5,2) DEFAULT 0,
    
    -- Detail breakdowns (JSON)
    kk_perempuan_detail JSON,
    produktif_detail JSON,
    lansia_detail JSON,
    pendidikan_tinggi_detail JSON,
    
    -- Analisis Ekonomi Lanjutan
    petani_lahan_kecil INT DEFAULT 0,
    petani_lahan_detail JSON,
    komoditas_detail JSON,
    
    -- Korelasi Pendidikan-Pekerjaan
    education_job_correlation JSON,
    
    -- Kesenjangan Pendapatan
    income_gap JSON,
    
    -- Non-Pertanian
    non_agriculture JSON,
    
    -- Analisis Integrasi
    integrated_analysis JSON,
    
    -- Analisis Spasial
    spatial_predictive JSON,
    
    -- Distribusi Pendapatan
    income_distribution JSON,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    calculation_time_ms INT DEFAULT 0,
    is_calculating BOOLEAN DEFAULT FALSE,
    
    UNIQUE KEY unique_desa (id_desa),
    INDEX idx_updated_at (updated_at),
    INDEX idx_is_calculating (is_calculating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk tracking status recalculation
CREATE TABLE IF NOT EXISTS analisis_calculation_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tingkat VARCHAR(20) NOT NULL, -- 'tingkat1', 'tingkat2', 'prediksi'
    id_desa VARCHAR(50) DEFAULT 'all',
    status VARCHAR(20) NOT NULL, -- 'pending', 'running', 'completed', 'failed'
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    execution_time_ms INT DEFAULT 0,
    error_message TEXT,
    triggered_by VARCHAR(50), -- 'user', 'cron', 'auto'
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_tingkat_desa (tingkat, id_desa),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk notifikasi update
CREATE TABLE IF NOT EXISTS analisis_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    tingkat VARCHAR(20) NOT NULL,
    id_desa VARCHAR(50) DEFAULT 'all',
    message TEXT,
    type VARCHAR(20) DEFAULT 'info', -- 'info', 'success', 'warning', 'error'
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
