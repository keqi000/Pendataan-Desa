-- Add administrative boundaries table
CREATE TABLE IF NOT EXISTS administrative_boundaries (
    id_boundary INT PRIMARY KEY AUTO_INCREMENT,
    boundary_type ENUM('provinsi', 'kabupaten', 'kecamatan', 'desa') NOT NULL,
    kd_provinsi VARCHAR(10),
    kd_kabupaten VARCHAR(10),
    kd_kecamatan VARCHAR(10),
    kd_desa VARCHAR(10),
    nm_provinsi VARCHAR(100),
    nm_kabupaten VARCHAR(100),
    nm_kecamatan VARCHAR(100),
    nm_desa VARCHAR(100),
    nm_kelurahan VARCHAR(100),
    geojson_data LONGTEXT,
    file_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample boundary data for testing (empty boundaries)
INSERT INTO administrative_boundaries (boundary_type, nm_kecamatan, geojson_data) VALUES 
('kecamatan', 'Tibawa', '{"type":"FeatureCollection","features":[]}'),
('desa', 'Tibawa Selatan', '{"type":"FeatureCollection","features":[]}'),
('desa', 'Tibawa Utara', '{"type":"FeatureCollection","features":[]}');