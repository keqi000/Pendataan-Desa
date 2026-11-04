<?php
// Entry Point UMKM - Sistem Informasi Publik
// File: public-portal/pages/umkm/umkm.php

require_once __DIR__ . '/../../config/base-url.php';

$page_title   = 'Direktori UMKM';
$current_page = 'umkm';
$page_css     = [BASE_URL_PUBLIC . 'pages/umkm/umkm.css'];
$page_js      = [
    BASE_URL_PUBLIC . 'pages/umkm/js/umkm-search.js',
    BASE_URL_PUBLIC . 'pages/umkm/js/umkm-category.js',
    BASE_URL_PUBLIC . 'pages/umkm/umkm.js'
];

ob_start();
require_once __DIR__ . '/php/umkm-view.php';
$content = ob_get_clean();

require_once __DIR__ . '/../../layout/main-layout.php';
?>
