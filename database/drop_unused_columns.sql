-- Drop unused columns from infrastruktur tables
-- Run this SQL to remove columns that are no longer used in the application

-- Drop coordinate columns from infrastruktur_jalan table
ALTER TABLE infrastruktur_jalan 
DROP COLUMN koordinat_start_lat,
DROP COLUMN koordinat_start_lng,
DROP COLUMN koordinat_end_lat,
DROP COLUMN koordinat_end_lng;

-- Drop kapasitas_beban column from infrastruktur_jembatan table
ALTER TABLE infrastruktur_jembatan 
DROP COLUMN kapasitas_beban;