<?php
// Base URL Configuration untuk Sistem Informasi Publik
// File: public-portal/config/base-url.php

function getPublicBaseUrl() {
    // Handle CLI execution
    if (php_sapi_name() === 'cli') {
        return 'http://localhost/public-portal/';
    }
    
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Deteksi environment
    if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false) {
        // Development environment
        return $protocol . '://' . $host . '/public-portal/';
    } else {
        // Production environment
        return $protocol . '://' . $host . '/public-portal/';
    }
}

// Set konstanta BASE_URL_PUBLIC
if (!defined('BASE_URL_PUBLIC')) {
    define('BASE_URL_PUBLIC', getPublicBaseUrl());
}

// API Base URL ke sistem utama
function getMainSystemApiUrl() {
    if (php_sapi_name() === 'cli') {
        return 'http://localhost/Pendataan-desa/';
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $hostname = strtolower((string)parse_url('//' . $host, PHP_URL_HOST));

    if (in_array($hostname, ['localhost', '127.0.0.1', '::1'], true)) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        return $protocol . '://' . $host . '/Pendataan-desa/';
    }

    return 'https://sipendesatibawa.my.id/Pendataan-desa/';
}

if (!defined('MAIN_SYSTEM_API_URL')) {
    define('MAIN_SYSTEM_API_URL', getMainSystemApiUrl());
}

// URL Login Pendataan-desa
function getPendataanLoginUrl() {
    if (php_sapi_name() === 'cli') {
        return 'http://localhost/Pendataan-desa/pages/auth/login.php';
    }

    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false) {
        return $protocol . '://' . $host . '/Pendataan-desa/pages/auth/login.php';
    }

    return 'https://sipendesatibawa.my.id/Pendataan-desa/pages/auth/login.php';
}

if (!defined('PENDATAAN_LOGIN_URL')) {
    define('PENDATAAN_LOGIN_URL', getPendataanLoginUrl());
}
?>