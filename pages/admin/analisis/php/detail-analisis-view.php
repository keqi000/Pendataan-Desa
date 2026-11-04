<?php
require_once __DIR__ . '/detail-analisis-functions.php';

$detailAnalisisFunctions = new DetailAnalisisFunctions();
$detailAnalisisFunctions->checkAuthentication();

// Handle AJAX requests
if ($detailAnalisisFunctions->isAjaxRequest() && isset($_GET['action'])) {
    $detailAnalisisFunctions->handleAjaxRequest();
    exit();
}

$all_desa = $detailAnalisisFunctions->getAllDesa();
$selected_desa = $_GET['desa'] ?? 'all';
$analysis_type = $_GET['type'] ?? 'kependudukan';

// Handle case where desa parameter is actually an analysis type
if (in_array($selected_desa, ['kependudukan', 'ekonomi', 'integrasi', 'spasial'])) {
    $analysis_type = $selected_desa;
    $selected_desa = 'all';
}

$filtered_desa = $detailAnalisisFunctions->getFilteredDesa($selected_desa, $all_desa);

$is_ajax = $detailAnalisisFunctions->isAjaxRequest();

if (!$is_ajax) {
    $page_title = 'Detail Analisis Tingkat 2';
    $current_page = 'analisis';
    $page_css = [BASE_URL . 'pages/admin/analisis/css/detail-analisis.css'];
    $page_js = [BASE_URL . 'pages/admin/analisis/js/detail-analisis.js'];
    
    // Debug: pastikan file CSS dan JS ada
    $css_file = __DIR__ . '/../css/detail-analisis.css';
    $js_file = __DIR__ . '/../js/detail-analisis.js';
    
    if (!file_exists($css_file)) {
        error_log('CSS file not found: ' . $css_file);
    }
    if (!file_exists($js_file)) {
        error_log('JS file not found: ' . $js_file);
    }
    
    ob_start();
}
?>

<div class="detail-analisis-container">
    <div class="detail-analisis-header">
        <div class="detail-analisis-title">
            <h2>Detail Analisis Tingkat 2</h2>
            <p>Data detail untuk <?php 
            if ($selected_desa === 'all') {
                echo 'Semua Desa';
            } else {
                foreach ($all_desa as $desa) {
                    if ($desa['id_desa'] == $selected_desa) {
                        echo $desa['nama_desa'];
                        break;
                    }
                }
            }
            ?></p>
        </div>
        <div class="detail-analisis-breadcrumb">
            <a href="<?php echo BASE_URL; ?>pages/admin/analisis/analisis.php">← Kembali ke Analisis</a>
        </div>
    </div>

    <div class="detail-analisis-tabs">
        <div class="detail-analisis-tab-nav">
            <button class="detail-analisis-tab-btn <?php echo $analysis_type === 'kependudukan' ? 'active' : ''; ?>" 
                    data-type="kependudukan">
                Analisis Kependudukan Lanjutan
            </button>
            <button class="detail-analisis-tab-btn <?php echo $analysis_type === 'ekonomi' ? 'active' : ''; ?>" 
                    data-type="ekonomi">
                Analisis Ekonomi Lanjutan
            </button>
            <button class="detail-analisis-tab-btn <?php echo $analysis_type === 'integrasi' ? 'active' : ''; ?>" 
                    data-type="integrasi">
                Analisis Integrasi Data
            </button>
            <button class="detail-analisis-tab-btn <?php echo $analysis_type === 'spasial' ? 'active' : ''; ?>" 
                    data-type="spasial">
                Analisis Spasial & Prediktif
            </button>
        </div>
    </div>

    <div class="detail-analisis-content">
        <div class="tab-loading" id="tabLoading" style="display: none;">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Memuat data analisis...</p>
            </div>
        </div>
        
        <div class="tab-content" id="tabContent">
            <?php 
            // Set flag to indicate this is included from view
            $included_from_view = true;
            // Variables are already set: $analysis_type, $selected_desa, $filtered_desa
            include __DIR__ . '/detail-analisis-ajax.php';
            ?>
        </div>
    </div>
</div>

<script>
// Inline fallback untuk memastikan lazy loading berfungsi
if (typeof DetailAnalisis === 'undefined') {
    console.log('DetailAnalisis not loaded, using fallback');
    
    window.DetailAnalisis = {
        switchAnalysisType: function(type) {
            console.log('Fallback switchAnalysisType called for:', type);
            const urlParams = new URLSearchParams(window.location.search);
            const currentDesa = urlParams.get('desa') || 'all';
            const newUrl = window.location.pathname + '?desa=' + currentDesa + '&type=' + type;
            window.location.href = newUrl;
        }
    };
}

function switchAnalysisType(type) {
    console.log('switchAnalysisType called for:', type);
    
    if (window.detailAnalisisInstance && window.detailAnalisisInstance.switchAnalysisType) {
        console.log('Using detailAnalisisInstance');
        window.detailAnalisisInstance.switchAnalysisType(type);
    } else if (window.DetailAnalisis && window.DetailAnalisis.switchAnalysisType) {
        console.log('Using DetailAnalisis fallback');
        window.DetailAnalisis.switchAnalysisType(type);
    } else {
        console.log('Using basic URL change');
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('type', type);
        window.location.search = urlParams.toString();
    }
}

function goBackToAnalisis() {
    window.location.href = window.location.origin + '/Pendataan-desa/pages/admin/analisis/analisis.php';
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, checking for DetailAnalisis');
    
    // Wait a bit for external JS to load
    setTimeout(function() {
        if (typeof DetailAnalisis !== 'undefined' && document.querySelector('.detail-analisis-container')) {
            console.log('Initializing DetailAnalisis');
            window.detailAnalisisInstance = DetailAnalisis;
            DetailAnalisis.init();
        } else {
            console.log('DetailAnalisis not available, using basic functionality');
            
            // Basic tab switching without lazy loading
            const tabButtons = document.querySelectorAll('.detail-analisis-tab-btn');
            tabButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const type = button.getAttribute('data-type');
                    if (type) {
                        switchAnalysisType(type);
                    }
                });
            });
        }
    }, 200);
});
</script>

<?php
if (!$is_ajax) {
    $content = ob_get_clean();
    require_once __DIR__ . '/../../../../layout/main.php';
} else {
    $content = ob_get_clean();
    echo $content;
    exit();
}
?>