// UMKM Category JS - Sistem Informasi Publik
// File: public-portal/pages/umkm/js/umkm-category.js
// Highlight active category card sesuai filter aktif

(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        const params = new URLSearchParams(window.location.search);
        const activeJenis = params.get('jenis');
        if (!activeJenis) return;

        document.querySelectorAll('.summary-card').forEach(card => {
            if (card.dataset.jenis === activeJenis) card.classList.add('active');
        });
    });
})();
