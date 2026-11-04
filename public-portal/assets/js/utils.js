// Utility Functions - Sistem Informasi Publik
// File: public-portal/assets/js/utils.js

(function() {
    'use strict';

    window.PublicUtils = window.PublicUtils || {};

    Object.assign(window.PublicUtils, {

        // Format angka ribuan: 43336 → "43.336"
        formatNumber: function(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        },

        // Format tanggal: "2024-01-15" → "15 Januari 2024"
        formatDate: function(dateString) {
            return new Date(dateString).toLocaleDateString('id-ID', {
                year: 'numeric', month: 'long', day: 'numeric'
            });
        },

        // Hitung persentase
        calcPercent: function(value, total) {
            if (!total) return 0;
            return ((value / total) * 100).toFixed(1);
        },

        // Truncate teks panjang
        truncate: function(str, maxLength = 100) {
            if (!str || str.length <= maxLength) return str;
            return str.substring(0, maxLength) + '...';
        },

        // Slug dari nama desa: "Isimu Selatan" → "isimu_selatan"
        toSlug: function(str) {
            return str.toLowerCase().replace(/\s+/g, '_');
        },

        // Nama dari slug: "isimu_selatan" → "Isimu Selatan"
        fromSlug: function(str) {
            return str.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        },

        // Debounce
        debounce: function(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        },

        // Get URL param
        getUrlParam: function(param) {
            return new URLSearchParams(window.location.search).get(param);
        },

        // Set URL param tanpa reload
        setUrlParam: function(param, value) {
            const url = new URL(window.location);
            url.searchParams.set(param, value);
            window.history.pushState({}, '', url);
        },

        // Sanitize HTML
        sanitizeHtml: function(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },

        // Copy ke clipboard
        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showNotification('Berhasil disalin ke clipboard', 'success', 2000);
                });
            } else {
                const ta = document.createElement('textarea');
                ta.value = text;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                this.showNotification('Berhasil disalin ke clipboard', 'success', 2000);
            }
        },

        // Show loading spinner di dalam container
        showLoading: function(container) {
            const el = typeof container === 'string' ? document.querySelector(container) : container;
            if (el) el.innerHTML = `<div class="loading"><div class="spinner"></div><span style="margin-left:10px">Memuat data...</span></div>`;
        },

        // Hide loading spinner
        hideLoading: function(container) {
            const el = typeof container === 'string' ? document.querySelector(container) : container;
            if (el) { const l = el.querySelector('.loading'); if (l) l.remove(); }
        },

        // Show toast notification
        showNotification: function(message, type = 'info', duration = 3000) {
            document.querySelectorAll('.notification').forEach(n => n.remove());

            const colors = { success: '#28a745', error: '#dc3545', warning: '#ffc107', info: '#17a2b8' };
            const n = document.createElement('div');
            n.className = 'notification';
            n.innerHTML = `<span>${message}</span><button onclick="this.parentElement.remove()" style="background:none;border:none;color:white;cursor:pointer;margin-left:10px;font-size:16px">&times;</button>`;
            n.style.cssText = `position:fixed;top:20px;right:20px;z-index:9999;padding:12px 18px;border-radius:6px;color:white;font-weight:500;box-shadow:0 4px 12px rgba(0,0,0,0.15);display:flex;align-items:center;background:${colors[type]||colors.info};transform:translateX(110%);transition:transform 0.3s ease;max-width:380px`;
            document.body.appendChild(n);

            setTimeout(() => n.style.transform = 'translateX(0)', 50);
            setTimeout(() => { n.style.transform = 'translateX(110%)'; setTimeout(() => n.remove(), 300); }, duration);
        },

        // API call helper
        apiCall: async function(endpoint, options = {}) {
            const url = (window.PublicPortalConfig?.apiUrl || '/public-portal/api/') + endpoint;
            try {
                const res = await fetch(url, {
                    method: 'GET',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    ...options
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return await res.json();
            } catch (err) {
                console.error('API call failed:', err);
                throw err;
            }
        }
    });

})();
