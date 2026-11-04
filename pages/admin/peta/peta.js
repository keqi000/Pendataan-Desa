// Peta Main Entry Point
(function() {
    'use strict';
    
    class PetaMain {
        constructor() {
            if (document.querySelector('.peta-admin-container')) {
                this.init();
            }
        }
        
        init() {
            // All functionality is now handled by separate modules:
            // - peta-core.js: Core utilities and main class
            // - peta-data.js: Data loading and marker management
            // - peta-boundary.js: Boundary and layer management
            // - peta-table.js: Table functionality and map interaction
            // - peta-upload.js: Upload functionality
            
            // This file only handles final initialization
            this.initializeModules();
            this.addCustomStyles();
        }
        
        initializeModules() {
            // Ensure all modules are loaded and initialized
            setTimeout(() => {
                if (window.petaAdmin && window.PetaData && window.PetaBoundary && window.PetaTable && window.PetaUpload) {
                    // All modules loaded successfully
                }
            }, 100);
        }
        
        addCustomStyles() {
            if (!document.getElementById('peta-custom-styles')) {
                const style = document.createElement('style');
                style.id = 'peta-custom-styles';
                style.textContent = `
                    .custom-marker {
                        background: white;
                        border-radius: 50%;
                        border: 2px solid #fff;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 16px;
                        color: white;
                    }
                    
                    .facility-marker {
                        background: #e74c3c;
                    }
                    
                    .bridge-marker {
                        background: #f39c12;
                    }
                    
                    @keyframes bounce {
                        0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
                        40% { transform: translateY(-10px); }
                        60% { transform: translateY(-5px); }
                    }
                    
                    .popup-content h5 {
                        color: #112D4E;
                        margin-bottom: 12px;
                        font-weight: 600;
                    }
                    
                    .popup-content p {
                        margin: 4px 0;
                        font-size: 14px;
                    }
                    
                    .kecamatan-label, .kabupaten-label, .desa-label {
                        background: none !important;
                        border: none !important;
                        box-shadow: none !important;
                    }
                    
                    .label-text {
                        background: rgba(255, 255, 255, 0.9);
                        border: 2px solid #2980b9;
                        border-radius: 4px;
                        padding: 4px 8px;
                        font-weight: bold;
                        font-size: 12px;
                        color: #2980b9;
                        text-align: center;
                        white-space: nowrap;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                    }
                    
                    .label-text-kabupaten {
                        background: rgba(255, 255, 255, 0.9);
                        border: 2px solid #e67e22;
                        border-radius: 4px;
                        padding: 6px 10px;
                        font-weight: bold;
                        font-size: 14px;
                        color: #e67e22;
                        text-align: center;
                        white-space: nowrap;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                    }
                    
                    .label-text-desa {
                        background: rgba(255, 255, 255, 0.9);
                        border: 1px solid #1abc9c;
                        border-radius: 3px;
                        padding: 2px 6px;
                        font-weight: bold;
                        font-size: 10px;
                        color: #1abc9c;
                        text-align: center;
                        white-space: nowrap;
                        box-shadow: 0 1px 2px rgba(0,0,0,0.2);
                    }
                `;
                document.head.appendChild(style);
            }
        }
    }
    
    // Initialize main peta
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.petaMainInstance) {
            window.petaMainInstance = new PetaMain();
        }
    });
    
    if (document.readyState !== 'loading') {
        if (!window.petaMainInstance) {
            window.petaMainInstance = new PetaMain();
        }
    }
})();