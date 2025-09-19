<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

class UserAPI
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function handleRequest()
    {
        $endpoint = $_GET['endpoint'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'];

        // Check authentication for most endpoints
        if ($endpoint !== 'get-user' && (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'operator_desa')) {
            return $this->response(false, 'Unauthorized access', null, 401);
        }
        
        // Special authentication for get-user (admin only)
        if ($endpoint === 'get-user' && (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin_kecamatan')) {
            return $this->response(false, 'Unauthorized access', null, 401);
        }

        $id_desa = $_SESSION['user_desa'] ?? $_SESSION['id_desa'] ?? 1;

        switch ($endpoint) {
            case 'get-user':
                return $this->getUser($_GET['id'] ?? null);
            case 'profile-desa':
            case 'data-desa':
                return $this->getProfileDesa($id_desa);
            case 'keluarga-options':
                return $this->getKeluargaOptions($id_desa);
            case 'penduduk-options':
                return $this->getPendudukOptions($id_desa);
            case 'penduduk':
                return $this->getPenduduk($id_desa);
            case 'keluarga':
                return $this->getKeluarga($id_desa);
            case 'mata-pencaharian':
                return $this->getMataPencaharian($id_desa);
            case 'umkm':
                return $this->getUmkm($id_desa);
            case 'pertanian':
                return $this->getPertanian($id_desa);
            case 'pasar':
                return $this->getPasar($id_desa);
            case 'bantuan-sosial':
                return $this->getBantuanSosial($id_desa);
            case 'fasilitas-pendidikan':
                return $this->getFasilitasPendidikan($id_desa);
            case 'pendidikan-stats':
                return $this->getPendidikanStats($id_desa);
            case 'infrastruktur-jalan':
                return $this->getInfrastrukturJalan($id_desa);
            case 'infrastruktur-jembatan':
                return $this->getInfrastrukturJembatan($id_desa);
            case 'create-penduduk':
                return $this->createPenduduk();
            case 'create-keluarga':
                return $this->createKeluarga();
            case 'penduduk-detail':
                return $this->getPendudukDetail($_GET['nik'] ?? null);
            case 'keluarga-detail':
                return $this->getKeluargaDetail($_GET['id'] ?? null);
            case 'delete-penduduk':
                return $this->deletePenduduk();
            case 'delete-keluarga':
                return $this->deleteKeluarga();
            case 'update-data-desa':
                return $this->updateDataDesa();
            case 'create-mata-pencaharian':
                return $this->createMataPencaharian();
            case 'create-umkm':
                return $this->createUmkm();
            case 'create-pertanian':
                return $this->createPertanian();
            case 'create-pasar':
                return $this->createPasar();
            case 'create-bantuan':
                return $this->createBantuan();
            case 'mata-pencaharian-detail':
                return $this->getMataPencaharianDetail($_GET['id'] ?? null);
            case 'update-mata-pencaharian':
                return $this->updateMataPencaharian();
            case 'delete-mata-pencaharian':
                return $this->deleteMataPencaharian();
            case 'umkm-detail':
                return $this->getUmkmDetail($_GET['id'] ?? null);
            case 'update-umkm':
                return $this->updateUmkm();
            case 'delete-umkm':
                return $this->deleteUmkm();
            case 'pertanian-detail':
                return $this->getPertanianDetail($_GET['id'] ?? null);
            case 'update-pertanian':
                return $this->updatePertanian();
            case 'delete-pertanian':
                return $this->deletePertanian();
            case 'pasar-detail':
                return $this->getPasarDetail($_GET['id'] ?? null);
            case 'update-pasar':
                return $this->updatePasar();
            case 'delete-pasar':
                return $this->deletePasar();
            case 'bantuan-detail':
                return $this->getBantuanDetail($_GET['id'] ?? null);
            case 'update-bantuan':
                return $this->updateBantuan();
            case 'delete-bantuan':
                return $this->deleteBantuan();
            case 'fasilitas-detail':
                return $this->getFasilitasDetail($_GET['id'] ?? null);
            case 'create-fasilitas':
                return $this->createFasilitas();
            case 'update-fasilitas':
                return $this->updateFasilitas();
            case 'delete-fasilitas':
                return $this->deleteFasilitas();
            case 'jalan-detail':
                return $this->getJalanDetail($_GET['id'] ?? null);
            case 'create-jalan':
                return $this->createJalan();
            case 'update-jalan':
                return $this->updateJalan();
            case 'delete-jalan':
                return $this->deleteJalan();
            case 'jembatan-detail':
                return $this->getJembatanDetail($_GET['id'] ?? null);
            case 'create-jembatan':
                return $this->createJembatan();
            case 'update-jembatan':
                return $this->updateJembatan();
            case 'delete-jembatan':
                return $this->deleteJembatan();

            default:
                return $this->response(false, 'Endpoint not found', null, 404);
        }
    }

    private function getUser($user_id)
    {
        try {
            if (!$user_id) {
                return $this->response(false, 'User ID required');
            }
            
            $query = "SELECT * FROM user WHERE id_user = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return $this->response(false, 'User not found');
            }

            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    private function getProfileDesa($id_desa)
    {
        try {
            $query = "SELECT * FROM desa WHERE id_desa = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_desa]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    private function getKeluargaOptions($id_desa)
    {
        try {
            $query = "SELECT id_keluarga, nomor_kk, nama_kepala_keluarga FROM keluarga WHERE id_desa = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_desa]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    private function getPendudukOptions($id_desa)
    {
        try {
            $search = $_GET['search'] ?? '';
            $query = "SELECT nik, nama_lengkap FROM penduduk WHERE id_desa = ? AND nik IN (SELECT DISTINCT nik FROM penduduk WHERE id_desa = ?)";
            $params = [$id_desa, $id_desa];
            
            if ($search) {
                $query .= " AND (nama_lengkap LIKE ? OR nik LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $query .= " ORDER BY nama_lengkap LIMIT 10";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    private function getPenduduk($id_desa)
    {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            $search = $_GET['search'] ?? '';
            $jenis_kelamin = $_GET['jenis_kelamin'] ?? '';
            $usia = $_GET['usia'] ?? '';
            
            $offset = ($page - 1) * $limit;
            
            $where = "WHERE id_desa = ?";
            $params = [$id_desa];
            
            if ($search) {
                $where .= " AND (nama_lengkap LIKE ? OR nik LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if ($jenis_kelamin) {
                $where .= " AND jenis_kelamin = ?";
                $params[] = $jenis_kelamin;
            }
            
            if ($usia) {
                switch($usia) {
                    case '0-5': $where .= " AND usia BETWEEN 0 AND 5"; break;
                    case '6-12': $where .= " AND usia BETWEEN 6 AND 12"; break;
                    case '13-17': $where .= " AND usia BETWEEN 13 AND 17"; break;
                    case '18-60': $where .= " AND usia BETWEEN 18 AND 60"; break;
                    case '60+': $where .= " AND usia > 60"; break;
                }
            }
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM penduduk $where";
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get data
            $query = "SELECT * FROM penduduk $where ORDER BY nama_lengkap LIMIT $limit OFFSET $offset";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $pagination = [
                'current_page' => (int)$page,
                'total_pages' => ceil($total / $limit),
                'total_items' => (int)$total,
                'items_per_page' => (int)$limit
            ];

            return $this->response(true, 'Success', ['data' => $result, 'pagination' => $pagination]);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    private function getKeluarga($id_desa)
    {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            $search = $_GET['search'] ?? '';
            
            $offset = ($page - 1) * $limit;
            
            $where = "WHERE k.id_desa = ?";
            $params = [$id_desa];
            
            if ($search) {
                $where .= " AND (k.nama_kepala_keluarga LIKE ? OR k.nomor_kk LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM keluarga k $where";
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get data with member count
            $query = "SELECT k.*, COUNT(p.nik) as jumlah_anggota 
                     FROM keluarga k 
                     LEFT JOIN penduduk p ON k.id_keluarga = p.id_keluarga 
                     $where 
                     GROUP BY k.id_keluarga 
                     ORDER BY k.nama_kepala_keluarga 
                     LIMIT $limit OFFSET $offset";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $pagination = [
                'current_page' => (int)$page,
                'total_pages' => ceil($total / $limit),
                'total_items' => (int)$total,
                'items_per_page' => (int)$limit
            ];

            return $this->response(true, 'Success', ['data' => $result, 'pagination' => $pagination]);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    private function getMataPencaharian($id_desa)
    {
        try {
            $query = "SELECT mp.*, p.nama_lengkap FROM mata_pencaharian mp LEFT JOIN penduduk p ON mp.nik = p.nik WHERE mp.id_desa = ? LIMIT 10";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_desa]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    private function getUmkm($id_desa)
    {
        try {
            $query = "SELECT u.*, p.nama_lengkap as nama_pemilik FROM umkm u LEFT JOIN penduduk p ON u.nik_pemilik = p.nik WHERE u.id_desa = ? LIMIT 10";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_desa]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    private function getPertanian($id_desa)
    {
        try {
            $query = "SELECT pt.*, p.nama_lengkap as nama_petani FROM pertanian pt LEFT JOIN penduduk p ON pt.nik_petani = p.nik WHERE pt.id_desa = ? LIMIT 10";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_desa]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    private function getPasar($id_desa)
    {
        try {
            $query = "SELECT * FROM pasar WHERE id_desa = ? LIMIT 10";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_desa]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    private function getBantuanSosial($id_desa)
    {
        try {
            $query = "SELECT wm.*, p.nama_lengkap FROM warga_miskin wm LEFT JOIN penduduk p ON wm.nik = p.nik WHERE wm.id_desa = ? LIMIT 10";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_desa]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    private function getFasilitasPendidikan($id_desa)
    {
        try {
            $search = $_GET['search'] ?? '';
            $jenis = $_GET['jenis'] ?? '';
            $kondisi = $_GET['kondisi'] ?? '';
            
            $query = "SELECT * FROM fasilitas_pendidikan WHERE id_desa = ?";
            $params = [$id_desa];
            
            if ($search) {
                $query .= " AND nama_fasilitas LIKE ?";
                $params[] = "%$search%";
            }
            
            if ($jenis) {
                $query .= " AND jenis_pendidikan = ?";
                $params[] = $jenis;
            }
            
            if ($kondisi) {
                $query .= " AND kondisi_bangunan = ?";
                $params[] = $kondisi;
            }
            
            $query .= " ORDER BY nama_fasilitas LIMIT 20";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    private function getPendidikanStats($id_desa)
    {
        try {
            $stats = [];
            $query = "SELECT jenis_pendidikan, COUNT(*) as total FROM fasilitas_pendidikan WHERE id_desa = ? GROUP BY jenis_pendidikan";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_desa]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($result as $row) {
                $stats[$row['jenis_pendidikan']] = $row['total'];
            }

            return $this->response(true, 'Success', $stats);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    private function getInfrastrukturJalan($id_desa)
    {
        try {
            $search = $_GET['search'] ?? '';
            $kondisi = $_GET['kondisi'] ?? '';
            $permukaan = $_GET['permukaan'] ?? '';
            
            $query = "SELECT * FROM infrastruktur_jalan WHERE id_desa = ?";
            $params = [$id_desa];
            
            if ($search) {
                $query .= " AND nama_jalan LIKE ?";
                $params[] = "%$search%";
            }
            
            if ($kondisi) {
                $query .= " AND kondisi_jalan = ?";
                $params[] = $kondisi;
            }
            
            if ($permukaan) {
                $query .= " AND jenis_permukaan = ?";
                $params[] = $permukaan;
            }
            
            $query .= " ORDER BY nama_jalan LIMIT 20";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    private function getInfrastrukturJembatan($id_desa)
    {
        try {
            $search = $_GET['search'] ?? '';
            $kondisi = $_GET['kondisi'] ?? '';
            
            $query = "SELECT * FROM infrastruktur_jembatan WHERE id_desa = ?";
            $params = [$id_desa];
            
            if ($search) {
                $query .= " AND nama_jembatan LIKE ?";
                $params[] = "%$search%";
            }
            
            if ($kondisi) {
                $query .= " AND kondisi_jembatan = ?";
                $params[] = $kondisi;
            }
            
            $query .= " ORDER BY nama_jembatan LIMIT 20";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function createPenduduk()
    {
        try {
            $data = $_POST;
            $data['id_desa'] = $_SESSION['user_desa'];
            $data['usia'] = date('Y') - date('Y', strtotime($data['tanggal_lahir']));
            
            $query = "INSERT INTO penduduk (nik, id_desa, id_keluarga, nama_lengkap, jenis_kelamin, tanggal_lahir, usia, agama, pendidikan_terakhir, pekerjaan, status_pernikahan, alamat) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['nik'], $data['id_desa'], $data['id_keluarga'] ?: null, $data['nama_lengkap'],
                $data['jenis_kelamin'], $data['tanggal_lahir'], $data['usia'], $data['agama'],
                $data['pendidikan_terakhir'], $data['pekerjaan'], $data['status_pernikahan'], $data['alamat']
            ]);
            
            return $this->response(true, 'Penduduk berhasil ditambahkan');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function createKeluarga()
    {
        try {
            $data = $_POST;
            $data['id_desa'] = $_SESSION['user_desa'];
            
            $query = "INSERT INTO keluarga (id_desa, nomor_kk, nama_kepala_keluarga, alamat_keluarga) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$data['id_desa'], $data['nomor_kk'], $data['nama_kepala_keluarga'], $data['alamat_keluarga']]);
            
            return $this->response(true, 'Keluarga berhasil ditambahkan');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function getPendudukDetail($nik)
    {
        try {
            $query = "SELECT * FROM penduduk WHERE nik = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$nik]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function getKeluargaDetail($id)
    {
        try {
            $query = "SELECT * FROM keluarga WHERE id_keluarga = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function deletePenduduk()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $query = "DELETE FROM penduduk WHERE nik = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$input['nik']]);
            
            return $this->response(true, 'Penduduk berhasil dihapus');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function deleteKeluarga()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $query = "DELETE FROM keluarga WHERE id_keluarga = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$input['id_keluarga']]);
            
            return $this->response(true, 'Keluarga berhasil dihapus');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function updateDataDesa()
    {
        try {
            $data = $_POST;
            $id_desa = $_SESSION['user_desa'];
            
            $query = "UPDATE desa SET luas_wilayah = ?, jumlah_dusun = ?, jumlah_rt = ?, jumlah_rw = ? WHERE id_desa = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['luas_wilayah'], 
                $data['jumlah_dusun'], 
                $data['jumlah_rt'], 
                $data['jumlah_rw'], 
                $id_desa
            ]);
            
            return $this->response(true, 'Data desa berhasil diupdate');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function createMataPencaharian()
    {
        try {
            $data = $_POST;
            $data['id_desa'] = $_SESSION['user_desa'];
            
            $query = "INSERT INTO mata_pencaharian (id_desa, nik, jenis_pekerjaan, sektor_pekerjaan, penghasilan_perbulan, status_pekerjaan) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['id_desa'], $data['nik'], $data['jenis_pekerjaan'], 
                $data['sektor_pekerjaan'], $data['penghasilan_perbulan'], $data['status_pekerjaan']
            ]);
            
            return $this->response(true, 'Data mata pencaharian berhasil ditambahkan');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function createUmkm()
    {
        try {
            $data = $_POST;
            $data['id_desa'] = $_SESSION['user_desa'];
            
            $query = "INSERT INTO umkm (id_desa, nik_pemilik, nama_usaha, jenis_usaha, modal_usaha, omzet_perbulan, jumlah_karyawan, status_izin, alamat_usaha) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['id_desa'], $data['nik_pemilik'], $data['nama_usaha'], $data['jenis_usaha'],
                $data['modal_usaha'], $data['omzet_perbulan'], $data['jumlah_karyawan'], 
                $data['status_izin'], $data['alamat_usaha']
            ]);
            
            return $this->response(true, 'Data UMKM berhasil ditambahkan');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function createPertanian()
    {
        try {
            $data = $_POST;
            $data['id_desa'] = $_SESSION['user_desa'];
            
            // Insert to data_ekonomi first
            $queryEkonomi = "INSERT INTO data_ekonomi (id_desa, jenis_data, nama_usaha_atau_pekerjaan, deskripsi) VALUES (?, 'pertanian', ?, ?)";
            $stmtEkonomi = $this->db->prepare($queryEkonomi);
            $stmtEkonomi->execute([$data['id_desa'], $data['jenis_komoditas'], 'Data pertanian']);
            $id_ekonomi = $this->db->lastInsertId();
            
            // Insert to pertanian table
            $query = "INSERT INTO pertanian (id_ekonomi, nik_petani, id_desa, luas_lahan, jenis_komoditas, jenis_lahan) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $id_ekonomi, $data['nik_petani'], $data['id_desa'], 
                $data['luas_lahan'], $data['jenis_komoditas'], $data['jenis_lahan']
            ]);
            
            return $this->response(true, 'Data pertanian berhasil ditambahkan');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function createPasar()
    {
        try {
            $data = $_POST;
            $data['id_desa'] = $_SESSION['user_desa'];
            
            $query = "INSERT INTO pasar (id_desa, nama_pasar, jenis_pasar, jumlah_pedagang, alamat_pasar) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['id_desa'], $data['nama_pasar'], $data['jenis_pasar'], 
                $data['jumlah_pedagang'], $data['alamat_pasar']
            ]);
            
            return $this->response(true, 'Data pasar berhasil ditambahkan');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function createBantuan()
    {
        try {
            $data = $_POST;
            $data['id_desa'] = $_SESSION['user_desa'];
            
            $query = "INSERT INTO warga_miskin (id_desa, nik, jenis_bantuan, nominal_bantuan, periode_bantuan, status_penerima) VALUES (?, ?, ?, ?, ?, 'aktif')";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['id_desa'], $data['nik'], $data['jenis_bantuan'], 
                $data['nominal_bantuan'], $data['periode_bantuan']
            ]);
            
            return $this->response(true, 'Data bantuan sosial berhasil ditambahkan');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function getMataPencaharianDetail($id)
    {
        try {
            $query = "SELECT mp.*, p.nama_lengkap FROM mata_pencaharian mp LEFT JOIN penduduk p ON mp.nik = p.nik WHERE mp.id_pencaharian = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function updateMataPencaharian()
    {
        try {
            $data = $_POST;
            $query = "UPDATE mata_pencaharian SET jenis_pekerjaan = ?, sektor_pekerjaan = ?, penghasilan_perbulan = ?, status_pekerjaan = ? WHERE id_pencaharian = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['jenis_pekerjaan'], $data['sektor_pekerjaan'], 
                $data['penghasilan_perbulan'], $data['status_pekerjaan'], $data['id_pencaharian']
            ]);
            
            return $this->response(true, 'Data mata pencaharian berhasil diupdate');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function deleteMataPencaharian()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $query = "DELETE FROM mata_pencaharian WHERE id_pencaharian = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$input['id_pencaharian']]);
            
            return $this->response(true, 'Data mata pencaharian berhasil dihapus');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function getUmkmDetail($id)
    {
        try {
            $query = "SELECT u.*, p.nama_lengkap as nama_pemilik FROM umkm u LEFT JOIN penduduk p ON u.nik_pemilik = p.nik WHERE u.id_umkm = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function updateUmkm()
    {
        try {
            $data = $_POST;
            $query = "UPDATE umkm SET nama_usaha = ?, jenis_usaha = ?, modal_usaha = ?, omzet_perbulan = ?, jumlah_karyawan = ?, status_izin = ?, alamat_usaha = ? WHERE id_umkm = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['nama_usaha'], $data['jenis_usaha'], $data['modal_usaha'], 
                $data['omzet_perbulan'], $data['jumlah_karyawan'], $data['status_izin'], 
                $data['alamat_usaha'], $data['id_umkm']
            ]);
            
            return $this->response(true, 'Data UMKM berhasil diupdate');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function deleteUmkm()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $query = "DELETE FROM umkm WHERE id_umkm = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$input['id_umkm']]);
            
            return $this->response(true, 'Data UMKM berhasil dihapus');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function getPertanianDetail($id)
    {
        try {
            $query = "SELECT pt.*, p.nama_lengkap FROM pertanian pt LEFT JOIN penduduk p ON pt.nik_petani = p.nik WHERE pt.id_pertanian = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function updatePertanian()
    {
        try {
            $data = $_POST;
            $query = "UPDATE pertanian SET luas_lahan = ?, jenis_komoditas = ?, jenis_lahan = ? WHERE id_pertanian = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['luas_lahan'], $data['jenis_komoditas'], 
                $data['jenis_lahan'], $data['id_pertanian']
            ]);
            
            return $this->response(true, 'Data pertanian berhasil diupdate');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function deletePertanian()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $query = "DELETE FROM pertanian WHERE id_pertanian = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$input['id_pertanian']]);
            
            return $this->response(true, 'Data pertanian berhasil dihapus');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function getPasarDetail($id)
    {
        try {
            $query = "SELECT * FROM pasar WHERE id_pasar = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function updatePasar()
    {
        try {
            $data = $_POST;
            $query = "UPDATE pasar SET nama_pasar = ?, jenis_pasar = ?, jumlah_pedagang = ?, alamat_pasar = ? WHERE id_pasar = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['nama_pasar'], $data['jenis_pasar'], 
                $data['jumlah_pedagang'], $data['alamat_pasar'], $data['id_pasar']
            ]);
            
            return $this->response(true, 'Data pasar berhasil diupdate');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function deletePasar()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $query = "DELETE FROM pasar WHERE id_pasar = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$input['id_pasar']]);
            
            return $this->response(true, 'Data pasar berhasil dihapus');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function getBantuanDetail($id)
    {
        try {
            $query = "SELECT wm.*, p.nama_lengkap FROM warga_miskin wm LEFT JOIN penduduk p ON wm.nik = p.nik WHERE wm.id_warga_miskin = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function updateBantuan()
    {
        try {
            $data = $_POST;
            $query = "UPDATE warga_miskin SET jenis_bantuan = ?, nominal_bantuan = ?, periode_bantuan = ? WHERE id_warga_miskin = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['jenis_bantuan'], $data['nominal_bantuan'], 
                $data['periode_bantuan'], $data['id_warga_miskin']
            ]);
            
            return $this->response(true, 'Data bantuan sosial berhasil diupdate');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function deleteBantuan()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $query = "DELETE FROM warga_miskin WHERE id_warga_miskin = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$input['id_warga_miskin']]);
            
            return $this->response(true, 'Data bantuan sosial berhasil dihapus');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function getFasilitasDetail($id)
    {
        try {
            $query = "SELECT * FROM fasilitas_pendidikan WHERE id_fasilitas = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function createFasilitas()
    {
        try {
            $data = $_POST;
            $data['id_desa'] = $_SESSION['user_desa'];
            
            $query = "INSERT INTO fasilitas_pendidikan (id_desa, nama_fasilitas, jenis_pendidikan, alamat_fasilitas, kapasitas_siswa, kondisi_bangunan, jumlah_guru, koordinat_latitude, koordinat_longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['id_desa'], $data['nama_fasilitas'], $data['jenis_pendidikan'], 
                $data['alamat_fasilitas'], $data['kapasitas_siswa'], $data['kondisi_bangunan'], 
                $data['jumlah_guru'], $data['koordinat_latitude'], $data['koordinat_longitude']
            ]);
            
            return $this->response(true, 'Fasilitas pendidikan berhasil ditambahkan');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function updateFasilitas()
    {
        try {
            $data = $_POST;
            
            $query = "UPDATE fasilitas_pendidikan SET nama_fasilitas = ?, jenis_pendidikan = ?, alamat_fasilitas = ?, kapasitas_siswa = ?, kondisi_bangunan = ?, jumlah_guru = ?, koordinat_latitude = ?, koordinat_longitude = ? WHERE id_fasilitas = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['nama_fasilitas'], $data['jenis_pendidikan'], $data['alamat_fasilitas'], 
                $data['kapasitas_siswa'], $data['kondisi_bangunan'], $data['jumlah_guru'], 
                $data['koordinat_latitude'], $data['koordinat_longitude'], $data['id_fasilitas']
            ]);
            
            return $this->response(true, 'Fasilitas pendidikan berhasil diupdate');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function deleteFasilitas()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $query = "DELETE FROM fasilitas_pendidikan WHERE id_fasilitas = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$input['id_fasilitas']]);
            
            return $this->response(true, 'Fasilitas pendidikan berhasil dihapus');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function getJalanDetail($id)
    {
        try {
            $query = "SELECT * FROM infrastruktur_jalan WHERE id_jalan = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function createJalan()
    {
        try {
            $data = $_POST;
            $data['id_desa'] = $_SESSION['user_desa'];
            
            $query = "INSERT INTO infrastruktur_jalan (id_desa, nama_jalan, panjang_jalan, lebar_jalan, kondisi_jalan, jenis_permukaan, koordinat_start_lat, koordinat_start_lng, koordinat_end_lat, koordinat_end_lng) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['id_desa'], $data['nama_jalan'], $data['panjang_jalan'], 
                $data['lebar_jalan'], $data['kondisi_jalan'], $data['jenis_permukaan'],
                $data['koordinat_start_lat'], $data['koordinat_start_lng'],
                $data['koordinat_end_lat'], $data['koordinat_end_lng']
            ]);
            
            return $this->response(true, 'Data jalan berhasil ditambahkan');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function updateJalan()
    {
        try {
            $data = $_POST;
            
            $query = "UPDATE infrastruktur_jalan SET nama_jalan = ?, panjang_jalan = ?, lebar_jalan = ?, kondisi_jalan = ?, jenis_permukaan = ?, koordinat_start_lat = ?, koordinat_start_lng = ?, koordinat_end_lat = ?, koordinat_end_lng = ? WHERE id_jalan = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['nama_jalan'], $data['panjang_jalan'], $data['lebar_jalan'], 
                $data['kondisi_jalan'], $data['jenis_permukaan'],
                $data['koordinat_start_lat'], $data['koordinat_start_lng'],
                $data['koordinat_end_lat'], $data['koordinat_end_lng'], $data['id_jalan']
            ]);
            
            return $this->response(true, 'Data jalan berhasil diupdate');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function deleteJalan()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $query = "DELETE FROM infrastruktur_jalan WHERE id_jalan = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$input['id_jalan']]);
            
            return $this->response(true, 'Data jalan berhasil dihapus');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function getJembatanDetail($id)
    {
        try {
            $query = "SELECT * FROM infrastruktur_jembatan WHERE id_jembatan = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function createJembatan()
    {
        try {
            $data = $_POST;
            $data['id_desa'] = $_SESSION['user_desa'];
            
            $query = "INSERT INTO infrastruktur_jembatan (id_desa, nama_jembatan, panjang_jembatan, lebar_jembatan, kondisi_jembatan, material_jembatan, kapasitas_beban, koordinat_latitude, koordinat_longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['id_desa'], $data['nama_jembatan'], $data['panjang_jembatan'], 
                $data['lebar_jembatan'], $data['kondisi_jembatan'], $data['material_jembatan'],
                $data['kapasitas_beban'], $data['koordinat_latitude'], $data['koordinat_longitude']
            ]);
            
            return $this->response(true, 'Data jembatan berhasil ditambahkan');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function updateJembatan()
    {
        try {
            $data = $_POST;
            
            $query = "UPDATE infrastruktur_jembatan SET nama_jembatan = ?, panjang_jembatan = ?, lebar_jembatan = ?, kondisi_jembatan = ?, material_jembatan = ?, kapasitas_beban = ?, koordinat_latitude = ?, koordinat_longitude = ? WHERE id_jembatan = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['nama_jembatan'], $data['panjang_jembatan'], $data['lebar_jembatan'], 
                $data['kondisi_jembatan'], $data['material_jembatan'], $data['kapasitas_beban'],
                $data['koordinat_latitude'], $data['koordinat_longitude'], $data['id_jembatan']
            ]);
            
            return $this->response(true, 'Data jembatan berhasil diupdate');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function deleteJembatan()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $query = "DELETE FROM infrastruktur_jembatan WHERE id_jembatan = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$input['id_jembatan']]);
            
            return $this->response(true, 'Data jembatan berhasil dihapus');
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }



    private function response($success, $message, $data = null, $code = 200)
    {
        http_response_code($code);
        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ];

        return json_encode($response);
    }
}

// Handle the request
if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $api = new UserAPI();
    echo $api->handleRequest();
    exit();
}
?>