<?php
require_once __DIR__ . '/../../../config/config.php';

// Handle AJAX request for desa overview
if (isset($_GET['action']) && $_GET['action'] === 'get_desa_overview') {
    require_once __DIR__ . '/php/dashboard-functions.php';
    
    header('Content-Type: application/json');
    
    $dashboardFunctions = new DashboardFunctions();
    $desaId = $_GET['id'] ?? null;
    
    if ($desaId) {
        try {
            $overview = $dashboardFunctions->getDesaOverview($desaId);
            echo json_encode([
                'success' => true,
                'data' => $overview
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'ID desa tidak ditemukan'
        ]);
    }
    exit;
}

// Handle AJAX request for trend data
if (isset($_GET['action']) && $_GET['action'] === 'get_trend_data') {
    require_once __DIR__ . '/php/dashboard-functions.php';
    
    header('Content-Type: application/json');
    
    $dashboardFunctions = new DashboardFunctions();
    $year = $_GET['year'] ?? date('Y');
    $period = $_GET['period'] ?? 'jan-jun';
    
    try {
        $result = $dashboardFunctions->getTrendDataAPI($year, $period);
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

$page_title = 'Dashboard Admin';
$current_page = 'dashboard';
$page_css = BASE_URL . 'pages/admin/dashboard/dashboard.css';
$page_js = [
    BASE_URL . 'pages/admin/dashboard/js/dashboard-core.js',
    BASE_URL . 'pages/admin/dashboard/js/dashboard-charts.js', 
    BASE_URL . 'pages/admin/dashboard/js/dashboard-overview.js',
    BASE_URL . 'pages/admin/dashboard/dashboard.js'
];

ob_start();
require_once __DIR__ . '/php/dashboard-view.php';
$content = ob_get_clean();
require_once __DIR__ . '/../../../layout/main.php';
?>