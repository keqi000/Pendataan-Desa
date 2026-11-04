// Monitoring Main Entry Point
(function() {
    'use strict';
    
    class MonitoringMain {
        constructor() {
            if (document.querySelector('.monitoring-admin-container')) {
                this.init();
            }
        }
        
        init() {
            // All functionality is now handled by separate modules:
            // - monitoring-core.js: Core utilities and main class
            // - monitoring-status.js: Status tab functions
            // - monitoring-comparison.js: Comparison tab functions
            
            // This file only handles final initialization
            this.initializeModules();
        }
        
        initializeModules() {
            // Ensure all modules are loaded and initialized
            setTimeout(() => {
                if (window.monitoringCoreInstance && window.MonitoringStatus && window.MonitoringComparison) {
                    console.log('All monitoring modules loaded successfully');
                }
            }, 100);
        }
    }
    
    // Initialize main monitoring
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.monitoringMainInstance) {
            window.monitoringMainInstance = new MonitoringMain();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (!window.monitoringMainInstance) {
            window.monitoringMainInstance = new MonitoringMain();
        }
    }
})();