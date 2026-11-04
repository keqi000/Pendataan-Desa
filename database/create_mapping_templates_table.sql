-- Migration: Create mapping_templates table for auto-transform feature
-- File: database/create_mapping_templates_table.sql

CREATE TABLE IF NOT EXISTS mapping_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_desa INT NOT NULL,
    template_name VARCHAR(100) NOT NULL,
    mapping_data JSON NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_template_per_desa (id_desa, template_name),
    FOREIGN KEY (id_desa) REFERENCES desa(id_desa) ON DELETE CASCADE
);

-- Insert default mapping templates
INSERT INTO mapping_templates (id_desa, template_name, mapping_data, description) VALUES
(1, 'Format Standard', '{"nik":{"index":0,"header":"NIK","confidence":1},"nama_lengkap":{"index":1,"header":"Nama Lengkap","confidence":1},"jenis_kelamin":{"index":2,"header":"Jenis Kelamin","confidence":1},"tanggal_lahir":{"index":3,"header":"Tanggal Lahir","confidence":1},"agama":{"index":4,"header":"Agama","confidence":1},"pendidikan_terakhir":{"index":5,"header":"Pendidikan Terakhir","confidence":1},"pekerjaan":{"index":6,"header":"Pekerjaan","confidence":1},"status_pernikahan":{"index":7,"header":"Status Pernikahan","confidence":1},"status_migrasi":{"index":8,"header":"Status Migrasi","confidence":1},"alamat":{"index":9,"header":"Alamat","confidence":1}}', 'Template standar untuk format Excel yang umum digunakan'),
(1, 'Format Sederhana', '{"nik":{"index":0,"header":"NIK","confidence":1},"nama_lengkap":{"index":1,"header":"Nama","confidence":1},"jenis_kelamin":{"index":2,"header":"JK","confidence":1},"tanggal_lahir":{"index":3,"header":"TTL","confidence":1},"alamat":{"index":4,"header":"Alamat","confidence":1}}', 'Template untuk format Excel sederhana dengan kolom minimal');