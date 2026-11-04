-- Update struktur tabel laporan_riwayat untuk menyimpan file PDF
-- Hapus semua data yang ada
DELETE FROM laporan_riwayat;

-- Reset auto increment
ALTER TABLE laporan_riwayat AUTO_INCREMENT = 1;

-- Hapus kolom yang tidak diperlukan
ALTER TABLE laporan_riwayat 
DROP COLUMN IF EXISTS data_laporan,
DROP COLUMN IF EXISTS konfigurasi;

-- Tambah kolom untuk file PDF
ALTER TABLE laporan_riwayat 
ADD COLUMN file_pdf VARCHAR(255) NOT NULL AFTER nama_laporan,
ADD COLUMN file_size INT DEFAULT 0 AFTER file_pdf,
ADD COLUMN sections TEXT AFTER file_size,
ADD COLUMN tahun_data YEAR DEFAULT 2025 AFTER sections,
ADD COLUMN cakupan_desa VARCHAR(100) DEFAULT 'all' AFTER tahun_data;

-- Update struktur final tabel
-- Kolom yang ada sekarang:
-- id_riwayat (PK)
-- nama_laporan
-- file_pdf (path ke file PDF)
-- file_size (ukuran file dalam bytes)
-- sections (JSON string berisi sections yang dipilih)
-- tahun_data (tahun data laporan)
-- cakupan_desa (desa yang dicakup)
-- created_at
-- updated_at