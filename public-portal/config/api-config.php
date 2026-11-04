<?php
// API Configuration untuk Sistem Informasi Publik
// File: public-portal/config/api-config.php

class ApiConfig {
    
    // API Keys untuk akses eksternal
    private static $validApiKeys = [
        'public_access_2024' => [
            'name' => 'Public Access',
            'rate_limit' => 1000, // requests per hour
            'permissions' => ['read']
        ],
        'research_key_2024' => [
            'name' => 'Research Access',
            'rate_limit' => 2000,
            'permissions' => ['read', 'export']
        ]
    ];
    
    // Rate limiting configuration
    private static $rateLimits = [
        'default' => 100,    // requests per hour for non-authenticated
        'authenticated' => 1000  // requests per hour for API key users
    ];
    
    // Cache configuration
    private static $cacheConfig = [
        'enabled' => true,
        'ttl' => 900,        // 15 minutes
        'path' => __DIR__ . '/../cache/'
    ];
    
    public static function validateApiKey($apiKey) {
        return isset(self::$validApiKeys[$apiKey]);
    }
    
    public static function getApiKeyInfo($apiKey) {
        return self::$validApiKeys[$apiKey] ?? null;
    }
    
    public static function getRateLimit($apiKey = null) {
        if ($apiKey && self::validateApiKey($apiKey)) {
            return self::$validApiKeys[$apiKey]['rate_limit'];
        }
        return self::$rateLimits['default'];
    }
    
    public static function getCacheConfig() {
        return self::$cacheConfig;
    }
    
    public static function getPublicDataFields() {
        return [
            'desa' => [
                'id_desa', 'nama_desa', 'luas_wilayah', 
                'jumlah_dusun', 'jumlah_rt', 'jumlah_rw'
            ],
            'statistik' => [
                'total_penduduk', 'total_kk', 'total_laki', 'total_perempuan',
                'distribusi_usia', 'distribusi_pendidikan'
            ],
            'fasilitas' => [
                'nama_fasilitas', 'jenis_pendidikan', 'kapasitas_siswa',
                'kondisi_bangunan', 'alamat_fasilitas'
            ],
            'umkm' => [
                'nama_usaha', 'jenis_usaha', 'alamat_usaha', 
                'jumlah_karyawan', 'status_izin'
            ],
            'infrastruktur' => [
                'nama_jalan', 'panjang_jalan', 'kondisi_jalan',
                'nama_jembatan', 'kondisi_jembatan'
            ]
        ];
    }
    
    public static function getSensitiveFields() {
        // Data yang TIDAK boleh diakses publik
        return [
            'nik', 'alamat_lengkap', 'nomor_kk', 'penghasilan_perbulan',
            'modal_usaha', 'omzet_perbulan', 'jenis_bantuan', 'nominal_bantuan'
        ];
    }
}
?>