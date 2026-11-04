<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';

// Initialize and validate session
if (!initSession() || !validateSession()) {
    header('Location: ' . BASE_URL . 'pages/auth/login.php');
    exit();
}

$page_title = $page_title ?? 'Sistem Pendataan Desa';
$page_class = $page_class ?? '';
$current_page = $current_page ?? '';
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo IMG_URL; ?>favicon.ico">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Global CSS -->
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>global.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>no-data.css">
    
    <!-- Layout Component CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>layout/header/header.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>layout/sidebar/sidebar.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>layout/footer/footer.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>layout/main-layout.css">
    
    <!-- Page specific CSS -->
    <?php if (isset($page_css)): ?>
        <?php if (is_array($page_css)): ?>
            <?php foreach ($page_css as $css): ?>
                <link rel="stylesheet" href="<?php echo $css; ?>">
            <?php endforeach; ?>
        <?php else: ?>
            <link rel="stylesheet" href="<?php echo $page_css; ?>">
        <?php endif; ?>
    <?php endif; ?>
</head>
<body class="main-layout-body <?php echo $page_class; ?>">
    <!-- Page Loader -->
    <div class="page-loader">
        <div class="loader-spinner"></div>
    </div>
    
    <!-- Main Layout Container -->
    <div class="main-layout-container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/sidebar/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <div class="main-layout-content" id="mainContent">
            <!-- Header -->
            <?php include __DIR__ . '/header/header.php'; ?>
            
            <!-- Content Area with Loader -->
            <main class="main-layout-main" id="contentArea">
                <!-- Content Loader -->
                <div class="content-loader hidden" id="contentLoader">
                    <div class="loader-spinner"></div>
                </div>
                
                <!-- Dynamic Content -->
                <div class="main-layout-content-wrapper">
                    <?php echo $content; ?>
                </div>
            </main>
            
            <!-- Footer -->
            <?php include __DIR__ . '/footer/footer.php'; ?>
        </div>
    </div>
    
    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Global JavaScript -->
    <script>
        // Pass BASE_URL to JavaScript
        window.BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <script src="<?php echo JS_URL; ?>theme.js"></script>
    <script src="<?php echo JS_URL; ?>global.js"></script>
    
    <!-- Layout Component JavaScript -->
    <script src="<?php echo BASE_URL; ?>layout/header/header.js"></script>
    <script src="<?php echo BASE_URL; ?>layout/sidebar/sidebar.js"></script>
    <script src="<?php echo BASE_URL; ?>layout/footer/footer.js"></script>
    <script src="<?php echo BASE_URL; ?>layout/main-layout.js"></script>
    
    <!-- Page specific JavaScript -->
    <?php if (isset($page_js)): ?>
        <?php if (is_array($page_js)): ?>
            <?php foreach ($page_js as $js): ?>
                <script src="<?php echo $js; ?>"></script>
            <?php endforeach; ?>
        <?php else: ?>
            <script src="<?php echo $page_js; ?>"></script>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>