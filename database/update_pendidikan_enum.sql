-- Update enum pendidikan_terakhir pada tabel penduduk
-- Menambahkan D1, D2, D4 yang sebelumnya tidak ada
-- Dijalankan: 2026

ALTER TABLE `penduduk` 
MODIFY COLUMN `pendidikan_terakhir` 
enum('Tidak Sekolah','SD','SMP','SMA','D1','D2','D3','D4','S1','S2','S3') 
DEFAULT 'Tidak Sekolah';
