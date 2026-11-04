// Detail Analisis Navigation Functions
(function() {
    'use strict';
    
    function loadDetailAnalisisAssets() {
        const baseUrl = window.location.origin + '/Pendataan-desa/';
        
        return new Promise((resolve) => {
            let assetsLoaded = 0;
            const totalAssets = 2;
            
            function checkComplete() {
                assetsLoaded++;
                if (assetsLoaded === totalAssets) {
                    resolve();
                }
            }
            
            const cssId = 'detail-analisis-css';
            if (!document.getElementById(cssId)) {
                const link = document.createElement('link');
                link.id = cssId;
                link.rel = 'stylesheet';
                link.href = `${baseUrl}pages/admin/analisis/css/detail-analisis.css`;
                link.onload = checkComplete;
                link.onerror = checkComplete;
                document.head.appendChild(link);
            } else {
                checkComplete();
            }
            
            const jsId = 'detail-analisis-js';
            if (!document.getElementById(jsId)) {
                const script = document.createElement('script');
                script.id = jsId;
                script.src = `${baseUrl}pages/admin/analisis/js/detail-analisis.js`;
                script.onload = () => {
                    if (typeof DetailAnalisis !== 'undefined' && DetailAnalisis.init) {
                        DetailAnalisis.init();
                        window.detailAnalisisInstance = DetailAnalisis;
                    }
                    checkComplete();
                };
                script.onerror = checkComplete;
                document.head.appendChild(script);
            } else {
                checkComplete();
            }
        });
    }
    
    window.openDetailAnalisis = function(type) {
        const selectedDesa = document.getElementById('selectedDesaId').value || 'all';
        const baseUrl = window.location.origin + '/Pendataan-desa/';
        const url = `${baseUrl}pages/admin/analisis/detail-analisis.php?desa=${selectedDesa}&type=${type}`;
        
        window.location.href = url;
    };
    

})();