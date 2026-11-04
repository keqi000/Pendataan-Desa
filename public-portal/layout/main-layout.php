<?php
// Main Layout untuk Sistem Informasi Publik
// File: public-portal/layout/main-layout.php

// Include konfigurasi
require_once __DIR__ . '/../config/base-url.php';

// Set default values jika tidak didefinisikan
$page_title = $page_title ?? 'Portal Data Terbuka';
$current_page = $current_page ?? '';
$page_css = $page_css ?? [];
$page_js = $page_js ?? [];

// Include header
require_once __DIR__ . '/header/header.php';

// Include navbar
require_once __DIR__ . '/navbar/navbar.php';
?>

<!-- Main Content Area -->
<main class="main-content">
    <?php
    // Content akan diisi oleh halaman yang menggunakan layout ini
    if (isset($content)) {
        echo $content;
    }
    ?>
</main>

<?php
// Include footer
require_once __DIR__ . '/footer/footer.php';
?>