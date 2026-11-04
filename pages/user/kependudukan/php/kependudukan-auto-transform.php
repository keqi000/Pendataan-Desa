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
        'tanggal_lahir' => ['tanggal_lahir', 'tgl_lahir', 'ttl', 'tanggal lahir', 'tgl lahir', 'birth_date', 'birthdate', 'tgl_lhr', 'tanggallahir'],
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
    public function detectExcelFormat($file, $manualHeaderRow = null) {
        try {
            $result = $this->extractHeadersWithMeta($file, $manualHeaderRow);
            if (!$result) {
                return ['success' => false, 'message' => 'Tidak dapat membaca header Excel'];
            }

            $headers    = $result['headers'];
            $headerRow  = $result['header_row'];
            $isMultiRow = $result['is_multi_row'];

            $mapping    = $this->createColumnMapping($headers);
            $confidence = $this->calculateMappingConfidence($mapping, $headers);

            return [
                'success' => true,
                'data' => [
                    'headers'     => $headers,
                    'mapping'     => $mapping,
                    'confidence'  => $confidence,
                    'header_row'  => $headerRow,
                    'is_multi_row'=> $isMultiRow,
                    'suggestions' => $this->generateMappingSuggestions($headers)
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Extract headers beserta metadata (header_row, is_multi_row)
     */
    private function extractHeadersWithMeta($file, $manualHeaderRow = null) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension === 'xlsx') {
            return $this->extractHeadersXLSX($file, $manualHeaderRow);
        } elseif ($extension === 'csv') {
            return $this->extractHeadersCSV($file, $manualHeaderRow);
        }
        return null;
    }

    /**
     * FIX #3: Parse shared strings dengan benar (support rich text + XML namespace)
     */
    private function parseSharedStrings($sharedStringsXML) {
        $sharedStrings = [];
        if (!$sharedStringsXML) return $sharedStrings;

        // Strip XML namespaces agar xpath('.//t') bekerja
        $clean = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $sharedStringsXML);
        $xml   = simplexml_load_string($clean);
        if (!$xml) return $sharedStrings;

        foreach ($xml->si as $si) {
            $text = '';
            foreach ($si->xpath('.//t') as $t) {
                $text .= (string)$t;
            }
            $sharedStrings[] = $text;
        }
        return $sharedStrings;
    }

    /**
     * FIX #4: Baca satu baris XML menjadi array berindeks kolom yang benar
     * Menggunakan atribut 'r' (misal A1, B1, C3) untuk posisi kolom aktual
     */
    private function parseRowCells($rowXml, $sharedStrings) {
        $cells = [];
        foreach ($rowXml->c as $cell) {
            $ref = (string)$cell['r']; // misal "A1", "C3"
            // Ambil huruf kolom saja, konversi ke index 0-based
            preg_match('/^([A-Z]+)/', $ref, $m);
            $colIndex = $this->colLetterToIndex($m[1] ?? 'A');

            $value = '';
            if (isset($cell->v)) {
                if (isset($cell['t']) && $cell['t'] == 's') {
                    $value = $sharedStrings[(int)$cell->v] ?? '';
                } else {
                    $value = (string)$cell->v;
                }
            }
            $cells[$colIndex] = trim($value);
        }
        return $cells;
    }

    /**
     * Konversi huruf kolom Excel (A, B, ..., Z, AA, AB, ...) ke index 0-based
     */
    private function colLetterToIndex($letters) {
        $letters = strtoupper($letters);
        $index = 0;
        for ($i = 0; $i < strlen($letters); $i++) {
            $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
        }
        return $index - 1;
    }

    /**
     * Normalisasi sparse cells array menjadi array berurutan 0..maxCol
     */
    private function normalizeCells($cells) {
        if (empty($cells)) return [];
        $maxCol = max(array_keys($cells));
        $result = [];
        for ($i = 0; $i <= $maxCol; $i++) {
            $result[] = $cells[$i] ?? '';
        }
        return $result;
    }

    /**
     * FIX #1: Deteksi baris header otomatis dengan scan baris 1-15
     * Kembalikan index baris (0-based) yang paling cocok sebagai header
     */
    private function detectHeaderRow($allRows, $sharedStrings) {
        $bestRow   = 0;
        $bestScore = -1;

        $scanLimit = min(15, count($allRows));
        for ($i = 0; $i < $scanLimit; $i++) {
            $cells   = $this->parseRowCells($allRows[$i], $sharedStrings);
            $cells   = $this->normalizeCells($cells);
            $score   = 0;
            foreach ($cells as $cell) {
                $cell = strtolower(trim($cell));
                if ($cell === '') continue;
                // Nilai numerik murni bukan header
                if (is_numeric($cell)) continue;
                // Cek apakah cocok dengan salah satu pattern
                foreach ($this->columnPatterns as $patterns) {
                    if ($this->calculateSimilarity($cell, $patterns) >= 0.6) {
                        $score += 2;
                        break;
                    }
                }
                // Bonus: teks pendek (header biasanya pendek)
                if (strlen($cell) <= 30) $score += 1;
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRow   = $i;
            }
        }
        return $bestRow;
    }

    /**
     * FIX #2: Deteksi dan gabungkan multi-row header
     * Jika baris sebelum header_row juga berisi teks (bukan angka/tanggal),
     * gabungkan sebagai prefix: "ParentHeader_ChildHeader"
     */
    private function mergeMultiRowHeaders($allRows, $sharedStrings, $headerRowIndex) {
        $headerCells = $this->normalizeCells(
            $this->parseRowCells($allRows[$headerRowIndex], $sharedStrings)
        );

        $isMultiRow  = false;
        $parentCells = [];

        if ($headerRowIndex > 0) {
            $prevCells = $this->normalizeCells(
                $this->parseRowCells($allRows[$headerRowIndex - 1], $sharedStrings)
            );

            // Hitung berapa cell yang tidak kosong di baris sebelumnya
            $nonEmptyCells = array_filter($prevCells, fn($c) => trim($c) !== '' && !is_numeric(trim($c)));

            // Hanya jadikan multi-row jika baris sebelumnya punya LEBIH DARI 1 cell berisi teks
            // (1 cell = kemungkinan hanya judul dokumen, bukan header row)
            if (count($nonEmptyCells) > 1) {
                $isMultiRow  = true;
                $parentCells = $prevCells;
            }
        }

        if (!$isMultiRow) {
            return ['headers' => $headerCells, 'is_multi_row' => false];
        }

        $lastParent = '';
        $merged     = [];
        foreach ($headerCells as $i => $child) {
            $parent = trim($parentCells[$i] ?? '');
            if ($parent !== '') $lastParent = $parent;
            $child = trim($child);
            if ($child === '') {
                $merged[] = $lastParent;
            } elseif ($lastParent !== '' && strtolower($lastParent) !== strtolower($child)) {
                $merged[] = $lastParent . '_' . $child;
            } else {
                $merged[] = $child;
            }
        }

        return ['headers' => $merged, 'is_multi_row' => true];
    }

    private function extractHeadersXLSX($file, $manualHeaderRow = null) {
        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) !== TRUE) return null;

        $sharedStrings = $this->parseSharedStrings($zip->getFromName('xl/sharedStrings.xml'));
        $worksheetRaw  = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if (!$worksheetRaw) return null;

        // Strip namespace agar simplexml bisa parse dengan benar
        $worksheetClean = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $worksheetRaw);
        $xml     = simplexml_load_string($worksheetClean);
        $allRows = iterator_to_array($xml->sheetData->row, false);
        if (empty($allRows)) return null;

        // FIX #1: Tentukan baris header
        $headerRowIndex = ($manualHeaderRow !== null)
            ? max(0, (int)$manualHeaderRow - 1)  // user input 1-based
            : $this->detectHeaderRow($allRows, $sharedStrings);

        // FIX #2: Gabungkan multi-row header jika ada
        $result = $this->mergeMultiRowHeaders($allRows, $sharedStrings, $headerRowIndex);

        return [
            'headers'      => $result['headers'],
            'header_row'   => $headerRowIndex + 1, // kembalikan 1-based
            'is_multi_row' => $result['is_multi_row']
        ];
    }

    private function extractHeadersCSV($file, $manualHeaderRow = null) {
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) return null;

        $headerRowIndex = ($manualHeaderRow !== null) ? max(1, (int)$manualHeaderRow) : 1;
        $currentRow = 0;
        $headers = null;

        while (($row = fgetcsv($handle)) !== FALSE) {
            $currentRow++;
            if ($currentRow === $headerRowIndex) {
                $headers = array_map('trim', $row);
                break;
            }
        }
        fclose($handle);

        if (!$headers) return null;
        return [
            'headers'      => $headers,
            'header_row'   => $headerRowIndex,
            'is_multi_row' => false
        ];
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
        
        // Strip prefix multi-row (misal "REGIS DUSUN ILOHELUMA_NAMA LENGKAP" → "nama lengkap")
        if (strpos($header, '_') !== false) {
            $parts  = explode('_', $header);
            $header = trim(end($parts));
        }
        
        // Normalisasi spasi ganda
        $header = preg_replace('/\s+/', ' ', $header);
        
        $maxSimilarity = 0;
        
        foreach ($patterns as $pattern) {
            $pattern = strtolower($pattern);
            
            if ($header === $pattern) return 1.0;
            
            if (strpos($header, $pattern) !== false || strpos($pattern, $header) !== false) {
                $similarity = 0.8;
            } else {
                $similarity = 1 - (levenshtein($header, $pattern) / max(strlen($header), strlen($pattern), 1));
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
    public function transformExcelData($file, $mapping, $customMapping = [], $headerRow = null) {
        try {
            $finalMapping = array_merge($mapping, $customMapping);

            $rawData = $this->extractRawData($file, $headerRow);
            if (!$rawData) {
                return ['success' => false, 'message' => 'Tidak dapat membaca data Excel'];
            }

            $transformedData = [];
            $errors = [];
            $dataStartRow = ($headerRow ?? 1) + 1;

            foreach ($rawData as $rowIndex => $row) {
                $transformedRow = $this->transformRow($row, $finalMapping, $dataStartRow + $rowIndex);
                if ($transformedRow['success']) {
                    $transformedData[] = $transformedRow['data'];
                } elseif (!($transformedRow['skip'] ?? false)) {
                    $errors[] = "Baris " . ($dataStartRow + $rowIndex) . ": " . $transformedRow['message'];
                }
            }

            return [
                'success' => true,
                'data' => [
                    'transformed_data' => $transformedData,
                    'total_rows'       => count($rawData),
                    'successful_rows'  => count($transformedData),
                    'errors'           => $errors
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Extract raw data dari Excel (skip baris header dan sebelumnya)
     */
    private function extractRawData($file, $headerRow = null) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension === 'xlsx') {
            return $this->extractRawDataXLSX($file, $headerRow);
        } elseif ($extension === 'csv') {
            return $this->extractRawDataCSV($file, $headerRow);
        }
        return null;
    }

    private function extractRawDataXLSX($file, $headerRow = null) {
        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) !== TRUE) return null;

        $sharedStrings = $this->parseSharedStrings($zip->getFromName('xl/sharedStrings.xml'));
        $worksheetRaw  = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if (!$worksheetRaw) return null;

        // Strip namespace
        $worksheetClean = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $worksheetRaw);
        $xml     = simplexml_load_string($worksheetClean);
        $allRows = iterator_to_array($xml->sheetData->row, false);

        // Tentukan baris header (1-based), default auto-detect
        if ($headerRow === null) {
            $headerRowIndex = $this->detectHeaderRow($allRows, $sharedStrings);
        } else {
            $headerRowIndex = max(0, (int)$headerRow - 1);
        }

        // Data dimulai setelah baris header
        $dataStartIndex = $headerRowIndex + 1;

        $data = [];
        for ($i = $dataStartIndex; $i < count($allRows); $i++) {
            // FIX #4: Gunakan parseRowCells + normalizeCells
            $cells = $this->normalizeCells(
                $this->parseRowCells($allRows[$i], $sharedStrings)
            );
            if (!empty(array_filter($cells))) {
                $data[] = $cells;
            }
        }
        return $data;
    }

    private function extractRawDataCSV($file, $headerRow = null) {
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) return null;

        $skipUntil = ($headerRow !== null) ? (int)$headerRow : 1;
        $data    = [];
        $rowNum  = 0;

        while (($row = fgetcsv($handle)) !== FALSE) {
            $rowNum++;
            if ($rowNum <= $skipUntil) continue; // Skip header dan baris sebelumnya
            if (!empty(array_filter($row))) {
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
        
        // Cek apakah baris ini adalah baris di luar tabel (keterangan, total, dll)
        $dataFields  = ['nik', 'nama_lengkap', 'jenis_kelamin', 'tanggal_lahir'];
        $filledCount = 0;
        $filledDebug = [];
        foreach ($dataFields as $f) {
            if (isset($mapping[$f])) {
                $idx = $mapping[$f]['index'] ?? $mapping[$f];
                $val = trim($row[$idx] ?? '');
                if (!empty($val)) {
                    $filledCount++;
                    $filledDebug[] = "$f=\"$val\"";
                }
            }
        }
        // Semua kolom utama kosong → skip
        if ($filledCount === 0) {
            return ['success' => false, 'skip' => true, 'message' => 'Baris kosong/di luar tabel, dilewati'];
        }

        // NIK kosong tapi nama terisi → cek apakah nama adalah baris keterangan/footer
        $nikIdx  = isset($mapping['nik']) ? ($mapping['nik']['index'] ?? $mapping['nik']) : null;
        $namaIdx = isset($mapping['nama_lengkap']) ? ($mapping['nama_lengkap']['index'] ?? $mapping['nama_lengkap']) : null;
        $nikVal  = $nikIdx !== null ? trim($row[$nikIdx] ?? '') : '';
        $namaVal = $namaIdx !== null ? strtolower(trim($row[$namaIdx] ?? '')) : '';

        if (empty($nikVal) && !empty($namaVal)) {
            // Kata kunci yang menandakan baris keterangan/footer
            $footerKeywords = [
                'jumlah', 'total', 'laki-laki', 'laki laki', 'perempuan',
                'kk ', 'kepala keluarga', 'bangunan', 'rumah', 'menumpang',
                'janda', 'duda', 'lengkap', 'layak', 'hantu', 'catatan',
                'keterangan', 'ket ', 'ttd', 'tanda tangan', 'mengetahui',
                'kepala desa', 'lurah', 'camat', 'rt ', 'rw '
            ];
            foreach ($footerKeywords as $kw) {
                if (strpos($namaVal, $kw) !== false) {
                    return ['success' => false, 'skip' => true, 'message' => 'Baris keterangan/footer, dilewati'];
                }
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
        
        // Format d-m-Y atau d/m/Y dengan hari/bulan 1-2 digit (misal: 8-9-1968, 01-07-2007)
        if (preg_match('/^(\d{1,2})[\-\/](\d{1,2})[\-\/](\d{4})$/', $dateString, $matches)) {
            $day   = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year  = $matches[3];
            return "$year-$month-$day";
        }
        
        // Format 15-Des-1990 atau 15-Dec-1990
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
        
        // Handle format Indonesia dengan nama bulan: 10 Juni 1988, 2004-februari-11
        $monthNames = [
            'januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04',
            'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08',
            'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12',
            'jan' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04',
            'may' => '05', 'jun' => '06', 'jul' => '07', 'aug' => '08', 'agu' => '08',
            'sep' => '09', 'oct' => '10', 'okt' => '10', 'nov' => '11', 'dec' => '12', 'des' => '12'
        ];
        
        $dateStringLower = strtolower($dateString);
        foreach ($monthNames as $monthName => $monthNum) {
            if (strpos($dateStringLower, $monthName) !== false) {
                // Format: 10 Juni 1988
                $escapedMonthName = preg_quote($monthName, '/');
                if (preg_match('/(\d{1,2})\s+' . $escapedMonthName . '\s+(\d{4})/', $dateStringLower, $matches)) {
                    $day  = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                    $year = $matches[2];
                    return "$year-$monthNum-$day";
                }
                // Format: 2004-februari-11
                $parts = preg_split('/[-\s]+/', $dateStringLower);
                if (count($parts) >= 3) {
                    if (strlen($parts[0]) == 4) {
                        $year = $parts[0];
                        $day  = str_pad($parts[2], 2, '0', STR_PAD_LEFT);
                    } else {
                        $day  = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                        $year = $parts[2];
                    }
                    return "$year-$monthNum-$day";
                }
            }
        }
        
        // Fallback: coba DateTime::createFromFormat
        $englishDateString = $this->convertIndonesianMonthToEnglish($dateString);
        $tryStrings = array_unique([$dateString, $englishDateString]);
        foreach ($tryStrings as $tryStr) {
            foreach ($this->dateFormats as $format) {
                $date = DateTime::createFromFormat($format, $tryStr);
                if ($date && $date->format($format) === $tryStr) {
                    return $date->format('Y-m-d');
                }
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
    public function transformAndSaveData($file, $mapping, $customMapping = [], $headerRow = null) {
        try {
            // Transform data terlebih dahulu
            $transformResult = $this->transformExcelData($file, $mapping, $customMapping, $headerRow);
            
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
    // Tangkap semua output/warning sebelum JSON dikirim
    ob_start();
    error_reporting(0);
    
    $autoTransform = new KependudukanAutoTransform();
    
    $junkOutput = ob_get_clean();
    ob_start();
    
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'detect_format':
            if (isset($_FILES['excel_file'])) {
                $manualHeaderRow = isset($_POST['header_row']) && $_POST['header_row'] !== '' ? (int)$_POST['header_row'] : null;
                $result = $autoTransform->detectExcelFormat($_FILES['excel_file'], $manualHeaderRow);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'File tidak ditemukan']);
            }
            break;

        case 'transform_data':
            if (isset($_FILES['excel_file']) && isset($_POST['mapping'])) {
                $mapping       = json_decode($_POST['mapping'], true);
                $customMapping = json_decode($_POST['custom_mapping'] ?? '[]', true);
                $headerRow     = isset($_POST['header_row']) && $_POST['header_row'] !== '' ? (int)$_POST['header_row'] : null;
                $result = $autoTransform->transformExcelData($_FILES['excel_file'], $mapping, $customMapping, $headerRow);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            }
            break;

        case 'transform_and_save':
            if (isset($_FILES['excel_file']) && isset($_POST['mapping'])) {
                $mapping       = json_decode($_POST['mapping'], true);
                $customMapping = json_decode($_POST['custom_mapping'] ?? '[]', true);
                $headerRow     = isset($_POST['header_row']) && $_POST['header_row'] !== '' ? (int)$_POST['header_row'] : null;
                $result = $autoTransform->transformAndSaveData($_FILES['excel_file'], $mapping, $customMapping, $headerRow);
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
    
    $output = ob_get_clean();
    echo $output;
    exit();
}
?>