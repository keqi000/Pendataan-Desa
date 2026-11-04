// Desa Statistics JS - Sistem Informasi Publik
// File: public-portal/pages/desa-detail/js/desa-statistics.js

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        // Animate progress bars
        document.querySelectorAll('.bar-fill').forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => { bar.style.width = width; }, 300);
        });
    });
})();
