-- Remove default values from pertanian table
ALTER TABLE `pertanian` 
MODIFY `luas_lahan` decimal(8,2) NULL,
MODIFY `jenis_komoditas` varchar(100) NULL,
MODIFY `jenis_lahan` enum('sawah','ladang','kebun','tambak') NULL,
MODIFY `status_kepemilikan` enum('milik_sendiri','sewa','bagi_hasil','hibah') NULL,
MODIFY `satuan_produksi` enum('ton','kuintal','kg','ikat','buah') NULL,
MODIFY `musim_tanam` enum('musim_hujan','musim_kemarau','sepanjang_tahun') NULL,
MODIFY `bantuan_pertanian` enum('ada','tidak_ada') NULL,
MODIFY `teknologi_pertanian` enum('tradisional','semi_modern','modern') NULL,
MODIFY `irigasi` enum('teknis','semi_teknis','sederhana','tadah_hujan') NULL;