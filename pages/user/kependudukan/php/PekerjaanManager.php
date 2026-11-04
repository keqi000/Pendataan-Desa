<?php
// Prevent any output before JSON
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once realpath(__DIR__ . '/../../../../config/config.php');
require_once realpath(__DIR__ . '/../../../../database/queries.php');

// Clear any unwanted output
ob_clean();

// Handle AJAX requests - hanya untuk pekerjaan management actions
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $pekerjaan_actions = ['get_all', 'get_by_id', 'get_pending_review', 'get_stats', 'get_mapping_stats', 'get_reviewed_mappings', 'debug_mapping'];
    if (in_array($_GET['action'], $pekerjaan_actions)) {
        try {
            $manager = new PekerjaanManager();
            header('Content-Type: application/json');

            switch ($_GET['action']) {
                case 'get_all':
                    $data = $manager->getAllPekerjaan();
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                case 'get_by_id':
                    $id = $_GET['id'] ?? 0;
                    $data = $manager->getPekerjaanById($id);
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                case 'get_pending_review':
                    $data = $manager->getPendingReview();
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                case 'get_stats':
                    $data = $manager->getStats();
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                case 'get_mapping_stats':
                    $data = $manager->getMappingStats();
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                case 'get_reviewed_mappings':
                    $data = $manager->getReviewedMappings();
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
                case 'debug_mapping':
                    $data = $manager->debugMapping();
                    echo json_encode(['success' => true, 'data' => $data]);
                    break;
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pekerjaan_actions = ['create_master', 'update_master', 'delete_master', 'approve_mapping', 'create_new_master', 'reject_mapping', 'scan_existing', 'normalize_existing', 'update_mapping', 'delete_mapping', 'reactivate_mapping', 'create_master_from_review'];
    $action = $_POST['action'] ?? '';

    if (in_array($action, $pekerjaan_actions)) {
        try {
            $manager = new PekerjaanManager();
            header('Content-Type: application/json');

            $admin_id = $_SESSION['user_id'] ?? 0;

            switch ($action) {
                case 'create_master':
                    try {
                        $standard_name = $_POST['standard_name'] ?? '';
                        $kategori = $_POST['kategori'] ?? 'Lainnya';

                        if (empty($standard_name)) {
                            echo json_encode(['success' => false, 'message' => 'Nama pekerjaan harus diisi']);
                            break;
                        }

                        $result = $manager->createMasterPekerjaan($standard_name, $kategori, $admin_id);
                        if ($result === true) {
                            echo json_encode(['success' => true, 'message' => 'Master pekerjaan berhasil dibuat']);
                        } else {
                            echo json_encode(['success' => false, 'message' => $result ?: 'Gagal membuat master pekerjaan']);
                        }
                    } catch (Exception $e) {
                        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
                    }
                    break;
                case 'update_master':
                    $id = $_POST['id'] ?? 0;
                    $standard_name = $_POST['standard_name'] ?? '';
                    $kategori = $_POST['kategori'] ?? 'Lainnya';
                    $status = $_POST['status'] ?? 'aktif';
                    $result = $manager->updateMasterPekerjaan($id, $standard_name, $kategori, $status, $admin_id);
                    echo json_encode(['success' => $result]);
                    break;
                case 'delete_master':
                    $id = $_POST['id'] ?? 0;
                    $result = $manager->deleteMasterPekerjaan($id, $admin_id);
                    echo json_encode(['success' => $result]);
                    break;
                case 'approve_mapping':
                    $id = $_POST['id'] ?? 0;
                    $standard_name = $_POST['standard_name'] ?? '';
                    $result = $manager->approveMapping($id, $standard_name, $admin_id);
                    echo json_encode(['success' => $result]);
                    break;
                case 'create_new_master':
                    $input_text = $_POST['input_text'] ?? '';
                    $standard_name = $_POST['standard_name'] ?? '';
                    $result = $manager->createNewMaster($input_text, $standard_name, $admin_id);
                    echo json_encode(['success' => $result]);
                    break;
                case 'reject_mapping':
                    $id = $_POST['id'] ?? 0;
                    $result = $manager->rejectMapping($id, $admin_id);
                    echo json_encode(['success' => $result]);
                    break;
                case 'normalize_case_variations':
                    $normalized = $manager->normalizeCaseVariations();
                    echo json_encode(['success' => true, 'normalized' => $normalized]);
                    break;
                case 'scan_existing':
                    $result = $manager->scanExistingPekerjaanData();
                    echo json_encode(['success' => true, 'result' => $result]);
                    break;
                case 'normalize_existing':
                    $processed = $manager->normalizeExistingData();
                    echo json_encode(['success' => true, 'processed' => $processed]);
                    break;
                case 'update_mapping':
                    $id = $_POST['id'] ?? 0;
                    $standard_name = $_POST['standard_name'] ?? '';
                    $kategori = $_POST['kategori'] ?? 'Lainnya';
                    $admin_id = $_SESSION['user_id'] ?? 0;
                    $result = $manager->updateMapping($id, $standard_name, $kategori, $admin_id);
                    echo json_encode(['success' => $result]);
                    break;
                case 'delete_mapping':
                    $id = $_POST['id'] ?? 0;
                    $admin_id = $_SESSION['user_id'] ?? 0;
                    $result = $manager->deleteMapping($id, $admin_id);
                    echo json_encode(['success' => $result]);
                    break;
                case 'reactivate_mapping':
                    $id = $_POST['id'] ?? 0;
                    $admin_id = $_SESSION['user_id'] ?? 0;
                    $result = $manager->reactivateMapping($id, $admin_id);
                    echo json_encode(['success' => $result]);
                    break;
                case 'create_master_from_review':
                    $id = $_POST['id'] ?? 0;
                    $input_text = $_POST['input_text'] ?? '';
                    $standard_name = $_POST['standard_name'] ?? '';
                    $kategori = $_POST['kategori'] ?? 'Lainnya';
                    $admin_id = $_SESSION['user_id'] ?? 0;

                    if (empty($input_text) || empty($standard_name)) {
                        echo json_encode(['success' => false, 'message' => 'Input text dan standard name harus diisi']);
                        break;
                    }

                    $result = $manager->createMasterFromReview($id, $input_text, $standard_name, $kategori, $admin_id);
                    if (is_array($result) && isset($result['success']) && $result['success']) {
                        echo json_encode(['success' => true, 'message' => 'Master berhasil dibuat', 'debug_info' => $result['debug_info']]);
                    } elseif ($result === true) {
                        echo json_encode(['success' => true, 'message' => 'Master berhasil dibuat']);
                    } else {
                        echo json_encode(['success' => false, 'message' => $result]);
                    }
                    break;
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit();
    }
}

class PekerjaanManager
{
    private $db;
    private $id_desa;

    public function __construct()
    {
        try {
            $queries = new Queries();
            $this->db = $queries->db;

            if (!$this->db) {
                throw new Exception("Database connection failed");
            }

            // Initialize session and get id_desa
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $this->id_desa = $_SESSION['user_desa'] ?? null;

            $this->initializeTable();
        } catch (Exception $e) {
            error_log("PekerjaanManager constructor error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Initialize pekerjaan_mapping table
     */
    private function initializeTable()
    {
        $query = "CREATE TABLE IF NOT EXISTS pekerjaan_mapping (
            id INT AUTO_INCREMENT PRIMARY KEY,
            input_text VARCHAR(255) NOT NULL COLLATE utf8mb4_bin,
            standard_name VARCHAR(255) NULL COLLATE utf8mb4_bin,
            kategori VARCHAR(100) DEFAULT 'Lainnya',
            status ENUM('approved', 'pending', 'rejected', 'aktif', 'nonaktif') DEFAULT 'pending',
            is_master TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            reviewed_by INT NULL,
            UNIQUE KEY unique_input (input_text),
            INDEX idx_status (status),
            INDEX idx_is_master (is_master)
        )";

        $this->db->exec($query);
    }



    /**
     * Normalize pekerjaan input with case-insensitive matching
     */
    public function normalizePekerjaan($input_pekerjaan)
    {
        $input_clean = trim($input_pekerjaan);

        if (empty($input_clean) || $input_clean === '-') {
            return 'Belum/Tidak Bekerja';
        }

        // Jangan normalize nilai yang sudah merupakan hasil normalisasi
        if ($input_clean === 'Menunggu Review' || $input_clean === 'Belum/Tidak Bekerja') {
            return $input_clean;
        }

        // Cek exact match - approved ATAU aktif (master)
        $query = "SELECT standard_name FROM pekerjaan_mapping 
                  WHERE input_text = :input 
                  AND status IN ('approved', 'aktif') 
                  AND standard_name IS NOT NULL";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':input' => $input_clean]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return $result['standard_name'];
        }

        // Cek case-insensitive match - approved ATAU aktif (master)
        $query = "SELECT standard_name FROM pekerjaan_mapping 
                  WHERE LOWER(input_text) = LOWER(:input) 
                  AND status IN ('approved', 'aktif') 
                  AND standard_name IS NOT NULL
                  LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':input' => $input_clean]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return $result['standard_name'];
        }

        // Belum ada mapping - simpan nilai ASLI ke database, masukkan ke review queue
        $this->addToReviewQueue($input_clean);
        return $input_clean; // Kembalikan nilai asli, BUKAN 'Menunggu Review'
    }

    /**
     * Add pekerjaan to review queue
     */
    private function addToReviewQueue($input_text)
    {
        $check = "SELECT id FROM pekerjaan_mapping WHERE input_text = :input";
        $stmt = $this->db->prepare($check);
        $stmt->execute([':input' => $input_text]);

        if (!$stmt->fetch()) {
            $insert = "INSERT INTO pekerjaan_mapping (input_text, status) VALUES (:input, 'pending')";
            $stmt = $this->db->prepare($insert);
            $stmt->execute([':input' => $input_text]);
        }
    }

    /**
     * Get pending pekerjaan for admin review from both tables
     */
    public function getPendingReview()
    {
        try {
            // Simplified query - just get pending records first
            $query = "SELECT * FROM pekerjaan_mapping WHERE status = 'pending' ORDER BY created_at ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $pending_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Add usage count for each record
            foreach ($pending_records as &$record) {
                $input_text = $record['input_text'];

                // Hitung berdasarkan input_text asli (nilai yang tersimpan di database)
                $query_p = "SELECT COUNT(*) as count FROM penduduk WHERE LOWER(pekerjaan) = LOWER(:input)";
                $stmt_p = $this->db->prepare($query_p);
                $stmt_p->execute([':input' => $input_text]);
                $penduduk_count = $stmt_p->fetchColumn();

                $query_mp = "SELECT COUNT(*) as count FROM mata_pencaharian WHERE LOWER(jenis_pekerjaan) = LOWER(:input)";
                $stmt_mp = $this->db->prepare($query_mp);
                $stmt_mp->execute([':input' => $input_text]);
                $mata_pencaharian_count = $stmt_mp->fetchColumn();

                $record['usage_count'] = $penduduk_count + $mata_pencaharian_count;

                // Determine source tables
                if ($penduduk_count > 0 && $mata_pencaharian_count > 0) {
                    $record['source_tables'] = 'both';
                } elseif ($penduduk_count > 0) {
                    $record['source_tables'] = 'penduduk';
                } elseif ($mata_pencaharian_count > 0) {
                    $record['source_tables'] = 'mata_pencaharian';
                } else {
                    $record['source_tables'] = 'none';
                }
            }

            // Sort by usage count descending
            usort($pending_records, function ($a, $b) {
                return $b['usage_count'] <=> $a['usage_count'];
            });

            return $pending_records;
        } catch (Exception $e) {
            error_log("getPendingReview error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get master pekerjaan list
     */
    public function getMasterList()
    {
        $query = "SELECT * FROM pekerjaan_mapping WHERE is_master = 1 AND status = 'aktif' ORDER BY standard_name";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Approve mapping by admin
     */
    public function approveMapping($id, $standard_name, $admin_id)
    {
        $get_input = "SELECT input_text FROM pekerjaan_mapping WHERE id = :id";
        $stmt = $this->db->prepare($get_input);
        $stmt->execute([':id' => $id]);
        $mapping_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$mapping_data) return false;
        
        $input_text = $mapping_data['input_text'];
        
        // Update data di penduduk/mata_pencaharian dari nilai asli ke standard_name
        $this->updatePendudukDataByText($input_text, $standard_name);
        // Juga update jika ada yang tersimpan sebagai 'Menunggu Review' (data lama)
        $this->updatePendudukDataByText('Menunggu Review', $standard_name);
        
        $query = "UPDATE pekerjaan_mapping 
                  SET standard_name = :standard, status = 'approved', reviewed_by = :admin, updated_at = NOW()
                  WHERE id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':standard' => $standard_name, ':admin' => $admin_id, ':id' => $id]);
    }

    /**
     * Create new master pekerjaan
     */
    public function createNewMaster($input_text, $standard_name, $admin_id)
    {
        // Insert master baru
        $insert_master = "INSERT INTO pekerjaan_mapping (input_text, standard_name, status, is_master, reviewed_by) 
                         VALUES (:input, :standard, 'approved', 1, :admin)";
        $stmt = $this->db->prepare($insert_master);
        $stmt->execute([
            ':input' => $standard_name,
            ':standard' => $standard_name,
            ':admin' => $admin_id
        ]);

        // Update pending item jadi mapping ke master baru
        $update_pending = "UPDATE pekerjaan_mapping 
                          SET standard_name = :standard, status = 'approved', reviewed_by = :admin, updated_at = NOW()
                          WHERE input_text = :input AND status = 'pending'";
        $stmt = $this->db->prepare($update_pending);
        $result = $stmt->execute([
            ':standard' => $standard_name,
            ':admin' => $admin_id,
            ':input' => $input_text
        ]);

        if ($result) {
            // Update data penduduk
            $this->updatePendudukDataByText($input_text, $standard_name);
        }

        return $result;
    }

    /**
     * Reject mapping
     */
    public function rejectMapping($id, $admin_id)
    {
        $query = "UPDATE pekerjaan_mapping 
                  SET status = 'rejected', reviewed_by = :admin, updated_at = NOW()
                  WHERE id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':admin' => $admin_id, ':id' => $id]);
    }

    /**
     * Update penduduk data after mapping approval
     */
    private function updatePendudukData($mapping_id)
    {
        // Get mapping info
        $get_mapping = "SELECT input_text, standard_name FROM pekerjaan_mapping WHERE id = :id";
        $stmt = $this->db->prepare($get_mapping);
        $stmt->execute([':id' => $mapping_id]);
        $mapping = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($mapping) {
            $this->updatePendudukDataByText($mapping['input_text'], $mapping['standard_name']);
        }
    }

    /**
     * Update penduduk data by text
     */
    private function updatePendudukDataByText($old_text, $new_text)
    {
        // Update tabel penduduk - gunakan case-insensitive untuk menangani variasi huruf
        $update_penduduk = "UPDATE penduduk SET pekerjaan = :new_text WHERE LOWER(pekerjaan) = LOWER(:old_text)";
        $stmt = $this->db->prepare($update_penduduk);
        $result1 = $stmt->execute([
            ':new_text' => $new_text,
            ':old_text' => $old_text
        ]);
        $affected_penduduk = $stmt->rowCount();

        // Update tabel mata_pencaharian - gunakan case-insensitive untuk menangani variasi huruf
        $update_mata_pencaharian = "UPDATE mata_pencaharian SET jenis_pekerjaan = :new_text WHERE LOWER(jenis_pekerjaan) = LOWER(:old_text)";
        $stmt = $this->db->prepare($update_mata_pencaharian);
        $result2 = $stmt->execute([
            ':new_text' => $new_text,
            ':old_text' => $old_text
        ]);
        $affected_mata_pencaharian = $stmt->rowCount();

        // Log untuk debugging
        error_log("updatePendudukDataByText: Updated '$old_text' to '$new_text' - Penduduk: $affected_penduduk, Mata Pencaharian: $affected_mata_pencaharian");
        
        return $result1 && $result2;
    }

    /**
     * Get pekerjaan statistics - both distribution and mapping
     */
    public function getStats()
    {
        try {
            $result = [
                'distribusi_pekerjaan' => $this->getDistribusiPekerjaan(),
                'distribusi_mapping' => $this->getDistribusiMapping()
            ];

            return $result;
        } catch (Exception $e) {
            error_log("getStats error: " . $e->getMessage());
            return [
                'distribusi_pekerjaan' => [],
                'distribusi_mapping' => []
            ];
        }
    }

    /**
     * Get distribusi pekerjaan dari kedua tabel
     */
    private function getDistribusiPekerjaan()
    {
        // Stats from penduduk table
        $query_penduduk = "SELECT pekerjaan, COUNT(*) as jumlah, 'penduduk' as source_table
                          FROM penduduk 
                          WHERE pekerjaan IS NOT NULL AND pekerjaan != ''";

        $params = [];
        // Jika ada id_desa (operator desa), filter berdasarkan desa
        if ($this->id_desa) {
            $query_penduduk .= " AND id_desa = ?";
            $params[] = $this->id_desa;
        }

        $query_penduduk .= " GROUP BY pekerjaan";

        $stmt = $this->db->prepare($query_penduduk);
        $stmt->execute($params);
        $penduduk_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Stats from mata_pencaharian table
        $query_mata_pencaharian = "SELECT jenis_pekerjaan as pekerjaan, COUNT(*) as jumlah, 'mata_pencaharian' as source_table
                                  FROM mata_pencaharian 
                                  WHERE jenis_pekerjaan IS NOT NULL AND jenis_pekerjaan != ''";

        $params_mp = [];
        if ($this->id_desa) {
            $query_mata_pencaharian .= " AND id_desa = ?";
            $params_mp[] = $this->id_desa;
        }

        $query_mata_pencaharian .= " GROUP BY jenis_pekerjaan";

        $stmt = $this->db->prepare($query_mata_pencaharian);
        $stmt->execute($params_mp);
        $mata_pencaharian_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Combine stats from both tables
        $combined_stats = [];
        $all_stats = array_merge($penduduk_stats, $mata_pencaharian_stats);

        foreach ($all_stats as $stat) {
            $pekerjaan = $stat['pekerjaan'];

            if (isset($combined_stats[$pekerjaan])) {
                $combined_stats[$pekerjaan]['jumlah'] += $stat['jumlah'];
                if (!in_array($stat['source_table'], $combined_stats[$pekerjaan]['source_tables'])) {
                    $combined_stats[$pekerjaan]['source_tables'][] = $stat['source_table'];
                }
            } else {
                $combined_stats[$pekerjaan] = [
                    'pekerjaan' => $pekerjaan,
                    'jumlah' => $stat['jumlah'],
                    'source_tables' => [$stat['source_table']]
                ];
            }
        }

        // Sort by jumlah descending
        uasort($combined_stats, function ($a, $b) {
            return $b['jumlah'] <=> $a['jumlah'];
        });

        return array_values($combined_stats);
    }

    /**
     * Get distribusi mapping dari tabel pekerjaan_mapping
     */
    private function getDistribusiMapping()
    {
        $query = "SELECT 
                    pm.input_text as pekerjaan,
                    pm.standard_name,
                    pm.kategori,
                    pm.status,
                    pm.is_master,
                    COALESCE(p_count.usage_count, 0) + COALESCE(mp_count.usage_count, 0) as usage_count,
                    CASE 
                        WHEN p_count.usage_count > 0 AND mp_count.usage_count > 0 THEN 'both'
                        WHEN p_count.usage_count > 0 THEN 'penduduk'
                        WHEN mp_count.usage_count > 0 THEN 'mata_pencaharian'
                        ELSE 'none'
                    END as source_tables,
                    pm.created_at,
                    pm.updated_at
                  FROM pekerjaan_mapping pm
                  LEFT JOIN (
                      SELECT pekerjaan, COUNT(*) as usage_count 
                      FROM penduduk 
                      WHERE pekerjaan IS NOT NULL AND pekerjaan != ''
                      GROUP BY pekerjaan
                  ) p_count ON p_count.pekerjaan = pm.input_text
                  LEFT JOIN (
                      SELECT jenis_pekerjaan, COUNT(*) as usage_count 
                      FROM mata_pencaharian 
                      WHERE jenis_pekerjaan IS NOT NULL AND jenis_pekerjaan != ''
                      GROUP BY jenis_pekerjaan
                  ) mp_count ON mp_count.jenis_pekerjaan = pm.input_text
                  ORDER BY pm.is_master DESC, pm.status ASC, usage_count DESC, pm.standard_name ASC";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get mapping statistics from both tables
     */
    public function getMappingStats()
    {
        try {
            $stats = [];

            // Total mapping
            $query = "SELECT COUNT(*) as total FROM pekerjaan_mapping";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats['total_mapping'] = $stmt->fetchColumn();

            // Approved mapping
            $query = "SELECT COUNT(*) as approved FROM pekerjaan_mapping WHERE status = 'approved'";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats['approved_mapping'] = $stmt->fetchColumn();

            // Pending mapping
            $query = "SELECT COUNT(*) as pending FROM pekerjaan_mapping WHERE status = 'pending'";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats['pending_mapping'] = $stmt->fetchColumn();

            // Master pekerjaan
            $query = "SELECT COUNT(*) as master FROM pekerjaan_mapping WHERE is_master = 1 AND status = 'aktif'";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats['master_pekerjaan'] = $stmt->fetchColumn();

            // Unique pekerjaan in penduduk - filter berdasarkan role
            $query_penduduk = "SELECT COUNT(DISTINCT pekerjaan) as unique_pekerjaan FROM penduduk WHERE pekerjaan IS NOT NULL AND pekerjaan != ''";
            $params = [];

            // Jika ada id_desa (operator desa), filter berdasarkan desa
            if ($this->id_desa) {
                $query_penduduk .= " AND id_desa = ?";
                $params[] = $this->id_desa;
            }

            $stmt = $this->db->prepare($query_penduduk);
            $stmt->execute($params);
            $stats['unique_pekerjaan_penduduk'] = $stmt->fetchColumn();

            // Unique jenis_pekerjaan in mata_pencaharian - filter berdasarkan role
            $query_mata_pencaharian = "SELECT COUNT(DISTINCT jenis_pekerjaan) as unique_jenis_pekerjaan FROM mata_pencaharian WHERE jenis_pekerjaan IS NOT NULL AND jenis_pekerjaan != ''";
            $params_mp = [];

            if ($this->id_desa) {
                $query_mata_pencaharian .= " AND id_desa = ?";
                $params_mp[] = $this->id_desa;
            }

            $stmt = $this->db->prepare($query_mata_pencaharian);
            $stmt->execute($params_mp);
            $stats['unique_jenis_pekerjaan_mata_pencaharian'] = $stmt->fetchColumn();

            // Combined unique count
            $query_combined = "SELECT COUNT(DISTINCT combined_pekerjaan) as total_unique
                              FROM (
                                  SELECT pekerjaan as combined_pekerjaan FROM penduduk WHERE pekerjaan IS NOT NULL AND pekerjaan != ''
                                  UNION
                                  SELECT jenis_pekerjaan as combined_pekerjaan FROM mata_pencaharian WHERE jenis_pekerjaan IS NOT NULL AND jenis_pekerjaan != ''
                              ) as combined_table";

            $stmt = $this->db->prepare($query_combined);
            $stmt->execute();
            $stats['total_unique_pekerjaan'] = $stmt->fetchColumn();

            return $stats;
        } catch (Exception $e) {
            error_log("getMappingStats error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Scan existing pekerjaan data from both penduduk and mata_pencaharian tables
     * with case sensitivity detection
     */
    public function scanExistingPekerjaanData()
    {
        // Get all unique pekerjaan from both tables in one query
        $query = "SELECT 
                    combined_pekerjaan as nama_pekerjaan,
                    SUM(usage_count) as total_usage_count,
                    GROUP_CONCAT(DISTINCT source_table) as source_tables
                  FROM (
                      SELECT pekerjaan as combined_pekerjaan, COUNT(*) as usage_count, 'penduduk' as source_table
                      FROM penduduk 
                      WHERE pekerjaan IS NOT NULL AND pekerjaan != '' AND pekerjaan != '-'
                      GROUP BY pekerjaan
                      
                      UNION ALL
                      
                      SELECT jenis_pekerjaan as combined_pekerjaan, COUNT(*) as usage_count, 'mata_pencaharian' as source_table
                      FROM mata_pencaharian 
                      WHERE jenis_pekerjaan IS NOT NULL AND jenis_pekerjaan != '' AND jenis_pekerjaan != '-'
                      GROUP BY jenis_pekerjaan
                  ) as combined_data
                  GROUP BY combined_pekerjaan
                  ORDER BY total_usage_count DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $all_pekerjaan = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $processed = 0;
        $already_mapped = 0;
        $case_sensitive_detected = 0;
        $case_sensitive_issues = [];

        foreach ($all_pekerjaan as $item) {
            $pekerjaan = trim($item['nama_pekerjaan']);
            $usage_count = $item['total_usage_count'];
            $source_tables = explode(',', $item['source_tables']);

            if (empty($pekerjaan)) {
                continue;
            }

            // Check if already exists in mapping (exact match)
            $check_exact = "SELECT id, status, standard_name FROM pekerjaan_mapping WHERE input_text = :input";
            $stmt = $this->db->prepare($check_exact);
            $stmt->execute([':input' => $pekerjaan]);
            $exact_match = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($exact_match) {
                $already_mapped++;
            } else {
                // Check for case-insensitive match
                $check_case_insensitive = "SELECT id, input_text, status, standard_name 
                                          FROM pekerjaan_mapping 
                                          WHERE LOWER(input_text) = LOWER(:input) 
                                          AND input_text != :input";
                $stmt = $this->db->prepare($check_case_insensitive);
                $stmt->execute([':input' => $pekerjaan]);
                $case_match = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($case_match) {
                    // Case sensitivity issue detected
                    $case_sensitive_detected++;
                    $case_sensitive_issues[] = [
                        'found_in_data' => $pekerjaan,
                        'existing_mapping' => $case_match['input_text'],
                        'standard_name' => $case_match['standard_name'],
                        'status' => $case_match['status'],
                        'usage_count' => $usage_count,
                        'source_tables' => $source_tables
                    ];

                    // Don't add to mapping, will be handled by case sensitivity fix
                } else {
                    // Add to mapping as pending review
                    $insert = "INSERT INTO pekerjaan_mapping (input_text, status, created_at) 
                              VALUES (:input, 'pending', NOW())";
                    $stmt = $this->db->prepare($insert);
                    $stmt->execute([':input' => $pekerjaan]);
                    $processed++;
                }
            }
        }

        // Get source counts for reporting
        $query_penduduk_count = "SELECT COUNT(DISTINCT pekerjaan) as count FROM penduduk WHERE pekerjaan IS NOT NULL AND pekerjaan != '' AND pekerjaan != '-'";
        $stmt = $this->db->prepare($query_penduduk_count);
        $stmt->execute();
        $penduduk_count = $stmt->fetchColumn();

        $query_mata_pencaharian_count = "SELECT COUNT(DISTINCT jenis_pekerjaan) as count FROM mata_pencaharian WHERE jenis_pekerjaan IS NOT NULL AND jenis_pekerjaan != '' AND jenis_pekerjaan != '-'";
        $stmt = $this->db->prepare($query_mata_pencaharian_count);
        $stmt->execute();
        $mata_pencaharian_count = $stmt->fetchColumn();

        return [
            'total_found' => count($all_pekerjaan),
            'processed' => $processed,
            'already_mapped' => $already_mapped,
            'case_sensitive_detected' => $case_sensitive_detected,
            'case_sensitive_issues' => $case_sensitive_issues,
            'data' => $all_pekerjaan,
            'sources' => [
                'penduduk_count' => $penduduk_count,
                'mata_pencaharian_count' => $mata_pencaharian_count
            ]
        ];
    }

    /**
     * Normalize existing data (after mapping is done) - both tables
     */
    public function normalizeExistingData()
    {
        // Normalize penduduk table
        $query_penduduk = "SELECT DISTINCT pekerjaan FROM penduduk WHERE pekerjaan IS NOT NULL AND pekerjaan != ''";
        $stmt = $this->db->prepare($query_penduduk);
        $stmt->execute();
        $existing_penduduk = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Normalize mata_pencaharian table
        $query_mata_pencaharian = "SELECT DISTINCT jenis_pekerjaan FROM mata_pencaharian WHERE jenis_pekerjaan IS NOT NULL AND jenis_pekerjaan != ''";
        $stmt = $this->db->prepare($query_mata_pencaharian);
        $stmt->execute();
        $existing_mata_pencaharian = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $processed = 0;

        // Process penduduk data
        foreach ($existing_penduduk as $pekerjaan) {
            $normalized = $this->normalizePekerjaan($pekerjaan);
            if ($normalized !== 'Menunggu Review' && $normalized !== $pekerjaan) {
                // Update penduduk table
                $update = "UPDATE penduduk SET pekerjaan = :new_text WHERE pekerjaan = :old_text";
                $stmt = $this->db->prepare($update);
                $stmt->execute([
                    ':new_text' => $normalized,
                    ':old_text' => $pekerjaan
                ]);
                $processed++;
            }
        }

        // Process mata_pencaharian data
        foreach ($existing_mata_pencaharian as $jenis_pekerjaan) {
            $normalized = $this->normalizePekerjaan($jenis_pekerjaan);
            if ($normalized !== 'Menunggu Review' && $normalized !== $jenis_pekerjaan) {
                // Update mata_pencaharian table
                $update = "UPDATE mata_pencaharian SET jenis_pekerjaan = :new_text WHERE jenis_pekerjaan = :old_text";
                $stmt = $this->db->prepare($update);
                $stmt->execute([
                    ':new_text' => $normalized,
                    ':old_text' => $jenis_pekerjaan
                ]);
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * Get all pekerjaan for management
     */
    public function getAllPekerjaan()
    {
        try {
            $query = "SELECT * FROM pekerjaan_mapping WHERE is_master = 1 AND status = 'aktif' ORDER BY standard_name";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $master_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Add usage count and debug info for each master record
            foreach ($master_records as &$record) {
                $standard_name = $record['standard_name'];

                // Debug: Show actual data in database for this master
                $debug_p = "SELECT DISTINCT pekerjaan FROM penduduk WHERE LOWER(TRIM(pekerjaan)) = LOWER(TRIM(:input)) LIMIT 10";
                $stmt_debug = $this->db->prepare($debug_p);
                $stmt_debug->execute([':input' => $standard_name]);
                $sample_penduduk = $stmt_debug->fetchAll(PDO::FETCH_COLUMN);
                
                $debug_mp = "SELECT DISTINCT jenis_pekerjaan FROM mata_pencaharian WHERE LOWER(TRIM(jenis_pekerjaan)) = LOWER(TRIM(:input)) LIMIT 10";
                $stmt_debug = $this->db->prepare($debug_mp);
                $stmt_debug->execute([':input' => $standard_name]);
                $sample_mata_pencaharian = $stmt_debug->fetchAll(PDO::FETCH_COLUMN);

                // Count usage in penduduk table - gunakan exact match untuk master
                $query_p = "SELECT COUNT(*) as count FROM penduduk WHERE pekerjaan = :input";
                $stmt_p = $this->db->prepare($query_p);
                $stmt_p->execute([':input' => $standard_name]);
                $penduduk_count = $stmt_p->fetchColumn();

                // Count usage in mata_pencaharian table - gunakan exact match untuk master
                $query_mp = "SELECT COUNT(*) as count FROM mata_pencaharian WHERE jenis_pekerjaan = :input";
                $stmt_mp = $this->db->prepare($query_mp);
                $stmt_mp->execute([':input' => $standard_name]);
                $mata_pencaharian_count = $stmt_mp->fetchColumn();
                
                // Debug: Also check case-insensitive count to see the difference
                $query_p_ci = "SELECT COUNT(*) as count FROM penduduk WHERE LOWER(TRIM(pekerjaan)) = LOWER(TRIM(:input))";
                $stmt_p_ci = $this->db->prepare($query_p_ci);
                $stmt_p_ci->execute([':input' => $standard_name]);
                $penduduk_count_ci = $stmt_p_ci->fetchColumn();
                
                $query_mp_ci = "SELECT COUNT(*) as count FROM mata_pencaharian WHERE LOWER(TRIM(jenis_pekerjaan)) = LOWER(TRIM(:input))";
                $stmt_mp_ci = $this->db->prepare($query_mp_ci);
                $stmt_mp_ci->execute([':input' => $standard_name]);
                $mata_pencaharian_count_ci = $stmt_mp_ci->fetchColumn();

                $record['usage_count'] = $penduduk_count + $mata_pencaharian_count;

                // Determine source tables
                if ($penduduk_count > 0 && $mata_pencaharian_count > 0) {
                    $record['source_tables'] = 'both';
                } elseif ($penduduk_count > 0) {
                    $record['source_tables'] = 'penduduk';
                } elseif ($mata_pencaharian_count > 0) {
                    $record['source_tables'] = 'mata_pencaharian';
                } else {
                    $record['source_tables'] = 'none';
                }
                
                // Add debug info to record
                $record['debug_info'] = [
                    'penduduk_count' => $penduduk_count,
                    'mata_pencaharian_count' => $mata_pencaharian_count,
                    'penduduk_count_case_insensitive' => $penduduk_count_ci,
                    'mata_pencaharian_count_case_insensitive' => $mata_pencaharian_count_ci,
                    'sample_penduduk' => $sample_penduduk,
                    'sample_mata_pencaharian' => $sample_mata_pencaharian
                ];
            }

            return $master_records;
        } catch (Exception $e) {
            error_log("getAllPekerjaan error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get pekerjaan by ID
     */
    public function getPekerjaanById($id)
    {
        $query = "SELECT * FROM pekerjaan_mapping WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create master pekerjaan
     */
    public function createMasterPekerjaan($standard_name, $kategori, $admin_id)
    {
        try {
            // Check for exact duplicate only (case-sensitive)
            $check_duplicate = "SELECT id, input_text FROM pekerjaan_mapping WHERE input_text = :input";
            $stmt = $this->db->prepare($check_duplicate);
            $stmt->execute([':input' => $standard_name]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                return "Pekerjaan dengan nama persis sama sudah ada: '{$existing['input_text']}'";
            }

            $query = "INSERT INTO pekerjaan_mapping (input_text, standard_name, kategori, status, is_master, reviewed_by) 
                      VALUES (:input, :standard, :kategori, 'aktif', 1, :admin)";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                ':input' => $standard_name,
                ':standard' => $standard_name,
                ':kategori' => $kategori,
                ':admin' => $admin_id
            ]);

            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                return "Database error: " . $errorInfo[2];
            }

            return true;
        } catch (Exception $e) {
            error_log("createMasterPekerjaan error: " . $e->getMessage());
            return "Exception: " . $e->getMessage();
        }
    }

    /**
     * Update master pekerjaan
     */
    public function updateMasterPekerjaan($id, $standard_name, $kategori, $status, $admin_id)
    {
        $query = "UPDATE pekerjaan_mapping 
                  SET input_text = :input, standard_name = :standard, kategori = :kategori, status = :status, reviewed_by = :admin, updated_at = NOW()
                  WHERE id = :id AND is_master = 1";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':input' => $standard_name,
            ':standard' => $standard_name,
            ':kategori' => $kategori,
            ':status' => $status,
            ':admin' => $admin_id,
            ':id' => $id
        ]);
    }

    /**
     * Delete master pekerjaan
     */
    public function deleteMasterPekerjaan($id, $admin_id)
    {
        // Check if pekerjaan is being used
        $check = "SELECT COUNT(*) as count FROM penduduk p 
                  JOIN pekerjaan_mapping pm ON p.pekerjaan = pm.standard_name 
                  WHERE pm.id = :id";
        $stmt = $this->db->prepare($check);
        $stmt->execute([':id' => $id]);
        $usage = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usage['count'] > 0) {
            return false; // Cannot delete if still in use
        }

        $query = "DELETE FROM pekerjaan_mapping WHERE id = :id AND is_master = 1";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':id' => $id]);
    }


    /**
     * Get reviewed mappings (approved and rejected)
     */
    public function getReviewedMappings()
    {
        try {
            // Simplified query - get reviewed records first
            $query = "SELECT * FROM pekerjaan_mapping WHERE status IN ('approved', 'rejected', 'aktif') ORDER BY status ASC, is_master DESC, updated_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $reviewed_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("=== GET REVIEWED MAPPINGS ===");
            
            // Add usage count for each record
            foreach ($reviewed_records as &$record) {
                // Untuk master, gunakan standard_name dengan exact match
                // Untuk non-master yang approved, gunakan input_text dengan exact match
                if ($record['is_master'] == 1) {
                    $search_text = $record['standard_name'];
                    $record_type = 'MASTER';
                } else {
                    $search_text = $record['input_text'];
                    $record_type = 'NON-MASTER';
                }

                // Count usage in penduduk table - gunakan exact match
                $query_p = "SELECT COUNT(*) as count FROM penduduk WHERE pekerjaan = :input";
                $stmt_p = $this->db->prepare($query_p);
                $stmt_p->execute([':input' => $search_text]);
                $penduduk_count = $stmt_p->fetchColumn();

                // Count usage in mata_pencaharian table - gunakan exact match
                $query_mp = "SELECT COUNT(*) as count FROM mata_pencaharian WHERE jenis_pekerjaan = :input";
                $stmt_mp = $this->db->prepare($query_mp);
                $stmt_mp->execute([':input' => $search_text]);
                $mata_pencaharian_count = $stmt_mp->fetchColumn();

                $record['usage_count'] = $penduduk_count + $mata_pencaharian_count;

                // Determine source tables
                if ($penduduk_count > 0 && $mata_pencaharian_count > 0) {
                    $record['source_tables'] = 'both';
                } elseif ($penduduk_count > 0) {
                    $record['source_tables'] = 'penduduk';
                } elseif ($mata_pencaharian_count > 0) {
                    $record['source_tables'] = 'mata_pencaharian';
                } else {
                    $record['source_tables'] = 'none';
                }
                
                error_log("$record_type: '{$record['input_text']}' -> '{$record['standard_name']}' ({$record['status']}) - Usage: {$record['usage_count']} (P:{$penduduk_count}, MP:{$mata_pencaharian_count}) - Source: {$record['source_tables']} - Search: '$search_text'");
            }
            
            error_log("=== END GET REVIEWED MAPPINGS ===");

            // Sort by usage count descending within same status
            usort($reviewed_records, function ($a, $b) {
                // First sort by is_master (masters first)
                if ($a['is_master'] != $b['is_master']) {
                    return $b['is_master'] <=> $a['is_master'];
                }
                // Then by status (aktif, approved, rejected)
                $status_order = ['aktif' => 1, 'approved' => 2, 'rejected' => 3];
                if ($a['status'] != $b['status']) {
                    return ($status_order[$a['status']] ?? 4) <=> ($status_order[$b['status']] ?? 4);
                }
                // Finally by usage count
                return $b['usage_count'] <=> $a['usage_count'];
            });

            return $reviewed_records;
        } catch (Exception $e) {
            error_log("getReviewedMappings error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update mapping
     */
    public function updateMapping($id, $standard_name, $kategori, $admin_id)
    {
        try {
            $query = "UPDATE pekerjaan_mapping 
                      SET standard_name = :standard, kategori = :kategori, reviewed_by = :admin, updated_at = NOW()
                      WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                ':standard' => $standard_name,
                ':kategori' => $kategori,
                ':admin' => $admin_id,
                ':id' => $id
            ]);

            if ($result) {
                // Update data in both tables
                $this->updatePendudukData($id);
            }

            return $result;
        } catch (Exception $e) {
            error_log("updateMapping error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete mapping
     */
    public function deleteMapping($id, $admin_id)
    {
        try {
            $query = "DELETE FROM pekerjaan_mapping WHERE id = :id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([':id' => $id]);
        } catch (Exception $e) {
            error_log("deleteMapping error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reactivate mapping for review
     */
    public function reactivateMapping($id, $admin_id)
    {
        try {
            $query = "UPDATE pekerjaan_mapping 
                      SET status = 'pending', standard_name = NULL, reviewed_by = :admin, updated_at = NOW()
                      WHERE id = :id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([':admin' => $admin_id, ':id' => $id]);
        } catch (Exception $e) {
            error_log("reactivateMapping error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create master pekerjaan from review item
     */
    public function createMasterFromReview($id, $input_text, $standard_name, $kategori, $admin_id)
    {
        try {
            $debug_info = [];
            $debug_info['process'] = 'CREATE_MASTER_FROM_REVIEW';
            $debug_info['input'] = [
                'id' => $id,
                'input_text' => $input_text,
                'standard_name' => $standard_name,
                'kategori' => $kategori
            ];
            
            // Log usage count sebelum proses
            $query_p_before = "SELECT COUNT(*) as count FROM penduduk WHERE LOWER(pekerjaan) = LOWER(:input)";
            $stmt_p = $this->db->prepare($query_p_before);
            $stmt_p->execute([':input' => $input_text]);
            $penduduk_before = $stmt_p->fetchColumn();
            
            $query_mp_before = "SELECT COUNT(*) as count FROM mata_pencaharian WHERE LOWER(jenis_pekerjaan) = LOWER(:input)";
            $stmt_mp = $this->db->prepare($query_mp_before);
            $stmt_mp->execute([':input' => $input_text]);
            $mata_pencaharian_before = $stmt_mp->fetchColumn();
            
            $debug_info['before_update'] = [
                'penduduk_count_case_insensitive' => $penduduk_before,
                'mata_pencaharian_count_case_insensitive' => $mata_pencaharian_before
            ];
            
            // Check exact match before
            $query_p_exact = "SELECT COUNT(*) as count FROM penduduk WHERE pekerjaan = :input";
            $stmt_p = $this->db->prepare($query_p_exact);
            $stmt_p->execute([':input' => $input_text]);
            $penduduk_exact_before = $stmt_p->fetchColumn();
            
            $query_mp_exact = "SELECT COUNT(*) as count FROM mata_pencaharian WHERE jenis_pekerjaan = :input";
            $stmt_mp = $this->db->prepare($query_mp_exact);
            $stmt_mp->execute([':input' => $input_text]);
            $mata_pencaharian_exact_before = $stmt_mp->fetchColumn();
            
            $debug_info['before_update']['penduduk_count_exact'] = $penduduk_exact_before;
            $debug_info['before_update']['mata_pencaharian_count_exact'] = $mata_pencaharian_exact_before;
            
            // Check if master with exact same name already exists (case-sensitive)
            $check_master = "SELECT id FROM pekerjaan_mapping WHERE standard_name = :standard AND is_master = 1";
            $stmt = $this->db->prepare($check_master);
            $stmt->execute([':standard' => $standard_name]);
            if ($stmt->fetch()) {
                return "Master dengan nama persis sama '$standard_name' sudah ada";
            }

            // First, update all data in both tables from old input_text to new standard_name using case-insensitive matching
            $this->updatePendudukDataByText($input_text, $standard_name);
            
            // Log usage count setelah update data
            $stmt_p->execute([':input' => $input_text]);
            $penduduk_after_old_exact = $stmt_p->fetchColumn();
            
            $stmt_mp->execute([':input' => $input_text]);
            $mata_pencaharian_after_old_exact = $stmt_mp->fetchColumn();
            
            $stmt_p->execute([':input' => $standard_name]);
            $penduduk_after_new_exact = $stmt_p->fetchColumn();
            
            $stmt_mp->execute([':input' => $standard_name]);
            $mata_pencaharian_after_new_exact = $stmt_mp->fetchColumn();
            
            $debug_info['after_update'] = [
                'old_input_penduduk_exact' => $penduduk_after_old_exact,
                'old_input_mata_pencaharian_exact' => $mata_pencaharian_after_old_exact,
                'new_standard_penduduk_exact' => $penduduk_after_new_exact,
                'new_standard_mata_pencaharian_exact' => $mata_pencaharian_after_new_exact
            ];

            // Then update the review item to become master
            $update_to_master = "UPDATE pekerjaan_mapping 
                                SET input_text = :input, standard_name = :standard, kategori = :kategori, status = 'aktif', is_master = 1, reviewed_by = :admin, updated_at = NOW()
                                WHERE id = :id";
            $stmt = $this->db->prepare($update_to_master);
            $result = $stmt->execute([
                ':input' => $standard_name,
                ':standard' => $standard_name,
                ':kategori' => $kategori,
                ':admin' => $admin_id,
                ':id' => $id
            ]);

            if (!$result) {
                return "Gagal mengupdate item menjadi master";
            }
            
            $debug_info['success'] = true;
            
            // Return debug info along with success
            return ['success' => true, 'debug_info' => $debug_info];
        } catch (Exception $e) {
            error_log("createMasterFromReview error: " . $e->getMessage());
            return "Database error: " . $e->getMessage();
        }
    }

    /**
     * Normalize case variations for all masters
     */
    public function normalizeCaseVariations()
    {
        try {
            $normalized_count = 0;
            
            // Get all masters
            $query = "SELECT standard_name FROM pekerjaan_mapping WHERE is_master = 1 AND status = 'aktif'";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $masters = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($masters as $master_name) {
                // Normalize penduduk table
                $update_penduduk = "UPDATE penduduk SET pekerjaan = :master 
                                   WHERE LOWER(TRIM(pekerjaan)) = LOWER(TRIM(:master)) 
                                   AND pekerjaan != :master";
                $stmt = $this->db->prepare($update_penduduk);
                $stmt->execute([':master' => $master_name]);
                $normalized_count += $stmt->rowCount();
                
                // Normalize mata_pencaharian table
                $update_mp = "UPDATE mata_pencaharian SET jenis_pekerjaan = :master 
                             WHERE LOWER(TRIM(jenis_pekerjaan)) = LOWER(TRIM(:master)) 
                             AND jenis_pekerjaan != :master";
                $stmt = $this->db->prepare($update_mp);
                $stmt->execute([':master' => $master_name]);
                $normalized_count += $stmt->rowCount();
            }
            
            return $normalized_count;
        } catch (Exception $e) {
            error_log("normalizeCaseVariations error: " . $e->getMessage());
            return 0;
        }
    }
    public function debugMapping()
    {
        try {
            $query = "SELECT id, input_text, standard_name, kategori, status, is_master, created_at FROM pekerjaan_mapping ORDER BY created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'total_records' => count($result),
                'pending_count' => count(array_filter($result, function ($item) {
                    return $item['status'] === 'pending'; })),
                'approved_count' => count(array_filter($result, function ($item) {
                    return $item['status'] === 'approved'; })),
                'master_count' => count(array_filter($result, function ($item) {
                    return $item['is_master'] == 1; })),
                'all_records' => $result
            ];
        } catch (Exception $e) {
            error_log("debugMapping error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
?>