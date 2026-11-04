<?php
// Entry Point Fasilitas - Sistem Informasi Publik
// File: public-portal/pages/fasilitas/fasilitas.php

require_once __DIR__ . '/../../config/base-url.php';

$page_title   = 'Fasilitas Publik';
$current_page = 'fasilitas';
$page_css     = [BASE_URL_PUBLIC . 'pages/fasilitas/fasilitas.css'];
$page_js      = [
    BASE_URL_PUBLIC . 'pages/fasilitas/js/fasilitas-map.js',
    BASE_URL_PUBLIC . 'pages/fasilitas/js/fasilitas-filter.js',
    BASE_URL_PUBLIC . 'pages/fasilitas/fasilitas.js'
];

ob_start();
require_once __DIR__ . '/php/fasilitas-view.php';
$content = ob_get_clean();

require_once __DIR__ . '/../../layout/main-layout.php';
?>
