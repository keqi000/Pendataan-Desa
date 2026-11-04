<?php
// Header Component untuk Sistem Informasi Publik
// File: public-portal/layout/header/header.php

require_once __DIR__ . '/../../config/base-url.php';
require_once __DIR__ . '/../../api/core/api-client.php';

$_headerClient  = new ApiClient();
$_headerOverview = $_headerClient->get('statistik.php', ['type' => 'overview']) ?? [];
$_headerTotalDesa     = $_headerOverview['total_desa']     ?? 0;
$_headerTotalPenduduk = number_format($_headerOverview['total_penduduk'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Portal Data Terbuka Kecamatan Tibawa - Akses informasi publik desa-desa di Kecamatan Tibawa">
    <meta name="keywords" content="data desa, kecamatan tibawa, gorontalo utara, informasi publik, statistik penduduk">
    <meta name="author" content="Pemerintah Kecamatan Tibawa">
    
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Portal Data Terbuka Kecamatan Tibawa</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL_PUBLIC; ?>assets/img/favicon.png">
    
    <!-- Global CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL_PUBLIC; ?>assets/css/global.css">
    <link rel="stylesheet" href="<?php echo BASE_URL_PUBLIC; ?>layout/header/header.css">
    <link rel="stylesheet" href="<?php echo BASE_URL_PUBLIC; ?>layout/navbar/navbar.css">
    <link rel="stylesheet" href="<?php echo BASE_URL_PUBLIC; ?>layout/footer/footer.css">
    
    <!-- Page specific CSS -->
    <?php if (isset($page_css) && is_array($page_css)): ?>
        <?php foreach ($page_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo isset($page_title) ? $page_title : 'Portal Data Terbuka Kecamatan Tibawa'; ?>">
    <meta property="og:description" content="Portal informasi publik dan data terbuka desa-desa di Kecamatan Tibawa, Kabupaten Gorontalo Utara">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo BASE_URL_PUBLIC; ?>">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo isset($page_title) ? $page_title : 'Portal Data Terbuka Kecamatan Tibawa'; ?>">
    <meta name="twitter:description" content="Portal informasi publik dan data terbuka desa-desa di Kecamatan Tibawa">
</head>
<body>
    <!-- Header Section -->
    <header class="public-header">
        <div class="container">
            <div class="header-content">
                <div class="header-logo">
                    <div class="header-text">
                        <h1>Portal Data Terbuka</h1>
                        <p>Kecamatan Tibawa</p>
                        <small>Kabupaten Gorontalo Utara</small>
                    </div>
                </div>
                
                <div class="header-info">
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo $_headerTotalDesa; ?> Desa</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-users"></i>
                        <span><?php echo $_headerTotalPenduduk; ?> Jiwa</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <span>Update: <?php echo date('d/m/Y'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </header>