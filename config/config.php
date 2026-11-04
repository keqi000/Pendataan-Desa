<?php
// Konfigurasi Base URL dinamis untuk localhost dan hosting
function getBaseURL() {
    $serverPort = (int)($_SERVER['SERVER_PORT'] ?? 0);
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $serverPort === 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Deteksi localhost
    if (in_array($domainName, ['localhost', '127.0.0.1', '::1']) || strpos($domainName, 'localhost:') === 0) {
        return $protocol . $domainName . '/Pendataan-desa/';
    }
    
    // Untuk hosting dengan folder Pendataan-desa
    return $protocol . $domainName . '/Pendataan-desa/';
}

// Konstanta BASE_URL
define('BASE_URL', getBaseURL());

// Konfigurasi API URL
define('API_URL', BASE_URL . 'data-publik/');

// URL Public Portal
function getPublicPortalURL() {
    return BASE_URL . 'public-portal/';
}
define('PUBLIC_PORTAL_URL', getPublicPortalURL());

// Konfigurasi Assets
define('ASSETS_URL', BASE_URL . 'assets/');
define('CSS_URL', BASE_URL . 'assets/css/');
define('JS_URL', BASE_URL . 'assets/js/');
define('IMG_URL', BASE_URL . 'assets/img/');

// Konfigurasi Upload
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . 'uploads/');

// Timezone
date_default_timezone_set('Asia/Makassar');
?>