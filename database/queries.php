<?php
require_once __DIR__ . '/../config/database.php';

class Queries {
    public $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // DESA QUERIES
    public function getAllDesa() {
        $query = "SELECT * FROM desa ORDER BY nama_desa ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getDesaById($id_desa) {
        $query = "SELECT * FROM desa WHERE id_desa = :id_desa";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // PENDUDUK QUERIES
    public function getPendudukByDesa($id_desa) {
        $query = "SELECT p.*, k.nama_kepala_keluarga 
                  FROM penduduk p 
                  LEFT JOIN keluarga k ON p.id_keluarga = k.id_keluarga 
                  WHERE p.id_desa = :id_desa 
                  ORDER BY p.nama_lengkap ASC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getStatistikPenduduk($id_desa) {
        $stats = [];
        
        // Total penduduk
        $query = "SELECT COUNT(*) as total FROM penduduk WHERE id_desa = :id_desa";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Berdasarkan jenis kelamin
        $query = "SELECT jenis_kelamin, COUNT(*) as jumlah 
                  FROM penduduk WHERE id_desa = :id_desa 
                  GROUP BY jenis_kelamin";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        $stats['jenis_kelamin'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Berdasarkan kelompok usia
        $query = "SELECT 
                    CASE 
                        WHEN usia BETWEEN 0 AND 5 THEN 'Balita'
                        WHEN usia BETWEEN 6 AND 12 THEN 'Anak'
                        WHEN usia BETWEEN 13 AND 17 THEN 'Remaja'
                        WHEN usia BETWEEN 18 AND 64 THEN 'Dewasa'
                        ELSE 'Lansia'
                    END as kelompok_usia,
                    COUNT(*) as jumlah
                  FROM penduduk WHERE id_desa = :id_desa
                  GROUP BY kelompok_usia";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        $stats['kelompok_usia'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    
    // EKONOMI QUERIES - Updated to use individual tables instead of data_ekonomi
    public function getDataEkonomi($id_desa) {
        $ekonomi_data = [];
        
        // Get UMKM data
        $umkm = $this->getUMKM($id_desa);
        foreach ($umkm as $u) {
            $ekonomi_data[] = [
                'jenis_data' => 'umkm',
                'nama_usaha_atau_pekerjaan' => $u['nama_usaha'],
                'deskripsi' => $u['deskripsi'] ?? null,
                'jumlah_pelaku' => $u['jumlah_karyawan'] ?? 0,
                'hasil_produksi' => $u['hasil_produksi'] ?? null,
                'nilai_ekonomi' => $u['nilai_ekonomi'] ?? $u['omzet_perbulan'],
                'lokasi' => $u['lokasi'] ?? $u['alamat_usaha']
            ];
        }
        
        // Get Pasar data
        $pasar = $this->getPasarByDesa($id_desa);
        foreach ($pasar as $p) {
            $ekonomi_data[] = [
                'jenis_data' => 'pasar',
                'nama_usaha_atau_pekerjaan' => $p['nama_pasar'],
                'deskripsi' => $p['deskripsi'] ?? null,
                'jumlah_pelaku' => $p['jumlah_pedagang'],
                'hasil_produksi' => null,
                'nilai_ekonomi' => $p['nilai_ekonomi'] ?? null,
                'lokasi' => $p['alamat_pasar']
            ];
        }
        
        // Get Pertanian data
        $pertanian = $this->getPertanianByDesa($id_desa);
        foreach ($pertanian as $pt) {
            $ekonomi_data[] = [
                'jenis_data' => 'pertanian',
                'nama_usaha_atau_pekerjaan' => $pt['jenis_komoditas'],
                'deskripsi' => $pt['deskripsi'] ?? null,
                'jumlah_pelaku' => 1,
                'hasil_produksi' => $pt['produksi_per_musim'],
                'nilai_ekonomi' => $pt['nilai_ekonomi'] ?? $pt['pendapatan_per_musim'],
                'lokasi' => $pt['lokasi'] ?? null
            ];
        }
        
        return $ekonomi_data;
    }
    
    public function getUMKM($id_desa) {
        $query = "SELECT u.*, p.nama_lengkap as nama_pemilik 
                  FROM umkm u 
                  JOIN penduduk p ON u.nik_pemilik = p.nik 
                  WHERE u.id_desa = :id_desa 
                  ORDER BY u.nama_usaha";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getPasarByDesa($id_desa) {
        $query = "SELECT * FROM pasar WHERE id_desa = :id_desa ORDER BY nama_pasar";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getPertanianByDesa($id_desa) {
        $query = "SELECT p.*, pd.nama_lengkap as nama_petani 
                  FROM pertanian p 
                  LEFT JOIN penduduk pd ON p.nik_petani = pd.nik 
                  WHERE p.id_desa = :id_desa 
                  ORDER BY p.jenis_komoditas";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // PENDIDIKAN QUERIES
    public function getFasilitasPendidikan($id_desa) {
        $query = "SELECT * FROM fasilitas_pendidikan WHERE id_desa = :id_desa ORDER BY jenis_pendidikan, nama_fasilitas";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // INFRASTRUKTUR QUERIES
    public function getInfrastrukturJalan($id_desa) {
        $query = "SELECT * FROM infrastruktur_jalan WHERE id_desa = :id_desa ORDER BY nama_jalan";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getInfrastrukturJembatan($id_desa) {
        $query = "SELECT * FROM infrastruktur_jembatan WHERE id_desa = :id_desa ORDER BY nama_jembatan";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // USER QUERIES
    public function getUserByUsername($username) {
        $query = "SELECT u.*, d.nama_desa 
                  FROM user u 
                  LEFT JOIN desa d ON u.id_desa = d.id_desa 
                  WHERE u.username = :username AND u.status_aktif = 'aktif'";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateLastLogin($id_user) {
        $query = "UPDATE user SET last_login = NOW() WHERE id_user = :id_user";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_user', $id_user);
        return $stmt->execute();
    }
    
    // ANALISIS TINGKAT 1 - DASAR
    public function analisisTingkat1Kependudukan($id_desa) {
        $hasil = [];
        
        // Jumlah penduduk berdasarkan jenis kelamin
        $query = "SELECT jenis_kelamin, COUNT(*) as jumlah FROM penduduk WHERE id_desa = :id_desa GROUP BY jenis_kelamin";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        $hasil['jenis_kelamin'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Jumlah penduduk berdasarkan agama
        $query = "SELECT agama, COUNT(*) as jumlah FROM penduduk WHERE id_desa = :id_desa GROUP BY agama";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        $hasil['agama'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Jumlah kepala keluarga
        $query = "SELECT COUNT(*) as jumlah_kk FROM keluarga WHERE id_desa = :id_desa";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        $hasil['kepala_keluarga'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $hasil;
    }
    
    // ANALISIS TINGKAT 2 - LANJUTAN
    public function analisisTingkat2Kependudukan($id_desa) {
        $hasil = [];
        
        // Rasio ketergantungan
        $query = "SELECT 
                    SUM(CASE WHEN usia BETWEEN 15 AND 64 THEN 1 ELSE 0 END) as produktif,
                    SUM(CASE WHEN usia < 15 OR usia > 64 THEN 1 ELSE 0 END) as non_produktif
                  FROM penduduk WHERE id_desa = :id_desa";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data['produktif'] > 0) {
            $hasil['rasio_ketergantungan'] = round(($data['non_produktif'] / $data['produktif']) * 100, 2);
        }
        
        return $hasil;
    }
    
    // INSERT QUERIES
    public function insertPenduduk($data) {
        // Calculate age from birth date
        if (isset($data['tanggal_lahir'])) {
            $data['usia'] = date('Y') - date('Y', strtotime($data['tanggal_lahir']));
        }
        
        $query = "INSERT INTO penduduk (nik, id_desa, id_keluarga, nama_lengkap, jenis_kelamin, tanggal_lahir, usia, agama, pendidikan_terakhir, pekerjaan, status_pernikahan, alamat) 
                  VALUES (:nik, :id_desa, :id_keluarga, :nama_lengkap, :jenis_kelamin, :tanggal_lahir, :usia, :agama, :pendidikan_terakhir, :pekerjaan, :status_pernikahan, :alamat)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute($data);
    }
    
    public function insertKeluarga($data) {
        $query = "INSERT INTO keluarga (id_desa, nomor_kk, nama_kepala_keluarga, alamat_keluarga) 
                  VALUES (:id_desa, :nomor_kk, :nama_kepala_keluarga, :alamat_keluarga)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute($data);
    }
    
    // UPDATE QUERIES
    public function updatePenduduk($nik, $data) {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
        }
        $query = "UPDATE penduduk SET " . implode(', ', $fields) . " WHERE nik = :nik";
        $data['nik'] = $nik;
        $stmt = $this->db->prepare($query);
        return $stmt->execute($data);
    }
    
    // DELETE QUERIES
    public function deletePenduduk($nik) {
        $query = "DELETE FROM penduduk WHERE nik = :nik";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':nik', $nik);
        return $stmt->execute();
    }
    
    // QUERIES UNTUK SKOR PEMBANGUNAN
    public function getPendudukBekerja($id_desa) {
        $query = "SELECT COUNT(*) as total FROM mata_pencaharian WHERE id_desa = :id_desa";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
    
    public function getMataPencaharianByDesa($id_desa) {
        $query = "SELECT mp.*, p.nama_lengkap 
                  FROM mata_pencaharian mp
                  JOIN penduduk p ON mp.nik = p.nik
                  WHERE mp.id_desa = :id_desa 
                  ORDER BY mp.jenis_pekerjaan";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getPendudukUsiaSekolah($id_desa) {
        $query = "SELECT * FROM penduduk WHERE id_desa = :id_desa AND usia BETWEEN 5 AND 18";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // QUERIES UNTUK PELAKU EKONOMI - Updated to use individual tables
    public function getPelakuEkonomi($id_desa) {
        $pelaku_data = [];
        
        // Get UMKM owners and employees
        $umkm = $this->getUMKM($id_desa);
        foreach ($umkm as $u) {
            $pelaku_data[] = [
                'nik' => $u['nik_pemilik'],
                'nama_lengkap' => $u['nama_pemilik'],
                'nama_usaha_atau_pekerjaan' => $u['nama_usaha'],
                'jenis_data' => 'umkm',
                'peran' => 'pemilik'
            ];
        }
        
        // Get farmers
        $pertanian = $this->getPertanianByDesa($id_desa);
        foreach ($pertanian as $p) {
            $pelaku_data[] = [
                'nik' => $p['nik_petani'],
                'nama_lengkap' => $p['nama_petani'],
                'nama_usaha_atau_pekerjaan' => $p['jenis_komoditas'],
                'jenis_data' => 'pertanian',
                'peran' => 'petani'
            ];
        }
        
        return $pelaku_data;
    }
    
    public function getTotalPelakuEkonomiByDesa($id_desa) {
        $query = "SELECT COUNT(*) as total_pelaku
                  FROM mata_pencaharian
                  WHERE id_desa = :id_desa";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_pelaku'] ?? 0;
    }
    
    public function getDataEkonomiWithPelaku($id_desa) {
        return $this->getDataEkonomi($id_desa);
    }
    
    public function getKeluargaByDesa($id_desa) {
        $query = "SELECT * FROM keluarga WHERE id_desa = :id_desa ORDER BY nama_kepala_keluarga";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_desa', $id_desa);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>