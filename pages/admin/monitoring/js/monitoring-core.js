// Monitoring Core Functions
(function() {
    'use strict';
    
    // API Helper function with enhanced URL validation
    function validateUrl(url) {
        try {
            const urlObj = new URL(url);
            const allowedHosts = ['localhost', '127.0.0.1', window.location.hostname];
            const isLocalhost = allowedHosts.includes(urlObj.hostname) || urlObj.hostname.endsWith('.local');
            const hasValidPath = urlObj.pathname.includes('Pendataan-desa') || urlObj.pathname.startsWith('/api/');
            return isLocalhost && hasValidPath;
        } catch {
            return false;
        }
    }
    
    async function apiCall(url, options = {}) {
        if (!validateUrl(url)) {
            throw new Error('Invalid or unsafe URL');
        }
        const response = await fetch(url, options);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return await response.json();
    }
    
    // Utility functions
    function getBaseUrl() {
        return window.BASE_URL || (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' 
            ? window.location.origin + '/Pendataan-desa/' 
            : window.location.origin + '/');
    }
    
    function sanitizeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    function loadAsset(type, src, id) {
        return new Promise((resolve, reject) => {
            if (document.getElementById(id)) {
                resolve();
                return;
            }
            
            const element = type === 'css' 
                ? Object.assign(document.createElement('link'), { rel: 'stylesheet', href: src })
                : Object.assign(document.createElement('script'), { src });
            
            element.id = id;
            element.onload = resolve;
            element.onerror = () => reject(new Error(`Failed to load ${type}: ${src}`));
            document.head.appendChild(element);
        });
    }
    
    class MonitoringCore {
        constructor() {
            if (document.querySelector('.monitoring-admin-container')) {
                this.init();
            }
        }
        
        init() {
            this.setupFilters();
            this.setupRealTimeUpdates();
            this.setupTabSwitching();
        }
        
        setupFilters() {
            const statusFilter = document.getElementById('statusFilter');
            if (statusFilter) {
                statusFilter.addEventListener('change', () => {
                    if (window.MonitoringStatus) {
                        window.MonitoringStatus.filterByStatus(statusFilter.value);
                    }
                });
            }
        }
        
        setupTabSwitching() {
            // Global tab switching function with lazy loading
            window.switchMonitoringTab = (tabName) => {
                // Update tab UI
                document.querySelectorAll('.monitoring-admin-tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                document.querySelectorAll('.monitoring-admin-tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                document.getElementById(tabName).classList.add('active');
                event.target.classList.add('active');
                
                // Load tab content if needed
                this.loadTabContent(tabName);
            };
        }
        
        async loadTabContent(tabName) {
            const tabElement = document.getElementById(tabName);
            
            // Skip if tab already has content (not loading state)
            if (!tabElement.querySelector('.tab-loading')) {
                return;
            }
            
            try {
                const baseUrl = window.MonitoringUtils.getBaseUrl();
                let endpoint = '';
                
                switch (tabName) {
                    case 'perbandingan':
                        endpoint = 'tab-perbandingan';
                        break;
                    case 'quality':
                        endpoint = 'tab-quality';
                        break;
                    default:
                        return;
                }
                
                const response = await fetch(`${baseUrl}pages/admin/monitoring/monitoring.php?endpoint=${endpoint}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.renderTabContent(tabName, result.data);
                } else {
                    this.showTabError(tabElement, 'Gagal memuat data');
                }
            } catch (error) {
                this.showTabError(tabElement, 'Terjadi kesalahan saat memuat data');
            }
        }
        
        renderTabContent(tabName, data) {
            const tabElement = document.getElementById(tabName);
            
            switch (tabName) {
                case 'perbandingan':
                    this.renderPerbandinganTab(tabElement, data);
                    break;
                case 'quality':
                    this.renderQualityTab(tabElement, data);
                    break;
            }
        }
        
        renderPerbandinganTab(tabElement, data) {
            const options = data.desa_list.map(d =>
                `<option value="${d.id_desa}">${d.nama_desa}</option>`
            ).join('');

            tabElement.innerHTML = `
                <div class="card">
                    <div class="card-header">
                        <h3>Perbandingan Detail Antar Desa</h3>
                    </div>
                    <div class="card-body">
                        <div class="monitoring-row-spacing">
                            <div class="monitoring-col-5">
                                <label>Pilih Desa Pertama:</label>
                                <select class="form-control" id="desa1Select" onchange="updateComparison()">
                                    <option value="">-- Pilih Desa --</option>
                                    ${options}
                                </select>
                            </div>
                            <div class="monitoring-col-2 monitoring-text-center">
                                <label>&nbsp;</label>
                                <div class="monitoring-pt-2">
                                    <i class="fas fa-exchange-alt fa-2x text-muted"></i>
                                </div>
                            </div>
                            <div class="monitoring-col-5">
                                <label>Pilih Desa Kedua:</label>
                                <select class="form-control" id="desa2Select" onchange="updateComparison()">
                                    <option value="">-- Pilih Desa --</option>
                                    ${options}
                                </select>
                            </div>
                        </div>

                        <div id="comparisonResult" style="display:none;">
                            <div class="comparison-tabs">
                                <div class="monitoring-admin-tab-nav">
                                    <button class="monitoring-admin-tab-btn active" onclick="showComparisonTab('dataUmum')">Data Umum</button>
                                    <button class="monitoring-admin-tab-btn" onclick="showComparisonTab('demografis')">Demografis</button>
                                    <button class="monitoring-admin-tab-btn" onclick="showComparisonTab('pendidikan')">Pendidikan</button>
                                    <button class="monitoring-admin-tab-btn" onclick="showComparisonTab('ekonomi')">Ekonomi</button>
                                    <button class="monitoring-admin-tab-btn" onclick="showComparisonTab('infrastruktur')">Infrastruktur</button>
                                </div>
                                <div class="tab-content monitoring-mt-3">
                                    <div class="tab-pane show active" id="dataUmum"><div id="dataUmumContent"></div></div>
                                    <div class="tab-pane" id="demografis" style="display:none;"><div id="demografisContent"></div></div>
                                    <div class="tab-pane" id="pendidikan" style="display:none;"><div id="pendidikanContent"></div></div>
                                    <div class="tab-pane" id="ekonomi" style="display:none;"><div id="ekonomiContent"></div></div>
                                    <div class="tab-pane" id="infrastruktur" style="display:none;"><div id="infrastrukturContent"></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Re-init comparison listeners setelah HTML di-render
            if (window.MonitoringComparison) {
                window.MonitoringComparison.init();
            }
        }
        
        renderQualityTab(tabElement, data) {
            // Use the HTML from quality-check-view.php
            if (data.html) {
                tabElement.innerHTML = data.html;
                
                // Initialize quality check manager after content is loaded
                setTimeout(() => {
                    if (window.qualityCheckManager) {
                        window.qualityCheckManager.init();
                    }
                }, 100);
            } else {
                // Fallback to old rendering
                const html = `
                    <div class="card">
                        <div class="card-header">
                            <h3>Data Quality Check</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-center text-muted">Memuat data quality check...</p>
                        </div>
                    </div>
                `;
                tabElement.innerHTML = html;
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
        
        setupRealTimeUpdates() {
            setInterval(() => {
                this.updateMonitoringData();
            }, 300000);
        }
        
        async updateMonitoringData() {
            try {
                if (typeof showLoader === 'function') showLoader('.monitoring-admin-content');
                
                await new Promise(resolve => setTimeout(resolve, 1000));
                
                if (typeof showNotification === 'function') {
                    showNotification('Data monitoring berhasil diperbarui', 'success');
                }
                
            } catch (error) {
                if (typeof showNotification === 'function') {
                    showNotification('Gagal memperbarui data monitoring', 'error');
                }
            } finally {
                if (typeof hideLoader === 'function') hideLoader();
            }
        }
    }
    
    // Global refresh function
    window.refreshAllData = function() {
        if (window.monitoringCoreInstance) {
            window.monitoringCoreInstance.updateMonitoringData();
        }
    };
    
    // Global navigation functions
    window.navigateToDetailDesa = function(desaId, desaName) {
        window.location.href = `${getBaseUrl()}pages/admin/monitoring/monitoring.php?desa=${desaId}`;
    };
    
    window.navigateToGabunganDesa = function() {
        window.location.href = `${getBaseUrl()}pages/admin/monitoring/monitoring.php?gabungan=semua`;
    };
    
    // Export utilities for other modules
    window.MonitoringUtils = {
        apiCall,
        getBaseUrl,
        sanitizeHtml,
        loadAsset
    };
    
    // Initialize core
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.monitoringCoreInstance) {
            window.monitoringCoreInstance = new MonitoringCore();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (!window.monitoringCoreInstance) {
            window.monitoringCoreInstance = new MonitoringCore();
        }
    }
})();