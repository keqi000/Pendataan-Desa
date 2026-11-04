// Desa Detail JS - Sistem Informasi Publik
// File: public-portal/pages/desa-detail/desa-detail.js

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        // Animate stat cards on load
        document.querySelectorAll('.desa-stat-card').forEach((card, i) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = `opacity 0.5s ease ${i * 0.08}s, transform 0.5s ease ${i * 0.08}s`;
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        });
    });
})();
