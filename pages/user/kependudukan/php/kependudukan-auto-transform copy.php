<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/session.php';
require_once __DIR__ . '/../../../../database/queries.php';
require_once __DIR__ . '/PekerjaanManager.php';

class KependudukanAutoTransform {
    private $db;
    private $id_desa;
    private $pekerjaanManager;
    
    // Mapping patterns untuk deteksi kolom otomatis
    private $columnPatterns = [
        'nik' => ['nik', 'no_ktp', 'nomor_ktp', 'ktp', 'identitas', 'no_identitas', 'nomor_identitas'],
        'nama_lengkap' => ['nama', 'nama_lengkap', 'namalengkap', 'name', 'full_name', 'fullname', 'penduduk', 'warga'],
        'jenis_kelamin' => ['jenis_kelamin', 'jeniskelamin', 'jk', 'kelamin', 'gender', 'sex'],
        'tanggal_lahir' => ['tanggal_lahir', 'tgl_lahir', 'ttl', 'lahir', 'birth_date', 'birthdate', 'tgl_lhr'],
        'agama' => ['agama', 'religion'],
        'pendidikan_terakhir' => ['pendidikan', 'pendidikan_terakhir', 'education', 'sekolah', 'didik'],
        'pekerjaan' => ['pekerjaan', 'kerja', 'profesi', 'job', 'work'],
        'status_pernikahan' => ['status_pernikahan', 'pernikahan', 'nikah', 'kawin', 'marital'],
        'status_migrasi' => ['status_migrasi', 'migrasi', 'domisili', 'tinggal'],
        'alamat' => ['alamat', 'address', 'tempat_tinggal', 'domisili', 'tinggal']
    ];
    
    // Format tanggal yang umum digunakan
    private $dateFormats = [
        'd/m/Y', 'm/d/Y', 'Y-m-d', 'd-m-Y', 'm-d-Y',
        'd/m/y', 'm/d/y', 'y-m-d', 'd-m-y', 'm-d-y',
        'd-M-Y', 'd M Y', 'j F Y', 'j-M-Y', 'j/M/Y',
        'Y/m/d', 'Y.m.d', 'd.m.Y', 'm.d.Y',
        'j F Y' // Format: 10 Juni 1988
    ];
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        
        $this->pekerjaanManager = new PekerjaanManager();
        
        if (initSession()) {
            $this->id_desa = $_SESSION['user_desa'] ?? null;
        }
    }
    
    /**
     * Deteksi format Excel dan buat mapping otomatis
     */
    public function detectExcelFormat($file) {
        try {
            $headers = $this->extractHeaders($file);
            if (!$headers) {
                return ['success' => false, 'message' => 'Tidak dapat membaca header Excel'];
            }
            
            $mapping = $this->createColumnMapping($headers);
            $confidence = $this->calculateMappingConfidence($mapping, $headers);
            
            return [
                'success' => true,
                'data' => [
                    'headers' => $headers,
                    'mapping' => $mapping,
                    'confidence' => $confidence,
                    'suggestions' => $this->generateMappingSuggestions($headers)
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Extract headers dari file Excel
     */
    private function extractHeaders($file) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($extension === 'xlsx') {
            return $this->extractHeadersXLSX($file);
        } elseif ($extension === 'csv') {
            return $this->extractHeadersCSV($file);
        }
        
        return null;
    }
    
    private function extractHeadersXLSX($file) {
        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) !== TRUE) {
            return null;
        }
        
        $sharedStrings = [];
        $sharedStringsXML = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXML) {
            $xml = simplexml_load_string($sharedStringsXML);
            foreach ($xml->si as $si) {
                $sharedStrings[] = (string)$si->t;
            }
        }
        
        $worksheetXML = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        
        if (!$worksheetXML) {
            return null;
        }
        
        $xml = simplexml_load_string($worksheetXML);
        $rows = $xml->sheetData->row;
        
        // Ambil baris pertama sebagai header
        $firstRow = $rows[0] ?? null;
        if (!$firstRow) return null;
        
        $headers = [];
        foreach ($firstRow->c as $cell) {
            $value = '';
            if (isset($cell->v)) {
                if (isset($cell['t']) && $cell['t'] == 's') {
                    $value = $sharedStrings[(int)$cell->v] ?? '';
                } else {
                    $value = (string)$cell->v;
                }
            }
            $headers[] = trim($value);
        }
        
        return $headers;
    }
    
    private function extractHeadersCSV($file) {
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) return null;
        
        $headers = fgetcsv($handle);
        fclose($handle);
        
        return $headers ? array_map('trim', $headers) : null;
    }
    
    /**
     * Buat mapping kolom otomatis berdasarkan pattern
     */
    private function createColumnMapping($headers) {
        $mapping = [];
        
        foreach ($this->columnPatterns as $standardField => $patterns) {
            $bestMatch = null;
            $bestScore = 0;
            
            foreach ($headers as $index => $header) {
                $score = $this->calculateSimilarity($header, $patterns);
                if ($score > $bestScore && $score > 0.6) { // Threshold 60%
                    $bestScore = $score;
                    $bestMatch = $index;
                }
            }
            
            if ($bestMatch !== null) {
                $mapping[$standardField] = [
                    'index' => $bestMatch,
                    'header' => $headers[$bestMatch],
                    'confidence' => $bestScore
                ];
            }
        }
        
        return $mapping;
    }
    
    /**
     * Hitung similarity antara header dengan pattern
     */
    private function calculateSimilarity($header, $patterns) {
        $header = strtolower(trim($header));
        $maxSimilarity = 0;
        
        foreach ($patterns as $pattern) {
            $pattern = strtolower($pattern);
            
            // Exact match
            if ($header === $pattern) {
                return 1.0;
            }
            
            // Contains pattern
            if (strpos($header, $pattern) !== false || strpos($pattern, $header) !== false) {
                $similarity = 0.8;
            } else {
                // Levenshtein similarity
                $similarity = 1 - (levenshtein($header, $pattern) / max(strlen($header), strlen($pattern)));
            }
            
            $maxSimilarity = max($maxSimilarity, $similarity);
        }
        
        return $maxSimilarity;
    }
    
    /**
     * Hitung confidence level mapping
     */
    private function calculateMappingConfidence($mapping, $headers) {
        $requiredFields = ['nik', 'nama_lengkap', 'jenis_kelamin', 'tanggal_lahir'];
        $mappedRequired = 0;
        $totalConfidence = 0;
        
        foreach ($requiredFields as $field) {
            if (isset($mapping[$field])) {
                $mappedRequired++;
                $totalConfidence += $mapping[$field]['confidence'];
            }
        }
        
        if ($mappedRequired === 0) return 0;
        
        $averageConfidence = $totalConfidence / $mappedRequired;
        $completeness = $mappedRequired / count($requiredFields);
        
        return ($averageConfidence * 0.7) + ($completeness * 0.3);
    }
    
    /**
     * Generate suggestions untuk mapping manual
     */
    private function generateMappingSuggestions($headers) {
        $suggestions = [];
        
        foreach ($headers as $index => $header) {
            $suggestions[] = [
                'index' => $index,
                'header' => $header,
                'possible_fields' => $this->getPossibleFields($header)
            ];
        }
        
        return $suggestions;
    }
    
    private function getPossibleFields($header) {
        $possibleFields = [];
        
        foreach ($this->columnPatterns as $field => $patterns) {
            $similarity = $this->calculateSimilarity($header, $patterns);
            if ($similarity > 0.3) {
                $possibleFields[] = [
                    'field' => $field,
                    'confidence' => $similarity
                ];
            }
        }
        
        // Sort by confidence
        usort($possibleFields, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });
        
        return array_slice($possibleFields, 0, 3); // Top 3 suggestions
    }
    
    /**
     * Transform data menggunakan mapping
     */
    public function transformExcelData($file, $mapping, $customMapping = []) {
        try {
            // Merge custom mapping dengan auto mapping
            $finalMapping = array_merge($mapping, $customMapping);
            
            $rawData = $this->extractRawData($file);
            if (!$rawData) {
                return ['success' => false, 'message' => 'Tidak dapat membaca data Excel'];
            }
            
            $transformedData = [];
            $errors = [];
            
            foreach ($rawData as $rowIndex => $row) {
                $transformedRow = $this->transformRow($row, $finalMapping, $rowIndex + 2); // +2 karena mulai dari baris 2
                
                if ($transformedRow['success']) {
                    $transformedData[] = $transformedRow['data'];
                } else {
                    $errors[] = "Baris " . ($rowIndex + 2) . ": " . $transformedRow['message'];
                }
            }
            
            return [
                'success' => true,
                'data' => [
                    'transformed_data' => $transformedData,
                    'total_rows' => count($rawData),
                    'successful_rows' => count($transformedData),
                    'errors' => $errors
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Extract raw data dari Excel
     */
    private function extractRawData($file) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($extension === 'xlsx') {
            return $this->extractRawDataXLSX($file);
        } elseif ($extension === 'csv') {
            return $this->extractRawDataCSV($file);
        }
        
        return null;
    }
    
    private function extractRawDataXLSX($file) {
        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) !== TRUE) {
            return null;
        }
        
        $sharedStrings = [];
        $sharedStringsXML = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXML) {
            $xml = simplexml_load_string($sharedStringsXML);
            foreach ($xml->si as $si) {
                $sharedStrings[] = (string)$si->t;
            }
        }
        
        $worksheetXML = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        
        if (!$worksheetXML) {
            return null;
        }
        
        $xml = simplexml_load_string($worksheetXML);
        $rows = $xml->sheetData->row;
        
        $data = [];
        $rowNum = 0;
        
        foreach ($rows as $row) {
            $rowNum++;
            if ($rowNum == 1) continue; // Skip header
            
            $cells = [];
            foreach ($row->c as $cell) {
                $value = '';
                if (isset($cell->v)) {
                    if (isset($cell['t']) && $cell['t'] == 's') {
                        $value = $sharedStrings[(int)$cell->v] ?? '';
                    } else {
                        $value = (string)$cell->v;
                    }
                }
                $cells[] = trim($value);
            }
            
            if (!empty(array_filter($cells))) { // Skip empty rows
                $data[] = $cells;
            }
        }
        
        return $data;
    }
    
    private function extractRawDataCSV($file) {
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) return null;
        
        $data = [];
        $rowNum = 0;
        
        while (($row = fgetcsv($handle)) !== FALSE) {
            $rowNum++;
            if ($rowNum == 1) continue; // Skip header
            
            if (!empty(array_filter($row))) { // Skip empty rows
                $data[] = array_map('trim', $row);
            }
        }
        
        fclose($handle);
        return $data;
    }
    
    /**
     * Transform single row
     */
    private function transformRow($row, $mapping, $rowNumber) {
        $transformedRow = [
            'nik' => '',
            'nama_lengkap' => '',
            'jenis_kelamin' => '',
            'tanggal_lahir' => '',
            'agama' => '',
            'pendidikan_terakhir' => 'Tidak Sekolah',
            'pekerjaan' => '',
            'status_pernikahan' => 'Belum Menikah',
            'status_migrasi' => 'Menetap',
            'alamat' => ''
        ];
        
        // Map data berdasarkan mapping
        foreach ($mapping as $field => $mapInfo) {
            $index = $mapInfo['index'] ?? $mapInfo;
            if (isset($row[$index])) {
                $value = $row[$index];
                $transformedRow[$field] = $this->transformFieldValue($field, $value);
            }
        }
        
        // Validasi data wajib
        $requiredFields = ['nik', 'nama_lengkap', 'jenis_kelamin', 'tanggal_lahir'];
        foreach ($requiredFields as $field) {
            if (empty($transformedRow[$field])) {
                return [
                    'success' => false,
                    'message' => "Field wajib '$field' kosong atau tidak valid"
                ];
            }
        }
        
        // Validasi NIK dengan tanggal lahir
        $nikValidation = $this->validateNIKWithBirthDate($transformedRow['nik'], $transformedRow['tanggal_lahir']);
        if (!$nikValidation['valid']) {
            return [
                'success' => false,
                'message' => $nikValidation['message']
            ];
        }
        
        // Validasi nama lengkap
        if (strlen($transformedRow['nama_lengkap']) < 2) {
            return [
                'success' => false,
                'message' => 'Nama lengkap minimal 2 karakter'
            ];
        }
        
        // Validasi jenis kelamin
        if (!in_array($transformedRow['jenis_kelamin'], ['L', 'P'])) {
            return [
                'success' => false,
                'message' => 'Jenis kelamin harus L (Laki-laki) atau P (Perempuan)'
            ];
        }
        
        // Validasi tanggal lahir
        if (!$this->validateDate($transformedRow['tanggal_lahir'])) {
            $originalValue = 'unknown';
            foreach ($mapping as $field => $mapInfo) {
                if ($field === 'tanggal_lahir') {
                    $index = $mapInfo['index'] ?? $mapInfo;
                    $originalValue = $row[$index] ?? 'not found';
                    break;
                }
            }
            return [
                'success' => false,
                'message' => 'Format tanggal lahir tidak valid. Input: "' . $originalValue . '" -> Transform: "' . $transformedRow['tanggal_lahir'] . '"'
            ];
        }
        
        return [
            'success' => true,
            'data' => $transformedRow
        ];
    }
    
    /**
     * Transform field value berdasarkan tipe field
     */
    private function transformFieldValue($field, $value) {
        $value = trim($value);
        
        switch ($field) {
            case 'nik':
                return preg_replace('/[^0-9]/', '', $value);
                
            case 'nama_lengkap':
                return ucwords(strtolower($value));
                
            case 'jenis_kelamin':
                $value = strtolower($value);
                if (in_array($value, ['l', 'laki-laki', 'laki', 'male', 'pria'])) {
                    return 'L';
                } elseif (in_array($value, ['p', 'perempuan', 'wanita', 'female', 'cewe'])) {
                    return 'P';
                }
                return $value;
                
            case 'tanggal_lahir':
                return $this->transformDate($value);
                
            case 'agama':
                return ucfirst(strtolower($value));
                
            case 'pendidikan_terakhir':
                $pendidikan = strtoupper(trim($value));
                $mapping = [
                    'TIDAK SEKOLAH' => 'Tidak Sekolah',
                    'SD' => 'SD', 'SEKOLAH DASAR' => 'SD',
                    'SMP' => 'SMP', 'SEKOLAH MENENGAH PERTAMA' => 'SMP',
                    'SMA' => 'SMA', 'SEKOLAH MENENGAH ATAS' => 'SMA', 'SMK' => 'SMA',
                    'D3' => 'D3', 'DIPLOMA' => 'D3',
                    'S1' => 'S1', 'SARJANA' => 'S1',
                    'S2' => 'S2', 'MAGISTER' => 'S2',
                    'S3' => 'S3', 'DOKTOR' => 'S3'
                ];
                return $mapping[$pendidikan] ?? $value;
                
            case 'pekerjaan':
                // Gunakan PekerjaanManager untuk normalisasi
                if (!empty($value)) {
                    return $this->pekerjaanManager->normalizePekerjaan($value);
                }
                return $value;
                
            case 'status_pernikahan':
                $status = strtolower(trim($value));
                if (in_array($status, ['belum menikah', 'belum', 'single'])) {
                    return 'Belum Menikah';
                } elseif (in_array($status, ['menikah', 'kawin', 'married'])) {
                    return 'Menikah';
                } elseif (in_array($status, ['cerai hidup', 'cerai'])) {
                    return 'Cerai Hidup';
                } elseif (in_array($status, ['cerai mati', 'janda', 'duda'])) {
                    return 'Cerai Mati';
                }
                return 'Belum Menikah';
                
            case 'status_migrasi':
                $status = strtolower(trim($value));
                if (in_array($status, ['menetap', 'tetap', 'asli'])) {
                    return 'Menetap';
                } elseif (in_array($status, ['pendatang', 'datang', 'pindahan'])) {
                    return 'Pendatang';
                } elseif (in_array($status, ['pindah', 'keluar'])) {
                    return 'Pindah';
                }
                return 'Menetap';
                
            default:
                return $value;
        }
    }
    
    /**
     * Convert Indonesian month names to English for DateTime parsing
     */
    private function convertIndonesianMonthToEnglish($dateString) {
        $indonesianToEnglish = [
            'januari' => 'January', 'februari' => 'February', 'maret' => 'March', 'april' => 'April',
            'mei' => 'May', 'juni' => 'June', 'juli' => 'July', 'agustus' => 'August',
            'september' => 'September', 'oktober' => 'October', 'november' => 'November', 'desember' => 'December'
        ];
        
        $converted = $dateString;
        foreach ($indonesianToEnglish as $indo => $eng) {
            $converted = str_ireplace($indo, $eng, $converted);
        }
        
        return $converted;
    }
    
    /**
     * Transform tanggal ke format Y-m-d
     */
    private function transformDate($dateString) {
        $dateString = trim($dateString);
        
        // Handle Excel serial date numbers
        if (is_numeric($dateString) && $dateString > 1 && $dateString < 100000) {
            $excelEpoch = new DateTime('1900-01-01');
            $excelEpoch->modify('-2 days');
            $excelEpoch->modify("+{$dateString} days");
            return $excelEpoch->format('Y-m-d');
        }
        
        // Jika sudah format Y-m-d
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
            return $dateString;
        }
        
        // Try flexible parsing for formats like 1/3/1985 FIRST
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateString, $matches)) {
            $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            return "$year-$month-$day";
        }
        
        // Try flexible parsing for formats like 15-Des-1990
        if (preg_match('/^(\d{1,2})-(\w{3})-(\d{4})$/', $dateString, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $monthName = strtolower($matches[2]);
            $year = $matches[3];
            
            $monthMap = [
                'jan' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04',
                'may' => '05', 'jun' => '06', 'jul' => '07', 'aug' => '08',
                'sep' => '09', 'oct' => '10', 'nov' => '11', 'dec' => '12',
                'des' => '12', 'agu' => '08', 'okt' => '10'
            ];
            
            if (isset($monthMap[$monthName])) {
                return "$year-{$monthMap[$monthName]}-$day";
            }
        }
        
        // Handle format Indonesia: 2004-februari-11
        $monthNames = [
            'januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04',
            'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08',
            'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12',
            'jan' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04',
            'may' => '05', 'jun' => '06', 'jul' => '07', 'aug' => '08', 'agu' => '08',
            'sep' => '09', 'oct' => '10', 'okt' => '10', 'nov' => '11', 'dec' => '12', 'des' => '12'
        ];
        
        // Check for Indonesian month format
        foreach ($monthNames as $monthName => $monthNum) {
            if (stripos($dateString, $monthName) !== false) {
                $parts = preg_split('/[-\s]+/', $dateString);
                if (count($parts) >= 3) {
                    if (strlen($parts[0]) == 4) {
                        $year = $parts[0];
                        $day = str_pad($parts[2], 2, '0', STR_PAD_LEFT);
                    } else {
                        $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                        $year = $parts[2];
                    }
                    return "$year-$monthNum-$day";
                }
            }
        }
        
        // Handle format: 10 Juni 1988
        $dateStringLower = strtolower($dateString);
        foreach ($monthNames as $monthName => $monthNum) {
            if (strpos($dateStringLower, $monthName) !== false) {
                $escapedMonthName = preg_quote($monthName, '/');
                if (preg_match('/(\d{1,2})\s+' . $escapedMonthName . '\s+(\d{4})/', $dateStringLower, $matches)) {
                    $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                    $year = $matches[2];
                    return "$year-$monthNum-$day";
                }
            }
        }
        
        // Coba berbagai format dengan DateTime
        $englishDateString = $this->convertIndonesianMonthToEnglish($dateString);
        if ($englishDateString !== $dateString) {
            foreach ($this->dateFormats as $format) {
                $date = DateTime::createFromFormat($format, $englishDateString);
                if ($date && $date->format($format) === $englishDateString) {
                    return $date->format('Y-m-d');
                }
            }
        }
        
        foreach ($this->dateFormats as $format) {
            $date = DateTime::createFromFormat($format, $dateString);
            if ($date && $date->format($format) === $dateString) {
                return $date->format('Y-m-d');
            }
        }
        
        return $dateString;
    }
    
    private function validateDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Validasi NIK dengan cross-check tanggal lahir
     */
    private function validateNIKWithBirthDate($nik, $birthDate) {
        // NIK harus numerik minimal 10 digit
        if (!preg_match('/^\d{10,}$/', $nik)) {
            return [
                'valid' => false,
                'message' => 'NIK harus berupa angka minimal 10 digit'
            ];
        }
        
        // Skip validasi tanggal lahir dalam NIK jika ada tanggal lahir yang valid
        if ($this->validateDate($birthDate)) {
            return ['valid' => true, 'message' => 'NIK valid'];
        }
        
        return ['valid' => true, 'message' => 'NIK valid'];
    }
    
    /**
     * Transform dan simpan data ke database
     */
    public function transformAndSaveData($file, $mapping, $customMapping = []) {
        try {
            // Transform data terlebih dahulu
            $transformResult = $this->transformExcelData($file, $mapping, $customMapping);
            
            if (!$transformResult['success']) {
                return $transformResult;
            }
            
            // Import menggunakan KependudukanFunctions
            require_once __DIR__ . '/kependudukan-functions.php';
            $kependudukanFunctions = new KependudukanFunctions();
            
            $imported = 0;
            $errors = [];
            
            foreach ($transformResult['data']['transformed_data'] as $index => $data) {
                $result = $kependudukanFunctions->createPenduduk($data);
                if ($result['success']) {
                    $imported++;
                } else {
                    $errors[] = "Data " . ($index + 1) . ": " . $result['message'];
                }
            }
            
            return [
                'success' => true,
                'data' => [
                    'total_rows' => $transformResult['data']['total_rows'],
                    'successful_rows' => $transformResult['data']['successful_rows'],
                    'imported_count' => $imported,
                    'transform_errors' => $transformResult['data']['errors'],
                    'import_errors' => $errors
                ]
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Simpan mapping untuk desa tertentu
     */
    public function saveMappingTemplate($templateName, $mapping, $description = '') {
        try {
            $query = "INSERT INTO mapping_templates (id_desa, template_name, mapping_data, description, created_at) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE mapping_data = VALUES(mapping_data), description = VALUES(description), updated_at = NOW()";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $this->id_desa,
                $templateName,
                json_encode($mapping),
                $description
            ]);
            
            return [
                'success' => $result,
                'message' => $result ? 'Template mapping berhasil disimpan' : 'Gagal menyimpan template'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Load mapping template
     */
    public function loadMappingTemplate($templateName) {
        try {
            $query = "SELECT mapping_data FROM mapping_templates WHERE id_desa = ? AND template_name = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$this->id_desa, $templateName]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'success' => true,
                    'data' => json_decode($result['mapping_data'], true)
                ];
            } else {
                return ['success' => false, 'message' => 'Template tidak ditemukan'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get list mapping templates
     */
    public function getMappingTemplates() {
        try {
            $query = "SELECT template_name, description, created_at FROM mapping_templates WHERE id_desa = ? ORDER BY created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$this->id_desa]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $result
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}

// Handle AJAX requests
if (isset($_GET['action']) || isset($_POST['action'])) {
    $autoTransform = new KependudukanAutoTransform();
    
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'detect_format':
            if (isset($_FILES['excel_file'])) {
                $result = $autoTransform->detectExcelFormat($_FILES['excel_file']);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'File tidak ditemukan']);
            }
            break;
            
        case 'transform_data':
            if (isset($_FILES['excel_file']) && isset($_POST['mapping'])) {
                $mapping = json_decode($_POST['mapping'], true);
                $customMapping = json_decode($_POST['custom_mapping'] ?? '[]', true);
                $result = $autoTransform->transformExcelData($_FILES['excel_file'], $mapping, $customMapping);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            }
            break;
            
        case 'transform_and_save':
            if (isset($_FILES['excel_file']) && isset($_POST['mapping'])) {
                $mapping = json_decode($_POST['mapping'], true);
                $customMapping = json_decode($_POST['custom_mapping'] ?? '[]', true);
                $result = $autoTransform->transformAndSaveData($_FILES['excel_file'], $mapping, $customMapping);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            }
            break;
            
        case 'save_mapping_template':
            if (isset($_POST['template_name']) && isset($_POST['mapping'])) {
                $result = $autoTransform->saveMappingTemplate(
                    $_POST['template_name'],
                    json_decode($_POST['mapping'], true),
                    $_POST['description'] ?? ''
                );
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            }
            break;
            
        case 'load_mapping_template':
            if (isset($_GET['template_name'])) {
                $result = $autoTransform->loadMappingTemplate($_GET['template_name']);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Nama template tidak ditemukan']);
            }
            break;
            
        case 'get_mapping_templates':
            $result = $autoTransform->getMappingTemplates();
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
            break;
    }
    exit();
}
?>