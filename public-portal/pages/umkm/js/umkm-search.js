// UMKM Search JS - Sistem Informasi Publik
// File: public-portal/pages/umkm/js/umkm-search.js

(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.querySelector('input[name="search"]');
        if (!input) return;

        // Submit form on Enter
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') this.closest('form').submit();
        });
    });
})();
