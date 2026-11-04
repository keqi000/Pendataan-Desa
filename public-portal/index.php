<?php
// Entry Point untuk Sistem Informasi Publik
// File: public-portal/index.php

// Include konfigurasi
require_once __DIR__ . '/config/base-url.php';

// Set page configuration
$page_title = 'Beranda';
$current_page = 'dashboard';
$page_css = [
    BASE_URL_PUBLIC . 'pages/dashboard/dashboard.css'
];
$page_js = [
    BASE_URL_PUBLIC . 'pages/dashboard/js/dashboard-charts.js',
    BASE_URL_PUBLIC . 'pages/dashboard/dashboard.js'
];

// Include dashboard content
ob_start();
require_once __DIR__ . '/pages/dashboard/php/dashboard-view.php';
$content = ob_get_clean();

// Include main layout
require_once __DIR__ . '/layout/main-layout.php';
?>