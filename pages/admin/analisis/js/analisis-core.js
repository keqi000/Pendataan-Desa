// Analisis Core Functions
(function() {
    'use strict';
    
    function getBaseUrl() {
        return window.BASE_URL || (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' 
            ? window.location.origin + '/Pendataan-desa/' 
            : window.location.origin + '/');
    }
    
    function showLoader(selector) {
        const element = document.querySelector(selector);
        if (element) {
            element.innerHTML = `
                <div class="loader-container">
                    <div class="loader"></div>
                    <p>Memuat data analisis...</p>
                </div>
            `;
        }
    }
    
    function hideLoader() {
        document.querySelectorAll('.loader-container').forEach(loader => {
            loader.remove();
        });
    }
    
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button onclick="this.parentElement.remove()">&times;</button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
    
    async function loadChartLibrary() {
        if (typeof Chart === 'undefined') {
            return new Promise((resolve) => {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                script.onload = resolve;
                document.head.appendChild(script);
            });
        }
    }
    
    class AnalisisCore {
        constructor() {
            if (document.querySelector('.analisis-admin-container')) {
                this.charts = {};
                this.currentTab = 'tingkat1';
                this.loadedTabs = new Set(['tingkat1']); // Track loaded tabs
                this.init();
            }
        }
        
        init() {
            this.setupTabs();
            this.setupDesaSearch();
            this.setupEventListeners();
            this.initializeActiveTab();
        }
        
        setupTabs() {
            // Global tab switching function with lazy loading
            const self = this;
            window.switchAnalisisTab = async function(tabName) {
                try {
                    const clickedBtn = event?.target;
                    
                    console.log('Switching to tab:', tabName);
                    
                    // Update tab UI
                    document.querySelectorAll('.analisis-admin-tab-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    
                    document.querySelectorAll('.analisis-admin-tab-btn').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    
                    const targetTab = document.getElementById(tabName);
                    if (!targetTab) {
                        console.error(`Tab ${tabName} not found`);
                        return;
                    }
                    
                    targetTab.classList.add('active');
                    if (clickedBtn) clickedBtn.classList.add('active');
                    
                    self.currentTab = tabName;
                    
                    // Force reload for tingkat2 to ensure content loads
                    if (tabName === 'tingkat2') {
                        console.log('Force reloading tingkat2 tab...');
                        self.loadedTabs.delete(tabName);
                    }
                    
                    // Load tab content if not loaded yet
                    if (!self.loadedTabs.has(tabName)) {
                        console.log(`Loading content for ${tabName}...`);
                        await self.loadTabContent(tabName);
                    } else {
                        console.log(`Tab ${tabName} already loaded`);
                    }
                    
                    // Initialize charts for the active tab
                    self.initializeTabCharts(tabName);
                    
                    // Initialize Indeks Ketahanan Ekonomi if needed
                    if (tabName === 'indeks_ekonomi' && window.IndeksKetahananEkonomi) {
                        console.log('Initializing Indeks Ketahanan Ekonomi...');
                        // Reset initialization flag to allow re-init
                        window.IndeksKetahananEkonomi.isInitialized = false;
                        setTimeout(() => {
                            window.IndeksKetahananEkonomi.init();
                        }, 500);
                    }
                    
                    // DISABLE cache handler initialization to prevent conflicts
                    // Cache system in PHP will handle background refresh automatically
                    console.log('Cache handler disabled - PHP cache system handles background refresh');
                    /*
                    if ((tabName === 'tingkat1' || tabName === 'tingkat2') && window.AnalisisCacheHandler && !window.BackgroundRefreshMonitor?.isMonitoring) {
                        const selectedDesa = document.getElementById('selectedDesaId')?.value || 'all';
                        const cacheHandler = new window.AnalisisCacheHandler();
                        cacheHandler.init(tabName, selectedDesa);
                    }
                    */
                } catch (error) {
                    console.error('Error switching tab:', error);
                }
            };
            
            // Bind to instance for direct access
            this.switchAnalisisTab = window.switchAnalisisTab;
        }
        
        async loadTabContent(tabName) {
            const tabElement = document.getElementById(tabName);
            
            // Skip if already loaded and prevent multiple loads
            if (this.loadedTabs.has(tabName) && !this.forceReload) {
                console.log(`Tab ${tabName} already loaded, skipping...`);
                return;
            }
            
            // Prevent multiple simultaneous loads
            if (this.loadingTabs && this.loadingTabs.has(tabName)) {
                console.log(`Tab ${tabName} already loading, skipping...`);
                return;
            }
            
            if (!this.loadingTabs) {
                this.loadingTabs = new Set();
            }
            this.loadingTabs.add(tabName);
            
            console.log(`Loading tab content for: ${tabName}`);
            
            // Show loading state
            tabElement.innerHTML = `
                <div class="tab-loading">
                    <div class="loader-container">
                        <div class="loader"></div>
                        <p>Memuat analisis ${tabName}...</p>
                    </div>
                </div>
            `;
            
            try {
                const baseUrl = getBaseUrl();
                const selectedDesaElement = document.getElementById('selectedDesaId');
                const selectedDesa = selectedDesaElement ? (selectedDesaElement.value || 'all') : 'all';
                
                console.log(`Loading ${tabName} with selectedDesa:`, selectedDesa);
                
                // Special handling for prediksi tab
                if (tabName === 'prediksi') {
                    // For prediksi tab, just mark as loaded and keep existing content
                    this.loadedTabs.add(tabName);
                    this.loadingTabs.delete(tabName);
                    tabElement.setAttribute('data-loaded', 'true');
                    
                    // Remove loading state and restore original content
                    const loadingDiv = tabElement.querySelector('.tab-loading');
                    if (loadingDiv) {
                        loadingDiv.remove();
                    }
                    
                    // If no content exists, show placeholder
                    if (!tabElement.innerHTML.trim() || tabElement.innerHTML.includes('tab-loading')) {
                        tabElement.innerHTML = `
                            <div class="analisis-admin-prediction">
                                <div class="card">
                                    <div class="card-header">
                                        <h3>Prediksi Struktur Penduduk 5 Tahun</h3>
                                        <button class="btn btn-secondary" onclick="generatePredictions()" style="float: right;">
                                            <i class="fas fa-sync"></i> Generate Prediksi
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="no-data">
                                            <i class="fas fa-chart-line fa-2x mb-3 text-muted"></i>
                                            <h6 class="text-muted">Prediksi Struktur Penduduk</h6>
                                            <p class="text-muted">Sistem prediksi menggunakan data penduduk berdasarkan tahun lahir untuk memprediksi struktur penduduk 5 tahun ke depan.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                    return;
                }
                
                const response = await fetch(`${baseUrl}pages/admin/analisis/analisis.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `tab=${tabName}&selected_desa=${selectedDesa}&ajax_load=1&use_cache=1`
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                // Check cache status from headers
                const cacheStatus = response.headers.get('X-Cache-Status');
                const cacheMessage = response.headers.get('X-Cache-Message');
                
                const html = await response.text();
                
                console.log('Tab loading response:', {
                    tabName: tabName,
                    status: response.status,
                    cacheStatus: cacheStatus,
                    contentLength: html.length,
                    preview: html.substring(0, 300)
                });
                
                if (html.trim()) {
                    this.renderTabContent(tabName, html);
                    this.loadedTabs.add(tabName);
                    this.loadingTabs.delete(tabName);
                    
                    // DISABLE background notification to prevent conflicts
                    // Cache system will handle background refresh automatically
                    if (cacheStatus === 'fresh') {
                        showNotification('Data dimuat dari cache (terbaru)', 'success');
                    }
                } else {
                    this.showTabError(tabElement, 'Tidak ada data yang diterima');
                    this.loadingTabs.delete(tabName);
                }
            } catch (error) {
                console.error('Error loading tab:', error);
                this.showTabError(tabElement, `Terjadi kesalahan: ${error.message}`);
                this.loadingTabs.delete(tabName);
            }
        }
        
        renderTabContent(tabName, html) {
            const tabElement = document.getElementById(tabName);
            
            console.log(`Rendering content for tab: ${tabName}`);
            console.log(`HTML length: ${html.length}`);
            console.log(`HTML preview: ${html.substring(0, 200)}...`);
            
            try {
                // Special handling for indeks_ekonomi tab
                if (tabName === 'indeks_ekonomi') {
                    console.log('Special handling for indeks_ekonomi tab');
                    // For indeks_ekonomi, the response should contain the full content
                    if (html.includes('indeks-ekonomi-container')) {
                        console.log('Found indeks-ekonomi-container, using direct HTML');
                        tabElement.innerHTML = html;
                        tabElement.setAttribute('data-loaded', 'true');
                        return;
                    }
                }
                
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // First try to find the specific tab content
                let tabContent = doc.querySelector(`#${tabName}`);
                
                if (tabContent) {
                    console.log(`Found tab content with ID: ${tabName}`);
                    tabElement.innerHTML = tabContent.innerHTML;
                    tabElement.setAttribute('data-loaded', 'true');
                    return;
                }
                
                // If not found, try to extract from full content
                const contentDiv = doc.querySelector('.analisis-admin-content');
                if (contentDiv) {
                    const targetTab = contentDiv.querySelector(`#${tabName}`);
                    if (targetTab) {
                        console.log(`Found tab content in analisis-admin-content`);
                        tabElement.innerHTML = targetTab.innerHTML;
                        tabElement.setAttribute('data-loaded', 'true');
                        return;
                    }
                }
                
                // Special case: if response contains indeks-ekonomi-container directly
                const indeksContainer = doc.querySelector('.indeks-ekonomi-container');
                if (indeksContainer && tabName === 'indeks_ekonomi') {
                    console.log('Found indeks-ekonomi-container in parsed doc');
                    tabElement.innerHTML = html;
                    tabElement.setAttribute('data-loaded', 'true');
                    return;
                }
                
                // If still not found, check if it's a valid HTML response
                const bodyContent = doc.body;
                if (bodyContent && bodyContent.innerHTML.trim()) {
                    console.log('Checking body content...');
                    // Try to find any content that looks like our tab
                    const cards = bodyContent.querySelectorAll('.card');
                    if (cards.length > 0) {
                        console.log(`Found ${cards.length} cards in body`);
                        tabElement.innerHTML = `<div class="analisis-admin-grid">${bodyContent.innerHTML}</div>`;
                        tabElement.setAttribute('data-loaded', 'true');
                        return;
                    }
                    
                    // For indeks_ekonomi, if we have any substantial content, use it
                    if (tabName === 'indeks_ekonomi' && bodyContent.innerHTML.length > 1000) {
                        console.log('Using substantial body content for indeks_ekonomi');
                        tabElement.innerHTML = bodyContent.innerHTML;
                        tabElement.setAttribute('data-loaded', 'true');
                        return;
                    }
                }
                
                console.log('No suitable content found, showing error');
                // Last resort: show error
                this.showTabError(tabElement, 'Konten tab tidak ditemukan dalam respons');
                
            } catch (error) {
                console.error('Error parsing HTML:', error);
                this.showTabError(tabElement, 'Error parsing response');
            }
        }
        
        showTabError(tabElement, message) {
            tabElement.innerHTML = `
                <div style="text-align: center; padding: 50px; color: #dc3545;">
                    <i class="fas fa-exclamation-triangle fa-2x" style="margin-bottom: 16px;"></i>
                    <p>${message}</p>
                    <button class="btn btn-primary" onclick="location.reload()">Muat Ulang</button>
                </div>
            `;
        }
        
        showBackgroundUpdateNotification(message, tabName, selectedDesa) {
            // Create progress notification in top-right corner
            const notification = document.createElement('div');
            notification.className = 'background-update-notification';
            notification.innerHTML = `
                <div class="notification-content">
                    <div class="notification-header">
                        <i class="fas fa-sync fa-spin"></i>
                        <span>Memperbarui Data</span>
                        <button class="close-btn" onclick="this.parentElement.parentElement.parentElement.remove()">&times;</button>
                    </div>
                    <div class="notification-body">
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
                        </div>
                        <p>${message}</p>
                    </div>
                </div>
            `;
            
            // Add styles if not exists
            if (!document.querySelector('#background-update-styles')) {
                const styles = document.createElement('style');
                styles.id = 'background-update-styles';
                styles.textContent = `
                    .background-update-notification {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        width: 300px;
                        background: white;
                        border: 1px solid #ddd;
                        border-radius: 8px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                        z-index: 10000;
                        animation: slideIn 0.3s ease-out;
                    }
                    .notification-content {
                        padding: 15px;
                    }
                    .notification-header {
                        display: flex;
                        align-items: center;
                        margin-bottom: 10px;
                        font-weight: bold;
                        color: #3F72AF;
                    }
                    .notification-header i {
                        margin-right: 8px;
                    }
                    .close-btn {
                        margin-left: auto;
                        background: none;
                        border: none;
                        font-size: 18px;
                        cursor: pointer;
                        color: #999;
                    }
                    .close-btn:hover {
                        color: #333;
                    }
                    .progress-bar {
                        width: 100%;
                        height: 4px;
                        background: #f0f0f0;
                        border-radius: 2px;
                        overflow: hidden;
                        margin-bottom: 8px;
                    }
                    .progress-fill {
                        height: 100%;
                        background: #3F72AF;
                        width: 0%;
                        animation: progressAnimation 10s ease-in-out;
                    }
                    .notification-body p {
                        margin: 0;
                        font-size: 12px;
                        color: #666;
                    }
                    @keyframes slideIn {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                    @keyframes progressAnimation {
                        0% { width: 0%; }
                        50% { width: 70%; }
                        100% { width: 100%; }
                    }
                `;
                document.head.appendChild(styles);
            }
            
            document.body.appendChild(notification);
            
            // Start background refresh check
            this.startBackgroundRefreshCheck(tabName, selectedDesa, notification);
        }
        
        async startBackgroundRefreshCheck(tabName, selectedDesa, notification) {
            let attempts = 0;
            const maxAttempts = 30; // 30 seconds max
            
            const checkInterval = setInterval(async () => {
                attempts++;
                
                try {
                    const baseUrl = getBaseUrl();
                    const response = await fetch(`${baseUrl}pages/admin/analisis/analisis.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: `action=get_calculation_status&tingkat=${tabName}&id_desa=${selectedDesa}`
                    });
                    
                    const result = await response.json();
                    
                    if (result.success && !result.is_calculating) {
                        // Calculation completed, refresh the tab
                        clearInterval(checkInterval);
                        
                        // Update notification to show completion
                        const notificationContent = notification.querySelector('.notification-content');
                        notificationContent.innerHTML = `
                            <div class="notification-header">
                                <i class="fas fa-check-circle" style="color: #28a745;"></i>
                                <span>Data Terbaru</span>
                                <button class="close-btn" onclick="this.parentElement.parentElement.parentElement.remove()">&times;</button>
                            </div>
                            <div class="notification-body">
                                <p>Data berhasil diperbarui</p>
                            </div>
                        `;
                        
                        // Refresh the tab content
                        this.loadedTabs.delete(tabName);
                        await this.loadTabContent(tabName);
                        this.initializeTabCharts(tabName);
                        
                        // Auto-close notification after 3 seconds
                        setTimeout(() => {
                            if (notification.parentElement) {
                                notification.remove();
                            }
                        }, 3000);
                    }
                } catch (error) {
                    console.error('Error checking calculation status:', error);
                }
                
                if (attempts >= maxAttempts) {
                    clearInterval(checkInterval);
                    // Update notification to show timeout
                    const notificationContent = notification.querySelector('.notification-content');
                    notificationContent.innerHTML = `
                        <div class="notification-header">
                            <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i>
                            <span>Timeout</span>
                            <button class="close-btn" onclick="this.parentElement.parentElement.parentElement.remove()">&times;</button>
                        </div>
                        <div class="notification-body">
                            <p>Pembaruan data memakan waktu lebih lama dari biasanya</p>
                        </div>
                    `;
                }
            }, 1000); // Check every second
        }
        
        initializeActiveTab() {
            const activeTab = document.querySelector('.analisis-admin-tab-content.active');
            if (activeTab) {
                const tabId = activeTab.id;
                this.currentTab = tabId;
                
                // Initialize charts for the initially active tab
                setTimeout(() => {
                    this.initializeTabCharts(tabId);
                }, 500);
            }
        }
        
        initializeTabCharts(tabName) {
            // Prevent multiple initializations
            if (this.initializingCharts) {
                console.log('Charts already initializing, skipping...');
                return;
            }
            
            this.initializingCharts = true;
            
            // Wait for Chart.js to load
            if (typeof Chart === 'undefined') {
                console.log('Chart.js not loaded, retrying...');
                setTimeout(() => {
                    this.initializingCharts = false;
                    this.initializeTabCharts(tabName);
                }, 200);
                return;
            }
            
            console.log('Initializing charts for tab:', tabName);
            
            setTimeout(() => {
                if (tabName === 'prediksi' && window.AnalisisPrediksi) {
                    console.log('Initializing prediksi charts...');
                    window.AnalisisPrediksi.init();
                } else if (tabName === 'tingkat1' && window.AnalisisTingkat1) {
                    console.log('Initializing tingkat1 charts...');
                    window.AnalisisTingkat1.init();
                } else if (tabName === 'tingkat2' && window.AnalisisTingkat2) {
                    console.log('Initializing tingkat2 charts...');
                    window.AnalisisTingkat2.init();
                } else if (tabName === 'indeks_ekonomi' && window.IndeksKetahananEkonomi) {
                    console.log('Initializing indeks ekonomi charts...');
                    window.IndeksKetahananEkonomi.init();
                } else {
                    console.log('No chart initializer found for tab:', tabName);
                }
                
                // Cache system handled entirely by PHP - no JavaScript intervention needed
                console.log('Cache system handled by PHP backend');
                
                // DISABLED - Background refresh monitor not needed since PHP handles all cache operations
                /*
                if ((tabName === 'tingkat1' || tabName === 'tingkat2') && window.BackgroundRefreshMonitor) {
                    window.BackgroundRefreshMonitor.currentTab = tabName;
                    const selectedDesa = document.getElementById('selectedDesaId')?.value || 'all';
                    window.BackgroundRefreshMonitor.selectedDesa = selectedDesa;
                }
                */
                
                this.initializingCharts = false;
            }, 500); // Increased delay to ensure DOM is ready
        }
        
        setupDesaSearch() {
            if (typeof window.desaData === 'undefined') {
                window.desaData = [];
            }
            
            document.addEventListener('click', function(e) {
                const dropdown = document.getElementById('autocompleteDropdown');
                if (dropdown && !e.target.closest('.desa-search-container')) {
                    dropdown.style.display = 'none';
                }
            });
            
            this.loadDesaData();
        }
        
        loadDesaData() {
            if (!window.desaData || window.desaData.length === 0) {
                const scriptTags = document.querySelectorAll('script');
                for (let script of scriptTags) {
                    if (script.textContent && script.textContent.includes('window.desaData')) {
                        try {
                            eval(script.textContent);
                            break;
                        } catch (e) {
                            // Silent fail
                        }
                    }
                }
            }
        }
        
        setupEventListeners() {
            document.addEventListener('DOMContentLoaded', () => this.loadDesaData());
            if (document.readyState !== 'loading') {
                this.loadDesaData();
            }
        }
        
        // Method to refresh current tab data
        async refreshCurrentTab() {
            if (this.currentTab) {
                this.forceReload = true;
                this.loadedTabs.delete(this.currentTab);
                if (this.loadingTabs) {
                    this.loadingTabs.delete(this.currentTab);
                }
                await this.loadTabContent(this.currentTab);
                this.initializeTabCharts(this.currentTab);
                this.forceReload = false;
            }
        }
    }
    
    // Global function to load detail analysis
    window.loadDetailAnalysis = async function(detailType, targetElement) {
        const element = document.querySelector(targetElement);
        if (!element) return;
        
        // Show loading state
        element.innerHTML = '<div class="detail-loading">Memuat detail...</div>';
        
        try {
            const baseUrl = getBaseUrl();
            const selectedDesa = document.getElementById('selectedDesaId')?.value || 'all';
            
            const response = await fetch(`${baseUrl}pages/admin/analisis/analisis.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `load_detail=1&detail_type=${detailType}&selected_desa=${selectedDesa}`
            });
            
            const data = await response.json();
            
            if (response.ok && !data.error) {
                // Process and display the detailed data
                displayDetailData(detailType, data, element);
            } else {
                element.innerHTML = '<div class="detail-error">Gagal memuat detail</div>';
            }
        } catch (error) {
            console.error('Error loading detail:', error);
            element.innerHTML = '<div class="detail-error">Terjadi kesalahan</div>';
        }
    };
    
    // Global functions
    window.switchTab = function(tabName) {
        if (window.analisisCoreInstance && window.analisisCoreInstance.switchAnalisisTab) {
            window.analisisCoreInstance.switchAnalisisTab(tabName);
        } else if (window.switchAnalisisTab) {
            window.switchAnalisisTab(tabName);
        }
    };
    
    function displayDetailData(detailType, data, element) {
        let html = '';
        
        switch (detailType) {
            case 'education_job_correlation':
                html = '<h6>Detail Korelasi Pendidikan-Pekerjaan</h6>';
                Object.keys(data).forEach(tingkat => {
                    if (data[tingkat].jumlah > 0) {
                        const rata = Math.round(data[tingkat].total_pendapatan / data[tingkat].jumlah);
                        const pertanian = Math.round((data[tingkat].pertanian / data[tingkat].jumlah) * 100);
                        const nonPertanian = Math.round((data[tingkat].non_pertanian / data[tingkat].jumlah) * 100);
                        html += `<div class="breakdown-item">
                            <span>${tingkat} - Rata-rata: Rp ${rata.toLocaleString()}</span>
                            <span>${pertanian}% Pertanian | ${nonPertanian}% Non-Pertanian</span>
                        </div>`;
                    }
                });
                break;
            case 'non_agriculture':
                html = '<h6>Detail Sektor Non-Pertanian</h6>';
                if (data.total > 0) {
                    Object.keys(data.detail).forEach(sektor => {
                        const persen = Math.round((data.detail[sektor] / data.total) * 100);
                        html += `<div class="breakdown-item">
                            <span>${sektor}</span>
                            <span>${persen}%</span>
                        </div>`;
                    });
                } else {
                    html = '<div class="breakdown-item"><span>Tidak ada data</span><span>-</span></div>';
                }
                break;
            default:
                html = '<div>Detail data berhasil dimuat</div>';
        }
        
        element.innerHTML = html;
    }
    
    window.showAllDesa = function() {
        const dropdown = document.getElementById('autocompleteDropdown');
        if (!dropdown || !window.desaData || window.desaData.length === 0) return;
        
        dropdown.innerHTML = window.desaData.slice(0, 6).map(desa => 
            `<div class="autocomplete-item" onclick="selectDesa('${desa.id}', '${desa.name}')">${desa.name}</div>`
        ).join('');
        dropdown.style.display = 'block';
    };
    
    window.searchDesa = function() {
        const input = document.getElementById('desaSearch');
        const dropdown = document.getElementById('autocompleteDropdown');
        
        if (!input || !dropdown || !window.desaData || window.desaData.length === 0) return;
        
        const query = input.value.toLowerCase();
        
        if (query.length === 0) {
            showAllDesa();
            return;
        }
        
        const filtered = window.desaData.filter(desa => 
            desa.name.toLowerCase().includes(query)
        );
        
        if (filtered.length === 0) {
            dropdown.style.display = 'none';
            return;
        }
        
        dropdown.innerHTML = filtered.slice(0, 6).map(desa => 
            `<div class="autocomplete-item" onclick="selectDesa('${desa.id}', '${desa.name}')">${desa.name}</div>`
        ).join('');
        
        dropdown.style.display = 'block';
    };
    
    window.selectDesa = function(id, name) {
        document.getElementById('desaSearch').value = name;
        document.getElementById('selectedDesaId').value = id;
        document.getElementById('autocompleteDropdown').style.display = 'none';
        showNotification(`${name} dipilih. Klik Generate untuk memuat data.`, 'info');
    };
    
    window.generateAnalysis = function() {
        const selectedDesa = document.getElementById('selectedDesaId').value;
        
        if (!selectedDesa) {
            showNotification('Pilih desa terlebih dahulu', 'warning');
            return;
        }
        
        showLoader('.analisis-admin-tab-content.active');
        showNotification('Memperbarui cache analisis...', 'info');
        
        const requestBody = `selected_desa=${selectedDesa}&action=generate_analysis`;
        
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: requestBody
        })
        .then(response => response.json())
        .then(data => {
            hideLoader();
            if (data.success) {
                // Clear loaded tabs cache to force reload
                if (window.analisisCoreInstance) {
                    window.analisisCoreInstance.loadedTabs.clear();
                }
                
                showNotification(`Analisis berhasil diperbarui! Terakhir update: ${data.last_update}`, 'success');
                
                // Force reload page to show fresh data
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification(data.message || 'Terjadi kesalahan saat memperbarui analisis', 'error');
            }
        })
        .catch(error => {
            hideLoader();
            console.error('Error:', error);
            showNotification('Terjadi kesalahan saat memperbarui analisis', 'error');
        });
    };
    
    // Export utilities
    window.AnalisisUtils = {
        getBaseUrl,
        showLoader,
        hideLoader,
        showNotification,
        loadChartLibrary
    };
    
    // Initialize core
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.analisisCoreInstance) {
            window.analisisCoreInstance = new AnalisisCore();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (!window.analisisCoreInstance) {
            window.analisisCoreInstance = new AnalisisCore();
        }
    }
})();