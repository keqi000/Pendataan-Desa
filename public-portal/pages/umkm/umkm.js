// UMKM JS - Sistem Informasi Publik
// File: public-portal/pages/umkm/umkm.js

(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        // Animate cards
        document.querySelectorAll('.umkm-card').forEach((card, i) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = `opacity 0.4s ease ${i * 0.05}s, transform 0.4s ease ${i * 0.05}s`;
            setTimeout(() => { card.style.opacity = '1'; card.style.transform = 'translateY(0)'; }, 100);
        });

        // Klik summary card → filter by jenis
        document.querySelectorAll('.summary-card').forEach(card => {
            card.addEventListener('click', function() {
                const jenis = this.dataset.jenis;
                const select = document.querySelector('select[name="jenis"]');
                if (select) {
                    select.value = jenis;
                    select.closest('form').submit();
                }
            });
        });
    });
})();
