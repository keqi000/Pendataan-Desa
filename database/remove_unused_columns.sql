-- SQL untuk menghapus kolom-kolom yang tidak diperlukan
-- Jalankan script ini di phpMyAdmin atau MySQL client

-- 1. Hapus kolom hasil_produksi_ekonomi dari tabel pertanian
ALTER TABLE pertanian DROP COLUMN IF EXISTS hasil_produksi_ekonomi;

-- 2. Hapus kolom pelaku_ekonomi dari tabel pasar (karena sudah ada jumlah_pedagang)
ALTER TABLE pasar DROP COLUMN IF EXISTS pelaku_ekonomi;

-- 3. Hapus kolom lokasi dari tabel pasar (karena sudah ada alamat_pasar)
ALTER TABLE pasar DROP COLUMN IF EXISTS lokasi;

-- Catatan: 
-- - Pastikan backup database sebelum menjalankan script ini
-- - Script menggunakan IF EXISTS untuk menghindari error jika kolom sudah tidak ada