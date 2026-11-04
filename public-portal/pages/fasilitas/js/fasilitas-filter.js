// Fasilitas Filter JS - Sistem Informasi Publik
// File: public-portal/pages/fasilitas/js/fasilitas-filter.js

(function() {
    'use strict';
    // Client-side search pada tabel
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('fasilitasSearch');
        if (!searchInput) return;

        searchInput.addEventListener('input', PublicUtils.debounce(function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.table tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        }, 300));
    });
})();
