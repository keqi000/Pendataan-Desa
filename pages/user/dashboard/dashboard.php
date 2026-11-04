<?php
require_once __DIR__ . '/../../../config/config.php';

$page_title = 'Dashboard Desa';
$current_page = 'dashboard';
$page_css = BASE_URL . 'pages/user/dashboard/dashboard.css';
$page_js = BASE_URL . 'pages/user/dashboard/dashboard.js';

ob_start();
require_once __DIR__ . '/php/dashboard-view.php';
$content = ob_get_clean();
require_once __DIR__ . '/../../../layout/main.php';
?>