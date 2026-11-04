// Pengaturan Main Entry Point
(function() {
    'use strict';
    
    class PengaturanMain {
        constructor() {
            if (document.querySelector('.pengaturan-container')) {
                this.init();
            }
        }
        
        init() {
            // All functionality is now handled by separate modules:
            // - pengaturan-core.js: Core utilities, tab switching, password change
            // - pengaturan-faq.js: FAQ tab functions
            // - pengaturan-users.js: User management tab functions
            // - pengaturan-desa.js: Desa management tab functions
            
            // This file only handles final initialization
            this.initializeModules();
        }
        
        initializeModules() {
            // Ensure all modules are loaded and initialized
            setTimeout(() => {
                if (window.pengaturanCoreInstance && window.PengaturanFAQ && window.PengaturanUsers && window.PengaturanDesa && window.pengaturanPekerjaanManager) {
                    // All modules loaded successfully
                }
            }, 100);
        }
    }
    
    // Initialize main pengaturan
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.pengaturanMainInstance) {
            window.pengaturanMainInstance = new PengaturanMain();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (!window.pengaturanMainInstance) {
            window.pengaturanMainInstance = new PengaturanMain();
        }
    }
})();