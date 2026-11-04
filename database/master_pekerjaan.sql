-- Master Data Pekerjaan
-- Copy paste query ini ke MySQL untuk menambahkan master data pekerjaan

INSERT INTO pekerjaan_mapping (input_text, standard_name, kategori, status, is_master, created_at, updated_at) VALUES
('Petani', 'Petani', 'Pertanian', 'aktif', 1, NOW(), NOW()),
('Ibu Rumah Tangga', 'Ibu Rumah Tangga', 'Lainnya', 'aktif', 1, NOW(), NOW()),
('Belum/Tidak Bekerja', 'Belum/Tidak Bekerja', 'Lainnya', 'aktif', 1, NOW(), NOW()),
('Pelajar/Mahasiswa', 'Pelajar/Mahasiswa', 'Pendidikan', 'aktif', 1, NOW(), NOW()),
('Wiraswasta', 'Wiraswasta', 'Perdagangan', 'aktif', 1, NOW(), NOW()),
('PNS', 'PNS', 'Pemerintahan', 'aktif', 1, NOW(), NOW()),
('TNI/Polri', 'TNI/Polri', 'Pemerintahan', 'aktif', 1, NOW(), NOW()),
('Karyawan Swasta', 'Karyawan Swasta', 'Industri', 'aktif', 1, NOW(), NOW()),
('Karyawan Honorer', 'Karyawan Honorer', 'Pemerintahan', 'aktif', 1, NOW(), NOW()),
('Guru', 'Guru', 'Pendidikan', 'aktif', 1, NOW(), NOW()),
('Pedagang', 'Pedagang', 'Perdagangan', 'aktif', 1, NOW(), NOW()),
('Buruh', 'Buruh', 'Industri', 'aktif', 1, NOW(), NOW()),
('Pensiunan', 'Pensiunan', 'Lainnya', 'aktif', 1, NOW(), NOW());