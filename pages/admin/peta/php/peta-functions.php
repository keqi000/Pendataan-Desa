<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/session.php';
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../../database/queries.php';

class PetaFunctions {
    private $queries;
    private $db;
    
    public function __construct() {
        $this->queries = new Queries();
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function checkAuthentication() {
        if (!initSession() || !validateSession()) {
            header('Location: ' . BASE_URL . 'pages/auth/login.php');
            exit();
        }
        if ($_SESSION['user_role'] !== 'admin_kecamatan') {
            header('Location: ' . BASE_URL . 'pages/auth/login.php');
            exit();
        }
    }
    
    public function getAllDesa() {
        try {
            $query = "SELECT * FROM desa ORDER BY nama_desa";
            $stmt = $this->queries->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching desa: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAllFasilitas() {
        try {
            $query = "SELECT f.*, d.nama_desa 
                     FROM fasilitas_pendidikan f 
                     JOIN desa d ON f.id_desa = d.id_desa 
                     ORDER BY d.nama_desa, f.nama_fasilitas";
            $stmt = $this->queries->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching fasilitas: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAllJalan() {
        try {
            $query = "SELECT j.*, d.nama_desa 
                     FROM infrastruktur_jalan j 
                     JOIN desa d ON j.id_desa = d.id_desa 
                     ORDER BY d.nama_desa, j.nama_jalan";
            $stmt = $this->queries->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching jalan: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAllJembatan() {
        try {
            $query = "SELECT j.*, d.nama_desa 
                     FROM infrastruktur_jembatan j 
                     JOIN desa d ON j.id_desa = d.id_desa 
                     ORDER BY d.nama_desa, j.nama_jembatan";
            $stmt = $this->queries->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching jembatan: " . $e->getMessage());
            return [];
        }
    }
    
    public function calculateFasilitasStats($fasilitas) {
        $jenis_count = [];
        foreach ($fasilitas as $f) {
            $jenis = $f['jenis_pendidikan'];
            $jenis_count[$jenis] = ($jenis_count[$jenis] ?? 0) + 1;
        }
        return $jenis_count;
    }
    
    public function calculateJalanStats($jalan) {
        return [
            'total_count' => count($jalan),
            'total_panjang' => array_sum(array_column($jalan, 'panjang_jalan'))
        ];
    }
    
    public function calculateJembatanStats($jembatan) {
        $kondisi_baik = count(array_filter($jembatan, function($j) { 
            return $j['kondisi_jembatan'] === 'baik'; 
        }));
        return [
            'total_count' => count($jembatan),
            'kondisi_baik' => $kondisi_baik
        ];
    }
    
    public function calculateDesaStats($desa) {
        return [
            'total_count' => count($desa),
            'total_luas' => array_sum(array_column($desa, 'luas_wilayah'))
        ];
    }
    
    public function getBadgeClass($kondisi, $type = 'kondisi') {
        if ($type === 'kondisi') {
            switch (strtolower($kondisi)) {
                case 'baik': return 'success';
                case 'sedang': return 'warning';
                default: return 'danger';
            }
        }
        return 'info';
    }
    
    public function handleAdministrativeBoundariesUpload() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit();
        }
        
        if (!isset($_FILES['administrative_boundaries_file'])) {
            echo json_encode(['success' => false, 'message' => 'No file uploaded']);
            exit();
        }
        
        $file = $_FILES['administrative_boundaries_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'File upload error']);
            exit();
        }
        
        $allowedTypes = ['application/json', 'text/plain'];
        if (!in_array($file['type'], $allowedTypes) && !str_ends_with($file['name'], '.geojson') && !str_ends_with($file['name'], '.json')) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only GeoJSON files allowed']);
            exit();
        }
        
        try {
            $geojsonContent = file_get_contents($file['tmp_name']);
            $geojson = json_decode($geojsonContent, true);
            
            if (!$geojson || $geojson['type'] !== 'FeatureCollection') {
                throw new Exception('Invalid GeoJSON format. Must be a FeatureCollection');
            }
            
            $inserted = 0;
            $errors = 0;
            
            foreach ($geojson['features'] as $feature) {
                try {
                    $properties = $feature['properties'] ?? [];
                    
                    $kdPropinsi = $properties['kd_propinsi'] ?? null;
                    $kdDati2 = $properties['kd_dati2'] ?? null;
                    $kdKecamatan = $properties['kd_kecamatan'] ?? null;
                    $kdDesa = $properties['kd_desa'] ?? null;
                    $kdKelurahan = $properties['kd_kelurahan'] ?? null;
                    $nmKabupaten = $properties['nm_kabupaten'] ?? null;
                    $nmKecamatan = $properties['nm_kecamatan'] ?? null;
                    $nmDesa = $properties['nm_desa'] ?? null;
                    $nmKelurahan = $properties['nm_kelurahan'] ?? null;
                    
                    $boundaryType = 'kecamatan';
                    if ($kdDesa || $kdKelurahan) $boundaryType = 'desa';
                    elseif ($kdKecamatan && !$kdDesa && !$kdKelurahan) $boundaryType = 'kecamatan';
                    elseif ($kdDati2 && !$kdKecamatan) $boundaryType = 'kabupaten';
                    elseif ($kdPropinsi && !$kdDati2) $boundaryType = 'provinsi';
                    
                    $geometryType = $feature['geometry']['type'] ?? 'MultiPolygon';
                    
                    $featureGeoJSON = json_encode([
                        'type' => 'Feature',
                        'geometry' => $feature['geometry'],
                        'properties' => $properties
                    ]);
                    
                    $stmtId = $this->db->prepare("
                        SELECT COALESCE(
                            (SELECT MIN(id + 1) 
                             FROM (SELECT 0 as id UNION SELECT id_boundary FROM administrative_boundaries) t 
                             WHERE (id + 1) NOT IN (SELECT id_boundary FROM administrative_boundaries)), 
                            1
                        ) as next_id
                    ");
                    $stmtId->execute();
                    $nextId = $stmtId->fetchColumn() ?: 1;
                    
                    $stmt = $this->db->prepare("
                        INSERT INTO administrative_boundaries 
                        (id_boundary, kd_propinsi, kd_dati2, kd_kecamatan, kd_desa, kd_kelurahan, nm_kabupaten, nm_kecamatan, nm_desa, nm_kelurahan, boundary_type, geometry_type, geojson_data) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                        geojson_data = VALUES(geojson_data),
                        updated_at = CURRENT_TIMESTAMP
                    ");
                    
                    $stmt->execute([$nextId, $kdPropinsi, $kdDati2, $kdKecamatan, $kdDesa, $kdKelurahan, $nmKabupaten, $nmKecamatan, $nmDesa, $nmKelurahan, $boundaryType, $geometryType, $featureGeoJSON]);
                    $inserted++;
                    
                } catch (Exception $e) {
                    $errors++;
                    error_log("Error processing feature: " . $e->getMessage());
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Administrative boundaries uploaded successfully',
                'inserted' => $inserted,
                'errors' => $errors
            ]);
            
        } catch (Exception $e) {
            error_log("Administrative boundaries upload error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error processing file: ' . $e->getMessage()
            ]);
        }
    }
    
    public function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    public function handleApiRequest() {
        $endpoint = $_GET['endpoint'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'];
        
        if ($endpoint === 'administrative-boundaries' || $endpoint === 'fasilitas-pendidikan' || $endpoint === 'infrastruktur-jembatan') {
            if (!isset($_SESSION['user_id'])) {
                return $this->response(false, 'Unauthorized access', null, 401);
            }
        } else {
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin_kecamatan') {
                return $this->response(false, 'Unauthorized access', null, 401);
            }
        }
        
        switch ($endpoint) {
            case 'map-data':
                return $this->getMapData();
            case 'administrative-boundaries':
                return $this->getAdministrativeBoundaries();
            case 'upload-boundaries':
                return $this->uploadBoundaries();
            case 'desa-coordinates':
                return $this->getDesaCoordinates();
            case 'update-coordinates':
                return $this->updateCoordinates();
            case 'map-statistics':
                return $this->getMapStatistics();
            case 'fasilitas-pendidikan':
                return $this->getFasilitasPendidikan();
            case 'infrastruktur-jembatan':
                return $this->getInfrastrukturJembatan();
            default:
                return $this->response(false, 'Endpoint not found', null, 404);
        }
    }
    
    private function getMapData() {
        try {
            $type = $_GET['type'] ?? 'all';
            
            if ($type === 'all') {
                $all_desa = $this->getAllDesa();
                $all_fasilitas = $this->getAllFasilitas();
                $all_jalan = $this->getAllJalan();
                $all_jembatan = $this->getAllJembatan();
                
                return $this->response(true, 'Success', [
                    'desa' => $all_desa,
                    'fasilitas' => $all_fasilitas,
                    'jalan' => $all_jalan,
                    'jembatan' => $all_jembatan
                ]);
            } else {
                $query = "SELECT d.*, 
                         (SELECT COUNT(*) FROM penduduk p WHERE p.id_desa = d.id_desa) as total_penduduk,
                         (SELECT COUNT(*) FROM umkm u WHERE u.id_desa = d.id_desa) as total_umkm,
                         (SELECT COUNT(*) FROM fasilitas_pendidikan f WHERE f.id_desa = d.id_desa) as total_fasilitas
                         FROM desa d 
                         WHERE d.koordinat_latitude IS NOT NULL AND d.koordinat_longitude IS NOT NULL
                         ORDER BY d.nama_desa";
                $stmt = $this->db->prepare($query);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $mapData = [];
                foreach ($result as $desa) {
                    $mapData[] = [
                        'id' => $desa['id_desa'],
                        'name' => $desa['nama_desa'],
                        'coordinates' => [
                            'lat' => (float)$desa['koordinat_latitude'],
                            'lng' => (float)$desa['koordinat_longitude']
                        ],
                        'stats' => [
                            'penduduk' => (int)$desa['total_penduduk'],
                            'umkm' => (int)$desa['total_umkm'],
                            'fasilitas' => (int)$desa['total_fasilitas']
                        ],
                        'info' => [
                            'luas_wilayah' => $desa['luas_wilayah'],
                            'jumlah_dusun' => $desa['jumlah_dusun']
                        ]
                    ];
                }
                
                return $this->response(true, 'Success', $mapData);
            }
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function getAdministrativeBoundaries() {
        try {
            $stmt = $this->db->prepare("
                SELECT id_boundary, boundary_type, kd_kecamatan, nm_kabupaten, nm_kecamatan, nm_desa, nm_kelurahan, geojson_data, created_at
                FROM administrative_boundaries 
                ORDER BY 
                    CASE boundary_type 
                        WHEN 'provinsi' THEN 1 
                        WHEN 'kabupaten' THEN 2 
                        WHEN 'kecamatan' THEN 3 
                        WHEN 'desa' THEN 4 
                    END,
                    nm_kabupaten, nm_kecamatan, nm_desa, nm_kelurahan
            ");
            
            $stmt->execute();
            $boundaries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->response(true, 'Success', $boundaries);
        } catch (Exception $e) {
            return $this->response(true, 'Success', []);
        }
    }
    
    private function uploadBoundaries() {
        try {
            if (!isset($_FILES['boundary_file'])) {
                return $this->response(false, 'No file uploaded');
            }
            
            $file = $_FILES['boundary_file'];
            $boundary_type = $_POST['boundary_type'] ?? 'desa';
            
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($file_extension, ['geojson', 'json'])) {
                return $this->response(false, 'File harus berformat GeoJSON');
            }
            
            $geojson_content = file_get_contents($file['tmp_name']);
            $geojson_data = json_decode($geojson_content, true);
            
            if (!$geojson_data || !isset($geojson_data['type']) || $geojson_data['type'] !== 'FeatureCollection') {
                return $this->response(false, 'Format GeoJSON tidak valid');
            }
            
            $upload_dir = __DIR__ . '/../../../../database/boundaries/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $filename = $boundary_type . '_' . date('Y-m-d_H-i-s') . '.geojson';
            $file_path = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                try {
                    $query = "INSERT INTO administrative_boundaries (boundary_type, name, file_path, geojson_data, created_at) VALUES (?, ?, ?, ?, NOW())";
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([
                        $boundary_type,
                        $_POST['boundary_name'] ?? $filename,
                        'database/boundaries/' . $filename,
                        $geojson_content
                    ]);
                } catch (Exception $e) {
                    // Continue anyway
                }
                
                return $this->response(true, 'Boundary file berhasil diupload', [
                    'filename' => $filename,
                    'features_count' => count($geojson_data['features'])
                ]);
            } else {
                return $this->response(false, 'Gagal menyimpan file');
            }
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function getDesaCoordinates() {
        try {
            $query = "SELECT id_desa, nama_desa, koordinat_latitude, koordinat_longitude FROM desa ORDER BY nama_desa";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function updateCoordinates() {
        try {
            $data = $_POST;
            $desa_id = $data['id_desa'] ?? null;
            $latitude = $data['latitude'] ?? null;
            $longitude = $data['longitude'] ?? null;
            
            if (!$desa_id || !$latitude || !$longitude) {
                return $this->response(false, 'Desa ID, latitude, dan longitude wajib diisi');
            }
            
            if (!is_numeric($latitude) || !is_numeric($longitude)) {
                return $this->response(false, 'Format koordinat tidak valid');
            }
            
            $query = "UPDATE desa SET koordinat_latitude = ?, koordinat_longitude = ? WHERE id_desa = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$latitude, $longitude, $desa_id]);
            
            if ($result) {
                return $this->response(true, 'Koordinat berhasil diupdate');
            } else {
                return $this->response(false, 'Gagal mengupdate koordinat');
            }
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function getMapStatistics() {
        try {
            $stats = [];
            
            $query = "SELECT COUNT(*) as total FROM desa WHERE koordinat_latitude IS NOT NULL AND koordinat_longitude IS NOT NULL";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats['desa_with_coordinates'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $query = "SELECT COUNT(*) as total FROM desa WHERE koordinat_latitude IS NULL OR koordinat_longitude IS NULL";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats['desa_without_coordinates'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $total_desa = $stats['desa_with_coordinates'] + $stats['desa_without_coordinates'];
            $stats['coverage_percentage'] = $total_desa > 0 ? round(($stats['desa_with_coordinates'] / $total_desa) * 100, 1) : 0;
            
            try {
                $query = "SELECT COUNT(*) as total FROM administrative_boundaries";
                $stmt = $this->db->prepare($query);
                $stmt->execute();
                $stats['boundary_files'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            } catch (Exception $e) {
                $stats['boundary_files'] = 0;
            }
            
            return $this->response(true, 'Success', $stats);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function getFasilitasPendidikan() {
        try {
            $id_desa = $_GET['id_desa'] ?? null;
            
            if ($id_desa) {
                $query = "SELECT f.*, d.nama_desa 
                         FROM fasilitas_pendidikan f 
                         JOIN desa d ON f.id_desa = d.id_desa 
                         WHERE f.id_desa = ?
                         ORDER BY f.nama_fasilitas";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$id_desa]);
            } else {
                $fasilitas = $this->getAllFasilitas();
                return $this->response(true, 'Success', $fasilitas);
            }
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function getInfrastrukturJembatan() {
        try {
            $id_desa = $_GET['id_desa'] ?? null;
            
            if ($id_desa) {
                $query = "SELECT j.*, d.nama_desa 
                         FROM infrastruktur_jembatan j 
                         JOIN desa d ON j.id_desa = d.id_desa 
                         WHERE j.id_desa = ?
                         ORDER BY j.nama_jembatan";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$id_desa]);
            } else {
                $jembatan = $this->getAllJembatan();
                return $this->response(true, 'Success', $jembatan);
            }
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->response(true, 'Success', $result);
        } catch (Exception $e) {
            return $this->response(false, $e->getMessage());
        }
    }
    
    private function response($success, $message, $data = null, $code = 200) {
        http_response_code($code);
        return json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
    }
}
?>