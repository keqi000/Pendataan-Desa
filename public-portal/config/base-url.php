<?php
// Base URL Configuration untuk Sistem Informasi Publik
// File: public-portal/config/base-url.php
require_once __DIR__ . '/../../config/config.php';

function getPublicBaseUrl() {
    return BASE_URL . 'public-portal/';
}

// Set konstanta BASE_URL_PUBLIC
if (!defined('BASE_URL_PUBLIC')) {
    define('BASE_URL_PUBLIC', getPublicBaseUrl());
}

// API Base URL ke sistem utama
function getMainSystemApiUrl() {
    return BASE_URL;
}

if (!defined('MAIN_SYSTEM_API_URL')) {
    define('MAIN_SYSTEM_API_URL', getMainSystemApiUrl());
}

// URL Login Pendataan-desa
function getPendataanLoginUrl() {
    return BASE_URL . 'pages/auth/login.php';
}

if (!defined('PENDATAAN_LOGIN_URL')) {
    define('PENDATAAN_LOGIN_URL', getPendataanLoginUrl());
}
?>