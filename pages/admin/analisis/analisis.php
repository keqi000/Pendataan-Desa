<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/php/analisis-functions.php';
require_once __DIR__ . '/php/analisis-cache-manager.php';
require_once __DIR__ . '/php/indeks-ketahanan-ekonomi.php';

if (isset($_GET['endpoint'])) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    header('Content-Type: application/json');
    $analisisFunctions = new AnalisisFunctions();
    echo $analisisFunctions->handleApiRequest();
    exit();
}

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin_kecamatan') {
    header('Location: ' . BASE_URL . 'pages/auth/login.php');
    exit();
}

$queries = new Queries();
$all_desa = $queries->getAllDesa();

// Initialize cache manager
$cacheManager = new AnalisisCacheManager();

// Debug endpoint to check cache content
if (isset($_GET['debug_cache'])) {
    $selected_desa = $_GET['id_desa'] ?? 'all';
    $tingkat = $_GET['tingkat'] ?? 'tingkat1';
    
    $cached_data = $cacheManager->getAnalisisData($tingkat, $selected_desa, false);
    
    header('Content-Type: application/json');
    echo json_encode([
        'cache_data' => $cached_data,
        'selected_desa' => $selected_desa,
        'tingkat' => $tingkat
    ], JSON_PRETTY_PRINT);
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'generate_analysis') {
    $selected_desa = $_POST['selected_desa'] ?? '';
    $_SESSION['selected_desa'] = $selected_desa;
    
    // Force clear existing cache first
    try {
        $cacheManager->clearSpecificCache('tingkat1', $selected_desa);
        $cacheManager->clearSpecificCache('tingkat2', $selected_desa);
        
        // Force recalculate and update cache
        $result1 = $cacheManager->calculateAndCache('tingkat1', $selected_desa);
        $result2 = $cacheManager->calculateAndCache('tingkat2', $selected_desa);
        $cacheManager->clearSpecificCache('tingkat1', $selected_desa);
        $cacheManager->clearSpecificCache('tingkat2', $selected_desa);
        
        // Force recalculate and update cache
        $result1 = $cacheManager->calculateAndCache('tingkat1', $selected_desa);
        $result2 = $cacheManager->calculateAndCache('tingkat2', $selected_desa);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Analisis berhasil diperbarui',
            'last_update' => date('Y-m-d H:i:s'),
            'debug' => [
                'tingkat1_calculated' => !$result1['from_cache'],
                'tingkat2_calculated' => !$result2['from_cache'],
                'selected_desa' => $selected_desa
            ]
        ]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    exit();
}

// Handle detail analysis loading
if (isset($_POST['load_detail']) && isset($_POST['detail_type'])) {
    $selected_desa = $_SESSION['selected_desa'] ?? 'all';
    // PERBAIKAN: Jika selected_desa kosong, set ke 'all'
    if (empty($selected_desa)) {
        $selected_desa = 'all';
        $_SESSION['selected_desa'] = 'all';
    }
    
    if ($selected_desa === 'all') {
        $filtered_desa = $all_desa;
    } elseif (!empty($selected_desa)) {
        $filtered_desa = array_filter($all_desa, function ($desa) use ($selected_desa) {
            return $desa['id_desa'] == $selected_desa;
        });
    } else {
        $filtered_desa = $all_desa;
    }
    
    $analisis = new AnalisisFunctions();
    $desa_data = $analisis->preloadDesaData($filtered_desa);
    
    $detail_type = $_POST['detail_type'];
    $result = [];
    
    switch ($detail_type) {
        case 'education_job_correlation':
            $result = $analisis->calculateEducationJobCorrelation();
            break;
        case 'non_agriculture':
            $result = $analisis->calculateNonAgricultureBreakdown();
            break;
        case 'integrated_analysis':
            $result = $analisis->calculateIntegratedAnalysis();
            break;
        case 'spatial_predictive':
            $result = $analisis->calculateSpatialPredictive();
            break;
        case 'economic_advanced_full':
            $result = $analisis->calculateEconomicAdvanced();
            break;
        default:
            $result = ['error' => 'Invalid detail type'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit();
}
// Handle Indeks Ketahanan Ekonomi calculation
if (isset($_POST['ajax_load']) && isset($_POST['tab']) && $_POST['tab'] === 'indeks_ekonomi') {
    $selected_desa = $_SESSION['selected_desa'] ?? '';
    if ($selected_desa === 'all') {
        $filtered_desa = $all_desa;
    } elseif (!empty($selected_desa)) {
        $filtered_desa = array_filter($all_desa, function ($desa) use ($selected_desa) {
            return $desa['id_desa'] == $selected_desa;
        });
    } else {
        $filtered_desa = $all_desa;
    }
    
    $indeks_ekonomi = new IndeksKetahananEkonomi();
    $indeks_ekonomi->preloadDesaData($filtered_desa);
    $hasil_indeks = $indeks_ekonomi->calculateIndeksKetahananEkonomi();
    
    ob_start();
    $is_ajax_tab_load = true;
    require_once __DIR__ . '/php/indeks-ekonomi-view.php';
    $content = ob_get_clean();
    echo $content;
    exit();
}

// Handle AJAX tab loading
if (isset($_POST['ajax_load']) && isset($_POST['tab'])) {
    $selected_desa = $_SESSION['selected_desa'] ?? 'all';
    // PERBAIKAN: Jika selected_desa kosong, set ke 'all'
    if (empty($selected_desa)) {
        $selected_desa = 'all';
        $_SESSION['selected_desa'] = 'all';
    }
    
    if ($selected_desa === 'all') {
        $filtered_desa = $all_desa;
    } elseif (!empty($selected_desa)) {
        $filtered_desa = array_filter($all_desa, function ($desa) use ($selected_desa) {
            return $desa['id_desa'] == $selected_desa;
        });
    } else {
        $filtered_desa = $all_desa;
    }
    
    $tab = $_POST['tab'];
    
    // ALWAYS try to get from cache first for tingkat1 and tingkat2
    if ($tab === 'tingkat1' || $tab === 'tingkat2') {
        $use_cache = isset($_POST['use_cache']) ? (bool)$_POST['use_cache'] : true;
        
        if ($use_cache) {
            $cached_result = $cacheManager->getAnalisisData($tab, $selected_desa);
            
            if ($cached_result['from_cache']) {
                // Use cached data - always fresh since we only update via Generate button
                if ($tab === 'tingkat1') {
                    $demographic_stats = $cached_result['data']['demographic_stats'] ?? [];
                    $economic_stats = $cached_result['data']['economic_stats'] ?? [];
                    $umkm_stats = $cached_result['data']['umkm_stats'] ?? [];
                    $poverty_stats = $cached_result['data']['poverty_stats'] ?? [];
                    $education_stats = $cached_result['data']['education_stats'] ?? [];
                    $capacity_per_level = $cached_result['data']['capacity_per_level'] ?? [];
                    $infrastructure_stats = $cached_result['data']['infrastructure_stats'] ?? [];
                    $spatial_analysis = $cached_result['data']['spatial_analysis'] ?? [];
                    
                    $advanced_demographics = [];
                    $education_job_correlation = [];
                    $income_gap = [];
                    $non_agriculture = [];
                    $integrated_analysis = [];
                    $spatial_predictive = [];
                    $income_distribution = [];
                    $economic_advanced = [];
                } elseif ($tab === 'tingkat2') {
                    $advanced_demographics = $cached_result['data']['advanced_demographics'] ?? [];
                    $economic_advanced = $cached_result['data']['economic_advanced'] ?? [];
                    $education_job_correlation = $cached_result['data']['education_job_correlation'] ?? [];
                    $income_gap = $cached_result['data']['income_gap'] ?? [];
                    $non_agriculture = $cached_result['data']['non_agriculture'] ?? [];
                    $integrated_analysis = $cached_result['data']['integrated_analysis'] ?? [];
                    $spatial_predictive = $cached_result['data']['spatial_predictive'] ?? [];
                    $income_distribution = $cached_result['data']['income_distribution'] ?? [];
                    
                    $demographic_stats = [];
                    $economic_stats = [];
                    $umkm_stats = [];
                    $poverty_stats = [];
                    $education_stats = [];
                    $capacity_per_level = [];
                    $infrastructure_stats = [];
                    $spatial_analysis = [];
                }
                
                // Add cache metadata
                $cache_metadata = [
                    'age_seconds' => $cached_result['age_seconds'] ?? 0,
                    'last_update' => $cached_result['last_update'] ?? null
                ];
                
                header('X-Cache-Status: cached');
                
                ob_start();
                $is_ajax_tab_load = true;
                // Don't show cache status in AJAX responses to avoid duplication
                $hide_cache_status = true;
                require_once __DIR__ . '/php/analisis-view.php';
                $content = ob_get_clean();
                echo $content;
                exit();
            }
        }
    }
    
    // If no cache or cache disabled, calculate normally
    // Initialize analysis functions
    $analisis = new AnalisisFunctions();
    $desa_data = $analisis->preloadDesaData($filtered_desa);
    
    // Calculate only required statistics based on tab
    if ($tab === 'tingkat1') {
        $demographic_stats = $analisis->calculateDemographicStats();
        $economic_stats = $analisis->calculateEconomicStats();
        $umkm_stats = $analisis->calculateUMKMStats();
        $poverty_stats = $analisis->calculatePovertyStats();
        $education_stats = $analisis->calculateEducationStats();
        $capacity_per_level = $analisis->calculateCapacityPerLevel();
        $infrastructure_stats = $analisis->calculateInfrastructureStats();
        $spatial_analysis = $analisis->calculateSpatialAnalysis();
        
        // Save to cache after calculation
        try {
            $data_to_cache = [
                'demographic_stats' => $demographic_stats,
                'economic_stats' => $economic_stats,
                'umkm_stats' => $umkm_stats,
                'poverty_stats' => $poverty_stats,
                'education_stats' => $education_stats,
                'capacity_per_level' => $capacity_per_level,
                'infrastructure_stats' => $infrastructure_stats,
                'spatial_analysis' => $spatial_analysis
            ];
            $cacheManager->saveToCache('tingkat1', $selected_desa, $data_to_cache);
        } catch (Exception $e) {
            error_log('Error saving tingkat1 cache: ' . $e->getMessage());
        }
        
        // Initialize empty arrays for other tabs
        $advanced_demographics = [];
        $education_job_correlation = [];
        $income_gap = [];
        $non_agriculture = [];
        $integrated_analysis = [];
        $spatial_predictive = [];
        $income_distribution = [];
        $economic_advanced = [];
    } elseif ($tab === 'tingkat2') {
        // Calculate all tingkat2 data including breakdowns
        $advanced_demographics = $analisis->calculateAdvancedDemographics();
        $income_gap = $analisis->calculateIncomeGap();
        $income_distribution = $analisis->calculateIncomeDistribution();
        $economic_advanced = $analisis->calculateEconomicAdvanced();
        $education_job_correlation = $analisis->calculateEducationJobCorrelation();
        $non_agriculture = $analisis->calculateNonAgricultureBreakdown();
        $integrated_analysis = $analisis->calculateIntegratedAnalysis();
        $spatial_predictive = $analisis->calculateSpatialPredictive();
        
        // Save to cache after calculation
        try {
            $data_to_cache = [
                'advanced_demographics' => $advanced_demographics,
                'economic_advanced' => $economic_advanced,
                'education_job_correlation' => $education_job_correlation,
                'income_gap' => $income_gap,
                'non_agriculture' => $non_agriculture,
                'integrated_analysis' => $integrated_analysis,
                'spatial_predictive' => $spatial_predictive,
                'income_distribution' => $income_distribution
            ];
            $cacheManager->saveToCache('tingkat2', $selected_desa, $data_to_cache);
        } catch (Exception $e) {
            error_log('Error saving tingkat2 cache: ' . $e->getMessage());
        }
        
        // Initialize empty arrays for tingkat1
        $demographic_stats = [];
        $economic_stats = [];
        $umkm_stats = [];
        $poverty_stats = [];
        $education_stats = [];
        $capacity_per_level = [];
        $infrastructure_stats = [];
        $spatial_analysis = [];
    } elseif ($tab === 'prediksi') {
        // For prediksi tab, return minimal response since it's handled differently
        echo json_encode(['status' => 'success', 'message' => 'Prediksi tab loaded']);
        exit();
    } else {
        // Initialize empty arrays for unknown tabs
        $demographic_stats = [];
        $economic_stats = [];
        $umkm_stats = [];
        $poverty_stats = [];
        $education_stats = [];
        $capacity_per_level = [];
        $infrastructure_stats = [];
        $spatial_analysis = [];
        $advanced_demographics = [];
        $education_job_correlation = [];
        $income_gap = [];
        $non_agriculture = [];
        $integrated_analysis = [];
        $spatial_predictive = [];
        $income_distribution = [];
        $economic_advanced = [];
    }
    
    ob_start();
    
    // Set flag untuk AJAX loading agar tidak render container penuh
    $is_ajax_tab_load = true;
    
    require_once __DIR__ . '/php/analisis-view.php';
    $content = ob_get_clean();
    
    echo $content;
    exit();
}

$selected_desa = $_SESSION['selected_desa'] ?? 'all';
// PERBAIKAN: Jika selected_desa kosong, set ke 'all'
if (empty($selected_desa)) {
    $selected_desa = 'all';
    $_SESSION['selected_desa'] = 'all';
}

if ($selected_desa === 'all') {
    $filtered_desa = $all_desa;
} elseif (!empty($selected_desa)) {
    $filtered_desa = array_filter($all_desa, function ($desa) use ($selected_desa) {
        return $desa['id_desa'] == $selected_desa;
    });
} else {
    $filtered_desa = $all_desa;
}

// Initialize analysis functions
$analisis = new AnalisisFunctions();
$desa_data = $analisis->preloadDesaData($filtered_desa);

// Try to get tingkat1 from cache first for initial load
$cached_tingkat1 = $cacheManager->getAnalisisData('tingkat1', $selected_desa);

if ($cached_tingkat1['from_cache']) {
    // Use cached data for initial load
    $demographic_stats = $cached_tingkat1['data']['demographic_stats'] ?? [];
    $economic_stats = $cached_tingkat1['data']['economic_stats'] ?? [];
    $umkm_stats = $cached_tingkat1['data']['umkm_stats'] ?? [];
    $poverty_stats = $cached_tingkat1['data']['poverty_stats'] ?? [];
    $education_stats = $cached_tingkat1['data']['education_stats'] ?? [];
    $capacity_per_level = $cached_tingkat1['data']['capacity_per_level'] ?? [];
    $infrastructure_stats = $cached_tingkat1['data']['infrastructure_stats'] ?? [];
    $spatial_analysis = $cached_tingkat1['data']['spatial_analysis'] ?? [];
    
    // Mark that we're using cache for initial load
    $using_cache_initial = true;
    $cache_status = $cached_tingkat1;
} else {
    // No cache available, must calculate (this will be slower)
    $using_cache_initial = false;
    $cache_status = null;
    
    // Calculate only tingkat1 statistics for initial load if no cache
    $demographic_stats = $analisis->calculateDemographicStats();
    $economic_stats = $analisis->calculateEconomicStats();
    $umkm_stats = $analisis->calculateUMKMStats();
    $poverty_stats = $analisis->calculatePovertyStats();
    $education_stats = $analisis->calculateEducationStats();
    $capacity_per_level = $analisis->calculateCapacityPerLevel();
    $infrastructure_stats = $analisis->calculateInfrastructureStats();
    $spatial_analysis = $analisis->calculateSpatialAnalysis();
    
    // Save to cache after calculation
    try {
        $data_to_cache = [
            'demographic_stats' => $demographic_stats,
            'economic_stats' => $economic_stats,
            'umkm_stats' => $umkm_stats,
            'poverty_stats' => $poverty_stats,
            'education_stats' => $education_stats,
            'capacity_per_level' => $capacity_per_level,
            'infrastructure_stats' => $infrastructure_stats,
            'spatial_analysis' => $spatial_analysis
        ];
        $cacheManager->saveToCache('tingkat1', $selected_desa, $data_to_cache);
    } catch (Exception $e) {
        error_log('Error saving tingkat1 cache on initial load: ' . $e->getMessage());
    }
}

// Initialize empty arrays for other tabs to prevent errors
$advanced_demographics = [];
$education_job_correlation = [];
$income_gap = [];
$non_agriculture = [];
$integrated_analysis = [];
$spatial_predictive = [];
$income_distribution = [];
$economic_advanced = [];

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    $page_title = 'Analisis Data';
    $current_page = 'analisis';
    $page_css = [
        BASE_URL . 'pages/admin/analisis/css/analisis.css', 
        BASE_URL . 'pages/admin/analisis/css/analisis-cache.css',
        BASE_URL . 'pages/admin/analisis/css/analisis-progress.css',
        BASE_URL . 'pages/admin/analisis/css/indeks-ketahanan-ekonomi.css'
    ];
    $page_js = [
        BASE_URL . 'pages/admin/analisis/js/analisis-core.js',
        BASE_URL . 'pages/admin/analisis/js/analisis-tingkat1.js', 
        BASE_URL . 'pages/admin/analisis/js/analisis-tingkat2.js',
        BASE_URL . 'pages/admin/analisis/js/analisis-prediksi.js',
        BASE_URL . 'pages/admin/analisis/js/analisis-detail-navigation.js',
        BASE_URL . 'pages/admin/analisis/js/indeks-ketahanan-ekonomi.js',
        BASE_URL . 'pages/admin/analisis/analisis.js'
    ];
    ob_start();
}

require_once __DIR__ . '/php/analisis-view.php';

if (!$is_ajax) {
    $content = ob_get_clean();
    require_once __DIR__ . '/../../../layout/main.php';
}
?>