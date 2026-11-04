// Dashboard Core Functions
(function() {
    'use strict';
    
    // API Helper function with URL validation
    function validateUrl(url) {
        try {
            const urlObj = new URL(url);
            const allowedHosts = ['localhost', '127.0.0.1', window.location.hostname];
            return allowedHosts.includes(urlObj.hostname) || urlObj.hostname.endsWith('.local');
        } catch {
            return false;
        }
    }
    
    async function apiCall(url, options = {}) {
        if (!validateUrl(url)) {
            throw new Error('Invalid URL');
        }
        const response = await fetch(url, options);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return await response.json();
    }
    
    function getBaseUrl() {
        return window.BASE_URL || (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' 
            ? window.location.origin + '/Pendataan-desa/' 
            : window.location.origin + '/');
    }
    
    class DashboardCore {
        constructor() {
            if (document.querySelector('.dashboard-admin-container')) {
                this.init();
            }
        }
        
        init() {
            this.setupQuickActions();
            this.loadDashboardData();
            
            setTimeout(() => {
                this.initCharts();
            }, 300);
        }
        
        setupQuickActions() {
            const actionBtns = document.querySelectorAll('.dashboard-admin-action-btn');
            actionBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const href = btn.getAttribute('href');
                    if (href) {
                        window.location.href = href;
                    }
                });
            });
        }
        
        loadDashboardData() {
            this.updateStats();
        }
        
        updateStats() {
            // Update dashboard statistics
        }
        
        initCharts() {
            if (!window.trendData || !window.monthlyData) {
                this.fetchChartData().then(() => {
                    this.proceedWithCharts();
                });
            } else {
                this.proceedWithCharts();
            }
        }
        
        async fetchChartData() {
            // Use existing data from PHP instead of AJAX
            if (!window.trendData) {
                window.trendData = { desa: 0, penduduk: 0, pendidikan: 0, umkm: 0, jalan: 0 };
            }
            if (!window.monthlyData) {
                window.monthlyData = {};
            }
        }
        
        proceedWithCharts() {
            if (typeof Chart === 'undefined') {
                this.loadChartJS().then(() => {
                    if (window.DashboardCharts) {
                        window.DashboardCharts.createTrendCharts();
                    }
                });
            } else {
                if (window.DashboardCharts) {
                    window.DashboardCharts.createTrendCharts();
                }
            }
        }
        
        async loadChartJS() {
            return new Promise((resolve) => {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                script.onload = resolve;
                document.head.appendChild(script);
            });
        }
    }
    
    // Global function to reinitialize dashboard
    window.reinitializeDashboard = function() {
        if (window.dashboardCoreInstance) {
            window.dashboardCoreInstance.init();
        } else {
            window.dashboardCoreInstance = new DashboardCore();
        }
    };
    
    // Export utilities for other modules
    window.DashboardUtils = {
        apiCall,
        getBaseUrl,
        validateUrl
    };
    
    // Initialize dashboard core
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.dashboardCoreInstance) {
            window.dashboardCoreInstance = new DashboardCore();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (!window.dashboardCoreInstance) {
            window.dashboardCoreInstance = new DashboardCore();
        }
    }
})();