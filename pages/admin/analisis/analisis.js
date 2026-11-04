// Analisis Main Entry Point
(function() {
    'use strict';
    
    class AnalisisMain {
        constructor() {
            if (document.querySelector('.analisis-admin-container')) {
                this.init();
            }
        }
        
        init() {
            // Initialize modules after core is ready
            this.initializeModules();
        }
        
        initializeModules() {
            setTimeout(() => {
                // Handle pending predictions from session storage
                const pendingPredictions = sessionStorage.getItem('pendingPredictions');
                if (pendingPredictions && window.AnalisisPrediksi) {
                    const predictions = JSON.parse(pendingPredictions);
                    sessionStorage.removeItem('pendingPredictions');
                    
                    setTimeout(() => {
                        window.AnalisisPrediksi.updatePredictionSection(predictions);
                    }, 1000);
                }
                
                // Initialize charts for the initially active tab only
                this.initializeActiveTab();
            }, 500);
        }
        
        initializeActiveTab() {
            const activeTab = document.querySelector('.analisis-admin-tab-content.active');
            if (activeTab && activeTab.getAttribute('data-loaded') === 'true') {
                const tabId = activeTab.id;
                
                // Only initialize if tab is actually loaded
                setTimeout(() => {
                    if (tabId === 'prediksi' && window.AnalisisPrediksi) {
                        window.AnalisisPrediksi.init();
                    } else if (tabId === 'tingkat1' && window.AnalisisTingkat1) {
                        window.AnalisisTingkat1.init();
                    } else if (tabId === 'tingkat2' && window.AnalisisTingkat2) {
                        window.AnalisisTingkat2.init();
                    }
                }, 200);
            }
        }
    }
    
    // Initialize main analisis
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.analisisMainInstance) {
            window.analisisMainInstance = new AnalisisMain();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (!window.analisisMainInstance) {
            window.analisisMainInstance = new AnalisisMain();
        }
    }
    
    // Global function to reinitialize when navigating back
    window.reinitializeAnalisis = function() {
        if (window.analisisMainInstance) {
            window.analisisMainInstance.initializeActiveTab();
        } else {
            window.analisisMainInstance = new AnalisisMain();
        }
    };
})();