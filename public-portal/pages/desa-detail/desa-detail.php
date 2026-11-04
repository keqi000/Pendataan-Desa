<?php
// Entry Point Desa Detail - Sistem Informasi Publik
// File: public-portal/pages/desa-detail/desa-detail.php

require_once __DIR__ . '/../../config/base-url.php';

$desa_slug = $_GET['desa'] ?? 'ilomata';

$page_title   = 'Profil Desa ' . ucwords(str_replace('_', ' ', $desa_slug));
$current_page = 'desa-detail';
$page_css     = [BASE_URL_PUBLIC . 'pages/desa-detail/desa-detail.css'];
$page_js      = [
    BASE_URL_PUBLIC . 'pages/desa-detail/js/desa-profile.js',
    BASE_URL_PUBLIC . 'pages/desa-detail/js/desa-statistics.js',
    BASE_URL_PUBLIC . 'pages/desa-detail/desa-detail.js'
];

ob_start();
require_once __DIR__ . '/php/desa-detail-view.php';
$content = ob_get_clean();

require_once __DIR__ . '/../../layout/main-layout.php';
?>
