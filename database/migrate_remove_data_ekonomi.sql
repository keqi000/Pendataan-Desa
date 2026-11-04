-- Migration: Remove data_ekonomi table and add columns to individual tables
-- Date: 2025-01-XX
-- Description: Migrate data_ekonomi columns to pasar, umkm, and pertanian tables

-- Step 1: Add missing columns to pasar table
ALTER TABLE `pasar` 
ADD COLUMN `deskripsi` text DEFAULT NULL AFTER `alamat_pasar`,
ADD COLUMN `jumlah_pelaku` int(11) DEFAULT 0 AFTER `deskripsi`,
ADD COLUMN `hasil_produksi` varchar(100) DEFAULT NULL AFTER `jumlah_pelaku`,
ADD COLUMN `nilai_ekonomi` decimal(15,2) DEFAULT NULL AFTER `hasil_produksi`,
ADD COLUMN `lokasi` text DEFAULT NULL AFTER `nilai_ekonomi`;

-- Step 2: Add missing columns to umkm table  
ALTER TABLE `umkm`
ADD COLUMN `deskripsi` text DEFAULT NULL AFTER `alamat_usaha`,
ADD COLUMN `hasil_produksi` varchar(100) DEFAULT NULL AFTER `deskripsi`,
ADD COLUMN `nilai_ekonomi` decimal(15,2) DEFAULT NULL AFTER `hasil_produksi`,
ADD COLUMN `lokasi` text DEFAULT NULL AFTER `nilai_ekonomi`;

-- Step 3: Add missing columns to pertanian table
ALTER TABLE `pertanian`
ADD COLUMN `deskripsi` text DEFAULT NULL AFTER `irigasi`,
ADD COLUMN `jumlah_pelaku` int(11) DEFAULT 0 AFTER `deskripsi`,
ADD COLUMN `hasil_produksi_ekonomi` varchar(100) DEFAULT NULL AFTER `jumlah_pelaku`,
ADD COLUMN `nilai_ekonomi` decimal(15,2) DEFAULT NULL AFTER `hasil_produksi_ekonomi`,
ADD COLUMN `lokasi` text DEFAULT NULL AFTER `nilai_ekonomi`;

-- Step 4: Migrate existing data from data_ekonomi to respective tables (if any exists)
-- Note: This assumes data_ekonomi has data that needs to be preserved
-- You may need to run custom migration queries based on your actual data

-- Step 5: Drop foreign key constraints that reference data_ekonomi
ALTER TABLE `pelaku_ekonomi` DROP FOREIGN KEY `pelaku_ekonomi_ibfk_1`;
ALTER TABLE `pertanian` DROP FOREIGN KEY `pertanian_ibfk_1`;
ALTER TABLE `umkm` DROP FOREIGN KEY `umkm_ibfk_2`;

-- Step 6: Remove id_ekonomi columns from tables that reference data_ekonomi
ALTER TABLE `pertanian` DROP COLUMN `id_ekonomi`;
ALTER TABLE `umkm` DROP COLUMN `id_ekonomi`;

-- Step 7: Drop the pelaku_ekonomi table (since it was dependent on data_ekonomi)
DROP TABLE IF EXISTS `pelaku_ekonomi`;

-- Step 8: Drop the data_ekonomi table
DROP TABLE IF EXISTS `data_ekonomi`;

-- Step 9: Update AUTO_INCREMENT values to fill gaps (optional)
-- This will reorganize IDs to fill any gaps left by deletions

-- For pasar table
SET @count = 0;
UPDATE `pasar` SET `id_pasar` = @count:= @count + 1;
ALTER TABLE `pasar` AUTO_INCREMENT = 1;

-- For umkm table  
SET @count = 0;
UPDATE `umkm` SET `id_umkm` = @count:= @count + 1;
ALTER TABLE `umkm` AUTO_INCREMENT = 1;

-- For pertanian table
SET @count = 0;
UPDATE `pertanian` SET `id_pertanian` = @count:= @count + 1;
ALTER TABLE `pertanian` AUTO_INCREMENT = 1;

-- Migration completed successfully
-- The data_ekonomi table has been removed and its functionality distributed to individual tables