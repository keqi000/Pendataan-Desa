// Dashboard Main Entry Point
(function() {
    'use strict';
    
    class DashboardMain {
        constructor() {
            if (document.querySelector('.dashboard-admin-container')) {
                this.init();
            }
        }
        
        init() {
            // All functionality is now handled by separate modules:
            // - dashboard-core.js: Core utilities and main class
            // - dashboard-charts.js: Chart functionality
            // - dashboard-overview.js: Overview modal functions
            
            // This file only handles final initialization
            this.initializeModules();
        }
        
        initializeModules() {
            // Ensure all modules are loaded and initialized
            setTimeout(() => {
                if (window.dashboardCoreInstance && window.DashboardCharts && window.DashboardOverview) {
                    console.log('All dashboard modules loaded successfully');
                }
            }, 100);
        }
    }
    
    // Initialize main dashboard
    const initDashboard = () => {
        if (document.querySelector('.dashboard-admin-container')) {
            if (!window.dashboardMainInstance) {
                window.dashboardMainInstance = new DashboardMain();
            } else {
                window.dashboardMainInstance.init();
            }
        }
    };
    
    // Multiple initialization methods
    document.addEventListener('DOMContentLoaded', initDashboard);
    
    if (document.readyState !== 'loading') {
        initDashboard();
    }
    
    // Also initialize when page becomes visible (for SPA navigation)
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && document.querySelector('.dashboard-admin-container')) {
            setTimeout(initDashboard, 100);
        }
    });
})();